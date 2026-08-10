<?php

namespace App\Http\Controllers;

use App\Models\AddProspectContact;
use App\Models\Client;
use App\Models\ClientTicket;
use App\Models\Prospect;
use App\Models\TaskAssignedPersons;
use App\Models\Tasks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientTicketController extends Controller
{
    /**
     * Add a new ticket
     */
    public function addTicket(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
            'issue_id' => 'nullable|integer',
            'priority_id' => 'nullable|integer',
            'is_urgent' => 'nullable|boolean',
            'category' => 'nullable|string',
            'attachment' => 'nullable|string',
        ]);

        $ticket = ClientTicket::create([
            'client_id' => $request->client_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'type' => $request->type,
            'issue_id' => $request->issue_id,
            'ticket_code' => 'TCK-' . date('Ymd') . '-' . Str::upper(Str::random(5)),
            'status' => 'open',
            'createdBy' => 'client',
            'priority_id' => $request->priority_id,
            'is_urgent' => $request->is_urgent ?? false,
            'category' => $request->category,
            'attachment' => $request->attachment,
            'is_completed' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket created successfully',
            'data' => $ticket
        ], 201);
    }

    /**
     * Get all tickets
     */
    public function getAllTicket()
    {
        $tickets = ClientTicket::with('client.prospect', 'priority', 'convertedTask')->latest()->get();
        return response()->json([
            'status' => 'success',
            'message' => 'All tickets retrieved successfully',
            'data' => $tickets
        ], 200);
    }

    public function externalTicketWebhook(Request $request)
    {
        $secret = env('EXTERNAL_TICKET_WEBHOOK_SECRET');

        if ($secret && $request->header('X-Webhook-Secret') !== $secret) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid webhook secret.',
            ], 401);
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'source' => 'nullable|string|max:100',
            'external_ticket_id' => 'nullable|string|max:255',
            'ticket_id' => 'nullable|string|max:255',
            'client_id' => 'nullable|integer',
            'external_client_id' => 'nullable|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'priority_id' => 'nullable|integer',
            'priority' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:255',
            'attachment' => 'nullable|string',
        ]);

        $source = $request->input('source', 'external');
        $externalTicketId = $request->input('external_ticket_id', $request->input('ticket_id'));
        $clientMatch = $this->matchClient($request);
        $ticketCode = $externalTicketId ? Str::limit($source . '-' . $externalTicketId, 250, '') : 'EXT-' . date('Ymd') . '-' . Str::upper(Str::random(6));

        $payload = [
            'client_id' => $clientMatch['client_id'],
            'subject' => $request->subject,
            'description' => $request->description,
            'type' => $request->input('type', 'external_ticket'),
            'issue_id' => $request->issue_id,
            'ticket_code' => $ticketCode,
            'status' => $request->input('status', 'open'),
            'createdBy' => $source,
            'source' => $source,
            'external_ticket_id' => $externalTicketId,
            'external_client_id' => $request->external_client_id,
            'external_client_name' => $request->client_name,
            'external_client_email' => $request->client_email,
            'external_client_phone' => $request->client_phone,
            'match_status' => $clientMatch['client_id'] ? $clientMatch['match_status'] : 'unmatched',
            'matched_by' => $clientMatch['matched_by'],
            'priority_id' => $request->priority_id,
            'external_priority' => $request->priority,
            'external_status' => $request->status,
            'is_urgent' => $request->boolean('is_urgent'),
            'category' => $request->category,
            'attachment' => $request->attachment,
            'is_completed' => false,
            'raw_payload' => $request->all(),
            'last_synced_at' => now(),
        ];

        $ticket = $externalTicketId
            ? ClientTicket::updateOrCreate(
                ['source' => $source, 'external_ticket_id' => $externalTicketId],
                $payload
            )
            : ClientTicket::create($payload);

        return response()->json([
            'status' => 'success',
            'message' => 'External ticket received successfully.',
            'data' => $ticket->load('client.prospect', 'priority'),
        ], 200);
    }

    public function getUnmatchedTickets()
    {
        $tickets = ClientTicket::where('match_status', 'unmatched')
            ->with('priority')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Unmatched tickets retrieved successfully',
            'data' => $tickets
        ], 200);
    }

    public function matchTicketClient(Request $request, $ticketId)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
        ]);

        $ticket = ClientTicket::findOrFail($ticketId);
        $ticket->client_id = $request->client_id;
        $ticket->match_status = 'manual_matched';
        $ticket->matched_by = 'manual';
        $ticket->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket client matched successfully',
            'data' => $ticket->load('client.prospect', 'priority')
        ], 200);
    }

    public function convertTicketToTask(Request $request, $ticketId)
    {
        $request->validate([
            'priority_id' => 'required|exists:priorities,id',
            'task_type_id' => 'required|exists:task_types,id',
            'status_id' => 'required|exists:task_statuses,id',
            'department_id' => 'required|exists:departments,id',
            'created_by' => 'required|exists:users,id',
            'assigned_person' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'project_id' => 'nullable|exists:projects,id',
            'project_phase_id' => 'nullable|integer',
            'task_title' => 'nullable|string|max:255',
            'task_details' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $ticketId) {
            $ticket = ClientTicket::with('client')->lockForUpdate()->findOrFail($ticketId);

            if ($ticket->converted_task_id) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'This ticket is already converted to a task.',
                    'data' => $ticket,
                ], 400);
            }

            $task = Tasks::create([
                'task_title' => $request->input('task_title', $ticket->subject),
                'task_details' => $request->input('task_details', $ticket->description),
                'priority_id' => $request->priority_id,
                'task_type_id' => $request->task_type_id,
                'is_remind' => $request->boolean('is_remind'),
                'is_waiting' => $request->boolean('is_waiting'),
                'due_date' => $request->due_date,
                'start_date' => $request->start_date,
                'project_id' => $request->project_id,
                'project_phase_id' => $request->project_phase_id,
                'prospect_id' => optional($ticket->client)->prospect_id,
                'status_id' => $request->status_id,
                'department_id' => $request->department_id,
                'created_by' => $request->created_by,
                'show_completion_percentage' => $request->boolean('show_completion_percentage'),
                'completion_percentage' => $request->input('completion_percentage', 0),
            ]);

            if ($request->filled('assigned_person')) {
                TaskAssignedPersons::create([
                    'assigned_person' => $request->assigned_person,
                    'assigned_by' => $request->created_by,
                    'is_main' => true,
                    'task_id' => $task->id,
                ]);
            }

            $ticket->converted_task_id = $task->id;
            $ticket->status = 'in_progress';
            $ticket->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Ticket converted to task successfully.',
                'data' => [
                    'ticket' => $ticket->load('client.prospect', 'priority', 'convertedTask'),
                    'task' => $task->load('priority', 'taskType', 'status', 'assignedPersons'),
                ],
            ], 200);
        });
    }

    /**
     * Get tickets by client
     */
    public function getTicketByClient($clientId)
    {
        $tickets = ClientTicket::where('client_id', $clientId)
            ->with('priority')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Tickets retrieved successfully',
            'data' => $tickets
        ], 200);
    }

    /**
     * Change status of a ticket
     */
    public function changeStatus(Request $request, $ticketId)
    {
        $request->validate([
            'status' => 'required|string|in:open,in_progress,resolved,closed',
        ]);

        $ticket = ClientTicket::findOrFail($ticketId);
        $ticket->status = $request->status;
        $ticket->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket status updated',
            'data' => $ticket
        ], 200);
    }

    /**
     * Update ticket details
     */
    public function updateTicket(Request $request, $ticketId)
    {
        $ticket = ClientTicket::findOrFail($ticketId);

        $ticket->update($request->only([
            'subject',
            'description',
            'type',
            'issue_id',
            'priority_id',
            'is_urgent',
            'category',
            'attachment',
            'is_completed',
            'source',
            'external_ticket_id',
            'external_client_id',
            'external_client_name',
            'external_client_email',
            'external_client_phone',
            'match_status',
            'matched_by',
            'converted_task_id',
            'external_priority',
            'external_status',
            'raw_payload',
            'last_synced_at',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket updated successfully',
            'data' => $ticket
        ], 200);
    }

    /**
     * Delete a ticket
     */
    public function deleteTicket($ticketId)
    {
        $ticket = ClientTicket::findOrFail($ticketId);
        $ticket->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket deleted successfully'
        ], 200);
    }

    private function matchClient(Request $request): array
    {
        if ($request->filled('client_id')) {
            $client = Client::find($request->client_id);
            if ($client) {
                return ['client_id' => $client->id, 'match_status' => 'matched', 'matched_by' => 'client_id'];
            }
        }

        if ($request->filled('external_client_id')) {
            $client = Client::where('client_code', $request->external_client_id)->first();
            if ($client) {
                return ['client_id' => $client->id, 'match_status' => 'matched', 'matched_by' => 'external_client_id'];
            }
        }

        if ($request->filled('client_email')) {
            $contact = AddProspectContact::where('email', $request->client_email)->first();
            if ($contact) {
                $client = Client::where('prospect_id', $contact->prospect_id)->first();
                if ($client) {
                    return ['client_id' => $client->id, 'match_status' => 'matched', 'matched_by' => 'email'];
                }
            }
        }

        if ($request->filled('client_phone')) {
            $contact = AddProspectContact::where('mobile', $request->client_phone)->first();
            if ($contact) {
                $client = Client::where('prospect_id', $contact->prospect_id)->first();
                if ($client) {
                    return ['client_id' => $client->id, 'match_status' => 'matched', 'matched_by' => 'phone'];
                }
            }
        }

        if ($request->filled('client_name')) {
            $prospect = Prospect::where('prospect_name', $request->client_name)->first();
            if ($prospect) {
                $client = Client::where('prospect_id', $prospect->id)->first();
                if ($client) {
                    return ['client_id' => $client->id, 'match_status' => 'matched', 'matched_by' => 'name'];
                }
            }
        }

        return ['client_id' => null, 'match_status' => 'unmatched', 'matched_by' => null];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ClientTicket;
use Illuminate\Http\Request;
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
            '' => 'nullable|integer',
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
        $tickets = ClientTicket::with('client', 'priority')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'All tickets retrieved successfully',
            'data' => $tickets
        ], 200);
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
}

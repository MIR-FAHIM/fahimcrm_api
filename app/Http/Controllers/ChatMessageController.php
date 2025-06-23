<?php

namespace App\Http\Controllers;


use Exception;
use App\Models\ChatMessage;
use App\Models\ConversationRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatMessageController extends Controller
{
    // Add a conversation room
    public function addConversationRoom(Request $request)
    {
        try {
            $validated = $request->validate([
                'prospect_id' => 'nullable|integer',
                'general_id' => 'nullable|integer',
                'project_id' => 'nullable|integer',
                'client_id' => 'nullable|integer',
                'type' => 'nullable|string',
                'room_name' => 'required|string',
                'cover_photo' => 'nullable|string',
            ]);

            $room = ConversationRoom::create($validated);
            ChatMessage::create([
                'conversation_room_id' => $room->id,
                'message' => 'Start Your Conversation here',
                'message_type' => 'text',
                'sender_id' => 1, // or a system/admin user ID
                'is_read' => false,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Conversation room created successfully.',
                'data' => $room
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create conversation room.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get conversation rooms (optionally filtered)
    public function getConversationRoom()
    {
        try {
            $rooms = ConversationRoom::all();

            

            return response()->json([
                'status' => 'success',
                'data' => $rooms
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch conversation rooms.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addChat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_room_id' => 'required|exists:conversation_rooms,id',
            'message' => 'required_if:message_type,text',
            'message_type' => 'required|in:text,image,file,audio,video',
            'file' => 'required_if:message_type,image,file,audio,video|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $senderId = $request->sender_id;

        $messageData = [
            'conversation_room_id' => $request->conversation_room_id,
            'sender_id' => $senderId,
            'message_type' => $request->message_type ?? 'text',
            'message' => $request->message ?? null,
            'is_read' => false,
            'is_delivered' => false,
            'is_seen' => false,
        ];

        // Handle file upload if present
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('chat_files', $fileName, 'public');

            $messageData['file_path'] = $filePath;
            $messageData['file_name'] = $file->getClientOriginalName();
            $messageData['file_size'] = $file->getSize();
        }

        $message = ChatMessage::create($messageData);

        // Broadcast the message to other participants
        // You would implement this based on your broadcasting system

        return response()->json([
            'status' => 'success',
            'message' => 'Chat message added successfully',
            'data' => $message
        ], 201);
    }

    public function getChatByConversationId(Request $request)
    {
        // Validate the conversation room exists

        try{
            $conversationRoom = ConversationRoom::find($request->conversation_room_id);

            if (!$conversationRoom) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Conversation room not found'
                ], 404);
            }
    
            // Get the current user ID
            $currentUserId = $request->user_id;
    
            // Get messages with sender information
            $messages = ChatMessage::with(['sender', 'receiver'])
                ->where('conversation_room_id', $request->conversation_room_id)
                ->orderBy('created_at', 'asc')
                ->get();
    
            // Mark messages as read if they were sent to the current user
            $unreadMessages = $messages->where('receiver_id', $currentUserId)
                ->where('is_read', false);
    
            foreach ($unreadMessages as $message) {
                $message->markAsRead();
            }
    
            return response()->json([
                'status' => 'success',
                'message' => 'Chat messages retrieved successfully',
                'data' => $messages
            ]);
        }
        catch(Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                
            ]);
        }
       
    }
    public function getChatByProjectId(Request $request)
    {
        try {
            $conversationRoom = ConversationRoom::where('project_id', $request->project_id)->first();
    
            if (!$conversationRoom) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Conversation room not found',
                ], 404);
            }
    
            $conversationRoomId = $conversationRoom->id;
            $currentUserId = $request->user_id;
    
            $messages = ChatMessage::with(['sender', 'receiver'])
                ->where('conversation_room_id', $conversationRoomId)
                ->orderBy('created_at', 'asc')
                ->get();
    
            // Mark unread messages sent to the current user as read
            $unreadMessages = $messages->where('receiver_id', $currentUserId)
                ->where('is_read', false);
    
            foreach ($unreadMessages as $message) {
                $message->markAsRead();
            }
    
            return response()->json([
                'status' => 'success',
                'message' => 'Chat messages retrieved successfully',
                'data' => $messages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);
        }
    }
    
}

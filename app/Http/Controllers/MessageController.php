<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Messaging
 *
 * Direct messaging between users, with privacy enforcement.
 */
class MessageController extends Controller
{
    /**
     * Start Conversation
     *
     * Start a direct-message conversation with another user.
     * Returns the existing conversation if one already exists.
     * Respects the recipient's `messaging_privacy` setting.
     *
     * @bodyParam recipient_id integer required The ID of the user to message. Example: 2
     * @response 201 scenario="Created" {"conversation":{"id":1,"participants":[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]}}
     * @response 200 scenario="Existing conversation returned" {"conversation":{"id":1}}
     * @response 403 scenario="Messaging disabled" {"message":"This user has disabled messaging."}
     * @response 403 scenario="Unverified sender" {"message":"This user only accepts messages from verified users."}
     */
    public function startConversation(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|integer|exists:users,id|different:' . Auth::id(),
        ]);

        /** @var User $recipient */
        $recipient = User::findOrFail($request->recipient_id);

        // ── Privacy checks ────────────────────────────────────────────────────
        if ($recipient->messaging_privacy === 'disabled') {
            return response()->json([
                'message' => 'This user has disabled messaging.',
            ], 403);
        }

        if ($recipient->messaging_privacy === 'verified_only' && ! Auth::user()->is_verified) {
            return response()->json([
                'message' => 'This user only accepts messages from verified users.',
            ], 403);
        }

        // ── Find or create the conversation ───────────────────────────────────
        // Look for an existing 1-to-1 conversation shared by exactly these two users
        $existing = Conversation::whereHas('participants', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->whereHas('participants', function ($q) use ($request) {
                $q->where('user_id', $request->recipient_id);
            })
            ->first();

        if ($existing) {
            return response()->json(['conversation' => $existing->load('participants:id,name,username,avatar')]);
        }

        $conversation = Conversation::create([]);
        $conversation->participants()->attach([Auth::id(), $request->recipient_id]);

        return response()->json([
            'conversation' => $conversation->load('participants:id,name,username,avatar'),
        ], 201);
    }

    /**
     * List Conversations
     *
     * Return all conversations the authenticated user is part of, ordered by most recent activity.
     *
     * @response 200 scenario="Success" {"conversations":[{"id":1,"latest_message":{"body":"Hello!","created_at":"2026-03-27T10:00:00Z"},"participants":[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]}]}
     */
    public function getConversations(): JsonResponse
    {
        $conversations = Auth::user()
            ->conversations()
            ->with([
                'participants:id,name,username,avatar',
                'latestMessage.sender:id,name,username',
            ])
            ->latest('updated_at')
            ->get();

        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Get Messages
     *
     * Fetch all messages in a conversation. Unread messages from other participants are marked as read.
     *
     * @urlParam id integer required The conversation ID. Example: 1
     * @response 200 scenario="Success" {"messages":[{"id":1,"body":"Hello!","read_at":null,"sender":{"id":2,"name":"Bob"}}]}
     * @response 404 scenario="Not found" {"message":"Conversation not found."}
     */
    public function getMessages(int $conversationId): JsonResponse
    {
        $conversation = $this->findAuthorizedConversation($conversationId);

        if (! $conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        // Mark unread messages from other participants as read
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', Auth::id())
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,name,username,avatar')
            ->get();

        return response()->json(['messages' => $messages]);
    }

    /**
     * Send Message
     *
     * Send a message in an existing conversation.
     *
     * @urlParam id integer required The conversation ID. Example: 1
     * @bodyParam body string required The message content (max 2000 chars). Example: God bless you!
     * @response 201 scenario="Sent" {"message":{"id":5,"body":"God bless you!","created_at":"2026-03-27T10:05:00Z","sender":{"id":1,"name":"Alice"}}}
     * @response 404 scenario="Not found" {"message":"Conversation not found."}
     */
    public function sendMessage(Request $request, int $conversationId): JsonResponse
    {
        $conversation = $this->findAuthorizedConversation($conversationId);

        if (! $conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => Auth::id(),
            'body'            => $request->body,
        ]);

        // Touch the conversation so it bubbles to the top of conversation lists
        $conversation->touch();

        return response()->json([
            'message' => $message->load('sender:id,name,username,avatar'),
        ], 201);
    }

    /**
     * Retrieve a conversation only if the authenticated user is a participant.
     */
    private function findAuthorizedConversation(int $id): ?Conversation
    {
        return Auth::user()
            ->conversations()
            ->where('conversations.id', $id)
            ->first();
    }
}

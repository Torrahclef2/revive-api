<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Start a direct-message conversation with another user, or return the
     * existing one if it already exists between the two users.
     *
     * Enforces the recipient's messaging_privacy setting:
     *   - disabled      → no one can message them
     *   - verified_only → sender must be verified
     *   - everyone      → always allowed
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
     * List all conversations the authenticated user belongs to,
     * with the latest message preview included.
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
     * Fetch all messages for a conversation the authenticated user is part of.
     * Marks any unread messages (sent by others) as read.
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
     * Send a message in an existing conversation.
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

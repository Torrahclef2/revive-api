<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($filter = $request->get('filter')) {
            match ($filter) {
                'verified'   => $query->where('is_verified', true),
                'unverified' => $query->where('is_verified', false),
                'admin'      => $query->where('is_admin', true),
                default      => null,
            };
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['hostedSessions' => fn ($q) => $q->latest()->limit(5), 'groups']);
        return view('admin.users.show', compact('user'));
    }

    public function toggleVerified(User $user)
    {
        $user->update(['is_verified' => !$user->is_verified]);
        return back()->with('success', "User verification status updated.");
    }

    public function toggleAdmin(User $user)
    {
        // Prevent removing your own admin
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own admin status.');
        }
        $user->update(['is_admin' => !$user->is_admin]);
        return back()->with('success', "User admin status updated.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:user,mentor']);
        $user->update(['role' => $request->role]);
        return back()->with('success', "Role updated to {$request->role}.");
    }
}

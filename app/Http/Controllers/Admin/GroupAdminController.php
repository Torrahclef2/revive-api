<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Group::withCount('members')->with('owner');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $groups = $query->latest()->paginate(20)->withQueryString();

        return view('admin.groups.index', compact('groups'));
    }

    public function show(Group $group)
    {
        $group->load(['owner', 'users']);
        return view('admin.groups.show', compact('group'));
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return redirect()->route('admin.groups.index')->with('success', 'Group deleted.');
    }
}

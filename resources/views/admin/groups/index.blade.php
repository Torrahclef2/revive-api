@extends('admin.layout')

@section('title', 'Groups')
@section('breadcrumb')
    <li class="breadcrumb-item active">Groups</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Groups</h4>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search group name…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Search</button>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="ps-3">#</th>
                    <th>Name</th>
                    <th>Owner</th>
                    <th>Members</th>
                    <th>Created</th>
                    <th class="pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($groups as $group)
                <tr>
                    <td class="ps-3 text-muted">{{ $group->id }}</td>
                    <td>
                        <a href="{{ route('admin.groups.show', $group) }}" class="text-decoration-none fw-medium">{{ $group->name }}</a>
                    </td>
                    <td>
                        @if($group->owner)
                            <a href="{{ route('admin.users.show', $group->owner) }}" class="text-decoration-none text-muted small">
                                {{ $group->owner->name }}
                            </a>
                        @else
                            <span class="text-muted small">Unknown</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $group->members_count }}</span>
                    </td>
                    <td class="text-muted small">{{ $group->created_at->format('M d, Y') }}</td>
                    <td class="pe-3">
                        <a href="{{ route('admin.groups.show', $group) }}" class="btn btn-sm btn-outline-secondary py-0 px-2">View</a>
                        <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete group {{ addslashes($group->name) }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger py-0 px-2">Del</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No groups found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($groups->hasPages())
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center p-3">
        <small class="text-muted">Showing {{ $groups->firstItem() }}–{{ $groups->lastItem() }} of {{ $groups->total() }}</small>
        {{ $groups->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection

@extends('admin.layout')

@section('title', 'Users')
@section('breadcrumb')
    <li class="breadcrumb-item active">Users</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Users</h4>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name, email, username…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="filter" class="form-select form-select-sm">
                    <option value="">All Users</option>
                    <option value="verified"   {{ request('filter') === 'verified'   ? 'selected' : '' }}>Verified</option>
                    <option value="unverified" {{ request('filter') === 'unverified' ? 'selected' : '' }}>Unverified</option>
                    <option value="admin"      {{ request('filter') === 'admin'      ? 'selected' : '' }}>Admins</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
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
                    <th>Username</th>
                    <th>Email</th>
                    <th>Level</th>
                    <th>Verified</th>
                    <th>Admin</th>
                    <th>Joined</th>
                    <th class="pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td class="ps-3 text-muted">{{ $user->id }}</td>
                    <td>
                        <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none fw-medium">{{ $user->name }}</a>
                    </td>
                    <td class="text-muted">@{{ $user->username }}</td>
                    <td class="text-muted small">{{ $user->email }}</td>
                    <td><span class="badge bg-light text-dark border">Lvl {{ $user->level }}</span></td>
                    <td>
                        <form action="{{ route('admin.users.toggleVerified', $user) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm border-0 p-0" title="{{ $user->is_verified ? 'Revoke verification' : 'Verify user' }}">
                                @if($user->is_verified)
                                    <i class="bi bi-patch-check-fill text-success fs-5"></i>
                                @else
                                    <i class="bi bi-patch-check text-muted fs-5"></i>
                                @endif
                            </button>
                        </form>
                    </td>
                    <td>
                        <form action="{{ route('admin.users.toggleAdmin', $user) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm border-0 p-0" title="{{ $user->is_admin ? 'Remove admin' : 'Make admin' }}">
                                @if($user->is_admin)
                                    <i class="bi bi-shield-fill-check text-primary fs-5"></i>
                                @else
                                    <i class="bi bi-shield text-muted fs-5"></i>
                                @endif
                            </button>
                        </form>
                    </td>
                    <td class="text-muted small">{{ $user->created_at->format('M d, Y') }}</td>
                    <td class="pe-3">
                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-secondary py-0 px-2">View</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger py-0 px-2">Del</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center p-3">
        <small class="text-muted">Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}</small>
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection

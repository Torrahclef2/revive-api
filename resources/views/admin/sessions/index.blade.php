@extends('admin.layout')

@section('title', 'Sessions')
@section('breadcrumb')
    <li class="breadcrumb-item active">Sessions</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Sessions</h4>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by host name…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="filter" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active"    {{ request('filter') === 'active'    ? 'selected' : '' }}>Live</option>
                    <option value="waiting"   {{ request('filter') === 'waiting'   ? 'selected' : '' }}>Waiting</option>
                    <option value="scheduled" {{ request('filter') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="ended"     {{ request('filter') === 'ended'     ? 'selected' : '' }}>Ended</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ route('admin.sessions.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
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
                    <th>Host</th>
                    <th>Type</th>
                    <th>Privacy</th>
                    <th>Status</th>
                    <th>Scheduled At</th>
                    <th>Duration</th>
                    <th>Created</th>
                    <th class="pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($sessions as $session)
                <tr>
                    <td class="ps-3 text-muted">{{ $session->id }}</td>
                    <td>
                        <a href="{{ route('admin.sessions.show', $session) }}" class="text-decoration-none fw-medium">
                            {{ $session->host->name ?? 'Unknown' }}
                        </a>
                    </td>
                    <td><span class="badge bg-light text-dark border text-capitalize">{{ $session->type }}</span></td>
                    <td class="text-muted small text-capitalize">{{ $session->privacy }}</td>
                    <td>
                        <span class="badge badge-{{ $session->scheduled_at && $session->status === 'waiting' ? 'scheduled' : $session->status }}">
                            {{ $session->scheduled_at && $session->status === 'waiting' ? 'Scheduled' : ucfirst($session->status) }}
                        </span>
                    </td>
                    <td class="text-muted small">
                        {{ $session->scheduled_at ? $session->scheduled_at->format('M d, H:i') : '—' }}
                    </td>
                    <td class="text-muted small">{{ $session->duration ? $session->duration . ' min' : '—' }}</td>
                    <td class="text-muted small">{{ $session->created_at->format('M d, Y') }}</td>
                    <td class="pe-3">
                        <a href="{{ route('admin.sessions.show', $session) }}" class="btn btn-sm btn-outline-secondary py-0 px-2">View</a>
                        <form action="{{ route('admin.sessions.destroy', $session) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this session?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger py-0 px-2">Del</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">No sessions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($sessions->hasPages())
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center p-3">
        <small class="text-muted">Showing {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} of {{ $sessions->total() }}</small>
        {{ $sessions->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection

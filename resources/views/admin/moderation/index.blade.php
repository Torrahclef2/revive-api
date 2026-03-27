@extends('admin.layout')

@section('title', 'Moderation')
@section('breadcrumb')
    <li class="breadcrumb-item active">Moderation</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">
        Moderation
        @if($pendingCount > 0)
            <span class="badge ms-2" style="background:#fee2e2;color:#991b1b;font-size:.75rem">{{ $pendingCount }} pending</span>
        @endif
    </h4>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs border-0 mb-3" id="modTabs">
    <li class="nav-item">
        <a class="nav-link {{ request('tab','reports') === 'reports' ? 'active' : '' }}" href="?tab=reports&status={{ request('status','pending') }}">
            <i class="bi bi-flag me-1"></i>Reports
            @if($pendingCount > 0)<span class="badge bg-danger ms-1" style="font-size:.65rem">{{ $pendingCount }}</span>@endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request('tab') === 'banned' ? 'active' : '' }}" href="?tab=banned">
            <i class="bi bi-slash-circle me-1"></i>Banned Users
            @if($bannedUsers->count() > 0)<span class="badge bg-secondary ms-1" style="font-size:.65rem">{{ $bannedUsers->count() }}</span>@endif
        </a>
    </li>
</ul>

@if(request('tab','reports') === 'reports')

{{-- Status filter --}}
<div class="d-flex gap-2 mb-3">
    @foreach(['pending' => 'Pending', 'reviewed' => 'Reviewed', 'dismissed' => 'Dismissed', 'all' => 'All'] as $val => $label)
        <a href="?tab=reports&status={{ $val }}"
           class="btn btn-sm {{ request('status','pending') === $val ? 'btn-dark' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Reporter</th>
                    <th>Target</th>
                    <th>Reason</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Reported</th>
                    <th class="pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($reports as $report)
            <tr>
                <td class="ps-3">
                    @if($report->reporter)
                        <a href="{{ route('admin.users.show', $report->reporter) }}" class="text-decoration-none fw-medium small">
                            {{ $report->reporter->name }}
                        </a>
                    @else
                        <span class="text-muted small">Deleted</span>
                    @endif
                </td>
                <td>
                    @if($report->reportedUser)
                        <span class="d-block small fw-medium">
                            <i class="bi bi-person me-1 text-danger"></i>
                            <a href="{{ route('admin.users.show', $report->reportedUser) }}" class="text-decoration-none">{{ $report->reportedUser->name }}</a>
                        </span>
                    @endif
                    @if($report->reportedSession)
                        <span class="d-block small text-muted">
                            <i class="bi bi-broadcast me-1"></i>
                            <a href="{{ route('admin.sessions.show', $report->reportedSession) }}" class="text-decoration-none">Session #{{ $report->reportedSession->id }}</a>
                        </span>
                    @endif
                </td>
                <td>
                    <span class="badge" style="background:#fee2e2;color:#991b1b;text-transform:capitalize">
                        {{ str_replace('_',' ', $report->reason) }}
                    </span>
                </td>
                <td class="text-muted small" style="max-width:200px">
                    {{ $report->description ? Str::limit($report->description, 70) : '—' }}
                </td>
                <td>
                    @php
                        $statusStyle = match($report->status) {
                            'pending'   => 'background:#fef3c7;color:#92400e',
                            'reviewed'  => 'background:#dcfce7;color:#166534',
                            'dismissed' => 'background:#f1f5f9;color:#475569',
                            default     => ''
                        };
                    @endphp
                    <span class="badge" style="{{ $statusStyle }}">{{ ucfirst($report->status) }}</span>
                </td>
                <td class="text-muted small">{{ $report->created_at->diffForHumans() }}</td>
                <td class="pe-3">
                    <div class="d-flex flex-wrap gap-1">
                        @if($report->status === 'pending')
                        <form action="{{ route('admin.moderation.review', $report) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm py-0 px-2" style="background:#dcfce7;color:#166534;border:none;font-size:.75rem">
                                <i class="bi bi-check-lg"></i> Review
                            </button>
                        </form>
                        <form action="{{ route('admin.moderation.dismiss', $report) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm py-0 px-2" style="background:#f1f5f9;color:#475569;border:none;font-size:.75rem">
                                Dismiss
                            </button>
                        </form>
                        @endif
                        @if($report->reportedUser && !$report->reportedUser->banned_at)
                        <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:.75rem"
                                data-bs-toggle="modal" data-bs-target="#banModal{{ $report->id }}">
                            <i class="bi bi-slash-circle"></i> Ban
                        </button>
                        @endif
                    </div>
                </td>
            </tr>

            {{-- Ban Modal --}}
            @if($report->reportedUser && !$report->reportedUser->banned_at)
            <div class="modal fade" id="banModal{{ $report->id }}" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <h6 class="modal-title fw-bold">Ban {{ $report->reportedUser->name }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('admin.moderation.ban', $report->reportedUser) }}" method="POST">
                            @csrf
                            <div class="modal-body pt-2">
                                <label class="form-label small fw-semibold">Reason</label>
                                <textarea name="reason" class="form-control form-control-sm" rows="3" required
                                          placeholder="e.g. Repeated harassment reports…"></textarea>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-danger">Ban User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
            @empty
            <tr><td colspan="7" class="text-center py-5 text-muted">No reports found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($reports->hasPages())
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center p-3">
        <small class="text-muted">Showing {{ $reports->firstItem() }}–{{ $reports->lastItem() }} of {{ $reports->total() }}</small>
        {{ $reports->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

@else
{{-- Banned Users --}}
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="ps-3">User</th>
                    <th>Email</th>
                    <th>Reason</th>
                    <th>Banned At</th>
                    <th class="pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($bannedUsers as $user)
            <tr>
                <td class="ps-3">
                    <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none fw-medium">{{ $user->name }}</a>
                    <span class="d-block text-muted small">@{{ $user->username }}</span>
                </td>
                <td class="text-muted small">{{ $user->email }}</td>
                <td class="text-muted small" style="max-width:220px">{{ $user->ban_reason ?? '—' }}</td>
                <td class="text-muted small">{{ $user->banned_at->format('M d, Y H:i') }}</td>
                <td class="pe-3">
                    <form action="{{ route('admin.moderation.unban', $user) }}" method="POST">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:.75rem">
                            <i class="bi bi-check-circle me-1"></i>Unban
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-5 text-muted">No banned users.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

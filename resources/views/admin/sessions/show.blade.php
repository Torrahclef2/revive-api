@extends('admin.layout')

@section('title', 'Session #' . $session->id)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.sessions.index') }}" class="text-decoration-none">Sessions</a></li>
    <li class="breadcrumb-item active">Session #{{ $session->id }}</li>
@endsection

@section('content')
<div class="row g-4">
    {{-- Details --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-semibold mb-0">Session Details</h6>
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">ID</dt>       <dd class="col-7">{{ $session->id }}</dd>
                    <dt class="col-5 text-muted">Type</dt>     <dd class="col-7 text-capitalize">{{ $session->type }}</dd>
                    <dt class="col-5 text-muted">Privacy</dt>  <dd class="col-7 text-capitalize">{{ $session->privacy }}</dd>
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        <span class="badge badge-{{ $session->status }}">{{ ucfirst($session->status) }}</span>
                    </dd>
                    <dt class="col-5 text-muted">Host</dt>
                    <dd class="col-7">
                        @if($session->host)
                            <a href="{{ route('admin.users.show', $session->host) }}" class="text-decoration-none">{{ $session->host->name }}</a>
                        @else
                            <span class="text-muted">Unknown</span>
                        @endif
                    </dd>
                    <dt class="col-5 text-muted">Max</dt>      <dd class="col-7">{{ $session->max_participants ?? '∞' }}</dd>
                    <dt class="col-5 text-muted">Duration</dt> <dd class="col-7">{{ $session->duration ? $session->duration . ' min' : '—' }}</dd>
                    <dt class="col-5 text-muted">Started</dt>  <dd class="col-7">{{ $session->started_at?->format('M d, H:i') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Ended</dt>    <dd class="col-7">{{ $session->ended_at?->format('M d, H:i') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Scheduled</dt><dd class="col-7">{{ $session->scheduled_at?->format('M d, H:i') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Reminder</dt> <dd class="col-7">{{ $session->reminder_sent ? 'Sent' : 'Pending' }}</dd>
                    <dt class="col-5 text-muted">Created</dt>  <dd class="col-7">{{ $session->created_at->format('M d, Y') }}</dd>
                </dl>

                @if($session->description)
                    <hr class="my-3">
                    <p class="small text-muted mb-1 fw-semibold">Description</p>
                    <p class="small mb-0">{{ $session->description }}</p>
                @endif
            </div>
            <div class="card-footer bg-white border-0">
                <form action="{{ route('admin.sessions.destroy', $session) }}" method="POST"
                      onsubmit="return confirm('Delete this session permanently?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i>Delete Session
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        {{-- Participants --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-semibold mb-0">Participants <span class="badge bg-light text-dark border ms-1">{{ $session->participants->count() }}</span></h6>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr>
                        <th class="ps-3">User</th>
                        <th>Joined</th>
                        <th class="pe-3">Left</th>
                    </tr></thead>
                    <tbody>
                    @forelse($session->participants as $p)
                        <tr>
                            <td class="ps-3">
                                @if($p->user)
                                    <a href="{{ route('admin.users.show', $p->user) }}" class="text-decoration-none fw-medium">{{ $p->user->name }}</a>
                                @else
                                    <span class="text-muted">Deleted user</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $p->joined_at ? \Carbon\Carbon::parse($p->joined_at)->format('H:i:s') : '—' }}</td>
                            <td class="pe-3 text-muted small">
                                @if($p->left_at)
                                    {{ \Carbon\Carbon::parse($p->left_at)->format('H:i:s') }}
                                @else
                                    <span class="badge" style="background:#dcfce7;color:#166534">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No participants.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Meta --}}
        @if($session->meta->count())
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-semibold mb-0">Session Metadata</h6>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr>
                        <th class="ps-3">Key</th>
                        <th class="pe-3">Value</th>
                    </tr></thead>
                    <tbody>
                    @foreach($session->meta as $m)
                        <tr>
                            <td class="ps-3 small fw-medium text-muted">{{ $m->key }}</td>
                            <td class="pe-3 small">{{ $m->value }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

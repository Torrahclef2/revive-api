@extends('admin.layout')

@section('title', 'Session Monitor')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.sessions.index') }}" class="text-decoration-none">Sessions</a></li>
    <li class="breadcrumb-item active">Live Monitor</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">
        <span class="me-2" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#10b981;animation:pulse 1.4s infinite"></span>
        Live Session Monitor
    </h4>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted small" id="refresh-label">Auto-refreshes in <span id="countdown">30</span>s</span>
        <a href="{{ route('admin.sessions.monitor') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Now
        </a>
    </div>
</div>

{{-- Summary row --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ $liveSessions->count() }}</div>
                    <div class="label">Active Sessions</div>
                </div>
                <i class="bi bi-broadcast-pin icon"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card blue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ $liveSessions->sum(fn($s) => $s->activeParticipants->count()) }}</div>
                    <div class="label">Total Participants</div>
                </div>
                <i class="bi bi-people-fill icon"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card purple">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ $scheduledSessions->count() }}</div>
                    <div class="label">Upcoming Scheduled</div>
                </div>
                <i class="bi bi-calendar-event-fill icon"></i>
            </div>
        </div>
    </div>
</div>

{{-- Live sessions --}}
<h6 class="fw-semibold mb-3 text-uppercase" style="font-size:.75rem;letter-spacing:.08em;color:#64748b">
    <span class="me-1" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981"></span>
    Active Now
</h6>

@forelse($liveSessions as $session)
<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body">
        <div class="row align-items-start">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;background:linear-gradient(135deg,#e94560,#0f3460)">
                        <i class="bi bi-broadcast text-white"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">
                            @if($session->host)
                                <a href="{{ route('admin.users.show', $session->host) }}" class="text-decoration-none">{{ $session->host->name }}</a>
                            @else
                                Unknown Host
                            @endif
                        </div>
                        <div class="small text-muted">
                            <span class="badge bg-light text-dark border text-capitalize me-1">{{ $session->type }}</span>
                            <span class="badge bg-light text-dark border text-capitalize">{{ $session->privacy }}</span>
                        </div>
                    </div>
                </div>
                @if($session->description)
                    <p class="small text-muted mb-0 fst-italic">{{ Str::limit($session->description, 80) }}</p>
                @endif
            </div>
            <div class="col-md-3">
                <div class="small text-muted mb-1">Started</div>
                <div class="fw-medium small">{{ $session->started_at ? $session->started_at->format('H:i:s') : '—' }}</div>
                <div class="text-muted small">{{ $session->started_at ? $session->started_at->diffForHumans() : '' }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted mb-1">Participants ({{ $session->activeParticipants->count() }}{{ $session->max_participants ? '/'.$session->max_participants : '' }})</div>
                <div class="d-flex flex-wrap gap-1">
                    @foreach($session->activeParticipants->take(8) as $p)
                        <span class="badge" style="background:#f1f5f9;color:#334155;font-size:.7rem">
                            {{ $p->user ? $p->user->name : ($p->alias ?? 'Guest') }}
                        </span>
                    @endforeach
                    @if($session->activeParticipants->count() > 8)
                        <span class="badge bg-secondary">+{{ $session->activeParticipants->count() - 8 }} more</span>
                    @endif
                </div>
            </div>
            <div class="col-md-2 text-end">
                <a href="{{ route('admin.sessions.show', $session) }}" class="btn btn-sm btn-outline-secondary">View</a>
                <form action="{{ route('admin.sessions.destroy', $session) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Force-end and delete this session?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger ms-1">End</button>
                </form>
            </div>
        </div>
    </div>
</div>
@empty
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-broadcast fs-1 d-block mb-2 opacity-25"></i>
        No live sessions right now.
    </div>
</div>
@endforelse

{{-- Scheduled --}}
<h6 class="fw-semibold mt-4 mb-3 text-uppercase" style="font-size:.75rem;letter-spacing:.08em;color:#64748b">
    <i class="bi bi-calendar-event me-1"></i>Upcoming Scheduled Sessions
</h6>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Host</th>
                    <th>Type</th>
                    <th>Scheduled At</th>
                    <th>Reminder</th>
                    <th class="pe-3">Description</th>
                </tr>
            </thead>
            <tbody>
            @forelse($scheduledSessions as $s)
                <tr>
                    <td class="ps-3">
                        @if($s->host)
                            <a href="{{ route('admin.users.show', $s->host) }}" class="text-decoration-none fw-medium">{{ $s->host->name }}</a>
                        @else
                            <span class="text-muted">Unknown</span>
                        @endif
                    </td>
                    <td><span class="badge bg-light text-dark border text-capitalize">{{ $s->type }}</span></td>
                    <td class="fw-medium">{{ $s->scheduled_at->format('M d, Y H:i') }}</td>
                    <td>
                        @if($s->reminder_sent)
                            <span class="badge" style="background:#dcfce7;color:#166534"><i class="bi bi-check me-1"></i>Sent</span>
                        @else
                            <span class="badge" style="background:#fef3c7;color:#92400e">Pending</span>
                        @endif
                    </td>
                    <td class="pe-3 text-muted small">{{ $s->description ? Str::limit($s->description, 60) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No scheduled sessions.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('head')
<style>
@keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(1.3); }
}
</style>
@endpush

@push('scripts')
<script>
let secs = 30;
const el = document.getElementById('countdown');
const tick = setInterval(() => {
    el.textContent = --secs;
    if (secs <= 0) {
        clearInterval(tick);
        window.location.reload();
    }
}, 1000);
</script>
@endpush

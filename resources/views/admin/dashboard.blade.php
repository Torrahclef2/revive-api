@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<h4 class="fw-bold mb-4">Dashboard Overview</h4>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ number_format($stats['total_users']) }}</div>
                    <div class="label">Total Users</div>
                </div>
                <i class="bi bi-people-fill icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card green">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ number_format($stats['verified_users']) }}</div>
                    <div class="label">Verified Users</div>
                </div>
                <i class="bi bi-patch-check-fill icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card blue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ number_format($stats['total_sessions']) }}</div>
                    <div class="label">Total Sessions</div>
                </div>
                <i class="bi bi-broadcast icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card orange">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ number_format($stats['live_sessions']) }}</div>
                    <div class="label">Live Now</div>
                </div>
                <i class="bi bi-broadcast-pin icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card purple">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ number_format($stats['total_groups']) }}</div>
                    <div class="label">Groups</div>
                </div>
                <i class="bi bi-collection-fill icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card teal">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ number_format($stats['total_messages']) }}</div>
                    <div class="label">Messages</div>
                </div>
                <i class="bi bi-chat-dots-fill icon"></i>
            </div>
        </div>
    </div>
</div>

{{-- Charts --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Activity — Last 7 Days</h6>
                <canvas id="activityChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Session Breakdown</h6>
                <canvas id="breakdownChart" height="175"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Recent tables --}}
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 pb-2">
                <h6 class="fw-semibold mb-0">Recent Users</h6>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:.75rem">View all</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr>
                        <th class="ps-3">Name</th>
                        <th>Email</th>
                        <th>Joined</th>
                    </tr></thead>
                    <tbody>
                    @forelse($recentUsers as $user)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none fw-medium">{{ $user->name }}</a>
                                @if($user->is_verified)<i class="bi bi-patch-check-fill text-success ms-1" style="font-size:.75rem" title="Verified"></i>@endif
                            </td>
                            <td class="text-muted">{{ $user->email }}</td>
                            <td class="text-muted">{{ $user->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No users yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 pb-2">
                <h6 class="fw-semibold mb-0">Recent Sessions</h6>
                <a href="{{ route('admin.sessions.index') }}" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:.75rem">View all</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr>
                        <th class="ps-3">Host</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr></thead>
                    <tbody>
                    @forelse($recentSessions as $session)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.sessions.show', $session) }}" class="text-decoration-none fw-medium">
                                    {{ $session->host->name ?? 'Unknown' }}
                                </a>
                            </td>
                            <td><span class="badge bg-light text-dark border text-capitalize">{{ $session->type }}</span></td>
                            <td>
                                <span class="badge badge-{{ $session->status }}">
                                    {{ ucfirst($session->status) }}
                                </span>
                            </td>
                            <td class="text-muted">{{ $session->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No sessions yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const labels = @json($labels);
const userCounts = @json($userCounts);
const sessionCounts = @json($sessionCounts);

new Chart(document.getElementById('activityChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: 'New Users',
                data: userCounts,
                backgroundColor: 'rgba(233,69,96,.75)',
                borderRadius: 6,
            },
            {
                label: 'Sessions',
                data: sessionCounts,
                backgroundColor: 'rgba(59,130,246,.7)',
                borderRadius: 6,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

new Chart(document.getElementById('breakdownChart'), {
    type: 'doughnut',
    data: {
        labels: ['Live', 'Scheduled', 'Ended'],
        datasets: [{
            data: [
                {{ $stats['live_sessions'] }},
                {{ $stats['scheduled_sessions'] }},
                {{ $stats['total_sessions'] - $stats['live_sessions'] - $stats['scheduled_sessions'] }}
            ],
            backgroundColor: ['#f59e0b','#8b5cf6','#e2e8f0'],
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        cutout: '68%',
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
@endpush

@extends('admin.layout')

@section('title', 'Analytics')
@section('breadcrumb')
    <li class="breadcrumb-item active">Analytics</li>
@endsection

@section('content')
<h4 class="fw-bold mb-4">Analytics</h4>

{{-- Summary stats --}}
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
                    <div class="value">{{ number_format($stats['active_users_7d']) }}</div>
                    <div class="label">Active (7d)</div>
                </div>
                <i class="bi bi-activity icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card blue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ $stats['retention_rate'] }}%</div>
                    <div class="label">Retention</div>
                </div>
                <i class="bi bi-arrow-repeat icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card purple">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ number_format($stats['prayer_sessions']) }}</div>
                    <div class="label">Prayer Sessions</div>
                </div>
                <i class="bi bi-hand-index-thumb icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card orange">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ number_format($stats['study_sessions']) }}</div>
                    <div class="label">Study Sessions</div>
                </div>
                <i class="bi bi-book-fill icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card" style="background:linear-gradient(135deg,#dc2626,#7f1d1d);box-shadow:0 4px 15px rgba(220,38,38,.25)">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="value">{{ number_format($stats['pending_reports']) }}</div>
                    <div class="label">Pending Reports</div>
                </div>
                <i class="bi bi-flag-fill icon"></i>
            </div>
        </div>
    </div>
</div>

{{-- Charts row 1: DAU & Sessions --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Daily Active Users & Sessions — Last 30 Days</h6>
                <canvas id="dauChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Session Type Breakdown</h6>
                <canvas id="typeChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Charts row 2: New Users --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">New User Registrations — Last 30 Days</h6>
                <canvas id="newUsersChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-semibold mb-0">Retention Gauge</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center flex-grow-1">
                <div class="text-center">
                    <div style="width:130px;height:130px;border-radius:50%;background:conic-gradient(#3b82f6 {{ $retentionRate * 3.6 }}deg, #e2e8f0 0deg);display:flex;align-items:center;justify-content:center;margin:0 auto">
                        <div style="width:90px;height:90px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-direction:column">
                            <span style="font-size:1.4rem;font-weight:800;color:#1e293b">{{ $retentionRate }}%</span>
                        </div>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Users who joined 2+ sessions</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top hosts table --}}
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-0 pt-3 pb-2">
        <h6 class="fw-semibold mb-0">Top Session Hosts</h6>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="ps-3">#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Verified</th>
                    <th class="pe-3">Sessions Hosted</th>
                </tr>
            </thead>
            <tbody>
            @forelse($topHosts as $i => $host)
                <tr>
                    <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                    <td>
                        <a href="{{ route('admin.users.show', $host) }}" class="text-decoration-none fw-medium">{{ $host->name }}</a>
                    </td>
                    <td class="text-muted small">@{{ $host->username }}</td>
                    <td><span class="badge bg-light text-dark border text-capitalize">{{ $host->role ?? 'user' }}</span></td>
                    <td>
                        @if($host->is_verified)
                            <i class="bi bi-patch-check-fill text-success"></i>
                        @else
                            <i class="bi bi-dash text-muted"></i>
                        @endif
                    </td>
                    <td class="pe-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px;max-width:120px">
                                <div class="progress-bar" style="width:{{ $topHosts->first()->hosted_sessions_count > 0 ? ($host->hosted_sessions_count / $topHosts->first()->hosted_sessions_count * 100) : 0 }}%;background:linear-gradient(90deg,#e94560,#0f3460)"></div>
                            </div>
                            <span class="fw-semibold">{{ $host->hosted_sessions_count }}</span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No session hosts yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
const labels       = @json($labels);
const dauCounts    = @json($dauCounts);
const sessionCounts= @json($sessionCounts);
const newUserCounts= @json($newUserCounts);

// DAU + Sessions
new Chart(document.getElementById('dauChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'Daily Active Users',
                data: dauCounts,
                borderColor: '#e94560',
                backgroundColor: 'rgba(233,69,96,.08)',
                fill: true,
                tension: .4,
                pointRadius: 2,
            },
            {
                label: 'Sessions Created',
                data: sessionCounts,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,.08)',
                fill: true,
                tension: .4,
                pointRadius: 2,
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// Session type breakdown
const typeData = @json($typeBreakdown);
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(typeData).map(k => k.replace('_',' ').replace(/\b\w/g,c=>c.toUpperCase())),
        datasets: [{
            data: Object.values(typeData),
            backgroundColor: ['#e94560','#3b82f6','#10b981','#8b5cf6','#f59e0b'],
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { position: 'bottom' } }
    }
});

// New users
new Chart(document.getElementById('newUsersChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'New Registrations',
            data: newUserCounts,
            backgroundColor: 'rgba(16,185,129,.75)',
            borderRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
@endpush

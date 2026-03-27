@extends('admin.layout')

@section('title', $user->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}" class="text-decoration-none">Users</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('content')
<div class="row g-4">
    {{-- Profile Card --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width:72px;height:72px;font-size:2rem">
                    <i class="bi bi-person-fill text-white"></i>
                </div>
                <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                <p class="text-muted small mb-1">@{{ $user->username }}</p>
                @if($user->headline)
                    <p class="small text-secondary fst-italic mb-2">"{{ $user->headline }}"</p>
                @endif
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    @if($user->is_verified)
                        <span class="badge" style="background:#dcfce7;color:#166534"><i class="bi bi-patch-check-fill me-1"></i>Verified</span>
                    @endif
                    @if($user->is_admin)
                        <span class="badge" style="background:#dbeafe;color:#1e3a8a"><i class="bi bi-shield-fill-check me-1"></i>Admin</span>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-white border-top-0">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7 text-truncate">{{ $user->email }}</dd>
                    <dt class="col-5 text-muted">Level</dt>
                    <dd class="col-7">{{ $user->level }}</dd>
                    <dt class="col-5 text-muted">Streak</dt>
                    <dd class="col-7">{{ $user->streak }} days</dd>
                    <dt class="col-5 text-muted">Messaging</dt>
                    <dd class="col-7">{{ ucfirst(str_replace('_',' ', $user->messaging_privacy ?? 'everyone')) }}</dd>
                    <dt class="col-5 text-muted">Joined</dt>
                    <dd class="col-7">{{ $user->created_at->format('M d, Y') }}</dd>
                </dl>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card border-0 shadow-sm rounded-3 mt-3">
            <div class="card-body d-grid gap-2">
                <form action="{{ route('admin.users.toggleVerified', $user) }}" method="POST">
                    @csrf @method('PATCH')
                    <button class="btn w-100 btn-sm {{ $user->is_verified ? 'btn-outline-warning' : 'btn-outline-success' }}">
                        <i class="bi bi-patch-check me-1"></i>{{ $user->is_verified ? 'Revoke Verification' : 'Verify User' }}
                    </button>
                </form>
                <form action="{{ route('admin.users.toggleAdmin', $user) }}" method="POST">
                    @csrf @method('PATCH')
                    <button class="btn w-100 btn-sm {{ $user->is_admin ? 'btn-outline-warning' : 'btn-outline-primary' }}">
                        <i class="bi bi-shield me-1"></i>{{ $user->is_admin ? 'Remove Admin' : 'Make Admin' }}
                    </button>
                </form>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                      onsubmit="return confirm('Delete this user permanently?')">
                    @csrf @method('DELETE')
                    <button class="btn w-100 btn-sm btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Activity --}}
    <div class="col-lg-8">
        {{-- Hosted Sessions --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-semibold mb-0">Hosted Sessions <span class="badge bg-light text-dark border ms-1">{{ $user->hostedSessions->count() }}</span></h6>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr>
                        <th class="ps-3">Type</th>
                        <th>Status</th>
                        <th>Privacy</th>
                        <th>Scheduled</th>
                        <th class="pe-3">Created</th>
                    </tr></thead>
                    <tbody>
                    @forelse($user->hostedSessions as $s)
                        <tr>
                            <td class="ps-3"><span class="badge bg-light text-dark border text-capitalize">{{ $s->type }}</span></td>
                            <td><span class="badge badge-{{ $s->status }}">{{ ucfirst($s->status) }}</span></td>
                            <td class="text-muted small">{{ ucfirst($s->privacy) }}</td>
                            <td class="text-muted small">{{ $s->scheduled_at ? $s->scheduled_at->format('M d, H:i') : '—' }}</td>
                            <td class="pe-3 text-muted small">{{ $s->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No sessions hosted yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Groups --}}
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-semibold mb-0">Groups <span class="badge bg-light text-dark border ms-1">{{ $user->groups->count() }}</span></h6>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr>
                        <th class="ps-3">Group</th>
                        <th>Role</th>
                        <th class="pe-3">Joined</th>
                    </tr></thead>
                    <tbody>
                    @forelse($user->groups as $g)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.groups.show', $g) }}" class="text-decoration-none fw-medium">{{ $g->name }}</a>
                            </td>
                            <td><span class="badge bg-light text-dark border text-capitalize">{{ $g->pivot->role }}</span></td>
                            <td class="pe-3 text-muted small">{{ \Carbon\Carbon::parse($g->pivot->created_at)->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Not in any groups.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

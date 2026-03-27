@extends('admin.layout')

@section('title', $group->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.groups.index') }}" class="text-decoration-none">Groups</a></li>
    <li class="breadcrumb-item active">{{ $group->name }}</li>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width:64px;height:64px;background:linear-gradient(135deg,#8b5cf6,#4c1d95);font-size:1.75rem">
                    <i class="bi bi-collection-fill text-white"></i>
                </div>
                <h5 class="fw-bold mb-1">{{ $group->name }}</h5>
                <p class="text-muted small">
                    Owned by
                    @if($group->owner)
                        <a href="{{ route('admin.users.show', $group->owner) }}" class="text-decoration-none">{{ $group->owner->name }}</a>
                    @else
                        Unknown
                    @endif
                </p>
                <span class="badge bg-light text-dark border fs-6">{{ $group->users->count() }} members</span>
            </div>
            <div class="card-footer bg-white border-0">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Group ID</dt> <dd class="col-7">{{ $group->id }}</dd>
                    <dt class="col-5 text-muted">Created</dt>  <dd class="col-7">{{ $group->created_at->format('M d, Y') }}</dd>
                </dl>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-3 mt-3">
            <div class="card-body">
                <form action="{{ route('admin.groups.destroy', $group) }}" method="POST"
                      onsubmit="return confirm('Delete this group permanently?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i>Delete Group
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-semibold mb-0">Members <span class="badge bg-light text-dark border ms-1">{{ $group->users->count() }}</span></h6>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr>
                        <th class="ps-3">Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Verified</th>
                        <th class="pe-3">Joined Group</th>
                    </tr></thead>
                    <tbody>
                    @forelse($group->users as $member)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.users.show', $member) }}" class="text-decoration-none fw-medium">{{ $member->name }}</a>
                            </td>
                            <td class="text-muted small">@{{ $member->username }}</td>
                            <td>
                                <span class="badge bg-light text-dark border text-capitalize">{{ $member->pivot->role }}</span>
                            </td>
                            <td>
                                @if($member->is_verified)
                                    <i class="bi bi-patch-check-fill text-success" title="Verified"></i>
                                @else
                                    <i class="bi bi-dash text-muted"></i>
                                @endif
                            </td>
                            <td class="pe-3 text-muted small">
                                {{ \Carbon\Carbon::parse($member->pivot->created_at)->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No members.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

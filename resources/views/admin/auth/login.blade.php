<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Revive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh; }
        .login-card { border: none; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
        .brand-icon { width: 60px; height: 60px; background: linear-gradient(135deg, #e94560, #0f3460); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="container" style="max-width:420px">
        <div class="text-center mb-4">
            <div class="brand-icon mx-auto mb-3">
                <i class="bi bi-lightning-charge-fill text-white"></i>
            </div>
            <h2 class="text-white fw-bold">Revive Admin</h2>
            <p class="text-white-50 small">Sign in to the admin panel</p>
        </div>

        <div class="card login-card">
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="admin@revive.app" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn w-100" style="background:linear-gradient(135deg,#e94560,#0f3460);color:#fff;font-weight:600;">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

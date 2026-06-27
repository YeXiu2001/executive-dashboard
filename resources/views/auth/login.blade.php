<x-layouts.auth title="Login">
    <x-slot:head>
        <style>
            body {
                background:
                    radial-gradient(circle at top left, rgba(13, 110, 253, 0.16), transparent 32%),
                    radial-gradient(circle at bottom right, rgba(32, 201, 151, 0.18), transparent 30%),
                    linear-gradient(135deg, #f4f7fb 0%, #eef4ff 48%, #f8fbff 100%);
            }

            .account-pages {
                min-height: 100vh;
                margin: 0;
                padding: 2rem 0;
                display: flex;
                align-items: center;
            }

            .executive-login-shell {
                border: 0;
                border-radius: 1.75rem;
                box-shadow: 0 1.5rem 3.5rem rgba(15, 23, 42, 0.12);
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(12px);
            }

            .executive-logo-badge img {
                max-width: 7rem;
                max-height: 7rem;
                border-radius: 1rem;
            }

            .executive-login-panel {
                padding: 2.75rem;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), #ffffff);
                border-radius: 1.75rem;
            }

            .executive-login-panel .card-title {
                color: #0f172a;
            }

            .executive-login-panel .card-subtitle {
                color: #64748b;
            }

            .executive-login-panel .form-control {
                min-height: 3.25rem;
                border-color: #dbe4f0;
                border-radius: 0.95rem;
                background-color: #f8fbff;
            }

            .executive-login-panel .form-control:focus {
                border-color: rgba(13, 110, 253, 0.5);
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.12);
                background-color: #fff;
            }

            .executive-login-panel .input-group-text {
                border-color: #dbe4f0;
                border-radius: 0.95rem 0 0 0.95rem;
                background-color: #f8fbff;
                color: #64748b;
            }

            .executive-login-panel .input-group .form-control {
                border-left: 0;
                border-radius: 0 0.95rem 0.95rem 0;
            }

            .executive-login-panel .btn-primary {
                min-height: 3.25rem;
                border-radius: 0.95rem;
                font-weight: 600;
                box-shadow: 0 1rem 2rem rgba(13, 110, 253, 0.18);
            }

            @media (max-width: 991.98px) {
                .account-pages {
                    padding: 1.5rem 0;
                }

                .executive-login-panel {
                    padding: 2rem;
                }
            }

            @media (max-width: 575.98px) {
                .executive-login-panel {
                    padding: 1.5rem;
                }
            }
        </style>
    </x-slot:head>

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-7 col-xl-5">
            <div class="card executive-login-shell">
                <div class="executive-login-panel h-100 d-flex flex-column justify-content-center">
                    <div class="text-center mb-4">
                        <div class="executive-logo-badge mb-3">
                            <img src="{{ asset('assets/images/hotelier/hotelierv3.png') }}" alt="{{ config('app.name') }} logo">
                        </div>
                        <h1 class="card-title h2 mb-2">Welcome back</h1>
                        <p class="card-subtitle mb-0">Sign in to continue to {{ config('app.name') }}.</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">
                                    <i class="bx bx-envelope"></i>
                                </span>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    placeholder="name@example.com"
                                    required
                                    autofocus
                                    autocomplete="username"
                                >
                            </div>
                            @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">
                                    <i class="bx bx-lock-alt"></i>
                                </span>
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    id="password"
                                    placeholder="Enter your password"
                                    required
                                    autocomplete="current-password"
                                >
                            </div>
                            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                            <div class="form-check mb-0">
                                <input type="checkbox" name="remember" value="1" class="form-check-input" id="remember" @checked(old('remember'))>
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-log-in me-1"></i> Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4 text-center text-muted">
                @if (Route::has('register'))
                    <p class="mb-2">Need an account? <a href="{{ route('register') }}" class="fw-semibold text-primary">Create one</a></p>
                @endif
                <p class="mb-0">&copy; {{ now()->year }} {{ config('app.name') }}.</p>
            </div>
        </div>
    </div>
</x-layouts.auth>

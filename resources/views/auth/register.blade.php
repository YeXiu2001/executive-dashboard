<x-layouts.auth title="Register">
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

            .register-shell {
                border: 0;
                border-radius: 1.25rem;
                box-shadow: 0 1.5rem 3.5rem rgba(15, 23, 42, 0.12);
                overflow: hidden;
            }

            .register-hero {
                background: linear-gradient(135deg, rgba(85, 110, 230, 0.14), rgba(52, 195, 143, 0.16));
            }

            .register-logo {
                height: 3rem;
                width: 3rem;
                object-fit: contain;
                border-radius: 0.8rem;
            }
        </style>
    </x-slot:head>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card register-shell">
                <div class="register-hero">
                    <div class="row g-0 align-items-center">
                        <div class="col-8">
                            <div class="text-primary p-4">
                                <h5 class="text-primary">Create Account</h5>
                                <p class="mb-0">Register to get started with {{ config('app.name') }}.</p>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <img src="{{ asset('assets/images/hotelier/hotelierv3.png') }}" alt="{{ config('app.name') }} logo" class="register-logo">
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                required
                                autofocus
                                autocomplete="name"
                            >
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                required
                                autocomplete="username"
                            >
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                required
                                autocomplete="new-password"
                            >
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input
                                type="password"
                                name="password_confirmation"
                                class="form-control"
                                id="password_confirmation"
                                required
                                autocomplete="new-password"
                            >
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-user-plus me-1"></i> Register
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4 text-center text-muted">
                <p class="mb-2">Already have an account? <a href="{{ route('login') }}" class="fw-semibold text-primary">Sign in</a></p>
                <p class="mb-0">&copy; {{ now()->year }} {{ config('app.name') }}.</p>
            </div>
        </div>
    </div>
</x-layouts.auth>

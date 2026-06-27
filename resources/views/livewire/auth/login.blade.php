<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4 text-center">Login</h2>

            <form wire:submit="authenticate">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" wire:model.live="email" class="form-control @error('email') is-invalid @enderror" id="email" required autofocus>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" wire:model.live="password" class="form-control @error('password') is-invalid @enderror" id="password" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" wire:model="remember" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4 text-center">Register</h2>

            <form wire:submit="register">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" wire:model.live="name" class="form-control @error('name') is-invalid @enderror" id="name" required autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" wire:model.live="email" class="form-control @error('email') is-invalid @enderror" id="email" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" wire:model.live="password" class="form-control @error('password') is-invalid @enderror" id="password" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input type="password" wire:model.live="password_confirmation" class="form-control" id="password_confirmation" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">Register</button>
                </div>
            </form>
        </div>
    </div>
</div>

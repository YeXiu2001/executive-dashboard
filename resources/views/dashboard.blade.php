<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body style="font-family: sans-serif; padding: 2rem;">
    <h1>Welcome, {{ Auth::user()->name ?? 'User' }}!</h1>
    <p>You are logged in.</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer;">Logout</button>
    </form>
</body>
</html>

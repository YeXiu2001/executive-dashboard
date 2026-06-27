<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vite Styles and Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: rgba(15, 23, 42, 0.7) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.6);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2));
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        h1 {
            font-weight: 800;
            letter-spacing: -0.025em;
            margin-bottom: 24px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .text-muted-custom {
            color: #94a3b8;
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .btn-custom-primary {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 32px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
        }

        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.5);
            color: white;
        }

        .btn-custom-outline {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 14px 32px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-custom-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .decoration-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 0;
            opacity: 0.5;
            animation: float 10s infinite ease-in-out alternate;
        }

        .blob-1 {
            background: rgba(99, 102, 241, 0.4);
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
        }

        .blob-2 {
            background: rgba(168, 85, 247, 0.4);
            width: 500px;
            height: 500px;
            bottom: -200px;
            left: -200px;
            animation-delay: 2s;
        }

        @keyframes float {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-30px) scale(1.05); }
        }

        .laravel-logo {
            color: #ef4444;
            max-width: 200px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top py-3">
        <div class="container">
            <a class="navbar-brand text-white fw-bold d-flex align-items-center gap-2" href="#">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#f8fafc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="#f8fafc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="#f8fafc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ config('app.name', 'Laravel') }}
            </a>
            
            @if (Route::has('login'))
                <div class="d-flex gap-3 ms-auto">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-custom-outline btn-sm px-4">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn text-white fw-medium">Log in</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-custom-outline btn-sm px-4">Register</a>
                        @endif
                    @endauth
                </div>
            @endif
        </div>
    </nav>

    <!-- Background Decorations -->
    <div class="decoration-blob blob-1"></div>
    <div class="decoration-blob blob-2"></div>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center justify-content-between g-5">
                <!-- Left Content -->
                <div class="col-lg-6">
                   

                    <h1 class="display-4 fw-bolder">The best version of your application</h1>
                    <p class="text-muted-custom">
                        Laravel has wonderful documentation covering every aspect of the framework. Whether you're a newcomer or have prior experience with Laravel, we recommend reading our documentation from beginning to end.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="https://laravel.com/docs" target="_blank" class="btn btn-custom-primary">
                            Documentation
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="ms-2" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <a href="https://laracasts.com" target="_blank" class="btn btn-custom-outline">Watch Laracasts</a>
                    </div>
                </div>

                <!-- Right Content -->
                <div class="col-lg-5">
                    <div class="glass-card mb-4 position-relative">
                        <div class="feature-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="h4 fw-bold mb-3">Vibrant Ecosystem</h3>
                        <p class="text-white-50 mb-0">Laravel's robust background processing, expressive ORM, and database agnostic features make it easy to build powerful large-scale applications.</p>
                    </div>

                    <div class="glass-card position-relative" style="background: rgba(255,255,255,0.015); backdrop-filter: blur(8px);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-dark rounded-circle d-flex align-items-center justify-content-center border border-secondary" style="width: 44px; height: 44px;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21 12H3M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12M21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-semibold h6">Laravel {{ app()->version() }}</h5>
                                    <small class="text-white-50">PHP v{{ PHP_VERSION }}</small>
                                </div>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill">Latest version</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

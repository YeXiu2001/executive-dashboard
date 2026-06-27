<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

# 🚀 Laravel 13 Starter Kit

A professional, out-of-the-box Laravel 13 starter kit designed for rapid development. This template comes pre-configured with essential authentication and styling tools to get your project running in minutes.

---

## 🛠 Tech Stack

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.3%20%2F%208.5-777BB4?style=for-the-badge&logo=php)
![Livewire](https://img.shields.io/badge/Livewire-3.x%2B-FB70A9?style=for-the-badge&logo=livewire)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap)
![Vite](https://img.shields.io/badge/Vite-8.x-646CFF?style=for-the-badge&logo=vite)
![Fortify](https://img.shields.io/badge/Fortify-1.x-FF2D20?style=for-the-badge&logo=laravel)
![Pest](https://img.shields.io/badge/Pest-4.x-FF2D20?style=for-the-badge&logo=pest)

- **Backend**: Laravel 13.x (PHP 8.3+)
- **Authentication**: Laravel Fortify
- **Frontend**: Bootstrap 5 + Vite + Livewire
- **Permissions**: Spatie Laravel Permission
- **Testing**: Pest PHP
- **Database**: SQLite (Default)

---

## ✨ Key Features

- **Authentication**: Fully functional Login/Register views powered by **Livewire** components for real-time validation and a smooth SPA-like experience.
- **Role Management**: Pre-installed Spatie Permissions for easy ACL.
- **UI**: Styled with Bootstrap 5 and bundled with Vite.
- **Testing**: Testing setup with Pest.
- **Developer Experience**: Optimized with Laravel Pail, Pint, and Flush UI.

---

## 📂 Folder Structure & Components

When working with components in this project, we follow a strict separation of concerns to keep the codebase organized and scalable.

### 1. Standard Blade Components (`resources/views/components/`)
These are "dumb" components that strictly output reusable HTML markup. They contain **no complex PHP logic** or state.
- **Usage**: Layouts, buttons, cards, alerts (`<x-layouts.app>`, `<x-button>`)
- **Location**: `resources/views/components/`
- **Example**: `resources/views/components/layouts/app.blade.php`

### 2. Livewire Components
These are "smart" components that contain real-time PHP logic, handle database interactions, and manage state (like form validation without page reloads). We enforce a **Class-Based Architecture** (no single-file/Volt components) to maintain a strict separation of concerns.
- **Usage**: Interactive features, real-time forms, dynamic tables (`<livewire:auth.login />`)
- **Location**:
  - **Logic (PHP Class)**: `app/Livewire/` (e.g., `app/Livewire/Auth/Login.php`)
  - **View (HTML Template)**: `resources/views/livewire/` (e.g., `resources/views/livewire/auth/login.blade.php`)

By keeping structural HTML (`components/`) separate from interactive stateful logic (`Livewire/`), developers can easily locate and maintain application features!

---

## 🚀 Getting Started

Follow these steps to set up the project locally:

### 1. Clone the repository
```bash
git clone https://github.com/YeXiu2001/laravel13-starterkit.git
cd laravel13-starterkit
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
```bash
php artisan migrate
```

### 5. Start the Development Server
```bash
composer run dev
```

---

## 📦 Project Scripts

The following custom scripts are available in `composer.json`:

- `composer run setup`: Automated full project setup (Install, Migrations, Build).
- `composer run dev`: Starts all development services (Vite, Server, Queue, Pail).
- `composer run test`: Runs the test suite via Pest.

---

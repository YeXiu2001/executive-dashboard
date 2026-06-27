<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests can view the fortify authentication screens', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Welcome back');

    $this->get('/register')
        ->assertOk()
        ->assertSee('Create Account');
});

test('users can register through fortify', function () {
    $response = $this->post('/register', [
        'name' => 'Executive User',
        'email' => 'executive@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'name' => 'Executive User',
        'email' => 'executive@example.com',
    ]);
});

test('users can log in and log out through fortify', function () {
    $user = User::factory()->create([
        'email' => 'leader@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'leader@example.com',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

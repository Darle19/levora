<?php

use App\Models\Agency;
use App\Models\User;

test('shows login form', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
});

test('logs in active user with active agency and redirects to dashboard', function () {
    $agency = Agency::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'agency_id' => $agency->id,
        'is_active' => true,
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('rejects inactive user with error message', function () {
    $agency = Agency::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'agency_id' => $agency->id,
        'is_active' => false,
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('rejects wrong password with error message', function () {
    $agency = Agency::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'agency_id' => $agency->id,
        'is_active' => true,
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('redirects to dashboard on success and user is authenticated', function () {
    $agency = Agency::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'agency_id' => $agency->id,
        'is_active' => true,
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    expect(auth()->user()->id)->toBe($user->id);
});

test('regenerates session after login', function () {
    $agency = Agency::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'agency_id' => $agency->id,
        'is_active' => true,
        'password' => bcrypt('password123'),
    ]);

    $oldSessionId = session()->getId();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $newSessionId = session()->getId();
    expect($newSessionId)->not->toBe($oldSessionId);
});

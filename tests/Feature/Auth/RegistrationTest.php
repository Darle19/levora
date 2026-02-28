<?php

use App\Models\Agency;
use App\Models\User;

function validRegistrationData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+998901234567',
        'agency_name' => 'Test Travel Agency',
        'legal_name' => 'Test Travel LLC',
        'legal_address' => '123 Main Street, Tashkent',
        'agency_phone' => '+998712345678',
        'agency_mobile' => '+998901234568',
        'agency_email' => 'agency@example.com',
        'director' => 'John Director',
        'inn' => '123456789',
    ], $overrides);
}

test('shows registration form', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('creates agency with is_active false after registration', function () {
    $data = validRegistrationData();

    $this->post(route('register'), $data);

    $this->assertDatabaseHas('agencies', [
        'name' => 'Test Travel Agency',
        'legal_name' => 'Test Travel LLC',
        'legal_address' => '123 Main Street, Tashkent',
        'phone' => '+998712345678',
        'email' => 'agency@example.com',
        'director' => 'John Director',
        'inn' => '123456789',
        'is_active' => false,
    ]);
});

test('creates user with is_active false after registration', function () {
    $data = validRegistrationData();

    $this->post(route('register'), $data);

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'is_active' => false,
    ]);

    $user = User::where('email', 'testuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->is_active)->toBeFalse();
    expect($user->agency_id)->not->toBeNull();
});

test('does not auto-login user after registration', function () {
    $data = validRegistrationData();

    $this->post(route('register'), $data);

    $this->assertGuest();
});

test('redirects to registration pending route after registration', function () {
    $data = validRegistrationData();

    $response = $this->post(route('register'), $data);

    $response->assertRedirect(route('registration.pending'));
});

test('validates required fields', function () {
    $response = $this->post(route('register'), []);

    $response->assertSessionHasErrors([
        'name',
        'email',
        'password',
        'agency_name',
        'legal_name',
        'legal_address',
        'agency_phone',
        'agency_email',
        'director',
        'inn',
    ]);
});

test('rejects duplicate email', function () {
    $agency = Agency::factory()->create();
    User::factory()->create([
        'email' => 'existing@example.com',
        'agency_id' => $agency->id,
    ]);

    $data = validRegistrationData(['email' => 'existing@example.com']);

    $response = $this->post(route('register'), $data);

    $response->assertSessionHasErrors(['email']);
});

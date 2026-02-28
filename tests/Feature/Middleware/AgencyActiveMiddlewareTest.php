<?php

use App\Models\Agency;
use App\Models\User;

test('active agency can access dashboard', function () {
    $agency = Agency::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'agency_id' => $agency->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
});

test('inactive agency is logged out and redirected to login', function () {
    $agency = Agency::factory()->create(['is_active' => false]);
    $user = User::factory()->create([
        'agency_id' => $agency->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('unauthenticated user is redirected to login', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

<?php

use App\Models\Agency;
use App\Models\User;

test('allows login attempts within rate limit', function () {
    $agency = Agency::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'agency_id' => $agency->id,
        'is_active' => true,
        'password' => bcrypt('password123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(302);
    }
});

test('blocks login after exceeding rate limit', function () {
    $agency = Agency::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'agency_id' => $agency->id,
        'is_active' => true,
        'password' => bcrypt('password123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);
    }

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429);
});

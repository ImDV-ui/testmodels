<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('get endpoint returns pagination of 10 users', function () {
    User::factory()->count(15)->create();

    $response = $this->getJson('/api/user/get');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'current_page',
            'data',
            'per_page',
            'total',
        ])
        ->assertJsonPath('per_page', 10)
        ->assertJsonCount(10, 'data');
});

test('create endpoint creates a new user in database', function () {
    $payload = [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'password' => 'secret1234',
    ];

    $response = $this->postJson('/api/user/create', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('username', 'johndoe')
        ->assertJsonPath('email', 'john@example.com');

    $this->assertDatabaseHas('users', [
        'username' => 'johndoe',
        'email' => 'john@example.com',
    ]);

    $user = User::where('email', 'john@example.com')->first();
    expect(Hash::check('secret1234', $user->password))->toBeTrue();
});

test('create endpoint validates input', function () {
    $response = $this->postJson('/api/user/create', [
        'username' => 'ab',
        'email' => 'invalid-email',
        'password' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username', 'email', 'password']);
});

test('login endpoint returns user data with valid credentials', function () {
    $user = User::factory()->create([
        'username' => 'janedoe',
        'email' => 'jane@example.com',
        'password' => 'mysecurepassword',
    ]);

    $response = $this->postJson('/api/user/login', [
        'email' => 'jane@example.com',
        'password' => 'mysecurepassword',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('username', 'janedoe')
        ->assertJsonPath('email', 'jane@example.com');
});

test('login endpoint fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'mysecurepassword',
    ]);

    $response = $this->postJson('/api/user/login', [
        'email' => 'jane@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Credenciales incorrectas.');
});

test('update_username endpoint updates username when passing email and password', function () {
    $user = User::factory()->create([
        'username' => 'oldname',
        'email' => 'user@example.com',
        'password' => 'mypassword123',
    ]);

    $response = $this->putJson('/api/user/update_username', [
        'email' => 'user@example.com',
        'password' => 'mypassword123',
        'username' => 'newcoolname',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('username', 'newcoolname');

    expect($user->fresh()->username)->toBe('newcoolname');
});

test('update_username endpoint fails with invalid credentials', function () {
    User::factory()->create([
        'username' => 'oldname',
        'email' => 'user@example.com',
        'password' => 'mypassword123',
    ]);

    $response = $this->putJson('/api/user/update_username', [
        'email' => 'user@example.com',
        'password' => 'wrongpassword',
        'username' => 'newcoolname',
    ]);

    $response->assertStatus(401);
});

test('update_email endpoint updates email when passing current email and password', function () {
    $user = User::factory()->create([
        'email' => 'oldemail@example.com',
        'password' => 'mypassword123',
    ]);

    $response = $this->putJson('/api/user/update_email', [
        'email' => 'oldemail@example.com',
        'password' => 'mypassword123',
        'new_email' => 'newemail@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('email', 'newemail@example.com');

    expect($user->fresh()->email)->toBe('newemail@example.com');
});

test('update_email fails when new email is already taken', function () {
    User::factory()->create([
        'email' => 'taken@example.com',
    ]);

    User::factory()->create([
        'email' => 'me@example.com',
        'password' => 'mypassword123',
    ]);

    $response = $this->putJson('/api/user/update_email', [
        'email' => 'me@example.com',
        'password' => 'mypassword123',
        'new_email' => 'taken@example.com',
    ]);

    $response->assertStatus(422);
});

test('update_password endpoint updates password when passing email and current password', function () {
    $user = User::factory()->create([
        'email' => 'pwduser@example.com',
        'password' => 'oldpassword123',
    ]);

    $response = $this->putJson('/api/user/update_password', [
        'email' => 'pwduser@example.com',
        'password' => 'oldpassword123',
        'new_password' => 'newbrandpassword456',
    ]);

    $response->assertStatus(200);

    $loginResponse = $this->postJson('/api/user/login', [
        'email' => 'pwduser@example.com',
        'password' => 'newbrandpassword456',
    ]);

    $loginResponse->assertStatus(200);
});

test('delete endpoint deletes user when passing email and password', function () {
    $user = User::factory()->create([
        'email' => 'delete_me@example.com',
        'password' => 'deleteme123',
    ]);

    $response = $this->deleteJson('/api/user/delete', [
        'email' => 'delete_me@example.com',
        'password' => 'deleteme123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Usuario eliminado correctamente.');

    $this->assertDatabaseMissing('users', [
        'email' => 'delete_me@example.com',
    ]);
});

test('delete endpoint fails with incorrect password', function () {
    User::factory()->create([
        'email' => 'keep_me@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->deleteJson('/api/user/delete', [
        'email' => 'keep_me@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);

    $this->assertDatabaseHas('users', [
        'email' => 'keep_me@example.com',
    ]);
});

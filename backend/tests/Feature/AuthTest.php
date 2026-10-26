<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('пользователь может зарегистрироваться', function () {
    $response = fromFrontend($this)->postJson('/api/register', [
        'name' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'user' => ['id', 'name', 'email']]);

    $this->assertDatabaseHas('users', ['name' => 'testuser']);
    $this->assertAuthenticated();
});

test('регистрация не проходит при несовпадении паролей', function () {
    $response = fromFrontend($this)->postJson('/api/register', [
        'name' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('пользователь может войти с правильными данными', function () {
    $user = User::factory()->create([
        'name' => 'testuser',
        'password' => bcrypt('password123'),
    ]);

    $response = fromFrontend($this)->postJson('/api/login', [
        'name' => 'testuser',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id);

    $this->assertAuthenticatedAs($user);
});

test('пользователь не может войти с неверными данными', function () {
    User::factory()->create([
        'name' => 'testuser',
        'password' => bcrypt('password123'),
    ]);

    $response = fromFrontend($this)->postJson('/api/login', [
        'name' => 'testuser',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('авторизованный пользователь может получить свои данные', function () {
    $user = User::factory()->create();

    $response = fromFrontend($this)
        ->actingAs($user)
        ->getJson('/api/me');

    $response->assertOk()
        ->assertJsonPath('id', $user->id);
});

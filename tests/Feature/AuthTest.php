<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase; // Seus testes PEST extendem TestCase por padrão.

// Usa o trait RefreshDatabase para cada teste, garantindo um banco de dados limpo
uses(RefreshDatabase::class);

// --- Testes de Registro ---

it('registers a new user successfully and creates a wallet', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'register@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(201)
             ->assertJsonStructure([
                 'success',
                 'message',
                 'data' => [
                     'user' => ['id', 'name', 'email'],
                     'token',
                     'expires_in'
                 ]
             ])
             ->assertJson([
                 'success' => true,
                 'message' => 'Usuário registrado com sucesso.',
                 'data' => [
                     'user' => [
                         'name' => 'Test User',
                         'email' => 'register@example.com',
                     ]
                 ]
             ]);

    // Verifica se o usuário foi criado no banco de dados
    $this->assertDatabaseHas('users', [
        'email' => 'register@example.com',
        'name' => 'Test User',
    ]);

    // Verifica se uma carteira foi criada para o usuário (via Observer/Factory)
    $user = User::where('email', 'register@example.com')->first();
    $this->assertNotNull($user->wallet);
    $this->assertEquals(0.00, (float) $user->wallet->balance); // Garante que o balance é float para comparação
});

it('does not register a user with missing required data', function () {
    $userData = [
        'email' => 'missing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

it('does not register a user with invalid email format', function () {
    $userData = [
        'name' => 'Invalid Email',
        'email' => 'invalid-email-format',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

it('does not register a user with a password mismatch', function () {
    $userData = [
        'name' => 'Mismatch Password',
        'email' => 'mismatch@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password456',
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['password']);
});

it('does not register a user with an already existing email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $userData = [
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

// // --- Testes de Login ---

it('logs in an existing user successfully', function () {
    $password = 'secretpassword';
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make($password),
    ]);

    $credentials = [
        'email' => 'login@example.com',
        'password' => $password,
    ];

    $response = $this->postJson('/api/login', $credentials);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'message',
                 'data' => ['token', 'expires_in']
             ])
             ->assertJson([
                 'success' => true,
                 'message' => 'Login realizado com sucesso.'
             ]);
});

it('does not log in with invalid credentials', function () {
    // Não cria usuário para simular credenciais inválidas
    $credentials = [
        'email' => 'nonexistent@example.com',
        'password' => 'wrongpassword',
    ];

    $response = $this->postJson('/api/login', $credentials);

    $response->assertStatus(401) // Unauthorized
             ->assertJson([
                 'success' => false,
                 'message' => 'Credenciais inválidas!',
                 'errors' => []
             ]);
});

it('does not log in with missing email', function () {
    $credentials = [
        'password' => 'anypassword',
    ];

    $response = $this->postJson('/api/login', $credentials);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

it('does not log in with missing password', function () {
    $credentials = [
        'email' => 'any@example.com',
    ];

    $response = $this->postJson('/api/login', $credentials);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['password']);
});

// // --- Testes de Obtenção de Usuário (me) ---

it('gets authenticated user details', function () {
    $user = User::factory()->create([
        'name' => 'Authenticated User',
        'email' => 'authenticated@example.com',
    ]);
    $token = JWTAuth::fromUser($user);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/me');

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Dados do usuário obtidos com sucesso.',
                 'data' => [
                     'id' => $user->id,
                     'name' => 'Authenticated User',
                     'email' => 'authenticated@example.com',
                 ]
             ]);
});

it('cannot get user details without authentication', function () {
    $response = $this->postJson('/api/me'); // Sem token

    $response->assertStatus(401);
});

it('cannot get user details with an invalid token', function () {
    $invalidToken = 'invalid.jwt.token';

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $invalidToken,
    ])->postJson('/api/me');

    $response->assertStatus(401);
});

// // --- Testes de Refresh de Token ---

it('can refresh an authenticated token', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/refresh');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'message',
                 'data' => ['token', 'expires_in']
             ])
             ->assertJson([
                 'success' => true,
                 'message' => 'Token atualizado com sucesso.'
             ]);

    // O novo token deve ser diferente do original
    $this->assertNotEquals($token, $response->json('data.token'));
});

it('cannot refresh an invalid token', function () {
    $invalidToken = 'invalid.jwt.token';

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $invalidToken,
    ])->postJson('/api/refresh');

    $response->assertStatus(401);
});

it('cannot refresh without a token', function () {
    $response = $this->postJson('/api/refresh');

    $response->assertStatus(401);
});

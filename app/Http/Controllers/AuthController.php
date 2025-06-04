<?php

namespace App\Http\Controllers;

use App\DTOs\UserRegisterDTO;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\UserService;
use App\Support\ApiResponse;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\UnauthorizedException;
use Tymon\JWTAuth\Facades\JWTAuth;
class AuthController extends Controller
{

    public function __construct(
        protected UserService $userService
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $userRegisterDto = UserRegisterDTO::fromRequest($request);
        $user = $this->userService->create($userRegisterDto);

        $token = JWTAuth::fromUser($user);

        return ApiResponse::success([
            'user' => $user->only(['id', 'name', 'email']),
            'token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 201, 'Usuário registrado com sucesso.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            throw new UnauthorizedException('Credenciais inválidas!');
        }

        return ApiResponse::success([
            'token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 200, 'Login realizado com sucesso.');
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return ApiResponse::success([], 200, 'Logout realizado com sucesso.');
    }

    public function getUser(): JsonResponse
    {
        $user = auth()->user();
        return ApiResponse::success($user->only(['id', 'name', 'email']), 200, 'Dados do usuário obtidos com sucesso.');
    }
    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh();

        return ApiResponse::success([
            'token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 200, 'Token atualizado com sucesso.');
    }
}

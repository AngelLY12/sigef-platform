<?php

namespace App\Http\Controllers;

use App\Core\Application\Services\Auth\RefreshTokenServiceFacades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class RefreshTokenController extends Controller
{
    private RefreshTokenServiceFacades $service;
    public function __construct(
        RefreshTokenServiceFacades $service
    )
    {
        $this->service= $service;
    }

    public function store(Request $request)
    {
        $request->validate(['refresh_token' => 'required|string']);
        $newToken =$this->service->refreshToken($request->refresh_token);

        return Response::success(['user_tokens' => $newToken], 'Tokens renovados');

    }

    public function logout(Request $request)
    {
        $user=Auth::user();
        $refreshToken = $request->header('x-refresh-token');
        $this->service->logout($user, $refreshToken);
        return response()->noContent();
    }
}

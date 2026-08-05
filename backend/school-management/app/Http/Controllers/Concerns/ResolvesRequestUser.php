<?php

namespace App\Http\Controllers\Concerns;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
trait ResolvesRequestUser
{
    public function targetUser(Request $request): User
    {
        /** @var \App\Models\User $user*/
        $user = $request->attributes->get('targetUser') ?? Auth::user();
        return $user;
    }

}

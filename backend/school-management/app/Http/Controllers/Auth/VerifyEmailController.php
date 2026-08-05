<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;


class VerifyEmailController extends Controller
{

    public function __invoke(Request $request): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url');
        $user = \App\Models\User::find($request->route('id'));

        if (!$user) {
            return redirect("{$frontendUrl}/common/email-verification?status=not-found");
        }

        if(!hash_equals((string) $request->route('hash'), sha1($user->email))) {
            return redirect("{$frontendUrl}/common/email-verification?status=invalid");
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(
                "{$frontendUrl}/common/email-verification?status=already-verified"
            );
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            return redirect(
                "{$frontendUrl}/common/email-verification?status=success"
            );
        }

        return redirect(
            "{$frontendUrl}/common/email-verification?status=error"
        );
    }
}

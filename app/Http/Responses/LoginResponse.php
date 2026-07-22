<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->intended($this->homeFor($request));
    }

    private function homeFor(Request $request): string
    {
        /** @var User $user */
        $user = $request->user();

        return $user->hasAnyRole(['admin', 'staff', 'support'])
            ? route('admin.dashboard')
            : Fortify::redirects('login');
    }
}

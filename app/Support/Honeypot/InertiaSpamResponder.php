<?php

namespace App\Support\Honeypot;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Honeypot\SpamResponder\SpamResponder;

class InertiaSpamResponder implements SpamResponder
{
    public function respond(Request $request, Closure $next): RedirectResponse
    {
        return redirect()->back()->withErrors([
            'spam' => __('Ta soumission a été bloquée, réessaie.'),
        ]);
    }
}

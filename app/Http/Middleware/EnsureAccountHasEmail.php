<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountHasEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        if (($user = $request->user()) && blank($user->email)) {
            return $request->expectsJson()
                ? abort(403, 'Your account must have a vaild email address.')
                : Redirect::guest(URL::route('set-email.notice'));
        }

        return $next($request);
    }
}

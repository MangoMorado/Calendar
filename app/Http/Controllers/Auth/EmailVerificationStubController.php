<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;

class EmailVerificationStubController extends Controller
{
    /**
     * Stub para verification.send cuando emailVerification está desactivado.
     * Redirige atrás con status para que el frontend no falle.
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->back()->with('status', Fortify::VERIFICATION_LINK_SENT);
    }
}

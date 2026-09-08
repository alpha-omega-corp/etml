<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminController extends Controller
{
    public const SESSION_KEY = 'is_admin';

    public function show(): View
    {
        return view('admin.login', ['enabled' => self::isConfigured()]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Entrez le mot de passe.',
        ]);

        if (! self::isConfigured()) {
            return back()->withErrors([
                'password' => "Le mode administrateur n'est pas configuré (ADMIN_PASSWORD).",
            ]);
        }

        if (! self::matches((string) $request->input('password'))) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        $request->session()->put(self::SESSION_KEY, true);
        $request->session()->regenerate();

        return redirect()->route('cards')->with('success', 'Mode administrateur activé.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('cards')->with('status', 'Mode administrateur quitté.');
    }

    public static function isConfigured(): bool
    {
        return filled(config('admin.password'));
    }

    /**
     * A bcrypt hash is checked as a hash; anything else is compared in constant
     * time, so a plain password in `.env` still gives nothing away by timing.
     */
    private static function matches(string $candidate): bool
    {
        $expected = (string) config('admin.password');

        if (str_starts_with($expected, '$2y$') || str_starts_with($expected, '$2a$')) {
            return Hash::check($candidate, $expected);
        }

        return hash_equals($expected, $candidate);
    }
}

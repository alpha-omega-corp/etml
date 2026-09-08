<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('login');
    }

    /**
     * Username-only sign-in: the name identifies the deck progress to restore.
     */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:2', 'max:40', 'regex:/^[\pL\pN _.\-]+$/u'],
        ], [
            'username.required' => 'Entrez votre nom.',
            'username.min' => 'Au moins 2 caractères.',
            'username.max' => 'Au plus 40 caractères.',
            'username.regex' => 'Lettres, chiffres, espaces, tirets et points uniquement.',
        ]);

        $user = User::firstOrCreate(['username' => trim($data['username'])]);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('cards'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

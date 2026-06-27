<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Hidden developer-only user management. Lets the developer change any
 * account's (admin or user) email and password directly — no current-password
 * confirmation and no email re-verification. Gated by the `developer` middleware.
 */
class DeveloperUserController extends Controller
{
    public function index(): View
    {
        return view('developer.users', [
            'users' => User::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Developer change is trusted — keep the account verified, don't force
        // re-verification on an email change.
        $user->email_verified_at ??= now();

        $user->save();

        return back()->with('status', "Updated {$user->email}.");
    }
}

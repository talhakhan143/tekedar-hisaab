<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SendPasswordResetCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetCodeController extends Controller
{
    /** Minutes a reset code stays valid. */
    private const TTL_MINUTES = 60;

    /**
     * Email a 6-digit reset code to the user.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = $request->string('email')->lower()->value();

        // Always behave the same way whether or not the email exists, so we
        // don't leak which addresses are registered.
        $user = User::where('email', $email)->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($code), 'created_at' => now()],
            );

            Notification::route('mail', $email)->notify(new SendPasswordResetCode($code));
        }

        return redirect()
            ->route('password.code', ['email' => $email])
            ->with('status', __('We have emailed you a 6-digit reset code.'));
    }

    /**
     * Show the "enter code + new password" form.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password-code', [
            'email' => $request->string('email')->value(),
        ]);
    }

    /**
     * Verify the code and set the new password.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'code'     => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $email = $request->string('email')->lower()->value();

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        $invalid = ! $record
            || Carbon::parse($record->created_at)->addMinutes(self::TTL_MINUTES)->isPast()
            || ! Hash::check($request->string('code')->value(), $record->token);

        if ($invalid) {
            throw ValidationException::withMessages([
                'code' => __('This reset code is invalid or has expired.'),
            ]);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->update(['password' => Hash::make($request->string('password')->value())]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()->route('login')->with('status', __('Your password has been reset. Please sign in.'));
    }
}

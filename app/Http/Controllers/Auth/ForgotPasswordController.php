<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Always show the same message to prevent email enumeration
        $successMessage = 'If an account with that email exists, a password reset link has been sent.';

        if (!$user) {
            return back()->with('status', $successMessage);
        }

        // Attempt to send the reset email via Laravel's Password broker
        try {
            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('status', $successMessage);
            }

            // Too many attempts
            if ($status === Password::RESET_THROTTLED) {
                return back()->withErrors(['email' => 'Please wait before requesting another reset link.']);
            }
        } catch (\Exception $e) {
            // Mail not configured — show artisan command as fallback
            return back()->with('status', 'Email delivery is not configured on this instance. ' .
                'Please contact your platform administrator and ask them to run: ' .
                'php artisan admin:reset-password ' . $request->email);
        }

        return back()->with('status', $successMessage);
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect('/login')->with('success', 'Password reset successfully. Please sign in with your new password.');
        }

        return back()->withErrors(['email' => match($status) {
            Password::INVALID_TOKEN => 'This reset link is invalid or has expired. Please request a new one.',
            Password::INVALID_USER  => 'No account found with that email address.',
            default                 => 'Password reset failed. Please try again.',
        }]);
    }
}

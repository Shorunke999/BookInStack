<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Developer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    // ─── Login ────────────────────────────────────────────────────────────────────

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        // Block unverified — send them to the notice page
        if (! Auth::user()->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('verification.notice')
                ->with('resend_email', $credentials['email']);
        }

        return redirect()->intended(route('dashboard'));
    }

    // ─── Register ─────────────────────────────────────────────────────────────────

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'business_name' => 'required|string|max:150',
            'email'         => 'required|email|unique:developers,email',
            'password'      => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $developer = Developer::create([
            'name'          => $data['name'],
            'business_name' => $data['business_name'],
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            'status'        => 'pending',
        ]);

        // Fires the Registered event → triggers email verification notification
        event(new Registered($developer));

        return redirect()->route('verification.notice')
            ->with('success', 'Account created! Please check your email to verify your address.');
    }

    // ─── Email Verification ───────────────────────────────────────────────────────

    public function verificationNotice(Request $request): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
        return view('auth.verify-email', [
            'email' => $request->session()->get('resend_email') ?? (Auth::user()?->email),
        ]);
    }

    public function verificationVerify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill(); // marks email_verified_at

        Auth::login($request->user());

        return redirect()->route('dashboard.api-keys')
            ->with('success', 'Email verified! Welcome to BookStack.');
    }

    public function verificationSend(Request $request): RedirectResponse
    {
        $email = $request->input('email') ?? Auth::user()?->email;

        if (!$email) {
            return back()->withErrors(['email' => 'Please provide your email address.']);
        }

        $developer = Developer::where('email', $email)->first();

        if (!$developer) {
            // Don't reveal whether the email exists
            return back()->with('success', 'If that email is registered, a verification link has been sent.');
        }

        if ($developer->hasVerifiedEmail()) {
            return redirect()->route('login')->with('success', 'Email already verified. Please log in.');
        }

        $developer->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent! Check your inbox.');
    }

    // ─── Forgot Password ──────────────────────────────────────────────────────────

    public function showForgotPassword(): View|RedirectResponse
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        // Tell Laravel to use the 'developers' broker (configured in auth.php)
        $status = Password::broker('developers')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'Password reset link sent! Check your inbox.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::broker('developers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Developer $developer, string $password) {
                $developer->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset! Please log in.')
            : back()->withErrors(['email' => __($status)]);
    }

    // ─── Logout ───────────────────────────────────────────────────────────────────

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

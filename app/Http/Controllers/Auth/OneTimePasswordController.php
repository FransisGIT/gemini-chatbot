<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

use App\Services\WhatsappService;
use App\Mail\OtpSender;
use App\Models\User;

class OneTimePasswordController extends Controller
{
    public function otp_view()
    {
        if (!session()->has('otp_verification_email')) {
            return redirect()->route('auth.view');
        }

        $email = session()->get('otp_verification_email');
        $user = User::where('email', $email)->first();

        return view('auth.verification', ['user' => $user]);
    }

    public function otp_verification(Request $request)
    {
        $request->validate([
            'otp' => 'required|array|size:6',
            'email' => 'required|email',
        ]);

        $otp = implode('', $request->otp);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            session()->flash('failed_message', 'Akun tidak ditemukan.');
            return back();
        }

        if ($user->otp_code !== $otp) {
            session()->flash('failed_message', 'Kode OTP tidak valid.');
            return back();
        }

        if (now()->gt($user->otp_expires_at)) {
            session()->flash('failed_message', 'Kode OTP telah kedaluwarsa.');
            return back();
        }

        auth()->login($user);

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return redirect()->intended('dashboard');
    }

    public function otp_resend()
    {
        if (!session()->has('otp_verification_email')) {
            return redirect()->route('auth.view');
        }

        $email = session()->get('otp_verification_email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->with('failed_message', 'User tidak ditemukan.');
        }

        // Generate OTP baru
        $otpCode = mt_rand(100000, 999999);
        $otpExpiresAt = Carbon::now()->addMinutes(5);

        // Update user dengan OTP baru
        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => $otpExpiresAt,
        ]);

        $message = "*{$otpCode}* adalah kode OTP untuk masuk ke akun Anda.\n\nJangan berikan kode ini kepada siapapun. Berlaku selama 5 menit.";

        $methods = [];

        if ($user->is_whatsapp_notification_enabled) {
            WhatsappService::send($user->phone, $message);
            $methods[] = 'whatsapp';
        }

        if ($user->is_email_notification_enabled) {
            Mail::to($user->email)->send(new OtpSender($user->email, $user->name, $otpCode));
            $methods[] = 'email';
        }

        session()->flash('success_message', 'Kode OTP baru telah dikirimkan ke ' . implode(' dan ', $methods) . ' Anda.');
        session()->put('otp_delivery', $methods);
        return back();
    }
}

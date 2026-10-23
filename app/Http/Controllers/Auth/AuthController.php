<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

use Jenssegers\Agent\Agent;
use App\Models\User;

use App\Services\WhatsappService;
use App\Mail\OtpSender;
use App\Mail\PasswordReset;

class AuthController extends Controller
{
    public function login_view()
    {
        return view('auth.login');
    }

    public function auth_login(Request $request)
    {
        $rules = [
            'login' => 'required',
            'password' => 'required',
        ];

        if (config('app.env') !== 'local') {
            $rules['g-recaptcha-response'] = 'required';
        }

        $request->validate($rules);

        if (config('app.env') !== 'local') {
            $recaptchaSecret = config('recaptcha.secret_key');
            $recaptchaResponse = $request->input('g-recaptcha-response');

            $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
            $responseData = json_decode($verifyResponse, true);

            if (!$responseData['success']) {
                session()->flash('failed_message', 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.');
                return back()->withInput($request->only('login'));
            }
        }

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $remember = $request->filled('remember');

        if (auth()->attempt([$field => $request->login, 'password' => $request->password], $remember)) {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            if ($user->status === 'nonaktif' || $user->status === 'banned') {
                auth()->logout();
                session()->flash('failed_message', 'Akun Anda telah dinonaktifkan atau diblokir. Silakan hubungi administrator untuk informasi lebih lanjut.');

                return back()->withInput($request->only('login'));
            }

            $request->session()->regenerate();

            $agent = new Agent();
            $browser = $agent->browser();
            $platform = $agent->platform();

            $user->active_device = "$browser di $platform";
            $user->save();

            if ($user->is_otp) {
                $kodeOtp = mt_rand(100000, 999999);
                $otpExpiredAt = Carbon::now()->addMinutes(5);

                $user->update([
                    'otp_code' => $kodeOtp,
                    'otp_expires_at' => $otpExpiredAt,
                ]);

                $message = "*{$kodeOtp}* adalah kode OTP untuk masuk ke akun Anda.\n\nJangan berikan kode ini kepada siapapun. Berlaku selama 5 menit.";

                $delivery = [];

                if ($user->is_whatsapp_notification_enabled) {
                    WhatsappService::send($user->phone, $message);
                    $delivery[] = 'whatsapp';
                }

                if ($user->is_email_notification_enabled) {
                    Mail::to($user->email)->send(new OtpSender($user->email, $user->name, $kodeOtp));
                    $delivery[] = 'email';
                }

                $request->session()->put('otp_verification_email', $user->email);
                session()->put('otp_delivery', $delivery);

                return redirect()->route('otp.view');
            }

            return redirect()->intended('dashboard');
        }

        session()->flash('failed_message', 'Email atau username dan password yang Anda masukkan salah. Silakan coba lagi!');
        return back()->withInput($request->only('login'));
    }

    public function register_view()
    {
        return view('auth.register');
    }

    public function auth_register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'g-recaptcha-response' => 'required',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
            'g-recaptcha-response.required' => 'Verifikasi reCAPTCHA wajib diisi.',
        ]);

        $recaptchaSecret = config('recaptcha.secret_key');
        $recaptchaResponse = $request->input('g-recaptcha-response');

        $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
        $responseData = json_decode($verifyResponse, true);

        if (!$responseData['success']) {
            session()->flash('failed_message', 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.');
            return back()->withInput($request->only('login'));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'password_changed_at' => now(),
            'is_otp' => false,
            'is_active' => true,
        ]);

        $agent = new Agent();
        $browser = $agent->browser();
        $platform = $agent->platform();

        $user->active_device = "$browser di $platform";
        $user->save();

        session()->flash('success_message', 'Registrasi berhasil! Silakan login menggunakan akun Anda.');
        return to_route('auth.view');
    }

    public function password_reset_view()
    {
        return view('auth.password_reset');
    }

    public function password_reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Silakan masukkan alamat email.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.exists' => 'Email tidak ditemukan dalam sistem kami.',
        ]);

        $token = Str::uuid()->toString();
        $user = User::where('email', $request->email)->first();
        $name = $user->name;

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        $delivery = [];

        $resetUrl = route('password.update.view', ['token' => $token]);

        if ($user->is_whatsapp_notification_enabled && $user->phone) {
            $message = "Halo *{$name}*,\n\n";
            $message .= "Kami menerima permintaan untuk mereset kata sandi akun Anda.\n\n";
            $message .= "Silakan klik tautan berikut untuk mereset kata sandi:\n";
            $message .= "{$resetUrl}\n\n";
            $message .= "Tautan ini berlaku selama 60 menit.\n\n";
            $message .= "Jika Anda tidak meminta reset kata sandi, abaikan pesan ini.";

            WhatsappService::send($user->phone, $message);
            $delivery[] = 'WhatsApp';
        }

        if ($user->is_email_notification_enabled) {
            Mail::to($user->email)->send(new PasswordReset($token, $request->email, $name));
            $delivery[] = 'Email';
        }

        if (empty($delivery)) {
            session()->flash('failed_message', 'Tidak ada metode notifikasi yang aktif. Silakan hubungi administrator.');
            return back();
        }

        session()->put('reset_delivery', $delivery);
        session()->flash('success_message', 'Tautan untuk mereset kata sandi telah dikirim ke ' . implode(' dan ', $delivery) . ' Anda.');
        return back();
    }

    public function password_update_view($token)
    {
        $data = DB::table('password_reset_tokens')->where('token', $token)->first();

        if (!$data) {
            session()->flash('failed_message', 'Token reset kata sandi tidak valid atau telah kedaluwarsa.');
            return redirect()->route('password.email');
        }

        $createdAt = Carbon::parse($data->created_at);
        if ($createdAt->addHour()->isPast()) {
            DB::table('password_reset_tokens')->where('token', $token)->delete();
            session()->flash('failed_message', 'Token reset kata sandi telah kedaluwarsa. Silakan ajukan permintaan baru.');
            return redirect()->route('password.email');
        }

        return view('auth.password_update', ['token' => $token, 'email' => $data->email]);
    }

    public function password_update(Request $request, $token)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => [
                'required',
                'min:8',
                'confirmed',
                new \App\Rules\StrongPassword()
            ],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Email tidak ditemukan.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $data = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->where('email', $request->email)
            ->first();

        if (!$data) {
            session()->flash('failed_message', 'Token tidak valid atau email tidak sesuai.');
            return back();
        }

        $createdAt = Carbon::parse($data->created_at);
        if ($createdAt->addHour()->isPast()) {
            DB::table('password_reset_tokens')->where('token', $token)->delete();
            session()->flash('failed_message', 'Token reset kata sandi telah kedaluwarsa. Silakan ajukan permintaan baru.');
            return redirect()->route('password.email');
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);
        $user->password_changed_at = Carbon::now();
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        session()->flash('success_message', 'Kata sandi berhasil direset. Silakan login dengan kata sandi baru.');
        return redirect()->route('auth.view');
    }

    public function auth_logout()
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        session()->flash('success_message', 'Sesi telah berakhir, silakan login kembali!');
        return redirect()->route('auth.view');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    public function redirect($provider)
    {
        $this->validateProvider($provider);
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            session()->flash('failed_message', 'Terjadi kesalahan saat mencoba mengautentikasi melalui ' . ucfirst($provider) . '. Silakan coba lagi.');
            return redirect()->route('auth.view');
        }

        if (auth()->check()) {
            return $this->linkAccount($provider, $socialUser);
        }

        return $this->loginWithSocial($provider, $socialUser);
    }

    protected function linkAccount($provider, $socialUser)
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasSocialAccount($provider)) {
            session()->flash('failed_message', 'Akun ' . ucfirst($provider) . ' sudah terhubung dengan profil Anda.');
            return redirect()->route('user.profile');
        }

        $existingAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($existingAccount && $existingAccount->user_id !== $user->id) {
            session()->flash('failed_message', 'Akun ' . ucfirst($provider) . ' ini sudah terhubung ke akun lain.');
            return redirect()->route('user.profile');
        }

        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_email' => $socialUser->getEmail(),
            'provider_name' => $socialUser->getName(),
            'provider_avatar' => $socialUser->getAvatar(),
            'provider_token' => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken ?? null,
        ]);

        session()->flash('success_message', 'Berhasil menghubungkan akun ' . ucfirst($provider) . '. Anda dapat mengaktifkan sinkronisasi data jika diinginkan.');
        return redirect()->route('user.profile');
    }

    protected function loginWithSocial($provider, $socialUser)
    {
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            $socialAccount->update([
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken ?? null,
                'provider_email' => $socialUser->getEmail(),
                'provider_name' => $socialUser->getName(),
                'provider_avatar' => $socialUser->getAvatar(),
            ]);

            auth()->login($socialAccount->user, true);

            session()->flash('success_message', 'Berhasil masuk dengan ' . ucfirst($provider) . '.');
            return redirect()->route('user.dashboard');
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            session()->flash('failed_message', 'Email Anda terdaftar tetapi belum terhubung dengan ' . ucfirst($provider) . '. Silakan login terlebih dahulu, lalu hubungkan akun ' . ucfirst($provider) . ' di halaman profil.');
            return redirect()->route('auth.view');
        }

        session()->flash('failed_message', 'Maaf, tidak dapat menemukan akun. Silakan hubungkan akun ' . ucfirst($provider) . ' terlebih dahulu pada halaman profil, atau hubungi Administrator untuk meminta bantuan.');
        return redirect()->route('auth.view');
    }

    public function disconnect($provider)
    {
        $this->validateProvider($provider);

        /** @var User $user */
        $user = auth()->user();
        $socialAccount = $user->getSocialAccount($provider);

        if (!$socialAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ' . ucfirst($provider) . ' tidak terhubung.',
            ]);
        }

        if (!$user->password && $user->socialAccounts()->count() === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat memutuskan koneksi! Ini adalah satu-satunya metode login Anda. Harap atur password terlebih dahulu.',
            ]);
        }

        $syncedProvider = $user->socialAccounts()
            ->where('provider', $provider)
            ->exists();

        if ($user->is_social_sync && $syncedProvider) {
            $user->update([
                'is_social_sync' => false,
                'name' => $user->original_name ?? $user->name,
                'avatar' => $user->original_avatar ?? $user->avatar,
            ]);
        }

        $socialAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memutuskan koneksi dengan ' . ucfirst($provider) . '.',
            'redirect' => route('user.profile')
        ]);
    }

    /**
     * PERBAIKAN: Toggle sinkronisasi dengan validasi hanya 1 provider
     */
    public function toggleSync(Request $request, $provider)
    {
        $this->validateProvider($provider);

        /** @var User $user */
        $user = auth()->user();
        $socialAccount = $user->getSocialAccount($provider);

        if (!$socialAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ' . ucfirst($provider) . ' belum terhubung.',
            ]);
        }

        $enableSync = $request->input('enable_sync', false);

        if ($enableSync) {
            if ($user->is_social_sync) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan sinkronisasi dengan provider lain. Nonaktifkan sinkronisasi tersebut terlebih dahulu.',
                ]);
            }

            if (!$user->original_name) {
                $user->original_name = $user->name;
            }
            if (!$user->original_avatar || $user->original_avatar === 'default-avatar.png') {
                $user->original_avatar = $user->avatar;
            }

            $avatarFilename = $this->downloadSocialAvatar($socialAccount->provider_avatar, $user->username, $provider);

            if (
                $user->avatar &&
                $user->avatar !== 'default-avatar.png' &&
                $user->avatar !== $user->original_avatar
            ) {
                $oldPath = public_path('uploads/avatar/' . $user->avatar);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $user->update([
                'is_social_sync' => true,
                'name' => $socialAccount->provider_name,
                'avatar' => $avatarFilename ?? $user->avatar,
            ]);

            $message = 'Sinkronisasi data dengan ' . ucfirst($provider) . ' berhasil diaktifkan.';
        } else {
            if (
                $user->avatar &&
                $user->avatar !== 'default-avatar.png' &&
                $user->avatar !== $user->original_avatar
            ) {
                $oldPath = public_path('uploads/avatar/' . $user->avatar);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $user->update([
                'is_social_sync' => false,
                'name' => $user->original_name ?? $user->name,
                'avatar' => $user->original_avatar ?? $user->avatar,
            ]);

            $message = 'Sinkronisasi data dinonaktifkan. Data dikembalikan ke data original.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => route('user.profile')
        ]);
    }

    private function downloadSocialAvatar($avatarUrl, $username, $provider)
    {
        if (!$avatarUrl) {
            return null;
        }

        try {
            $imageContent = file_get_contents($avatarUrl);
            $extension = 'jpg';

            if (strpos($avatarUrl, '.png') !== false) {
                $extension = 'png';
            } elseif (strpos($avatarUrl, '.gif') !== false) {
                $extension = 'gif';
            }

            $filename = $username . '_' . $provider . '_sync_' . time() . '.' . $extension;
            $path = public_path('uploads/avatar/' . $filename);

            file_put_contents($path, $imageContent);

            return $filename;
        } catch (Exception $e) {
            Log::error('Failed to download social avatar: ' . $e->getMessage());
            return null;
        }
    }

    protected function validateProvider($provider)
    {
        if (!in_array($provider, ['google', 'github', 'facebook'])) {
            abort(404);
        }
    }
}

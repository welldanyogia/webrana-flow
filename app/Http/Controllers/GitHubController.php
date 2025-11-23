<?php

namespace App\Http\Controllers;

use App\Models\GitAccount;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class GitHubController extends Controller
{
    // 1. Redirect ke GitHub untuk minta izin
    public function redirect()
    {
        // Scope 'repo' wajib agar kita bisa search private repository user
        return Socialite::driver('github')
            ->scopes(['repo'])
            ->redirect();
    }

    // 2. Callback setelah user klik "Authorize" di GitHub
    public function callback()
    {
        try {
            $githubUser = Socialite::driver('github')->user();
            $user = Auth::user();

            if (!$user) {
                return redirect('/app/login')->with('error', 'Session expired. Please login first.');
            }

            // Simpan / Update Token GitHub ke database
            GitAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'github',
                    'provider_id' => $githubUser->getId(),
                ],
                [
                    'username' => $githubUser->getNickname(), // Username GitHub
                    'access_token' => $githubUser->token,     // Token SAKTI untuk search repo
                    'refresh_token' => $githubUser->refreshToken,
                    'token_expires_at' => $githubUser->expiresIn ? now()->addSeconds($githubUser->expiresIn) : null,
                ]
            );

            // Redirect kembali ke form create app dengan pesan sukses
            // Kita arahkan ke dashboard user (console)
            return redirect()->to('/dashboard/applications/create')
                ->with('success', 'GitHub berhasil dihubungkan! Sekarang Anda bisa mencari repository.');

        } catch (\Exception $e) {
            // Log error untuk debugging
            logger()->error('GitHub Callback Error: ' . $e->getMessage());

            return redirect()->to('/dashboard/applications/create')
                ->with('error', 'Gagal menghubungkan GitHub. Silakan coba lagi.');
        }
    }
}

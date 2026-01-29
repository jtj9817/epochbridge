<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    protected $allowedProviders = ['google'];

    public function redirectToProvider(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)
            ->enablePKCE()
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    public function handleProviderCallback(Request $request, string $provider)
    {
        $this->validateProvider($provider);

        if ($request->has('error')) {
            return $this->handleOAuthError($request);
        }

        try {
            $socialUser = Socialite::driver($provider)
                ->enablePKCE()
                ->user();
        } catch (Exception $e) {
            Log::error('OAuth callback error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->withErrors(['oauth' => 'Authentication failed. Please try again.']);
        }

        if (! $socialUser->getEmail()) {
            return redirect()->route('login')
                ->withErrors(['oauth' => 'Email not provided by provider. Please use a different method.']);
        }

        try {
            DB::beginTransaction();

            $user = $this->findOrCreateUser($socialUser, $provider);

            DB::commit();

            Auth::login($user, true);

            return redirect()->intended(config('fortify.home', '/dashboard'));

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('User creation/login error', [
                'provider' => $provider,
                'email' => $socialUser->getEmail(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->withErrors(['oauth' => 'Unable to complete authentication. Please try again.']);
        }
    }

    protected function findOrCreateUser($socialUser, string $provider)
    {
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            $this->updateSocialAccount($socialAccount, $socialUser);

            return $socialAccount->user;
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if (! $user) {
            $user = $this->createUser($socialUser);
        }

        $this->createSocialAccount($user, $provider, $socialUser);

        return $user;
    }

    protected function createUser($socialUser)
    {
        $user = User::create([
            'name' => $socialUser->getName() ?? $socialUser->getEmail(),
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
            'password' => null,
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    protected function createSocialAccount(User $user, string $provider, $socialUser)
    {
        return $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'access_token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken,
            'expires_in' => $socialUser->expiresIn,
            'avatar' => $socialUser->getAvatar(),
            'nickname' => $socialUser->getNickname(),
        ]);
    }

    protected function updateSocialAccount(SocialAccount $account, $socialUser)
    {
        $account->update([
            'access_token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken ?? $account->refresh_token,
            'expires_in' => $socialUser->expiresIn,
            'avatar' => $socialUser->getAvatar(),
        ]);
    }

    protected function handleOAuthError(Request $request)
    {
        $error = $request->input('error');
        $errorDescription = $request->input('error_description', 'Authentication cancelled');

        Log::warning('OAuth error from provider', [
            'error' => $error,
            'description' => $errorDescription,
        ]);

        return redirect()->route('login')
            ->withErrors(['oauth' => $errorDescription]);
    }

    protected function validateProvider(string $provider): void
    {
        if (! in_array($provider, $this->allowedProviders)) {
            abort(404, 'Invalid provider');
        }
    }
}

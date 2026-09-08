<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a social identity must not be linked to an existing account.
 */
class OAuthAccountLinkException extends Exception
{
    public static function unverifiedEmail(string $provider): self
    {
        $providerName = ucfirst($provider);

        return new self(
            'An account with this email already exists but its email address has not been verified. '.
            "Sign in with your password, verify your email, then try {$providerName} sign-in again."
        );
    }
}

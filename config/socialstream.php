<?php

use JoelButcher\Socialstream\Features;
use JoelButcher\Socialstream\Providers;

return [

    /*
    |--------------------------------------------------------------------------
    | Socialstream Guard
    |--------------------------------------------------------------------------
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Socialstream Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Socialstream Prompt
    |--------------------------------------------------------------------------
    */

    'prompt' => env('SOCIALSTREAM_PROMPT', 'Or Login Via'),

    /*
    |--------------------------------------------------------------------------
    | Socialstream Providers
    |--------------------------------------------------------------------------
    |
    | Twitter OAuth 1.0 ("twitter") is intentionally excluded because it
    | requires live API keys even for the redirect step.
    |
    */

    'providers' => [
        Providers::bitbucket(),
        Providers::facebook(),
        Providers::github(),
        Providers::gitlab(),
        Providers::google(),
        Providers::linkedin(),
        Providers::linkedinOpenId(),
        Providers::slack(),
        Providers::twitterOAuth2(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */

    'features' => [
        // Features::generateMissingEmails(),
        // Features::createAccountOnFirstLogin(),
        // Features::globalLogin(),
        // Features::authExistingUnlinkedUsers(),
        Features::rememberSession(),
        Features::providerAvatars(),
        Features::refreshOAuthTokens(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    */

    'home' => '/app',

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    */

    'redirects' => [
        'login'                => '/app',
        'register'             => '/app',
        'login-failed'         => '/login',
        'registration-failed'  => '/register',
        'provider-linked'      => '/user/profile',
        'provider-link-failed' => '/user/profile',
    ],

];

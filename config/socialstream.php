<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Socialstream Providers
    |--------------------------------------------------------------------------
    |
    | Lists the social providers that the application should expose. Twitter
    | OAuth 1.0 ("twitter") is intentionally excluded because it requires
    | live API keys for redirects. Twitter OAuth 2.0 is enabled.
    |
    */
    'providers' => [
        'bitbucket',
        'facebook',
        'github',
        'gitlab',
        'google',
        'linkedin',
        'linkedin-openid',
        'slack',
        'twitter-oauth-2',
    ],

    /* Prompt shown above social buttons */
    'prompt' => env('SOCIALSTREAM_PROMPT', 'Or Login Via'),
];

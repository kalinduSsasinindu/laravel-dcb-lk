<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | Which carrier billing gateway to use when you call the DcbLk facade
    | without naming a driver explicitly - "ideamart" or "mspace". Both are
    | hSenid Mobile platforms; which one you're provisioned on depends on
    | your own application registration, not the end user's carrier.
    |
    */
    'default' => env('DCB_LK_DRIVER', 'ideamart'),

    'drivers' => [

        'ideamart' => [
            'app_id' => env('IDEAMART_APP_ID'),
            'password' => env('IDEAMART_PASSWORD'),
            'base_url' => env('IDEAMART_BASE_URL', 'https://api.ideamart.io/subscription'),
            'version' => env('IDEAMART_VERSION', '1.0'),

            // Verifies inbound webhook POSTs are actually from Ideamart -
            // checked against the request's ?secret= query param. Register
            // this exact value with Ideamart's portal alongside your
            // webhook URL. See DcbLk\Webhooks\WebhookPayload::verify().
            'webhook_secret' => env('IDEAMART_WEBHOOK_SECRET'),

            'otp' => [
                'client' => env('IDEAMART_OTP_CLIENT', 'MOBILEAPP'),
                'device' => env('IDEAMART_OTP_DEVICE', env('APP_NAME', 'Laravel')),
                'os' => env('IDEAMART_OTP_OS', 'web'),
                'app_code' => env('IDEAMART_OTP_APP_CODE'),
            ],

            // Separate app + credentials required - Ideamart provisions
            // SMS-send as its own application, distinct from the
            // subscription/OTP app above.
            'sms' => [
                'app_id' => env('IDEAMART_SMS_APP_ID'),
                'password' => env('IDEAMART_SMS_PASSWORD'),
                'base_url' => env('IDEAMART_SMS_BASE_URL', 'https://api.ideamart.io'),
            ],
        ],

        'mspace' => [
            'app_id' => env('MSPACE_APP_ID'),
            'password' => env('MSPACE_PASSWORD'),

            // Root host only, no /subscription suffix - mSpace's OTP
            // endpoints are root-level (/otp/request) while
            // subscription-management endpoints live under /subscription/*;
            // MSpaceDriver builds each full path itself.
            'base_url' => env('MSPACE_BASE_URL', 'https://api.mspace.lk'),
            'version' => env('MSPACE_VERSION', '1.0'),

            'webhook_secret' => env('MSPACE_WEBHOOK_SECRET'),

            'otp' => [
                'client' => env('MSPACE_OTP_CLIENT', 'MOBILEAPP'),
                'device' => env('MSPACE_OTP_DEVICE', env('APP_NAME', 'Laravel')),
                'os' => env('MSPACE_OTP_OS', 'web'),
                'app_code' => env('MSPACE_OTP_APP_CODE'),
            ],

            // Falls back to the main app credentials if SendSMS wasn't
            // provisioned as a distinct application on the mSpace portal.
            'sms' => [
                'app_id' => env('MSPACE_SMS_APP_ID'),
                'password' => env('MSPACE_SMS_PASSWORD'),
            ],
        ],

    ],

];

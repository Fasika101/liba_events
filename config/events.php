<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public agent self-registration
    |--------------------------------------------------------------------------
    |
    | When false, the /register route is disabled. Company admins and super
    | admins create agent accounts from the admin panel.
    |
    */
    'allow_public_registration' => (bool) env('ALLOW_PUBLIC_AGENT_REGISTRATION', false),

    /*
    |--------------------------------------------------------------------------
    | Default company for public registration
    |--------------------------------------------------------------------------
    |
    | Required when allow_public_registration is true. New accounts get this
    | company_id and role agent.
    |
    */
    'default_registration_company_id' => env('DEFAULT_REGISTRATION_COMPANY_ID'),

    /*
    |--------------------------------------------------------------------------
    | Organization registration (new members)
    |--------------------------------------------------------------------------
    |
    | Public registration at /register-organization. Applicants verify their
    | organization phone via SMS and wait for super-admin approval.
    |
    */
    'allow_organization_registration' => (bool) env('ALLOW_ORGANIZATION_REGISTRATION', true),

    /*
    |--------------------------------------------------------------------------
    | SMS verification — defaults used until saved in Super Admin → SMS settings.
    | driver "log" writes OTP codes to the application log (local development).
    | driver "smsethiopia" sends via https://smsethiopia.com/api/sms/send
    |
    */
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'otp_length' => (int) env('SMS_OTP_LENGTH', 6),
        'otp_expiry_minutes' => (int) env('SMS_OTP_EXPIRY_MINUTES', 10),
    ],

];

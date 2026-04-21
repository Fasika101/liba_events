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

];

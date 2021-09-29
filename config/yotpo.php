<?php

return [
    /*
    * Enable or disable Yotpo api. Useful for local development when runing tests.
    */
    'api_enabled' => env('YOTPO_API_ENABLED', false),

    /*
   * Enable or disable Yotpo Loyalty. Useful for local development when runing tests.
   */
    'loyalty_enabled' => env('YOTPO_LOYALTY_ENABLED', false),

    /*
    * Core API V3 Credentials
    */
    'app_key' => env('YOTPO_APP_KEY'),
    'secret_key' => env('YOTPO_SECRET_KEY'),

    /*
    * Loyalty & Referrals API Credentials
    */
    'loyalty_api_key' => env('LOYALTY_API_KEY'),
    'loyalty_guid_key' => env('LOYALTY_GUID_KEY'),

    /*
     * CDNs
     * */
    'loyalty_js_sdk_url' => env('LOYALTY_JS_SDK_URL'),
    'loyalty_modules_loader_url' => env('LOYALTY_MODULES_LOADER_URL'),

    /*
     * Default currency code you want to use. Make sure to have the same in your Yotpo Loyalty admin: https://loyalty.yotpo.com/general-settings
     * */
    'currency' => 'MAD',

    /*
    * Default country code you want to use. This is important for API requests when submitting the phone number
    * */
    'country' => 'MA'
];

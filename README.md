# Yotpo Core API V3 & Loyalty & Referrals API implementation for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/combindma/yotpo-api.svg?style=flat-square)](https://packagist.org/packages/combindma/yotpo-api)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/combindma/yotpo-api/Check%20&%20fix%20styling?label=code%20style)](https://github.com/combindma/yotpo-api/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/combindma/yotpo-api.svg?style=flat-square)](https://packagist.org/packages/combindma/yotpo-api)

## Installation

You can install the package via composer:

```bash
composer require combindma/yotpo-api
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Combindma\YotpoApi\YotpoApiServiceProvider" --tag="yotpo-config"
```

This is the contents of the published config file:

```php
return [
    /*
    * Enable or disable Yotpo api. Useful for local development.
    */
    'enabled' => env('YOTPO_ENABLED', false),
    
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
];
```

## Usage
* You should create a free account in https://yotpo.com and add your credentials in .env file
* You must add `Combindma\YotpoApi\Traits\YotpoCustomer` to user model or implement yours. Just be sure to have the same structure (same array keys).
* You must add `Combindma\YotpoApi\Traits\YotpoPurchase` to order model or implement yours. Just be sure to have the same structure (same array keys).

For further documentation
* Loyalty API Documentation: https://loyaltyapi.yotpo.com/reference
* Core API V3 Documentation: https://core-api.yotpo.com/reference

Example of use: create a customer in loyality program
````PHP
use Combindma\YotpoApi\Facades\YotpoApi;

//yotpoApiData is implemented in Combindma\YotpoApi\Traits\YotpoCustomer. You can create yours.
YotpoApi::createOrUpdateLoyaltyCustomer($user->yotpoApiData());
````

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Combind](https://github.com/combindma)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

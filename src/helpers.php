<?php

if (!function_exists('loyaltyReferLink')) {
    function loyaltyReferLink(string $code = ''): ?string
    {
        return config('yotpo.refer_link') . '/' . $code;
    }
}

if (!function_exists('loyaltyJsSdk')) {
    function loyaltyJsSdk(): ?string
    {
        return '<script type="text/javascript" async src="' . config('yotpo.loyalty_js_sdk_url') . '"></script>';
    }
}

if (!function_exists('loyaltyJsModule')) {
    function loyaltyJsModule(): ?string
    {
        return '<script src="' . config('yotpo.loyalty_modules_loader_url') . '" async></script>';
    }
}

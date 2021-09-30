<?php

if (! function_exists('loyaltyReferLink')) {
    function loyaltyReferLink(string $code = ''): ?string
    {
        return config('yotpo.refer_link').'/'.$code;
    }
}

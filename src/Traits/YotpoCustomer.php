<?php

namespace Combindma\YotpoApi\Traits;

Trait YotpoCustomer
{
    public function yotpoApiData(string $tags = 'registred,customer', string $currencyCode = null, string $countryCode = null)
    {
        return [
            'user_id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => [
                'address1' => $this->address,
                'city' => $this->city,
                'zip' => $this->postcode,
                'country_code' => $countryCode??config('yotpo.country'),
            ],
            'currency' => $currencyCode??config('yotpo.currency'),
            'tags' => $tags,
            'created_at' => $this->created_at->format('Y-m-d\TH:i:s\Z')
        ];
    }
}

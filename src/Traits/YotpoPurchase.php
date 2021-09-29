<?php

namespace Combindma\YotpoApi\Traits;

trait YotpoPurchase
{
    /*
     * Possible values for payment status
    - pending
    - authorized
    - partially_paid
    - paid
    - partially_refunded
    - refunded
    - voided
    */
    public function yotpoApiPurchasedData(string $tags = 'website', string $currencyCode = null, string $countryCode = null)
    {
        $orderItems = $this->items->map(function ($item) use($currencyCode){
            return [
                'quantity' => $item->quantity,
                'subtotal_price' => (double)$item->total,
                'total_price' => (double)$item->total,
                'product' => [
                    'external_id' => $item->product_id,
                    'name' => $item->product->name,
                    'url' => route('products.index', $item->product->slug),
                    'image_url' => $item->product->featured_image_url(),
                    'price' => (double)$item->product->price,
                    'currency' => $currencyCode??config('yotpo.currency'),
                    'inventory_quantity' => $item->product->quantity,
                    'is_discontinued' => false,
                    'brand' => $item->product->brand_name,
                    'sku' => $item->product->sku
                ]
            ];
        });
        $fulfilled_items = $this->items->map(function ($item){
            return [
                'external_product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ];
        });

        return [
            'order_number' => $this->order_number,
            'subtotal_price' => (double)$this->sub_total,
            'discount_amount' => (double)$this->discount_amount,
            'total_price' => (double)$this->total,
            'currency' => $currencyCode??config('yotpo.currency'),
            'payment_method' => $this->payment_method->description,
            'payment_status' => 'paid',
            'coupon_code' => $this->coupon_code,
            'customer' => [
                'external_id' => $this->user->id,
                'email_address' => $this->customer_email,
                'phone_number' => $this->customer_phone,//The phone number of the customer in E.164 format.
                'first_name' => $this->customer_name,
                'accepts_sms_marketing' => true,
                'accepts_email_marketing' => true,
            ],
            'customer_address' => [
                'address1' => $this->customer_address,
                'city' => $this->customer_city,
                'zip' => $this->customer_postcode,
                'country_code' => $countryCode??config('yotpo.country'),
            ],
            'order_items' => $orderItems,
            'order_fulfillments' => [
                'external_id' => $this->order_number,
                'fulfillment_date' => $this->updated_at->format('Y-m-d\TH:i:s\Z'),
                'status' => 'success',
                'shipment_info' => [
                    'shipment_status' => 'delivered',
                    'tracking_company' => config('carrier.'.$this->shipment->carrier_code.'.label'),
                    'tracking_url' => $this->shipment->carrier_url.$this->shipment->track_number,
                    'tracking_number' => $this->shipment->track_number
                ],
                'fulfilled_items' => $fulfilled_items
            ],
            'tags' => $tags,
            'created_at' => $this->created_at->format('Y-m-d\TH:i:s\Z')
        ];
    }
}

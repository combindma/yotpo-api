<?php

namespace Combindma\YotpoApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/*--------------------------------------------
 *
 *
 * loyalty API Documentation: https://loyaltyapi.yotpo.com/reference
 * Core API V3 Documentation: https://core-api.yotpo.com/reference
 *
 *
 *____________________________________________*/

class YotpoApi
{
    protected $storeId;
    protected $secretKey;
    protected $loyaltyApiKey;
    protected $loyaltyGuid;
    protected $baseUri = 'https://api.yotpo.com/core/v3/stores';
    protected $loyaltyBaseUri = 'https://loyalty.yotpo.com/api/v2';
    protected $uToken;
    protected $apiEnabled;
    protected $loyaltyEnabled;

    public function __construct()
    {
        $this->apiEnabled = config('yotpo.api_enabled');
        $this->loyaltyEnabled = config('yotpo.loyalty_enabled');
        $this->storeId = config('yotpo.app_key');
        $this->secretKey = config('yotpo.secret_key');
        $this->loyaltyApiKey = config('yotpo.loyalty_api_key');
        $this->loyaltyGuid = config('yotpo.loyalty_guid_key');
        $this->setUToken();
    }

    public function getUToken(): ?string
    {
        return $this->uToken;
    }

    public function getLoyaltyApiKey(): ?string
    {
        return $this->loyaltyApiKey;
    }

    public function getLoyaltyGuid(): ?string
    {
        return $this->loyaltyGuid;
    }

    public function getBaseUri(): string
    {
        return $this->baseUri . '/' . $this->storeId;
    }

    public function getLoyaltyBaseUri(): string
    {
        return $this->loyaltyBaseUri;
    }

    public function apiIsNotEnabled()
    {
        return !$this->apiEnabled;
    }

    public function loyaltyIsNotEnabled()
    {
        return !$this->loyaltyEnabled;
    }

    public function setUToken(): void
    {
        if ($this->apiEnabled){
            //get cached token for the last 7 days
            $this->uToken = Cache::remember('yotpoToken', 60 * 60 * 24 * 7, function () {
                return $this->getOauthToken();
            });
        }
    }

    public function resetUToken(): void
    {
        Cache::forget('yotpoToken');
        $this->uToken = $this->getOauthToken();
        //cache token for the last 7 days
        Cache::put('yotpoToken', $this->uToken, 60 * 60 * 24 * 7);
    }

    protected function getOauthToken(): ?string
    {
        $data['secret'] = $this->secretKey;
        $response = $this->sendRequest($data, 'POST', 'access_tokens');
        return optional($response)['access_token'];
    }





    /*--------------------------------------------
     *
     *
     *
     * ALL Loyalty & Referrals API methods
     *
     *
     * ____________________________________________*/

    /*
     * This endpoint records an action performed by a customer. It will apply the action
     * to all active custom action campaigns and award the necessary points and/or discounts.
     *
     * The type of action. Almost always set this to CustomAction
     * $actionName: The name of the action that was performed. This must match the name set for one of your Custom Action Campaigns.
     */
    public function recordLoyaltyAction(array $customerHash, string $actionName, string $ip, string $userAgent)
    {
        $data = [
            'type' => 'CustomAction',
            'customer_id' => $customerHash['user_id'],
            'action_name' => $actionName,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ];
        return $this->sendLoyaltyRequest($data, 'POST', 'actions');
    }

    //This endpoint both creates and updates a customer’s record in the Yotpo system.
    public function createOrUpdateLoyaltyCustomer(array $customerHash)
    {
        $data = [
            'id' => $customerHash['user_id'],
            'email' => $customerHash['email'],
            'first_name' => $customerHash['name'],
            'tags' => $customerHash['tags'],
            'has_account' => true,
        ];
        if (!empty($customerHash['phone'])){
            $data ['phone_number'] = $customerHash['phone'];
            //if unable to send phone_number in full E.164 format. Example: "US", "GB"..
            $data ['country_iso_code'] = $customerHash['address']['country_code'];
        }
        return $this->sendLoyaltyRequest($data, 'POST', 'customers');
    }

    //This endpoint returns a customer record. Most commonly used to fetch a customer’s point balance and unique referral link.
    public function getLoyaltyCustomer(array $customerHash)
    {
        $action = 'customers?customer_id=' . $customerHash['user_id'] . '&with_referral_code=false&with_history=false';
        return $this->sendLoyaltyRequest([], 'GET', $action);
    }

    //This endpoint both creates and updates a customer’s record in the Yotpo system.
    public function createOrUpdateLoyaltyAnniversary(array $customerHash, $day, $month)
    {
        $data = [
            'customer_email' => $customerHash['email'],
            'day' => $day,
            'month' => $month,
        ];
        return $this->sendLoyaltyRequest($data, 'POST', 'customer_anniversary');
    }

    /*
     * This endpoint will redeem a customer’s points for a particular redemption option. It will check to ensure the
     * customer is eligible and has enough points for the selected redemption option then it will deduct the points
     * from their balance, generate the coupon code, and return it in the response.
     * */
    public function CreateLoyaltyRedemption(array $customerHash, int $redemptionOptionId)
    {
        $data = [
            'customer_external_id' => (string)$customerHash['user_id'],
            'customer_email' => $customerHash['email'],
            'redemption_option_id' => $redemptionOptionId,
            'delay_points_deduction' => true,
            'currency' => $customerHash['currency']
        ];
        return $this->sendLoyaltyRequest($data, 'POST', 'redemptions');
    }

    /*
     * This endpoint will confirm the Redemption Cancellation Request webhook so that the customer will receive their
     * points from redemption back into their account. This is the final step in the process of invalidating an imported
     * coupon code to return a customer's point for our In-Store Widget.
     * */
    public function CancelLoyaltyRedemption(string $rewardText, int $pointRedemptionId)
    {
        $data = [
            'reward_text' => $rewardText
        ];
        return $this->sendLoyaltyRequest($data, 'POST', 'redemptions/' . $pointRedemptionId . '/cancellation_completed');
    }

    //$coupons: Up to 10,000 comma-separated coupon codes. ex: "code1,code2,code3"
    public function uploadLoyaltyCouponCodes(int $redemptionOptionId, string $coupons)
    {
        $data = [
            'redemption_option_id' => $redemptionOptionId,
            'codes' => $coupons,
        ];

        return $this->sendLoyaltyRequest($data, 'POST', 'redemption_codes');
    }

    //This endpoint returns a list of redemption options available for customers to redeem.
    public function getLoyaltyRedemptionOptions(array $customerHash, bool $offline = false)
    {
        $query = '?customer_id='.$customerHash['user_id'].'&is_offline='.$offline;
        return $this->sendLoyaltyRequest([], 'GET', 'redemption_options'.$query);
    }

    /*
     * This endpoint lets you fetch the email address of a customer who redeemed a discount by providing the discount code.
     * This enables merchants to validate (at checkout) if the shopper placing the order is different than the shopper who redeemed and used the discount.
     * */
    public function getLoyaltyRedemptionCode(string $code)
    {
        return $this->sendLoyaltyRequest([], 'GET', 'redemption_codes?code='.$code);
    }

    //Find or create a referral link for an email address.
    public function findOrCreateLoyaltyReferrer(array $customerHash)
    {
        $data = [
            'email' => $customerHash['email'],
            'first_name' => $customerHash['name'],
        ];
        return $this->sendLoyaltyRequest($data, 'POST', 'referral/referrer');
    }

    /*
     * Share referral link via email to a list of email addresses
     *
     * $emails: Comma-separated list of email addresses to share the referral link to ex:  "firstreferred@gmail.com,secondreferred@gmail.com"
     * */
    public function sendLoyaltyReferralEmails(array $customerHash, string $emails)
    {
        $data = [
            'customer_id' => $customerHash['user_id'],
            'emails' => $emails,
        ];
        return $this->sendLoyaltyRequest($data, 'POST', 'referral/share');
    }

    /*
     *
     * This endpoint returns a list of campaigns available for customers to participate in. If you provide a particular
     * customer we can return their current status and eligibility on each of the campaigns.
     * */
    public function getLoyaltyActiveCampaigns(array $customerHash, bool $withStatus = false)
    {
        $query = '?customer_id='.$customerHash['user_id'].'&with_status='.$withStatus;
        return $this->sendLoyaltyRequest([], 'GET', 'campaigns'.$query);
    }

    /*
     *
     * This endpoint records an order made by a customer. It will apply the order to all matching active campaigns and award the necessary points and/or discounts.
     *
     * Be sure that the user is authenticated before using this endpoint
     * */
    public function createLoyaltyOrder(array $purchaseHash, string $status= 'paid', string $ip = null, string $userAgent = null, bool $ignoreIpUa = true)
    {
        $data = [
            'order_id' => $purchaseHash['order_number'],
            'customer_email' => $purchaseHash['customer']['email_address'],
            'customer_id' => $purchaseHash['customer']['external_id'],
            'total_amount_cents' => $purchaseHash['total_price']*100, //The total amount the customer spent in cents.
            'discount_amount_cents' => $purchaseHash['discount_amount']*100,//The total amount the customer saved using a discount in cents
            'currency_code' => $purchaseHash['currency'],
            'coupon_code' => $purchaseHash['coupon_code'],
            'tags' => $purchaseHash['tags'],
            'status' => $status,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'ignore_ip_ua' => $ignoreIpUa,//If set to true, this will ignore the ip_address & user_agent fraud checks
            'channel_type' => 'online',
            'created_at' => $purchaseHash['created_at']
        ];
        return $this->sendLoyaltyRequest($data, 'POST', 'orders');
    }

    /*
     *
     * Send a new refund to the Yotpo API to adjust previously processed order. Requests are processed asynchronously.
     *
     * */
    public function refundLoyaltyOrder(string $orderId, $totalRefunded, string $currencyCode = 'MAD')
    {
        $data = [
            'order_id' => $orderId,
            'total_amount_cents' => (int)$totalRefunded*100, //The total amount in cents.
            'currency' => $currencyCode,
        ];
        return $this->sendLoyaltyRequest($data, 'POST', 'refunds');
    }


    /*
     * Fetch VIP Tiers
     */
    public function fetchLoyaltyVipTiers()
    {
        return $this->sendLoyaltyRequest([], 'GET', 'vip_tiers');
    }

    /*
     * A synchronous request that checks for existing account data (true/false) in the Yotpo loyalty system.
     * */
    public function userExistsInLoyaltyProgram(string $email)
    {
        $data = ['email' => $email];
        return $this->sendLoyaltyRequest($data, 'GET', 'privacy/data/exists')['has_data'];
    }







    /*--------------------------------------------
     *
     *
     *
     * ALL CORE API V3 methods
     *
     *
     * ____________________________________________*/


    public function createOrUpdateCustomer(array $customerHash)
    {
        $data = [
            'customer' => [
                'external_id' => $customerHash['user_id'],
                'email' => $customerHash['email'],
                'first_name' => $customerHash['name'],
                'address' => $customerHash['address'],
                'account_created_at' => $customerHash['created_at'], // Y-m-d\TH:i:s\Z format
                'account_status' => 'enabled',
                'default_language' => 'fr',
                'dafault_currency' => $customerHash['currency'],
                'tags' => $customerHash['tags'],
                'accepts_sms_marketing' => true,
                'accepts_email_marketing' => true,
            ]
        ];
        if (!empty($customerHash['phone'])){
            $data ['customer']['phone_number'] = $customerHash['phone'];
        }
        return $this->sendRequest($data, 'PATCH', 'customers');
    }

    public function createSubscriber(string $phone, string $listId)
    {
        $data = [
            'subscriber' => [
                'phone' => $phone,
                'list_id' =>$listId,
            ]
        ];
        return $this->sendRequest($data, 'POST', 'subscribers');
    }

    public function createPurchase(array $purchaseHash)
    {
        $data = [
            'purchase' => [
                'external_order_id' => $purchaseHash['order_number'],
                'order_date' => $purchaseHash['created_at'],
                'subtotal_price' => $purchaseHash['subtotal_price'],
                'total_price' => $purchaseHash['total_price'],
                'currency' => $purchaseHash['currency'],
                'payment_method' => $purchaseHash['payment_method'],
                'payment_status' => $purchaseHash['payment_status'],
                'customer' => $purchaseHash['customer'],
                'billing_address' => $purchaseHash['customer_address'],
                'shipping_address' => $purchaseHash['customer_address'],
                'line_items' => $purchaseHash['order_items'],
                'fulfillments' => $purchaseHash['order_fulfillments']
            ]
        ];
        return $this->sendRequest($data, 'POST', 'register_purchase');
    }

    public function createOrder(array $orderHash)
    {
        $data = [
            'order' => [
                'external_id' => $orderHash['order_number'],
                'order_date' => $orderHash['created_at'],
                'payment_method' => $orderHash['payment_method'],
                'total_price' => $orderHash['total_price'],
                'subtotal_price' => $orderHash['subtotal_price'],
                'currency' => $orderHash['currency'],
                'payment_status' => $orderHash['payment_status'],
                'customer' => $orderHash['customer'],
                'billing_address' => $orderHash['customer_address'],
                'shipping_address' => $orderHash['customer_address'],
                'line_items' => $orderHash['order_items'],
                'fulfillments' => $orderHash['order_fulfillments']
            ]
        ];
        return $this->sendRequest($data, 'POST', 'orders');
    }


    /*--------------------------------------------
     *
     *
     *
     * Requests for CORE API V3 and Loyalty & Referrals API
     *
     *
     * ____________________________________________*/

    protected function sendRequest($data, $type, $action): ?array
    {
        try {
            if ($this->apiIsNotEnabled())
            {
                return null;
            }
            $client = new Client();
            $body = (string)$client->request($type, $this->getBaseUri() . '/' . $action, [
                'body' => json_encode($data),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Yotpo-Token' => $this->getUToken()
                ],
            ])->getBody();

            return json_decode($body, true);
        } catch (GuzzleException | Exception $e) {
            if ($e->getCode() === 401){
                //if token expires we need to generate a new token
                $this->resetUToken();
            }
            if ($e->getCode() !== 404)
            {
                Log::error($e);
            }
            return null;
        }
    }

    protected function sendLoyaltyRequest($data, $type, $action): ?array
    {
        try {
            if ($this->loyaltyIsNotEnabled())
            {
                return null;
            }
            $client = new Client();
            $body = (string)$client->request($type, $this->getLoyaltyBaseUri() . '/' . $action, [
                'body' => json_encode($data),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-guid' => $this->getLoyaltyGuid(),
                    'x-api-key' => $this->getLoyaltyApiKey()
                ],
            ])->getBody();

            return json_decode($body, true);
        } catch (GuzzleException | Exception $e) {
            if ($e->getCode() !== 404)
            {
                Log::error($e);
            }
            return null;
        }
    }
}

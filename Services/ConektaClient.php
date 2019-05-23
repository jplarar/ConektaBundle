<?php

namespace Jplarar\ConektaBundle\Services;

use Conekta\Conekta;
use Conekta\Customer;
use Conekta\PaymentSource;
use Conekta\Subscription;
use Conekta\Order;

/**
 * Class ConektaClient
 * @package Jplarar\ConektaBundle\Services
 */
class ConektaClient
{
    protected $service;

    const API_VER = "2.0.0";

    // Order types
    const PAYMENT_OXXO_CASH = 'oxxo_cash';
    const PAYMENT_TOKEN = 'token_id';
    const PAYMENT_CARD = 'card';
    const PAYMENT_SPEI = 'spei';

    // Events
    const EVENT_CHARGE_CREATED = "charge.created";
    const EVENT_CHARGE_PAID = "charge.paid";
    const EVENT_CHARGE_REFUND = "charge.refunded";
    const EVENT_CHARGEBACK_CREATED = "charge.chargeback.created";
    const EVENT_CHARGEBACK_UPDATED = "charge.chargeback.updated";
    const EVENT_CHARGEBACK_REVIEW = "charge.chargeback.under_review";
    const EVENT_CHARGEBACK_WON = "charge.chargeback.won";
    const EVENT_CHARGEBACK_LOST = "charge.chargeback.lost";
    const EVENT_SUBSCRIPTION_CREATED = "subscription.created";
    const EVENT_SUBSCRIPTION_PAUSED = "subscription.paused";
    const EVENT_SUBSCRIPTION_RESUMED = "subscription.resumed";
    const EVENT_SUBSCRIPTION_CANCELED = "subscription.canceled";
    const EVENT_SUBSCRIPTION_UPDATED = "subscription.updated";
    const EVENT_SUBSCRIPTION_PAID = "subscription.paid";
    const EVENT_SUBSCRIPTION_FAIL = "subscription.payment_failed";


    /**
     * ConektaClient constructor.
     *
     * @param string $conekta_private_key
     */
    public function __construct($conekta_private_key = "")
    {
        Conekta::setApiKey($conekta_private_key);
        Conekta::setApiVersion(self::API_VER);
    }

    #########################
    ##       COSTUMER      ##
    #########################

    /**
     * @param $fullName
     * @param $email
     * @param $phoneNumber
     * @param $token
     *
     * @return mixed|string
     */
    public function createCustomer($fullName, $email, $phoneNumber, $token)
    {
        $customer = Customer::create(
            array(
                "name" => preg_replace("/[^A-Za-z ]/", '', $fullName),
                "email" => $email,
                "phone" => $phoneNumber,
                "payment_sources" => array(
                    array(
                        "type" => "card",
                        "token_id" => $token
                    )
                )//payment_sources
            )
        );

        return $customer;
    }

    /**
     * @param $customerId
     *
     * @return bool|string
     */
    public function deleteCustomer($customerId)
    {
        try {
            $customer = $this->getCustomer($customerId);
            $customer->delete();

            return true;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    /**
     * @param $customerId
     *
     * @return Customer|string
     */
    public function getCustomer($customerId)
    {
        try {
            /** @var Customer $customer */
            $customer = Customer::find($customerId);

            return $customer;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    #########################
    ##        Order        ##
    #########################

    /**
     * @param $name
     * @param $phone
     * @param $email
     * @param $productName
     * @param $unitPrice
     * @param $quantity
     * @param $street
     * @param $city
     * @param $state
     * @param $zipCode
     * @param $customerId
     * @param $sourceId
     * @param int $shippingAmount
     * @param string $shippingCarrier
     * @param string $country
     * @param string $currency
     * @return mixed|string
     */
    public function createCustomerOrder($name, $phone, $email, $productName, $unitPrice, $quantity, $street, $city, $state, $zipCode, $customerId, $sourceId, $shippingAmount = 0, $shippingCarrier = 'Fedex', $country = 'MX', $currency = 'MXN')
    {
        try {
            $order = Order::create(
                $this->generateOrderPayload(
                    self::PAYMENT_CARD,
                    $name,
                    $phone,
                    $email,
                    $street,
                    $city,
                    $state,
                    $zipCode,
                    $productName,
                    $unitPrice,
                    $quantity,
                    $customerId,
                    $sourceId,
                    null,
                    $shippingAmount,
                    $shippingCarrier,
                    $country,
                    $currency
                )
            );
            return $order;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $name
     * @param $phone
     * @param $email
     * @param $productName
     * @param $unitPrice
     * @param $quantity
     * @param $token
     * @param $street
     * @param $city
     * @param $state
     * @param $zipCode
     * @param int $shippingAmount
     * @param string $shippingCarrier
     * @param string $country
     * @param string $currency
     * @return mixed|string
     */
    public function createOneTimeOrder($name, $phone, $email,$productName, $unitPrice, $quantity, $token, $street, $city, $state, $zipCode, $shippingAmount = 0, $shippingCarrier = 'Fedex', $country = 'MX', $currency = 'MXN')
    {
        try {
            $order = Order::create(
                $this->generateOrderPayload(
                    self::PAYMENT_TOKEN,
                    $name,
                    $phone,
                    $email,
                    $street,
                    $city,
                    $state,
                    $zipCode,
                    $productName,
                    $unitPrice,
                    $quantity,
                    null,
                    null,
                    $token,
                    $shippingAmount,
                    $shippingCarrier,
                    $country,
                    $currency
                )
            );
            return $order;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $name
     * @param $phone
     * @param $email
     * @param $productName
     * @param $unitPrice
     * @param $quantity
     * @param $street
     * @param $city
     * @param $state
     * @param $zipCode
     * @param int $shippingAmount
     * @param string $shippingCarrier
     * @param string $country
     * @param string $currency
     * @return mixed|string
     */
    public function createOxxoOrder($name, $phone, $email, $productName, $unitPrice, $quantity, $street, $city, $state, $zipCode, $shippingAmount = 0, $shippingCarrier = 'Fedex', $country = 'MX', $currency = 'MXN')
    {
        try {
            $order = Order::create(
                $this->generateOrderPayload(
                    self::PAYMENT_OXXO_CASH,
                    $name,
                    $phone,
                    $email,
                    $street,
                    $city,
                    $state,
                    $zipCode,
                    $productName,
                    $unitPrice,
                    $quantity,
                    null,
                    null,
                    null,
                    $shippingAmount,
                    $shippingCarrier,
                    $country,
                    $currency
                )
            );
            return $order;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $name
     * @param $phone
     * @param $email
     * @param $street
     * @param $city
     * @param $state
     * @param $zipCode
     * @param $productName
     * @param $unitPrice
     * @param $quantity
     * @param int $shippingAmount
     * @param string $shippingCarrier
     * @param string $country
     * @param string $currency
     * @return mixed|string
     */
    public function createSpeiOrder($name, $phone, $email, $street, $city, $state, $zipCode, $productName, $unitPrice, $quantity, $shippingAmount = 0, $shippingCarrier = 'Fedex', $country = 'MX', $currency = 'MXN')
    {
        try {
            $order = Order::create(
                $this->generateOrderPayload(
                    self::PAYMENT_SPEI,
                    $name,
                    $phone,
                    $email,
                    $street,
                    $city,
                    $state,
                    $zipCode,
                    $productName,
                    $unitPrice,
                    $quantity,
                    null,
                    null,
                    null,
                    $shippingAmount,
                    $shippingCarrier,
                    $country,
                    $currency
                    )
            );
            return $order;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * @param $paymentType
     * @param $name
     * @param $phone
     * @param $email
     * @param $street
     * @param $city
     * @param $state
     * @param $zipCode
     * @param $productName
     * @param $unitPrice
     * @param $quantity
     * @param null $customerId
     * @param null $sourceId
     * @param null $token
     * @param int $shippingAmount
     * @param string $shippingCarrier
     * @param string $country
     * @param string $currency
     * @return array
     */
    private function generateOrderPayload(
        $paymentType,
        $name,
        $phone,
        $email,
        $street,
        $city,
        $state,
        $zipCode,
        $productName,
        $unitPrice,
        $quantity,
        $customerId = null,
        $sourceId = null,
        $token = null,
        $shippingAmount = 0,
        $shippingCarrier = 'Fedex',
        $country = 'MX',
        $currency = 'MXN') {

        $customer = [
            "name" => preg_replace("/[^A-Za-z ]/", '', $name),
            "email" => $email,
            "phone" => $phone
        ];
        if ($customerId) $customer = ["customer_id" => $customerId];
        switch ($paymentType) {
            case self::PAYMENT_SPEI:
                $paymentMethod = ["type" => self::PAYMENT_SPEI];
                break;
            case self::PAYMENT_CARD:
                $paymentMethod = ["type" => self::PAYMENT_CARD, "payment_source_id" => $sourceId];
                break;
            case self::PAYMENT_OXXO_CASH:
                $paymentMethod = ["type" => self::PAYMENT_OXXO_CASH];
                break;
            case self::PAYMENT_TOKEN:
                $paymentMethod = ["type" => self::PAYMENT_CARD, self::PAYMENT_TOKEN => $token];
                break;
            default:
                $paymentMethod = [];
                break;
        }

        $order = array(
                "currency" => $currency,
                "line_items" => array(
                    array(
                        "name" => $productName,
                        "unit_price" => $unitPrice,
                        "quantity" => $quantity
                    )
                ),
                "customer_info" => $customer,
                "shipping_contact" => $this->generateOrderAddress($zipCode, $name, $phone, $street, $city, $state, $country),
                "shipping_lines" => array(
                    array(
                        "amount" => $shippingAmount,
                        "carrier" => $shippingCarrier
                    )
                ),
                "charges" => array(
                    array(
                        "payment_method" => $paymentMethod
                    ) //first charge
                ) //charges
            );//order
        return $order;
    }

    /**
     * @param $zipCode
     * @param $name
     * @param $phone
     * @param $street
     * @param $city
     * @param $state
     * @param string $country
     * @return array
     */
    private function generateOrderAddress($zipCode, $name, $phone, $street, $city, $state, $country)
    {
        $zipCode = "{$zipCode}";
        while (strlen($zipCode) != 5) {
            $zipCode = '0'.$zipCode;
        }
        return array(
            'receiver' => preg_replace("/[^A-Za-z ]/", '', $name),
            'phone' => $phone,
            'address' => array(
                'street1' => $street,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'postal_code' => $zipCode
            )
        );
    }

    #########################
    ##     Subscription    ##
    #########################

    /**
     * @param $customerId
     * @param $plan
     *
     * @return Subscription|string
     */
    public function createSubscription($customerId, $plan)
    {
        try {
            /** @var Customer $customer */
            $customer = $this->getCustomer($customerId);
            /** @var Subscription $subscription */
            $subscription = $customer->createSubscription(
                array(
                    'plan' => $plan,
                )
            );

            return $subscription;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    /**
     * @param $customerId
     * @param $status
     * @param $token
     *
     * @return PaymentSource|string
     */
    public function updateSubscriptionCard($customerId, $status, $token)
    {
        try {
            /** @var Customer $customer */
            $customer = $this->getCustomer($customerId);

            /** @var \Conekta\PaymentSource $card */
            $card = $this->addPaymentSource($customerId, $token);
            $customer->update(['default_payment_source_id' => $card['id']]);

            if ($status !== "canceled") {
                $customer->subscription->update(
                    array(
                        'card' => $card['id'],
                    )
                );
            }

            return $card;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    /**
     * @param $customerId
     * @param $plan
     *
     * @return Subscription|string
     */
    public function updateSubscription($customerId, $plan)
    {
        try {
            /** @var Customer $customer */
            $customer = $this->getCustomer($customerId);
            /** @var Subscription $subscription */
            $subscription = $customer->subscription->update(
                array(
                    'plan' => $plan,
                )
            );

            return $subscription;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    /**
     * @param $customerId
     *
     * @return Subscription|string
     */
    public function cancelSubscription($customerId)
    {
        try {
            /** @var Customer $customer */
            $customer = $this->getCustomer($customerId);
            /** @var Subscription $subscription */
            $subscription = $customer->subscription->cancel();
            return $subscription;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    /**
     * @param $customerId
     * @return Subscription|string
     */
    public function getCustomerSubscription($customerId)
    {
        try {
            /** @var Customer $customer */
            $customer = $this->getCustomer($customerId);
            /** @var Subscription $subscription */
            $subscription = $customer->subscription;

            return $subscription;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    #########################
    ##    Payment Source   ##
    #########################

    /**
     * @param $customerId
     * @param $paymentSourceId
     *
     * @return PaymentSource|string
     */
    public function deletePaymentSource($customerId, $paymentSourceId)
    {
        try {
            /** @var Customer $customer */
            $customer = $this->getCustomer($customerId);
            /** @var PaymentSource $payment_source */
            foreach ($customer->payment_sources as $payment_source) {
                if ($payment_source['id'] === $paymentSourceId) {
                    $payment_source->delete();
                }
            }
            return $payment_source;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    /**
     * @param $customerId
     * @param $token
     *
     * @return PaymentSource|string
     */
    public function addPaymentSource($customerId, $token)
    {
        /** @var Customer $customer */
        $customer = $this->getCustomer($customerId);
        /** @var PaymentSource $card */
        $card = $customer->createPaymentSource(
            array(
                'type' => 'card',
                'token_id' => $token,
            )
        );
        return $card;
    }

    /**
     * @param $customerId
     * @param $sourceId
     *
     * @return Customer|string
     */
    public function removePaymentSource($customerId, $sourceId)
    {
        try {
            /** @var Customer $customer */
            $customer = $this->getCustomer($customerId);
            $customer->deletePaymentSourceById($sourceId);

            return $customer;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    /**
     * @param $customerId
     * @param $paymentSourceId
     * @return PaymentSource|null|string
     */
    public function updateDefaultCard($customerId, $paymentSourceId)
    {
        try {
            /** @var Customer $customer */
            $customer = $this->getCustomer($customerId);
            $source = null;
            /** @var PaymentSource $payment_source */
            foreach ($customer->payment_sources as $payment_source) {
                if ($payment_source['id'] === $paymentSourceId) {
                    $payment_source->update(
                        array(
                            'default' => true,
                        )
                    );
                    $source = $payment_source;
                } else {
                    $payment_source->update(
                        array(
                            'default' => false,
                        )
                    );
                }
            }
            return $source;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

}

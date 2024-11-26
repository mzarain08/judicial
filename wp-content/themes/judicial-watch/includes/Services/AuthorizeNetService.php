<?php

namespace Engage\JudicialWatch\Services;

use GuzzleHttp\Client as GuzzleClient;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Money\Currency;
use Money\Money;
use net\authorize\api\contract\v1\CreditCardType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\controller\CreateTransactionController;
use net\authorize\api\contract\v1\TransactionResponseType\ErrorsAType\ErrorAType;


class AuthorizeNetService
{
    public $apiLoginId;

    public $apiTransactionKey;

    public $apiEnvironment;

    public $merchantAuth;

    public function __construct()
    {
        if (!defined('AUTHORIZENET_API_KEYS')
            || !isset(AUTHORIZENET_API_KEYS['id'])
            || !isset(AUTHORIZENET_API_KEYS['transaction_key'])
        ) {
            $prodKeys = [
                //'transaction_key' => '38bL7YTp7v22ChVE',
                'transaction_key' => '23Us2jTf29C7AxKM',
                'id' => 'sn3a3V9uV'
            ];
            define('AUTHORIZENET_API_KEYS', $prodKeys);
        }

        if (!defined('WP_SERVER_ENVIRONMENT')) {
            $serverEnv = 'production';
        } else {
            $serverEnv = WP_SERVER_ENVIRONMENT;
        }

        $this->apiLoginId = AUTHORIZENET_API_KEYS['id'];
        $this->apiTransactionKey = AUTHORIZENET_API_KEYS['transaction_key'];

        $this->merchantAuth = new AnetAPI\MerchantAuthenticationType();
        $this->merchantAuth->setName($this->apiLoginId);
        $this->merchantAuth->setTransactionKey($this->apiTransactionKey);

        // API Env
        if ('production' === $serverEnv) {
            $this->apiEnvironment = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        } else {
            $this->apiEnvironment = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
        }
    }

    /**
     * Create Transaction From Post Request
     *
     * Given a POST request of a donation form, enact an Authorize.net transaction.
     *
     * @param $postRequest array
     * @return AnetAPI\AnetApiResponseType
     * @throws \Exception
     */
    public function createTransactionFromPostRequest($postRequest)
    {
    /** @var $customer AnetAPI\GetCustomerProfileResponse */
    $cardnumber = $postRequest['card']['number'];
    
    if ((WP_SERVER_ENVIRONMENT == 'production') && in_array($cardnumber, WP_TEST_CARDS)) {
        return wp_send_json([
        'success' => false,
        'message' => 'Sorry, we were unable to process this transaction.',
        ]);
    }

    
        $monthlySubscription = data_get($postRequest, 'isMonthlyDonation'); 
        $customerDataType = new AnetAPI\CustomerDataType();

        if($monthlySubscription == 'true' ){	
            //Do not create customer profile when the donation is not monthly.	
           //  if(!$newCustomer){	
           //     $customer =  $this->getCustomerProfileFromPostRequest($postRequest);	
           //  }else{	
           $customer = $this->getOrCreateCustomerFromPostRequest($postRequest);	
           //  }	
               
           $customerProfile = $customer->getProfile();	
           //Do not create customer profile when the donation is not monthly.	
          $customerDataType->setId($customerProfile->getMerchantCustomerId());	
        }

       $customerDataType->setType('individual');
       $customerDataType->setEmail(data_get($postRequest, 'person.email'));

       // Set the transaction's refId
        $refId = 'ref' . time();

        // Create the payment data for a credit card
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber(data_get($postRequest, 'card.number'));
        $creditCard->setExpirationDate(sprintf('%s-%s', data_get($postRequest, 'card.year'), data_get($postRequest, 'card.month')));
        $creditCard->setCardCode(data_get($postRequest, 'card.ccv'));

        // Add the payment data to a paymentType object
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        // Create order information
        $order = new AnetAPI\OrderType();
        $intCode = data_get($postRequest, 'int_code');
        if ($intCode) {
            // Came from a petition page;
                $order->setDescription($intCode);
                if($monthlySubscription == 'true' ){
                        $order->setDescription($intCode.' AMICUS');
                }
        }else{
                $order->setDescription(data_get($postRequest, 'mbTrackingCode'));
                if($monthlySubscription == 'true' ){
                        $order->setDescription(data_get($postRequest, 'mbTrackingCode'). ' AMICUS');
                }
        }

        // Set the customer's Bill To address
        $customerAddress = new AnetAPI\CustomerAddressType();
        $customerAddress->setFirstName(data_get($postRequest, 'person.name.first'));
        $customerAddress->setLastName(data_get($postRequest, 'person.name.last'));
        $customerAddress->setAddress(data_get($postRequest, 'person.address.street') . ' ' . data_get($postRequest, 'person.address.street_2'));
        $customerAddress->setCity(data_get($postRequest, 'person.address.city'));
        $customerAddress->setState(data_get($postRequest, 'person.address.state'));
        $customerAddress->setZip(data_get($postRequest, 'person.address.zipcode'));
        $customerAddress->setCountry('USA');
        $customerAddress->setEmail(data_get($postRequest, 'person.email'));
        $customerAddress->setPhoneNumber(data_get($postRequest, 'person.phone'));

        // Add values for transaction settings
        $duplicateWindowSetting = new AnetAPI\SettingType();
        $duplicateWindowSetting->setSettingName('duplicateWindow');
        $duplicateWindowSetting->setSettingValue(10);

        // MB Tracking Code
        $trackingField = new AnetAPI\UserFieldType();
        $trackingField->setName('md1');
        $trackingField->setValue(data_get($postRequest, 'mbTrackingCode'));

        // Create a TransactionRequestType object and add the previous objects to it
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType('authCaptureTransaction');
        $transactionRequestType->setAmount(data_get($postRequest, 'transaction_amount'));
        $transactionRequestType->setOrder($order);
        $transactionRequestType->setCustomer($customerDataType);
        if($monthlySubscription == 'true' ){
             //Do not create customer profile when the donation is not monthly.
          
         }
        $transactionRequestType->setPayment($paymentOne);
        $transactionRequestType->setBillTo($customerAddress);
        $transactionRequestType->setCustomerIP(null);
        $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
        $transactionRequestType->addToUserFields($trackingField);

        // Assemble the complete transaction request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuth);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);

        // Create the controller and get the response
        $controller = new AnetController\CreateTransactionController($request);
        $response   = $controller->executeWithApiResponse($this->apiEnvironment);

        //dd($response);

        //error_log("Request for Onetime:-> ".json_encode($request));
        //error_log("Response for Onetime:-> ".json_encode($response));

        //exit;

        if ($response) {
            return $response;
        }
    }

    /**
     * Create Monthly Subscription From Post Request
     *
     * Given a donation POST request, create a monthly subscription for the user.
     * Note: a separate request is made to create a transaction immediately, otherwise the user is not first billed until
     * the cycle is over.
     *
     * @param $postRequest
     * @return AnetAPI\ARBCreateSubscriptionRequest
     * @throws \Exception
     */
    public function createMonthlySubscriptionFromPostRequest($postRequest)
    {
        /** @var $customer AnetAPI\GetCustomerProfileResponse */
        $customer = $this->getOrCreateCustomerFromPostRequest($postRequest);
        $customerProfile = $customer->getProfile();

        // Customer payment type
        $customerDataType = new AnetAPI\CustomerType();
        $customerDataType->setType('individual');
        $customerDataType->setId($customerProfile->getMerchantCustomerId());
        $customerDataType->setEmail(data_get($postRequest, 'person.email'));

        // Set the transaction's refId
        $refId = 'ref' . time();

        // Subscription Type Info
        $subscription = new AnetAPI\ARBSubscriptionType();
        $subscription->setName('Recurring Donation');

        // Interval
        $interval = new AnetAPI\PaymentScheduleType\IntervalAType();
        $interval->setLength('1');
        $interval->setUnit('months');

        // Payment Schedule
        $paymentSchedule = new AnetAPI\PaymentScheduleType();
        $paymentSchedule->setInterval($interval);
        $paymentSchedule->setStartDate(new \DateTime('first day of next month'));
        $paymentSchedule->setTotalOccurrences('9999');
        $subscription->setPaymentSchedule($paymentSchedule);
        $subscription->setAmount(data_get($postRequest, 'transaction_amount'));

        // Credit Card
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber(data_get($postRequest, 'card.number'));
        $creditCard->setExpirationDate(sprintf('%s-%s', data_get($postRequest, 'card.year'), data_get($postRequest, 'card.month')));
        $creditCard->setCardCode(data_get($postRequest, 'card.ccv'));

        // Payment
        $payment = new AnetAPI\PaymentType();
        $payment->setCreditCard($creditCard);
        $subscription->setPayment($payment);

        // Order
        $order = new AnetAPI\OrderType();
        $intCode = data_get($postRequest, 'int_code');
    if ($intCode) {
            // Came from a petition page;
            $order->setDescription($intCode . ' AMICUS');
        } else {
            // Came from a non-petition page
            $order->setDescription(data_get($postRequest, 'mbTrackingCode') . ' AMICUS');
        }
    $subscription->setOrder($order);

        // Bill to
        $billTo = new AnetAPI\NameAndAddressType();
        $billTo->setFirstName(data_get($postRequest, 'person.name.first'));
        $billTo->setLastName(data_get($postRequest, 'person.name.last'));
        $billTo->setAddress(data_get($postRequest, 'person.address.street') . ' ' . data_get($postRequest, 'person.address.street_2'));
        $billTo->setCity(data_get($postRequest, 'person.address.city'));
        $billTo->setState(data_get($postRequest, 'person.address.state'));
        $billTo->setZip(data_get($postRequest, 'person.address.zipcode'));
        $subscription->setBillTo($billTo);
        $subscription->setCustomer($customerDataType);


        // MB Tracking Code
        $trackingField = new AnetAPI\UserFieldType();
        $trackingField->setName('md1');
        $trackingField->setValue(data_get($postRequest, 'mbTrackingCode'));

        // Subscription Request
        $request = new AnetAPI\ARBCreateSubscriptionRequest();
        $request->setmerchantAuthentication($this->merchantAuth);
        $request->setRefId($refId);
        $request->setSubscription($subscription);
        $controller = new AnetController\ARBCreateSubscriptionController($request);

        /** @var $response AnetAPI\ARBCreateSubscriptionRequest */
        $controller->executeWithApiResponse($this->apiEnvironment);

        //error_log("Request for Recurring:-> ".json_encode($request));
        //error_log("Response for Recurring:-> ".json_encode($response));

        /**
         * Enact first transaction immediately
         * If we don't, the user won't be billed til next cycle
         */
        $response = $this->createTransactionFromPostRequest($postRequest);


        if ($response) {
            return $response;
        }
    }

    /**
     * Get Or Create Customer From Post Request
     *
     * Given a donation POST request, attempt to find the user in Authorize.net if they exist.
     * If the user does not exist, create the user so we can bill them.
     *
     * @param $postRequest array
     * @return AnetAPI\AnetApiResponseType
     * @throws \Exception
     */
    public function getOrCreateCustomerFromPostRequest($postRequest)
    {
        $emailAddress = data_get($postRequest, 'person.email');
        if (!$emailAddress) {
            throw new \Exception('Email address is required for a donation');
        }

        /**
         * Attempt to get existing profile first
         */
        $customer = $this->getCustomerProfileFromPostRequest($postRequest);
        if (!$customer->getProfile()) {
            $this->createCustomerProfileFromPostRequest($postRequest);
            $customer = $this->getCustomerProfileFromPostRequest($postRequest);
        }

        return $customer;
    }

    /**
     * Get Customer Profile From Post Request
     *
     * Given a POST request, attempt to get the customer's profile.
     *
     * @param $postRequest array
     * @return AnetAPI\AnetApiResponseType
     * @throws \Exception
     */
    public function getCustomerProfileFromPostRequest($postRequest)
    {
        $emailAddress = data_get($postRequest, 'person.email');
        if (!$emailAddress) {
            throw new \Exception('Email address is required for a donation');
        }

        $request = new AnetAPI\GetCustomerProfileRequest();
        $request->setMerchantAuthentication($this->merchantAuth);
        $request->setEmail($emailAddress);
        $controller  = new AnetController\GetCustomerProfileController($request);

        return $controller->executeWithApiResponse($this->apiEnvironment);
    }

    /**
     * Create Customer Profile From Post Request
     *
     * Given a valid donation POST request, create a new customer profile in Authorize.net.
     * This should only be called after verifying the user does not already exist.
     *
     * @param $postRequest array
     * @return AnetAPI\CreateCustomerProfileResponse
     * @throws \Exception
     */
    public function createCustomerProfileFromPostRequest($postRequest)
    {
        $emailAddress = data_get($postRequest, 'person.email');
        if (!$emailAddress) {
            throw new \Exception('Email address is required for a donation');
        }

        // Set the transaction's refId
        $refId = 'ref' . time();

        // Set credit card information for payment profile
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber(data_get($postRequest, 'card.number'));
        $creditCard->setExpirationDate(sprintf('%s-%s', data_get($postRequest, 'card.year'), data_get($postRequest, 'card.month')));
        $creditCard->setCardCode(data_get($postRequest, 'card.ccv'));


        // Payment Type
        $paymentCreditCard = new AnetAPI\PaymentType();
        $paymentCreditCard->setCreditCard($creditCard);

        // Create the Bill To info for new payment type
        $billTo = new AnetAPI\CustomerAddressType();
        $billTo->setFirstName(data_get($postRequest, 'person.name.first'));
        $billTo->setLastName(data_get($postRequest, 'person.name.last'));
        $billTo->setAddress(data_get($postRequest, 'person.address.street') . ' ' . data_get($postRequest, 'person.address.street_2'));
        $billTo->setCity(data_get($postRequest, 'person.address.city'));
        $billTo->setState(data_get($postRequest, 'person.address.state'));
        $billTo->setZip(data_get($postRequest, 'person.address.zipcode'));
        $billTo->setCountry('USA');
        $billTo->setEmail(data_get($postRequest, 'person.email'));
        $billTo->setPhoneNumber(data_get($postRequest, 'person.phone'));

        // Create a customer shipping address
        // Commenting out the code since we don't want the shipping address in the print.
        
      

        $customerShippingAddress = new AnetAPI\CustomerAddressType();
        $customerShippingAddress->setFirstName("-");
        $customerShippingAddress->setLastName("-");
        $customerShippingAddress->setCompany("-");
        $customerShippingAddress->setAddress(rand() . " -");
        $customerShippingAddress->setCity("-");
        $customerShippingAddress->setState("-");
        $customerShippingAddress->setZip("-");
        $customerShippingAddress->setCountry("-");
        $customerShippingAddress->setPhoneNumber("-");
        $customerShippingAddress->setFaxNumber("-"); 

        // Create an array of any shipping addresses
        $shippingProfiles[] = $customerShippingAddress;

        // Create a new CustomerPaymentProfile object
        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setBillTo($billTo);
        $paymentProfile->setPayment($paymentCreditCard);
        $paymentProfile->setDefaultpaymentProfile(true);
        $paymentProfiles[] = $paymentProfile;


        // Create a new CustomerProfileType and add the payment profile object
        $customerProfile = new AnetAPI\CustomerProfileType();
        $customerProfile->setDescription($emailAddress);
        $customerProfile->setEmail($emailAddress);
        $customerProfile->setpaymentProfiles($paymentProfiles);
        
        // Commenting out the code since we don't want the shipping address in the print.
        /* $customerProfile->setShipToList($shippingProfiles); */


        // Assemble the complete transaction request
        $request = new AnetAPI\CreateCustomerProfileRequest();
        $request->setMerchantAuthentication($this->merchantAuth);
        $request->setRefId($refId);
        $request->setProfile($customerProfile);

        // Create the controller and get the response
        $controller = new AnetController\CreateCustomerProfileController($request);
        /** @var $response AnetAPI\CreateCustomerProfileResponse */
        $response  = $controller->executeWithApiResponse($this->apiEnvironment);

        if (!$response->getCustomerProfileId()) {
            dd($response);
            throw new \Exception('Unable to create customer profile, please try again later.');
        }

        return $response;
    }
}

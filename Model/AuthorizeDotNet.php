<?php
namespace Imranweb7\AuthorizeDotNet\Model;

use net\authorize\api\constants\ANetEnvironment;
use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\contract\v1\CreditCardType;
use net\authorize\api\contract\v1\CustomerAddressType;
use net\authorize\api\contract\v1\CustomerDataType;
use net\authorize\api\contract\v1\MerchantAuthenticationType;
use net\authorize\api\contract\v1\OrderType;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\contract\v1\SettingType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\controller\CreateTransactionController;

class AuthorizeDotNet extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'imranweb7_authorizedotnet';

    protected $_code = self::CODE;

    protected $_canAuthorize = true;
    protected $_canCapture = true;

    /**
     * Capture Payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            if(is_null($payment->getParentTransactionId())) {
                $this->authorize($payment, $amount);
            }

            $response = $this->makeCaptureRequest($payment, $amount);
            $payment->setIsTransactionClosed(1);

        } catch (\Exception $e) {
            $this->debug($payment->getData(), $e->getMessage());
        }

        return $this;
    }

    /**
     * Authorize a payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $response = $this->makeAuthRequest($payment, $amount);

        } catch (\Exception $e) {
            $this->debug($payment->getData(), $e->getMessage());
        }

        if(isset($response['transactionID'])) {
            $payment->setTransactionId($response['transactionID']);
            $payment->setParentTransactionId($response['transactionID']);
        }

        $payment->setIsTransactionClosed(0);

        return $this;
    }


    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeAuthRequest(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $responseCode = [];
        $responseSuccess = false;
        $response = $this->processRequest($payment, $amount, "authorize");

        if ($response != null) {
            if ($response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $responseSuccess = true;
                    $responseCode['transactionId'] = $tresponse->getTransId();
                }
            }
        }

        if(!$responseSuccess) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Failed auth request.'));
        }

        return $response;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeCaptureRequest(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $responseCode = [];
        $responseSuccess = false;
        $response = $this->processRequest($payment, $amount, "capture");

        if ($response != null) {
            if($response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $responseSuccess = true;
                    $responseCode[] = 'success';
                }

            }
        }

        if(!$responseSuccess) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Failed capture request.'));
        }

        return $responseCode;
    }

    private function processRequest(\Magento\Payment\Model\InfoInterface $payment, $amount, $action)
    {
        $authorizeOnly = false;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $merchantAuthentication = new MerchantAuthenticationType();
        $merchantAuthentication->setName($this->getConfigData('api_login_id'));
        $merchantAuthentication->setTransactionKey($this->getConfigData('api_trans_key'));

        $request = new CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);

        $transactionRequestType = new TransactionRequestType();

        switch ($action) {
            case "authorize":
                $authorizeOnly = true;

                $orderType = new OrderType();
                $orderType->setInvoiceNumber($order->getIncrementId());
                $orderType->setDescription("");

                $duplicateWindowSetting = new SettingType();
                $duplicateWindowSetting->setSettingName("duplicateWindow");
                $duplicateWindowSetting->setSettingValue("60");

                $creditCard = new CreditCardType();
                $creditCard->setCardNumber($payment->getCcNumberEnc());
                $creditCard->setExpirationDate($payment->getCcExpYear() . "-" . $payment->getCcExpMonth());
                $creditCard->setCardCode($payment->getCcType());

                $paymentOne = new PaymentType();
                $paymentOne->setCreditCard($creditCard);

                $customerData = new CustomerDataType();
                $customerData->setType("individual");
                $customerData->setId($order->getCustomerId());
                $customerData->setEmail($order->getCustomerEmail());

                $transactionRequestType->setAmount($amount);
                $transactionRequestType->setOrder($orderType);
                $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
                $transactionRequestType->setTransactionType("authOnlyTransaction");
                $transactionRequestType->setPayment($paymentOne);
                $transactionRequestType->setCustomer($customerData);

                $refId = 'ref' . time();
                $request->setRefId($refId);
                break;

            case "capture":
                $transactionRequestType->setTransactionType("priorAuthCaptureTransaction");
                $transactionRequestType->setRefTransId($this->getRealParentTransactionId($payment));
                break;
        }


        if (!empty($order) && $authorizeOnly) {
            $billing = $order->getBillingAddress();
            if (!empty($billing)) {
                $billingAddress = new CustomerAddressType();
                $billingAddress->setFirstName($billing->getFirstname());
                $billingAddress->setLastName($billing->getLastname());
                $billingAddress->setCompany($billing->getCompany());
                $billingAddress->setAddress($billing->getStreetLine(1));
                $billingAddress->setCity($billing->getCity());
                $billingAddress->setState($billing->getRegion());
                $billingAddress->setZip($billing->getPostcode());
                $billingAddress->setCountry("USA");

                $transactionRequestType->setBillTo($billingAddress);
            }

            $shipping = $order->getShippingAddress();
            if (!empty($shipping)) {
                $shippingAddress = new CustomerAddressType();
                $shippingAddress->setFirstName($shipping->getFirstname());
                $shippingAddress->setLastName($shipping->getLastname());
                $shippingAddress->setCompany($shipping->getCompany());
                $shippingAddress->setAddress($shipping->getStreetLine(1));
                $shippingAddress->setCity($shipping->getCity());
                $shippingAddress->setState($shipping->getRegion());
                $shippingAddress->setZip($shipping->getPostcode());
                $shippingAddress->setCountry("USA");

                $transactionRequestType->setShipTo($shippingAddress);
            }
        }

        $request->setTransactionRequest($transactionRequestType);

        $controller = new CreateTransactionController($request);

        $apiUrl = ANetEnvironment::SANDBOX;
        if($this->getConfigData('test') == 0){
            $apiUrl = ANetEnvironment::PRODUCTION;
        }

        $response = $controller->executeWithApiResponse($apiUrl);
        return $response;
    }
}
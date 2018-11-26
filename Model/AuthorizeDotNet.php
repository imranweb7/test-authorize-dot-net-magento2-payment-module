<?php
namespace Imranweb7\AuthorizeDotNet\Model;

class AuthorizeDotNet extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'imranweb7_authorizedotnet';

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        //todo add functionality later
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        //todo add functionality later
    }
}
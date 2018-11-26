# test-authorize-dot-net-magento2-payment-module
Test Authorize.Net Magento2 Payment Module

# How to Install?
1) Run following command in magento root:
php composer require imranweb7/test-authorize-dot-net-magento2-payment-module

2) Enable module using following command in magento root:
php bin/magento module:enable Imranweb7_AuthorizeDotNet

3) Update databse using following command in magento root:
php bin/magento setup:upgrade

4) Configure module in Admin -> Stores -> Configurations -> Sales -> Payment Methods -> Test Authorize.Net Payment Gateway

<?php
/**
 * (c) Payrexx AG <info@payrexx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Ueli Kramer <dev@payrexx.com>
 */

namespace PayrexxPaymentGateway\Components\PayrexxGateway;

use Payrexx\Models\Response\Gateway;

class PayrexxGatewayService
{
    /**
     * Check the Payrexx Gateway status whether it is paid or not
     *
     * @param integer $gatewayId The Payrexx Gateway ID
     * @return bool TRUE if the payment has been confirmed, FALSE if it is not confirmed
     */
    public function checkPayrexxGatewayStatus($gatewayId)
    {
        if (!$gatewayId) {
            return false;
        }
        $payrexx = $this->getInterface();
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setId($gatewayId);
        try {
            $gateway = $payrexx->getOne($gateway);
            return ($gateway->getStatus() == 'confirmed');
        } catch (\Payrexx\PayrexxException $e) {
        }
        return false;
    }

    /**
     * get the Payrexx Transaction
     * @param $gatewayId
     * @return bool|string
     */
    public function getPayrexxTransactionByGatewayID($gatewayId)
    {
        if (!$gatewayId) {
            return false;
        }

        $payrexx = $this->getInterface();
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setId($gatewayId);

        try {
            /** @var \Payrexx\Models\Request\Gateway $gateway */
            $gateway = $payrexx->getOne($gateway);
            if($gateway){
                $invoices = $gateway->getInvoices();
                if($invoices && $invoices[0]){
                    $invoice = $invoices[0];
                    $transactions = $invoice['transactions'];
                    if($transactions && $transactions[0]){
                        return $transactions[0];
                    }
                }
            }
        } catch (\Payrexx\PayrexxException $e) {
            return $e->getMessage();
        }
        return false;
    }

    /**
     * get the Payrexx Transaction
     * @param $transactionId
     * @return bool|\Payrexx\Models\Request\Transaction
     */
    public function getTransaction($transactionId)
    {
        if (!$transactionId) {
            return false;
        }

        $payrexx = $this->getInterface();
        $gateway = new \Payrexx\Models\Request\Transaction();
        $gateway->setId($transactionId);

        try {
            /** @var \Payrexx\Models\Request\Transaction $transaction */
            $transaction = $payrexx->getOne($gateway);
            if($transaction){
                return $transaction;
            }
        } catch (\Payrexx\PayrexxException $e) {
            //return $e->getMessage();
        }
        return false;
    }

    /**
     * capture a Transaction
     *
     * @param integer $gatewayId The Payrexx Gateway ID
     * @return string
     */
    public function captureTransaction($gatewayId)
    {
        if (!$gatewayId) {
            return false;
        }
        $payrexx = $this->getInterface();

        $transaction = new \Payrexx\Models\Request\Transaction();
        $transaction->setId($gatewayId);

        try {
            $response = $payrexx->capture($transaction);
            //var_dump($response);
            return $response;
        } catch (\Payrexx\PayrexxException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @return \Payrexx\Payrexx
     */
    private function getInterface()
    {
        $shop = false;
        if (Shopware()->Container()->initialized('shop')) {
            $shop = Shopware()->Container()->get('shop');
        }

        if (!$shop) {
            $shop = Shopware()->Container()->get('models')->getRepository(\Shopware\Models\Shop\Shop::class)->getActiveDefault();
        }

        $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('PayrexxPaymentGateway', $shop);
        return new \Payrexx\Payrexx($config['instanceName'], $config['apiKey']);
    }

    /**
     * Create a checkout page in Payrexx (Payrexx Gateway)
     *
     * @param $orderNumber
     * @param $amount
     * @param $currency
     * @param $paymentMean
     * @param $user
     * @param $urls
     * @param $basket
     * @param $shippingAmount
     * @return Gateway
     *
     */
    public function createPayrexxGateway($orderNumber, $amount, $currency, $paymentMean, $user, $urls, $basket, $shippingAmount = 0)
    {
        $billingInformation = $user['billingaddress'];

        if (!empty($shippingAmount)) {
            $shippingAmount = $shippingAmount * 100;
        }

        $payrexx = $this->getInterface();
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setAmount($amount * 100);
        $gateway->setCurrency($currency);
        $gateway->setSuccessRedirectUrl($urls['successUrl']);
        $gateway->setFailedRedirectUrl($urls['errorUrl']);
        $gateway->setCancelRedirectUrl($urls['errorUrl']);
        $gateway->setSkipResultPage(true);

        $gateway->setPsp(array());
        $gateway->setPm(array($paymentMean));
        $gateway->setReferenceId($orderNumber);
        $gateway->setValidity(60);

        $gateway->addField('forename', $billingInformation['firstname']);
        $gateway->addField('surname', $billingInformation['lastname']);
        $gateway->addField('company', $billingInformation['company']);
        $gateway->addField('street', $billingInformation['street']);
        $gateway->addField('postcode', $billingInformation['zipcode']);
        $gateway->addField('place', $billingInformation['city']);
        $gateway->addField('email', $user['additional']['user']['email']);

        // country
        if (!empty($user['additional']['countryShipping']['countryiso'])) {
            $country = $user['additional']['countryShipping']['countryiso'];
        }
        if (empty($country)) {
            $country = $user['additional']['country']['countryiso'];
        }
        if (!empty($country)) {
            $gateway->addField('country', $country);
        }

        $products = [];
        if (!empty($basket) && !empty($basket['content'])) {
            foreach ($basket['content'] as $item) {
                $amount = $item['additional_details']['price_numeric'] ?: $item['amountNumeric'];
                $products[] = [
                    'name' => $item['articlename'],
                    'description' => $item['additional_details']['description'] ?: '',
                    'quantity' => $item['quantity'],
                    'amount' => $amount * 100,
                    'sku' => $item['ordernumber'],
                ];
            }
        }

        if (!empty($shippingAmount)) {
            $products[] = [
                'name' => 'Versandkosten',
                'amount' => $shippingAmount,
                'quantity' => 1
            ];
        }

        if (!empty($products)) {
            $gateway->setBasket($products);
        }


        try {
            return $payrexx->create($gateway);
        } catch (\Payrexx\PayrexxException $e) {
        }
        return null;
    }
}

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
use PayrexxPaymentGateway\Components\Services\ConfigService;

class PayrexxGatewayService
{

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
        $transactionReq = new \Payrexx\Models\Request\Transaction();
        $transactionReq->setId($transactionId);

        try {
            /** @var \Payrexx\Models\Request\Transaction $transaction */
            $transaction = $payrexx->getOne($transactionReq);
            if ($transaction) {
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
        /** @var ConfigService $configService */
        $configService = Shopware()->Container()->get('prexx_payment_payrexx.config_service');
        $config = $configService->getConfig();

        $platform = !empty($config['platform']) ? $config['platform'] : '';
        return new \Payrexx\Payrexx($config['instanceName'], $config['apiKey'], '', $platform);
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
    public function createPayrexxGateway($orderNumber, $totalAmount, $currency, $paymentMean, $user, $urls, $basket, $shippingAmount = 0)
    {
        $billingInformation = $user['billingaddress'];

        if (!empty($shippingAmount)) {
            $shippingAmount = $shippingAmount * 100;
        }

        $products = [];
        $shopwareConfig = Shopware()->Config();
        $amountNet = $shopwareConfig->get('sARTICLESOUTPUTNETTO');
        $basketAmount = 0;
        if (!empty($basket) && !empty($basket['content'])) {
            foreach ($basket['content'] as $item) {
                $amount = (float)$item['priceNumeric'];
                $tax = (float)str_replace(',', '.', $item['tax']);
                if ($amountNet) {
                    $amount += $tax;
                }

                $products[] = [
                    'name' => $item['articlename'],
                    'description' => $item['additional_details']['description'] ?: '',
                    'quantity' => $item['quantity'],
                    'amount' => $amount * 100,
                    'sku' => $item['ordernumber'],
                ];
                $basketAmount += $amount;
            }
        }

        if (!empty($shippingAmount)) {
            $products[] = [
                'name' => 'Shipping',
                'amount' => $shippingAmount,
                'quantity' => 1
            ];
            $basketAmount += $shippingAmount;
        }

        $payrexx = $this->getInterface();
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setAmount($totalAmount * 100);
        $gateway->setCurrency($currency);
        $gateway->setSuccessRedirectUrl($urls['successUrl']);
        $gateway->setFailedRedirectUrl($urls['errorUrl']);
        $gateway->setCancelRedirectUrl($urls['errorUrl']);
        $gateway->setSkipResultPage(true);

        $gateway->setPsp(array());
        $gateway->setPm(array($paymentMean));
        $gateway->setReferenceId($orderNumber);
        $gateway->setValidity(15);

        if (!empty($products) && $basketAmount === $totalAmount) {
            $gateway->setBasket($products);
        }

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


        try {
            return $payrexx->create($gateway);
        } catch (\Payrexx\PayrexxException $e) {
        }
        return null;
    }
}

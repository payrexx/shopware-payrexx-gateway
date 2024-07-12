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
use Payrexx\Models\Response\Transaction;
use PayrexxPaymentGateway\Components\Services\ConfigService;

class PayrexxGatewayService
{

    /**
     *
     * @param int $gatewayId
     * @return \Payrexx\Models\Response\Gateway|null
     */
    public function getPayrexxGateway($gatewayId)
    {
        if (!$gatewayId) {
            return null;
        }

        $payrexx = $this->getInterface();
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setId($gatewayId);

        try {
            return $payrexx->getOne($gateway);
        } catch (\Payrexx\PayrexxException $e) {
        }
        return null;
    }

    /**
     * get the Payrexx Transaction
     * @param $gatewayId
     * @return bool|string
     */
    public function getPayrexxTransactionByGateway($gateway)
    {
        if (!in_array($gateway->getStatus(), [Transaction::CONFIRMED, Transaction::WAITING])) {
            return false;
        }

        $invoices = $gateway->getInvoices();

        if (!$invoices || !$invoice = end($invoices)) {
            return null;
        }

        if (!$transactions = $invoice['transactions']) {
            return null;
        }

        return $this->getTransaction(end($transactions)['id']);
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
                $basketAmount += $amount * $item['quantity'];
            }
        }

        if (!empty($shippingAmount)) {
            $products[] = [
                'name' => 'Shipping',
                'amount' => $shippingAmount * 100,
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

        $billingCountry = $user['additional']['country']['countryiso'] ?? '';
        $shippingCountry = $user['additional']['countryShipping']['countryiso'] ?? '';

        $gateway->addField('forename', $billingInformation['firstname']);
        $gateway->addField('surname', $billingInformation['lastname']);
        $gateway->addField('company', $billingInformation['company']);
        $gateway->addField('street', $billingInformation['street']);
        $gateway->addField('postcode', $billingInformation['zipcode']);
        $gateway->addField('place', $billingInformation['city']);
        $gateway->addField('email', $user['additional']['user']['email']);
        $gateway->addField('country', $billingCountry ?? $shippingCountry);

        $shippingAddress = $user['shippingaddress'];
        $gateway->addField('delivery_forename', $shippingAddress['firstname']);
        $gateway->addField('delivery_surname', $shippingAddress['lastname']);
        $gateway->addField('delivery_company', $shippingAddress['company']);
        $gateway->addField('delivery_street', $shippingAddress['street']);
        $gateway->addField('delivery_postcode', $shippingAddress['zipcode']);
        $gateway->addField('delivery_place', $shippingAddress['city']);
        $gateway->addField('delivery_country', $shippingCountry ?? $billingCountry);

        try {
            return $payrexx->create($gateway);
        } catch (\Payrexx\PayrexxException $e) {
        }
        return null;
    }
}

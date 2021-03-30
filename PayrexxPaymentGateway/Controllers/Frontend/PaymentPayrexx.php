<?php
/**
 * (c) Payrexx AG <info@payrexx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Ueli Kramer <dev@payrexx.com>
 */

use PayrexxPaymentGateway\Components\PayrexxGateway\PayrexxGatewayService;
use PayrexxPaymentGateway\PayrexxPaymentGateway;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Status;
use Shopware\Models\Order\Order;
use Doctrine\ORM\EntityManagerInterface;

class Shopware_Controllers_Frontend_PaymentPayrexx extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * @inheritdoc
     */
    public function getWhitelistedCSRFActions()
    {
        return array('notify', 'webhook');
    }

    /**
     * After clicking the order confirmation button, the user gets redirected
     * to this action, which will redirect the user to the newly generated Payrexx gateway page
     */
    public function indexAction()
    {
        $orderService = $this->container->get('prexx_payment_payrexx.order_service');

        // Workaround if amount is 0
        if ($this->getAmount() <= 0) {
            $this->saveOrder(time(), $this->createPaymentUniqueId(), Status::PAYMENT_STATE_COMPLETELY_PAID);
            Shopware()->Session()->offsetUnset('prexxPaymentPayrexx');
            $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
            return;
        }

        if (!$this->getOrderNumber()) {
            $this->saveOrder(time(), $this->createPaymentUniqueId(), Status::PAYMENT_STATE_OPEN, false);
        }

        // Get the Payrexx Gateway object
        $payrexxGateway = $this->getPayrexxGateway();

        $orderService->addTransactionIdToOrder($this->getOrderNumber(), $payrexxGateway->getId());

        Shopware()->Session()->prexxPaymentPayrexx['gatewayId'] = $payrexxGateway->getId();
        // Create Payrexx Gateway link for checkout and redirect user
        $providerUrl = $this->getProviderUrl();
        $this->redirect($providerUrl . '?payment=' . $payrexxGateway->getHash());
    }

    /**
     * @return string Get the base URL for the Payrexx Gateway checkout page by instanceName setting
     */
    private function getProviderUrl()
    {
        $shop = false;
        if ($this->container->initialized('shop')) {
            $shop = $this->container->get('shop');
        }

        if (!$shop) {
            $shop = $this->container->get('models')->getRepository(\Shopware\Models\Shop\Shop::class)->getActiveDefault();
        }

        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('PayrexxPaymentGateway', $shop);
        return 'https://' . $config['instanceName'] . '.payrexx.com/';
    }

    /**
     * Create a Payrexx Gateway with Payrexx API
     * @return Payrexx\Models\Response\Gateway
     */
    private function getPayrexxGateway()
    {
        /** @var PayrexxGatewayService $service */
        $service = $this->container->get('prexx_payment_payrexx.payrexx_gateway_service');
        $router = $this->Front()->Router();
        $user = $this->getUser();
        $basket = $this->getBasket();
        $shippingAmount = $this->getShipment();

        // Define the return urls (successful / cancel urls)
        $successUrl = $router->assemble(array('action' => 'return', 'forceSecure' => true));
        $errorUrl = $router->assemble(array('action' => 'cancel', 'forceSecure' => true));

        $paymentMean = str_replace(PayrexxPaymentGateway::PAYMENT_MEAN_PREFIX, '', $this->getPaymentShortName());

        return $service->createPayrexxGateway(
            $this->getOrderNumber(),
            $this->getAmount(),
            $this->getCurrencyShortName(),
            $paymentMean,
            $user,
            array(
                'successUrl' => $successUrl,
                'errorUrl' => $errorUrl,
            ),
            $basket,
            $shippingAmount
        );
    }

    /**
     * Redirection to finish page
     * If the Payrexx Gateway has been paid, the order gets persisted to database
     */
    public function returnAction()
    {
        /** @var PayrexxGatewayService $service */
        $gatewayId = Shopware()->Session()->prexxPaymentPayrexx['gatewayId'];
        $service = $this->container->get('prexx_payment_payrexx.payrexx_gateway_service');

        $orderService = $this->container->get('prexx_payment_payrexx.order_service');
        $order = $orderService->getShopwareOrderByNumber($this->getOrderNumber());

        if (!$service->checkPayrexxGatewayStatus(Shopware()->Session()->prexxPaymentPayrexx['gatewayId'])) {
            $transaction = $service->getPayrexxTransactionByGatewayID(Shopware()->Session()->prexxPaymentPayrexx['gatewayId']);

            if ($transaction && $transaction['uuid'] && $transaction['id']) {
                $this->handleTransactionStatus($transaction['status'], $gatewayId, $order);
                Shopware()->Session()->offsetUnset('prexxPaymentPayrexx');
                $orderService->addTransactionIdToOrder($this->getOrderNumber(), $transaction->getUuid());
            }
        }

        $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
    }

    /**
     * Redirect to finish page
     * The payment has not been processed, means that the user has to start ordering again
     */
    public function cancelAction()
    {
        /** @var Order|null $order */

        $orderService = $this->container->get('prexx_payment_payrexx.order_service');
        $order = $orderService->getShopwareOrderByNumber($this->getOrderNumber());


        if ($order instanceof Order) {
            $orderService->restoreCartFromOrder($order);
        }

        $this->redirect(['controller' => 'checkout', 'action' => 'payment']);
    }

    public function webhookAction()
    {
        $orderService = $this->container->get('prexx_payment_payrexx.order_service');
        $payrexxApiService = $this->container->get('prexx_payment_payrexx.payrexx_gateway_service');

        $requestTransaction = $this->request()->getParam('transaction');
        $swOrderId = $requestTransaction['referenceId'];
        $requestGatewayId = $requestTransaction['invoice']['paymentRequestId'];
        $requestTransactionStatus = $requestTransaction['status'];

        // check required data
        if (!$swOrderId || !$requestTransactionStatus) {
            throw new \Exception('Payrexx Webhook Data incomplete');
        }

        $order = $orderService->getShopwareOrderByNumber($swOrderId);
        if (!$order instanceof Order) {
            throw new \Exception('No order found with ID ' . $swOrderId);
        }
        if (!$order->getTransactionId() == $requestGatewayId) {
            throw new \Exception('Transaction ID ('.$order->getTransactionId().') does not match requests gateway ID ( '.$requestGatewayId.')');
        }
        $transaction = $payrexxApiService->getTransaction($requestTransaction['id']);

        if ($requestTransactionStatus != $transaction->getStatus()) {
            throw new \Exception('Corrupt webhook status');
        }

        $this->handleTransactionStatus($requestTransactionStatus, $requestGatewayId, $order);
        die;
    }

    private function handleTransactionStatus($requestTransactionStatus,$requestGatewayId, $order) {
        $status = null;
        switch ($requestTransactionStatus) {
            case \Payrexx\Models\Response\Transaction::CONFIRMED:
                $status = Status::PAYMENT_STATE_COMPLETELY_PAID;
                break;
            case \Payrexx\Models\Response\Transaction::WAITING:
                $status = Status::PAYMENT_STATE_OPEN;
                break;
            case \Payrexx\Models\Response\Transaction::REFUNDED:
            case \Payrexx\Models\Response\Transaction::PARTIALLY_REFUNDED:
                $status = Status::PAYMENT_STATE_RE_CREDITING;
                break;
            case \Payrexx\Models\Response\Transaction::CANCELLED:
            case \Payrexx\Models\Response\Transaction::EXPIRED:
            case \Payrexx\Models\Response\Transaction::ERROR:
                $status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                break;
        }

        if (!$status) {
            return;
        }

        try {
            $this->savePaymentStatus($requestGatewayId, $order->getTemporaryId(), $status, true);
        } catch(\Payrexx\PayrexxException $e) {
            throw new \Exception('Saving payment status failed');
        }
    }
}

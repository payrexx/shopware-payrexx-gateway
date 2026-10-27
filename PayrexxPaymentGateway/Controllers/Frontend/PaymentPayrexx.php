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
use PayrexxPaymentGateway\Components\Services\ConfigService;
use PayrexxPaymentGateway\Components\Services\OrderService;
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
        /** @var OrderService $orderService */
        $orderService = $this->container->get('prexx_payment_payrexx.order_service');

        /** @var ConfigService $configService */
        $configService = $this->container->get('prexx_payment_payrexx.config_service');
        $config = $configService->getConfig();

        // Workaround if amount is 0
        if ($this->getAmount() <= 0) {
            $this->saveOrder(time(), $this->createPaymentUniqueId(), Status::PAYMENT_STATE_COMPLETELY_PAID);
            $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
            return;
        }

        if (!$this->getOrderNumber() && $config['orderBeforePayment']) {
            $this->saveOrder(time(), $this->createPaymentUniqueId(), Status::PAYMENT_STATE_OPEN, false);
        }

        // Get the Payrexx Gateway object
        $payrexxGateway = $this->createPayrexxGateway();
        if (!$payrexxGateway) {
            $router = $this->Front()->Router();
            $errorUrl = $router->assemble(array('action' => 'cancel', 'forceSecure' => true));
            $this->redirect($errorUrl);
            return;
        }

        if ($config['orderBeforePayment']) {
            $orderService->addTransactionIdToOrder($this->getOrderNumber(), $payrexxGateway->getId());
        }

        $this->container->get('session')->offsetSet('payrexxGatewayId', $payrexxGateway->getId());
        // Create Payrexx Gateway link for checkout and redirect user
        $this->redirect($payrexxGateway->getLink());
    }

    /**
     * Create a Payrexx Gateway with Payrexx API
     * @return Payrexx\Models\Response\Gateway
     */
    private function createPayrexxGateway()
    {
        /** @var PayrexxGatewayService $gatewayService */
        $gatewayService = $this->container->get('prexx_payment_payrexx.payrexx_gateway_service');

        $router = $this->Front()->Router();
        $user = $this->getUser();
        $basket = $this->getBasket();
        $shippingAmount = $this->getShipment();

        // Define the return urls (successful / cancel urls)
        $successUrl = $router->assemble(array('action' => 'return', 'forceSecure' => true));
        $errorUrl = $router->assemble(array('action' => 'cancel', 'forceSecure' => true));

        $paymentMean = str_replace(PayrexxPaymentGateway::PAYMENT_MEAN_PREFIX, '', $this->getPaymentShortName());

        // TO DO: Remove upcoming version release
        $unavailablePaymentMethods = ['postfinance_card', 'postfinance_efinance'];
        if (in_array($paymentMean, $unavailablePaymentMethods)) {
            return false;
        }
        return $gatewayService->createPayrexxGateway(
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
        /** @var PayrexxGatewayService $gatewayService */
        $gatewayService = $this->container->get('prexx_payment_payrexx.payrexx_gateway_service');

        /** @var ConfigService $configService */
        $configService = $this->container->get('prexx_payment_payrexx.config_service');
        $config = $configService->getConfig();

        /** @var OrderService $orderService */
        $orderService = $this->container->get('prexx_payment_payrexx.order_service');

        $gatewayId = $this->container->get('session')->offsetGet('payrexxGatewayId');

        if (!$gateway = $gatewayService->getPayrexxGateway($gatewayId)) {
            $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
            return;
        }
        if ($transaction = $gatewayService->getPayrexxTransactionByGateway($gateway)) {

            if (!$config['orderBeforePayment']) {
                $this->saveOrder(
                    $gatewayId,
                    $this->createPaymentUniqueId(),
                    Status::PAYMENT_STATE_OPEN
                );
            }
            $order = $orderService->getShopwareOrderByNumber($this->getOrderNumber());

            $this->handleTransactionStatus($transaction->getStatus(), $gatewayId, $order);
            $this->container->get('session')->offsetUnset('payrexxGatewayId');
        }

        $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
    }

    /**
     * Redirect to finish page
     * The payment has not been processed, means that the user has to start ordering again
     */
    public function cancelAction()
    {
        /** @var ConfigService $configService */
        $configService = $this->container->get('prexx_payment_payrexx.config_service');
        $config = $configService->getConfig();

        /** @var OrderService $orderService */
        $orderService = $this->container->get('prexx_payment_payrexx.order_service');

        if (!$config['orderBeforePayment']) {
            $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
            return;
        }

        /** @var Order|null $order */
        $order = $orderService->getShopwareOrderByNumber($this->getOrderNumber());

        if ($order instanceof Order) {
            $orderService->restoreCartFromOrder($order);
            $orderVariables = Shopware()->Session()->get('sOrderVariables');
            $orderVariables['sOrderNumber'] = null;
            Shopware()->Session()->set('sOrderVariables', $orderVariables);
        }

        $this->redirect(['controller' => 'checkout', 'action' => 'payment']);
    }

    public function webhookAction()
    {
        // Disable frontend rendering, since this is a webhook request
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        /** @var ConfigService $configService */
        $configService = $this->container->get('prexx_payment_payrexx.config_service');
        $config = $configService->getConfig();

        /** @var OrderService $orderService */
        $orderService = $this->container->get('prexx_payment_payrexx.order_service');

        /** @var PayrexxGatewayService $gatewayService */
        $gatewayService = $this->container->get('prexx_payment_payrexx.payrexx_gateway_service');

        $requestTransaction = $this->request()->getParam('transaction');
        $requestTransactionId = $requestTransaction['id'] ?? null;
        $requestGatewayId = $requestTransaction['invoice']['paymentRequestId'] ?? null;

        // check required data
        if (!$requestTransactionId || !$requestGatewayId) {
            throw new \Exception('Payrexx Webhook Data incomplete');
        }

        $order = $orderService->getShopwareOrderByGatewayID($requestGatewayId);
        if (!$order instanceof Order) {
            if (!$config['orderBeforePayment']) {
                // Probably no order exists yet and no error should be thrown
                return;
            }
            throw new \Exception('No order found with paymentID ' . $requestGatewayId);
        }

        // Status and gateway binding are taken from the API, not from the request
        $transaction = $gatewayService->getTransaction($requestTransactionId);
        if (!$transaction) {
            throw new \Exception('No transaction found with ID ' . $requestTransactionId);
        }
        $gateway = $gatewayService->getPayrexxGateway($order->getTransactionId());
        if (!$gateway || !$this->isTransactionOfGateway($gateway, $transaction)) {
            throw new \Exception(
                'Transaction ' . $transaction->getId() . ' does not belong to gateway ' . $order->getTransactionId()
            );
        }

        $transactionStatus = $transaction->getStatus();
        // A bank transfer can be paid in parts, so only the sum of all confirmed transactions settles the order.
        // An overpayment still settles it, otherwise the order would stay open since the webhook is not resent.
        if (
            $transactionStatus === \Payrexx\Models\Response\Transaction::CONFIRMED
            && $this->getConfirmedAmount($gateway) < (int)round($order->getInvoiceAmount() * 100)
        ) {
            // A partial payment is expected, so it is logged without failing the webhook
            $this->container->get('pluginlogger')->warning(
                'Payrexx webhook: confirmed amount of gateway ' . $gateway->getId()
                . ' is lower than the total of order ' . $order->getNumber()
            );
            return;
        }

        $this->handleTransactionStatus($transactionStatus, $requestGatewayId, $order);
    }

    // The transaction response carries no gateway id, so the gateway is asked for its transactions instead
    private function isTransactionOfGateway($gateway, $transaction)
    {
        foreach ($gateway->getInvoices() ?? [] as $invoice) {
            foreach ($invoice['transactions'] ?? [] as $gatewayTransaction) {
                if ((int)($gatewayTransaction['id'] ?? 0) === (int)$transaction->getId()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getConfirmedAmount($gateway)
    {
        $amount = 0;
        foreach ($gateway->getInvoices() ?? [] as $invoice) {
            foreach ($invoice['transactions'] ?? [] as $gatewayTransaction) {
                if (($gatewayTransaction['status'] ?? '') === \Payrexx\Models\Response\Transaction::CONFIRMED) {
                    $amount += (int)($gatewayTransaction['amount'] ?? 0);
                }
            }
        }

        return $amount;
    }

    private function handleTransactionStatus($requestTransactionStatus,$requestGatewayId, $order) {
        $status = null;
        switch ($requestTransactionStatus) {
            case \Payrexx\Models\Response\Transaction::CONFIRMED:
                $status = Status::PAYMENT_STATE_COMPLETELY_PAID;
                break;
            case \Payrexx\Models\Response\Transaction::WAITING:
                if ($order->getPaymentStatus()->getId() === Status::PAYMENT_STATE_COMPLETELY_PAID) {
                    return;
                }
                $status = Status::PAYMENT_STATE_OPEN;
                break;
            case \Payrexx\Models\Response\Transaction::REFUNDED:
            case \Payrexx\Models\Response\Transaction::PARTIALLY_REFUNDED:
                $status = Status::PAYMENT_STATE_RE_CREDITING;
                break;
            case \Payrexx\Models\Response\Transaction::CANCELLED:
            case \Payrexx\Models\Response\Transaction::DECLINED:
            case \Payrexx\Models\Response\Transaction::EXPIRED:
            case \Payrexx\Models\Response\Transaction::ERROR:
                if ($order->getPaymentStatus()->getId() === Status::PAYMENT_STATE_COMPLETELY_PAID) {
                    return;
                }
                $status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                break;
        }

        if (!$status) {
            return;
        }

        try {
            $this->savePaymentStatus($requestGatewayId, $order->getTemporaryId(), $status);
        } catch(\Payrexx\PayrexxException $e) {
            throw new \Exception('Saving payment status failed');
        }
    }
}

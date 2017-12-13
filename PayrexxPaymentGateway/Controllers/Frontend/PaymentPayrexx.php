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

class Shopware_Controllers_Frontend_PaymentPayrexx extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * @inheritdoc
     */
    public function getWhitelistedCSRFActions()
    {
        return array('notify');
    }

    /**
     * After clicking the order confirmation button, the user gets redirected
     * to this action, which will redirect the user to the newly generated Payrexx gateway page
     */
    public function indexAction()
    {
        // Get the Payrexx Gateway object
        $payrexxGateway = $this->getPayrexxGateway();
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
        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('PayrexxPaymentGateway');
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
            )
        );
    }

    /**
     * Redirection to finish page
     * If the Payrexx Gateway has been paid, the order gets persisted to database
     */
    public function returnAction()
    {
        /** @var PayrexxGatewayService $service */
        $service = $this->container->get('prexx_payment_payrexx.payrexx_gateway_service');
        if ($service->checkPayrexxGatewayStatus(Shopware()->Session()->prexxPaymentPayrexx['gatewayId'])) {
            $this->saveOrder(
                Shopware()->Session()->prexxPaymentPayrexx['gatewayId'],
                $this->createPaymentUniqueId(),
                Status::PAYMENT_STATE_COMPLETELY_PAID
            );
            Shopware()->Session()->offsetUnset('prexxPaymentPayrexx');
        }
        $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
    }

    /**
     * Redirect to finish page
     * The payment has not been processed, means that the user has to start ordering again
     */
    public function cancelAction()
    {
        $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
    }
}

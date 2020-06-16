<?php
/**
 * (c) Payrexx AG <info@payrexx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PayrexxPaymentGateway;

use PayrexxPaymentGateway\Components\PayrexxGateway\PayrexxGatewayService;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Order\Order;

class PayrexxPaymentGateway extends Plugin
{
    const PAYMENT_MEAN_PREFIX = 'payment_payrexx_';

    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch' => 'onPostDispatch',
            'Enlight_Controller_Front_StartDispatch' => 'onRegisterSubscriber',
            'Shopware_Console_Add_Command' => 'onRegisterSubscriber',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentPayrexx' => 'registerController',
            'Enlight_Bootstrap_InitResource_prexx_payment_payrexx.payrexx_gateway_service' => 'onInitPayrexxGateway',
            'Shopware_Controllers_Backend_Order::saveAction::before' => 'onBeforeSaveAction',
        );
    }

    public function onPostDispatch(\Enlight_Event_EventArgs $args)
    {
        $controller = $args->getSubject();
        $controller->View()->addTemplateDir($this->getPath() . '/Resources/views/');
        $controller->View()->extendsTemplate('frontend/header.tpl');
    }

    public function onRegisterSubscriber(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyComponents();
    }

    public function registerMyComponents()
    {
        require_once $this->getPath() . '/vendor/autoload.php';
    }

    public function registerController()
    {
        return $this->getPath() . '/Controllers/Frontend/PaymentPayrexx.php';
    }

    public function onInitPayrexxGateway()
    {
        return new PayrexxGatewayService();
    }

    /**
     * @inheritdoc
     */
    public function install(InstallContext $context)
    {
        $this->registerPayment($context);
        parent::install($context);
    }

    private function registerPayment(InstallContext $context)
    {
        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $paymentMethods = array(
            'masterpass' => 'Masterpass',
            'mastercard' => 'Mastercard',
            'visa' => 'Visa',
            'apple_pay' => 'Apple Pay',
            'maestro' => 'Maestro',
            'jcb' => 'JCB',
            'american_express' => 'American Express',
            'wirpay' => 'WIRpay',
            'paypal' => 'PayPal',
            'bitcoin' => 'Bitcoin',
            'sofortueberweisung_de' => 'Sofort Überweisung',
            'airplus' => 'Airplus',
            'billpay' => 'Billpay',
            'bonuscard' => 'Bonus card',
            'cashu' => 'CashU',
            'cb' => 'Carte Bleue',
            'diners_club' => 'Diners Club',
            'direct_debit' => 'Direct Debit',
            'discover' => 'Discover',
            'elv' => 'ELV',
            'ideal' => 'iDEAL',
            'invoice' => 'Invoice',
            'myone' => 'My One',
            'paysafecard' => 'Paysafe Card',
            'postfinance_card' => 'PostFinance Card',
            'postfinance_efinance' => 'PostFinance E-Finance',
            'swissbilling' => 'SwissBilling',
            'twint' => 'TWINT',
            'barzahlen' => 'Barzahlen/Viacash',
            'bancontact' => 'Bancontact',
            'giropay' => 'GiroPay',
            'eps' => 'EPS',
            'google_pay' => 'Google Pay',
        );
        foreach ($paymentMethods as $name => $paymentMethod) {
            $options = array(
                'name' => self::PAYMENT_MEAN_PREFIX . $name,
                'description' => $paymentMethod . ' (Payrexx)',
                'action' => 'PaymentPayrexx',
                'active' => 0,
                'position' => 0,
                'additionalDescription' => '<img src="{link file=\'frontend/card_' . $name . '.svg\' fullPath}" width="50" />'
            );
            $installer->createOrUpdate($context->getPlugin(), $options);
        }
    }


    /**
     * @param \Enlight_Hook_HookArgs $args
     * @throws \Exception
     */
    public function onBeforeSaveAction(\Enlight_Hook_HookArgs $args)
    {
        $subject = $args->getSubject();
        $newOrderStatus = $subject->Request()->getParam('status');

        $id = $subject->Request()->getParam('id');
        if (empty($id)) {
            return;
        }

        /** @var Order $order */
        $order = Shopware()->Models()->getRepository(Order::class)->find($id);
        if (!($order instanceof Order)) {
            return;
        }

        $statusBefore = $order->getOrderStatus();
        $oldOrderStatus = $statusBefore->getId();
        $transactionId = $order->getTransactionId();
        if( $transactionId && ($oldOrderStatus !== $newOrderStatus) && $newOrderStatus == 7){

            /** @var PayrexxGatewayService $service */
            $service = $this->container->get('prexx_payment_payrexx.payrexx_gateway_service');
            $transaction = $service->getPayrexxGatewayStatus($transactionId);

            if ($transaction['status'] == 'uncaptured') {
                $status = $service->captureTransaction($transaction['id']);
                //var_dump($status);
            }
        }
    }


    /**
     * @param $message
     */
    public function createLog($message){
        file_put_contents(__DIR__."/log.txt", "[ ".date("Y-m-d H:i:s")." ]:" . $message . " \r\n", FILE_APPEND);
    }


    /**
     * @inheritdoc
     */
    public function uninstall(UninstallContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
        parent::uninstall($context);
    }

    /**
     * @param Payment[] $payments
     * @param $active bool
     */
    private function setActiveFlag($payments, $active)
    {
        $em = $this->container->get('models');
        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $em->flush();
    }

    /**
     * @inheritdoc
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
        parent::deactivate($context);
    }

    /**
     * @inheritdoc
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);
        parent::activate($context);
    }
}

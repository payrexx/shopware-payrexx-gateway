<?php
/**
 * (c) Payrexx AG <info@payrexx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PayrexxPaymentGateway;

use Payrexx\Models\Response\Transaction;
use PayrexxPaymentGateway\Components\PayrexxGateway\PayrexxGatewayService;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Models\Order\Order;
use Shopware\Models\Payment\Payment;

class PayrexxPaymentGateway extends Plugin
{
    const PAYMENT_MEAN_PREFIX = 'payment_payrexx_';
    const PAYMENT_MEAN_APPLE_PAY = 'apple_pay';
    const PAYMENT_MEAN_GOOGLE_PAY = 'google-pay';

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
        $request = $args->getSubject()->Request();
        $response = $args->getSubject()->Response();

        /** @var Enlight_View_Default $view */
        $view = $args->getSubject()->View();

        if (!$request->isDispatched() || $response->isException()
            || $request->getModuleName() !== 'frontend'
            || !$view->hasTemplate()
        ) {
            return;
        }

        $controller = $args->getSubject();
        $controller->View()->addTemplateDir($this->getPath() . '/Resources/views/');

        $applePayActive = false;
        $googlePayActive = false;
        foreach (Shopware()->Modules()->Admin()->sGetPaymentMeans() as $paymentMean) {
            if ($paymentMean['name'] === (self::PAYMENT_MEAN_PREFIX . self::PAYMENT_MEAN_APPLE_PAY)) {
                $applePayActive = true;
            }
            if ($paymentMean['name'] === (self::PAYMENT_MEAN_PREFIX . self::PAYMENT_MEAN_GOOGLE_PAY)) {
                $googlePayActive = true;
            }
            $view->assign('payrexx-payment-method', $paymentMean['name']);
        }
        if ($applePayActive || $googlePayActive) {
            $controller->View()->extendsTemplate('frontend/header.tpl');
        }
        $view->assign('applePayActive', $applePayActive);
        $view->assign('googlePayActive', $googlePayActive);
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

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context)
    {
        $this->registerPayment($context);
        parent::update($context);
    }

    /**
     * Register payment methods
     *
     * @param InstallContext|UpdateContext $context
     */
    private function registerPayment($context)
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
            'klarna' => 'Klarna',
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
            'post-finance-pay' => 'Post Finance Pay',
            'swissbilling' => 'SwissBilling',
            'twint' => 'TWINT',
            'barzahlen' => 'Barzahlen/Viacash',
            'bancontact' => 'Bancontact',
            'giropay' => 'GiroPay',
            'eps' => 'EPS',
            'google-pay' => 'Google Pay',
            'coinbase' => 'Coinbase',
            'antepay' => 'AntePay',
            'wechat-pay' => 'WeChat Pay',
            'alipay' => 'Alipay',
            'samsung_pay' => 'Samsung Pay',
            'ideal_payment' => 'ideal Payment',
            'centi' => 'Centi',
            'heidipay' => 'Pay monthly with HeidiPay',
            'reka' => 'Reka',
            'bank_transfer' => 'Bank Transfer',
            'pay-by-bank' => 'Pay by Bank',
        );

        $installedPaymentMethods = $this->getInstalledPaymentMethods();
        if (!empty($installedPaymentMethods)) {
            $installedPaymentMethods = array_column($installedPaymentMethods, 'name');
        }
        foreach ($paymentMethods as $name => $paymentMethod) {
            $paymentMethodName = self::PAYMENT_MEAN_PREFIX . $name;
            if (!empty($installedPaymentMethods) &&
                in_array($paymentMethodName, $installedPaymentMethods)
            ) {
                continue;
            }
            $options = array(
                'name' => $paymentMethodName,
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
        $transactionIds = $order->getTransactionId();
        $transactionIds = explode("_", $transactionIds);
        if ($transactionIds && is_array($transactionIds)) $transactionId = $transactionIds[1];

        if ($transactionId && ($oldOrderStatus !== $newOrderStatus) && $newOrderStatus == 7) {

            /** @var PayrexxGatewayService $service */
            $service = $this->container->get('prexx_payment_payrexx.payrexx_gateway_service');

            /** @var \Payrexx\Models\Request\Transaction $transaction */
            $transaction = $service->getTransaction($transactionId);

            if ($transaction instanceof \Payrexx\Models\Request\Transaction && $transaction->getStatus() == Transaction::UNCAPTURED) {
                $service->captureTransaction($transactionId);
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

    /**
     * Get existing payment methods
     */
    private function getInstalledPaymentMethods()
    {
        $modelManager = $this->container->get('models');
        $qb = $modelManager->createQueryBuilder();
        $qb->select(['p.name'])
            ->from(Payment::class, 'p')
            ->where($qb->expr()->like('p.name', ':namePattern'))
            ->setParameter(':namePattern', self::PAYMENT_MEAN_PREFIX . '%');
        return $qb->getQuery()->getResult();
    }
}

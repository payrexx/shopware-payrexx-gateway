<?php
/**
 * (c) Payrexx AG <info@payrexx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PrexxPaymentPayrexx;

use PrexxPaymentPayrexx\Components\PayrexxGateway\PayrexxGatewayService;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class PrexxPaymentPayrexx extends Plugin
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

        foreach (['mastercard', 'visa', 'postfinance_card', 'postfinance_efinance', 'apple_pay'] as $paymentMethod) {
            $options = [
                'name' => self::PAYMENT_MEAN_PREFIX . $paymentMethod,
                'description' => ucwords(str_replace('_', ' ', $paymentMethod)) . ' (Payrexx)',
                'action' => 'PaymentPayrexx',
                'active' => 0,
                'position' => 0,
                'additionalDescription' => '<img src="{link file=\'frontend/card_' . $paymentMethod . '.svg\' fullPath}" width="50" />'
            ];
            $installer->createOrUpdate($context->getPlugin(), $options);
        }
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

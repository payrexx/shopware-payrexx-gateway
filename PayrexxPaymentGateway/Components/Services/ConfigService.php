<?php

namespace PayrexxPaymentGateway\Components\Services;

class ConfigService
{
    public function __construct()
    {
    }

    public function getConfig() {
        $shop = false;
        if (Shopware()->Container()->initialized('shop')) {
            $shop = Shopware()->Container()->get('shop');
        }

        if (!$shop) {
            $shop = Shopware()->Container()->get('models')->getRepository(\Shopware\Models\Shop\Shop::class)->getActiveDefault();
        }

        return Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('PayrexxPaymentGateway', $shop);
    }
}
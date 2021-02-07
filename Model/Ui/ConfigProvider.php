<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package MageBild_Paymongo
 */

namespace Magebild\Paymongo\Model\Ui;

/**
 * Class ConfigProvider
 *
 * @package Magebild\Paymongo\Model\Ui
 */
class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    const CODE = 'paymongo';

    const EWALLETS = ['gcash', 'grab_pay'];

    const XML_PATH_EWALLET = 'payment/paymongo_section/ewallet/%s_enable';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    protected $config;

    /**
     * @var \Magento\Framework\UrlInterface $url
     */
    protected $url;


    /**
     * ConfigProvider constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->config = $config;
        $this->url = $url;
    }

    /**
     * Implementation
     *
     * @return array|\array[][]
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->areEwalletsEnabled(),
                    'ewallets' => $this->getEnabledEwallets(),
                    'redirectUrl' => $this->url->getUrl('paymongo/payment/start')
                ]
            ]
        ];
    }

    /**
     * Check ewallets
     *
     * @return bool
     */
    private function areEwalletsEnabled()
    {
        $enabled = false;
        foreach (self::EWALLETS as $ewallet) {
            $path = sprintf(self::XML_PATH_EWALLET, $ewallet);
            if ($this->config->isSetFlag($path)) {
                $enabled = true;
            }
        }
        return $enabled;
    }

    /**
     * Get enabled wallets
     *
     * @return array
     */
    public function getEnabledEwallets()
    {
        $wallets = [];
        foreach (self::EWALLETS as $ewallet) {
            $path = sprintf(self::XML_PATH_EWALLET, $ewallet);
            if ($this->config->isSetFlag($path)) {
                $wallets[] = $ewallet;
            }
        }
        return $wallets;
    }
}

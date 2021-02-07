<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */

namespace Magebild\Paymongo\Model\Config;

/**
 * Class Ewallet
 *
 * @package Magebild\Paymongo\Model\Config
 */
class Ewallet extends \Magento\Framework\App\Config\Value
{
    const EWALLET_PROVIDERS = [
        'gcash',
        'grab_pay'
    ];

    /**
     * Custom implementation
     *
     * @return array
     */
    public function getPrefixes()
    {
        $prefixes = [];
        foreach (self::EWALLET_PROVIDERS as $provider) {
            $prefixes[] = [
                'field' => strtolower($provider) . '_',
                'label' => strtoupper($provider),
            ];
        }
        return $prefixes;
    }
}

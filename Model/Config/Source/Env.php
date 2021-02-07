<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */

namespace Magebild\Paymongo\Model\Config\Source;

/**
 * Class Env
 *
 * @package Magebild\Paymongo\Model\Config\Source
 */
class Env implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Implementation
     *
     * @return array|array[]
     */
    public function toOptionArray()
    {
        return [['value' => true, 'label' => __('Live')], ['value' => false, 'label' => __('Development')]];
    }
}

<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */
namespace Magebild\Paymongo\Model\Config\Backend;

/**
 * Class Cloned
 *
 * @package Magebild\Paymongo\Model\Config\Backend
 */
class Cloned extends \Magento\Framework\App\Config\Value
{
    /**
     * Override
     *
     * @return $this|Cloned
     */
    public function beforeSave()
    {
        parent::beforeSave();
        return $this;
    }
}

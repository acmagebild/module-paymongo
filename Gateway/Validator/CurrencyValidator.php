<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */
namespace Magebild\Paymongo\Gateway\Validator;

/**
 * Class CurrencyValidator
 *
 * @package Magebild\Paymongo\Gateway\Validator
 */
class CurrencyValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    const PHP_CURRENCY_CODE = 'PHP';

    /**
     * Implementation
     *
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (isset($validationSubject['currency']) && $validationSubject['currency'] == self::PHP_CURRENCY_CODE) {
            return $this->createResult(true);
        }
        return $this->createResult(false);
    }
}

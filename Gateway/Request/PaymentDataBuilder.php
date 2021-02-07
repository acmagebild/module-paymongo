<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */

namespace Magebild\Paymongo\Gateway\Request;

/**
 * Class PaymentDataBuilder
 *
 * @package Magebild\Paymongo\Gateway\Request
 */
class PaymentDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    const PAYMENT_SOURCE_TYPE ='source';

    const PAYMENT_SOURCE_URL = 'https://api.paymongo.com/v1/payments';

    /**
     * @var \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     */
    protected $subjectReader;

    /**
     * PaymentDataBuilder constructor.
     *
     * @param \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Build payment request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $amount = $this->subjectReader->readAmount($buildSubject) * 100;
        $payment = $this->subjectReader->readPayment($buildSubject)->getPayment();
        $sourceId = $payment->getAdditionalInformation('source_id');
        return [
            'api_url' => self::PAYMENT_SOURCE_URL,
            'secret_key_only' => 1,
            'amount' => $amount,
            'currency' => SourceDataBuilder::PHP_CURRENCY,
            'source' => [
                'id' => $sourceId,
                'type' => self::PAYMENT_SOURCE_TYPE
            ]
        ];
    }
}

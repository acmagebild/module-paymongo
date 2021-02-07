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
class SourceDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    const PHP_CURRENCY = 'PHP';

    const SOURCE_URL = 'https://api.paymongo.com/v1/sources';

    /**
     * @var \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     */
    protected $subjectReader;

    /**
     * @var \Magento\Framework\UrlInterface $url
     */
    protected $url;

    /**
     * CustomerDataBuilder constructor.
     *
     * @param \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Framework\UrlInterface  $url
    ) {
        $this->subjectReader = $subjectReader;
        $this->url = $url;
    }

    /**
     * Build Source request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (isset($buildSubject['payment']) &&
            $buildSubject['payment'] instanceof \Magento\Quote\Api\Data\PaymentInterface) {
            $payment = $buildSubject['payment'];
            $amount = $buildSubject['amount'];
        } else {
            $payment = $this->subjectReader->readPayment($buildSubject)
                ->getPayment();
            $amount = $this->subjectReader->readAmount($buildSubject);
        }
        $provider = $payment->getAdditionalInformation('provider');
        if ($provider === null) {
            return [];
        }
        return [
            'api_url' => self::SOURCE_URL,
            'type' => $provider,
            'amount' =>  (int)($amount * 100),
            'currency' => self::PHP_CURRENCY,
            'redirect' => [
                'success' => $this->url->getUrl('paymongo/payment/authorize'),
                'failed' => $this->url->getUrl('paymongo/payment/fail')
            ]
        ];
    }
}

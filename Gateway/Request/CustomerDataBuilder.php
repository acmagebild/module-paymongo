<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */
namespace Magebild\Paymongo\Gateway\Request;

/**
 * Class CustomerDataBuilder
 *
 * @package Magebild\Paymongo\Gateway\Request
 */
class CustomerDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    const XML_PATH_USE_BILLING = 'payment/paymongo_section/address/use_billing_address';

    /**
     * @var \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     */
    protected $subjectReader;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    protected $config;

    /**
     * CustomerDataBuilder constructor.
     *
     * @param \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    public function __construct(
        \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Framework\App\Config\ScopeConfigInterface $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    /**
     * Build customer request
     *
     * @param array $buildSubject
     * @return array|array[]
     */
    public function build(array $buildSubject)
    {
        if (!$this->config->isSetFlag(self::XML_PATH_USE_BILLING)) {
            return [];
        }
        if (isset($buildSubject['quote']) &&
            $buildSubject['quote'] instanceof \Magento\Quote\Api\Data\CartInterface) {
            $quote = $buildSubject['quote'];
            $billingAddress = $quote->getBillingAddress();
            $name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        } else {
            $order = $this->subjectReader->readPayment($buildSubject)->getOrder();
            $billingAddress = $order->getBillingAddress();
            $name = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        }
        return [
            'billing' => [
                'name' => $name,
                'phone' => $billingAddress->getTelephone(),
                'email' => $billingAddress->getEmail(),
                'address' => [
                    'city' => $billingAddress->getCity(),
                    'postal_code' => $billingAddress->getPostcode(),
                    'line1' => $billingAddress->getStreetLine1(),
                    'line2' => $billingAddress->getStreetLine2()
                ]
            ]
        ];
    }
}

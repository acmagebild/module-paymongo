<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */
namespace Magebild\Paymongo\Gateway\Validator;

/**
 * Class MinimumAmountValidator
 *
 * @package Magebild\Paymongo\Gateway\Validator
 */
class MinimumAmountValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    const MINIMUM_TRANSACTION_AMOUNT = 100;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;

    /**
     * MinimumAmountValidator constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
    ) {
        parent::__construct($resultFactory);
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Validate payment params
     *
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function validate(array $validationSubject)
    {
        if (isset($validationSubject['payment'])) {
            /** @var \Magento\Payment\Gateway\Data\PaymentDataObject $payment */
            $payment = $validationSubject['payment'];
            $quote = $this->quoteRepository->get($payment->getOrder()->getId());
            if ($quote->getGrandTotal() >= self::MINIMUM_TRANSACTION_AMOUNT) {
                return $this->createResult(true);
            }
        }
        return $this->createResult(false, [__('Minimum Transaction amount is 100')]);
    }
}

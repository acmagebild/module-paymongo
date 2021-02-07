<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */

namespace Magebild\Paymongo\Controller\Payment;

use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Class Fail
 *
 * @package Magebild\Paymongo\Controller\Payment
 */
class Fail extends \Magebild\Paymongo\Controller\AbstractPayment
{
    /**
     * @var \Magebild\Paymongo\Model\SourceFactory $sourceFactory
     */
    protected $sourceFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory $resultFactory
     */
    protected $resultFactory;

    /**
     * Fail constructor.
     *
     * @param \Magebild\Paymongo\Model\SourceFactory $sourceFactory
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultFactory
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magebild\Paymongo\Model\SourceFactory $sourceFactory,
        \Magento\Framework\Controller\Result\RedirectFactory $resultFactory,
        \Magento\Framework\Session\Generic $session,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct(
            $session,
            $checkoutSession,
            $customerSession,
            $request,
            $response,
            $redirect,
            $checkoutData,
            $messageManager,
            $quoteRepository
        );
        $this->sourceFactory = $sourceFactory;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Implement
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->_initCheckout();
        $quote = $this->_getQuote();
        $payment = $quote->getPayment();
        $sourceId = $payment->getAdditionalInformation('source_id');
        $resultRedirect = $this->resultFactory->create();
        $resultRedirect->setPath('checkout/cart');
        if (!$sourceId) {
            $this->messageManager->addErrorMessage(__('An error has occurred: Missing source_id'));
        }
        try {
            $source = $this->sourceFactory->create();
            $response = $source->retrieveSource($sourceId);
            if (!is_array($response)) {
                $this->messageManager->addErrorMessage(__('Paymonggo returned a status of %1', $response));
            }
            $this->messageManager->addErrorMessage(__('Payment failed. Please try another provider'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());

        }
        return $resultRedirect;
    }
}

<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */

namespace Magebild\Paymongo\Controller\Payment;

use Magebild\Paymongo\Model\Checkout;
use Magento\Quote\Api\CartRepositoryInterface;
use function Symfony\Component\String\s;

/**
 * Class Authorize
 *
 * @package Magebild\Paymongo\Controller\Payment
 */
class Authorize extends \Magebild\Paymongo\Controller\AbstractPayment
{
    /**
     * @var \Magento\Quote\Api\CartManagementInterface $cartManager
     */
    protected $cartManager;

    /**
     * @var \Magebild\Paymongo\Model\CheckoutFactory $checkoutFactory
     */
    protected $checkoutFactory;

    /**
     * Authorize constructor.
     *
     * @param \Magebild\Paymongo\Model\CheckoutFactory $checkoutFactory
     * @param \Magento\Quote\Api\CartManagementInterface $cartManager
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
        \Magebild\Paymongo\Model\CheckoutFactory $checkoutFactory,
        \Magento\Quote\Api\CartManagementInterface $cartManager,
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
        $this->cartManager = $cartManager;
        $this->checkoutFactory = $checkoutFactory;
    }

    /**
     * Implementation
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $quote = $this->_getQuote();
        $payment = $quote->getPayment();
        $info = $payment->getAdditionalInformation('source_id');

        if (empty($info)) {
            $this->messageManager->addErrorMessage(__('Paymongo didn\'t return source'));
            $this->redirect->redirect($this->getResponse(), 'checkout/cart');
        }
        if ($this->getCheckoutMethod() == \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {
            $this->prepareGuestQuote();
        }
        try {
            $checkout = $this->checkoutFactory->create();
            $orderId = $this->cartManager->placeOrder($quote->getId(), $payment);
            $checkout->process($orderId);
            $this->redirect->redirect($this->getResponse(), 'checkout/onepage/success/');
        } catch (\Magento\Framework\Exception\CouldNotSaveException $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $this->redirect->redirect($this->getResponse(), 'checkout/cart');
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $this->redirect->redirect($this->getResponse(), 'checkout/cart');
        }
    }

    /**
     * Prepare guest quote
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function prepareGuestQuote()
    {
        $quote = $this->_getQuote();
        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
        return $this;
    }
}

<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */

namespace Magebild\Paymongo\Controller;

use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Class AbstractPayment
 *
 * @package Magebild\Paymongo\Controller
 */
abstract class AbstractPayment implements
    \Magento\Framework\App\Action\HttpGetActionInterface,
    \Magento\Framework\App\Action\HttpPostActionInterface
{

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote = false;

    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Session\Generic $session
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ResponseInterface $response
     */
    protected $response;

    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Helper\Data $checkoutData
     */
    protected $checkoutData;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface $redirect
     */
    protected $redirect;

    /**
     * @var \Magento\Framework\Message\ManagerInterface $messageManager
     */
    protected $messageManager;

    /**
     * AbstractPayment constructor.
     *
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
        $this->session = $session;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->request = $request;
        $this->response = $response;
        $this->customerSession = $customerSession;
        $this->checkoutData = $checkoutData;
        $this->redirect = $redirect;
        $this->messageManager = $messageManager;
    }

    /**
     * Generic method.
     *
     * @return string
     */
    public function getCheckoutMethod()
    {
        if ($this->_getCustomerSession()->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }

        if (!$this->_getQuote()->getCheckoutMethod()) {
            if ($this->checkoutData->isAllowedGuestCheckout($this->_getQuote())) {
                $this->_getQuote()->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $this->_getQuote()->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }
        return $this->_getQuote()->getCheckoutMethod();
    }

    /**
     * Implement
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    abstract public function execute();

    /**
     * Initialize checkout objects
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quoteObj
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initCheckout(
        \Magento\Quote\Api\Data\CartInterface $quoteObj = null
    ) {
        $quote = $quoteObj ? $quoteObj : $this->_getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
//            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t initialize Express Checkout.'));
        }
        if (!(float)$quote->getGrandTotal()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Paymongo can\'t process orders with zero grand total')
            );
        }
    }

    /**
     * Get current quote
     *
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _getQuote()
    {
        if (!$this->quote) {
            if ($this->_getSession()->getQuoteId()) {
                $this->quote = $this->quoteRepository->get($this->_getSession()->getQuoteId());
                $this->_getCheckoutSession()->replaceQuote($this->quote);
            } else {
                $this->quote = $this->_getCheckoutSession()->getQuote();
            }
        }
        return $this->quote;
    }

    /**
     * Get Session object
     *
     * @return \Magento\Framework\Session\Generic
     */
    protected function _getSession()
    {
        return $this->session;
    }

    /**
     * Get checkout session
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * Get customer session
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * Get request object
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * Get response object
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function getResponse()
    {
        return $this->response;
    }
}

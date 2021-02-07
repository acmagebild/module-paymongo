<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */
namespace Magebild\Paymongo\Controller\Payment;

use Magebild\Paymongo\Gateway\Http\TransferFactory;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Class Start
 *
 * @package Magebild\Paymongo\Controller\Payment
 */
class Start extends \Magebild\Paymongo\Controller\AbstractPayment
{
    /**
     * @var \Magebild\Paymongo\Gateway\Request\SourceDataBuilderFactory $paymentDataBuilderFactory
     */
    protected $paymentDataBuilderFactory;

    /**
     * @var \Magebild\Paymongo\Gateway\Request\CustomerDataBuilderFactory $customerDataBuilderFactory
     */
    protected $customerDataBuilderFactory;

    /**
     * @var TransferFactory $transferFactory
     */
    protected $transferFactory;

    /**
     * @var \Magebild\Paymongo\Gateway\Http\Client\AuthorizationFactory $authorizationFactory
     */
    protected $authorizationFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    protected $serializer;

    /**
     * Start constructor.
     *
     * @param \Magebild\Paymongo\Gateway\Request\SourceDataBuilderFactory $paymentDataBuilderFactory
     * @param \Magebild\Paymongo\Gateway\Request\CustomerDataBuilderFactory $customerDataBuilderFactory
     * @param TransferFactory $transferFactory
     * @param \Magebild\Paymongo\Gateway\Http\Client\AuthorizationFactory $authorizationFactory
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magebild\Paymongo\Gateway\Request\SourceDataBuilderFactory $paymentDataBuilderFactory,
        \Magebild\Paymongo\Gateway\Request\CustomerDataBuilderFactory $customerDataBuilderFactory,
        \Magebild\Paymongo\Gateway\Http\TransferFactory $transferFactory,
        \Magebild\Paymongo\Gateway\Http\Client\AuthorizationFactory $authorizationFactory,
        \Magento\Framework\Session\Generic $session,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
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
        $this->paymentDataBuilderFactory = $paymentDataBuilderFactory;
        $this->customerDataBuilderFactory = $customerDataBuilderFactory;
        $this->transferFactory = $transferFactory;
        $this->authorizationFactory = $authorizationFactory;
        $this->serializer = $serializer;
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
        $this->_initCheckout();
        $quote = $this->_getQuote();

        $paymentDataBuilder = $this->paymentDataBuilderFactory->create();
        $customerDataBuilder = $this->customerDataBuilderFactory->create();

        $paymentData = $paymentDataBuilder->build([
            'payment' => $quote->getPayment(),
            'amount' => $quote->getGrandTotal()
        ]);
        $quoteData = $customerDataBuilder->build([
            'quote' => $quote
        ]);
        $payload = array_merge($quoteData, $paymentData);
        $authorizeTransfer = $this->transferFactory->create($payload);
        try {
            $client = $this->authorizationFactory->create();
            $response = $client->placeRequest($authorizeTransfer);
            if (!is_array($response) && count($response) == 0) {
                return;
            }
            $response = $this->serializer->unserialize($response[0]);
            $payment = $quote->getPayment();
            $payment->setAdditionalInformation('source_id', $response['data']['id']);
            $payment->save();
            $this->getResponse()->setRedirect($response['data']['attributes']['redirect']['checkout_url']);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $this->redirect->redirect($this->getResponse(), 'checkout/cart');
        }
    }
}

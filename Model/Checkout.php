<?php

namespace Magebild\Paymongo\Model;

/**
 * Class Checkout
 *
 * @package Magebild\Paymongo\Model
 */
class Checkout
{
    /**
     * @var \Magento\Sales\Api\OrderManagementInterface $orderManager
     */
    protected $orderManager;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    protected $serializer;

    /**
     * @var \Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory
     */
    protected $historyFactory;

    /**
     * @var \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    protected $orderStatusHistoryRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\InvoiceOrderInterface $invoiceOrder
     */
    protected $invoiceOrder;

    /**
     * Checkout constructor.
     *
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManager
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory
     * @param \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\InvoiceOrderInterface $invoiceOrder
     */
    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json  $serializer,
        \Magento\Sales\Api\OrderManagementInterface $orderManager,
        \Magento\Sales\Model\Order\Status\HistoryFactory  $historyFactory,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\InvoiceOrderInterface $invoiceOrder
    ) {
        $this->serializer = $serializer;
        $this->orderManager = $orderManager;
        $this->historyFactory = $historyFactory;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
        $this->orderRepository = $orderRepository;
        $this->invoiceOrder = $invoiceOrder;
    }

    /**
     * Process order
     *
     * @param int $orderId
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function process($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();

        if (isset($additionalInfo['error'])) {
            $errors = $this->serializer->unserialize($additionalInfo['error']);
            foreach ($errors as $error) {
                $this->addCommentsHistory($orderId, $this->serializer->serialize($error));
            }
            $this->orderManager->hold($orderId);
        } else {
            $this->invoiceOrder->execute($orderId);
            $this->addCommentsHistory(
                $orderId,
                __('Captured Amount', $order->getGrandTotal()),
                false,
                \Magento\Sales\Model\Order::STATE_PROCESSING
            );
        }
    }

    /**
     * Add order comments
     *
     * @param int $orderId
     * @param string $message
     * @param bool $notify
     * @param string $status
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function addCommentsHistory($orderId, $message, $notify = false, $status = \Magento\Sales\Model\Order::STATE_HOLDED)
    {
        $history = $this->historyFactory->create();
        $history->setParentId($orderId)
            ->setIsCustomerNotified($notify)
            ->setStatus($status)
            ->setComment($message);
        $this->orderStatusHistoryRepository->save($history);
        $this->orderManager->addComment($orderId, $history);
    }
}

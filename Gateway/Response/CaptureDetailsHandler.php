<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */

namespace Magebild\Paymongo\Gateway\Response;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class CaptureDetailsHandler
 *
 * @package Magebild\Paymongo\Gateway\Response
 */
class CaptureDetailsHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     */
    protected $subjectReader;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    protected $serializer;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface $orderManager
     */
    protected $orderManager;

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
     * @var \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * CaptureDetailsHandler constructor.
     *
     * @param \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManager
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory
     * @param \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Framework\Serialize\Serializer\Json  $serializer,
        \Magento\Sales\Api\OrderManagementInterface $orderManager,
        \Magento\Sales\Model\Order\Status\HistoryFactory  $historyFactory,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->serializer = $serializer;
        $this->orderManager = $orderManager;
        $this->historyFactory = $historyFactory;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * Handle response
     *
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $this->subjectReader->readPayment($handlingSubject);
        $paymentObject = $payment->getPayment();

        if (count($response) > 0) {
            $responseObj = $this->serializer->unserialize($response[0]);
        }
        if (isset($responseObj['errors'])) {
            $error = $this->serializer->serialize($responseObj['errors']);
            $paymentObject->setAdditionalInformation('error', $error);
        }
        if (isset($responseObj['data'])) {
            $data = $responseObj['data'];
            $paymentObject->setAdditionalInformation('pay_id', $data['id']);
            $paymentObject->setAdditionalInformation('pay_status', $data['attributes']['status']);
        }
    }

    /**
     * Get order by increment id.
     *
     * @param int $incrementId
     * @return OrderInterface|null
     */
    private function getOrderByIncrementId($incrementId)
    {
        $criteria = $this->searchCriteriaBuilderFactory->create();
        $criteria->addFilter(OrderInterface::INCREMENT_ID, $incrementId);
        $orders = $this->orderRepository->getList($criteria->create())->getItems();
        return count($orders) ? $orders[0] : null;
    }
}

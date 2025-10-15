<?php

namespace Razam\Coordinadora\Model\Sync;

use Razam\Coordinadora\Helper\Email;
use Razam\Coordinadora\Helper\WebService;
use Exception;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

class Guide
{
    const DELIVERED = 'complete_shipping';
    const SHIPPING = 'processing_shipping';

    /**
     * @var WebService
     */
    private $_webService;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var CollectionFactory
     */
    private $_orderCollectionFactory;

    /**
     * @var Email
     */
    private $_email;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * Guide constructor.
     * @param WebService $webService
     * @param Email $email
     * @param CollectionFactory $collectionFactory
     * @param Order $order
     * @param OrderRepository $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        WebService $webService,
        Email $email,
        CollectionFactory $collectionFactory,
        Order $order,
        OrderRepository $orderRepository,
        LoggerInterface $logger
    ) {
        $this->_webService = $webService;
        $this->_email = $email;
        $this->_orderCollectionFactory = $collectionFactory;
        $this->_order = $order;
        $this->orderRepository = $orderRepository;
        $this->_logger = $logger;
    }

    /**
     * Checks tracking status for specific tracking numbers
     * @throws Exception
     */
    /* public function syncStatuses()
    {
        $orderCollection = $this->_orderCollectionFactory->create()
            ->addFieldToSelect(['increment_id'])
            ->addFieldToFilter('status', ['in' => [self::SHIPPING, Order::STATE_COMPLETE]])
            ->addFieldToFilter('shipping_method', ['eq' => 'coordinadora_coordinadora'])
            ->join(['t2' => 'sales_shipment'], 't2.order_id = main_table.entity_id', [])
            ->join(['t3' => 'sales_shipment_track'], 't3.parent_id = t2.entity_id', ['t3.track_number'])
            ->getData();
        $arrayOrders = [];
        $xml = new SimpleXMLElement("<guias></guias>");
        $xmlResponse = null;
        $count = 0;
        foreach ($orderCollection as $orderInfo) {
            $xml->addChild("guia")
                ->addChild("numero_guia", $orderInfo['track_number']);
            $arrayOrders[] = ["order" => $orderInfo['increment_id'], "track" => $orderInfo['track_number']];
            $count++;
            if ($count == 50) {
                $xmlResponse = $this->_webService->EstadoGuiasXML($xml->asXML());
                $this->_logger->debug(json_encode($xmlResponse));
                $this->bridgeStatus($arrayOrders, $xmlResponse);
                $count = 0;
                $arrayOrders = [];
            }
        }
    } */


    /**
     * Checks tracking status for specific tracking numbers
     * @throws Exception
     */
    public function syncStatuses()
    {
        $orderCollection = $this->_orderCollectionFactory->create()
            ->addFieldToSelect(['increment_id'])
            ->addFieldToFilter('status', ['in' => [self::SHIPPING, Order::STATE_COMPLETE]])
            ->addFieldToFilter('shipping_method', ['eq' => 'coordinadora_coordinadora'])
            ->join(['t2' => 'sales_shipment'], 't2.order_id = main_table.entity_id', [])
            ->join(['t3' => 'sales_shipment_track'], 't3.parent_id = t2.entity_id', ['t3.track_number'])
            ->getData();

        $arrayOrders = [];
        $result = null;
        foreach ($orderCollection as $orderInfo) {
            $arrayOrders[] = ["order" => $orderInfo['increment_id'], "track" => $orderInfo['track_number']];
            $result = $this->_webService->Seguimiento_simple( $orderInfo['track_number'] );
            $this->_logger->debug(json_encode($result));
            $this->bridgeStatus($arrayOrders, $result);
            
        }
    }

    /**
     * Bridge functions to separate Coordinadora response and sync Magento statuses.
     * @param $arrayOrders
     * @param $result
     */
    public function bridgeStatus($arrayOrders, $result)
    {
        $response = $result;
        try {
            foreach ($arrayOrders as $arrayOrder) {
                if ( in_array($response->codigo_remision, $arrayOrder) ) {
                    $this->updateStatus($response, $arrayOrder);
                }
            }
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
    }

    /**
     * Updates status order depending tracking status.
     * @param $guideInfo
     * @param $orderInfo
     * @throws Exception
     */
    public function updateStatus($guideInfo, $orderInfo)
    {
        try {
            $order = $this->_order->loadByIncrementId($orderInfo['order']);
            $status = $order->getStatus();
            if ($status == Order::STATE_COMPLETE || $status == self::SHIPPING) {
                if ($guideInfo->codigo == 2) {
                    $comment = $order->getStatusHistoryCollection()
                        ->addFieldToFilter('status', ['eq' => self::SHIPPING])
                        ->getData();
                    if (!count($comment)) {
                        $order->addCommentToStatusHistory(
                            __('Your order is on its way.'),
                            self::SHIPPING,
                            true
                        );
                        $this->orderRepository->save($order);
                        $this->_email->sendShippingEmail($order, $guideInfo, 1);
                    }
                } elseif ($guideInfo->codigo == 6) {
                    $order->addCommentToStatusHistory(
                        __("Your order has been delivered. Delivery date: {$guideInfo->fecha_texto}"),
                        self::DELIVERED,
                        true
                    );
                    $this->orderRepository->save($order);
                    $this->_email->sendShippingEmail($order, $guideInfo, 2);
                }
                sleep(7);
            }
        } catch (Exception $e) {
            throw new Exception("An error has occurred: " . $e->getMessage());
        }
    }
}

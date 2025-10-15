<?php

namespace Razam\Coordinadora\Block\Tracking;

use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class Popup extends \Magento\Shipping\Block\Tracking\Popup
{
    protected $_code = 'coordinadora';

    /**
     * @var \Razam\Coordinadora\Helper\WebService
     */
    private $_webService;
    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        DateTimeFormatterInterface $dateTimeFormatter,
        \Razam\Coordinadora\Helper\WebService $webService,
        ShipmentRepositoryInterface $shipmentRepository,
        array $data = []
    ) {
        parent::__construct($context, $registry, $dateTimeFormatter, $data);
        $this->_webService = $webService;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * @inheridoc
     */
    public function getTrackingInfo(): array
    {
        $dataTracking = null;
        /* @var $info \Magento\Shipping\Model\Info */
        $info = $this->_registry->registry('current_shipping_info');
        $isCustom = $this->getIsCustomCarrier($info);
        if ($isCustom['response']) {
            $dataTracking = $this->_webService->Seguimiento_detallado($isCustom['trackNumber']);
            //$dataTracking = $this->_webService->Seguimiento_detallado('68170500009');
            $this->_logger->debug(json_encode($dataTracking->Seguimiento_detalladoResult));
            return ['isCustomCarrier' => true, 'trackingInfo' => json_decode(json_encode($dataTracking->Seguimiento_detalladoResult), true)];
        } else {
            return ['isCustomCarrier' => false, 'trackingInfo' => $info->getTrackingInfo()];
        }
    }

    /**
     * Testing method.
     * @return string
     */
    public function getTest(): string
    {
        $this->_logger->debug("Estoy en el override block!");
        return "This is a proof!";
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->_code;
    }

    /**
     * @param \Magento\Shipping\Model\Info $info
     * @return false[]
     */
    private function getIsCustomCarrier(\Magento\Shipping\Model\Info $info): array
    {
        $trackingInfo = $info->getTrackingInfo();
        if (empty($trackingInfo)) {
            return ['response' => false];
        }
        $shipmentId = array_key_first($trackingInfo);
        // In modern Magento, the key is often the shipment ID, not increment ID.
        // We should load by entity_id.
        $shipping = $this->shipmentRepository->get($shipmentId);
        $shippingMethod = $shipping->getOrder()->getShippingMethod(true)->getData();
        if ($shippingMethod['carrier_code'] == $this->getCode()) {
            $trackNum = null;
            $tracks = $shipping->getTracksCollection()->getData();
            foreach ($tracks as $track) {
                $trackNum = $track['track_number'];
            }
            return ['response' => true, 'trackNumber' => $trackNum];
        }
        return ['response' => false];
    }

}

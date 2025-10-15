<?php

namespace Razam\Coordinadora\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class ShipmentGeneration
{
    const PDF_SAVE_DIR = 'coordinadora';
    const SHIPPING_METHOD = 'coordinadora_coordinadora';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Razam\Coordinadora\Helper\WebService
     */
    private $_webService;

    /**
     * @var \Magento\Shipping\Model\ShipmentNotifier
     */
    private $_shipmentNotifier;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    private $_shipment;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    private $_trackFactory;

    /**
     * @var \Razam\Coordinadora\Helper\Configuration
     */
    private $_configuration;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $_orderCollectionFactory;

    /**
     * @var TransactionFactory
     */
    private $_transactionFactory;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $_orderRepository;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * ShipmentGeneration constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Razam\Coordinadora\Helper\WebService $webService
     * @param \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier
     * @param \Magento\Sales\Model\Convert\Order $shipment
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     * @param \Razam\Coordinadora\Helper\Configuration $configuration
     * @param \Magento\Framework\UrlInterface $url
     * @param TransactionFactory $transactionFactory
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param Filesystem $filesystem
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Razam\Coordinadora\Helper\WebService $webService,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Sales\Model\Convert\Order $shipment,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Razam\Coordinadora\Helper\Configuration $configuration,
        \Magento\Framework\UrlInterface $url,
        ShipmentRepositoryInterface $shipmentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        DefaultSourceProviderInterface $defaultSourceProvider,
        ResourceConnection $resourceConnection,
        Filesystem $filesystem,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->_logger = $logger;
        $this->_webService = $webService;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->_orderCollectionFactory = $collectionFactory;
        $this->_shipment = $shipment;
        $this->_trackFactory = $trackFactory;
        $this->_configuration = $configuration;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderRepository = $orderRepository;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->resourceConnection = $resourceConnection;
        $this->filesystem = $filesystem;
        $this->_eventManager = $eventManager;
    }

    /**
     * @param Order $order
     * @throws \Exception
     */
    public function generateShipmentGuide(Order $order)
    {
        $orderItems = $order->getAllVisibleItems();
        $qty = 0;
        foreach ($orderItems as $item) {
            if ($item->getIsVirtual() || $item->getQtyOrdered() - $item->getQtyShipped() - $item->getQtyRefunded() - $item->getQtyCanceled() <= 0) {
                continue;
            }
            $qty += $item->getQtyOrdered();
        }

        if ( $qty >= 1 && $qty <= 3) {
            $codigo_producto = 2;
            $ubl = 19362778;
            $alto = 2;
            $ancho = 50;
            $largo = 50;
            $peso = 2;
        }elseif ( $qty >= 4 && $qty <= 6) {
            $codigo_producto = 6;
            $ubl = 19362779;
            $alto = 5;
            $ancho = 50;
            $largo = 50;
            $peso = 3;
        }elseif ( $qty >= 7 ) {
            $codigo_producto = 1;
            $ubl = 19383680;
            $alto = 60;
            $ancho = 33;
            $largo = 60;
            $peso = 5;
        }
        // --- MEJORA SUGERIDA: Estos valores deberían ser configurables ---
        // Se recomienda mover la lógica de `if/elseif` a un método separado
        // y obtener los valores (alto, ancho, largo, peso, etc.) desde
        // la configuración del sistema (system.xml) en lugar de tenerlos fijos en el código.
        // Esto aumenta la flexibilidad del módulo.

        $shippingInfo = $order->getShippingAddress();

        $getStreet = implode(' ', $order->getShippingAddress()->getStreet());

        // --- MEJORA: Obtener datos del remitente desde la configuración ---
        // Estos valores ya no están fijos en el código. Deben ser añadidos
        // a la configuración del módulo en system.xml y al helper de Configuración.
        $params2 = array(
            'codigo_remision' => '',
            'fecha' => '',
            'id_cliente' => $this->_configuration->getClientID(),
            'id_remitente' => '',
            'nit_remitente' => $this->_configuration->getSenderNit(),
            'nombre_remitente' => $this->_configuration->getSenderName(),
            'direccion_remitente' => $this->_configuration->getSenderAddress(),
            'telefono_remitente' => $this->_configuration->getSenderPhone(),
            'ciudad_remitente' => $this->_configuration->getSenderCity(),
            'nit_destinatario' => $shippingInfo->getVatId(),
            'div_destinatario' => '',
            'nombre_destinatario' => $shippingInfo->getName(),
            'direccion_destinatario' => $getStreet,
            'ciudad_destinatario' => $this->getFormattedCity($shippingInfo->getPostcode()),
            'telefono_destinatario' => $shippingInfo->getTelephone(),
            'valor_declarado' => $order->getGrandTotal(),
            'codigo_cuenta' =>  1,
            'codigo_producto' => 0,
            'nivel_servicio' => 1,
            'linea' => '',
            // --- MEJORA: Contenido configurable ---
            'contenido' => $this->_configuration->getDefaultContentDescription(),
            'referencia' => $order->getIncrementId(),
            'observaciones' => 'PEDIDO ECOMMERCE #' . $order->getIncrementId(),
            'estado' => 'IMPRESO',
            'detalle' => array(
                'item' => array(
                    'ubl' => 0,
                    'alto' => $alto,
                    'ancho' => $ancho,
                    'largo' => $largo,
                    'peso' => $peso,
                    'unidades' => 1,
                    'referencia' => '',
                    'nombre_empaque' => '',
                )
            ),
            'cuenta_contable' => '0',
            'centro_costos' => '0',
            'recaudos' => '',
            'margen_izquierdo' => '',
            'margen_superior' => '',
            'usuario_vmi' => '',
            'formato_impresion' => '',
            'atributo1_nombre' => '',
            'atributo1_valor' => '',
            'notificaciones' => (object)array(
                'tipo_medio' => '1',
                'destino_notificacion' => $shippingInfo->getEmail()
            ),
            'atributos_retorno' => (object)array(
                'nit' => '',
                'div' => '',
                'nombre' => '',
                'direccion' => '',
                'codigo_ciudad' => '',
                'telefono' => ''
            ),
            'nro_doc_radicados' => '',
            'nro_sobre' => '',
            'codigo_vendedor' => '',
            'usuario' => $this->_configuration->getUserName(),
            'clave' => hash('sha256', $this->_configuration->getUserPassword())
        );

        $response = $this->_webService->Guias_generarGuia($params2);

        if ($response) {
            $guideNumber = $response->codigo_remision ?? null;
            $pdf_guia = $response->pdf_guia;

            try {
                $this->savePDFsGuide($guideNumber);
                $this->createShipment($order->getId(), $guideNumber);
	        $this->updateShipmentOrderGrid($order->getIncrementId(), $order->getData('sap_id'));
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
                $message = 'Hubo un error en el proceso de Coordinadora para la orden #' . $order->getIncrementId() . $e->getMessage();
                $order->addCommentToStatusHistory($message);
                $this->_orderRepository->save($order);
            }
        } else {
            $this->_logger->error("Error al generar la guía 2 " . json_encode($response));
        }
    }

    /**
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws InputException
     */
    private function createShipment($orderId, $trackingNumber)
    {
        $order = null;
        try {
            $order = $this->_orderRepository->get($orderId);
            try {
                $shipment = $this->prepareShipment($order, $trackingNumber);
                $this->shipmentRepository->save($shipment);

            } catch (\Exception $e) {
                throw new LocalizedException(__($e->getMessage()));
            }
            $this->_shipmentNotifier->notify($shipment);
        } catch (\Exception $e) {
            $message = 'Cannot create shipment for order #'. $order->getIncrementId() . ' ' . $e->getMessage();
            $order->addCommentToStatusHistory($message);
        }
        $this->_orderRepository->save($order);
    }

    /**
     * @param $order Order
     * @param $trackingNumber
     * @return Order\Shipment
     * @throws LocalizedException
     * @throws InputException
     */
    private function prepareShipment(Order $order, $trackingNumber): Order\Shipment
    {
        $shipment = $this->_shipment->toShipment($order);
        try {
            foreach ($order->getAllItems() as $orderItem) {
                // Check if order item has qty to ship or is virtual
                if ($orderItem->getIsVirtual()) {
                    continue;
                }
                $qtyShipped = $orderItem->getQtyOrdered();
                // Create shipment item with qty
                $shipmentItem = $this->_shipment->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                // Add shipment item to shipment
                $shipment->addItem($shipmentItem);
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__("An error has occurred" . " --- " . $e->getMessage()));
        }
        $shipment->addComment($this->getGeneratedShippingComments($trackingNumber), false, false);
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        // --- MEJORA: Simplificación de la obtención del Source Code ---
        $sourceCode = $this->defaultSourceProvider->getCode();

        $dataCarrier = [
            'weight' => '10',
            'qty' => $shipment->getOrder()->getTotalQtyOrdered(),
            'carrier_code' => 'coordinadora',
            'title' => 'Coordinadora',
            'number' => $trackingNumber,
            'description' => 'Coordinadora shipment and tracking'
        ];

        $track = $this->_trackFactory->create()->addData($dataCarrier);
        $shipment->addTrack($track);
        $shipment->getExtensionAttributes()->setSourceCode($sourceCode);

        return $shipment;
    }

    /**
     * Retrieves orders to be shipped and generates the shipment.
     * @throws \Exception
     */
    public function ordersToShip()
    {
        $orderCollection = $this->_orderCollectionFactory->create()
            ->addFieldToSelect(['increment_id'])
            ->addFieldToFilter('status', ['in' => ['processing']])
            ->addFieldToFilter('shipping_method', ['eq' => 'coordinadora_coordinadora'])
            ->addAttributeToFilter('sap_id', ['notnull' => true])
            ->getItems();
        /**
         * @var $orderInfo Order
         */
        foreach ($orderCollection as $orderInfo) {
            $shipment = $this->getShipment($orderInfo->getId());
            if (!$shipment) {
                $order = $this->_orderRepository->get($orderInfo->getId());
                $this->generateShipmentGuide($order); // Pass the loaded order object
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param string $trackingNumber The number guide.
     */
    public function saveShipment(\Magento\Sales\Model\Order\Shipment $shipment, string $trackingNumber)
    {
        $dataCarrier = [
            'weight' => '10',
            'qty' => $shipment->getOrder()->getTotalQtyOrdered(),
            'carrier_code' => 'coordinadora',
            'title' => 'Coordinadora',
            'number' => $trackingNumber,
            'description' => 'Coordinadora shipment and tracking'
        ];
        $shipment->getOrder()->setIsInProcess(true);
        try {
            $track = $this->_trackFactory->create()->addData($dataCarrier);
            $shipment->addTrack($track);
            $shipment->getExtensionAttributes()->setSourceCode('default');
            $shipment->save();
            $this->_shipmentNotifier->notify($shipment);
            $this->savePDFsGuide($trackingNumber);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $guide
     * @return \Magento\Framework\Phrase|string
     * @throws FileSystemException
     */
    public function getGeneratedShippingComments($guide)
    {
        if ($this->_configuration->allowSavePDF()) {
            $urls = $this->getUrls($guide);
            return __("Guide number # ") . "<a href=\"" . $urls['guideUrl'] . "\" download=\"true\" >" . $guide . "</a>"
                . "<br>"
                . __("Tracking number # ") . "<a target=\"_blank\" href=\"" . $urls['trackingUrl'] . "\">" . $guide . "</a>";
        } else {
            return __("Guide number # {$guide}")
                . "\n"
                . __("Tracking number # {$guide}");
        }
    }

    /**
     * @param $guide
     * @throws FileSystemException
     */
    public function savePDFsGuide($guide)
    {
        $numberbody = 1;
        if ($this->_configuration->allowSavePDF()) {
            $response = $this->_webService->Guias_imprimirRotulos($guide, $numberbody);
            if (isset($response->rotulos) && $response->rotulos) {
                $this->saveFile($guide . '.pdf', base64_decode($response->rotulos));
            }
        }
    }

    /**
     * @param string $filename
     * @param string $content
     * @return void
     * @throws FileSystemException
     */
    private function saveFile(string $filename, string $content)
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::PUB);
        // Ensure the directory exists
        $directory->create(self::PDF_SAVE_DIR);
        $filepath = self::PDF_SAVE_DIR . DIRECTORY_SEPARATOR . $filename;
        $directory->writeFile($filepath, $content);
    }

    /**
     * Retrieve urls where files are saved.
     * @param $guide
     * @return array
     */
    public function getUrls($guide): array
    {
        $guideUrl = $this->_configuration->getPDFPath();
        $uriTracking = $this->_configuration->getURLMTrack();
        return [ // The getPDFPath() helper should be updated to return the correct URL
            'guideUrl' => $guideUrl . $guide . '.pdf',
            'trackingUrl' => $uriTracking . $guide
        ];
    }

    /**
     * Checks if order has any shipment.
     * @param $orderId
     * @return array|null
     */
    private function getShipment($orderId): ?array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create();
        try {
            $shipments = $this->shipmentRepository->getList($searchCriteria);
            $shipmentsRecords = $shipments->getItems();
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
            $shipmentsRecords = null;
        }

        return $shipmentsRecords;
    }

    /**
     * Get postcode formatted.
     * @param $postcode
     * @return string|null
     */
    private function getFormattedCity($postcode): ?string
    {
        if ($postcode) {
            return $postcode . '000';
        }
        return null;
    }

    private function getFormattedRegion($region)
    {
        $fRegion = null;
        if ($region == 'VALLE DEL CAUCA') {
            $fRegion = 'VALLE';
        } else {
            $fRegion = $region;
        }
        return $fRegion;
    }

    /**
     * Set the sap order id in the shipment order grid
     *
     * @param $incrementId
     * @param $orderSapId
    */
    public function updateShipmentOrderGrid($incrementId, $orderSapId)
    {
        // --- MEJORA: Evitar SQL directo para mayor compatibilidad y seguridad ---
        // El uso de SQL directo puede ser problemático en futuras actualizaciones de Magento.
        // Una mejor aproximación sería usar los repositorios para cargar el envío
        // y luego guardar el atributo `sap_id` si este se ha añadido correctamente
        // a la entidad de envío y a su tabla de la cuadrícula (grid).
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('sales_shipment_grid');

        $connection->update(
            $tableName,
            ['sap_id' => $orderSapId],
            ['order_increment_id = ?' => $incrementId]
        );
    }
}

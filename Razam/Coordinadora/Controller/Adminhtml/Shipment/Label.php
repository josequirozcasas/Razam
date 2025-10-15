<?php

namespace Razam\Coordinadora\Controller\Adminhtml\Shipment;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Razam\Coordinadora\Helper\WebService;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Label extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::shipment';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    protected $redirectUrl = 'sales/shipment/';

    /**
     * @var \Razam\Coordinadora\Helper\WebService
     */
    private $_webService;


    /**
     * Label constructor.
     * @param Action\Context $context
     * @param LoggerInterface $logger
     * @param FileFactory $fileFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Action\Context $context,
        LoggerInterface $logger,
        FileFactory $fileFactory,
        Filter $filter,
        CollectionFactory $collectionFactory,
        WebService $webService
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->fileFactory = $fileFactory;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->_webService = $webService;
    }

    /**
     * Handles creating process for ZIP archive containing PDF guides
     * from Coordinadora Shipments. This is a custom "Mass Action" performance
     * for multiple saving process.
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
	
	    $numberbody = 2;

            $collection = $this->filter->getCollection($this->collectionFactory->create());

            $guides = $this->getTrackNumbers($collection);
            
            $response = $this->_webService->Guias_imprimirRotulos($guides, $numberbody);

            $fileContent = ['type' => 'string', 'value' => base64_decode( $response->rotulos ), 'rm' => true];

            return $this->fileFactory->create(
                'Coordinadora_Guides.pdf',
                $fileContent,
                DirectoryList::SYS_TMP,
                'application/pdf'
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath($this->redirectUrl);
        }
    }

    /**
     * Retrieves array zip content file.
     * @param $collection
     * @return array
     */
    public function getTrackNumbers($collection): array
    {
        $arrayGuides = [];
        foreach ($collection->getItems() as $shipment) {
            $trackCollections = $shipment->getOrder()->getTracksCollection();
            foreach ($trackCollections as $tc) {
                array_push( $arrayGuides, $tc->getTrackNumber() );
            }
        }
        
        return $arrayGuides;
    }
}

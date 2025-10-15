<?php

namespace Razam\Coordinadora\Setup;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class InstallData implements InstallDataInterface
{
    const ORDER_STATUS_PROCESSING_SHIPPING_CODE = 'processing_shipping';
    const ORDER_STATUS_PROCESSING_SHIPPING_LABEL = 'Shipping';

    /**
     * @var StatusFactory
     */
    protected $_statusFactory;
    /**
     * @var StatusResourceFactory
     */
    protected $_statusResourceFactory;

    /**
     * InstallData constructor.
     * @param StatusFactory $statusFactory
     * @param StatusResourceFactory $statusResourceFactory
     */
    public function __construct(
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->_statusFactory = $statusFactory;
        $this->_statusResourceFactory = $statusResourceFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Exception
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $statusResource = $this->_statusResourceFactory->create();
        $status = $this->_statusFactory->create();
        $status->setData([
            'status' => self::ORDER_STATUS_PROCESSING_SHIPPING_CODE,
            'label' => self::ORDER_STATUS_PROCESSING_SHIPPING_LABEL,
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }

        $status->assignState(Order::STATE_PROCESSING, false, true);
    }
}

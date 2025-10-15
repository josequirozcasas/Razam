<?php

namespace Razam\Coordinadora\Setup;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class UpgradeData implements UpgradeDataInterface
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
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), "1.0.0", "==")) {
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
}

<?php

namespace Razam\Coordinadora\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReviewShipment extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;
    /**
     * @var \Razam\Coordinadora\Model\ShipmentGeneration
     */
    private $_shipmentGeneration;

    public function __construct(
        \Razam\Coordinadora\Model\ShipmentGeneration $shipmentGeneration,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct();
        $this->_shipmentGeneration = $shipmentGeneration;
        $this->state = $state;
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
        $this->_shipmentGeneration->reviewShipments();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("razam:coordinadora:review");
        $this->setDescription("Review generated shipment for orders");
        parent::configure();
    }
}

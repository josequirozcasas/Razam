<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Razam\Coordinadora\Cron;

class GenerateShipping
{
    protected $logger;
    /**
     * @var \Razam\Coordinadora\Model\ShipmentGeneration
     */
    private $shipment;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Razam\Coordinadora\Model\ShipmentGeneration $shipmentGeneration
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Razam\Coordinadora\Model\ShipmentGeneration $shipmentGeneration
    ) {
        $this->logger = $logger;
        $this->shipment = $shipmentGeneration;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->addInfo("Cronjob GenerateShipping is executed.");
        $this->shipment->ordersToShip();
    }
}

<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Razam\Coordinadora\Cron;

class TrackingStatus
{

    protected $logger;
    /**
     * @var \Razam\Coordinadora\Model\Sync\Guide
     */
    protected $_guide;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Razam\Coordinadora\Model\Sync\Guide $guide
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Razam\Coordinadora\Model\Sync\Guide $guide
    ) {
        $this->logger = $logger;
        $this->_guide = $guide;
    }

    /**
     * Execute the cron
     *
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->addInfo("Cronjob Tracking is executed.");
        $this->_guide->syncStatuses();
    }
}

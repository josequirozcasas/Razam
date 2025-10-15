<?php

namespace Razam\Coordinadora\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Guide extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;
    /**
     * @var \Razam\Coordinadora\Model\Sync\Guide
     */
    protected $_guide;

    public function __construct(
        \Razam\Coordinadora\Model\Sync\Guide $guide,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct();
        $this->_guide = $guide;
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
        // $this->_guide->syncStatuses();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("razam:coordinadora:sync:status");
        $this->setDescription("Sync statuses in orders from Coordinadora Guides");
        parent::configure();
    }
}

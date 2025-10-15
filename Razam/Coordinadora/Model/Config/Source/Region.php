<?php

namespace Razam\Coordinadora\Model\Config\Source;

use Magento\Framework\Exception\NoSuchEntityException;

class Region implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    protected $countryAcquirer;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Magento\Directory\Api\CountryInformationAcquirerInterface $acquirer,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->countryAcquirer = $acquirer;
        $this->logger = $logger;
    }

    /**
     * @inheridoc
     */
    public function toOptionArray(): array
    {
        $regions = [];
        try {
            $country = $this->countryAcquirer->getCountryInfo('CO');
            $availableRegions = $country->getAvailableRegions();
            foreach ($availableRegions as $region) {
                $regions[] = [
                    'value' => $region->getId(),
                    'label' => $region->getName()
                ];
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->debug($e->getMessage());
        }
        return $regions;
    }
}

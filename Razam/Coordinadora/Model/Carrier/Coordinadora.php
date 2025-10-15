<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Razam\Coordinadora\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;

class Coordinadora extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    protected $_code = 'coordinadora';

    protected $_isFixed = true;

    protected $_rateResultFactory;

    protected $_rateMethodFactory;

    protected $_trackStatusFactory;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;
    /**
     * @var \Razam\Coordinadora\Helper\Configuration
     */
    private $_configuration;

    /**
     * Coordinadora constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Checkout\Model\Session $session
     * @param \Razam\Coordinadora\Helper\Configuration $configuration
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Checkout\Model\Session $session,
        \Razam\Coordinadora\Helper\Configuration $configuration,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_trackStatusFactory = $trackStatusFactory;
        $this->_checkoutSession = $session;
        $this->_configuration = $configuration;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $postcode = $request->getDestPostcode();
        $regionId = $request->getDestRegionId();

        // --- MEJORA: Simplificar la lógica de validación ---
        if (!$this->isStateAllowed($postcode) && !$this->isRegionAllowed($regionId)) {
            return false;
        }

        if ($this->checkMainState($postcode)) {
            $shippingPrice = $this->getConfigData('pricemc');
        } elseif ($this->checkSecondaryState($postcode)) {
            $shippingPrice = $this->getConfigData('pricesc');
        }

        $result = $this->_rateResultFactory->create();

        if ($shippingPrice !== false) {
            $method = $this->_rateMethodFactory->create();

            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));

            if ($this->checkFreeShipping() && !$this->checkSecondaryState($postcode)) {
                $shippingPrice = '0.00';
            }

            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);

            $result->append($method);
        }

        return $result;
    }

    /**
     * getAllowedMethods
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @return bool
     */
    public function isTrackingAvailable(): bool
    {
        return true;
    }

    /**
     * Checks if the quote total amount is valid for free shipping.
     *
     * @return bool Returns <b>TRUE</b> if it meets, <b>FALSE</b> otherwise.
     */
    private function checkFreeShipping(): bool
    {
        $isFree = $this->_configuration->isFreeShipping();
        if ($isFree) {
            $freeAmount = $this->_configuration->getFreeAmount();
            try {
                $cartAmount = $this->_checkoutSession->getQuote()->getGrandTotal();
                if ($cartAmount > $freeAmount) {
                    return true;
                }
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }
        return false;
    }

    /**
     * Checks the allowed regions to ship.
     * @param $region
     * @return bool
     */
    private function isRegionAllowed($region): bool
    {
        $allowRegions = $this->_configuration->getAllowRegions();
        if ($allowRegions === null || $allowRegions === '') {
            return true; // Si no hay restricciones, permitir todas las regiones
        }
        $regions = explode(',', $allowRegions);
        if (in_array($region, $regions)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the postcode belongs to an allowed state (main or secondary).
     * @param string $postcode
     * @return bool
     */
    private function isStateAllowed(?string $postcode): bool
    {
        return $this->checkMainState($postcode) || $this->checkSecondaryState($postcode);
    }

    /**
     * Checks the allowed main states to ship.
     * @param $state
     * @return bool
     */
    private function checkMainState($state): bool
    {
        $allowStates = $this->_configuration->getAllowMainStates();
        if ($allowStates === null || $allowStates === '') {
            return true; // Si no hay restricciones, permitir todos los estados
        }
        $states = explode(',', $allowStates);
        if (in_array($state, $states)) {
            return true;
        }
        return false;
    }

    /**
     * Checks the allowed secondary states to ship.
     * @param $state
     * @return bool
     */
    private function checkSecondaryState($state): bool
    {
        $allowStates = $this->_configuration->getAllowSecondaryStates();
        if ($allowStates === null || $allowStates === '') {
            return true; // Si no hay restricciones, permitir todos los estados
        }
        $states = explode(',', $allowStates);
        if (in_array($state, $states)) {
            return true;
        }
        return false;
    }
}

<?php

namespace Razam\Coordinadora\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Configuration extends AbstractHelper
{
    public const PATH_URL_TRACKING = 'carriers/coordinadora/url_tracking';
    public const PATH_USER_NAME = 'carriers/coordinadora/username';
    public const PATH_USER_PASSWORD = 'carriers/coordinadora/password';
    public const PATH_SENDER_CODE = 'carriers/coordinadora/sender_code';
    public const PATH_URL_WEBSERVICE = 'carriers/coordinadora/url_webservice';
    public const PATH_CLIENT_ID = 'carriers/coordinadora/id_client';
    public const PATH_MODE = 'carriers/coordinadora/mode';
    public const PATH_ALLOW_PDF = 'carriers/coordinadora/allow_pdf';
    public const PATH_ALLOW_FREE_SHIPPING = 'carriers/coordinadora/free_shipping';
    public const PATH_FREE_SHIPPING_AMOUNT = 'carriers/coordinadora/free_shipping_rule';
    public const PATH_ALLOWED_REGIONS = 'carriers/coordinadora/allow_regions';
    public const PATH_ALLOWED_MAIN_STATES = 'carriers/coordinadora/allow_main_states';
    public const PATH_ALLOWED_SECONDARY_STATES = 'carriers/coordinadora/allow_secondary_states';

    public const NAMESPACE_GUIDES = 'http://schemas.xmlsoap.org/soap/envelope/';
    public const DIR_PDF_FILES = 'pub/coordinadora/';
    public const URL_MOBILE_TRACKING = 'https://ws.coordinadora.com/param/36513/guia/';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * @param null $store
     * @return string
     */
    public function getMode($store = null): string
    {
        return '_' . $this->scopeConfig->getValue(self::PATH_MODE, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @return string
     */
    public function getNameSpacesGuide(): string
    {
        return self::NAMESPACE_GUIDES;
    }

    /**
     * @param string|null $store
     * @return string|null
     */
    public function getUrlTracking(?string $store = null): ?string
    {
        return $this->scopeConfig->getValue(self::PATH_URL_TRACKING, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param string|null $store
     * @return string|null
     */
    public function getUrlWebservice(?string $store = null): ?string
    {
        return $this->scopeConfig->getValue(self::PATH_URL_WEBSERVICE . $this->getMode(), ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param string|null $store
     * @return string|null
     */
    public function getUserName(?string $store = null): ?string
    {
        return $this->scopeConfig->getValue(self::PATH_USER_NAME . $this->getMode(), ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param string|null $store
     * @return string|null
     */
    public function getUserPassword(?string $store = null): ?string
    {
        return $this->scopeConfig->getValue(self::PATH_USER_PASSWORD . $this->getMode(), ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getClientID($store = null)
    {
        return $this->scopeConfig->getValue(self::PATH_CLIENT_ID . $this->getMode(), ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getSenderCode($store = null)
    {
        return $this->scopeConfig->getValue(self::PATH_SENDER_CODE . $this->getMode(), ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Retrieves the path where guides are saved.
     *
     * @return string
     */
    public function getPDFPath(): string
    {
        return $this->_urlBuilder->getBaseUrl() . self::DIR_PDF_FILES;
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function allowSavePDF($store = null)
    {
        return $this->scopeConfig->getValue(self::PATH_ALLOW_PDF, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function isFreeShipping($store = null)
    {
        return $this->scopeConfig->getValue(self::PATH_ALLOW_FREE_SHIPPING, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getFreeAmount($store = null)
    {
        return $this->scopeConfig->getValue(self::PATH_FREE_SHIPPING_AMOUNT, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @return string
     */
    public function getURLMTrack(): string
    {
        return self::URL_MOBILE_TRACKING;
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getAllowRegions($store = null)
    {
        return $this->scopeConfig->getValue(self::PATH_ALLOWED_REGIONS, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getAllowMainStates($store = null)
    {
        return $this->scopeConfig->getValue(self::PATH_ALLOWED_MAIN_STATES, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getAllowSecondaryStates($store = null)
    {
        return $this->scopeConfig->getValue(self::PATH_ALLOWED_SECONDARY_STATES, ScopeInterface::SCOPE_STORE, $store);
    }
}

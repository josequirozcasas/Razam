<?php

namespace Razam\Coordinadora\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    /**
     * @var Configuration
     */
    protected $_configuration;

    public function __construct(
        Context $context,
        \Razam\Coordinadora\Helper\Configuration $configuration
    ) {
        parent::__construct($context);
        $this->_configuration = $configuration;
    }

    /**
     * @return array
     */
    public function optionsSoap(): array
    {
        return array(
            //"uri" => $this->_configuration->getUrlWebservice(),
            //"use" => SOAP_LITERAL,
            "trace" => true,
            "soap_version"  => SOAP_1_1,
            "connection_timeout"=> 60,
            "exceptions" => false,
            "encoding"=> "utf-8",
            'stream_context' => stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    //"ciphers"=>"AES256-SHA"
                )   
            )),
            "cache_wsdl" => WSDL_CACHE_NONE
        );
    }

    /**
     * @param string $name_function
     * @param array $params
     * @param bool $tracking
     * @return \SimpleXMLElement|string|false
     * @throws \Exception
     */
    public function getResource(string $name_function, array $params = array('p' => array()), bool $tracking = false)
    {
        $result = null;
        try {
            if (!$tracking) {
                $client = new \SoapClient($this->_configuration->getUrlWebservice(), $this->optionsSoap());
                //$client->__setLocation($this->_configuration->getUrlWebservice());
            } else {
                $client = new \SoapClient($this->_configuration->getUrlTracking(), $this->optionsSoap());
            }
            
            $data = $client->$name_function($params);

            $name_function_result = $name_function . "Response";

            $result = isset($data->$name_function_result) ? $data->$name_function_result : $data;

            //$result = $client->$name_function( $params );
            //$client->$name_function( $params );
            //$result = $client->__getLastRequest();
        } catch (\SoapFault $e) {
            $this->_logger->error($e->getMessage());
            return false;
        }
        return $result;
    }

}

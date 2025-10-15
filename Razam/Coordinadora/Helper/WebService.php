<?php

namespace Razam\Coordinadora\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use SimpleXMLElement as SimpleXMLElementAlias;

class WebService extends AbstractHelper
{
    /**
     * @var Data
     */
    private $_data;
    /**
     * @var Configuration
     */
    private $_configuration;

    /**
     * WebService constructor.
     * @param Context $context
     * @param Data $data
     * @param Configuration $configuration
     */
    public function __construct(
        Context $context,
        \Razam\Coordinadora\Helper\Data $data,
        \Razam\Coordinadora\Helper\Configuration $configuration
    ) {
        $this->_data = $data;
        $this->_configuration = $configuration;
        parent::__construct($context);
    }

    public function Guias_generarGuia(array $params)
    {
        $response = null;

        try {
            $response = $this->_data->getResource(__FUNCTION__, $params);
        } catch (\SoapFault $e) {
            $this->_logger->error($e->getMessage());
            $response = false;
        }
        return $response;
    }

    /**
     * Sends a request to generate the Sticker Guide PDF.
     * @param mixed $guide
     * @param int $numberbody
     * @return SimpleXMLElementAlias|string|false|null
     * @throws \Exception
     */
    public function Guias_imprimirRotulos($guide, int $numberbody)
    {
	if ( $numberbody == 1) {
            $body = [
                'id_rotulo' => '55',
                'codigos_remisiones' => [ 
                    'item' => $guide
                ],
                'usuario' => $this->_configuration->getUserName(),
                'clave' => hash('sha256', $this->_configuration->getUserPassword())
            ];           
        }elseif ( $numberbody == 2) {
            $body = [
                'id_rotulo' => '55',
                'codigos_remisiones' => $guide,
                'usuario' => $this->_configuration->getUserName(),
                'clave' => hash('sha256', $this->_configuration->getUserPassword())
            ]; 
        }

        return $this->_data->getResource(__FUNCTION__, $body);
    }

    /**
     * @param string $param
     * @return SimpleXMLElementAlias|string|false|null
     * @throws \Exception
     */
    public function Seguimiento_simple(string $param)
    {
        $body = [
            'codigo_remision' => $param,
            'apikey' => $this->_configuration->getTrackingApiKey(),
            'clave' => $this->_configuration->getTrackingApiPassword()
        ];
        return $this->_data->getResource(__FUNCTION__, $body, true);
    }

    /**
     * @param string $param
     * @return SimpleXMLElementAlias|string|false|null
     * @throws \Exception
     */
    public function Seguimiento_detallado(string $param)
    {
        $body = [
            'codigo_remision' => $param,
            'apikey' => $this->_configuration->getTrackingApiKey(),
            'clave' => $this->_configuration->getTrackingApiPassword()
        ];
        return $this->_data->getResource(__FUNCTION__, $body, true);
    }


    /**
     * @param string $guides
     * @return SimpleXMLElementAlias|string|false|null
     * @throws \Exception
     */
    public function EstadoGuiasXML(string $guides)
    {
        $body = [
            'ID_Cliente' => $this->_configuration->getClientID(),
            'RelacionGuias' => $guides
        ];
        return $this->_data->getResource(__FUNCTION__, $body, true);
    }


}

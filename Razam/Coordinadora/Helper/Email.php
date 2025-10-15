<?php

namespace Razam\Coordinadora\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManager;

class Email extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $_transportBuilder;
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $_inlineTranslation;
    /**
     * @var StoreManager
     */
    private $_storeManager;

    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
    }

    /**
     * Send to customer an email when his order is on its way.
     * @param \Magento\Sales\Model\Order $order The order.
     * @param array $track The tracking info.
     */
    public function sendShippingEmail(\Magento\Sales\Model\Order $order, array $track, string $templateId)
    {
        $template = ($templateId == 1) ? 'shipping_processing' : 'shipping_delivered';
        try {
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->_storeManager->getStore()->getId()
            ];
            $templateVars = [
                'store' => $this->_storeManager->getStore(),
                'customer' => $order->getCustomerName(),
                'orderId' => $order->getIncrementId(),
                'trackingID' => $track->codigo_remision,
                'deliveryDate' => $track->fecha_texto
            ];
            $from = ['email' => "hola@evacol.com", 'name' => 'Evacol'];
            $this->_inlineTranslation->suspend();
            $to = ["{$order->getCustomerEmail()}"];
            $transport = $this->_transportBuilder->setTemplateIdentifier($template)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($to)
                ->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslation->resume();
        } catch (NoSuchEntityException | LocalizedException | MailException $e) {
            $this->_logger->error($e->getMessage());
        }
    }
}

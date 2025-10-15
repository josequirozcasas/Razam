<?php

namespace Razam\Coordinadora\Model\Config\Source;

class Mode implements \Magento\Framework\Data\OptionSourceInterface
{
    /** @inheridoc  */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'test', 'label' => __('Testing')],
            ['value' => 'prod', 'label' => __('Production')]
        ];
    }
}

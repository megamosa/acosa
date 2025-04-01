<?php
/**
 * MagoArab OrderActions Actions List Source
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ActionsList implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'view', 'label' => __('View Order')],
            ['value' => 'cancel', 'label' => __('Cancel Order')],
            ['value' => 'hold', 'label' => __('Hold Order')],
            ['value' => 'unhold', 'label' => __('Unhold Order')],
            ['value' => 'invoice', 'label' => __('Invoice Order')],
            ['value' => 'ship', 'label' => __('Ship Order')],
            ['value' => 'reorder', 'label' => __('Reorder')],
            ['value' => 'edit', 'label' => __('Edit Order')],
            ['value' => 'creditmemo', 'label' => __('Credit Memo')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $optionList = [];
        foreach ($this->toOptionArray() as $option) {
            $optionList[$option['value']] = $option['label'];
        }
        return $optionList;
    }
}
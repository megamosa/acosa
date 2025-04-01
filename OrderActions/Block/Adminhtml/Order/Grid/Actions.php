<?php
/**
 * MagoArab OrderActions Grid Actions Block
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Block\Adminhtml\Order\Grid;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;

class Actions extends AbstractRenderer
{
    /**
     * @var OrderActionsHelper
     */
    protected $helper;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param OrderActionsHelper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        OrderActionsHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Render grid column
     *
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        if (!$this->helper->isEnabled()) {
            // If module is not enabled, render default actions
            return $this->getOriginalRenderer()->render($row);
        }

        // Get actions from the original renderer
        $originalHtml = $this->getOriginalRenderer()->render($row);
        
        // Parse the actions from the original HTML
        if (!$originalHtml) {
            return '';
        }
        
        // Filter actions based on permissions
        return $this->filterActions($originalHtml);
    }
    
    /**
     * Get original renderer
     *
     * @return AbstractRenderer
     */
    protected function getOriginalRenderer()
    {
        // Get the original renderer from the column
        $column = $this->getColumn();
        $rendererClass = $column->getData('original_renderer');
        
        if (!$rendererClass) {
            // Default renderer for actions column
            $rendererClass = \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action::class;
        }
        
        return $this->_layout->createBlock($rendererClass);
    }
    
    /**
     * Filter actions HTML based on permissions
     *
     * @param string $html
     * @return string
     */
    protected function filterActions($html)
    {
        // This is a simplified version - in a real implementation,
        // you would need to parse the HTML and filter actions more precisely
        
        $actions = [
            'view' => 'view',
            'cancel' => 'cancel',
            'hold' => 'hold',
            'unhold' => 'unhold',
            'invoice' => 'invoice',
            'ship' => 'ship',
            'reorder' => 'reorder',
            'edit' => 'edit',
            'creditmemo' => 'creditmemo'
        ];
        
        foreach ($actions as $actionId => $actionName) {
            if (!$this->helper->isActionAllowed($actionId)) {
                // Simple string replacement to remove action
                // In a real implementation, this should be more precise
                $html = preg_replace('/<a[^>]*>' . $actionName . '<\/a>/i', '', $html);
            }
        }
        
        return $html;
    }
}
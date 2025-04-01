<?php
/**
 * MagoArab OrderActions Plugin
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Plugin;

use MagoArab\OrderActions\Model\OrderActionsInterface;
use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Backend\Block\Widget\Container;
use Psr\Log\LoggerInterface;

class OrderActionPlugin
{
    /**
     * @var OrderActionsInterface
     */
    protected $orderActions;
    
    /**
     * @var OrderActionsHelper
     */
    protected $helper;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param OrderActionsInterface $orderActions
     * @param OrderActionsHelper $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderActionsInterface $orderActions,
        OrderActionsHelper $helper,
        LoggerInterface $logger
    ) {
        $this->orderActions = $orderActions;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Around get button list
     *
     * @param View $subject
     * @param \Closure $proceed
     * @return array
     */
    public function aroundGetButtonList(View $subject, \Closure $proceed)
    {
        $buttons = $proceed();
        
        if (!$this->helper->isEnabled()) {
            return $buttons;
        }
        
        $filteredButtons = [];
        foreach ($buttons as $buttonId => $buttonData) {
            $actionId = $this->mapButtonToAction($buttonId);
            
            // If this action is controlled and not allowed, skip it
            if ($actionId && !$this->helper->isActionAllowed($actionId)) {
                continue;
            }
            
            $filteredButtons[$buttonId] = $buttonData;
        }
        
        return $filteredButtons;
    }
    
    /**
     * Around prepare mass actions
     *
     * @param Container $subject
     * @param \Closure $proceed
     * @return Container
     */
    public function aroundPrepareMassaction($subject, \Closure $proceed)
    {
        // Call original method
        $result = $proceed();
        
        if (!$this->helper->isEnabled()) {
            return $result;
        }
        
        try {
            // Get mass action block from grid
            $massActionBlock = $subject->getMassactionBlock();
            if ($massActionBlock) {
                $items = $massActionBlock->getItems();
                $filteredItems = [];
                
                foreach ($items as $itemId => $item) {
                    $actionId = $this->mapButtonToAction($itemId);
                    
                    // If this action is not controlled or is allowed, keep it
                    if (!$actionId || $this->helper->isActionAllowed($actionId)) {
                        $filteredItems[$itemId] = $item;
                    }
                }
                
                // Replace items with filtered ones
                $massActionBlock->setItems($filteredItems);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error filtering mass actions: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Map button ID to action code
     *
     * @param string $buttonId
     * @return string|null
     */
    protected function mapButtonToAction($buttonId)
    {
        // Static map for common actions
        $map = [
            'back' => 'view',
            'order_cancel' => 'cancel',
            'order_hold' => 'hold',
            'order_unhold' => 'unhold',
            'order_invoice' => 'invoice',
            'order_ship' => 'ship',
            'order_reorder' => 'reorder',
            'order_edit' => 'edit',
            'order_creditmemo' => 'creditmemo',
            'order_print' => 'print',
            'print_invoice' => 'print_invoice',
            'print_shipment' => 'print_shipment',
            'print_creditmemo' => 'print_creditmemo',
            'print_all' => 'print_all',
            'print_order' => 'print_order',
            'add_comment' => 'add_comment',
            'change_status' => 'change_status',
            
            // Mass action IDs
            'cancel_order' => 'cancel',
            'hold_order' => 'hold',
            'unhold_order' => 'unhold',
            'print_shipping_label' => 'print_shipping',
            'print_invoice' => 'print_invoice',
            'print_packing_slip' => 'print_packing',
            'print_order' => 'print_order',
            'pdfinvoices_order' => 'pdf_invoices',
            'pdfshipments_order' => 'pdf_shipments',
            'pdfcreditmemos_order' => 'pdf_creditmemos',
            'pdfdocs_order' => 'pdf_docs',
            'delete' => 'delete',
            'masscancel' => 'cancel',
            'masshold' => 'hold',
            'massunhold' => 'unhold',
            'massclose' => 'close',
            'create_shipping_label' => 'create_shipping_label',
            'massprint' => 'print',
            'mass_status' => 'change_status',
            'mass_update' => 'update'
        ];
        
        // Try to find in static map
        if (isset($map[$buttonId])) {
            return $map[$buttonId];
        }
        
        // Try to normalize button ID to find a match
        $normalizedId = $this->normalizeActionId($buttonId);
        
        // Add dynamic mapping for third-party extensions
        $customActions = $this->getCustomActions();
        if (isset($customActions[$normalizedId])) {
            return $normalizedId;
        }
        
        // If we can't find a specific mapping, use the normalized button ID
        return $normalizedId;
    }
    
    /**
     * Normalize action ID to be used as a resource identifier
     *
     * @param string $actionId
     * @return string
     */
    protected function normalizeActionId($actionId)
    {
        // Remove common prefixes
        $actionId = str_replace(['order_', 'sales_', 'action_', 'mass'], '', $actionId);
        
        // Sanitize ID
        $actionId = preg_replace('/[^a-z0-9_]/i', '_', strtolower($actionId));
        
        return $actionId;
    }
    
    /**
     * Get custom actions from cache or config
     *
     * @return array
     */
    protected function getCustomActions()
    {
        static $customActions = null;
        
        if ($customActions === null) {
            $customActions = $this->helper->getCustomActions();
        }
        
        return $customActions;
    }
}
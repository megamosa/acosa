<?php
/**
 * MagoArab OrderActions Grid Plugin
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Plugin;

use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;
use Magento\Sales\Block\Adminhtml\Order\Grid;
use Psr\Log\LoggerInterface;

class OrderGridPlugin
{
    /**
     * @var OrderActionsHelper
     */
    protected $helper;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param OrderActionsHelper $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderActionsHelper $helper,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * After prepare mass actions
     *
     * @param Grid $subject
     * @param Grid $result
     * @return Grid
     */
    public function afterPrepareMassaction(Grid $subject, $result)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }
        
        try {
            $items = $subject->getMassactionBlock()->getItems();
            $filteredItems = [];
            
            foreach ($items as $itemId => $item) {
                $actionId = $this->normalizeActionId($itemId);
                
                // Check if we should control this action
                if (!$actionId || $this->helper->isActionAllowed($actionId)) {
                    $filteredItems[$itemId] = $item;
                }
            }
            
            // Replace with filtered items
            $subject->getMassactionBlock()->setItems($filteredItems);
        } catch (\Exception $e) {
            $this->logger->error('Error filtering mass actions: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * After get available actions
     *
     * @param Grid $subject
     * @param array $result
     * @return array
     */
    public function afterGetRowActions($subject, $result)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }
        
        try {
            $filteredActions = [];
            
            foreach ($result as $actionId => $actionData) {
                $normalizedActionId = $this->normalizeActionId($actionId);
                
                // If this action is not controlled or is allowed, keep it
                if (!$normalizedActionId || $this->helper->isActionAllowed($normalizedActionId)) {
                    $filteredActions[$actionId] = $actionData;
                }
            }
            
            return $filteredActions;
        } catch (\Exception $e) {
            $this->logger->error('Error filtering grid row actions: ' . $e->getMessage());
            return $result;
        }
    }
    
    /**
     * After add column
     *
     * @param Grid $subject
     * @param Grid $result
     * @return Grid
     */
    public function afterAddColumn($subject, $result)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }
        
        try {
            $columns = $subject->getColumns();
            
            foreach ($columns as $column) {
                if ($column->getType() == 'action' || $column->getType() == 'actions') {
                    $actions = $column->getActions();
                    if (is_array($actions)) {
                        $filteredActions = [];
                        
                        foreach ($actions as $actionId => $action) {
                            $normalizedActionId = $this->normalizeActionId($actionId);
                            
                            // If this action is not controlled or is allowed, keep it
                            if (!$normalizedActionId || $this->helper->isActionAllowed($normalizedActionId)) {
                                $filteredActions[$actionId] = $action;
                            }
                        }
                        
                        $column->setActions($filteredActions);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error filtering column actions: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * After get actions list
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Grid\Massaction $subject
     * @param array $result
     * @return array
     */
    public function afterGetAvailableActions($subject, $result)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }
        
        try {
            $filteredActions = [];
            
            foreach ($result as $actionId => $actionData) {
                $normalizedActionId = $this->normalizeActionId($actionId);
                
                // If this action is not controlled or is allowed, keep it
                if (!$normalizedActionId || $this->helper->isActionAllowed($normalizedActionId)) {
                    $filteredActions[$actionId] = $actionData;
                }
            }
            
            return $filteredActions;
        } catch (\Exception $e) {
            $this->logger->error('Error filtering available actions: ' . $e->getMessage());
            return $result;
        }
    }
    
    /**
     * Filter actions dropdown items
     *
     * @param \Magento\Backend\Block\Widget\Grid\Extended $subject
     * @param array $result
     * @return array
     */
    public function afterGetHtmlActions($subject, $result)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }
        
        try {
            // This is a little hackish since we need to modify HTML content
            // A better approach would be to extend the Grid template, but this works for now
            $actionOptions = $this->helper->getAvailableActions();
            
            foreach ($actionOptions as $action) {
                if (!$this->helper->isActionAllowed($action)) {
                    // Try to remove action from HTML by looking for common patterns
                    $actionId = preg_quote($action, '/');
                    $result = preg_replace(
                        '/(<li[^>]*>.*?' . $actionId . '.*?<\/li>)/i',
                        '',
                        $result
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error filtering HTML actions: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Normalize action ID
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
}
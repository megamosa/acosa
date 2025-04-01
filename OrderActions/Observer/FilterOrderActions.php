<?php
/**
 * MagoArab OrderActions Menu Filter Observer
 */
namespace MagoArab\OrderActions\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;
use Psr\Log\LoggerInterface;

class FilterActionMenus implements ObserverInterface
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
     * Filter order action menus in UI components
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnabled()) {
            return;
        }
        
        try {
            $jsLayout = $observer->getEvent()->getJsLayout();
            
            if (!$jsLayout || !isset($jsLayout['components'])) {
                return;
            }
            
            $this->processComponents($jsLayout['components']);
            
        } catch (\Exception $e) {
            $this->logger->error('Error filtering action menus: ' . $e->getMessage());
        }
    }
    
    /**
     * Process components recursively to filter actions
     *
     * @param array $components
     */
    private function processComponents(&$components)
    {
        foreach ($components as $name => &$component) {
            if (is_array($component)) {
                // Check if this is an actions component
                if (isset($component['config']['actions']) && is_array($component['config']['actions'])) {
                    $this->filterActionsInComponent($component['config']['actions']);
                }
                
                // Process children components
                if (isset($component['children']) && is_array($component['children'])) {
                    $this->processComponents($component['children']);
                }
                
                // Process nested components
                if (isset($component['components']) && is_array($component['components'])) {
                    $this->processComponents($component['components']);
                }
            }
        }
    }
    
    /**
     * Filter actions in a component based on permissions
     *
     * @param array $actions
     */
    private function filterActionsInComponent(&$actions)
    {
        $filteredActions = [];
        
        foreach ($actions as $key => $action) {
            $actionId = $this->normalizeActionId($action);
            
            // If action is allowed or we couldn't determine an action ID, keep it
            if (!$actionId || $this->helper->isActionAllowed($actionId)) {
                $filteredActions[$key] = $action;
            }
        }
        
        $actions = $filteredActions;
    }
    
    /**
     * Normalize action ID from action config
     *
     * @param array $action
     * @return string|null
     */
    private function normalizeActionId($action)
    {
        if (!is_array($action)) {
            return null;
        }
        
        // Try to get action ID from different possible properties
        $actionId = null;
        
        if (isset($action['type'])) {
            $actionId = $action['type'];
        } elseif (isset($action['action'])) {
            $actionId = $action['action'];
        } elseif (isset($action['actionName'])) {
            $actionId = $action['actionName'];
        } elseif (isset($action['label'])) {
            $actionId = is_string($action['label']) ? $action['label'] : null;
        }
        
        if (!$actionId) {
            return null;
        }
        
        // Remove common prefixes and sanitize
        $actionId = str_replace(['order_', 'sales_', 'action_', 'mass'], '', $actionId);
        $actionId = preg_replace('/[^a-z0-9_]/i', '_', strtolower($actionId));
        
        return $actionId;
    }
}
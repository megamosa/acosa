<?php
/**
 * MagoArab OrderActions Collect Actions Observer
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Psr\Log\LoggerInterface;

class CollectOrderActions implements ObserverInterface
{
    /**
     * @var OrderActionsHelper
     */
    protected $helper;
    
    /**
     * @var CacheTypeConfig
     */
    protected $configCache;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param OrderActionsHelper $helper
     * @param CacheTypeConfig $configCache
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderActionsHelper $helper,
        CacheTypeConfig $configCache,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->configCache = $configCache;
        $this->logger = $logger;
    }

    /**
     * Collect all available order actions from the UI
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
            $block = $observer->getEvent()->getBlock();
            
            // Check if block exists before proceeding
            if (!$block) {
                return;
            }
            
            // Check if this is a sales order grid or related block
            $blockClass = get_class($block);
            
            if (($block instanceof \Magento\Sales\Block\Adminhtml\Order\Grid) || 
                ($block instanceof \Magento\Sales\Block\Adminhtml\Order\View) ||
                (strpos($blockClass, 'Order') !== false && 
                 strpos($blockClass, 'Action') !== false)) {
                    
                // For grid blocks, collect mass actions
                if ($block instanceof \Magento\Sales\Block\Adminhtml\Order\Grid) {
                    $this->collectGridActions($block);
                }
                
                // For view blocks, collect button actions
                if ($block instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
                    $this->collectViewActions($block);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error collecting order actions: ' . $e->getMessage());
        }
    }
    
    /**
     * Collect actions from order grid
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Grid $block
     * @return void
     */
    protected function collectGridActions($block)
    {
        try {
            $customActions = [];
            
            // Get mass actions
            $massActionBlock = $block->getMassactionBlock();
            if ($massActionBlock) {
                $items = $massActionBlock->getItems();
                foreach ($items as $itemId => $item) {
                    $label = $item->getLabel();
                    if ($label) {
                        $actionId = $this->normalizeActionId($itemId);
                        $customActions[$actionId] = $label;
                    }
                }
            }
            
            // Get column actions
            $columns = $block->getColumns();
            foreach ($columns as $column) {
                if ($column->getType() == 'action') {
                    $actions = $column->getActions();
                    if (is_array($actions)) {
                        foreach ($actions as $actionId => $action) {
                            $label = isset($action['caption']) ? $action['caption'] : $actionId;
                            $actionId = $this->normalizeActionId($actionId);
                            $customActions[$actionId] = $label;
                        }
                    }
                }
            }
            
            // Save to cache if we found actions
            if (!empty($customActions)) {
                $this->saveActionCache($customActions);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error collecting grid actions: ' . $e->getMessage());
        }
    }
    
    /**
     * Collect actions from order view
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View $block
     * @return void
     */
    protected function collectViewActions($block)
    {
        try {
            $customActions = [];
            
            // Use reflection to get button list
            $reflection = new \ReflectionClass($block);
            if ($reflection->hasProperty('_buttons')) {
                $property = $reflection->getProperty('_buttons');
                $property->setAccessible(true);
                $buttons = $property->getValue($block);
                
                if (is_array($buttons)) {
                    foreach ($buttons as $buttonId => $button) {
                        if (isset($button['label'])) {
                            $label = $button['label'];
                            $actionId = $this->normalizeActionId($buttonId);
                            $customActions[$actionId] = $label;
                        }
                    }
                }
            }
            
            // Save to cache if we found actions
            if (!empty($customActions)) {
                $this->saveActionCache($customActions);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error collecting view actions: ' . $e->getMessage());
        }
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
        $actionId = str_replace(['order_', 'sales_', 'action_'], '', $actionId);
        
        // Sanitize ID
        $actionId = preg_replace('/[^a-z0-9_]/i', '_', strtolower($actionId));
        
        return $actionId;
    }
    
    /**
     * Save collected actions to cache
     *
     * @param array $newActions
     * @return void
     */
    protected function saveActionCache($newActions)
    {
        try {
            // Get existing actions
            $cachedActions = [];
            $cachedData = $this->configCache->load('magoarab_custom_order_actions');
            
            if ($cachedData) {
                $cachedActions = json_decode($cachedData, true);
                if (!is_array($cachedActions)) {
                    $cachedActions = [];
                }
            }
            
            // Merge with new actions
            $mergedActions = array_merge($cachedActions, $newActions);
            
            // Save to cache
            $this->configCache->save(
                json_encode($mergedActions),
                'magoarab_custom_order_actions',
                ['CONFIG_CACHE'],
                86400 // 24 hours cache
            );
        } catch (\Exception $e) {
            $this->logger->error('Error saving actions to cache: ' . $e->getMessage());
        }
    }
}
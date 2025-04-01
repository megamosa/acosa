<?php
/**
 * MagoArab OrderActions AJAX Controller
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Psr\Log\LoggerInterface;

class CollectActions extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * @var CacheTypeConfig
     */
    protected $configCache;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CacheTypeConfig $configCache
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CacheTypeConfig $configCache,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configCache = $configCache;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $actions = $this->getRequest()->getParam('actions', []);
            
            if (!empty($actions)) {
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
                $mergedActions = array_merge($cachedActions, $actions);
                
                // Save to cache
                $this->configCache->save(
                    json_encode($mergedActions),
                    'magoarab_custom_order_actions',
                    ['CONFIG_CACHE'],
                    86400 // 24 hours cache
                );
                
                return $result->setData([
                    'success' => true,
                    'message' => __('Actions collected successfully.'),
                    'count' => count($mergedActions)
                ]);
            }
            
            return $result->setData([
                'success' => true,
                'message' => __('No actions to collect.'),
                'count' => 0
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error collecting actions: ' . $e->getMessage());
            
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
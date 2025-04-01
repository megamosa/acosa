<?php
/**
 * MagoArab OrderActions Helper
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory as RulesCollectionFactory;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\App\ResourceConnection;

class Data extends AbstractHelper
{
    /**
     * Config paths
     */
    const XML_PATH_ENABLED = 'magoarab_orderactions/general/enabled';
    const XML_PATH_ACTIONS_LIST = 'magoarab_orderactions/general/actions_list';

    /**
     * @var RulesCollectionFactory
     */
    protected $rulesCollectionFactory;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;
    
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param Context $context
     * @param RulesCollectionFactory $rulesCollectionFactory
     * @param AuthorizationInterface $authorization
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        RulesCollectionFactory $rulesCollectionFactory,
        AuthorizationInterface $authorization,
        ResourceConnection $resourceConnection
    ) {
        $this->rulesCollectionFactory = $rulesCollectionFactory;
        $this->authorization = $authorization;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get configured available actions
     *
     * @return array
     */
    public function getAvailableActions()
    {
        $actions = $this->scopeConfig->getValue(
            self::XML_PATH_ACTIONS_LIST,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        
        return $actions ? explode(',', $actions) : [];
    }

    /**
     * Check if user has permission for action
     *
     * @param string $action
     * @return bool
     */
    public function isActionAllowed($action)
    {
        if (!$this->isEnabled()) {
            return true;
        }

        return $this->authorization->isAllowed('MagoArab_OrderActions::action_' . $action);
    }

    /**
     * Get action resource ID
     *
     * @param string $action
     * @return string
     */
    public function getActionResourceId($action)
    {
        return 'MagoArab_OrderActions::action_' . $action;
    }
    
    /**
     * Get custom actions from the system
     *
     * @return array
     */
    public function getCustomActions()
    {
        $customActions = [];
        
        try {
            // Get custom actions from cache or database
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $cacheManager = $objectManager->get(\Magento\Framework\App\Cache\Manager::class);
            
            // Try to get from cache first
            $cache = $objectManager->get(\Magento\Framework\App\Cache\Type\Config::class);
            $cachedActions = $cache->load('magoarab_custom_order_actions');
            
            if ($cachedActions) {
                $customActions = json_decode($cachedActions, true);
            } else {
                // Scan for all possible actions by examining the admin menu and other extensions
                $customActions = $this->scanForCustomActions();
                
                // Save to cache for better performance
                $cache->save(
                    json_encode($customActions),
                    'magoarab_custom_order_actions',
                    ['CONFIG_CACHE'],
                    86400 // 24 hours cache
                );
            }
        } catch (\Exception $e) {
            // Fail silently and return empty array
            $this->_logger->error('Error getting custom actions: ' . $e->getMessage());
        }
        
        return $customActions;
    }
    
    /**
     * Scan for custom actions in the system
     *
     * @return array
     */
    private function scanForCustomActions()
    {
        $customActions = [];
        
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            
            // Add common actions from third-party modules
            $moduleList = $objectManager->get(\Magento\Framework\Module\ModuleListInterface::class);
            $modules = $moduleList->getNames();
            
            $actionPatterns = [
                'print', 'export', 'import', 'send', 'email', 'download', 'generate', 'update',
                'create', 'delete', 'remove', 'add', 'edit', 'change', 'apply', 'assign', 'unassign'
            ];
            
            // Check for event observers that might add custom actions
            foreach ($modules as $moduleName) {
                if ($moduleName === 'Magento_Sales' || $moduleName === 'MagoArab_OrderActions') {
                    continue;
                }
                
                // Look for potential action-related words in the module name
                foreach ($actionPatterns as $pattern) {
                    if (stripos($moduleName, $pattern) !== false) {
                        $moduleNameParts = explode('_', $moduleName);
                        $vendor = $moduleNameParts[0];
                        $name = isset($moduleNameParts[1]) ? $moduleNameParts[1] : '';
                        
                        if ($name && stripos($name, 'Order') !== false) {
                            $actionId = strtolower($vendor . '_' . $name);
                            $customActions[$actionId] = $vendor . ' ' . $name;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error('Error scanning for custom actions: ' . $e->getMessage());
        }
        
        return $customActions;
    }

    /**
     * Get all order actions with their permissions
     *
     * @param int $roleId
     * @return array
     */
    public function getOrderActionsWithPermissions($roleId)
    {
        // Default actions
        $actions = [
            'view' => ['id' => 'view', 'title' => __('View Order')],
            'cancel' => ['id' => 'cancel', 'title' => __('Cancel Order')],
            'hold' => ['id' => 'hold', 'title' => __('Hold Order')],
            'unhold' => ['id' => 'unhold', 'title' => __('Unhold Order')],
            'invoice' => ['id' => 'invoice', 'title' => __('Invoice Order')],
            'ship' => ['id' => 'ship', 'title' => __('Ship Order')],
            'reorder' => ['id' => 'reorder', 'title' => __('Reorder')],
            'edit' => ['id' => 'edit', 'title' => __('Edit Order')],
            'creditmemo' => ['id' => 'creditmemo', 'title' => __('Credit Memo')],
            'print' => ['id' => 'print', 'title' => __('Print')],
            'print_invoice' => ['id' => 'print_invoice', 'title' => __('Print Invoices')],
            'print_shipment' => ['id' => 'print_shipment', 'title' => __('Print PDF Shipments')],
            'print_order' => ['id' => 'print_order', 'title' => __('Print PDF Orders')],
            'print_all' => ['id' => 'print_all', 'title' => __('Print All')],
            'add_comment' => ['id' => 'add_comment', 'title' => __('Add Order Comments')],
            'change_status' => ['id' => 'change_status', 'title' => __('Change Order Status')]
        ];
        
        // Get custom actions from configuration or other sources
        $customActions = $this->getCustomActions();
        
        // Merge custom actions with default ones
        if (!empty($customActions)) {
            foreach ($customActions as $code => $title) {
                if (!isset($actions[$code])) {
                    $actions[$code] = ['id' => $code, 'title' => $title];
                }
            }
        }

        // Get allowed actions for role
        if ($roleId) {
            // Get rules from database directly
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(
                    $this->resourceConnection->getTableName('authorization_rule'),
                    ['resource_id']
                )
                ->where('role_id = ?', $roleId)
                ->where('permission = ?', 'allow');
                
            $resources = $connection->fetchCol($select);
            
            if (!empty($resources)) {
                foreach ($actions as $code => &$action) {
                    $resourceId = $this->getActionResourceId($code);
                    $action['allowed'] = in_array($resourceId, $resources);
                }
            }
        }

        return $actions;
    }
	/**
 * Get current user role ID
 *
 * @return int|null
 */
public function getCurrentUserRoleId()
{
    try {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $authSession = $objectManager->get(\Magento\Backend\Model\Auth\Session::class);
        
        if ($authSession->isLoggedIn()) {
            $userId = $authSession->getUser()->getId();
            
            // Get role ID from user
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from($this->resourceConnection->getTableName('authorization_role'), ['parent_id'])
                ->where('user_id = ?', $userId)
                ->where('user_type = ?', \Magento\Authorization\Model\UserContextInterface::USER_TYPE_ADMIN);
                
            return $connection->fetchOne($select);
        }
    } catch (\Exception $e) {
        $this->_logger->error('Error getting current user role ID: ' . $e->getMessage());
    }
    
    return null;
}
/**
 * Get permissions for JavaScript
 *
 * @return array
 */
public function getPermissionsForJs()
{
    $roleId = $this->getCurrentUserRoleId();
    $actions = $this->getOrderActionsWithPermissions($roleId);
    
    $result = [];
    foreach ($actions as $code => $action) {
        $result[$code] = [
            'id' => $code,
            'allowed' => isset($action['allowed']) ? (bool)$action['allowed'] : true
        ];
    }
    
    return $result;
}
}
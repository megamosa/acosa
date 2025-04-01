<?php
/**
 * MagoArab OrderActions Model
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Model;

use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;
use Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory as RulesCollectionFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Framework\App\ResourceConnection;

class OrderActions implements OrderActionsInterface
{
    /**
     * @var OrderActionsHelper
     */
    protected $helper;
    
    /**
     * @var RulesCollectionFactory
     */
    protected $rulesCollectionFactory;
    
    /**
     * @var RulesFactory
     */
    protected $rulesFactory;
    
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @param OrderActionsHelper $helper
     * @param RulesCollectionFactory $rulesCollectionFactory
     * @param RulesFactory $rulesFactory
     * @param ResourceConnection $resource
     */
    public function __construct(
        OrderActionsHelper $helper,
        RulesCollectionFactory $rulesCollectionFactory,
        RulesFactory $rulesFactory,
        ResourceConnection $resource
    ) {
        $this->helper = $helper;
        $this->rulesCollectionFactory = $rulesCollectionFactory;
        $this->rulesFactory = $rulesFactory;
        $this->resource = $resource;
    }

    /**
     * Get all available order actions
     *
     * @return array
     */
    public function getAvailableActions()
    {
        return $this->helper->getAvailableActions();
    }
    
    /**
     * Check if action is allowed for current user
     *
     * @param string $action
     * @return bool
     */
    public function isActionAllowed($action)
    {
        return $this->helper->isActionAllowed($action);
    }
    
    /**
     * Filter actions based on current user's permissions
     *
     * @param array $actions
     * @return array
     */
    public function filterActions(array $actions)
    {
        if (!$this->helper->isEnabled()) {
            return $actions;
        }
        
        $filteredActions = [];
        foreach ($actions as $actionId => $actionData) {
            // Skip if action is in our control list and not allowed
            if ($this->isActionControlled($actionId) && !$this->isActionAllowed($actionId)) {
                continue;
            }
            
            $filteredActions[$actionId] = $actionData;
        }
        
        return $filteredActions;
    }
    
    /**
     * Check if an action is controlled by our module
     *
     * @param string $actionId
     * @return bool
     */
    protected function isActionControlled($actionId)
    {
        return in_array($actionId, $this->getAvailableActions());
    }
    
    /**
     * Save role action permissions
     *
     * @param int $roleId
     * @param array $permissions
     * @return bool
     */
    public function saveRoleActionPermissions($roleId, array $permissions)
    {
        $connection = $this->resource->getConnection();
        $rulesTable = $this->resource->getTableName('authorization_rule');
        
        try {
            $connection->beginTransaction();
            
            // Remove existing permissions
            $this->removeExistingPermissions($roleId);
            
            // Add new permissions
            foreach ($permissions as $action => $allowed) {
                if (!$allowed) {
                    continue;
                }
                
                $resourceId = $this->helper->getActionResourceId($action);
                $rule = $this->rulesFactory->create();
                $rule->setRoleId($roleId)
                    ->setResourceId($resourceId)
                    ->setPermission('allow');
                $rule->save();
            }
            
            $connection->commit();
            return true;
        } catch (\Exception $e) {
            $connection->rollBack();
            return false;
        }
    }
    
    /**
     * Remove existing permissions for role
     *
     * @param int $roleId
     * @return void
     */
    protected function removeExistingPermissions($roleId)
    {
        $connection = $this->resource->getConnection();
        $rulesTable = $this->resource->getTableName('authorization_rule');
        
        $connection->delete(
            $rulesTable,
            [
                'role_id = ?' => $roleId,
                'resource_id LIKE ?' => 'MagoArab_OrderActions::action_%'
            ]
        );
    }
}
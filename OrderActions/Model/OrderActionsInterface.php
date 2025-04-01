<?php
/**
 * MagoArab OrderActions Interface
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Model;

interface OrderActionsInterface
{
    /**
     * Get all available order actions
     *
     * @return array
     */
    public function getAvailableActions();
    
    /**
     * Check if action is allowed for current user
     *
     * @param string $action
     * @return bool
     */
    public function isActionAllowed($action);
    
    /**
     * Filter actions based on current user's permissions
     *
     * @param array $actions
     * @return array
     */
    public function filterActions(array $actions);
    
    /**
     * Save role action permissions
     *
     * @param int $roleId
     * @param array $permissions
     * @return bool
     */
    public function saveRoleActionPermissions($roleId, array $permissions);
}
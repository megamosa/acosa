<?php
/**
 * MagoArab OrderActions Setup Patch
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Authorization\Model\RulesFactory;

class AddOrderActionPermissions implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    
    /**
     * @var RulesFactory
     */
    private $rulesFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RulesFactory $rulesFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        RulesFactory $rulesFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->rulesFactory = $rulesFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        
        try {
            // Use Object Manager to get admin role
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $connection = $this->moduleDataSetup->getConnection();
            $roleTable = $this->moduleDataSetup->getTable('authorization_role');
            
            // Find administrator role ID
            $select = $connection->select()
                ->from($roleTable, ['role_id'])
                ->where('role_name = ?', 'Administrators')
                ->limit(1);
                
            $adminRoleId = $connection->fetchOne($select);
            
            if ($adminRoleId) {
                $actions = [
                    'view', 'cancel', 'hold', 'unhold', 'invoice', 
                    'ship', 'reorder', 'edit', 'creditmemo'
                ];
                
                foreach ($actions as $action) {
                    $this->rulesFactory->create()
                        ->setRoleId($adminRoleId)
                        ->setResourceId('MagoArab_OrderActions::action_' . $action)
                        ->setPermission('allow')
                        ->save();
                }
            }
        } catch (\Exception $e) {
            // Log error but continue installation
            $objectManager->get(\Psr\Log\LoggerInterface::class)
                ->error('Error applying MagoArab_OrderActions data patch: ' . $e->getMessage());
        }
        
        $this->moduleDataSetup->endSetup();
        
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
<?php
/**
 * MagoArab OrderActions Dynamic Resources Block
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Block\Adminhtml\User\Role\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory as RulesCollectionFactory;
use Magento\Authorization\Model\Acl\AclRetriever;
use Magento\Framework\Acl\RootResource;
use Magento\Authorization\Model\UserContextInterface;

class DynamicResources extends Template
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
     * @var RulesCollectionFactory
     */
    protected $rulesCollectionFactory;
    
    /**
     * @var AclRetriever
     */
    protected $aclRetriever;
    
    /**
     * @var RootResource
     */
    protected $rootResource;

    /**
     * @param Context $context
     * @param OrderActionsHelper $helper
     * @param CacheTypeConfig $configCache
     * @param RulesCollectionFactory $rulesCollectionFactory
     * @param AclRetriever $aclRetriever
     * @param RootResource $rootResource
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderActionsHelper $helper,
        CacheTypeConfig $configCache,
        RulesCollectionFactory $rulesCollectionFactory,
        AclRetriever $aclRetriever,
        RootResource $rootResource,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->configCache = $configCache;
        $this->rulesCollectionFactory = $rulesCollectionFactory;
        $this->aclRetriever = $aclRetriever;
        $this->rootResource = $rootResource;
        parent::__construct($context, $data);
    }
    
    /**
     * Get dynamic order actions
     *
     * @return array
     */
    public function getDynamicOrderActions()
    {
        $actions = [];
        
        // Get from cache
        $cachedData = $this->configCache->load('magoarab_custom_order_actions');
        if ($cachedData) {
            $cachedActions = json_decode($cachedData, true);
            if (is_array($cachedActions)) {
                foreach ($cachedActions as $actionId => $label) {
                    $actions[$actionId] = [
                        'id' => $actionId,
                        'title' => $label,
                        'resource_id' => $this->helper->getActionResourceId($actionId)
                    ];
                }
            }
        }
        
        return $actions;
    }
    
    /**
     * Check if action is allowed for role
     *
     * @param int $roleId
     * @param string $resourceId
     * @return bool
     */
    public function isActionAllowedForRole($roleId, $resourceId)
    {
        if (!$roleId) {
            return false;
        }
        
        $allowedResources = $this->getAllowedResourcesForRole($roleId);
        return in_array($resourceId, $allowedResources);
    }
    
    /**
     * Get allowed resources for role
     *
     * @param int $roleId
     * @return array
     */
    protected function getAllowedResourcesForRole($roleId)
    {
        static $allowedResources = [];
        
        if (!isset($allowedResources[$roleId])) {
            // Get all allowed resources for role
            $rules = $this->rulesCollectionFactory->create()
                ->getByRoles($roleId)
                ->getItems();
            
            $resources = [];
            foreach ($rules as $rule) {
                if ($rule->getPermission() == 'allow') {
                    $resources[] = $rule->getResourceId();
                }
            }
            
            $allowedResources[$roleId] = $resources;
        }
        
        return $allowedResources[$roleId];
    }
    
    /**
     * Get role ID
     *
     * @return int|null
     */
    public function getRoleId()
    {
        return $this->getRequest()->getParam('id');
    }
    
    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isModuleEnabled()
    {
        return $this->helper->isEnabled();
    }
}
<?php
/**
 * MagoArab OrderActions Save Permissions Observer
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MagoArab\OrderActions\Model\OrderActionsInterface;
use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;

class SaveActionPermissions implements ObserverInterface
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
     * @param OrderActionsInterface $orderActions
     * @param OrderActionsHelper $helper
     */
    public function __construct(
        OrderActionsInterface $orderActions,
        OrderActionsHelper $helper
    ) {
        $this->orderActions = $orderActions;
        $this->helper = $helper;
    }

    /**
     * Save order action permissions for role
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnabled()) {
            return;
        }
        
        $request = $observer->getEvent()->getRequest();
        $role = $observer->getEvent()->getObject();
        
        if (!$role || !$role->getId()) {
            return;
        }
        
        $orderActions = $request->getParam('order_actions', []);
        $this->orderActions->saveRoleActionPermissions($role->getId(), $orderActions);
    }
}
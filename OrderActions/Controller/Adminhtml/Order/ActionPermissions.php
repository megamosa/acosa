<?php
/**
 * MagoArab OrderActions Controller
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MagoArab\OrderActions\Model\OrderActionsInterface;

class ActionPermissions extends Action
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
     * @var OrderActionsInterface
     */
    protected $orderActions;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param OrderActionsInterface $orderActions
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderActionsInterface $orderActions
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderActions = $orderActions;
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
            $roleId = $this->getRequest()->getParam('role_id');
            $permissions = $this->getRequest()->getParam('permissions', []);
            
            $success = $this->orderActions->saveRoleActionPermissions($roleId, $permissions);
            
            return $result->setData([
                'success' => $success,
                'message' => $success 
                    ? __('Order action permissions have been saved.')
                    : __('An error occurred while saving order action permissions.')
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
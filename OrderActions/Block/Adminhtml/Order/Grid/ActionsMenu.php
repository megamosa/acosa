<?php
/**
 * MagoArab OrderActions Menu Block
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Block\Adminhtml\Order\Grid;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;

class ActionsMenu extends Template
{
    /**
     * @var OrderActionsHelper
     */
    protected $helper;

    /**
     * @param Context $context
     * @param OrderActionsHelper $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderActionsHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }
    
    /**
     * Get all actions with permissions
     *
     * @return array
     */
    public function getActionsWithPermissions()
    {
        // Get all order actions
        $actions = [
            'change_status' => [
                'id' => 'change_status',
                'title' => __('Change Order Status'),
                'url' => '#',
                'classes' => 'action-primary',
                'sub_menu' => true
            ],
            'create_invoice' => [
                'id' => 'create_invoice',
                'title' => __('Create Invoice'),
                'url' => '#',
                'sub_menu' => false
            ],
            'print_pdf_shipments' => [
                'id' => 'print_shipment',
                'title' => __('Print PDF Shipments'),
                'url' => '#',
                'sub_menu' => false
            ],
            'print_pdf_invoices' => [
                'id' => 'print_invoice',
                'title' => __('Print PDF Invoices'),
                'url' => '#',
                'sub_menu' => false
            ],
            'print_pdf_orders' => [
                'id' => 'print_order',
                'title' => __('Print PDF Orders'),
                'url' => '#',
                'sub_menu' => false
            ],
            'print_all' => [
                'id' => 'print_all',
                'title' => __('Print All'),
                'url' => '#',
                'sub_menu' => false
            ],
            'add_comment' => [
                'id' => 'add_comment',
                'title' => __('Add Comment'),
                'url' => '#',
                'sub_menu' => false
            ],
            'cancel' => [
                'id' => 'cancel',
                'title' => __('Cancel'),
                'url' => '#',
                'sub_menu' => false
            ],
            'hold' => [
                'id' => 'hold',
                'title' => __('Hold'),
                'url' => '#',
                'sub_menu' => false
            ],
            'unhold' => [
                'id' => 'unhold',
                'title' => __('Unhold'),
                'url' => '#',
                'sub_menu' => false
            ],
            'print_invoices' => [
                'id' => 'print_invoices',
                'title' => __('Print Invoices'),
                'url' => '#',
                'sub_menu' => false
            ],
            'print_packing_slips' => [
                'id' => 'print_packing',
                'title' => __('Print Packing Slips'),
                'url' => '#',
                'sub_menu' => false
            ],
            'print_credit_memos' => [
                'id' => 'print_credit_memos',
                'title' => __('Print Credit Memos'),
                'url' => '#',
                'sub_menu' => false
            ],
            'print_all_documents' => [
                'id' => 'print_all_docs',
                'title' => __('Print All Documents'),
                'url' => '#',
                'sub_menu' => false
            ],
            'print_shipping_label' => [
                'id' => 'print_shipping',
                'title' => __('Print Shipping Label'),
                'url' => '#',
                'sub_menu' => false
            ],
        ];
        
        // Filter actions based on permissions
        foreach ($actions as $key => $action) {
            $actionId = $action['id'];
            $actions[$key]['allowed'] = $this->helper->isActionAllowed($actionId);
        }
        
        return $actions;
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
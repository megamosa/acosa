<?php
/**
 * MagoArab OrderActions Admin Role Plugin
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Plugin;

use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;
use Magento\User\Block\Role\Edit\Tab\Info;
use Magento\Framework\DataObject;

class AdminRoleSavePlugin
{
    /**
     * @var OrderActionsHelper
     */
    protected $helper;

    /**
     * @param OrderActionsHelper $helper
     */
    public function __construct(
        OrderActionsHelper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * After get form
     *
     * @param Info $subject
     * @param \Magento\Framework\Data\Form $result
     * @return \Magento\Framework\Data\Form
     */
    public function afterGetForm(Info $subject, $result)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }

        $roleId = $subject->getRequest()->getParam('id', false);
        $actions = $this->helper->getOrderActionsWithPermissions($roleId);
        
        // Create a new fieldset for our Order Actions
        $orderActionsFieldset = $result->addFieldset(
            'magoarab_order_actions',
            [
                'legend' => __('Order Actions Permissions'),
                'class' => 'fieldset-wide',
                'collapsable' => true
            ]
        );

        foreach ($actions as $actionCode => $actionData) {
            $orderActionsFieldset->addField(
                'order_action_' . $actionCode,
                'checkbox',
                [
                    'name' => 'order_actions[' . $actionCode . ']',
                    'label' => $actionData['title'],
                    'title' => $actionData['title'],
                    'value' => 1,
                    'checked' => isset($actionData['allowed']) ? (bool)$actionData['allowed'] : false
                ]
            );
        }

        return $result;
    }
}
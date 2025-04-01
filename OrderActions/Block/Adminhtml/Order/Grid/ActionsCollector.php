<?php
/**
 * MagoArab OrderActions Actions Collector Block
 *
 * @category  MagoArab
 * @package   MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Block\Adminhtml\Order\Grid;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use MagoArab\OrderActions\Helper\Data as OrderActionsHelper;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;

class ActionsCollector extends Template
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
     * @param Context $context
     * @param OrderActionsHelper $helper
     * @param CacheTypeConfig $configCache
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderActionsHelper $helper,
        CacheTypeConfig $configCache,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->configCache = $configCache;
        parent::__construct($context, $data);
    }
    
    /**
     * Collect actions from DOM after page is rendered
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->helper->isEnabled()) {
            return '';
        }
        
        // We use this block to inject a JavaScript that will scan the DOM
        // and collect all order action elements dynamically
        return '<script type="text/javascript">
            require(["jquery", "mage/cookies"], function($) {
                $(document).ready(function() {
                    // Wait for the page to fully load
                    setTimeout(function() {
                        var actions = {};
                        
                        // Collect from dropdown menus
                        $(".admin__data-grid-header-row .action-select-wrap .action-menu li a, ' .
                        '.page-actions-buttons button, ' .
                        '.page-actions .actions-split .dropdown-menu li a, ' .
                        '.order-actions-toolbar button, ' .
                        '.order-details div.actions a, ' .
                        '.order-details div.actions button, ' .
                        '.order-actions button").each(function() {
                            var text = $(this).text().trim();
                            var id = $(this).attr("id") || "";
                            var className = $(this).attr("class") || "";
                            var dataAction = $(this).attr("data-action") || "";
                            
                            // Skip if empty text
                            if (!text) return;
                            
                            // Generate an action ID from various attributes
                            var actionId = "";
                            
                            if (id) {
                                actionId = id.toLowerCase().replace(/[^a-z0-9_]/g, "_");
                            } else if (dataAction) {
                                actionId = dataAction.toLowerCase().replace(/[^a-z0-9_]/g, "_");
                            } else {
                                // Generate from text
                                actionId = text.toLowerCase().replace(/[^a-z0-9]/g, "_");
                            }
                            
                            // Skip if already collected
                            if (!actions[actionId]) {
                                actions[actionId] = text;
                            }
                        });
                        
                        // Save to server if we found any actions
                        if (Object.keys(actions).length > 0) {
                            // Store in a cookie, limited to 4K in size
                            var json = JSON.stringify(actions);
                            if (json.length < 4000) {
                                $.mage.cookies.set("magoarab_order_actions", json, {
                                    lifetime: 86400  // 24 hours
                                });
                            }
                            
                            // Also send to server for persistent storage
                            $.ajax({
                                url: "' . $this->getUrl('magoarab_orderactions/ajax/collectActions') . '",
                                type: "POST",
                                data: {
                                    actions: actions,
                                    form_key: window.FORM_KEY
                                },
                                dataType: "json"
                            });
                        }
                    }, 2000);  // Wait 2 seconds for everything to load
                });
            });
        </script>';
    }
}
/**
 * Direct Action Filter for MagoArab OrderActions
 */
define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';
    
    return function(config) {
        // Wait for DOM to be fully loaded
        $(document).ready(function() {
            if (!config.isEnabled) {
                return;
            }
            
            // Set a timer to check periodically for the dropdown
            var checkInterval = setInterval(function() {
                // Find the Actions dropdown in sales order grid
                var $actionsMenu = $('#sales_order_grid-sales_order_columns-actions, .admin__data-grid-header-row .action-select-wrap');
                
                if ($actionsMenu.length) {
                    filterActionsDropdown();
                    clearInterval(checkInterval);
                }
            }, 500);
            
            // Also add event listener for dynamic content
            $(document).on('click', '.action-select, .action-menu-item', function() {
                setTimeout(filterActionsDropdown, 50);
            });
            
            /**
             * Filter the actions dropdown based on permissions
             */
            function filterActionsDropdown() {
                // Target the specific dropdown menu for Actions
                $('.admin__data-grid-header-row .action-select-wrap .action-menu li, ' + 
                  '.admin__data-grid-header-row .actions-split, ' +
                  '.action-menu-items').each(function() {
                    processActionMenu($(this));
                });
                
                // Handle the Change Order Status submenu specifically
                $('.action-submenu, .action-menu li, ' +
                  '.action-menu-items .item, ' +
                  '.action-menu-item').each(function() {
                    processActionMenuItem($(this));
                });
            }
            
            /**
             * Process an action menu container
             */
            function processActionMenu($menuContainer) {
                $menuContainer.find('> .action-menu-item, > li, > a').each(function() {
                    processActionMenuItem($(this));
                });
            }
            
            /**
             * Process a single action menu item
             */
            function processActionMenuItem($item) {
                // Get the text content to identify the action
                var text = $item.text().trim();
                var actionId = normalizeActionId(text);
                var href = $item.attr('href') || '';
                
                // Special case for Change Order Status
                if (text === 'Change Order Status' || 
                    actionId === 'change_order_status' || 
                    href.indexOf('massStatus') !== -1) {
                    
                    if (!isActionAllowed('change_status')) {
                        $item.hide();
                    }
                    return;
                }
                
                // Handle specific cases
                if (text === 'Cancel' || actionId === 'cancel') {
                    if (!isActionAllowed('cancel')) {
                        $item.hide();
                    }
                    return;
                }
                
                if (text === 'Hold' || actionId === 'hold') {
                    if (!isActionAllowed('hold')) {
                        $item.hide();
                    }
                    return;
                }
                
                if (text === 'Unhold' || actionId === 'unhold') {
                    if (!isActionAllowed('unhold')) {
                        $item.hide();
                    }
                    return;
                }
                
                if (text.indexOf('Invoice') !== -1 || actionId.indexOf('invoice') !== -1) {
                    if (!isActionAllowed('invoice')) {
                        $item.hide();
                    }
                    return;
                }
                
                if (text.indexOf('Shipment') !== -1 || 
                    text.indexOf('Ship') !== -1 || 
                    actionId.indexOf('ship') !== -1) {
                    if (!isActionAllowed('ship')) {
                        $item.hide();
                    }
                    return;
                }
                
                if (text.indexOf('Comment') !== -1 || actionId.indexOf('comment') !== -1) {
                    if (!isActionAllowed('add_comment')) {
                        $item.hide();
                    }
                    return;
                }
                
                if (text.indexOf('Print') !== -1 || actionId.indexOf('print') !== -1) {
                    if (!isActionAllowed('print')) {
                        $item.hide();
                    }
                    return;
                }
                
                // For all other actions, check by normalized ID
                if (actionId && !isActionAllowed(actionId)) {
                    $item.hide();
                }
            }
            
            /**
             * Normalize action ID from text
             */
            function normalizeActionId(text) {
                if (!text) {
                    return '';
                }
                
                return text.toLowerCase()
                    .replace(/[^a-z0-9]/g, '_');
            }
            
            /**
             * Check if an action is allowed based on configuration
             */
            function isActionAllowed(actionId) {
                var permissions = config.allowedActions || {};
                
                // First check directly
                if (permissions[actionId] && permissions[actionId].allowed === false) {
                    return false;
                }
                
                // Then check variations
                for (var id in permissions) {
                    if (permissions.hasOwnProperty(id) && 
                        (actionId.indexOf(id) !== -1 || id.indexOf(actionId) !== -1) && 
                        permissions[id].allowed === false) {
                        return false;
                    }
                }
                
                return true;
            }
            
            // Initial filter
            setTimeout(filterActionsDropdown, 1000);
        });
    };
});
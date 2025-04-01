/**
 * MagoArab OrderActions JS Component
 */
define([
    'jquery',
    'mage/cookies'
], function ($) {
    'use strict';
    
    return {
        /**
         * Initialize the component
         * 
         * @param {Object} config Configuration
         */
        init: function (config) {
            var self = this;
            
            this.config = $.extend({
                isEnabled: true,
                allowedActions: {}
            }, config);
            
            if (!this.config.isEnabled) {
                return;
            }
            
            $(document).ready(function () {
                self.filterActionMenus();
                
                // Handle dynamically generated menus
                $(document).on('click', '.admin__data-grid-wrap .action-select, .actions-split .action-toggle, #sales_order_grid_massaction-select', function () {
                    setTimeout(function () {
                        self.filterActionMenus();
                    }, 50);
                });
            });
        },
        
        /**
         * Filter action menus based on permissions
         */
        filterActionMenus: function () {
            var self = this;
            
            // Process grid action menus
            $('.admin__data-grid-wrap .action-select-wrap .action-menu, .actions-split .dropdown-menu, #sales_order_grid_massaction-select + ul').each(function () {
                var $menu = $(this);
                
                $menu.find('li').each(function () {
                    var $item = $(this);
                    var text = $item.text().trim();
                    var actionId = self.normalizeActionId(text);
                    
                    // Check if action is allowed
                    if (self.isActionAllowed(actionId, text)) {
                        $item.show();
                    } else {
                        $item.hide();
                    }
                });
            });
        },
        
        /**
         * Normalize an action ID from text
         * 
         * @param {String} text Action text
         * @return {String} Normalized action ID
         */
        normalizeActionId: function (text) {
            return text.toLowerCase().replace(/[^a-z0-9]/g, '_');
        },
        
        /**
         * Check if an action is allowed
         * 
         * @param {String} actionId Action ID
         * @param {String} text Original action text
         * @return {Boolean} True if allowed
         */
        isActionAllowed: function (actionId, text) {
            var allowedActions = this.config.allowedActions;
            
            // If no actions specified, all are allowed
            if ($.isEmptyObject(allowedActions)) {
                return true;
            }
            
            // Check if action is explicitly allowed
            for (var id in allowedActions) {
                if (allowedActions.hasOwnProperty(id)) {
                    var action = allowedActions[id];
                    
                    // Match by ID or title
                    if ((action.id === actionId || 
                         action.title.toLowerCase() === text.toLowerCase() || 
                         text.toLowerCase().indexOf(action.title.toLowerCase()) !== -1)) {
                        return action.allowed;
                    }
                }
            }
            
            // Default to allowed if not found
            return true;
        }
    };
});
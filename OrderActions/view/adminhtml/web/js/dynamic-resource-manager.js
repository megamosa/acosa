/**
 * MagoArab OrderActions Dynamic Resource Manager JS
 */
define([
    'jquery',
    'mage/cookies'
], function ($) {
    'use strict';
    
    return {
        /**
         * Initialize the resource manager
         */
        init: function (config) {
            var self = this;
            
            // Configuration
            this.config = $.extend({
                collectUrl: '',
                formKey: '',
                isEnabled: true
            }, config);
            
            if (!this.config.isEnabled) {
                return;
            }
            
            // Wait for the page to load fully
            $(document).ready(function () {
                setTimeout(function () {
                    self.collectActions();
                }, 2000); // Wait 2 seconds for JS to fully load
            });
        },
        
        /**
         * Collect actions from the page
         */
        collectActions: function () {
            var self = this;
            var actions = {};
            
            // Collect from various UI elements
            this.collectFromSelectors([
                '.admin__data-grid-header-row .action-select-wrap .action-menu li a',
                '.page-actions-buttons button',
                '.page-actions .actions-split .dropdown-menu li a',
                '.order-actions-toolbar button',
                '.order-details div.actions a',
                '.order-details div.actions button',
                '.order-actions button',
                '.action-select-wrap .action-menu-items .action-menu-item',
                '.admin__action-dropdown-menu .action-dropdown-menu-item'
            ], actions);
            
            // Save to server if we found any actions
            if (Object.keys(actions).length > 0) {
                // Store in a cookie, limited to 4K in size
                var json = JSON.stringify(actions);
                if (json.length < 4000) {
                    $.mage.cookies.set('magoarab_order_actions', json, {
                        lifetime: 86400 // 24 hours
                    });
                }
                
                // Send to server
                if (this.config.collectUrl && this.config.formKey) {
                    $.ajax({
                        url: this.config.collectUrl,
                        type: 'POST',
                        data: {
                            actions: actions,
                            form_key: this.config.formKey
                        },
                        dataType: 'json'
                    });
                }
            }
        },
        
        /**
         * Collect actions from UI elements matching selectors
         * 
         * @param {Array} selectors
         * @param {Object} actions
         */
        collectFromSelectors: function (selectors, actions) {
            var self = this;
            var selector = selectors.join(', ');
            
            $(selector).each(function () {
                var $element = $(this);
                var text = $element.text().trim();
                var id = $element.attr('id') || '';
                var className = $element.attr('class') || '';
                var dataAction = $element.attr('data-action') || '';
                
                // Skip if empty text
                if (!text) {
                    return;
                }
                
                // Generate an action ID from various attributes
                var actionId = '';
                
                if (id) {
                    actionId = id.toLowerCase().replace(/[^a-z0-9_]/g, '_');
                } else if (dataAction) {
                    actionId = dataAction.toLowerCase().replace(/[^a-z0-9_]/g, '_');
                } else {
                    // Generate from text
                    actionId = text.toLowerCase().replace(/[^a-z0-9]/g, '_');
                }
                
                // Skip if already collected
                if (!actions[actionId]) {
                    actions[actionId] = text;
                }
            });
            
            return actions;
        }
    };
});
/**
 * MagoArab OrderActions Filter JS
 */
define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';
    
    /**
     * Filter action menus in the UI based on permissions
     */
    return function (config) {
        // Check if the module is enabled
        if (!config.isEnabled) {
            return;
        }
        
        // Add a DOM observer to watch for menu elements being added to the page
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length) {
                    for (var i = 0; i < mutation.addedNodes.length; i++) {
                        var node = mutation.addedNodes[i];
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            filterActionMenus($(node));
                        }
                    }
                }
            });
        });
        
        // Start observing the document body for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Initial filtering of any existing menus
        $(document).ready(function() {
            filterActionMenus($('body'));
        });
        
        /**
         * Filter action menus in the given container
         * 
         * @param {jQuery} $container The container element to filter menus in
         */
        function filterActionMenus($container) {
            // Filter action menu items in dropdowns
            $container.find('.admin__action-dropdown-menu li, .action-menu li, .actions-split .dropdown-menu li').each(function() {
                var $item = $(this);
                var text = $item.text().trim();
                var actionId = normalizeActionId(text);
                
                if (actionId && !isActionAllowed(actionId)) {
                    $item.hide();
                }
            });
            
            // Handle mass actions in sales grid
            $container.find('.action-select-wrap .action-menu-item').each(function() {
                var $item = $(this);
                var text = $item.text().trim();
                var actionId = normalizeActionId(text);
                
                if (actionId && !isActionAllowed(actionId)) {
                    $item.hide();
                }
            });
        }
        
        /**
         * Normalize action ID from text
         * 
         * @param {string} text Action text
         * @return {string} Normalized action ID
         */
        function normalizeActionId(text) {
            if (!text) {
                return '';
            }
            
            // Remove common prefixes and sanitize
            var actionId = text.toLowerCase()
                .replace(/[^a-z0-9]/g, '_');
            
            return actionId;
        }
        
        /**
         * Check if an action is allowed based on configuration
         * 
         * @param {string} actionId Action ID
         * @return {boolean} Whether the action is allowed
         */
        function isActionAllowed(actionId) {
            if (!config.allowedActions || !config.allowedActions[actionId]) {
                // If no specific configuration for this action, default to allowed
                return true;
            }
            
            return config.allowedActions[actionId].allowed !== false;
        }
    };
});
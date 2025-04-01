/**
 * MagoArab OrderActions DOM Observer
 * This runs directly as a script to catch DOM changes even before RequireJS is fully loaded
 */
window.magoarabOrderActionsObserver = {
    init: function(config) {
        // Store the configuration for later use
        this.config = config || {};
        
        // Initialize immediately
        this.setupObserver();
        
        // Also run on DOMContentLoaded to catch existing elements
        document.addEventListener('DOMContentLoaded', function() {
            window.magoarabOrderActionsObserver.processExistingElements();
        });
    },
    
    setupObserver: function() {
        var self = this;
        
        // Create a MutationObserver to watch for changes
        this.observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length) {
                    for (var i = 0; i < mutation.addedNodes.length; i++) {
                        var node = mutation.addedNodes[i];
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            self.processElement(node);
                        }
                    }
                }
            });
        });
        
        // Start observing the document body
        this.observer.observe(document.body || document.documentElement, {
            childList: true,
            subtree: true
        });
    },
    
    processExistingElements: function() {
        var self = this;
        
        // Process all existing action menus
        var actionMenus = document.querySelectorAll('.action-menu, .action-select-wrap, .actions-split');
        actionMenus.forEach(function(menu) {
            self.processElement(menu);
        });
    },
    
    processElement: function(element) {
        // Skip if not relevant for actions
        if (!element.classList) return;
        
        // Look for action menus
        var isActionContainer = 
            element.classList.contains('action-menu') || 
            element.classList.contains('action-select-wrap') || 
            element.classList.contains('actions-split') ||
            element.classList.contains('action-menu-items');
        
        if (isActionContainer) {
            this.processActionMenu(element);
        }
        
        // Also look for individual action items
        if (element.classList.contains('action-menu-item') || 
            element.tagName === 'LI' || 
            element.classList.contains('item')) {
            this.processActionItem(element);
        }
        
        // Process children
        var actionItems = element.querySelectorAll('.action-menu-item, .action-menu li, .item');
        actionItems.forEach(this.processActionItem.bind(this));
    },
    
    processActionMenu: function(menuElement) {
        var self = this;
        
        // Process all items in this menu
        var items = menuElement.querySelectorAll('li, .action-menu-item, a.item');
        items.forEach(function(item) {
            self.processActionItem(item);
        });
    },
    
    processActionItem: function(item) {
        // Get the text content to identify the action
        var text = item.textContent.trim();
        if (!text) return;
        
        var actionId = this.normalizeActionId(text);
        if (!actionId) return;
        
        // Check for specific action types
        if (actionId === 'change_order_status' || 
            text.indexOf('Change Order Status') !== -1) {
            if (!this.isActionAllowed('change_status')) {
                this.hideElement(item);
            }
            return;
        }
        
        // Handle common cases
        if (actionId === 'cancel' || text === 'Cancel') {
            if (!this.isActionAllowed('cancel')) {
                this.hideElement(item);
            }
            return;
        }
        
        if (actionId === 'hold' || text === 'Hold') {
            if (!this.isActionAllowed('hold')) {
                this.hideElement(item);
            }
            return;
        }
        
        if (actionId === 'unhold' || text === 'Unhold') {
            if (!this.isActionAllowed('unhold')) {
                this.hideElement(item);
            }
            return;
        }
        
        // General check for any other action
        if (!this.isActionAllowed(actionId)) {
            this.hideElement(item);
        }
    },
    
    normalizeActionId: function(text) {
        if (!text) return '';
        
        return text.toLowerCase()
            .replace(/[^a-z0-9]/g, '_');
    },
    
    isActionAllowed: function(actionId) {
        if (!this.config.permissions) return true;
        
        var permissions = this.config.permissions;
        
        // Direct check
        if (permissions[actionId] && permissions[actionId].allowed === false) {
            return false;
        }
        
        // Check similar actions
        for (var id in permissions) {
            if (permissions.hasOwnProperty(id) && 
                (actionId.indexOf(id) !== -1 || id.indexOf(actionId) !== -1) && 
                permissions[id].allowed === false) {
                return false;
            }
        }
        
        return true;
    },
    
    hideElement: function(element) {
        // Add both display:none and a class
        element.style.display = 'none';
        element.classList.add('magoarab-hidden-action');
    }
};
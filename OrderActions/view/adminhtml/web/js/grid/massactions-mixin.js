/**
 * MagoArab OrderActions Massactions Mixin
 */
define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';
    
    return function (target) {
        return target.extend({
            /**
             * Initialize component
             * @returns {Object} Chainable
             */
            initialize: function () {
                this._super();
                this.filterActions();
                return this;
            },
            
            /**
             * Filter actions based on user permissions
             */
            filterActions: function () {
                if (!window.magoarabOrderActions || !window.magoarabOrderActions.permissions) {
                    return;
                }
                
                var permissions = window.magoarabOrderActions.permissions;
                var filteredActions = [];
                
                _.each(this.actions, function (action) {
                    var actionId = this.normalizeActionId(action);
                    
                    if (!actionId || this.isActionAllowed(actionId, permissions)) {
                        filteredActions.push(action);
                    }
                }, this);
                
                this.actions = filteredActions;
            },
            
            /**
             * Normalize action ID from action object
             * 
             * @param {Object} action Action object
             * @return {string|null} Normalized action ID
             */
            normalizeActionId: function (action) {
                if (!action) {
                    return null;
                }
                
                var actionId = null;
                
                if (action.type) {
                    actionId = action.type;
                } else if (action.label) {
                    actionId = action.label;
                }
                
                if (!actionId) {
                    return null;
                }
                
                if (typeof actionId === 'object' && actionId.trim) {
                    actionId = actionId.trim();
                }
                
                if (typeof actionId !== 'string') {
                    return null;
                }
                
                // Remove common prefixes and sanitize
                actionId = actionId.replace(/[^a-z0-9]/gi, '_').toLowerCase();
                
                return actionId;
            },
            
            /**
             * Check if an action is allowed based on permissions
             * 
             * @param {string} actionId Action ID
             * @param {Object} permissions Permissions object
             * @return {boolean} Whether the action is allowed
             */
            isActionAllowed: function (actionId, permissions) {
                if (!permissions || !permissions[actionId]) {
                    // If no specific configuration for this action, default to allowed
                    return true;
                }
                
                return permissions[actionId].allowed !== false;
            }
        });
    };
});
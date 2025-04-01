/**
 * MagoArab OrderActions RequireJS config
 */
var config = {
    map: {
        '*': {
            magoarabDynamicResourceManager: 'MagoArab_OrderActions/js/dynamic-resource-manager',
            magoarabOrderActions: 'MagoArab_OrderActions/js/order-actions',
            magoarabActionFilter: 'MagoArab_OrderActions/js/action-filter',
            magoarabDirectActionFilter: 'MagoArab_OrderActions/js/direct-action-filter'
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/grid/massactions': {
                'MagoArab_OrderActions/js/grid/massactions-mixin': true
            },
            'Magento_Sales/js/order/grid/massactions': {
                'MagoArab_OrderActions/js/grid/massactions-mixin': true
            }
        }
    }
};
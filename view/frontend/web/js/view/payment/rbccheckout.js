 define(
     [
         'uiComponent',
         'Magento_Checkout/js/model/payment/renderer-list'
     ],
     function(
         Component,
         rendererList
     ) {
         'use strict';
         rendererList.push({
            type: 'rbccheckout',
            component: 'Bread_BreadCheckout/js/view/payment/method-renderer/rbccheckout'
        });
         /**
          * Add view logic here if needed 
          */
         return Component.extend({});
     }
 );
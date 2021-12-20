Bread Checkout for Magento 2
=============================

Helping retailers acquire and convert more customers.

Bread Finance Features
----------------------

* Full Funnel. Your shoppers can discover, pre-qualify, and check out from anywhere - your homepage, 
category page, product page, cart, or checkout. 
* Real-Time Decision. Pre-qualification is quick and easy. Let your customers learn 
about their purchase power in seconds without ever leaving your site.

Installation
------------

### Install using Zip archive

1. Download repository as zip file

2. Unzip contents into `app/code/Bread/BreadCheckout` folder

3. Setup files

For US Merchants
-----------------

- Copy file `app/code/Bread/BreadCheckout/etc/adminhtml/system.us.xml` to `app/code/Bread/BreadCheckout/etc/adminhtml/system.xml`
- Copy file `app/code/Bread/BreadCheckout/view/adminhtml/web/js/validation.us.js` to `app/code/Bread/BreadCheckout/view/adminhtml/web/js/validation.js`

For Canada Merchants
--------------------

- Copy file `app/code/Bread/BreadCheckout/etc/adminhtml/system.ca.xml` to `app/code/Bread/BreadCheckout/etc/adminhtml/system.xml`
- Copy file `app/code/Bread/BreadCheckout/view/adminhtml/web/js/validation.ca.js` to `app/code/Bread/BreadCheckout/view/adminhtml/web/js/validation.js`


4. Enable module
    ```bash
    bin/magento module:enable Bread_BreadCheckout
    bin/magento setup:upgrade
    bin/magento setup:di:compile
    bin/magento setup:static-content:deploy
    ```

### Install using composer. 

- This is currently available for Bread Classic merchants only

1. Navigate to your Magento 2 root folder

2. Install the Bread Checkout module    
     ```bash
     composer require breadfinance/module-breadcheckout
     ```

3. Enable module
    ```bash
    bin/magento module:enable Bread_BreadCheckout
    bin/magento setup:upgrade
    bin/magento setup:di:compile
    bin/magento setup:static-content:deploy
    ```
    

## Usage instructions:

For US Merchants
-----------------
See documentation at https://docs.getbread.com
Contact your Bread representative for login credentials.

For Canada Merchants
--------------------
See documentation at https://rbcpayplan.readme.io/rbc-onboarding/docs/magento-2-integration-steps 
Contact your Payplan representative for login credentials.
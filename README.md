Bread Checkout for Magento 2
============================

Bread is a full-funnel, white label financing solution that helps retailers acquire 
and convert more customers. Retailers who use Bread have seen an increase of 5-15% in sales, 
up to 120% higher AOV, and an 84% increase in email click-through-rates.

Bread’s Features
----------------

* Full Funnel. Your shoppers can discover, pre-qualify, and check out from anywhere - your homepage, 
category page, product page, cart, or checkout. 
* Real-Time Decision. Pre-qualification is quick and easy. Let your customers learn 
about their purchase power in seconds without ever leaving your site.

Installation
------------

### Install using Composer

1. Navigate to your Magento 2 root folder

2. Install the Bread Checkout module

    ```bash
    composer require breadfinance/module-breadcheckout
    ```
3. Enable module
    ```bash
    bin/magento module:enable Bread/BreadCheckout
    bin/magento setup:upgrade
    bin/magento setup:di:compile
    bin/magento setup:static-content:deploy
    ```

### Install using Zip archive

1. Download Bread checkout module from Magento marketplace
   https://marketplace.magento.com/bread-module-breadcheckout.html

2. Unzip contents into `app/code/Bread/BreadCheckout` folder

3. Enable module
    ```bash
    bin/magento module:enable Bread/BreadCheckout
    bin/magento setup:upgrade
    bin/magento setup:di:compile
    bin/magento setup:static-content:deploy
    ```


## Usage instructions:

See documentation at https://docs.breadpayments.com/bread-classic/docs/magento-2
Contact your Bread representative for your merchant credentials.

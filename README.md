# beGateway Payment Module for Magento 2 CE

This is a Payment Module for Magento 2 Community Edition, that gives you the ability to process payments through payment service providers running on beGateway platform.

## Requirements

  * Magento 2 Community Edition 2.x (Tested up to __2.1.3__)
  * [beGateway PHP API library v2.7.x](https://github.com/beGateway/begateway-api-php) - (Integrated in Module)
  * PCI DSS certified server in order to use ```beGateway Direct```

*Note:* this module has been tested only with Magento 2 __Community Edition__, it may not work as intended with Magento 2 __Enterprise Edition__

## Installation (composer)
---------------------
  * Install __Composer__ - [Composer Download Instructions](https://getcomposer.org/doc/00-intro.md)

  * Install __beGateway Gateway__

    * Install Payment Module

        ```sh
        $ composer require begateway/magento2-payment-module
        ```

    * Enable Payment Module

        ```sh
        $ php bin/magento module:enable BeGateway_BeGateway
        ```

        ```sh
        $ php bin/magento setup:upgrade
        ```
    * Deploy Magento Static Content (__Execute If needed__)

        ```sh
        $ php bin/magento setup:static-content:deploy
        ```    

## Installation (manual)
---------------------

  * Upload the contents of the folder (excluding ```README.md```) to a new folder ```<root>/app/code/BeGateway/BeGateway/``` of your Magento 2 installation

  * Install beGateway PHP API Library

    ```sh
    $ composer require begateway/begateway-api-php
    ```

  * Enable Payment Module

    ```sh
    $ php bin/magento module:enable BeGateway_BeGateway --clear-static-content
    ```

    ```sh
    $ php bin/magento setup:upgrade
    ```

  * Deploy Magento Static Content (__Execute If needed__)

    ```sh
    $ php bin/magento setup:static-content:deploy
    ```   

## Configuration

  * Login inside the __Admin Panel__ and go to ```Stores``` -> ```Configuration``` -> ```Sales``` -> ```Payment Methods```
  * If the Payment Module Panel ```beGateway``` is not visible in the list of available Payment Methods,
  go to  ```System``` -> ```Cache Management``` and clear Magento Cache by clicking on ```Flush Magento Cache```
  * Go back to ```Payment Methods``` and click the button ```Configure``` under the payment method ```beGateway Checkout``` or ```beGateway Direct``` to expand the available settings
  * Set ```Enabled``` to ```Yes```, set the correct credentials, select your prefered transaction types and additional settings and click ```Save config```

## Configure Magento over secured HTTPS Connection

This configuration is needed for ```beGateway Direct``` Method to be usable.

Steps:

  * Ensure you have installed a valid SSL Certificate on your Web Server & you have configured your Virtual Host correctly.
  * Login to Magento 2 Admin Panel
  * Navigate to ```Stores``` -> ```Configuration``` -> ```General``` -> ```Web```
  * Expand Tab ```Base URLs (Secure)``` and set ```Use Secure URLs on Storefront``` and ```Use Secure URLs in Admin``` to **Yes**
  * Set your ```Secure Base URL``` and click ```Save Config```
  * It is recommended to add a **Rewrite Rule** from ```http``` to ```https``` or to configure a **Permanent Redirect** to ```https``` in your virtual host

## Test data

  If you setup the module with default values, you can use the test data
  to make a test payment:

  * Shop Id __361__
  * Shop Secret Key __b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d__
  * Checkout Domain __checkout.begateway.com__
  * Gateway Domain __demo-gateway.begateway.com__

### Test card details

  * Card number __4200000000000000__
  * Card name __John Doe__
  * Card expiry month __01__ to get a success payment
  * Card expiry month __10__ to get a failed payment
  * CVC __123__

# beGateway Payment Module for Magento 2 CE

[Русская версия](#Модуль-оплаты-begateway-для-magento-2-ce)

This is a Payment Module for Magento 2 Community Edition, that gives you the ability to process payments through payment service providers running on beGateway platform.

## Requirements

  * Magento 2 Community Edition 2.x (Tested up to 2.0.18 / 2.1.18 / 2.2.11)
  * [BeGateway PHP API library ](https://github.com/begateway/begateway-api-php) - (Integrated in Module)

*Note:* this module has been tested only with Magento 2 __Community Edition__, it may not work as intended with Magento 2 __Enterprise Edition__

## Installation (composer)

  * Install __Composer__ - [Composer Download Instructions](https://getcomposer.org/doc/00-intro.md)

  * Install Payment Module

    ```sh
    $ composer require begateway/magento2-payment-module 2.2.0
    ```

  * Enable Payment Module

    ```sh
    $ php bin/magento module:enable BeGateway_BeGateway
    ```

    ```sh
    $ php bin/magento setup:upgrade
    ```

  * If you are not running your Magento installation in compiled mode, skip to the next step. If you are running in compiled mode, complete this step:

    ```sh
    $ php bin/magento setup:di:compile
    ```

  * Deploy Magento Static Content (__Execute If needed__)

    ```sh
    $ php bin/magento setup:static-content:deploy en_GB en_US
    ```

    To see the full list of [ISO-636](http://www.loc.gov/standards/iso639-2/php/code_list.php) language codes, run:

    ```sh
    $ php magento info:language:list  
    ```

## Installation (manual)

  * [Download the Payment Module archive](https://github.com/beGateway/magento2-payment-module/archive/master.zip), unpack it and upload its contents to a new folder ```<root>/app/code/BeGateway/BeGateway/``` of your Magento 2 installation

  * Install beGateway PHP API Library

    ```sh
    $ composer require begateway/begateway-api-php 4.4.3
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
    $ php bin/magento setup:static-content:deploy en_GB en_US
    ```   

    To see the full list of [ISO-636](http://www.loc.gov/standards/iso639-2/php/code_list.php) language codes, run:

    ```sh
    $ php magento info:language:list  
    ```

## Configuration

  * Login inside the __Admin Panel__ and go to ```Stores``` -> ```Configuration``` -> ```Sales``` -> ```Payment Methods```
  * If the Payment Module Panel ```beGateway``` is not visible in the list of available Payment Methods,
  go to  ```System``` -> ```Cache Management``` and clear Magento Cache by clicking on ```Flush Magento Cache```
  * Go back to ```Payment Methods``` and click the button ```Configure``` under the payment method ```beGateway Checkout``` to expand the available settings
  * Set ```Enabled``` to ```Yes```, set the correct credentials, select your prefered transaction types and additional settings and click ```Save config```

## Test data

If you setup the module with default values, you can use the test data to make a test payment:

  * Shop Id ```361```
  * Shop Secret Key ```b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d```
  * Checkout Domain ```checkout.begateway.com```
  * Enable test mode ``Yes``

### Test card details

Use the following test card to make successful test payment:

  * Card number: `4200000000000000`
  * Name on card: `JOHN DOE`
  * Card expiry date: `01/30`
  * CVC: `123`

Use the following test card to make failed test payment:

  * Card number: `4005550000000019`
  * Name on card: `JOHN DOE`
  * Card expiry date: `01/30`
  * CVC: `123`

# Модуль оплаты beGateway для Magento 2 CE

Модуль оплаты для Magento 2 Community Edition, который даст вам возможность начать принимать платежи через провайдеров платежей, использующих платформу beGateway.

## Требования

  * Magento 2 Community Edition 2.x (тестировалось на версиях 2.0.18 / 2.1.18 / 2.2.11)
  * [BeGateway PHP API библиотека](https://github.com/beGateway/begateway-api-php) - (поставляется с модулем)

*Примечание:* этот модуль тестировался только с Magento 2 __Community Edition__ и может работать не стабильно с Magento 2 __Enterprise Edition__

## Установка (composer)

  * Установите __Composer__ - [инструкция по установке Composer](https://getcomposer.org/doc/00-intro.md)

  * Установите модуль оплаты

    ```sh
    $ composer require begateway/magento2-payment-module 2.2.0
    ```

  * Включите модуль оплаты

    ```sh
    $ php bin/magento module:enable BeGateway_BeGateway
    ```

    ```sh
    $ php bin/magento setup:upgrade
    ```

  * Пропустите этот шаг, если ваша версия Magento не запускается в режиме компиляции. В противном случае выполните эту команду:

    ```sh
    $ php bin/magento setup:di:compile
    ```

  * Создайте статичный контент Magento (__выполните если необходимо__)

    ```sh
    $ php bin/magento setup:static-content:deploy en_GB ru_RU
    ```    

    Чтобы получить полный список [ISO-636](http://www.loc.gov/standards/iso639-2/php/code_list.php) кодов языковых локалей, поддерживаемых Magento, выполните:

    ```sh
    $ php magento info:language:list  
    ```

## Установка (ручная)

  * [Скачайте архив модуля](https://github.com/beGateway/magento2-payment-module/archive/master.zip), распакуйте его и скопируйте его содержимое в новую директорию ```<root>/app/code/BeGateway/BeGateway/``` вашей Magento 2 инсталляции

  * Установите beGateway PHP API библиотеку

    ```sh
    $ composer require begateway/begateway-api-php 4.4.3
    ```

  * Включить модуль оплаты

    ```sh
    $ php bin/magento module:enable BeGateway_BeGateway --clear-static-content
    ```

    ```sh
    $ php bin/magento setup:upgrade
    ```
  * Пропустите этот шаг, если ваша версия Magento не запускается в режиме компиляции. В противном случае выполните эту команду:

    ```sh
    $ php bin/magento setup:di:compile
    ```

  * Создайте статичный контент Magento (__выполните если необходимо__)

    ```sh
    $ php bin/magento setup:static-content:deploy en_GB ru_RU
    ```   

## Настройка

  * Войдите в личный кабинет администратора и перейдите в ```Магазины``` -> ```Конфигурация``` -> ```Продажи``` -> ```Методы оплаты```
  * Если панель модуля оплаты ```beGateway``` не видна в списке доступных методов оплаты, то перейдите в ```Система``` -> ```Управление кэшем``` и очистите Magento кэш, нажав ```Очистить кэш Magento```
  * Вернитесь назад в ```Методы оплаты``` и нажмите кнопку ```Настроить``` под способом оплаты ```beGateway Checkout```, чтобы раскрыть доступные настройки
  * Выберите ```Да``` в выпадающем списке параметра ```Включено```, задайте данные вашего магазина, выберите тип операции, доступные способы оплаты и прочие настройки. Нажмите ```Сохранить конфигурацию```, чтобы их сохранить

## Тестовые данные

Вы можете использовать приведенные ниже тестовые данные, чтобы протестировать оплату.

  * Id магазина ```361```
  * Секретный ключ магазина ```b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d```
  * Домен страницы оплаты ```checkout.begateway.com```
  * Включить тестовый режим ``Да``

### Тестовая карта

Используйте следующие данные карты для успешного тестового платежа:

  * Номер карты: 4200000000000000
  * Имя на карте: JOHN DOE
  * Месяц срока действия карты: 01/30
  * CVC: 123

Используйте следующие данные карты для неуспешного тестового платежа:

  * Номер карты: 4005550000000019
  * Имя на карте: JOHN DOE
  * Месяц срока действия карты: 01/30
  * CVC: 123

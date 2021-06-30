# Development

Clone and install `dockergento` client tool https://github.com/begateway/magento2-dockergento

Clone and install Magento from Github https://github.com/magento/magento2 to the directory `magento2` and then run commands as follows

    export COMPOSE_HTTP_TIMEOUT=180
    git clone https://github.com/magento/magento2
    cd magento2

    # clone module repository
    mkdir -p app/code/BeGateway
    git clone https://github.com/begateway/magento2-payment-module app/code/BeGateway/BeGateway

    # feel free to switch to needed Magento version
    # e.g. switch to Magento 2.2
    git checkout 2.2

    dockergento setup

    # See Workaround to improve performance on Mac before to move forward

    dockergento start
    dockergento composer config repositories.0 composer https://repo.magento.com
    dockergento composer install

    dockergento magento setup:install \
      --db-host=db \
      --db-name=magento \
      --db-user=magento \
      --db-password=magento \
      --base-url=http://webhook.begateway.com:8443/ \
      --admin-firstname=John \
      --admin-lastname=Smith \
      --admin-email=admin@ecomcharge.com \
      --admin-user=admin \
      --backend-frontname=admin \
      --admin-password=password123 \
      --language=ru_RU \
      --currency=USD \
      --timezone=UTC \
      --use-rewrites=1

    dockergento magento deploy:mode:set developer
    dockergento composer require begateway/begateway-api-php 4.4.3

    # install sample data
    dockergento magento sampledata:deploy

    # install module
    dockergento magento module:enable BeGateway_BeGateway --clear-static-content
    dockergento magento setup:upgrade

    # copy sample data to host
    dockergento mirror-container .

## Workaround to improve performance on Mac.

1. Remove these lines on `docker-compose.dev.mac.yml`

    ```
        - ./app:/var/www/html/app:delegated
        - ./.git:/var/www/html/.git:delegated
        - ./.github:/var/www/html/.github:delegated
        - ./dev:/var/www/html/dev:delegated
        - ./generated:/var/www/html/generated:delegated
        - ./pub:/var/www/html/pub:delegated
        - ./var:/var/www/html/var:delegated
    ```

1. Remove these lines on `docker-compose.yml`

    ```
        - ../.composer:/var/www/html/var/composer_home:delegated
    ```

2. Sync `app` using `unison` container. Add this in `docker-compose.dev.mac.yml`

    ```
    unison:
      volumes:
        - ./app:/sync/app
    ```

3. Mirror not synced folders before executing composer the first time

    ```
    dockergento start
    dockergento mirror-host app dev generated pub var
    ```

4. Start unison watcher to sync module files between host and container.

    ```
    dockergento watch app/code/BeGateway
    ```

# Frontend login

Username: roni_cost@example.com
Password: roni_cost@example.com

# PHP requirements

| Magento version | PHP version |
| ----------------| ------------|
| 2.2 | 7.1 |
| 2.1 | 7.1 |
| 2.0 | 7.0 |

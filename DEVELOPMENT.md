# Development

## Magento installation command

Run inside the `magento_allinone` container (defined in docker-compose.yml) to install Magento.
Ensure the database credentials below match your docker-compose.yml configuration:

```bash
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition .
bin/magento setup:install \
--base-url=https://magentobegateway.loca.lt \
--db-host=db \
--db-name=magento \
--db-user=root \
--db-password=root \
--admin-firstname=Admin \
--admin-lastname=User \
--admin-email=admin@ecomcharge.com \
--admin-user=admin \
--admin-password=admin123 \
--language=en_US \
--currency=USD \
--timezone=UTC \
--use-rewrites=1 \
--search-engine=opensearch \
--opensearch-host=opensearch \
--opensearch-port=9200 \
--cache-backend=redis \
--cache-backend-redis-server=redis \
--cache-backend-redis-db=0
```

Switch Magento to a developer mode and install the sample data:

```bash
bin/magento deploy:mode:set developer
bin/magento sampledata:deploy
```
Disable 2FA (for development environment only; do not disable in production):

```bash
bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth
bin/magento module:disable Magento_TwoFactorAuth
bin/magento cache:flush
```

Install and enable the `BeGateway_BeGateway` module:

```bash
composer require begateway/begateway-api-php "~4.4.0"
bin/magento module:enable BeGateway_BeGateway --clear-static-content
bin/magento setup:upgrade
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

## Get admin login url

```bash
docker exec -it magento_allinone bin/magento info:adminuri
```

## install.sh Script

Run the script from the project root: `bash install.sh`

The following script automates the full installation of Magento 2.4.8-p3, sets up all required services (MySQL, OpenSearch, Redis, Nginx, PHP), installs Magento with your configuration, and enables your `BeGateway_BeGateway` module.

This script will:

* Build and start all Docker containers
* Wait until MySQL and OpenSearch are ready
* Install Magento inside `/src`
* Enable your `BeGateway_BeGateway` payment module
* Expose the site externally via [LocalTunnel](https://theboroer.github.io/localtunnel-www/) (https://magentobegateway.loca.lt). LocalTunnel creates a public URL that tunnels to your local Magento instance, which is required for beGateway webhook callbacks during development

## Change base url

Use instead of `magentobegateway.loca.lt` your tunnel host

```bash
docker exec -it magento_allinone bin/magento setup:store-config:set --base-url=https://magentobegateway.loca.lt/
docker exec -it magento_allinone bin/magento setup:store-config:set --base-url-secure=https://magentobegateway.loca.lt/
```

## Frontend login

Username: `roni_cost@example.com`
Password: `roni_cost3@example.com`

## PHP requirements

| Magento version | PHP version |
| ----------------| ------------|
| 2.3 | 7.3 |
| 2.4 | 7.4, 8.2 |

The Docker environment uses the PHP version bundled in the `docker/php-nginx` image. Verify with: `docker exec magento_allinone php -v`

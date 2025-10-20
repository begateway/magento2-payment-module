#!/bin/bash
set -e

echo "🔹 Step 1: Build and start Docker containers"
docker-compose up -d --build

echo "🔹 Step 2: Wait for MySQL and OpenSearch to become ready..."
until docker exec -i magento_db mysqladmin ping -h "localhost" --silent; do
    echo "Waiting for MySQL..."
    sleep 3
done

until curl -s http://localhost:9200 >/dev/null; do
    echo "Waiting for OpenSearch..."
    sleep 3
done

echo "🔹 Step 3: Install Magento into /var/www/html/src"
docker exec -i magento_php bash -c "
if [ -d /var/www/html/src ]; then
  echo 'Cleaning src directory before installation...'
  find /var/www/html/src -mindepth 1 -delete
fi

composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition /var/www/html/src --remove-vcs --no-interaction

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
"

echo "🔹 Step 4: Enable the BeGateway_BeGateway module"
docker exec -i magento_php bash -c "
bin/magento deploy:mode:set developer
bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth
bin/magento module:disable Magento_TwoFactorAuth
composer require begateway/begateway-api-php 4.4.3
bin/magento sampledata:deploy
bin/magento module:enable BeGateway_BeGateway || true
bin/magento setup:upgrade
bin/magento cache:flush
"

echo "🔹 Step 5: Expose Magento via LocalTunnel"
echo "Visit your Magento store at: https://magentobegateway.loca.lt"
echo "If LocalTunnel is not installed, run: npm install -g localtunnel"
lt --port 8080 --subdomain magentobegateway

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BeGateway payment module for Magento 2 Community Edition (`BeGateway_BeGateway`). Integrates with the beGateway payment platform to process credit card, Halva, and ERIP payments. Uses the `begateway/begateway-api-php` SDK (v4.4.x) for API communication.

## Development Environment

Docker-based setup with `docker-compose.yml` providing Magento 2 + MySQL 8.0 + OpenSearch + Redis:

```bash
# Full setup (builds containers, installs Magento, enables module)
./install.sh

# Run commands inside the Magento container
docker exec -it magento_allinone <command>

# Enable the module manually
docker exec -it magento_allinone bin/magento module:enable BeGateway_BeGateway --clear-static-content
docker exec -it magento_allinone bin/magento setup:upgrade
docker exec -it magento_allinone bin/magento cache:flush

# Get admin panel URL
docker exec -it magento_allinone bin/magento info:adminuri
```

The module source is volume-mounted into the container at `/var/www/html/app/code/BeGateway/BeGateway`, so local edits are reflected immediately.

## Architecture

**Namespace:** `BeGateway\BeGateway` (PSR-4 autoloaded from repo root)

**Payment method code:** `begateway_checkout` -- a single payment method providing a redirect-based checkout flow.

### Key Components

- **`Model/Method/Checkout.php`** -- The payment method model. Extends `Magento\Payment\Model\Method\AbstractMethod`. Handles order placement (creates a payment token via `BeGateway\GetPaymentToken`), capture, refund, void, and availability checks. Uses the `OnlinePaymentMethod` trait for shared gateway operations.

- **`Model/Traits/OnlinePaymentMethod.php`** -- Shared trait with `processReferenceTransaction()` for capture/refund/void operations against the beGateway API. Instantiates `\BeGateway\{Type}Operation` classes dynamically.

- **`Model/Config.php`** -- Payment configuration reader implementing `Magento\Payment\Model\Method\ConfigInterface`. Reads settings from `payment/begateway_checkout/*` scope config paths. `initGatewayClient()` sets static properties on `\BeGateway\Settings`.

- **`Helper/Data.php`** -- Central helper with URL building, transaction lookups, order state management, currency validation, and gateway response parsing. Constants define transaction types (`AUTHORIZE`, `PAYMENT`, `CAPTURE`, `VOID`, `REFUND`) and payment methods (`CREDIT_CARD`, `CREDIT_CARD_HALVA`, `ERIP`).

- **`Model/Ipn/BeGatewayIpn.php`** -- IPN (webhook) handler. Processes async payment notifications, updates transaction info, and registers authorization/capture notifications on the payment object.

- **`Controller/Checkout/Redirect.php`** -- Handles customer return from payment gateway (success/failure/cancel actions).

### Payment Flow

1. Customer places order -> `Checkout::order()` creates a `GetPaymentToken` request
2. Customer is redirected to beGateway checkout page (URL stored in session)
3. After payment, customer returns via `Controller/Checkout/Redirect`
4. beGateway sends async IPN notification to `Controller/Ipn/Index` -> `BeGatewayIpn::processNotification()`
5. Admin can capture/refund/void from Magento admin panel (calls back to beGateway API)

### Configuration (XML)

- `etc/config.xml` -- Default values (shop_id, domains, enabled payment methods)
- `etc/adminhtml/system.xml` + `etc/adminhtml/system/method_checkout.xml` -- Admin panel config fields
- `etc/module.xml` -- Module declaration with dependencies (Sales, Quote, Checkout, Payment, Directory, Config)
- `etc/frontend/routes.xml` -- Frontend route `begateway`
- `etc/frontend/di.xml` -- DI preferences
- `etc/events.xml` -- Event observers

### Translations

`i18n/` directory contains translation CSV files.

## Testing

No automated test suite exists. Test manually using the Docker environment.

### Test Credentials

- Shop Id: `361`
- Shop Secret Key: `b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d`
- Checkout Domain: `checkout.begateway.com`
- Gateway Domain: `demo-gateway.begateway.com`
- Test mode: enabled
- Successful test card: `4200000000000000`, exp `01/30`, CVC `123`
- Failed test card: `4005550000000019`, exp `01/30`, CVC `123`

### E2E Test Procedure

Prerequisites: Docker running, `lt` (localtunnel) installed (`npm install -g localtunnel`).

**1. Start containers and install Magento (skip if already running):**

```bash
docker compose up -d
# If fresh install needed (empty DB):
docker exec magento_allinone rm -f /var/www/html/app/etc/env.php
docker exec magento_db mysql -uroot -proot -e "DROP DATABASE IF EXISTS magento; CREATE DATABASE magento;"
# Fix OpenSearch disk threshold if blocked:
curl -s -X PUT "http://localhost:9200/_cluster/settings" \
  -H 'Content-Type: application/json' \
  -d '{"transient":{"cluster.routing.allocation.disk.threshold_enabled":false}}'
# Install Magento:
docker exec magento_allinone bin/magento setup:install \
  --cleanup-database --base-url=http://localhost:8080 \
  --db-host=db --db-name=magento --db-user=root --db-password=root \
  --admin-firstname=Admin --admin-lastname=User --admin-email=admin@ecomcharge.com \
  --admin-user=admin --admin-password=admin123 \
  --language=en_US --currency=USD --timezone=UTC --use-rewrites=1 \
  --search-engine=opensearch --opensearch-host=opensearch --opensearch-port=9200 \
  --cache-backend=redis --cache-backend-redis-server=redis --cache-backend-redis-db=0
# Disable 2FA and run setup:upgrade:
docker exec magento_allinone bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth Magento_TwoFactorAuth
docker exec magento_allinone bin/magento setup:upgrade
```

**2. Enable payment method and start tunnel:**

```bash
docker exec magento_allinone bin/magento config:set payment/begateway_checkout/active 1
# Start localtunnel (keep running in background):
lt --port 8080 --subdomain magentobegateway &
# Update base URL to tunnel:
docker exec magento_allinone bin/magento setup:store-config:set --base-url=https://magentobegateway.loca.lt/
docker exec magento_allinone bin/magento setup:store-config:set --base-url-secure=https://magentobegateway.loca.lt/
docker exec magento_redis redis-cli FLUSHALL
```

**3. Place order via REST API:**

```bash
BASE="https://magentobegateway.loca.lt"

# Create guest cart and add a sample product
CART_ID=$(curl -s -X POST "$BASE/rest/V1/guest-carts" -H 'Content-Type: application/json' | tr -d '"')

curl -s -X POST "$BASE/rest/V1/guest-carts/$CART_ID/items" \
  -H 'Content-Type: application/json' \
  -d "{\"cartItem\":{\"sku\":\"24-MB01\",\"qty\":1,\"quote_id\":\"$CART_ID\"}}"

# Set shipping info
curl -s -X POST "$BASE/rest/V1/guest-carts/$CART_ID/shipping-information" \
  -H 'Content-Type: application/json' \
  -d '{"addressInformation":{"shipping_address":{"firstname":"John","lastname":"Doe","street":["123 Main St"],"city":"New York","region_id":43,"region_code":"NY","country_id":"US","postcode":"10001","telephone":"5551234567","email":"john@example.com"},"billing_address":{"firstname":"John","lastname":"Doe","street":["123 Main St"],"city":"New York","region_id":43,"region_code":"NY","country_id":"US","postcode":"10001","telephone":"5551234567","email":"john@example.com"},"shipping_carrier_code":"flatrate","shipping_method_code":"flatrate"}}'

# Place order (returns order ID)
ORDER_ID=$(curl -s -X POST "$BASE/rest/V1/guest-carts/$CART_ID/payment-information" \
  -H 'Content-Type: application/json' \
  -d '{"email":"john@example.com","paymentMethod":{"method":"begateway_checkout"},"billingAddress":{"firstname":"John","lastname":"Doe","street":["123 Main St"],"city":"New York","region_id":43,"region_code":"NY","country_id":"US","postcode":"10001","telephone":"5551234567","email":"john@example.com"}}')
echo "Order ID: $ORDER_ID"
```

**4. Get tracking_id and pay via beGateway gateway API:**

```bash
# Get tracking_id from beGateway using the payment token
TOKEN=$(docker exec magento_db mysql -uroot -proot magento -N -e \
  "SELECT txn_id FROM sales_payment_transaction WHERE order_id = $ORDER_ID LIMIT 1;" 2>/dev/null)

TRACKING_ID=$(curl -s "https://checkout.begateway.com/ctp/api/checkouts/$TOKEN" \
  -u "361:b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d" \
  | python3 -c "import sys,json;print(json.load(sys.stdin)['checkout']['order']['tracking_id'])")

# Submit payment with test card (triggers webhook to tunnel)
curl -s -X POST "https://demo-gateway.begateway.com/transactions/payments" \
  -H 'Content-Type: application/json' \
  -u "361:b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d" \
  -d "{\"request\":{\"amount\":3900,\"currency\":\"USD\",\"description\":\"Order # $ORDER_ID payment\",\"tracking_id\":\"$TRACKING_ID\",\"notification_url\":\"https://magentobegateway.loca.lt/begateway/ipn/index/\",\"test\":true,\"credit_card\":{\"number\":\"4200000000000000\",\"holder\":\"JOHN DOE\",\"exp_month\":\"01\",\"exp_year\":\"2030\",\"verification_value\":\"123\"}}}"
```

**5. Verify order status changed:**

```bash
# Should show status=processing, state=processing (was payment_review)
docker exec magento_db mysql -uroot -proot magento -e \
  "SELECT entity_id, increment_id, status, state FROM sales_order WHERE entity_id = $ORDER_ID;" 2>/dev/null

# Check transaction was recorded
docker exec magento_db mysql -uroot -proot magento -e \
  "SELECT txn_id, txn_type, is_closed FROM sales_payment_transaction WHERE order_id = $ORDER_ID;" 2>/dev/null

# Check status history shows capture notification
docker exec magento_db mysql -uroot -proot magento -e \
  "SELECT comment FROM sales_order_status_history WHERE parent_id = $ORDER_ID;" 2>/dev/null
```

**Expected results:**
- Order status transitions from `payment_review` to `processing`
- Two transactions: `order` (from token) + `capture` (from payment)
- Status history shows "Registered notification about captured amount"

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

No automated test suite exists in this repository. Test manually using the Docker environment with test credentials:

- Shop Id: `361`
- Shop Secret Key: `b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d`
- Checkout Domain: `checkout.begateway.com`
- Gateway Domain: `demo-gateway.begateway.com`
- Test mode: enabled
- Successful test card: `4200000000000000`, exp `01/30`, CVC `123`
- Failed test card: `4005550000000019`, exp `01/30`, CVC `123`

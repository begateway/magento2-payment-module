<?php
/*
 * Copyright (C) 2017 beGateway
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      beGateway
 * @copyright   2017 beGateway
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace BeGateway\BeGateway\Model;

use Magento\Store\Model\ScopeInterface;

class Config implements \Magento\Payment\Model\Method\ConfigInterface
{
    /**
     * Current payment method code
     *
     * @var string
     */
    protected $_methodCode;
    /**
     * Current store id
     *
     * @var int
     */
    protected $_storeId;
    /**
     * @var string
     */
    protected $pathPattern;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Get an Instance of the Magento ScopeConfig
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected function getScopeConfig()
    {
        return $this->_scopeConfig;
    }

    /**
     * Set the the Credentials and Environment to the Gateway Client
     * @return void
     */
    public function initGatewayClient()
    {
        \BeGateway\Settings::$shopId  = $this->getShopId();
        \BeGateway\Settings::$shopKey = $this->getShopKey();
        \BeGateway\Settings::$gatewayBase = 'https://' . $this->getDomainGateway();
        \BeGateway\Settings::$checkoutBase = 'https://' . $this->getDomainCheckout();
    }

    /**
     * Payment method instance code getter
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_methodCode;
    }

    /**
     * Store ID setter
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = (int)$storeId;
        return $this;
    }

    /**
     * Returns payment configuration value
     *
     * @param string $key
     * @param null $storeId
     * @return null|string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValue($key, $storeId = null)
    {
        switch ($key) {
            default:
                $underscored = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $key));
                $path = $this->getSpecificConfigPath($underscored);
                if ($path !== null) {
                    $value = $this->getScopeConfig()->getValue(
                        $path,
                        ScopeInterface::SCOPE_STORE,
                        $this->_storeId
                    );
                    return $value;
                }
        }
        return null;
    }

    /**
     * Sets method code
     *
     * @param string $methodCode
     * @return void
     */
    public function setMethodCode($methodCode)
    {
        $this->_methodCode = $methodCode;
    }

    /**
     * Sets path pattern
     *
     * @param string $pathPattern
     * @return void
     */
    public function setPathPattern($pathPattern)
    {
        $this->pathPattern = $pathPattern;
    }

    /**
     * Map any supported payment method into a config path by specified field name
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function getSpecificConfigPath($fieldName)
    {
        if ($this->pathPattern) {
            return sprintf($this->pathPattern, $this->_methodCode, $fieldName);
        }

        return "payment/{$this->_methodCode}/{$fieldName}";
    }

    /**
     * Check whether Gateway API credentials are available for this method
     *
     * @param null $methodCode
     *
     * @return bool
     */
    public function isApiAvailable($methodCode = null)
    {
        return !empty($this->getShopId()) &&
               !empty($this->getShopKey()) &&
               !empty($this->getTransactionTypes());
    }

    /**
     * Check whether method available for checkout or not
     *
     * @param null $methodCode
     *
     * @return bool
     */
    public function isMethodAvailable($methodCode = null)
    {
        return $this->isMethodActive($methodCode) &&
               $this->isApiAvailable($methodCode);
    }

    /**
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @param string $methodCode Method code
     * @return bool
     */
    public function isMethodActive($methodCode = null)
    {
        $methodCode = $methodCode?: $this->_methodCode;

        return $this->isFlagChecked($methodCode, 'active');
    }

    /**
     * Check if Method Bool Setting Checked
     * @param string|null $methodCode
     * @param string $name
     * @return bool
     */
    public function isFlagChecked($methodCode, $name)
    {
        $methodCode = $methodCode?: $this->_methodCode;

        return $this->getScopeConfig()->isSetFlag(
            "payment/{$methodCode}/{$name}",
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    /**
     * Get Method Shop Id Admin Setting
     * @return null|string
     */
    public function getShopId()
    {
        return $this->getValue('shop_id');
    }

    /**
     * Get Method Shop Key Admin Setting
     * @return null|string
     */
    public function getShopKey()
    {
        return $this->getValue('shop_key');
    }

    /**
     * Get Method Domain Gateway Admin Setting
     * @return null|string
     */
    public function getDomainGateway()
    {
        return $this->getValue('domain_gateway');
    }

    /**
     * Get Method Domain Checkout Admin Setting
     * @return null|string
     */
    public function getDomainCheckout()
    {
        return $this->getValue('domain_checkout');
    }

    /**
     * Get Method Checkout Page Title
     * @return null|string
     */
    public function getCheckoutTitle()
    {
        return $this->getValue('title');
    }

    /**
     * Get Method Available Transaction Types
     * @return array
     */
    public function getTransactionTypes()
    {
        return
            array_map(
                'trim',
                explode(
                    ',',
                    $this->getValue('transaction_types')
                )
            );
    }

    /**
     * Get Method New Order Status
     * @return null|string
     */
    public function getOrderStatusNew()
    {
        return $this->getValue('order_status');
    }

    /**
     * Get if specific currencies are allowed
     * (not all global allowed currencies)
     * @return array
     */
    public function getAreAllowedSpecificCurrencies()
    {
        return $this->isFlagChecked($this->_methodCode, 'allow_specific_currency');
    }

    /**
     * Get Method Allowed Currency array
     * @return array
     */
    public function getAllowedCurrencies()
    {
        return array_map(
            'trim',
            explode(
                ',',
                $this->getValue('specific_currencies')
            )
        );
    }

    /**
     * Get Test Mode state
     * @return null|string
     */
    public function getTestMode()
    {
        return $this->getValue('test_mode');
    }
}

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

namespace BeGateway\BeGateway\Helper;

/**
 * Helper Class for all Payment Methods
 *
 * Class Data
 * @package BeGateway\BeGateway\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SECURE_TRANSACTION_TYPE_SUFFIX = '3-D';

    const ADDITIONAL_INFO_KEY_STATUS           = 'status';
    const ADDITIONAL_INFO_KEY_TRANSACTION_TYPE = 'type';
    const ADDITIONAL_INFO_KEY_REDIRECT_URL     = 'redirect_url';
    const ADDITIONAL_INFO_KEY_PAYMENT_METHOD   = 'payment_method_type';
    const ADDITIONAL_INFO_KEY_TEST             = 'test';

    const AUTHORIZE                            = 'authorization';
    const PAYMENT                              = 'payment';
    const CAPTURE                              = 'capture';
    const VOID                                 = 'void';
    const REFUND                               = 'refund';

    const CREDIT_CARD                          = 'credit_card';
    const CREDIT_CARD_HALVA                    = 'halva';
    const ERIP                                 = 'erip';

    const PENDING                              = 'pending';
    const INCOMPLETE                           = 'incomplete';
    const SUCCESSFUL                           = 'successful';
    const FAILED                               = 'failed';
    const ERROR                                = 'error';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentData;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_configFactory;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager,
     * @param \BeGateway\BeGateway\Model\ConfigFactory $configFactory,
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \BeGateway\BeGateway\Model\ConfigFactory $configFactory,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        $this->_objectManager = $objectManager;
        $this->_paymentData   = $paymentData;
        $this->_storeManager  = $storeManager;
        $this->_configFactory = $configFactory;
        $this->_localeResolver = $localeResolver;

        $this->_scopeConfig   = $context->getScopeConfig();

        parent::__construct($context);
    }

    /**
     * Creates an Instance of the Helper
     * @param  \Magento\Framework\ObjectManagerInterface $objectManager
     * @return \BeGateway\BeGateway\Helper\Data
     */
    public static function getInstance($objectManager)
    {
        return $objectManager->create(get_class());
    }

    /**
     * Get an Instance of the Magento Object Manager
     * @return \Magento\Framework\ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return $this->_objectManager;
    }

    /**
     * Get an Instance of the Magento Store Manager
     * @return \Magento\Store\Model\StoreManagerInterface
     */
    protected function getStoreManager()
    {
        return $this->_storeManager;
    }

    /**
     * Get an Instance of the Config Factory Class
     * @return \BeGateway\BeGateway\Model\ConfigFactory
     */
    protected function getConfigFactory()
    {
        return $this->_configFactory;
    }

    /**
     * Get an Instance of the Magento UrlBuilder
     * @return \Magento\Framework\UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->_urlBuilder;
    }

    /**
     * Get an Instance of the Magento Scope Config
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected function getScopeConfig()
    {
        return $this->_scopeConfig;
    }

    /**
     * Get an Instance of the Magento Core Locale Object
     * @return \Magento\Framework\Locale\ResolverInterface
     */
    protected function getLocaleResolver()
    {
        return $this->_localeResolver;
    }

    /**
     * Build URL for store
     *
     * @param string $moduleCode
     * @param string $controller
     * @param string|null $queryParams
     * @param bool|null $secure
     * @param int|null $storeId
     * @return string
     */
    public function getUrl($moduleCode, $controller, $queryParams = null, $secure = null, $storeId = null)
    {
        list($route, $module) = explode('_', $moduleCode);

        $path = sprintf("%s/%s/%s", $route, $module, $controller);

        $store = $this->getStoreManager()->getStore($storeId);
        $params = [
            "_store" => $store,
            "_secure" =>
                ($secure === null
                    ? $this->isStoreSecure($storeId)
                    : $secure
                )
        ];

        if (isset($queryParams) && is_array($queryParams)) {
            foreach ($queryParams as $queryKey => $queryValue) {
                $params[$queryKey] = $queryValue;
            }
        }

        return $this->getUrlBuilder()->getUrl(
            $path,
            $params
        );
    }

    /**
     * Construct Module Notification Url
     * @param string $moduleCode
     * @param bool|null $secure
     * @param int|null $storeId
     * @return string
     * @SuppressWarning(PHPMD.UnusedLocalVariable)
     */
    public function getNotificationUrl($moduleCode, $secure = null, $storeId = null)
    {
        $store = $this->getStoreManager()->getStore($storeId);
        $params = [
            "_store" => $store,
            "_secure" =>
                ($secure === null
                    ? $this->isStoreSecure($storeId)
                    : $secure
                )
        ];

        return $this->getUrlBuilder()->getUrl(
            "begateway/ipn",
            $params
        );
    }

    /**
     * Build Return Url from Payment Gateway
     * @param string $moduleCode
     * @param string $returnAction
     * @return string
     */
    public function getReturnUrl($moduleCode, $returnAction)
    {
        return $this->getUrl(
            $moduleCode,
            "redirect",
            [
                "action" => $returnAction
            ]
        );
    }

    /**
     * Generates a unique hash, used for the transaction id
     * @return string
     */
    protected function uniqHash()
    {
        return md5(uniqid(microtime().mt_rand(), true));
    }

    /**
     * Builds a transaction id
     * @param int|null $orderId
     * @return string
     */
    public function genTransactionId($orderId = null)
    {
        if (empty($orderId)) {
            return $this->uniqHash();
        }

        return sprintf(
            "%s_%s",
            strval($orderId),
            $this->uniqHash()
        );
    }

    /**
     * Get Transaction Additional Parameter Value
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @param string $paramName
     * @return null|string
     */
    public function getTransactionAdditionalInfoValue($transaction, $paramName)
    {
        $transactionInformation = $transaction->getAdditionalInformation(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
        );

        if (is_array($transactionInformation) && isset($transactionInformation[$paramName])) {
            return $transactionInformation[$paramName];
        }

        return null;
    }

    /**
     * Get Transaction Additional Parameter Value
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $paramName
     * @return null|string
     */
    public function getPaymentAdditionalInfoValue(
        \Magento\Payment\Model\InfoInterface $payment,
        $paramName
    ) {
        $paymentAdditionalInfo = $payment->getTransactionAdditionalInfo();

        $rawDetailsKey = \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS;

        if (!array_key_exists($rawDetailsKey, $paymentAdditionalInfo)) {
            return null;
        }

        if (!array_key_exists($paramName, $paymentAdditionalInfo[$rawDetailsKey])) {
            return null;
        }

        return $paymentAdditionalInfo[$rawDetailsKey][$paramName];
    }

    /**
     * Get Transaction Type
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @return null|string
     */
    public function getTransactionTypeByTransaction($transaction)
    {
        return $this->getTransactionAdditionalInfoValue(
            $transaction,
            self::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE
        );
    }

    /**
     * Get Transaction Type
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @return null|string
     */
    public function getPaymentMethodByTransaction($transaction)
    {
        return $this->getTransactionAdditionalInfoValue(
            $transaction,
            self::ADDITIONAL_INFO_KEY_PAYMENT_METHOD
        );
    }

    /**Get an Instance of a Method Object using the Method Code
     * @param string $methodCode
     * @return \BeGateway\BeGateway\Model\Config
     */
    public function getMethodConfig($methodCode)
    {
        $parameters = [
            'params' => [
                $methodCode,
                $this->getStoreManager()->getStore()->getId()
            ]
        ];

        $config = $this->getConfigFactory()->create(
            $parameters
        );

        $config->setMethodCode($methodCode);

        return $config;
    }

    /**
     * Hides generated Exception and raises WebApiException in order to
     * display the message to user
     * @param \Exception $e
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function maskException(\Exception $e)
    {
        $this->throwWebApiException(
            $e->getMessage(),
            $e->getCode()
        );
    }

    /**
     * Creates a WebApiException from Message or Phrase
     *
     * @param \Magento\Framework\Phrase|string $phrase
     * @param int $httpCode
     * @return \Magento\Framework\Webapi\Exception
     */
    public function createWebApiException(
        $phrase,
        $httpCode = \Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR
    ) {
        if (is_string($phrase)) {
            $phrase = new \Magento\Framework\Phrase($phrase);
        }

        return new \Magento\Framework\Webapi\Exception(
            $phrase,
            0,
            $httpCode,
            [],
            '',
            null,
            null
        );
    }

    /**
     * Generates WebApiException from Exception Text
     * @param \Magento\Framework\Phrase|string $errorMessage
     * @param int $errorCode
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function throwWebApiException($errorMessage, $errorCode = 0)
    {
        $webApiException = $this->createWebApiException($errorMessage, $errorCode);

        throw $webApiException;
    }

    /**
     * Find Payment Transaction per Field Value
     * @param string $fieldValue
     * @param string $fieldName
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function getPaymentTransaction($fieldValue, $fieldName = 'txn_id')
    {
        if (!isset($fieldValue) || empty($fieldValue)) {
            return null;
        }

        $transaction = $this->getObjectManager()->create(
            "\\Magento\\Sales\\Model\\Order\\Payment\\Transaction"
        )->load(
            $fieldValue,
            $fieldName
        );

        return ($transaction->getId() ? $transaction : null);
    }

    /**
     * Generates an array from Payment Gateway Response Object
     * @param \stdClass $response
     * @return array
     */
    public function getArrayFromGatewayResponse($response)
    {
        try {
          $arResponse = $response->getResponseArray();

          if (isset($arResponse['transaction'])) {

            $arResponse = $arResponse['transaction'];

            if (isset($arResponse['credit_card'])) {
              $arResponse['credit_card'] =
                $arResponse['credit_card']['first_1'] . ' xxxx ' .
                $arResponse['credit_card']['last_4'];

              if (isset($arResponse['credit_card']['sub_brand']))
                $arResponse['credit_card_sub_brand'] =
                  $arResponse['credit_card']['sub_brand'];

              if (isset($arResponse['credit_card']['product']))
                $arResponse['credit_card_product'] =
                  $arResponse['credit_card']['product'];
            }

            if (isset($arResponse['type'])) {
              $arResponse = array_merge($arResponse, $arResponse[$arResponse['type']]);
            }
          }

          if (isset($arResponse['checkout'])) {
            $arResponse = $arResponse['checkout'];
          }

          foreach ($arResponse as $p => $v) {
            if (!is_array($v))
              $transaction_details[$p] = (string)$v;
          }

        } catch (Exception $e) {
          $transaction_details = array();
        }
        return $transaction_details;
    }

    /**
     * Checks if the store is secure
     * @param $storeId
     * @return bool
     */
    public function isStoreSecure($storeId = null)
    {
        $store = $this->getStoreManager()->getStore($storeId);
        return $store->isCurrentlySecure();
    }

    /**
     * Sets the AdditionalInfo to the Payment transaction
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \stdClass $responseObject
     * @return void
     */
    public function setPaymentTransactionAdditionalInfo($payment, $responseObject)
    {
        $payment->setTransactionAdditionalInfo(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $this->getArrayFromGatewayResponse(
                $responseObject
            )
        );
    }

    /**
     * Updates a payment transaction additional info
     * @param string $transactionId
     * @param \stdClass $responseObject
     * @param bool $shouldCloseTransaction
     * @return bool
     */
    public function updateTransactionAdditionalInfo($transactionId, $responseObject, $shouldCloseTransaction = false)
    {
        $transaction = $this->getPaymentTransaction($transactionId);

        if (isset($transaction)) {
            $this->setTransactionAdditionalInfo(
                $transaction,
                $responseObject
            );

            if ($shouldCloseTransaction) {
                $transaction->setIsClosed(true);
            }

            $transaction->save();

            return true;
        }

        return false;
    }

    /**
     * Set transaction additional information
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @param $responseObject
     */
    public function setTransactionAdditionalInfo($transaction, $responseObject)
    {
        $transaction
            ->setAdditionalInformation(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $this->getArrayFromGatewayResponse(
                    $responseObject
                )
            );
    }

    /**
     * Update Order Status and State
     * @param \Magento\Sales\Model\Order $order
     * @param string $state
     */
    public function setOrderStatusByState($order, $state)
    {
        $order
            ->setState($state)
            ->setStatus(
                $order->getConfig()->getStateDefaultStatus(
                    $state
                )
            );
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $status
     * @param string $message
     */
    public function setOrderState($order, $status, $message = '')
    {
        switch ($status) {
            case self::SUCCESSFUL:
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_PROCESSING
                );
                $order->save();
                break;

            case self::INCOMPLETE:
            case self::PENDING:
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
                );
                $order->save();
                break;

            case self::FAILED:
            case self::ERROR:
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $invoice->cancel();
                }
                $order
                    ->registerCancellation($message)
                    ->setCustomerNoteNotify(true)
                    ->save();
                break;
            default:
                $order->save();
                break;
        }
    }

    /**
     * Build Description Information for the Transaction
     * @param \Magento\Sales\Model\Order $order
     * @param string $lineSeparator
     * @return string
     */
    public function buildOrderDescriptionText($order, $lineSeparator = PHP_EOL)
    {
        $orderDescriptionText = "";

        $orderItems = $order->getItems();

        foreach ($orderItems as $orderItem) {
            $separator = ($orderItem == end($orderItems)) ? '' : $lineSeparator;

            $orderDescriptionText .=
                $orderItem->getQtyOrdered() .
                ' x ' .
                $orderItem->getName() .
                $separator;
        }

        return $orderDescriptionText;
    }

    /**
     * Generates Usage Text (needed to create Transaction)
     * @return \Magento\Framework\Phrase
     */
    public function buildOrderUsage()
    {
        return __("Magento 2 Transaction");
    }

    /**
     * Search for a transaction by transaction types
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $transactionTypes
     * @return \Magento\Sales\Model\Order\Payment\Transaction
     */
    public function lookUpPaymentTransaction($payment, array $transactionTypes)
    {
        $transaction = null;

        $lastPaymentTransactionId = $payment->getLastTransId();

        $transaction = $this->getPaymentTransaction(
            $lastPaymentTransactionId
        );

        while (isset($transaction)) {
            if (in_array($transaction->getTxnType(), $transactionTypes)) {
                break;
            }
            $transaction = $this->getPaymentTransaction(
                $transaction->getParentId(),
                'transaction_id'
            );
        }

        return $transaction;
    }

    /**
     * Find Authorization Payment Transaction
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $transactionTypes
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function lookUpAuthorizationTransaction($payment, $transactionTypes = [
            \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH
        ]
    )
    {
        return $this->lookUpPaymentTransaction(
            $payment,
            $transactionTypes
        );
    }

    /**
     * Find Capture Payment Transaction
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $transactionTypes
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function lookUpCaptureTransaction($payment, $transactionTypes = [
            \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE
        ]
    )
    {
        return $this->lookUpPaymentTransaction(
            $payment,
            $transactionTypes
        );
    }

    /**
     * Find Void Payment Transaction Reference (Auth or Capture)
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $transactionTypes
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function lookUpVoidReferenceTransaction($payment, $transactionTypes = [
        \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
        \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH
        ]
    )
    {
        return $this->lookUpPaymentTransaction(
            $payment,
            $transactionTypes
        );
    }

    /**
     * Get an array of all global allowed currency codes
     * @return array
     */
    public function getGlobalAllowedCurrencyCodes()
    {
        $allowedCurrencyCodes = $this->getScopeConfig()->getValue(
            \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_ALLOW
        );

        return array_map(
            'trim',
            explode(
                ',',
                $allowedCurrencyCodes
            )
        );
    }

    /**
     * Builds Select Options for the Allowed Currencies in the Admin Zone
     * @param array $availableCurrenciesOptions
     * @return array
     */
    public function getGlobalAllowedCurrenciesOptions(array $availableCurrenciesOptions)
    {
        $allowedCurrenciesOptions = [];

        $allowedGlobalCurrencyCodes = $this->getGlobalAllowedCurrencyCodes();

        foreach ($availableCurrenciesOptions as $availableCurrencyOptions) {
            if (in_array($availableCurrencyOptions['value'], $allowedGlobalCurrencyCodes)) {
                $allowedCurrenciesOptions[] = $availableCurrencyOptions;
            }
        }
        return $allowedCurrenciesOptions;
    }

    /**
     * Filter Module allowed Currencies with the global allowed currencies
     * @param array $allowedLocalCurrencies
     * @return array
     */
    public function getFilteredLocalAllowedCurrencies(array $allowedLocalCurrencies)
    {
        $result = [];
        $allowedGlobalCurrencyCodes = $this->getGlobalAllowedCurrencyCodes();

        foreach ($allowedLocalCurrencies as $allowedLocalCurrency) {
            if (in_array($allowedLocalCurrency, $allowedGlobalCurrencyCodes)) {
                $result[] = $allowedLocalCurrency;
            }
        }

        return $result;
    }

    /**
     * Get Magento Core Locale
     * @param string $default
     * @return string
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function getLocale($default = 'en')
    {
        $languageCode = strtolower(
            $this->getLocaleResolver()->getLocale()
        );

        $languageCode = substr($languageCode, 0, 2);

        return $languageCode;
    }

    /**
     * Get is allowed to refund transaction
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @return bool
     */
    public function canRefundTransaction($transaction)
    {
        $refundableTransactions = [
            self::CAPTURE,
            self::PAYMENT
        ];

        $transactionType = $this->getTransactionTypeByTransaction(
            $transaction
        );

        $paymentMethod = $this->getPaymentMethodByTransaction($transaction);

        return (
          !empty($transactionType) &&
          in_array($transactionType, $refundableTransactions) &&
          $paymentMethod == self::CREDIT_CARD
        );
    }

    /**
     * Check is Payment Method available for currency
     * @param string $methodCode
     * @param string $currencyCode
     * @return bool
     */
    public function isCurrencyAllowed($methodCode, $currencyCode)
    {
        $methodConfig = $this->getMethodConfig($methodCode);

        if (!$methodConfig->getAreAllowedSpecificCurrencies()) {
            $allowedMethodCurrencies = $this->getGlobalAllowedCurrencyCodes();
        } else {
            $allowedMethodCurrencies =
                $this->getFilteredLocalAllowedCurrencies(
                    $methodConfig->getAllowedCurrencies()
                );
        }

        return in_array($currencyCode, $allowedMethodCurrencies);
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public function getStringEndsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    /**
     * @param string $transactionType
     * @return bool
     */
    public function getIsTransaction3dSecure($transactionType)
    {
        return
            $this->getStringEndsWith(
                strtoupper($transactionType),
                self::SECURE_TRANSACTION_TYPE_SUFFIX
            );
    }

    /**
     * Retrieves the complete error message from gateway
     *
     * @param \stdClass $response
     * @return string
     */
    public function getErrorMessageFromGatewayResponse($response)
    {
        return
            (!empty($response->getMessage()))
                ? "{$response->getMessage()}"
                : __('An error has occurred while processing your request to the gateway');
    }
}

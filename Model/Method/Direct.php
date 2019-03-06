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

namespace BeGateway\BeGateway\Model\Method;

use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Direct Payment Method Model Class
 * Class Direct
 * @package BeGateway\BeGateway\Model\Method
 */
class Direct extends \Magento\Payment\Model\Method\Cc
{
    use \BeGateway\BeGateway\Model\Traits\OnlinePaymentMethod;

    const CODE = 'begateway_direct';

    /**
     * Direct Method Code
     */
    protected $_code = self::CODE;

    protected $_canOrder = true;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCancelInvoice = true;
    protected $_canVoid = true;

    protected $_isInitializeNeeded = false;

    protected $_canFetchTransactionInfo = true;
    protected $_canSaveCc = false;

    /**
     * Direct constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\App\Action\Context $actionContext
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \BeGateway\BeGateway\Helper\Data $moduleHelper
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList ,
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate ,
     * @param \Magento\Directory\Model\CountryFactory $countryFactory ,
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Action\Context $actionContext,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \BeGateway\BeGateway\Helper\Data $moduleHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_actionContext = $actionContext;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleHelper = $moduleHelper;
        $this->_configHelper =
            $this->getModuleHelper()->getMethodConfig(
                $this->getCode()
            );
    }

    /**
     * Retrieves the Checkout Payment Action according to the
     * Module Transaction Type setting
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $helper = $this->getModuleHelper();

        $transactionTypeActions = [
            $helper::AUTHORIZE    =>
                \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE,
            $helper::PAYMENT      =>
                \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE
        ];

        $transactionType = $this->getConfigTransactionType();

        if (!array_key_exists($transactionType, $transactionTypeActions)) {
            $this->getModuleHelper()->throwWebApiException(
                sprintf(
                    'Transaction Type (%s) not supported yet',
                    $transactionType
                )
            );
        }

        return $transactionTypeActions[$transactionType];
    }

    /**
     * Retrieves the Module Transaction Type Setting
     *
     * @return string
     */
    public function getConfigTransactionType()
    {
        return $this->getConfigData('transaction_type');
    }

    /**
     * Gets Instance of the Magento Code Logger
     *
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Authorize payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this->processTransaction($payment, $amount);
    }

    /**
     * Capture payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
            $payment
        );

        /*
         * When no Auth then Process Sale / Sale3d
         * Note: this method is called when:
         *    - Capturing Payment in Admin Area
         *    - Doing a purchase when Payment Action is "ACTION_AUTHORIZE_CAPTURE"
         */
        if (!isset($authTransaction)) {
            return $this->processTransaction($payment, $amount);
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->getLogger()->debug('Capture transaction for order #' . $order->getIncrementId());

        try {
            $this->doCapture($payment, $amount, $authTransaction);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );
            $this->getModuleHelper()->maskException($e);
        }

        return $this;
    }

    /**
     * Refund payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->getLogger()->debug('Refund transaction for order #' . $order->getIncrementId());

        $captureTransaction = $this->getModuleHelper()->lookUpCaptureTransaction(
            $payment
        );

        if (!isset($captureTransaction)) {
            $errorMessage = __('Refund transaction for order # %1 cannot be finished (No Capture Transaction exists)',
                $order->getIncrementId()
            );

            $this->getLogger()->error(
                $errorMessage
            );

            $this->getMessageManager()->addError($errorMessage);

            $this->getModuleHelper()->throwWebApiException(
                $errorMessage
            );
        }

        try {
            $this->doRefund($payment, $amount, $captureTransaction);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );

            $this->getMessageManager()->addError(
                $e->getMessage()
            );

            $this->getModuleHelper()->maskException($e);
        }

        return $this;
    }

    /**
     * Void payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order $order */

        $order = $payment->getOrder();

        $this->getLogger()->debug('Void transaction for order #' . $order->getIncrementId());

        $referenceTransaction = $this->getModuleHelper()->lookUpVoidReferenceTransaction(
            $payment
        );

        if ($referenceTransaction->getTxnType() == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH) {
            $authTransaction = $referenceTransaction;
        } else {
            $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
                $payment
            );
        }

        if (!isset($authTransaction) || !isset($referenceTransaction)) {
            $errorMessage = __('Void transaction for order # %1 cannot be finished (No Authorize / Capture Transaction exists)',
                            $order->getIncrementId()
            );

            $this->getLogger()->error($errorMessage);
            $this->getModuleHelper()->throwWebApiException($errorMessage);
        }

        try {
            $this->doVoid($payment, $authTransaction, $referenceTransaction);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );
            $this->getModuleHelper()->maskException($e);
        }

        return $this;
    }

    /**
     * Cancel order
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->void($payment);
    }

    /**
     * Assign data to info model instance
     *
     * @param \Magento\Framework\DataObject|mixed $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();

        /*
         * Skip fix if CC Info already assigned (Magento 2.1.x)
         */
        if ($this->getInfoInstanceHasCcDetails($info)) {
            return $this;
        }

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        /** @var DataObject $info */
        $info->addData(
            [
                'cc_type'           => $additionalData->getCcType(),
                'cc_owner'          => $additionalData->getCcOwner(),
                'cc_last_4'         => substr($additionalData->getCcNumber(), -4),
                'cc_number'         => $additionalData->getCcNumber(),
                'cc_cid'            => $additionalData->getCcCid(),
                'cc_exp_month'      => $additionalData->getCcExpMonth(),
                'cc_exp_year'       => $additionalData->getCcExpYear(),
                'cc_ss_issue'       => $additionalData->getCcSsIssue(),
                'cc_ss_start_month' => $additionalData->getCcSsStartMonth(),
                'cc_ss_start_year'  => $additionalData->getCcSsStartYear()
            ]
        );

        return $this;
    }

    /**
     * Determines if the CC Details are supplied to the Payment Info Instance
     *
     * @param \Magento\Payment\Model\InfoInterface $info
     * @return bool
     */
    protected function getInfoInstanceHasCcDetails(\Magento\Payment\Model\InfoInterface $info)
    {
        return
            !empty($info->getCcNumber()) &&
            !empty($info->getCcCid()) &&
            !empty($info->getCcExpMonth()) &&
            !empty($info->getCcExpYear());
    }

    /**
     * Builds full Request Class Name by Transaction Type
     * @param string $transactionType
     * @return string
     */
    protected function getTransactionTypeRequestClassName($transactionType)
    {
        $requestClassName = ucfirst($transactionType);

        return "\\BeGateway\\{$requestClassName}Operation";
    }

    /**
     * Processes initial transactions
     *      - Authorize
     *      - Payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return $this
     * @throws \Exception
     * @throws \BeGateway\Exceptions\ErrorAPI
     */
    protected function processTransaction(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionType = $this->getConfigTransactionType();

        $order = $payment->getOrder();

        $helper = $this->getModuleHelper();

        $this->getConfigHelper()->initGatewayClient();

        $billing = $order->getBillingAddress();
        if (empty($billing)) {
            throw new \Exception(__('Billing address is empty.'));
        }

        $transaction = new $this->getTransactionTypeRequestClassName(
          $transactionType
        );

        $orderId = ltrim(
            $order->getIncrementId(),
            '0'
        );

        $transaction->setDescription($helper->buildOrderDescriptionText($order));
        $transaction->setLanguage($helper->getLocale());
        $transaction->money->setAmount($amount);
        $transaction->money->setCurrency($order->getBaseCurrencyCode());
        $transaction->setTrackingId($this->getModuleHelper()->genTransactionId(
          $orderId
        ));
        $transaction->customer->setIp($order->getRemoteIp());
        $transaction->customer->setEmail($order->getCustomerEmail());
        $transaction->customer->setPhone($billing->getTelephone());
        $transaction->customer->setFirstName($billing->getFirstname());
        $transaction->customer->setLastName($billing->getLastname());
        $transaction->customer->setCountry($billing->getCountryId());
        $transaction->customer->setAddress(
          $billing->getStreetLine(1) . ' '.
          $billing->getStreetLine(2)
        );
        $transaction->customer->setCity($billing->getCity());
        $transaction->customer->setZip($billing->getPostcode());
        $transaction->customer->setState($billing->getRegionCode());
        $transaction->setTestMode(intval($this->getConfigHelper()->getTestMode()) == 1);

        if (!empty($payment->getCcOwner())) {
          $transaction->card->setCardHolder($payment->getCcOwner());
        } else {
          $transaction->card->setCardHolder(
            $billing->getFirstname() . ' ' . $billing->getLastname()
          );
        }

        $transaction->card->setCardNumber($payment->getCcNumber());
        $transaction->card->setCardExpMonth($payment->getCcExpMonth());
        $transaction->card->setCardExpYear($payment->getCcExpYear());
        $transaction->card->setCardCvc($payment->getCcCid());
        $transaction->setReturnUrl(
          $helper->getReturnUrl(
              $this->getCode(),
              "process3d"
          )
        );

        $transaction->setNotificationUrl(
          $helper->getNotificationUrl(
            $this->getCode()
          )
        );

        try {
            $response = $transaction->submit();
        } catch (\Exception $e) {
            $logInfo =
                'Transaction ' . $transactionType .
                ' for order #' . $order->getIncrementId() .
                ' failed with message "' . $e->getMessage() . '"';

            $this->getLogger()->error($logInfo);

            $this->getCheckoutSession()->setBeGatewayLastCheckoutError(
                $e->getMessage()
            );

            $this->getModuleHelper()->maskException($e);
        }

        $this->setBeGatewayResponse(
            $response
        );

        $begateway_response = $this->getModuleHelper()->getArrayFromGatewayResponse(
            $this->getBeGatewayResponse()->getResponse()
        );

        $payment
            ->setTransactionId(
                $this->getBeGatewayResponse()->getUid()
            )
            ->setIsTransactionClosed(
                false
            )
            ->setIsTransactionPending(
                $this->getBeGatewayResponse()->isIncomplete()
            )
            ->setTransactionAdditionalInfo(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $begateway_response
            );

        $isTransactionFailed =
            !$this->getBeGatewayResponse()->isSuccess() &&
            !$this->getBeGatewayResponse()->isIncomplete();

        if ($isTransactionFailed) {
            $errorMessage = $this->getModuleHelper()->getErrorMessageFromGatewayResponse(
                $this->getBeGatewayResponse()
            );

            $this->getCheckoutSession()->setBeGatewayLastCheckoutError(
                $errorMessage
            );

            $this->getModuleHelper()->throwWebApiException($errorMessage);
        }

        if ($this->getBeGatewayResponse()->isIncomplete()) {
            $this->setRedirectUrl(
                $this->getBeGatewayResponse()->getResponse()->transaction->redirect_url
            );
            $payment->setPreparedMessage(__('3-D Secure: Redirecting customer to a verification page.'));
        } else {
            $this->unsetRedirectUrl();
        }
    }

    /**
     * Sets the 3D-Secure redirect URL or throws an exception on failure
     *
     * @param string $redirectUrl
     * @throws \Exception
     */
    public function setRedirectUrl($redirectUrl)
    {
        if (!isset($redirectUrl)) {
            throw new \Exception(__('Empty 3-D Secure redirect URL'));
        }

        if (filter_var($redirectUrl, FILTER_VALIDATE_URL) === false) {
            throw new \Exception(__('Invalid 3-D Secure redirect URL'));
        }

        $this->getCheckoutSession()->setBeGatewayCheckoutRedirectUrl($redirectUrl);
    }

    /**
     * Unsets the 3D-Secure redirect URL
     */
    public function unsetRedirectUrl()
    {
        $this->getCheckoutSession()->setBeGatewayCheckoutRedirectUrl(null);
    }

    /**
     * Determines method's availability based on config data and quote amount
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote) &&
            $this->getConfigHelper()->isMethodAvailable() &&
            $this->getModuleHelper()->isStoreSecure();
    }

    /**
     * Checks base currency against the allowed currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->getModuleHelper()->isCurrencyAllowed(
            $this->getCode(),
            $currencyCode
        );
    }
}

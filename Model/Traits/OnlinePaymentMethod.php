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

namespace BeGateway\BeGateway\Model\Traits;

/**
 * Trait for defining common variables and methods for all Payment Solutions
 * Trait OnlinePaymentMethod
 * @package BeGateway\BeGateway\Model\Traits
 */
trait OnlinePaymentMethod
{
    /**
     * @var \BeGateway\BeGateway\Model\Config
     */
    protected $_configHelper;
    /**
     * @var \BeGateway\BeGateway\Helper\Data
     */
    protected $_moduleHelper;
    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $_actionContext;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface
     */
    protected $_transactionManager;

    /**
     * Get an Instance of the Config Helper Object
     * @return \BeGateway\BeGateway\Model\Config
     */
    protected function getConfigHelper()
    {
        return $this->_configHelper;
    }

    /**
     * Get an Instance of the Module Helper Object
     * @return \BeGateway\BeGateway\Helper\Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Get an Instance of the Magento Action Context
     * @return \Magento\Framework\App\Action\Context
     */
    protected function getActionContext()
    {
        return $this->_actionContext;
    }

    /**
     * Get an Instance of the Magento Core Message Manager
     * @return \Magento\Framework\Message\ManagerInterface
     */
    protected function getMessageManager()
    {
        return $this->getActionContext()->getMessageManager();
    }

    /**
     * Get an Instance of Magento Core Store Manager Object
     * @return \Magento\Store\Model\StoreManagerInterface
     */
    protected function getStoreManager()
    {
        return$this->_storeManager;
    }

    /**
     * Get an Instance of the Url
     * @return \Magento\Framework\UrlInterface
     */
    protected function getUrlBuilder()
    {
        return $this->_urlBuilder;
    }

    /**
     * Get an Instance of the Magento Core Checkout Session
     * @return \Magento\Checkout\Model\Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Get an Instance of the Magento Transaction Manager
     * @return \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface
     */
    protected function getTransactionManager()
    {
        return $this->_transactionManager;
    }

    /**
     * Initiate a Payment Gateway Reference Transaction
     *      - Capture
     *      - Refund
     *      - Void
     *
     * @param string $transactionType
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $data
     * @return \stdClass
     */
    protected function processReferenceTransaction(
        $transactionType,
        \Magento\Payment\Model\InfoInterface $payment,
        $data
    ) {
        $transactionType = ucfirst(
            strtolower(
                $transactionType
            )
        );

        $this->getConfigHelper()->initGatewayClient();
        $helper = $this->getModuleHelper();

        $begateway = "\\BeGateway\\{$transactionType}Operation";
        $begateway = new $begateway;

        $begateway->setParentUid($data['reference_id']);
        $begateway->money->setAmount($data['amount']);
        $begateway->money->setCurrency($data['currency']);

        if (strtolower($transactionType) == $helper::REFUND)
          $begateway->setReason($data['reason']);

        $responseObject = $begateway->submit();

        if (!$responseObject->isSuccess())
          throw new \Exception(
            __('%1 operation error. Reason: %2',
              $transactionType,
              $responseObject->getMessage()
            )
          );

        $payment
            ->setTransactionId(
                $responseObject->getUid()
            )
            ->setParentTransactionId(
                $data['reference_id']
            )
            ->setShouldCloseParentTransaction(
                true
            )
            ->setIsTransactionPending(
                false
            )
            ->setIsTransactionClosed(
                true
            )
            ->resetTransactionAdditionalInfo(

            );

        $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
            $payment,
            $responseObject
        );

        $payment->save();

        return $responseObject;
    }

    /**
     * Base Payment Capture Method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param \Magento\Sales\Model\Order\Payment\Transaction|null $authTransaction
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    protected function doCapture(\Magento\Payment\Model\InfoInterface $payment, $amount, $authTransaction)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $helper = $this->getModuleHelper();

        $data = array(
            'reference_id'   =>
                $authTransaction->getTxnId(),
            'currency'       =>
                $order->getBaseCurrencyCode(),
            'amount'         =>
                $amount
        );

        $responseObject = $this->processReferenceTransaction(
            $helper::CAPTURE,
            $payment,
            $data
        );

        if ($responseObject->isSuccess()) {
            $this->getMessageManager()->addSuccess($responseObject->getMessage());
        } else {
            $this->getModuleHelper()->throwWebApiException(
                $responseObject->getMessage()
            );
        }

        unset($data);

        return $this;
    }

    /**
     * Base Payment Refund Method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param \Magento\Sales\Model\Order\Payment\Transaction|null $captureTransaction
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function doRefund(\Magento\Payment\Model\InfoInterface $payment, $amount, $captureTransaction)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $helper = $this->getModuleHelper();
        if (!$this->getModuleHelper()->canRefundTransaction($captureTransaction)) {
            $errorMessage = __('Order cannot be refunded online.');

            $this->getMessageManager()->addError($errorMessage);
            $this->getModuleHelper()->throwWebApiException($errorMessage);
        }
        $data = array(
            'reference_id'   =>
                $captureTransaction->getTxnId(),
            'currency'       =>
                $order->getBaseCurrencyCode(),
            'amount'         =>
                $amount,
            'reason'         => __('Merchant refund')
        );

        $responseObject = $this->processReferenceTransaction(
            $helper::REFUND,
            $payment,
            $data
        );

        if ($responseObject->isSuccess()) {
            $this->getMessageManager()->addSuccess($responseObject->getMessage());
        } else {
            $this->getMessageManager()->addError($responseObject->getMessage());
            $this->getModuleHelper()->throwWebApiException(
                $responseObject->getMessage()
            );
        }

        unset($data);

        return $this;
    }

    /**
     * Base Payment Void Method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \Magento\Sales\Model\Order\Payment\Transaction|null $authTransaction
     * @param \Magento\Sales\Model\Order\Payment\Transaction|null $referenceTransaction
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function doVoid(\Magento\Payment\Model\InfoInterface $payment, $authTransaction, $referenceTransaction)
    {
        /** @var \Magento\Sales\Model\Order $order */

        $order = $payment->getOrder();
        $helper = $this->getModuleHelper();

        $data = array(
            'reference_id'   =>
                $referenceTransaction->getTxnId(),
            'currency'       =>
                $order->getBaseCurrencyCode(),
            'amount'         =>
                $amount
        );

        $responseObject = $this->processReferenceTransaction(
            $helper::VOID,
            $payment,
            $data
        );

        if ($responseObject->isSuccess()) {
            $this->getMessageManager()->addSuccess($responseObject->getMessage());
        } else {
            $this->getMessageManager()->addError($responseObject->getMessage());
            $this->getModuleHelper()->throwWebApiException(
                $responseObject->getMessage()
            );
        }

        unset($data);

        return $this;
    }
}

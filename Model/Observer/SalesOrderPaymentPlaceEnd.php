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

namespace BeGateway\BeGateway\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Observer Class (called just after the Sales Order has been Places)
 * Class SalesOrderPaymentPlaceEnd
 * @package BeGateway\BeGateway\Model\Observer
 */
class SalesOrderPaymentPlaceEnd implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;
    /**
     * @var \BeGateway\BeGateway\Helper\Data
     */
    protected $_moduleHelper;

    /**
     * SalesOrderPaymentPlaceEnd constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \BeGateway\BeGateway\Helper\Data $moduleHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \BeGateway\BeGateway\Helper\Data $moduleHelper
    ) {
        $this->_storeManager = $storeManager;
        $this->_moduleHelper = $moduleHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getData('payment');

        switch ($payment->getMethod()) {
            case \BeGateway\BeGateway\Model\Method\Checkout::CODE:
                $this->updateOrderStatusToNew($payment);
                break;
            case \BeGateway\BeGateway\Model\Method\Direct::CODE:
                $this->updateOrderStatus($payment);
                break;
            default:
                // Payment method not implemented. Do nothing.
        }
    }

    /**
     * Update OrderStatus for the new Order
     *
     * Used by the Checkout Payment method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    protected function updateOrderStatusToNew(\Magento\Payment\Model\InfoInterface $payment)
    {
        $order = $payment->getOrder();

        $configHelper = $this->getModuleHelper()->getMethodConfig(
            $payment->getMethod()
        );

        $this->getModuleHelper()->setOrderStatusByState(
            $order,
            $configHelper->getOrderStatusNew()
        );

        $order->save();
    }

    /**
     * Update Order Status
     *
     * Used by the Direct Payment method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    protected function updateOrderStatus(\Magento\Payment\Model\InfoInterface $payment)
    {
        $helper = $this->getModuleHelper();

        $transactionStatus = $this->getModuleHelper()->getPaymentAdditionalInfoValue(
            $payment,
            $helper::ADDITIONAL_INFO_KEY_STATUS
        );

        if (!$transactionStatus) {
            $order = $payment->getOrder();


            switch ($transactionStatus) {
                case $helper::PENDING:
                case $helper::INCOMPLETE:
                    $redirectUrl = $this->getModuleHelper()->getPaymentAdditionalInfoValue(
                        $payment,
                        $helper::ADDITIONAL_INFO_KEY_REDIRECT_URL
                    );

                    if ($redirectUrl) {
                        $this->getModuleHelper()->setOrderState(
                            $order,
                            $helper::PENDING
                        );
                    }
                    break;
                case $helper::SUCCESSFUL:
                    $this->getModuleHelper()->setOrderStatusByState(
                        $order,
                        \Magento\Sales\Model\Order::STATE_PROCESSING
                    );

                    break;
                default:
                    // Other status. Do nothing.
            }
        }
    }

    /**
     * Get an Instance of the Module Helper Object
     * @return \BeGateway\BeGateway\Helper\Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }
}

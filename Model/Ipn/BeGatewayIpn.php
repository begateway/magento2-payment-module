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

namespace BeGateway\BeGateway\Model\Ipn;

/**
 * Checkout Method IPN Handler Class
 * Class CheckoutIpn
 * @package BeGateway\BeGateway\Model\Ipn
 */
class BeGatewayIpn extends \BeGateway\BeGateway\Model\Ipn\AbstractIpn
{
    /**
     * @return string
     */
    protected function getPaymentMethodCode()
    {
        return \BeGateway\BeGateway\Model\Method\Checkout::CODE;
    }

    /**
     * Update Pending Transactions and Order Status
     * @param \stdClass $responseObject
     * @throws \Exception
     */
    protected function processNotification($responseObject)
    {
        $payment = $this->getPayment();
        $helper = $this->getModuleHelper();

        $this->getModuleHelper()->updateTransactionAdditionalInfo(
            $responseObject->getUid(),
            $responseObject,
            true
        );

        if (isset($responseObject->getResponse()->transaction)) {
            $payment_transaction = $responseObject;

            $payment
                ->setLastTransId(
                    $payment_transaction->getUid()
                )
                ->setTransactionId(
                    $payment_transaction->getUid()
                )
                ->setParentTransactionId(
                    isset(
                      $responseObject->getResponse()->transaction->parent_uid
                    ) ?
                      $responseObject->getResponse()->transaction->parent_uid
                      : null
                )
                ->setIsTransactionPending(
                    $this->getShouldSetCurrentTranPending(
                        $payment_transaction
                    )
                )
                ->setShouldCloseParentTransaction(
                    true
                )
                ->setIsTransactionClosed(
                    $this->getShouldCloseCurrentTransaction(
                        $payment_transaction
                    )
                )
                ->setPreparedMessage(
                    $this->createIpnComment(
                        $payment_transaction->getMessage()
                    )
                )
                ->resetTransactionAdditionalInfo(

                );

            $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
                $payment,
                $payment_transaction
            );

            $money = new \BeGateway\Money;
            $money->setCents($payment_transaction->getResponse()->transaction->amount);
            $money->setCurrency($payment_transaction->getResponse()->transaction->currency);

            switch ($payment_transaction->getResponse()->transaction->type) {
                case $helper::AUTHORIZE:
                    $payment->registerAuthorizationNotification($money->getAmount());
                    break;
                case $helper::PAYMENT:
                    $payment->registerCaptureNotification($money->getAmount());
                    break;
                default:
                    break;
            }

            //if (!$this->getOrder()->getEmailSent()) {
            //    $this->_orderSender->send($this->getOrder());
            //}

            $payment->save();
        }

        $this->getModuleHelper()->setOrderState(
            $this->getOrder(),
            $responseObject->getStatus()
        );
    }
}

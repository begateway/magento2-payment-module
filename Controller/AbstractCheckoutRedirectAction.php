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

namespace BeGateway\BeGateway\Controller;

/**
 * Base Checkout Redirect Controller Class
 * Class AbstractCheckoutRedirectAction
 * @package BeGateway\BeGateway\Controller
 */
abstract class AbstractCheckoutRedirectAction extends \BeGateway\BeGateway\Controller\AbstractCheckoutAction
{
    /**
     * @var \BeGateway\BeGateway\Helper\Checkout
     */
    private $_checkoutHelper;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \BeGateway\BeGateway\Helper\Checkout $checkoutHelper
    ) {
        parent::__construct($context, $logger, $checkoutSession, $orderFactory);
        $this->_checkoutHelper = $checkoutHelper;
    }

    /**
     * Get an Instance of the Magento Checkout Helper
     * @return \BeGateway\BeGateway\Helper\Checkout
     */
    protected function getCheckoutHelper()
    {
        return $this->_checkoutHelper;
    }

    /**
     * Handle Success Action
     * @return void
     */
    protected function executeSuccessAction()
    {
        if ($this->getCheckoutSession()->getLastRealOrderId()) {
            $this->getMessageManager()->addSuccess(__("Your payment is complete"));
            $this->redirectToCheckoutOnePageSuccess();
        }
    }

    /**
     * Handle Cancel Action from Payment Gateway
     */
    protected function executeCancelAction()
    {
        $this->getCheckoutHelper()->cancelCurrentOrder('');
        $this->getCheckoutHelper()->restoreQuote();
        $this->redirectToCheckoutCart();
    }

    /**
     * Get the redirect action
     *      - success
     *      - cancel
     *      - failure
     *
     * @return string
     */
    protected function getReturnAction()
    {
        return $this->getRequest()->getParam('action');
    }
}

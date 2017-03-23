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
 * Base Controller Class
 * Class AbstractAction
 * @package BeGateway\BeGateway\Controller
 */
abstract class AbstractAction extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Action\Context
     */
    private $_context;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->_context = $context;
        $this->_logger = $logger;
    }

    /**
     * Get Instance of Magento Controller Action
     * @return \Magento\Framework\App\Action\Context
     */
    protected function getContext()
    {
        return $this->_context;
    }

    /**
     * Get Instance of Magento Object Manager
     * @return \Magento\Framework\ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return $this->_objectManager;
    }

    /**
     * Get Instance of Magento global Message Manager
     * @return \Magento\Framework\Message\ManagerInterface
     */
    protected function getMessageManager()
    {
        return $this->getContext()->getMessageManager();
    }

    /**
     * Get Instance of Magento global Logger
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Check if param exists in the post request
     * @param string $key
     * @return bool
     */
    protected function isPostRequestExists($key)
    {
        $post = $this->getPostRequest();

        return isset($post[$key]);
    }

    /**
     * Get an array of the Submitted Post Request
     * @param string|null $key
     * @return null|array
     */
    protected function getPostRequest($key = null)
    {
        $post = $this->getRequest()->getPostValue();

        if (isset($key) && isset($post[$key])) {
            return $post[$key];
        } elseif (isset($key)) {
            return null;
        } else {
            return $post;
        }
    }
}

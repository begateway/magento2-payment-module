<?php
/*
 * Copyright (C) 2021 beGateway
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
 * @copyright   2021 beGateway
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace BeGateway\BeGateway\Model\Traits;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Trait for defining common variables and methods for all Payment Solutions
 * Trait OnlinePaymentMethod
 * @package BeGateway\BeGateway\Model\Traits
 */
trait Logger
{

  /**
   * Collected debug information
   *
   * @var array
   */
  protected $_debugData = [];

  /**
  * Init BeGateway Logger
  *
  * @return \Monolog\Logger
  */
  protected function _initLogger()
  {
    $writer = new StreamHandler(BP . '/var/log/begateway.log');
    $logger = new Logger();
    $logger->pushHandler($writer);
    return $logger;
  }

  /**
   * Log debug data to file
   *
   * @return void
   */
  protected function _writeDebugData()
  {
    if ($this->getConfigHelper()->getDebug()) {
      $this->getLogger()->debug(var_export($this->_debugData, true));
    }
  }

  /**
   * @param string $key
   * @param array|string $value
   * @return $this
   */
  protected function _addDebugData($key, $value)
  {
      $this->_debugData[$key] = $value;
      return $this;
  }
}

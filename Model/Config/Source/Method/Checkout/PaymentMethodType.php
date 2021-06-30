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

namespace BeGateway\BeGateway\Model\Config\Source\Method\Checkout;

use \BeGateway\BeGateway\Helper\Data as DataHelper;

/**
 * Checkout Payment Method Types Model Source
 * Class PaymentMethodType
 * @package BeGateway\BeGateway\Model\Config\Source\Method\Checkout
 */
class PaymentMethodType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Builds the options for the MultiSelect control in the Admin Zone
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => DataHelper::CREDIT_CARD,
                'label' => __('Bankcard'),
            ],
            [
                'value' => DataHelper::CREDIT_CARD_HALVA,
                'label' => __('Halva bankcard'),
            ],
            [
                'value' => DataHelper::ERIP,
                'label' => __('ERIP'),
            ]
        ];
    }
}

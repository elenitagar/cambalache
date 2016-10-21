<?php
/**
 * @author		Sashas
 * @category    Sashas
 * @package     Sashas_CouponGift
 * @copyright   Copyright (c) 2015 Sashas IT Support Inc. (http://www.sashas.org)
 * @license     http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
 */

$installer = $this;

$installer->startSetup();
$installer->getConnection()->addColumn(
		$installer->getTable('sales/quote_item'),
		'is_coupongift',
		array(
				'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
				'nullable'  => false,
				'default'	=>0,
				'comment'   => ' Is Item Coupon Gift'
		)
		);
$installer->endSetup();
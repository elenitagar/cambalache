<?php
/**
 * @author		Sashas
 * @category    Sashas
 * @package     Sashas_CouponGift
 * @copyright   Copyright (c) 2015 Sashas IT Support Inc. (http://www.sashas.org)
 * @license     http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
 * @version     2
 */
class Sashas_CouponGift_Model_Observer {
	protected static $_singletonFlag = false;
	const COUPON_GIFT_CODE = 'coupon_gift';
	
	public function SalesRulePrepareForm(Varien_Event_Observer $observer) {
		$form = $observer->getForm ();
		
		$field = $form->getElement ( 'simple_action' );
		$options = $field->getValues ();
		
		$options [] = array ('value' => self::COUPON_GIFT_CODE, 'label' => 'Add Gift Product' );
		
		$field->setValues ( $options );
		
		$after_element_js = "
		<script type=\"text/javascript\" >
		document.getElementById('rule_simple_action').addEventListener('change', function(){couponGiftFields();}, false);
		function couponGiftFields () {			  
			if ($('rule_simple_action').value=='" . self::COUPON_GIFT_CODE . "') {		  
				$('rule_discount_amount').value=0;				
				if ($$('#rule_action_fieldset tr')[0]!=undefined) {  
					$$('#rule_action_fieldset tr').each(function(tr_el) {
						if  ($(tr_el).down('#rule_gift_product_sku')!=undefined || $(tr_el).down('#rule_gift_product_force_price')!=undefined  )						 
							$(tr_el).show();
						else
							$(tr_el).hide();
							
					if ($(tr_el).down('#rule_simple_action')!=undefined || $(tr_el).down('#rule_discount_amount')!=undefined  )
						$(tr_el).show();							
					});	  
				}				
			} else {
				if ($$('#rule_action_fieldset tr')[0]!=undefined) {  
					$$('#rule_action_fieldset tr').each(function(tr_el) {
						if  ($(tr_el).down('#rule_gift_product_sku')!=undefined || $(tr_el).down('#rule_gift_product_force_price')!=undefined  )						 
							$(tr_el).hide();
						else
							$(tr_el).show();
							
					if ($(tr_el).down('#rule_simple_action')!=undefined || $(tr_el).down('#rule_discount_amount')!=undefined  )
						$(tr_el).show();							
					});	  
				}				 				 									
			}
		}
		document.observe('dom:loaded', function() {  couponGiftFields(); });		 
		</script>";
		$field->setAfterElementHtml ( $after_element_js );
		
		$fieldset = $form->getElement ( 'action_fieldset' );
		
		$fieldset->addField ( 'gift_product_sku', 'text', array ('name' => 'gift_product_sku', 'label' => Mage::helper ( 'coupongift' )->__ ( 'Gift Product SKU' ) ) );
		
		$fieldset->addField ( 'gift_product_force_price', 'select', array ('name' => 'gift_product_force_price', 'label' => Mage::helper ( 'cms' )->__ ( 'Set gift product price 0' ), 'options' => array (1 => "Yes", 0 => "No" ) ) );
		
		return $this;
	}
	
	public function SalesRuleGiftValidator(Varien_Event_Observer $observer) {

		$rule = $observer->getRule ();
		if ($rule->getSimpleAction () != self::COUPON_GIFT_CODE)
			return $this;
//		error_log("ICPDEV: SalesRuleGiftValidator");
		$force_price = $rule->getGiftProductForcePrice ();
		$gift_products_sku_arr = explode( ',',  $rule->getGiftProductSku () );	
		$quoteObj = Mage::getModel ( 'checkout/cart' )->getQuote ();
		$cart_obj = Mage::getModel ( 'checkout/cart' );
		foreach ( $gift_products_sku_arr as $gift_product_sku ) {
			$this->_SalesRuleAddProduct( $gift_product_sku, $force_price,$quoteObj,$cart_obj);
		}
		$quoteObj->save ();
//		error_log("ICPDEV: SalesRuleGiftValidator OK!!!");
		return $this;
	}

	function _SalesRuleAddProduct($gift_product_sku,$force_price,$quoteObj,$cart_obj){
		$product_id = Mage::getModel ( 'catalog/product' )->getIdBySku ( $gift_product_sku );
		$delete_gift_product = 0;
		$was_added = false;
		
		if (! $product_id) {
			Mage::getSingleton ( 'checkout/session' )->addError ( Mage::helper ( 'coupongift' )->__ ( 'Gift Product SKU "%s" Not Found.', Mage::helper ( 'core' )->htmlEscape ( $gift_product_sku ) ) );
			/*
			 * Mage::throwException(Mage::helper('coupongift')->__('Gift Product
			 * SKU Not Found.'));
			 */
			return $this;
		}
		// Busca si ya esta metido el producto en el carro.
		foreach ( $cart_obj->getItems () as $quote_item ) {
			if ($quote_item->getProductId () == $product_id) {
				$was_added = true;
				$gift_quote_item = $quote_item;
				$gift_quote_item->setQty ( 1 );
				return $this;
			}
		}
		// Check if original product was deleted
		if (count ( $cart_obj->getItems () ) < 2 && $gift_quote_item instanceof Mage_Sales_Model_Quote_Item)
			$delete_gift_product = 1;
/*		
		if ($was_added && ! $delete_gift_product){
			error_log("ICPDEV: Exit >>>>> ");
			return $this;
		}
*/		
		$_product = Mage::getModel ( 'catalog/product' )->load ( $product_id );
		// Check if original product was deleted
		if ($delete_gift_product) {
//			error_log("ICPDEV: removeItem >>>>> ". $gift_quote_item->getId () );
			$quoteObj->removeItem ( $gift_quote_item->getId () );
			return $this;
		}
		// Check if original product was deleted
		$quoteItem = Mage::getModel ( 'sales/quote_item' )->setProduct ( $_product );
//		error_log("ICPDEV: ADD >>>>> ".$gift_product_sku);
		if ($force_price)
			$quoteItem->setOriginalCustomPrice ( 0 );
		else
			$quoteItem->setOriginalCustomPrice ();
		$quoteItem->setQty ( 1 );
		$quoteItem->setIsCoupongift(1);
//		error_log("ICPDEV: ADD isDeleted  >>>>> ".$quoteItem->isDeleted()." <<<<");
		$quoteObj->addItem ( $quoteItem ); 
		return $this;
	}

	public function RemoveCoupon(Varien_Event_Observer $observer) {

		if (Mage::app ()->getRequest ()->getParam ( 'remove' ) != 1)
			return $this;
//		error_log("ICPDEV: RemoveCoupon 1 ".Mage::app ()->getRequest ()->getParam ( 'remove' ));
		$quote = $observer->getQuote ();
		$quote_id = $quote->getEntityId ();
		$dbQuote=Mage::getModel ( 'sales/quote' )->load ( $quote_id) ;
		$applied_coupon_ids =$dbQuote->getAppliedRuleIds ();
//		error_log("ICPDEV: RemoveCoupon 2 ".$applied_coupon_ids);
		if (! $applied_coupon_ids)
			return $this;
		$applied_coupon_ids_arr=explode(',', $applied_coupon_ids);
		$rule=Mage::getModel ( 'salesrule/rule' );
		$couponCode=$dbQuote->getCouponCode();
		
		foreach ( $applied_coupon_ids_arr as $apr ) {
			$tmp_rule = Mage::getModel ( 'salesrule/rule' )->load ( $apr );
			if ($tmp_rule->getSimpleAction()!= 'coupon_gift')
				continue;
			if ($couponCode==$tmp_rule->getCouponCode()) {			 		 
				$rule=$tmp_rule; 
				break;
			} 			 
		}  
//		error_log("ICPDEV: RemoveCoupon 2 ".$rule->getSimpleAction ());
		if ($rule && $rule->getSimpleAction () != 'coupon_gift')
			return $this;
		$gift_products_sku_arr = explode( ',',  $rule->getGiftProductSku () );	
		foreach ( $gift_products_sku_arr as $gift_product_sku ) {
			$this->_RemoveItemFromCart( $gift_product_sku);
		}
		return $this;
	}
	
	function _RemoveItemFromCart($gift_product_sku){
		$product_id = Mage::getModel ( 'catalog/product' )->getIdBySku ( $gift_product_sku );
		$cart_obj = Mage::getModel ( 'checkout/cart' );
		
		$gift_product_item_id = '';
		foreach ( $cart_obj->getItems () as $quote_cart_item ) {
			if ($quote_cart_item->getProductId () == $product_id) {
				$gift_product_item = $quote_cart_item;
				break;
			}
		}
		
		if (! $gift_product_item instanceof Mage_Sales_Model_Quote_Item)
			return;
			
		$gift_product_item->isDeleted ( true );
		//$quote->removeItem ( $gift_product_item->getId () );
	}
	
	public function UpdateCartItem(Varien_Event_Observer $observer) {
//		error_log("ICPDEV: UpdateCartItem 0");
		$new_info = $observer->getInfo ();
		$cart = $observer->getCart ();
		
		$quoteObj = Mage::getModel ( 'checkout/cart' )->getQuote ();
		$applied_coupon_ids = Mage::getModel ( 'sales/quote' )->load ( $quoteObj->getEntityId () )->getAppliedRuleIds ();
//		error_log("ICPDEV: UpdateCartItem 1 ".$applied_coupon_ids);
		if (! $applied_coupon_ids)
			return $this;
//		error_log("ICPDEV: UpdateCartItem 2 ".$applied_coupon_ids);
			
			/*
		 * if few rules applied 
		 */
		$applied_coupon_ids_arr=explode(',', $applied_coupon_ids);
		$rule=Mage::getModel ( 'salesrule/rule' );
		foreach ( $applied_coupon_ids_arr as $apr ) {
			$tmp_rule = Mage::getModel ( 'salesrule/rule' )->load ( $apr );
			  
			if ($tmp_rule->getSimpleAction()== 'coupon_gift') {
				$rule=$tmp_rule;				
				break;
			}
		}		
//		error_log("ICPDEV: UpdateCartItem 3 ".$rule->getSimpleAction ());
		if ($rule && $rule->getSimpleAction () != 'coupon_gift')
			return $this;
	  
		
		$gift_product_sku = $rule->getGiftProductSku ();
 		Mage::getSingleton ( 'checkout/session' )->addError ( Mage::helper ( 'coupongift' )->__ ( 'Quitando producto del cupon' ) );
		
		if (strpos($gift_product_sku, ',') !== false) return $this;
		$product_id = Mage::getModel ( 'catalog/product' )->getIdBySku ( $gift_product_sku );
		
		foreach ( $observer->getCart ()->getItems () as $quote_item ) {
			if ($quote_item->getProductId () == $product_id) {
				$gift_product_item = $quote_item;
				/*
				 * Force qty
				 */
				if ($new_info [$gift_product_item->getId ()] ['qty'] > 1)
					$gift_product_item->setQty ( 1 )->save ();
					/*
				 * Force qty
				 */
				break;
			}
		}
		
		/*
		 * remove if qty changed
		 */
		$quoteItemBack = clone $gift_product_item;
		
		$quoteObj->removeItem ( $gift_product_item->getId () );
		unset ( $new_info [$gift_product_item->getId ()] );
		$quoteObj->setTotalsCollectedFlag ( false )->collectTotals ()->save ();
		
		if ($cart->getQuote ()->getAppliedRuleIds ()) {
			/*
			 * Add Item back if rule validated
			 */
			$quoteItemBack->setQty ( 1 );
			$quoteItemBack->setIsCoupongift(1);
			$quoteObj->addItem ( $quoteItemBack );
			$quoteObj->setTotalsCollectedFlag ( false )->collectTotals ()->save ();
		}
		/*
		 * remove if qty changed
		 */
		
		return $this;
	}
	
	public function RemovefromCart(Varien_Event_Observer $observer) {

		$removed_quote_item = $observer->getQuoteItem ();
		$removed_product_id = $removed_quote_item->getProductId ();
		$cart = Mage::getModel ( 'checkout/cart' );
		$quoteObj = Mage::getModel ( 'checkout/cart' )->getQuote ();
		$applied_coupon_ids = Mage::getModel ( 'sales/quote' )->load ( $quoteObj->getEntityId () )->getAppliedRuleIds ();
		if (! $applied_coupon_ids)
			return $this;

//		error_log("ICPDEV: RemovefromCart 1 ".$removed_product_id. " borrado ".$removed_quote_item->isDeleted() );	
		// if few rules applied 

		$applied_coupon_ids_arr=explode(',', $applied_coupon_ids);
		$rule=Mage::getModel ( 'salesrule/rule' );
		foreach ( $applied_coupon_ids_arr as $apr ) {
			$tmp_rule = Mage::getModel ( 'salesrule/rule' )->load ( $apr );
			  
			if ($tmp_rule->getSimpleAction()== 'coupon_gift') {
				$rule=$tmp_rule;				
				break;
			}
		}		
//		error_log("ICPDEV: RemovefromCart 2 ".$rule->getSimpleAction ());
		 
		if ($rule && $rule->getSimpleAction () != 'coupon_gift')
			return $this;
// 		error_log("ICPDEV: RemovefromCart 3 ".$rule->getGiftProductSku ());
		$gift_products_sku_arr = explode( ',',  $rule->getGiftProductSku () );
		/*
		foreach ( $gift_products_sku_arr as $gift_product_sku ) {		
			$this->_RemoveGif($gift_product_sku, $removed_product_id,$quoteObj,$cart);
		}
		$quoteObj->save ();
		*/
		return $this;
	
	}
	
	function _RemoveGif($gift_product_sku, $removed_product_id,$quoteObj,$cart){
		$product_id = Mage::getModel ( 'catalog/product' )->getIdBySku ( $gift_product_sku );
		
		if ($removed_product_id == $product_id)
			return $this;
		
		$quoteObj->setTotalsCollectedFlag ( false )->collectTotals ()->save ();
		if ($cart->getQuote ()->getAppliedRuleIds ()) {
			return $this;
		} else {
			foreach ( $quoteObj->getAllItems () as $quote_item ) {
				if ($quote_item->getProductId () == $product_id && !$quote_item->isDeleted () ) {
					$quote_item->isDeleted ( true );
					$quoteObj->removeItem ( $quote_item->getId () );//->save ();
					
					break;
				}
			}
		}	
		return $this;
	}
}

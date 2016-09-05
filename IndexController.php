<?php

class Cmsmart_AjaxCart_IndexController extends Mage_Core_Controller_Front_Action
{
	public function _constructor(){
		$_SERVER['REQUEST_URI'] = array_pop(explode('web.com', @$_SERVER['HTTP_REFERER']));
	}
	
    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }
	
	protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
	
    protected function _updateShoppingCart()
    {
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    }
                }
                $cart = $this->_getCart();
                if (! $cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }

                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                    ->save();
            }
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError(Mage::helper('core')->escapeHtml($e->getMessage()));
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot update shopping cart.'));
            Mage::logException($e);
        }
    }

	public function indexAction(){
		$delete = $this->getRequest()->getParam('delete');
		$kiemtra = true;
		
		$update = $this->getLayout()->getUpdate();
		$update->addHandle('default');
		$this->addActionLayoutHandles();
		if(!$delete){
			$product = $this->_getProductFromUrl();
			$product = Mage::registry('product');
			$options = $product->getProductOptionsCollection();
			$conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
			$col = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
			//$kiemtra = (!$product->getTypeInstance(true)->hasRequiredOptions($product) || $this->getRequest()->getParam('product') || count($col) == 0);
			//$kiemtra = ($product->getTypeId() == 'simple'  || $this->getRequest()->getParam('product') || $delete) && !$product->getTypeInstance(true)->hasRequiredOptions($product);	
			$kiemtra = $delete || $this->getRequest()->getParam('product') || !$product->getTypeInstance(true)->hasRequiredOptions($product);
			if($kiemtra) {
				self::tryaddAction($product, $_GET);
			}
	
			Mage::dispatchEvent('catalog_controller_product_view', array('product'=>$product));
			if ($this->getRequest()->getParam('options')) {
				$notice = $product->getTypeInstance(true)->getSpecifyOptionMessage();
				Mage::getSingleton('catalog/session')->addNotice($notice);
			}
			if(@$product) Mage::getSingleton('catalog/session')->setLastViewedProductId($product->getId());
			
			if(@$product){
				$update->addHandle('PRODUCT_TYPE_'.$product->getTypeId());
				$update->addHandle('PRODUCT_'.$product->getId());
			}
		}else{
			$update->addUpdate('<remove name="product.info"/>');	
			$kiemtra = true;
			if($delete == 'all'){
				 self::_emptyShoppingCart();
			}else{
				if($delete != 'udaj') { $this->removeAction($delete); }else {
					if(@$_POST['cart']) $this->_updateShoppingCart();
						//foreach(@$_POST['cart'] as $k => $v){
					//	$this->udpateAction($k, $v);
				}
			}
		}

		$this->loadLayoutUpdates();
		$this->generateLayoutXml()->generateLayoutBlocks();
		
		$json_encode = array();
		$json_encode['ajaxcart'] = '';
		$json_encode['ajaxsidebar'] = '';
		$json_encode['ajaxSummaryCount'] = '';
		$json_encode['ajaxCountItem'] = '';
		$json_encode['ajaxcartmsg'] = '';
		$json_encode['ajaxcontinue'] = '';

		if($kiemtra) {
		// Show the message add to cart is success 			
		if(@$product) $json_encode['ajaxcontinue'] = '
		<div>
			<ul class="messages ajaxcart-messages">
				<li class="success-msg">
					<ul>
						<li><span><a title="'.@$product->getName().'" href="'.@$product->getProductUrl().'">'.@$product->getName().' </a> '.$this->__('has been added to cart').'.</span></li>
						<li style="text-align:center;"><button onclick="setLocation(\''.Mage::getBaseUrl().'checkout/cart\')" class="button btn-continue" title="'.$this->__('Mi carrito').'" type="button"><span><span>'.$this->__('Mi carrito').'</span></span></button>
					</ul>
				</li>
			</ul>
		</div>';
		//<button class="button btn-continue closemsg" title="Continue Shopping" type="button"><span><span>Continue Shopping</span></span></button>
			//<button onclick="setLocation(\''.Mage::getBaseUrl().'checkout/onepage/\')" class="button btn-continue" title="'.$this->__('Proceed to Checkout').'" type="button"><span><span>'.$this->__('Proceed to Checkout').'</span></span></button>
	
		// Drop Down Cart Block 
			$block = $this->getLayout()->createBlock('checkout/cart_sidebar')->setBlockId('block-id-in-magento')->setTemplate('checkout/cart/sidebar.phtml');
			//$this->getLayout()->getBlock('content')->append($block);
			$count = Mage::helper('checkout/cart')->getSummaryCount();
			if ($count == 1) {
                $text = $this->__('My Cart (%s item)', $count);
            } elseif ($count > 0) {
                $text = $this->__('My Cart (%s items)', $count);
            } else {
                $text = $this->__('My Cart');
            }
			$json_encode['ajaxCountItem'] = $count;
			$json_encode['ajaxSummaryCount'] = $text;
			$json_encode['ajaxsidebar'] = str_replace('class="block block-cart"', 'id="cart-ajax-sidebar"', @$block->toHtml());
			
			$block2 = $this->getLayout()->createBlock('checkout/cart_sidebar')
				->setTemplate('cmsmart/ajaxcart/view/sidebar.phtml')
				->addItemRender('simple', 'checkout/cart_item_renderer', 'cmsmart/ajaxcart/view/sidebar/default.phtml')
				->addItemRender('grouped', 'checkout/cart_item_renderer', 'cmsmart/ajaxcart/view/sidebar/default.phtml')
				->addItemRender('configurable', 'checkout/cart_item_renderer', 'cmsmart/ajaxcart/view/sidebar/default.phtml');
				
			$json_encode['ajaxcart'] = @$block2->toHtml();
			
		}else{
			$json_encode['ajaxcartmsg'] = $this->getLayout()->getBlock('content')->toHtml();
		}
		echo json_encode($json_encode);
	}

	private function tryaddAction($product, $params = array()){		
			
		$cart = Mage::getModel("checkout/cart");                            
		$cart->addProduct($product->getId(), $params);                          
		return $cart->save();
	}
	
	private function udpateAction($itemId, $qty){
		$qty = (int)$qty?(int)$qty:1;
		$cartHelper = Mage::getSingleton('checkout/cart');
		$cartHelper->updateItem($itemId, $qty);
		//$items = $cartHelper->getCart()->getItems();
//		foreach ($items as $item) {
//			if ($item->getId() == $itemId) {
//				$item->setQty($qty);
//			}
//		}
		$cartHelper->save(); 
		Mage::getSingleton('checkout/session')->setCartWasUpdated(true);                
	}
	
	private function removeAction($itemId){
		try{
			$cartHelper = Mage::helper('checkout/cart');
			$cartHelper->getCart()->removeItem($itemId)->save();
		}catch (Mage_Core_Exception $exception) {}
	}
	
	private function emptyAction(){
		$cartHelper = Mage::helper('checkout/cart');
		$items = $cartHelper->getCart()->getItems();
		foreach ($items as $item) {
			$cartHelper->getCart()->removeItem($item->getItemId());
		}
		$cartHelper->getCart()->save();               
	}
	
	protected function _emptyShoppingCart()
    {
        try {
            $this->_getCart()->truncate()->save();
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $exception) {
            $this->_getSession()->addError($exception->getMessage());
        } catch (Exception $exception) {
            $this->_getSession()->addException($exception, $this->__('Cannot update shopping cart.'));
        }
    }
					 
	public function _getProductFromUrl(){
		Mage::dispatchEvent('catalog_controller_product_init_before', array('controller_action'=>$this));
		$product_id = $this->getRequest()->getParam('product');
		if(!$product_id):
			$path  = $this->getRequest()->getParam('id');
			if(ctype_digit ($path))
				$product_id = (int) $path;
			echo ">>>4 ",$path, " >>>> ",$product_id;
	
			if(!$product_id){
				
				$path[0] == "\/" ? $path = substr($path, 1, strlen($path)) : $path;
				$tableName = Mage::getSingleton('core/resource')->getTableName('core_url_rewrite'); 
				$write = Mage::getSingleton('core/resource')->getConnection('core_write');
				
				$rs = $write->query('select `product_id` from `'.$tableName.'` where `request_path` = "'.$path.'"');
				
				if ($row = $rs->fetch() ) {
					$product_id = $row['product_id'];
				}	
				echo ">>>5 ",$path, " >>>> ",$product_id;
			}
		endif;	

        if (!$product_id) {
            return false;
        }
		
        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($product_id);

        if (!Mage::helper('catalog/product')->canShow($product)) {
            return false;
        }
        if (!in_array(Mage::app()->getStore()->getWebsiteId(), $product->getWebsiteIds())) {
            return false;
        }

        $category = null;
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $product->setCategory($category);
            Mage::register('current_category', $category);
        }
        elseif ($categoryId = Mage::getSingleton('catalog/session')->getLastVisitedCategoryId()) {
            if ($product->canBeShowInCategory($categoryId)) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $product->setCategory($category);
                Mage::register('current_category', $category);
            }
        }
        Mage::register('current_product', $product);
        Mage::register('product', $product);

        try {
            Mage::dispatchEvent('catalog_controller_product_init', array('product'=>$product));
            Mage::dispatchEvent('catalog_controller_product_init_after', array('product'=>$product, 'controller_action' => $this));
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $product;
	}


}

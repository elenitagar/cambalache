<script>
<?php 
    $order = Mage::getSingleton('sales/order');
    $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
    if($_SERVER[SCRIPT_NAME]=="/success.html"): 
?>	
dataLayer = [{
    'transactionId': '<?php echo $order->getIncrementId() ?>', // Transaction ID - Type:String - Required 
    'transactionAffiliation': '<?php echo Mage::app()->getStore()->getName() ?>', // store name - Type:String - Optional to use
    'transactionTotal': <?php echo $order->getGrandTotal() ?>, //total revenue - Type:Numeric - Required
    'transactionTax': <?php echo $order->getTaxAmount() ?>, // Tax amount for transaction - Type:Numeric - Optional to use
    'transactionShipping': <?php echo $order->getShippingAmount() ?>, // Shipping cost - Type:Numeric - Optional to use
    'transactionProducts': [
    <?php  foreach ($order->getAllVisibleItems() as $item): ?>
	{
        'sku': '<?php echo $item->getSku() ?>', // Product SKU - Type:String - Required 
//        'name': '<?php echo $products_array[$i]["name"] ?>', // Product Name - Type:String - Required 
//        'category': '<?php echo $products_array[$i]["category"] ?>', // Product Category - Type:String - Optional to use
//        'price': <?php echo $products_array[$i]["price"] ?>, // Product Price - Type:Numeric - Required 
        'quantity': <?php echo (int)$item->getQty() ?> // Product Quantity - Type:Numeric - Required 
	},
    <?php endforeach; ?>
     ]
    }];
<?php endif; ?>  
</script>

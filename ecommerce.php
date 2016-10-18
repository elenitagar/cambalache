<script>
<?php 
if($_SERVER[SCRIPT_NAME]=="/success.html"):
?>	

dataLayer = [{
    'transactionId': '<?php echo  $transactions["trans_id"] ?>', // Transaction ID - Type:String - Required 
    'transactionAffiliation': '<?php echo  $transactions["store_name"] ?>', // store name - Type:String - Optional to use
    'transactionTotal': <?php echo  $transactions["revenue"] ?>, //total revenue - Type:Numeric - Required
    'transactionTax': <?php echo  $transactions["tax"] ?>, // Tax amount for transaction - Type:Numeric - Optional to use
    'transactionShipping': <?php echo  $transactions["shipping_cost"] ?>, // Shipping cost - Type:Numeric - Optional to use
    'transactionProducts': [
    <?php 
    for ($i=0;$n=sizeof($products_array);$i<$n;$i++):
    ?>
	{
        'sku': '<?php echo $products_array[$i]["sku"] ?>', // Product SKU - Type:String - Required 
        'name': '<?php echo $products_array[$i]["name"] ?>', // Product Name - Type:String - Required 
        'category': '<?php echo $products_array[$i]["category"] ?>', // Product Category - Type:String - Optional to use
        'price': <?php echo $products_array[$i]["price"] ?>, // Product Price - Type:Numeric - Required 
        'quantity': <?php echo $products_array[$i]["quantity"] ?> // Product Quantity - Type:Numeric - Required 
	},
    <?php endfor; ?>
     }]
    }];
<?php endif; ?>  
</script>

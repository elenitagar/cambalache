<script>
<?php 

If($_SERVER[SCRIPT_NAME]=="/success.html") {

?>	

dataLayer = [{
    'transactionId': '<? = $transactions['trans_id'] ?>', // Transaction ID - Type:String - Required 
    'transactionAffiliation': '<? = $transactions['store_name'] ?>', // store name - Type:String - Optional to use
    'transactionTotal': <? = $transactions['revenue'] ?>, //total revenue - Type:Numeric - Required
    'transactionTax': <? = $transactions['tax'] ?>, // Tax amount for transaction - Type:Numeric - Optional to use
    'transactionShipping': <? = $transactions['shipping_cost'] ?>, // Shipping cost - Type:Numeric - Optional to use
    <?php 
    for ($i=0;$n=sizeof($products_array);$i<$n;$i++){
	?>	
     
     'transactionProducts': [{
        'sku': '<? =$products_array[$i]['sku'] ?>', // Product SKU - Type:String - Required 
        'name': '<? =$products_array[$i]['name'] ?>', // Product Name - Type:String - Required 
        'category': '<? =$products_array[$i]['category'] ?>', // Product Category - Type:String - Optional to use
        'price': <? =$products_array[$i]['price'] ?>, // Product Price - Type:Numeric - Required 
        'quantity': <? =$products_array[$i]['quantity'] ?> // Product Quantity - Type:Numeric - Required 
     }]
}];
<?php 
}
?>  
<?php 
}
?>  
</script>

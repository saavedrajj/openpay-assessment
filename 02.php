<?php

include '02-database.php';

$dummyJSON = '[{"BrandID": 153,"BrandName": "TEST1","ProductNr": "0001","StandardDescription": "Product 1","StandardDescriptionID": 402},{"BrandID": 153,"BrandName": "TEST1","ProductNr": "0002","StandardDescription": "Product 2","StandardDescriptionID": 403},{"BrandID": 153,"BrandName": "TEST1","ProductNr": "0003","StandardDescription": "Product 3","StandardDescriptionID": 404},{"BrandID": 153,"BrandName": "TEST1","ProductNr": "0004","StandardDescription": "Product 4","StandardDescriptionID": 405},{"BrandID": 153,"BrandName": "TEST1","ProductNr": "0005","StandardDescription": "Product 5","StandardDescriptionID": 406}]';

 # Convert JSON string to Array
$responseArray = json_decode($dummyJSON, true);

$i = 0;

/* 
* A. For each productNr, read the "unit_price" and "quantity" from a database table called "product_price" and compute the total_price (unit_price * quantity) 
*/
foreach($responseArray as $v) {
	$productNr = $responseArray[$i]["ProductNr"];

	$sql = 'SELECT id, unit_price, quantity FROM product_price WHERE product_nr = ' . $productNr;
    $connA = new mysqli($servername, $username, $password, $dbname);
    if ($connA->connect_error) {
	    die("Connection failed: " . $connA->connect_error);
    }
	$result = $connA->query($sql);

	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$total_price = $row["unit_price"] * $row["quantity"];
			echo "ProductNr: ". $productNr . " => unit_price: " . $row["unit_price"]. " * quantity: " . $row["quantity"] . " = " .$total_price ."<br>";
			/* 
			* B. Persist the data in the JSON response along with total_price in a database table called "products" 
			*/
			$sqlB = 'UPDATE products SET total_price = ' . $total_price . ' WHERE product_nr = "' . $productNr . '"';
			$connB = new mysqli($servername, $username, $password, $dbname);
			if ($connB->connect_error) {
				die("Connection failed: " . $connB->connect_error);
			}
			if ($connB->query($sqlB) === TRUE) {
				echo "Record in table products updated successfully<br>";
			} else {
				echo "Error updating record: " . $connB->error . "<br/>";
			}
		}
	}
	$i++;
}

$connA->close();
$connB->close();
?>

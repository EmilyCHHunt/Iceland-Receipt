<!DOCTYPE html>
<html>
<body>

<?php   // connecting to the databse 
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "iceland";   // changed name 
	$conn = new mysqli($servername, $username, $password, $dbname) ;

//orderpay, item, tran, cust data table used
// SQL integrated to get the wanted data from the database
// Get data linked to the requested orderid

if ($_POST != null) {
    $orderid = (int) $_POST['orderidform'];

// SQL to find orderid 
    $sqlcheckorderid =
        "SELECT orderpay.orderid FROM orderpay
		WHERE orderpay.orderid =" .
        $orderid .
        ";";
    $checkresult = $conn->query($sqlcheckorderid);
    $row = $checkresult->fetch_assoc();
// Select and display 
    if ($checkresult->num_rows > 0) {
        $sql ="
            SELECT iname,quantity, unitprice,quantity * unitprice AS total,cat FROM tran
			INNER JOIN item on item.itemid = tran.itemid
			WHERE item.itemid = tran.itemid AND tran.orderid=" .$orderid .";";

        $refundsql ="
            SELECT orderpay.orderid, cust.name, sum(item.unitprice * tran.quantity) AS refundTotal
				,orderpay.totalpay,sum(item.unitprice * tran.quantity)-orderpay.totalpay AS refund
				,sum(tran.quantity) AS itemtotal
				FROM tran,item,orderpay,cust
			WHERE item.itemid = tran.itemid AND orderpay.orderid = tran.orderid AND cust.custid = orderpay.custid AND orderpay.orderid = " .$orderid ."
			GROUP BY tran.orderid;";

        $deliverysql ="
            SELECT * FROM orderpay
			INNER JOIN cust ON cust.custid = orderpay.custid
			WHERE orderpay.custid = cust.custid AND orderpay.orderid = " .
            $orderid .
            ";";

        $deliveryresult = $conn->query($deliverysql);
        $row = $deliveryresult->fetch_assoc();

// creating the Order Acknowledgement sheet, inserting the iceland image and data
        echo " 
<table style='border: 2px solid;width:35%'>
	<tr>
	<td>
		<table>
			<tr>
				<th style='text-align: left;font-size:41.9px';>Order Acknowledgement</th>
				<th><img align='right' src='iceland.png'></th>
			</tr>
			<tr>
				<td td colspan='4'>
					<hr align = 'center' color ='gray'>
				</td>
			</tr>
			<tr>
				<th style='text-align: left'>Delivery Details</th>
				<th style='text-align: left'>Payment Details</th>
			</tr>
			<tr>
				<td>Order #:" .
				$row['orderid'] .
				"</td>
				<td>Total:   ";
		$result2 = $conn->query($refundsql);
        while ($rowtemp = $result2->fetch_assoc()) {
			if($rowtemp['refund']<0.01){
				echo $row['totalpay']+$rowtemp['refund'];
			}else{
				echo $row['totalpay'];
			}
		}
// Inputing and displaying name, method of payment, email, card, address, date to page
			echo
            "</td>
		</tr>		
		<tr>
			<td>Name:    " .
            $row['name'] .
            "</td>
			<td>Method:  " .
            $row['cardtype'] .
            "</td>
		</tr>
		<tr>
			<td>Email:   " .
            $row['email'] .
            "</td>
			<td>Card:    " .
            $row['cardnum'] .
            "</td>
		</tr>
		<tr>
			<td>Address: " .                
            $row['address'] .
            "</td>
			<td>Date:    " .
            $row['date'] .
            "</td>
		</tr>
		<tr>
			<td td colspan='4'>
				<hr align = 'center' color ='gray'>
			</td>
		</tr>
	</table>
	</td>
</tr>
<tr>
	<td>
	";

        $chilledYet = false;
        $drinksYet = false;
        $foodcupboardYet = false;
        $freshYet = false;
        $frozenYet = false;
        $nonfoodYet = false;

        // set the resulting array to associative
        $result = $conn->query($sql);
        $result2 = $conn->query($refundsql);

        echo "
	<tr>
	<td>
		<table>
		<tr>
		<th style='text-align: left;width:65%;'>Order Details</th>
		</tr>
		  <tr>
			<th style='width:85; font-weight: normal;text-align: left'>Product Details</th>
			<th style='font-weight: normal;width:5%;text-align: left'>Ordered</th> 
			<th style='font-weight: normal;width:5%;text-align: left'>Price</th>
			<th style='font-weight: normal;width:5%;text-align: left'>Total</th>
		  </tr>
		  ";

//Checks each item category suing iteration
        while ($row = $result->fetch_assoc()) {
            if ($row['cat'] == "chilled" && !$chilledYet) {
                $chilledYet = true;
                echo "
			<tr>
				<td><b>Chilled</b></td>
			</tr>";
            } elseif ($row['cat'] == "drinks" && !$drinksYet) {
                $drinksYet = true;
                echo "
			<tr>
				<td><b>Drinks</b></td>
			</tr>";
            } elseif ($row['cat'] == "foodcupboard" && !$foodcupboardYet) {
                $foodcupboardYet = true;
                echo "
			<tr>
				<td><b>foodcupboard</b></td>
			</tr>";
            } elseif ($row['cat'] == "fresh" && !$freshYet) {
                $freshYet = true;
                echo "
			<tr>
				<td><b>fresh</b></td>
			</tr>";
            } elseif ($row['cat'] == "frozen" && !$frozenYet) {
                $frozenYet = true;
                echo "
			<tr>
				<td><b>frozen</b></td>
			</tr>";
            } elseif ($row['cat'] == "nonfood" && !$nonfoodYet) {
                $nonfoodYet = true;
                echo "
			<tr>
				<td><b>nonfood</b></td>
			</tr>";
            }
            echo "
			<tr>
				<td>" .
                $row['iname'] .
                "</td>
				<td>" .
                $row['quantity'] .
                "</td>
				<td>£" .
                $row['unitprice'] .
                "</td >
				<td>£" .
                $row['total'] .
                "</td>
			</tr>";
        }
        while ($row = $result2->fetch_assoc()) {

        	//Check for discount (customer paid less)

            if ($row['refund'] >= 0.001) {
                echo "
		<tr>
			<td><b>Grand Total</td>
			<td>".$row['itemtotal']."</td>
			<td></td>
			<td><b>£" .$row['refundTotal'].
            "</td>
		</tr>
			<tr>
				<td><b>Discount</td>
				<td></td>
				<td></td>
				<td><b>£".$row['refund']."</td>
			</tr>";

			// Check redund (customer paid more)
            } elseif ($row['refund'] <= -0.001) {
                echo "
		<tr>
			<td><b>Grand Total</td>
			<td>".$row['itemtotal']."</td>
			<td></td>
			<td><b>£" .$row['refundTotal']-$row['refund']."</td>
		</tr>
			<tr>
				<td><b>Refund</td>
				<td></td>
				<td></td>
				<td><b>£" .-$row['refund'] .
                    "</td>
			</tr>";
            }else{
				echo"
				<tr>
					<td><b>Grand Total</td>
					<td>".$row['itemtotal']."</td>
					<td></td>
					<td><b>£" .$row['refundTotal']."</td>
				</tr>";
			
			}
        }
		echo"
		<tr align='center';>
			<td colspan='4' style='background-color:#cccccc;'>
				Lines are open Monday to Saturday 6am-10pm and Sunday 8am-10pm<br>
				<img src='paypal.png'>
				<img src='mastervisa.png'>
			</td>
		</tr>
		<tr>
			<td td colspan='4'>
				<hr align = 'center' color ='gray'>
			</td>
		</tr>";
        $conn = null;
    }
}
echo "
	<tr>
		<td td colspan='3';style='width:100';>
			<form action='iceland.php' method='post'>
				Query Order#:
			   <input type='text' name='orderidform' value='' />
			   <input style='float:right' type='submit' name='submit_button' value='Submit' />
			</form> 
			</td>
	</tr>
	</table>
</td>
</tr>
</table>";
?>
</body>
</html>
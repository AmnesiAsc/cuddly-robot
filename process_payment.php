<?php
if(!isset($GLOBALS["make_payment"])){
	header("location:home.php");
}
require "send_email.phpscr";

$cType = $GLOBALS["card_type"];
$cNum = $GLOBALS["card_num"];
$cExp = $GLOBALS["card_exp"];
$cCvv2 = $GLOBALS["card_cvv2"];

$bFName = $GLOBALS["bill_first_name"];
$bLName = $GLOBALS["bill_last_name"];
$bState = $GLOBALS["bill_state"];
$bCity = $GLOBALS["bill_city"];
$bAddr = $GLOBALS["bill_address"];
$bZip = $GLOBALS["bill_zip"];

$total = $GLOBALS["bill_total"];
$desc = $GLOBALS["bill_desc"];

$requestParams = array(
	"IPADDRESS" => $_SERVER["REMOTE_ADDR"],
	"PAYMENTACTION" => "Sale"
);

$creditCardDetails = array(
	"CREDITCARDDTYPE" => $cType,
	"ACCT" => $cNum,
	"EXPDATE" => $cExp,
	"CVV2" => $cCvv2
);

$payerDetails = array(
	"FIRSTNAME" => $bFName,
	"LASTNAME" => $bLName,
	"COUNTRYCODE" => "US",
	"STATE" => $bState,
	"CITY" => $bCity,
	"STREET" => $bAddr,
	"ZIP" => $bZip
);

$orderParams = array(
	"AMT" => $total,
	"ITEMAMT" => $total,
	"SHIPPINGAMT" => "0",
	"CURRENCYCODE" => "USD"
);

$item = array(
	"L_NAME0" => "Venango Music Together",
	"L_DESC0" => $desc,
	"L_AMT0" => $total,
	"L_QTY0" => "1"
);

$paypal = new Paypal();
$response = $paypal -> request("DoDirectPayment",
	$requestParams + $creditCardDetails + $payerDetails + $orderParams + $item
);

if( is_array($response) && $response["ACK"] == "Success"){ //Payment successful
	$transactionId = $response["TRANSACTIONID"];
	if($response["ACK"] == "Success"){
		$responseId = 0;
	}else if($response["ACK"] == "SuccessWithWarning"){
		$responseId = 1;
	}else if($response["ACK"] == "Failure"){
		$responseId = 2;
	}else if($response["ACK"] == "FailureWithWarning"){
		$responseId = 3;
	}else{
		$responseId = 3;
		$addlError = "Unknown Response (" . $response["ACK"] . ")";
	}
	
	$defaultErr = "Unable to log payment, please contact <a href='lacey@laceym.com'>Lacey@laceym.com</a> with RESPONSEID=$responseId";
	$logEntry = date("G:i:s Ymd") . $response["ACK"] . " " . $bFName . " " . $bLName . " [$" . $total . "] " . $desc . PHP_EOL;
	//Log payment
	$fileLog = fopen("payment_log.txt", "a")or die($defaultErr);
	fwrite($fileLog,$logEntry);
	$fclose($fileLog);
	//Email
	$emailMessage = "Payment attempted from $bFName $bLName for $total.\r\nThis payment was " .
		$response["ACK"] . ".\r\n$desc\r\n<a href='localhost/payment_log.txt'>View Payment Log</a>";
	if( !send_email( "lacey@laceym.com", "noreply@laceym.com", "Payment Manager", "Payment " . $response["ACK"], $emailMessage)){
		//problem sending email
	}
}

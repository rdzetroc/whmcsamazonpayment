<?php
session_start();
# Required File Includes
if (file_exists("../../../dbconnect.php")){
	include("../../../dbconnect.php");
} else {
	include("../../../init.php");
}

include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
require_once('../PayWithAmazon/Client.php');

$gatewaymodule = "amazonpayment"; # Enter your gateway module name here replacing template

try {
	  $gatewayVariables = getGatewayVariables($gatewaymodule);
      $varPresent = 1;	
} catch (\Exception $e) {
	
}

if (is_numeric($varPresent)) {
	
	$amazonConfig = array('merchant_id' => $gatewayVariables['merchantid'],
                      'access_key' => $gatewayVariables['accesskey'],
					  'secret_key' => $gatewayVariables['secretkey'],
					  'client_id' => $gatewayVariables['clientid'],
					  'region' => $gatewayVariables['region']);
					  
	$amazonClient = new PayWithAmazon\Client($amazonConfig);

	  if ($gatewayVariables['sandbox'] === 'on') {
		
		$amazonClient->setSandbox(true);
		
		switch ($getGatewayVariables['region']) {
			case 'us':
				$endpointurl = "<script type='text/javascript' src='https://static-na.payments-amazon.com/OffAmazonPayments/us/sandbox/js/Widgets.js'></script>";
			 break;
			
			case 'uk':
			    $endpointurl = "<script type='text/javascript' src='https://static-eu.payments-amazon.com/OffAmazonPayments/uk/sandbox/js/Widgets.js'></script>";
			 break;
			
			case 'de':
				$endpointurl = "<script type='text/javascript' src='https://static-eu.payments-amazon.com/OffAmazonPayments/uk/sandbox/js/Widgets.js'></script>";
			 break;	
			
			case 'jp':
				$endpointurl = "<script type='text/javascript' src='https://origin-na.ssl-images-amazon.com/images/G/09/EP/offAmazonPayments/sandbox/prod/lpa/js/Widgets.js'></script>";
			 break;
			 
			default:
				$endpointurl = "<script type='text/javascript' src='https://static-na.payments-amazon.com/OffAmazonPayments/us/sandbox/js/Widgets.js'></script>";
		     break;
		}
	  } else 
      {
		switch ($getGatewayVariables['region']) {
			case 'us':
				$endpointurl = "<script type='text/javascript' src='https://static-na.payments-amazon.com/OffAmazonPayments/us/js/Widgets.js'></script>";
			 break;
			
			case 'uk':
			    $endpointurl = "<script type='text/javascript' src='https://static-eu.payments-amazon.com/OffAmazonPayments/uk/js/Widgets.js'></script>";
			 break;
			
			case 'de':
				$endpointurl = "<script type='text/javascript' src='https://static-eu.payments-amazon.com/OffAmazonPayments/uk/js/Widgets.js'></script>";
			 break;	
			
			case 'jp':
				$endpointurl = "<script type='text/javascript' src='https://origin-na.ssl-images-amazon.com/images/G/09/EP/offAmazonPayments/prod/lpa/js/Widgets.js'></script>";
			 break;
			 
			default:
				$endpointurl = "<script type='text/javascript' src='https://static-na.payments-amazon.com/OffAmazonPayments/us/sandbox/js/Widgets.js'></script>";
		     break;
		}		  
	  }		  
	  
$code = '<html><header>
<style type="text/css">
 #LayerOver {
    opacity:100;
    filter: alpha(opacity=50);
    background-color:#fff; 
    width:100%; 
    height:50%; 
    z-index:10;
    top:200; 
    left:0; 
    position:fixed; 
  }
  #addressBookWidgetDiv {display: none;
  }
  
  #walletWidgetDiv {display: none;
  }
  
</style></header><body>';

$code .= '<div id="addressBookWidgetDiv" style="width:400px; height:240px;"></div>
<div id="walletWidgetDiv" style="width:400px; height:240px;"></div><div id="LayerOver" align="center"><img src="http://media.giphy.com/media/LN31zBaVbUzDO/giphy.gif"></div>';

$code .= '<script type=\'text/javascript\'>
    window.onAmazonLoginReady = function () {
        amazon.Login.setClientId(\''.$gatewayVariables['clientid'].'\');
    };
</script>';

$code .= $endpointurl;

$code .= '<script type="text/javascript">
    new OffAmazonPayments.Widgets.AddressBook({
        sellerId: \''.$gatewayVariables['merchantid'].'\',
        onOrderReferenceCreate: function (orderReference) {
           orderReferenceId = orderReference.getAmazonOrderReferenceId();
		   window.location = window.location.href + "&AmazonOrderReferenceId=" + orderReferenceId;
        },
        onAddressSelect: function () {
            // do stuff here like recalculate tax and/or shipping
			
        },
        design: {
            designMode: \'responsive\'
        },
        onError: function (error) {
            // your error handling code
        }
    }).bind("addressBookWidgetDiv");

    new OffAmazonPayments.Widgets.Wallet({
        sellerId: \''.$gatewayVariables['merchantid'].'\',
        onPaymentSelect: function () {
        },
        design: {
            designMode: \'responsive\'
        },
        onError: function (error) {
            // your error handling code
        }
    }).bind("walletWidgetDiv");
</script>';

$code .= '</body></html>';

if (!isset($_REQUEST['AmazonOrderReferenceId'])){
    echo $code;
	exit();
}


$requestParameters = array();
$gAmazonOrderReferenceId = $_REQUEST['AmazonOrderReferenceId'];
$invoiceDetails = explode('::', urldecode(base64_decode($_REQUEST['trd'])));

// Create the parameters array to set the order
$requestParameters['amazon_order_reference_id'] = $gAmazonOrderReferenceId;
$requestParameters['amount']            = $invoiceDetails[1];
$requestParameters['currency_code']     = 'USD';
$requestParameters['seller_note']   = $invoiceDetails[2];
$requestParameters['seller_order_id']   = $invoiceDetails[0];
$requestParameters['store_name']        = $invoiceDetails[3];

// Set the Order details by making the SetOrderReferenceDetails API call
$response = $amazonClient->SetOrderReferenceDetails($requestParameters);

// If the API call was a success Get the Order Details by making the GetOrderReferenceDetails API call
if($amazonClient->success)
{
    $requestParameters['address_consent_token'] = null;
    $response = $amazonClient->GetOrderReferenceDetails($requestParameters);
}
// Pretty print the Json and then echo it for the Ajax success to take in
$json = json_decode($response->toJson());

// Confirm the order by making the ConfirmOrderReference API call
$response = $amazonClient->confirmOrderReference($requestParameters);

$responsearray['confirm'] = json_decode($response->toJson());


// If the API call was a success make the Authorize API call


if($amazonClient->success)
{
    $requestParameters['authorization_amount'] = $invoiceDetails[1];
    $requestParameters['authorization_reference_id'] = md5($gAmazonOrderReferenceId);
    $requestParameters['seller_authorization_note'] = 'Authorizing payment';
	//$requestParameters['capture_now'] = "true";
    $requestParameters['transaction_timeout'] = 0;

    $response = $amazonClient->authorize($requestParameters);
    $responsearray['authorize'] = json_decode($response->toJson());
}

// If the Authorize API call was a success, make the Capture API call when you are ready to capture for the order (for example when the order has been dispatched)
if($amazonClient->success)
{
    $authorizationResponse = json_decode($response->toJson(),true);
	$requestParameters['amazon_authorization_id'] = $authorizationResponse['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'];
    $requestParameters['capture_amount'] = $invoiceDetails[1];;
    $requestParameters['currency_code'] = 'USD';
    $requestParameters['capture_reference_id'] = md5($authorizationResponse['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId']);

    $response = $amazonClient->capture($requestParameters);
    $responsearray['capture'] = json_decode($response->toJson());
	
}

// Echo the Json encoded array for the Ajax success



$GATEWAY = getGatewayVariables($gatewaymodule);

if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback

# Get Returned Variables - Adjust for Post Variable Names from your Gateway's Documentation

$responseCapture = json_decode($response->toJson(),true);

$status = $responseCapture['CaptureResult']['CaptureDetails']['CaptureStatus']['State'];

$invoiceid = $invoiceDetails[0];
$transid = $gAmazonOrderReferenceId;
$amount = $responseCapture['CaptureResult']['CaptureDetails']['CaptureAmount']['Amount'];
$fee = $responseCapture['CaptureResult']['CaptureDetails']['CaptureFee']['Amount'];

$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does

if ($status=="Completed") {
    # Successful
    addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
	logTransaction($GATEWAY["name"],$responseCapture,"Successful"); # Save to Gateway Log: name, data array, status
	echo "<script type='text/javascript'>window.location = '".$invoiceDetails[4]."';</script>";
} else {
	# Unsuccessful
    logTransaction($GATEWAY["name"],$responseCapture,"Unsuccessful"); # Save to Gateway Log: name, data array, status
 }
}
session_destroy();
?>
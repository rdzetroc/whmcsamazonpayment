<?php
require_once("PayWithAmazon/Client.php");

try {
	  $gatewayVariables = getGatewayVariables('amazonpayment');
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
	
    if ($gatewayVariables['sandbox'] == 'on') {
		$amazonClient->setSandbox(true);
	} 					  


}

function amazonpayment_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value" => "Amazon Payment"),
     "merchantid" => array("FriendlyName" => "Merchant ID", "Type" => "text", "Size" => "20", ),
     "accesskey" => array("FriendlyName" => "Access Key", "Type" => "text", "Size" => "20", ),
	 "secretkey" => array("FriendlyName" => "Secret Key", "Type" => "text", "Size" => "20", ),
	 "clientid" => array("FriendlyName" => "Client ID", "Type" => "text", "Size" => "20", ),
	 "allowedreturnurl" => array("FriendlyName" => "Allowed Return URL", "Type" => "text", "Size" => "20", ),
	 "region" => array("FriendlyName" => "Region", "Type" => "dropdown", "Options" => "us,uk,de,jp", ),
     "sandbox" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Enable sandbox", ),
    );
	return $configarray;
}

function amazonpayment_link($params) {

	# Gateway Specific Variables
	$gatewaymerchantid = $params['merchantid'];
	$gatewayaccesskey = $params['accesskey'];
	$gatewaysecretkey = $params['secretkey'];
	$gatewayclientid = $params['clientid'];
	$gatewayallowedreturnurl = $params['allowedreturnurl'];
	$gatewaytestmode = $params['testmode'];
	$gatewayendpointurl = $params['endpointurl'];

	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
    $amount = $params['amount']; # Format: ##.##
    $currency = $params['currency']; # Currency Code

	# Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	# System Variables
	$companyname = $params['companyname'];
	$systemurl = $params['systemurl'];
	$currency = $params['currency'];
    $returnToInvoiceUrl = $params['systemurl'].'/viewinvoice.php?id='.$invoiceid;
	$transdetails = urlencode(base64_encode($invoiceid.'::'.$amount.'::'.$description.'::'.$companyname.'::'.$returnToInvoiceUrl));
	
	$getGatewayVariables = getGatewayVariables('amazonpayment');
	
	if (!$getGatewayVariables["type"]) die("Module Not Activated");
	  
	  if ($getGatewayVariables['sandbox'] === 'on') {
		
		switch ($getGatewayVariables['region']) {
			case 'us':
				$endpointurl = "<script type='text/javascript' src='https://static-na.payments-amazon.com/OffAmazonPayments/us/sandbox/js/Widgets.js'></script>";
			 break;
			
			case 'uk':
			    $endpointurl = "<script type='text/javascript' src='https://static-eu.payments-amazon.com/OffAmazonPayments/uk/sandbox/lpa/js/Widgets.js'></script>";
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
				$endpointurl = "<script type='text/javascript' src='https://static-na.payments-amazon.com/OffAmazonPayments/us/js/Widgets.js'></script>";
		     break;
		}		  
	  }		  

	//return $code;
	$code1 = '<a href="#" id="LoginWithAmazon">
  <img border="0" alt="Login with Amazon"
    src="https://images-na.ssl-images-amazon.com/images/G/01/lwa/btnLWA_gold_156x32.png"
    width="156" height="32" />
  </a>';
  
    $code1 .= '<div id="amazon-root"></div>
<script type="text/javascript">

  window.onAmazonLoginReady = function() {
    amazon.Login.setClientId(\''.$gatewayclientid.'\');
  };
  (function(d) {
    var a = d.createElement(\'script\'); a.type = \'text/javascript\';
    a.async = true; a.id = \'amazon-login-sdk\';
    a.src = \'https://api-cdn.amazon.com/sdk/login1.js\';
    d.getElementById(\'amazon-root\').appendChild(a);
  })(document);

</script>';

	$code1 .= '<script type=\'text/javascript\'>
    window.onAmazonLoginReady = function () {
        amazon.Login.setClientId(\''.$gatewayclientid.'\');
    };
</script>';

	//$code1 .= $sandboxurl;

    $code1 .= '<script type="text/javascript">

  document.getElementById(\'LoginWithAmazon\').onclick = function() {
    options = { scope : \'profile\' };
    amazon.Login.authorize(options, \''.$gatewayallowedreturnurl.'\');
    return false;
  };

</script>';

	$code = '<div id="AmazonLoginButton"></div>';
	$code .= '<script type=\'text/javascript\'>
    window.onAmazonLoginReady = function () {
        amazon.Login.setClientId(\''.$gatewayclientid.'\');
    };
</script>';

	$code .= $endpointurl;
	$code .= '<script type=\'text/javascript\'>
    var authRequest;
    OffAmazonPayments.Button("AmazonLoginButton", "'.$gatewaymerchantid.'", {
        type: "PwA",
        color: "Gold",
        authorization: function () {
            loginOptions = { scope: "profile postal_code payments:widget payments:shipping_address", popup: true };
            authRequest = amazon.Login.authorize(loginOptions, "'.$gatewayallowedreturnurl.'?trd='.$transdetails.'");
        },
        onError: function (error) {
            // something bad happened
        }
    });
</script>';

  return $code;
}

?>
 <?php

// tesing file
// see https://developers.paysera.com/en/payments/current

require_once('WebToPay.php');

function get_self_url()
{
    $s = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0,
                strpos($_SERVER['SERVER_PROTOCOL'], '/'));

    if (!empty($_SERVER["HTTPS"]))
	{
        $s .= ($_SERVER["HTTPS"] == "on") ? "s" : "";
    }

    $s .= '://'.$_SERVER['HTTP_HOST'];

    if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80')
	{
        $s .= ':'.$_SERVER['SERVER_PORT'];
    }

    $s .= dirname($_SERVER['SCRIPT_NAME']);

    return $s;
}

try
{
	$self_url = get_self_url();

	$projectid = 87983; // kvanto id
	$orderid = 1;
	$amount = 10000; // in cents
	$sign_password = '20cc4475972ff03c612b1720ac61098d'; // kvanto id
	$accepturl = $self_url.'/accept.php';
	$cancelurl = $self_url.'/cancel.php';
	$callbackurl = $self_url.'/callback.php';

	$request = WebToPay::redirectToPayment(array(
		'projectid'     => $projectid,
		'sign_password' => $sign_password,
		'orderid'       => $orderid,
		'amount'        => $amount,
		'currency'      => 'EUR',
		'lang'     		=> 'ENG', // LIT, LAV, EST, RUS, ENG, GER, POL
		'country'       => 'LT',
		'accepturl'     => $accepturl,
		'cancelurl'     => $cancelurl,
		'callbackurl'   => $callbackurl,
		'test'          => 1, // 0 off, 1 test on
    ));
}
catch (WebToPayException $e)
{
    // handle exception
}

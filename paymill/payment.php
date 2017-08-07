<?php 

/**********

MUST GO INTO A SEPARATE q2apro page, e.g. /paymill

**********/


	// echo implode(' | ',$_POST);
	// amount, currency, token*, usermail
	$usermail = trim($_POST['usermail']);
	$username = trim($_POST['username']);
?><!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="content-type"
              content="text/html; charset=utf-8"/>
       <?php
       //
       // Please download the Paymill PHP Wrapper using composer.
       // If you don't already use Composer,
       // then you probably should read the installation guide http://getcomposer.org/download/.
       //

       // Change the following constants
       define('PAYMILL_API_KEY', '996f932dc5916801a56fcabfee5cb091'); // private TEST key
	   
       // define('PAYMILL_API_KEY', '2a4283ad242ccc3000600813666a1641'); // private key LIVE
	   
       // define('CUSTOMER_EMAIL', 'service@matheretter.de'); // dev
       define('CUSTOMER_EMAIL', $usermail); // appears at least in admin panel
       require './php/autoload.php';

       if (isset($_POST['paymillToken']))
	   {
            $service = new Paymill\Request(PAYMILL_API_KEY);
            $client = new Paymill\Models\Request\Client();
            $payment = new Paymill\Models\Request\Payment();
            $transaction = new \Paymill\Models\Request\Transaction();

			$successpay = false;
			// https://github.com/paymill/paymill-php
            try {
                $client->setEmail(CUSTOMER_EMAIL);
                $client->setDescription('Kunde: '.$username); // appears at least in admin panel
                $clientResponse = $service->create($client);

                $payment->setToken($_POST['paymillToken']);
                $payment->setClient($clientResponse->getId());
                $paymentResponse = $service->create($payment);

                $transaction->setPayment($paymentResponse->getId());
                $transaction->setAmount($_POST['amount'] * 100);
                $transaction->setCurrency($_POST['currency']);
                $transaction->setDescription('Lernzugang bei Matheretter'); // appears at least in admin panel
                $transactionResponse = $service->create($transaction);

                $title = "<h1>Vielen Dank für deine Zahlung!</h1>";
				
				// see paymill-php\lib\Paymill\Models\Response\Transaction.php
				/*
				echo $transactionResponse->getOriginAmount(); // e.g. 7491 (in euro cents)
				echo '<br />';
				echo $transactionResponse->getStatus(); // closed
				echo '<br />';
				echo $transactionResponse->getResponseCode(); // 20000 is success
				echo '<br />';
				*/
				// object: echo $transactionResponse->getPayment(); //
				// echo '<br />';
				// object: echo $transactionResponse->getClient(); // 
				// echo '<br />';
				// echo implode(', ',$transactionResponse->getFees()); // empty Array
				// echo '<br />';
				// echo $transactionResponse->getSource(); // was empty
				// echo '<br />';
				// echo $transactionResponse->getMandateReference(); // was empty
				
				if( $transactionResponse->getResponseCode() == '20000')
				{
					$successpay = true;
				}
				else
				{
					echo 'Es ist ein unerwarteter Zahlungsstatus aufgetreten. Bitte kontaktieren Sie uns kurz: info@matheretter.de';
					$successpay = false;
				}
				
                // $result = print_r($transactionResponse, true);
            }
			catch (\Paymill\Services\PaymillException $e)
			{
                $errormsg = "<p>Zahlungsfehler aufgetreten. Code: ".($e->getResponseCode()).' | '.($e->getErrorMessage()).'</p>'; 
				error_log( 'Code: '.($e->getResponseCode()).' | '.($e->getErrorMessage()) ); // for tracing in production mode! ***
                // $result = print_r($e->getResponseCode(), true) ." <br />" . print_r($e->getResponseCode(), true) ." <br />" .print_r($e->getErrorMessage(), true);
				$successpay = false;
            }

			if($successpay)
			{
				reg_paymentsuccess($usermail);
			}
			else
			{
				reg_paymentfailed($usermail);
			}
       } // end isset($_POST['paymillToken'])



function reg_paymentsuccess($usermail)
{
	// 
	
	// CONNECT TO DATABASE
	require_once('../../../tools/zdb/config-5.php');
	$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
	mysqli_set_charset($db, 'utf8');
	mysqli_select_db($db, DB_NAME);
	
	$today = date('Y-m-d');
	
	$query = mysqli_query($db, 'SELECT username,abotime FROM `customers` 
							WHERE `usermail` = "'.mysqli_real_escape_string($db, $usermail).'"
							;');
	$row = mysqli_fetch_array($query);
			
	// set datepay
	$query_paid = 'UPDATE `customers` SET datepay = "'.mysqli_real_escape_string($db, $today).'" 
					WHERE `usermail` = "'.mysqli_real_escape_string($db, $usermail).'"
					;';  
	mysqli_query($db, $query_paid);
	
	// set dateAboEnd
	// calculate Laufzeit
	$paydate = date($today);
	$abotime = $row['abotime'];
	$laufzeitTage = 0;
	if($abotime == "1") {
		$laufzeitTage = 31; // 1*31
	}
	else if($abotime == "3") {
		$laufzeitTage = 91; // 31+30+31
	}
	else if($abotime == "6") {
		$laufzeitTage = 183; // former: 365/2 = 183 + bonus 2 wochen = 197 Tage
	}
	else if($abotime == "12") {
		$laufzeitTage = 365; // former: 365 Tage + bonus 4 wochen = 396 Tage
	}
	$endDateT = strtotime(date('Y-m-d', strtotime($today)) . " +".$laufzeitTage." days");
	$endDate = date('Y-m-d', $endDateT);

	$query_date = 'UPDATE `customers` SET dateAboEnd = "'.mysqli_real_escape_string($db, $endDate).'" 
					WHERE `usermail` = "'.mysqli_real_escape_string($db, $usermail).'"
					;';  
	mysqli_query($db, $query_date);
	
	// in case of extend, remove flag
	$query_update = "UPDATE `customers` SET 
						aboChange = NULL 
						WHERE `usermail` = '".mysqli_real_escape_string($db, $usermail)."';";  
	mysqli_query($db, $query_update);
	
	// set paymethod
	$query_paymethod = 'UPDATE `customers` SET 
						payment = "kreditkarte"
						WHERE `usermail` = "'.mysqli_real_escape_string($db, $usermail).'" 
						';  
	mysqli_query($db, $query_paymethod);
	
	
	$dateAboEnd = date_create($endDate);
	$dateAboEnd = date_format($dateAboEnd, 'd.m.Y');

	$dateAboStart = date_create($today);
	$dateAboStart = date_format($dateAboStart, 'd.m.Y');

	// Confirmation Mail to NEW Customer
	$header = "From: Matheretter <service@matheretter.de>\n";
	$header .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8;\nContent-Transfer-Encoding: 8bit";
	$message = "Hallo ".$row['username']."!\n\nDeine Zahlung ist bei uns eingegangen. Vielen Dank.\n\nDein ".$abotime."-Monats-Lernzugang ist nun aktiviert.\n";
	$message .= "Laufzeit: ". $dateAboStart . " bis " . $dateAboEnd . "\n\n";	
	$message .= "Du kannst Dich jetzt im geschützten Bereich einloggen: \nhttp://www.matheretter.de/zugang/login/";
	$message .= "\n\nWir wünschen Dir viel Erfolg und gute Noten in Mathematik!\n\nLiebe Grüße,\nMatheretter";
	mail($usermail, 'Dein Lernzugang wurde aktiviert', $message, $header);
	// copy to admin
	mail('service@matheretter.de', 'Lernzugang via Paymill aktiviert', "Client: ".$usermail."\n\n\n".$message, $header);

	echo '
<!DOCTYPE html>
<html lang="de">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="content-language" content="de,ch,at" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="description" content="Zahlung erfolgreich" />
	<meta name="copyright" content="Matheretter" />
	<meta name="author" content="Matheretter" />
	<meta name="distribution" content="global" />
	<meta name="language" content="deutsch,de,at,ch" />
	<meta name="expires" content="never" />
	<meta name="robots" content="noindex,nofollow" />

	<title>Zahlung erfolgreich | Matheretter</title>

	<link rel="shortcut icon" href="http://www.matheretter.de/favicon.ico" />
	<link rel="image_src" href="http://www.matheretter.de/template/logo/logo.png" />
	<link rel="apple-touch-icon" href="http://www.matheretter.de/template/logo/logo-apple-57x57.png" />
	
	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:400" type="text/css" />
	<link rel="stylesheet" type="text/css" href="http://www.matheretter.de/template/styles.new.css?v=<?php echo $cssjs_v; ?>" />

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js" type="text/javascript"></script>
		<script type="text/javascript">window.jQuery || document.write(\'<script src="http://www.matheretter.de/tools/jquery.min.js"><\/script>\')</script>
	<script src="http://www.matheretter.de/tools/matheretter.min.js?v=<?php echo $cssjs_v; ?>" type="text/javascript"></script>
	
	<style type="text/css">
		#startmain {
			margin:80px 0 0 0;
			min-height:500px;
			font-size:14px;
		}
		#startmain h1 {
			font-size:20px;
			margin-bottom:40px;
		}
	</style>
</head>

<body>

	<div id="outer-wrapper">
		<div id="main-content">

			<div id="startmain">

			<h1>Deine Zahlung ist bei uns eingegangen. Vielen Dank!</h1>
			<p style="padding-bottom:15px;">Wir haben Dir eine Bestätigungsmail gesendet.</p>
			<p style="padding-bottom:15px;">Du kannst nun auf alle Mathematik-Videos und Lernprogramme zugreifen. Viel Spaß dabei!</p>
			<a class="buttonb" href="http://www.matheretter.de/zugang/login/">Jetzt einloggen</a>

			</div> <!-- startmain -->

		</div> <!-- main-content -->
	</div> <!-- outer-wrapper -->

</body>
</html>
';
	
} // end reg_paymentsuccess($usermail);


function reg_paymentfailed($usermail) {

$paylink = 'http://www.matheretter.de/zugang/bestellung/mail_pay.php?uid='.$usermail;
$paypaylink = 'http://www.matheretter.de/zugang/bestellung/mail_pay.php?uid='.$usermail.'&paypal=1';

echo '<!DOCTYPE html>
<html lang="de">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="content-language" content="de,ch,at" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="description" content="Zahlung fehlgeschlagen" />
	<meta name="copyright" content="Matheretter" />
	<meta name="author" content="Matheretter" />
	<meta name="distribution" content="global" />
	<meta name="language" content="deutsch,de,at,ch" />
	<meta name="expires" content="never" />
	<meta name="robots" content="noindex,nofollow" />

	<title>Zahlung fehlgeschlagen | Matheretter</title>

	<link rel="shortcut icon" href="http://www.matheretter.de/favicon.ico" />
	<link rel="image_src" href="http://www.matheretter.de/template/logo/logo.png" />
	<link rel="apple-touch-icon" href="http://www.matheretter.de/template/logo/logo-apple-57x57.png" />
	
	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:400" type="text/css" />
	<link rel="stylesheet" type="text/css" href="http://www.matheretter.de/template/styles.new.css?v=<?php echo $cssjs_v; ?>" />

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js" type="text/javascript"></script>
		<script type="text/javascript">window.jQuery || document.write(\'<script src="http://www.matheretter.de/tools/jquery.min.js"><\/script>\')</script>
	<script src="http://www.matheretter.de/tools/matheretter.min.js?v=<?php echo $cssjs_v; ?>" type="text/javascript"></script>
	
	<style type="text/css">
		#startmain {
			margin:80px 0 0 0;
			min-height:500px;
			font-size:14px;
		}
		#startmain h1 {
			font-size:20px;
			margin-bottom:40px;
		}
		p.inform {
			margin:30px 0 10px 0;
		}
	</style>
</head>

<body>

	<div id="outer-wrapper">
		<div id="main-content">

			<div id="startmain">

				<h1>Zahlung leider nicht erfolgt</h1>
				<p>Deine Zahlung wurde leider nicht ausgeführt. Es ist ein Fehler aufgetreten.</p>
				
				<p class="inform">Bitte wähle:</p>
				<p class="inform" style="font-weight:bold;">1. Zahlung erneut versuchen:</p>
				<a class="buttonb" style="margin-top:0;" href="'.$paylink.'">Zahlung mit Sofortüberweisung</a> 
				
				<p class="inform" style="font-weight:bold;">2. Zahlung mit PayPal:</p>
				<a class="buttonb" style="margin-top:0;" href="'.$paypaylink.'">Zahlung mit PayPal</a> 
				
				<p class="inform" style="font-weight:bold;">3. Zahlung per Banküberweisung an:</p>
				<p>Empfänger: Matheretter</p>
				<p>IBAN: DE07100700240286564000</p>
				<p>BIC: DEUTDEDBBER</p>
				<p>Deutsche Bank</p>

				<p class="inform">Liegt ein anderes Problem vor, dann <a href="http://www.matheretter.de/kontakt">kontaktiere uns</a> bitte. Wir helfen dir gerne weiter.</p>
			</div>

		</div>
	</div>
	
</body>
</html>
';
} // end reg_paymentfailed($usermail)


// echo 'transaction: <br />'.$result;

?>

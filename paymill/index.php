<?php

	header('Content-type: text/html; charset=utf-8');
	
	// css js version number, brings variable: $cssjs_v = '0.0.x';
	include('../../../template/version.php');

	// can be KREDITKARTE or LASTSCHRIFT
	// slightly different forms are output 
	// $payment = strip_tags($_GET['payment']);
	// $payment = "lastschrift";
	
	$usermail = trim(strip_tags($_GET['usermail']));
	if(empty($usermail))
	{
		exit();
	}
	$usermail = str_replace(' ', '', $usermail);
	$usermail = strtolower($usermail);	
	if(!preg_match("/^[_a-zA-Z0-9-](\.{0,1}[_a-zA-Z0-9-])*@([a-zA-Z0-9-]{2,}\.){0,}[a-zA-Z0-9-]{2,}(\.[a-zA-Z]{2,4}){1,2}$/", $usermail))
	{
		echo 'E-Mail-Adresse ist ungültig.';
		return;
	}

	/*
	$paysum = strip_tags($_GET['paysum']); // should do without - manipulation possible, should READ db instead (!)
	$paypurpose = strip_tags($_GET['paypurpose']); 
	$purposesub = $usermail; // str_replace("@","+",$usermail);
	*/

	// CONNECT TO DATABASE
	require_once('../../../tools/zdb/config-5.php');
	$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
	mysqli_set_charset($db, 'utf8');
	mysqli_select_db($db, DB_NAME);
	
	
	$user_query = mysqli_query($db, 'SELECT username,abotime,paysum,payment FROM `customers` 
										WHERE `usermail` = "'.mysqli_real_escape_string($db, $usermail).'"
									;');
	$row = mysqli_fetch_array($user_query);
	if(empty($row)) {
		exit();		
	}
	$paysum = $row['paysum'];
	$username = $row['username'];
	$abotime = $row['abotime'];
	$paysum = $row['paysum'];
	$payment = $row['payment'];
	
	// default if other value set
	if($payment!='kreditkarte' && $payment!='lastschrift') {
		$payment='kreditkarte';
	}
	
	$paypurpose = $row['abotime']."-Monats-Lernzugang ".$username;
	$purposesub = $usermail;
	
	
	
	// DEV
	/*
	$usermail = "presse@matheretter.de";
	$paysum = '74.91'; // should do without - manipulation possible, should READ db instead (!)
	$paypurpose = '3-Monats-Lernzugang'; 
	$purposesub = $usermail; // str_replace("@","+",$usermail);
	*/
	
?><!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="index,follow" />

<script type="text/javascript">
    <?php
		// public TEST KEY
		// var PAYMILL_PUBLIC_KEY = '9154008352411e3a931e7b7c23b60d1f';
	?>
	var PAYMILL_PUBLIC_KEY = '8a8394c64c462257014c85ca286329f6';
    var VALIDATE_CVC = true;
</script>

	<title>Lernzugang bezahlen | Matheretter</title>

	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:400" type="text/css" />
	<link rel="stylesheet" type="text/css" href="http://www.matheretter.de/template/styles.new.css?v=<?php echo $cssjs_v; ?>" />

	<link rel="stylesheet" type ="text/css" href="paymill_styles.css">

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js" type="text/javascript"></script>
		<script type="text/javascript">window.jQuery || document.write('<script src="http://www.matheretter.de/tools/jquery.min.js"><\/script>')</script>

	<script type="text/javascript" src="https://bridge.paymill.com/"></script>
	<script type="text/javascript" src="js/translation.js"></script>
	<script type="text/javascript" src="js/BrandDetection.js"></script>
    <script type="text/javascript" src="js/Iban.js"></script>
	<script type="text/javascript">
		$.noConflict();

		jQuery(document).ready(function ($) {
			var formlang = 'de';
			var doc = document;
			var body = $( doc.body );
			function translateForm(language){
				formlang = language;
				var lang;
				if(translation[language] === undefined){
					lang = translation['en'];
				}else{
					lang = translation[language];
				}

				<?php
					if($payment=='kreditkarte') {?>
						$(".card-number-label").text(lang["form"]["card-number"]);
						$(".card-cvc-label").text(lang["form"]["card-cvc"]);
						$(".card-holdername-label").text(lang["form"]["card-holdername"]);
						$(".card-expiry-label").text(lang["form"]["card-expiry"]);
					<?php
					}
					else if($payment=='lastschrift') {?>
						$("#btn-paymenttype-elv").text(lang["form"]["elv-paymentname"] + " / " + lang["form"]["elv-paymentname-advanced"]);
						$(".elv-account-label").text(lang["form"]["elv-account"] + " / " + lang["form"]["elv-iban"]);
						$(".elv-holdername-label").text(lang["form"]["elv-holdername"]);
						$(".elv-bankcode-label").text(lang["form"]["elv-bankcode"] + " / " + lang["form"]["elv-bic"]);
					<?php }
				?>
				
				$(".amount-label").text(lang["form"]["amount"]);
				$(".currency-label").text(lang["form"]["currency"]);
				$(".submit-button").text(lang["form"]["submit-button"]);
				$("#tooltip").attr('title', lang["form"]["tooltip"]);
			}

			$('.card-number').keyup(function() {
				var detector = new BrandDetection();
				var brand = detector.detect($('.card-number').val());
				$(".card-number")[0].className = $(".card-number")[0].className.replace(/paymill-card-number-.*/g, '');
				if (brand !== 'unknown') {
					$('#card-number').addClass("paymill-card-number-" + brand);

					if (!detector.validate($('.card-number').val())) {
						$('#card-number').addClass("paymill-card-number-grayscale");
					}

					if (brand !== 'maestro') {
						VALIDATE_CVC = true;
					} else {
						VALIDATE_CVC = false;
					}
				}
			});

			$('.card-expiry').keyup(function() {
				if ( /^\d\d$/.test( $('.card-expiry').val() ) ) {
					text = $('.card-expiry').val();
					$('.card-expiry').val(text += "/");
				}
			});


			function PaymillResponseHandler(error, result) {
				if (error) {
					$(".payment_errors").text(error.apierror);
					$(".payment_errors").css("display","inline-block");
				} else {
					$(".payment_errors").css("display","none");
					$(".payment_errors").text("");
					var form = $("#payment-form");
					// Token
					var token = result.token;
					form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
					form.get(0).submit();
				}
				$(".submit-button").removeAttr("disabled");
			}

                function validate() {
                    var iban = new Iban();
                    if ('' === $('.elv-holdername').val()) {
                        $(".payment_errors").text(translation[formlang]["error"]["invalid-elv-holdername"]);
                        $(".payment_errors").css("display", "inline-block");
                        $(".submit-button").removeAttr("disabled");
                        return false;
                    }

                    if (isSepa()) {
                        if (!iban.validate($('.elv-account').val())) {
                            $(".payment_errors").text(translation[formlang]["error"]["invalid-elv-iban"]);
                            $(".payment_errors").css("display", "inline-block");
                            $(".submit-button").removeAttr("disabled");
                            return false;
                        }
                        if ($('.elv-bankcode').val().length !== 9 && $('.elv-bankcode').val().length !== 11) {
                            $(".payment_errors").text(translation[formlang]["error"]["invalid-elv-bic"]);
                            $(".payment_errors").css("display", "inline-block");
                            $(".submit-button").removeAttr("disabled");
                            return false;
                        }
                    } else {
                        if (!paymill.validateAccountNumber($('.elv-account').val()) || $('.elv-bankcode').val().length > 10) {
                            $(".payment_errors").text(translation[formlang]["error"]["invalid-elv-accountnumber"]);
                            $(".payment_errors").css("display", "inline-block");
                            $(".submit-button").removeAttr("disabled");
                            return false;
                        }
                        if (!paymill.validateBankCode($('.elv-bankcode').val())) {
                            $(".payment_errors").text(translation[formlang]["error"]["invalid-elv-bankcode"]);
                            $(".payment_errors").css("display", "inline-block");
                            $(".submit-button").removeAttr("disabled");
                            return false;
                        }
                    }
                    return true;
                }

                function isSepa() {
                    var reg = new RegExp(/^\D{2}/);
                    return reg.test($('.elv-account').val());
                }

			$("#payment-form").submit(function (event) {
				$('.submit-button').attr("disabled", "disabled");
				<?php
				if($payment=='kreditkarte') {?>
					if (false === paymill.validateHolder($('.card-holdername').val())) {
						$(".payment_errors").text(translation[formlang]["error"]["invalid-card-holdername"]);
						$(".payment_errors").css("display","inline-block");
						$(".submit-button").removeAttr("disabled");
						return false;
					}
					if ((false === paymill.validateCvc($('.card-cvc').val()))) {
						if(VALIDATE_CVC){
							$(".payment_errors").text(translation[formlang]["error"]["invalid-card-cvc"]);
							$(".payment_errors").css("display","inline-block");
							$(".submit-button").removeAttr("disabled");
							return false;
						} else {
							$('.card-cvc').val("000");
						}
					}
					if (false === paymill.validateCardNumber($('.card-number').val())) {
						$(".payment_errors").text(translation[formlang]["error"]["invalid-card-number"]);
						$(".payment_errors").css("display","inline-block");
						$(".submit-button").removeAttr("disabled");
						return false;
					}
					var expiry = $('.card-expiry').val();
					expiry = expiry.split("/");
					if(expiry[1] && (expiry[1].length <= 2)){
						expiry[1] = '20'+expiry[1];
					}
					if (false === paymill.validateExpiry(expiry[0], expiry[1])) {
						$(".payment_errors").text(translation[formlang]["error"]["invalid-card-expiry-date"]);
						$(".payment_errors").css("display","inline-block");
						$(".submit-button").removeAttr("disabled");
						return false;
					}
					var params = {
						amount_int:     parseInt($('.amount').val().replace(/[\.,]/, '.') * 100),  // E.g. "15" for 0.15 Eur
						currency:       $('.currency').val(),    // ISO 4217 e.g. "EUR"
						number:         $('.card-number').val(),
						exp_month:      expiry[0],
						exp_year:       expiry[1],
						cvc:            $('.card-cvc').val(),
						cardholder:     $('.card-holdername').val()
					};

					paymill.createToken(params, PaymillResponseHandler);
					return false;
				<?php } // end output kreditkarte 
				else if($payment=='lastschrift') {?>
                    if (validate()) {
                        var params = null;

                        if (isSepa()) {
                            ibanData = $('.elv-account').val().replace(/\s+/g, "");
                            params = {
                                iban: ibanData,
                                bic: $('.elv-bankcode').val(),
                                accountholder: $('.elv-holdername').val()
                            };
                        } else {
                            params = {
                                number: $('.elv-account').val(),
                                bank: $('.elv-bankcode').val(),
                                accountholder: $('.elv-holdername').val()
                            };
                        }
                        paymill.createToken(params, PaymillResponseHandler);
                    }
                    return false;
				<?php } // end output lastschrift
				?>
				
			});

			$("#language_switch").click(function(){
				var language = formlang;
				var newimg;

				if(formlang === 'en'){
					newimg = "image/gb.png";
					language = "de";
				} else {
					newimg = "image/de.png";
					language = "en";
				}

				$(this).attr("src", newimg);
				translateForm(language);
			});

			translateForm(formlang);
		});
	</script>
</head>
<body>

<?php 
	include('../../../template/menu.php');
?>

<div id="outer-wrapper">
	<div id="main-content">
		<div id="wrap">

			<div id="paymill_form">
				<div id="top_switch" class="minimal">
					<img src="image/gb.png" id="language_switch" alt="English">
				</div>
				<form id="payment-form" action="payment.php" method="POST">
					<header>
						<img alt="Logo" src="image/paymill-logo-signet.png">
						<h1 class="form-signin-heading"><?php echo $paypurpose; ?></h1>
						<h2 class="form-signin-heading"><?php echo $purposesub; ?></h2>
						<h2 class="form-signin-heading">Zahlbetrag: <?php echo number_format($paysum, 2, ",", ".").' EUR'; ?></h2>
					</header>
					<div class="payment_errors">&nbsp;</div>
					
				<?php
				if($payment=='kreditkarte') {?>
					<fieldset>
						<label for="card-number" class="card-number-label field-left"></label>
						<input id ="card-number" class="card-number field-left" type="text" placeholder="**** **** **** ****" maxlength="19">
						<label for="card-expiry" class="card-expiry-label field-right"></label>
						<input id="card-expiry" class="card-expiry field-right" type="text" placeholder="MM/YY" maxlength="7">
					</fieldset>
					
					<fieldset>
						<label for="card-holdername" class="card-holdername-label field-left"></label>
						<input id="card-holdername" class="card-holdername field-left" type="text">
						<label for="card-cvc" class="field-right"><span class="card-cvc-label"></span><span id="tooltip" title="">?</span></label>
						<input id="card-cvc" class="card-cvc field-right" type="text" placeholder="CVC" maxlength="4">
					</fieldset>
					
				<?php } // end output kreditkarte 
				else if($payment=='lastschrift') {?>
					<fieldset>
						<label for="elv-account" class="elv-account-label field-left"></label>
						<input id="elv-account" class="elv-account field-left" type="text">
						<label for="elv-bankcode" class="elv-bankcode-label field-right" style="width:auto"></label>
						<input id="elv-bankcode" class="elv-bankcode field-right" type="text">
					</fieldset>
					<fieldset>
						<label for="elv-holdername" class="elv-holdername-label field-full"></label>
						<input id="elv-holdername" class="elv-holdername field-full" type="text">
					</fieldset>
				<?php } // end output lastschrift 
				?>
				
					<fieldset style="display:none;">
						<label for="amount" class="amount-label field-left"></label>
						<input id="amount" class="amount field-left" type="text" value="<?php echo $paysum; ?>" name="amount">
						<label for="currency" class="currency-label field-right"></label>
						<input id="currency" class="currency field-right" type="text" value="EUR" name="currency">
					</fieldset>
					
					<fieldset id="buttonWrapper">
						<button id="paymill-submit-button" class="submit-button btn btn-primary" type="submit"></button>
					</fieldset>
					
					<fieldset style="display:none;">
						<input type="hidden" value="<?php echo $usermail; ?>" name="usermail">
						<input type="hidden" value="<?php echo $username; ?>" name="username">
					</fieldset>
					
				</form>
			</div> <!-- paymill_form -->
	
		</div> <!-- wrap -->
	</div> <!-- main-content -->
</div> <!-- outer-wrapper -->

<?php 
	include('../../../template/footer.php');
?>

</body>
</html>
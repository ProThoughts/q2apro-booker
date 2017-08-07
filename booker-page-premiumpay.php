<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_premiumpay
	{

		var $directory;
		var $urltoroot;

		function load_module($directory, $urltoroot)
		{
			$this->directory = $directory;
			$this->urltoroot = $urltoroot;
		}

		// for display in admin interface under admin/pages
		function suggest_requests()
		{
			return array(
				array(
					'title' => 'booker Page premiumpay', // title of page
					'request' => 'premiumpay', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='premiumpay')
			{
				return true;
			}
			return false;
		}

		function process_request($request)
		{

			if(!qa_opt('booker_enabled'))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker premiumpay');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			$userid = qa_get_logged_in_userid();
			// dev: super admin can pay for others
			if(qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN)
			{
				$userid = qa_get('userid');
				if(empty($userid))
				{
					$userid = qa_get_logged_in_userid();
				}
			}


			// PAYMENT POST by user
			$post_orderid = qa_post_text('orderid');
			$post_premiumtype = qa_post_text('premiumtype');
			$post_paymethod = qa_post_text('paymethod');

			if(isset($post_premiumtype) && isset($post_paymethod))
			{
				$orderid = $post_orderid;

				$qa_content = qa_content_prepare();
				qa_set_template('booker premiumpay');
				$qa_content['title'] = qa_lang('booker_lang/pay_title');

				// init
				$qa_content['custom'] = '';

				// appears in email
				// $paymentshort = number_format($totalprice, 2, ",", ".").' '.qa_opt('booker_currency');

				$contractorname = booker_get_realname($userid);

				// send mail to expert contractor, must wait for payment
				require_once QA_INCLUDE_DIR.'qa-app-emails.php';

				// only email if not yet informed
				// get status of bookid, if still open (0) then first time on pay page, email and set status to reserved (1), i.e. payment will be processed

				// SEND EMAILS

				// MAIL TO contractor
				// $subject = qa_lang('booker_lang/reservation_from').' '.$customername;
				$subject = 'Premium Subscription';

				$emailbody = '';
				$emailbody .= '
					<p>
						'.qa_lang('booker_lang/hello').' '.$contractorname.',
					</p>
					<p>
						Thank you for your premium subscription. We welcome you on board of a successful community.
					</p>
					<p>
						You can now enjoy all premium features provided by Kvanto.
					</p>
				';

				$emailbody .= '
					<p>
						'.qa_lang('booker_lang/waitforpay').'
					</p>
				';

				$emailbody .= '
					<p>
						'.qa_lang('booker_lang/mail_greetings').'
						<br />
						'.qa_lang('booker_lang/mail_greeter').'
					</p>
				';

				$emailbody .= booker_mailfooter();
				$emailbody .= cssemailstyles();

				/*
				$bcclist = explode(';', qa_opt('booker_mailcopies'));
				q2apro_send_mail(array(
							'fromemail' => q2apro_get_sendermail(),
							'fromname'  => qa_opt('booker_mailsendername'),
							// 'toemail'   => $toemail,
							'senderid'	=> 1, // for log
							'touserid'  => $userid,
							'toname'    => $contractorname,
							'bcclist'   => $bcclist,
							'subject'   => $subject,
							'body'      => $emailbody,
							'html'      => true
				));
				*/

				// data for payment providers
				$paypurpose = qa_lang('booker_lang/membership_premium').' '.$contractorname.' (ID '.$userid.')';
				$item_number = $post_premiumtype; // 1 or 2

				$totalprice = qa_opt('booker_pricepremium'); // 14
				if($post_premiumtype==2)
				{
					// VIP
					$paypurpose = qa_lang('booker_lang/membership_vip').' '.$contractorname.' (ID '.$userid.')';
					$totalprice = qa_opt('booker_pricepremium'); // 19
				}


				if($post_paymethod=='paypal')
				{
					$payee = qa_opt('booker_paypal');

// *** paypal test account:
// kai-buyer@echteinfachtv.com
// pw: testingnow

					$qa_content['custom'] .= '
					<div class="payment_paypal">
						<p style="font-weight:bold;font-size:120%;">'.qa_lang('booker_lang/connect_paypal').'</p>
						<p><strong>'.qa_lang('booker_lang/pleasewait').'</strong></p>
						<img src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
						<form method="post" name="paypal_form" action="https://www.paypal.com/cgi-bin/webscr">
						<!-- <form method="post" name="paypal_form" action="https://www.sandbox.paypal.com/cgi-bin/webscr"> -->
							<input type="hidden" name="charset" value="utf-8">
							<input type="hidden" name="business" value="'.$payee.'" />
							<input type="hidden" name="cmd" value="_xclick" />
							<input type="hidden" name="image_url" value="'.$this->urltoroot.'images/paypal_banner.png" />
							<input type="hidden" name="return" value="'.q2apro_site_url().'ipnpremium?userid='.$userid.'&is=success&type='.$post_premiumtype.'" />
							<input type="hidden" name="cancel_return" value="'.q2apro_site_url().'ipnpremium?userid='.$userid.'&is=cancel&type='.$post_premiumtype.'" />
							<input type="hidden" name="notify_url" value="'.q2apro_site_url().'ipnpremium?orderid='.$orderid.'&userid='.$userid.'" />
							<input type="hidden" name="rm" value="2" />
							<input type="hidden" name="currency_code" value="'.qa_opt('booker_paypal_currency').'" />
							<input type="hidden" name="lc" value="'.qa_opt('booker_paypal_lc').'" />
							<input type="hidden" name="cbt" value="Continue" />
							<input type="hidden" name="no_shipping" value="1" />
							<input type="hidden" name="no_note" value="1" />
							<input type="hidden" name="cn" value="Comments" />
							<input type="hidden" name="cs" value="0" />
							<input type="hidden" name="item_name" value="'.$paypurpose.'" />
							<input type="hidden" name="amount" value="'.$totalprice.'" />
							<input type="hidden" name="quantity" value="1" />
							<input type="hidden" name="item_number" value="'.$item_number.'" />
						</form>

					<noscript>
						<p>'.qa_lang('booker_lang/paypal_js').'</p>
						<input type="submit" name="Submit" value="Process Payment" />
					</noscript>

						<p><img src="'.$this->urltoroot.'images/paypallogo.gif" alt="PayPal" /></p>
					</div>'; // doPayField

					$qa_content['custom'] .= '
					<script type="text/javascript">
						$(document).ready(function(){
							document.paypal_form.submit();
						});
					</script>
					';

				} // end paymethod paypal
				else if($post_paymethod=='paysera')
				{
					require_once($this->urltoroot.'libwebtopay/WebToPay.php');

					// get usermail from userid
					$usermail = helper_getemail($userid);

					try
					{
						$orderid = $post_orderid;
						$amount = $totalprice*100; // must be cents
						$projectid = qa_opt('booker_paysera_projectid'); // kvanto id
						$sign_password = qa_opt('booker_paysera_sign_password'); // kvanto id password
						$accepturl = q2apro_site_url().'payserahandler?userid='.$userid.'&is=accept&type='.$post_premiumtype;
						$cancelurl = q2apro_site_url().'payserahandler?userid='.$userid.'&is=cancel&type='.$post_premiumtype;
						$callbackurl = q2apro_site_url().'payserahandler?is=callback';

						$lang = qa_opt('booker_paysera_paylanguage'); // LIT, LAV, EST, RUS, ENG, GER, POL
						$currency = qa_opt('booker_paysera_paycurrency'); // Currency (USD, EUR)
						$country = qa_opt('booker_paysera_paycountry'); // Payer’s country (LT, EE, LV, GB, PL, DE)

						// more fields are: p_firstname, p_lastname, p_street, p_city, p_state, p_zip, p_countrycode, personcode
						// ref: https://developers.paysera.com/en/payments/current
						$request = WebToPay::redirectToPayment(array(
							'projectid'     => $projectid,
							'sign_password' => $sign_password,
							'orderid'       => $orderid,
							'amount'        => $amount,
							'currency'      => $currency,
							'lang'     	 => $lang,
							'country'       => $country,
							'accepturl'     => $accepturl,
							'cancelurl'     => $cancelurl,
							'callbackurl'   => $callbackurl,
							'paytext'	   	 => $paypurpose,
							'p_email'	   	 => $usermail,
							'test'          => 0, // 0 off, 1 test on
						));
					}
					catch (WebToPayException $e)
					{
						// handle exception
						error_log('Payment Problem: ');
						error_log($e);
					}
				} // end paysera
				else if($post_paymethod=='paymill')
				{
					$paymillroot = $this->urltoroot.'paymill/';

					// $purposesub = str_replace('~bookid~', $bookid, qa_lang('booker_lang/booking_bookid'));
					$purposesub = '';

					// get usermail from userid
					$usermail = helper_getemail($userid);

					$lang = qa_opt('booker_language');

					$qa_content['custom'] .= "
						<script>
							// public TEST KEY
							var PAYMILL_PUBLIC_KEY = '".qa_opt('booker_paymill_public_key')."';
							var VALIDATE_CVC = true;

							var paymillroot = '".$paymillroot."';
							var formlang = '".$lang."';
						</script>

						<script src=\"https://bridge.paymill.com/\"></script>
						<script src=\"".$paymillroot."js/translation.js\"></script>
						<script src=\"".$paymillroot."js/BrandDetection.js\"></script>
						<script src=\"".$paymillroot."js/Iban.js\"></script>
						<script src=\"".$paymillroot."js/Iban.js\"></script>
						<script src=\"".$paymillroot."script.js\"></script>
					";

					$qa_content['custom'] .= '
						<link rel="stylesheet" type ="text/css" href="'.$paymillroot.'paymill_styles.css">
						<style type="text/css">
						h2 {
							font-size:22px;
							color:#424A4D;
							padding-top:0;
						}
						</style>
					';


					$qa_content['custom'] .= '
						<div id="paymill_form">
							<div id="top_switch" class="minimal">
								<img src="'.$this->urltoroot.'paymill/image/gb.png" id="language_switch" alt="English">
							</div>
							<form id="payment-form" action="'.qa_path('paymill').'" method="POST">
								<header>
									<img alt="Logo" src="'.$paymillroot.'image/paymill-logo-signet.png">
									<h1 class="form-signin-heading" style="max-width:260px;">'.$paypurpose.'</h1>
									<h2 class="form-signin-heading">'.$purposesub.'</h2>
									<h2 class="form-signin-heading">'.qa_lang('booker_lang/payamount').': '.roundandcomma($totalprice).' '.qa_opt('booker_currency').'</h2>
								</header>
								<div class="payment_errors">&nbsp;</div>

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

								<fieldset style="display:none;">
									<label for="amount" class="amount-label field-left"></label>
									<input id="amount" class="amount field-left" type="text" value="'.$totalprice.'" name="amount">
									<label for="currency" class="currency-label field-right"></label>
									<input id="currency" class="currency field-right" type="text" value="EUR" name="currency">
								</fieldset>

								<fieldset id="buttonWrapper">
									<button id="paymill-submit-button" class="submit-button btn btn-primary" type="submit"></button>
								</fieldset>

								<fieldset style="display:none;">
									<input type="hidden" value="'.$usermail.'" name="usermail">
									<input type="hidden" value="'.$customername.'" name="username">
									<input type="hidden" value="'.$bookid.'" name="bookid">
								</fieldset>

							</form>
						</div> <!-- paymill_form -->
					';

				} // end paymill


				return $qa_content;

			} // END PAYMENT POST transferstring - if(isset($post_premiumtype) && isset($post_paymethod))


			// default inital page request
			$premiumtype = qa_get('type');

			if(empty($premiumtype))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker premiumpay');
				$qa_content['title'] = qa_lang('booker_lang/payment');
				$qa_content['error'] = 'Missing premium type'; // qa_lang('booker_lang/nobookingchosen');
				return $qa_content;
			}

			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker premiumpay');
			$qa_content['title'] = qa_lang('booker_lang/confirmbooking');

			// init
			$qa_content['custom'] = '';

			$totalprice = qa_opt('booker_pricepremium'); // 14
			$premiumline = qa_lang('booker_lang/price_premium');
			if($premiumtype==2)
			{
				$totalprice = qa_opt('booker_pricevip'); // 19
				$premiumline = qa_lang('booker_lang/price_vip');
			}

			// show frontend
			$qa_content['custom'] .= '
			<div class="premiumpay-wrap">

				<div class="summarytablewrap">
					<table class="summarytable">
						<tr id="payrow">
							<td>
								'.$premiumline.'
							</td>
							<td>
								'.number_format($totalprice, 2, ',', '.').' '.qa_opt('booker_currency').'
							</td>
						</tr>
						<tr id="depositrow">
							<td>
								'.str_replace('~vatvalue~', qa_opt('booker_vatvalue')*100, qa_lang('booker_lang/vat_included')).'
							</td>
							<td>
								'.number_format($totalprice-$totalprice/(1+qa_opt('booker_vatvalue')), 2, ',', '.').' '.qa_opt('booker_currency').'
							</td>
						</tr>
						<tr id="balancerow" style="background:#FFC;">
							<td style="font-weight:bold;">
								'.qa_lang('booker_lang/payment').'
							</td>
							<td style="font-weight:bold;">
								'.number_format($totalprice, 2, ',', '.').' '.qa_opt('booker_currency').'
							</td>
						</tr>
					</table>
				</div> <!-- summarytablewrap -->

				<h3 class="paymchoice">
					'.qa_lang('booker_lang/paymethodchoice').'
				</h3>

				<form id="customerform" action="" method="post">
					<div class="paychoice">

						<label id="label_paypal">
							<input type="radio" name="paymethod" class="inputpaymethod" value="paypal" checked="checked" />
							<img src="'.$this->urltoroot.'images/payicon_paypal _square.png" alt="PayPal" />
							<span class="payhint">
								<!-- '.qa_lang('booker_lang/paypalpayhint').'  -->
							</span>
						</label>


						<label id="label_paysera">
							<input type="radio" name="paymethod" class="inputpaymethod" value="paysera" />
							<img src="'.$this->urltoroot.'images/payicon_paysera.png" alt="Paysera - Bank account, Visa, Mastercard" />
							<span class="payhint">
								<!-- '.qa_lang('booker_lang/paymillpayhint').' -->
							</span>
						</label>

						<!--
						DEACTIVE
						<label id="label_bank">
							<input type="radio" name="paymethod" class="inputpaymethod" value="bank" />
							<img src="'.$this->urltoroot.'images/payicon_bank.png" alt="bank" />
							<span style="font-size:15px;">'.qa_lang('booker_lang/banktransfer').'</span>
							<span class="payhint">'.qa_lang('booker_lang/bankpayhint').'</span>
						</label>
						-->

						<input type="hidden" name="orderid" value="'.booker_get_orderid($premiumtype).'" />
						<input type="hidden" name="premiumtype" value="'.$premiumtype.'" />

					</div> <!-- paychoice -->

					<div class="bookmeholder">
						<button id="bookme" class="btn btn-primary btn-huge" type="submit">'.qa_lang('booker_lang/btn_paynow').'</button>
					</div>

				</form>
			</div> <!-- premiumpay-wrap -->
			';

			return $qa_content;
		}

	}; // end class booker_page_pay


/*
	Omit PHP closing tag to help avoid accidental output
*/

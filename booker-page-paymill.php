<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_paymill 
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
					'title' => 'booker Page Paymill', // title of page
					'request' => 'paymill', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='paymill')
			{
				return true;
			}
			return false;
		}

		function process_request($request) 
		{
			/*
			if(!qa_opt('booker_enabled'))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker paymill');
				$qa_content['error'] = 'Diese Seite ist derzeit deaktiviert.';
				return $qa_content;
			}
			*/
			
			$userid = qa_get_logged_in_userid();

			$usermail = trim(qa_post_text('usermail'));
			$username = trim(qa_post_text('username'));
			$bookid = trim(qa_post_text('bookid'));
			
			if(empty($usermail) || empty($username) || empty($bookid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker paymill');
				$qa_content['title'] = qa_lang('booker_lang/paymill_title');
				$qa_content['error'] = qa_lang('booker_lang/missing_data');
				return $qa_content;
			}
			
			$qa_content = qa_content_prepare();
			qa_set_template('booker paymill');
			$qa_content['title'] = qa_lang('booker_lang/thxpaid');
			
			// init
			$output = '';
			
			// Change the following constants
			define('PAYMILL_API_KEY', qa_opt('booker_paymill_private_key')); // private key (TEST or LIVE)

			define('CUSTOMER_EMAIL', $usermail); // appears at least in admin panel
			require $this->directory.'/paymill/php/autoload.php';
			
			$contractorid = qa_db_read_one_value(
								qa_db_query_sub('SELECT contractorid FROM `^booking_orders` 
												WHERE `bookid` = #
												', $bookid), 
												true
												);
			$contractorname = booker_get_realname($contractorid);
			
			$paypurpose = str_replace('~bookid~', $bookid, qa_lang('booker_lang/booking_bookid')).' '.qa_lang('booker_lang/servicefor');
			$paypurpose = str_replace('~client~', $username, $paypurpose);
			$paypurpose = str_replace('~contractor~', $contractorname, $paypurpose);
			
			$errormsg = '';
			$amount = null;
			
			if(isset($_POST['paymillToken']))
			{
				$service = new Paymill\Request(PAYMILL_API_KEY);
				$client = new Paymill\Models\Request\Client();
				$payment = new Paymill\Models\Request\Payment();
				$transaction = new \Paymill\Models\Request\Transaction();

				$successpay = false;
				// https://github.com/paymill/paymill-php
				try
				{
					$client->setEmail(CUSTOMER_EMAIL);
					$client->setDescription('Kunde: '.$username); // appears at least in admin panel
					$clientResponse = $service->create($client);

					$payment->setToken($_POST['paymillToken']);
					$payment->setClient($clientResponse->getId());
					$paymentResponse = $service->create($payment);

					$transaction->setPayment($paymentResponse->getId());
					$transaction->setAmount($_POST['amount'] * 100);
					$transaction->setCurrency($_POST['currency']);
					$transaction->setDescription($paypurpose); // appears at least in admin panel
					$transactionResponse = $service->create($transaction);

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
					
					$amount = $_POST['amount'];
					
					if($transactionResponse->getResponseCode() == '20000')
					{
						$successpay = true;
					}
					else
					{
						echo qa_lang('booker_lang/paymill_error').' '.qa_lang('booker_lang/contactus').': '.qa_opt('booker_email');
						$successpay = false;
					}
					
					// $result = print_r($transactionResponse, true);
				}
				catch(\Paymill\Services\PaymillException $e)
				{
					$errormsg = ($e->getResponseCode()).' | '.($e->getErrorMessage());
					// for tracing in production mode
					error_log('Error Code: '.($e->getResponseCode()).' | '.($e->getErrorMessage()));
					$successpay = false;
				}

				if($successpay)
				{
					$output .= $this->reg_paymentsuccess($bookid, $amount);
				}
				else
				{
					$output .= $this->reg_paymentfailed($bookid, $errormsg);
				}
			} // end isset($_POST['paymillToken'])

			$qa_content['custom'] = $output;
			
			return $qa_content;
			
		} // END process_request
				
				
		function reg_paymentsuccess($bookid, $amount)
		{
			// $isdepositpay not yet implemented, see example in booker-page-ipn.php
			
			booker_set_status_booking($bookid, MB_EVENT_PAID, 'creditcard');
			
			booker_register_payment_booking($bookid, $amount);
			
			// MAIL TO contractor - we received the payment
			booker_sendmail_contractor_bookidpaid($bookid);

			// MAIL TO CUSTOMER - we received the payment
			booker_sendmail_customer_paid_bookid($bookid);
			
			$output = '';
				
			// Buchung
			$output .= '
							<div style="font-size:14px;">
							<p>
								'.qa_lang('booker_lang/mail_paid1').'
							</p>
							<p style="margin:50px 0 10px 0;">
								'.qa_lang('booker_lang/mail_paid2').':
							</p>
							<p>
								<a class="defaultbutton" href="'.qa_path('clientschedule').'?bookid='.$bookid.'">'.qa_lang('booker_lang/mail_btndetails').'</a>
							</p>
						</div>';
			
			return $output;
		} // end reg_paymentsuccess($usermail);

		function reg_paymentfailed($bookid, $errormsg) 
		{
			$paylink = qa_path('pay').'?bookid='.$bookid;

			$output = '
						<div style="font-size:14px;">
							<p>
								'.qa_lang('booker_lang/payment_fail').'
							</p>
							<p>
								'.
								strtr( qa_lang('booker_lang/trypaymentagain'), array( 
								'^1' => '<a href="'.qa_path('pay').'?bookid='.$bookid.'">',
								'^2' => '</a>'
								)).
								'
							</p>
							<p>
								Error: '.$errormsg.'
							</p>
						';
						// Liegt ein anderes Problem vor, dann <a href="'.booker_getcontactlink().'">kontaktieren Sie uns</a> bitte. 
						// Wir helfen Ihnen gerne weiter.
			
			return $output;
		} // end reg_paymentfailed($usermail)


	}; // END class
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
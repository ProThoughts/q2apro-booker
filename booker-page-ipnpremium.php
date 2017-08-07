<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_ipnpremium
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
					'title' => 'booker Page ipnpremium', // title of page
					'request' => 'ipnpremium', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='ipnpremium')
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
				qa_set_template('booker ipnpremium');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			// success redirect with ?userid=1&is=success
			// cancel redirect with ?userid=1&is=cancel
			// without parameters -> paypal success

			$postpaystate = qa_get('is');

			if(isset($postpaystate))
			{
				$userid = qa_get('userid');
				$premiumtype = qa_get('type');

				if($postpaystate=='success')
				{
					$qa_content = qa_content_prepare();
					qa_set_template('booker ipnpremium');
					$qa_content['title'] = qa_lang('booker_lang/payment_success');

					$qa_content['custom'] = '
					<div>
						<p>
							Thanks for your payment.
						</p>
						<p>
							You are now Premium member at Kvanto.lt and can enjoy all advantages!
						</p>
					</div>
					';

					return $qa_content;
				}
				else if($postpaystate=='cancel')
				{
					$qa_content = qa_content_prepare();
					qa_set_template('booker ipnpremium');
					$qa_content['title'] = qa_lang('booker_lang/payment_fail_title');

					$qa_content['custom'] = '';
					$qa_content['custom'] .= '
						<div>
							<p>
								'.qa_lang('booker_lang/payment_fail').'
							</p>
							<p>
								'.
								strtr( qa_lang('booker_lang/trypaymentagain'), array(
								'^1' => '<a href="'.qa_path('premiumpay').'?type='.$premiumtype.'">',
								'^2' => '</a>'
								)).
								'
							</p>
						</div>
					';

					return $qa_content;
				}
			} // END if(isset($postpaystate))

			/*
				Simple IPN processing script based on code from the "PHP Toolkit" provided by PayPal
			*/

			$url = 'https://www.paypal.com/cgi-bin/webscr';
			// $url = 'https://www.sandbox.paypal.com/cgi-bin/webscr'; // DEV ***

			// paypal test account:
			// kai-buyer@echteinfachtv.com
			// pw: testingnow


			$postdata = '';
			foreach($_POST as $i => $v)
			{
				$postdata .= $i.'='.urlencode($v).'&';
			}
			$postdata .= 'cmd=_notify-validate';

			$web = parse_url($url);
			if($web['scheme'] == 'https')
			{
				$web['port'] = 443;
				$ssl = 'ssl://';
			}
			else
			{
				$web['port'] = 80;
				$ssl = '';
			}
			$fp = @fsockopen($ssl.$web['host'], $web['port'], $errnum, $errstr, 30);

			if(!$fp)
			{
				echo $errnum.': '.$errstr;
			}
			else
			{
				fputs($fp, "POST ".$web['path']." HTTP/1.1\r\n");
				fputs($fp, "Host: ".$web['host']."\r\n");
				fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
				fputs($fp, "Content-length: ".strlen($postdata)."\r\n");
				fputs($fp, "Connection: close\r\n\r\n");
				fputs($fp, $postdata . "\r\n\r\n");

				while(!feof($fp))
				{
					$info[] = @fgets($fp, 1024);
				}
				fclose($fp);
				$info = implode(',', $info);


				if(!isset($_POST['payment_status']))
				{
					exit();
				}

				$payment_status = strip_tags($_POST['payment_status']);

				// for mailing
				require_once QA_INCLUDE_DIR.'qa-app-emails.php';

				// valid payment
				if(preg_match('/VERIFIED/i', $info))
				{
					// for $_POST variables see https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNandPDTVariables/#id091EB04C0HS

					// DEV sandbox ***
					// if($payment_status == 'Completed' || $payment_status == 'Pending')

					// only mark as paid if payment is successful
					if($payment_status == 'Completed')
					{
						// item_number, see our paypal submit form for values
						$premiumtype = strip_tags($_POST['item_number']); // 1 - Premium, 2 - VIP
						if(empty($premiumtype))
						{
							$premiumtype = 1;
						}

						$orderid = qa_get('orderid');
						$userid = qa_get('userid');
						$username = booker_get_realname($userid);


						// get payment amount
						$amount = strip_tags($_POST['mc_gross']);

						booker_setpremium($userid, $premiumtype);

						// also does logging
						booker_register_payment_premium($orderid, $userid, $amount, $premiumtype, 'paypal');

						// MAIL TO contractor - we received the payment
						// booker_sendmail_contractor_bookidpaid($bookid);

						// MAIL TO ADMIN - we received the payment
						// ...

						exit();
					} // end payment completed
					else
					{
						// $payment_status is "Pending" or payment failed
						// invalid, log error or something, inform admin via email
						qa_send_notification(1, null, null, 'Paypal Problem: Status', 'Zahlungsstatus war nicht "completed".', null, false);
					}
				} // end VERIFIED
				else
				{
					// invalid, log error or something, inform admin via email
					qa_send_notification(1, null, null, 'Paypal IPN Problem', "Zahlungsfehler.\npostdata: ".$postdata, null, false);
				}
			}

			exit();

		} // end process_request

	}; // end class booker_page_ipnpremium


/*
	Omit PHP closing tag to help avoid accidental output
*/

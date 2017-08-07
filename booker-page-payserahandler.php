<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_payserahandler
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
					'title' => 'booker Page payserahandler', // title of page
					'request' => 'payserahandler', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='payserahandler')
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
				qa_set_template('booker payserahandler');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			// success redirect with ?userid=1&is=accept
			// cancel redirect with ?userid=1&is=cancel
			// callback -> ?is=callback

			// check at first if we have a accept or cancel status
			$postpaystate = qa_get('is');

			if(isset($postpaystate))
			{
				$userid = qa_get('userid');
				$premiumtype = qa_get('type');

				// successful payment
				if($postpaystate=='accept')
				{
					$qa_content = qa_content_prepare();
					qa_set_template('booker payserahandler');
					
					$qa_content['title'] = qa_lang('booker_lang/payment_success');

					$qa_content['custom'] = booker_displaypremium_success($userid);

					/*
					// Get user profile avatar
					$condata = qa_db_read_one_assoc(qa_db_query_sub('SELECT handle,avatarblobid FROM ^users WHERE userid = #', $userid), true);

					$imgsize = 250;
					$avatar = './?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size='.$imgsize;

					if(!empty($condata))
					{
						if(isset($condata['avatarblobid']))
						{
							$avatar = './?qa=image&qa_blobid='.$condata['avatarblobid'].'&qa_size='.$imgsize;
						}
					}

					$qa_content['custom'] = '
					<script>
						$(document).ready(function(){
							$(".success-image").addClass("success-animate");
						});
					</script>
					<div class="success-image">
						<div class="success-avatar" style="background-image: url('.$avatar.')"></div>
						<div class="success-stars"></div>
						<div class="success-stars-2"></div>
						<div class="success-ribbon"></div>
					</div>
					<div class="success-message">
						<p>
							'.qa_lang('booker_lang/payment_success_message').'
						</p>
					</div>
					';
					*/

					return $qa_content;
				}
				else if($postpaystate=='cancel')
				{
					$qa_content = qa_content_prepare();
					qa_set_template('booker payserahandler');
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
				Paysera CALLBACK processing script
			*/
			require_once($this->urltoroot.'libwebtopay/WebToPay.php');

			try
			{
				$response = WebToPay::checkResponse($_GET, array(
					'projectid'     => qa_opt('booker_paysera_projectid'), // kvanto id 87983
					'sign_password' => qa_opt('booker_paysera_sign_password'), // kvanto id password 20cc4475972ff03c612b1720ac61098d
				));

				/*
				// DEV: Will block the further execution
				if ($response['test'] !== '0')
				{
					error_log('Testing, real payment was not made');
					throw new Exception('Testing, real payment was not made');
				}
				*/

				if ($response['type'] !== 'macro')
				{
					error_log('Only macro payment callbacks are accepted');
					throw new Exception('Only macro payment callbacks are accepted');
				}

				$orderid = $response['orderid'];
				if(empty($orderid))
				{
					error_log('Error: orderid was empty');
					throw new Exception('Error: orderid was empty');
				}
				$amount = $response['amount'];
				$currency = $response['currency'];
				$usermail = $response['p_email'];

				// error_log('Orderid: '.$orderid);

				// get userid from orderid, e.g.
				$orderid_data = explode('_', $orderid);
				if(empty($orderid_data[0]) || empty($orderid_data[1]))
				{
					error_log('Error: orderid was empty after explode');
					error_log('orderid is shown as: '.$orderid);
					throw new Exception('Error: part of orderid was empty');
				}

				$userid = $orderid_data[0];
				$premiumtype = $orderid_data[1];

				if(empty($premiumtype))
				{
					$premiumtype = 1;
				}

				$username = booker_get_realname($userid);

				/*
				error_log('orderid: '.$orderid);
				error_log('amount: '.$amount);
				error_log('currency: '.$currency);
				error_log('usermail: '.$usermail);
				error_log('userid: '.$userid);
				error_log('username: '.$username);
				error_log('type: '.$premiumtype);
				*/

				// Paysera notes:
				//@todo: check whether the order with $orderid not yet confirmed (callback can be repeated several times)
				//@todo: check whether the amount of the order and currency correspond to $amount $currency
				//@todo: confirm the order

				booker_setpremium($userid, $premiumtype);

				// also does logging
				booker_register_payment_premium($orderid, $userid, $amount/100, $premiumtype, 'paysera');

				// MAIL TO contractor - we received the payment
				// booker_sendmail_contractor_bookidpaid($bookid);

				// MAIL TO ADMIN - we received the payment
				// ...

				// "OK" must be returned
				echo 'OK';
			}
			catch (Exception $e)
			{
				$error = get_class($e) . ': ' . $e->getMessage();
				echo $error;

				// for mailing
				require_once QA_INCLUDE_DIR.'qa-app-emails.php';

				// invalid, log error or something, inform admin via email
				qa_send_notification(1, null, null, 'Paysera Problem', 'Payment not completed.'.$error, null, false);
			}

			exit();

		} // end process_request

	}; // end class booker_page_payserahandler


/*
	Omit PHP closing tag to help avoid accidental output
*/

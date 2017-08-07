<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_pay
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
					'title' => 'booker Page Pay', // title of page
					'request' => 'pay', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='pay')
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
				qa_set_template('booker pay');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			// PAYMENT POST by user
			$post_bookid = qa_post_text('bookid');

			if(isset($post_bookid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker pay');
				$qa_content['title'] = qa_lang('booker_lang/pay_title');
				$bookid = $post_bookid;

				$userid = qa_get_logged_in_userid();
				$userhandle = booker_get_realname($userid);
				if(empty($userhandle))
				{
					$qa_content = qa_content_prepare();
					qa_set_template('booker pay');
					$qa_content['title'] = 'Server Problem'; // page title
					$qa_content['error'] = 'Bitte kontaktieren Sie uns. Fehlercode: 477';
					return $qa_content;
				}

				// init
				$qa_content['custom'] = '';

				// get booking data, can hold one or more events
				$bookdata = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT eventid, bookid, offerid, created, contractorid, starttime, endtime, customerid, unitprice, needs, status FROM ^booking_orders
														WHERE bookid = #
														ORDER BY starttime ASC',
														$bookid));
				$needs = $bookdata[0]['needs'];
				$customerid = $bookdata[0]['customerid'];
				$customername = booker_get_realname($customerid);
				$offerid = $bookdata[0]['offerid'];

				$isoffer = !empty($offerid);

				$totalprice = 0;
				if($isoffer)
				{
					$totalprice = $bookdata[0]['unitprice'];
				}
				else
				{
					// go over all events and calculate total price
					foreach($bookdata as $bookitem)
					{
						$totalprice += booker_get_eventprice($bookitem['eventid']);
					}
				}

				// appears in email
				$paymentshort = number_format($totalprice, 2, ",", ".").' '.qa_opt('booker_currency');

				$payprice = $totalprice;

				$contractorid = $bookdata[0]['contractorid'];
				$contractorname = booker_get_realname($contractorid);
				$contractorskype = booker_get_userfield($contractorid, 'skype');

				// send mail to expert contractor, must wait for payment
				require_once QA_INCLUDE_DIR.'qa-app-emails.php';

				// only email if not yet informed
				// get status of bookid, if still open (0) then first time on pay page, email and set status to reserved (1), i.e. payment will be processed
				if(booker_get_status_booking($bookid)==MB_EVENT_OPEN)
				{
					// SEND EMAILS

					// MAIL TO contractor
					$subject = qa_lang('booker_lang/reservation_from').' '.$customername;

					$youwerebooked = qa_lang('booker_lang/youwerebooked');
					$youwerebooked = str_replace('~name~',
												 '<a href="'.q2apro_site_url().'mbmessages?to='.$bookdata[0]['customerid'].'">'.$customername.'</a>',
												 $youwerebooked);
					$youwerebooked = str_replace('~bookid~', $bookid, $youwerebooked);

					$emailbody = '';
					$emailbody .= '
						<p>
							'.qa_lang('booker_lang/hello').' '.$contractorname.',
						</p>
						<p>
							'.$youwerebooked.'
						</p>
							'.getbookingtable($bookid).'
						<p>
						</p>
					';

					$subject = qa_lang('booker_lang/appts_with').' '.$customername;

					// paid by deposit notice
					/*
					$emailbody .= '
						<p style="color:#00F;">
							'.qa_lang('booker_lang/paidbydeposit').'
						</p>';
					*/

					// contractor needs to confirm event time
					$emailbody .= '
						<p>
							'.qa_lang('booker_lang/confirmtime').':
						</p>
						<p>
							<a href="'.q2apro_site_url().'contractorschedule" class="defaultbutton">'.qa_lang('booker_lang/confirm_appts').'</a>
						</p>
					';

					// prepare contact string
					$contactdetails = '<a href="'.q2apro_site_url().'mbmessages?to='.$customerid.'">'.qa_lang('booker_lang/directmessage').'</a>';

					$customerphone = booker_get_userfield($customerid, 'phone');
					if(!empty($customerphone))
					{
						$contactdetails .= ' | <a href="tel:'.$customerphone.'">'.qa_lang('booker_lang/telephone_abbr').' '.$customerphone.'</a>';
					}

					$customerskype = booker_get_userfield($customerid, 'skype');
					if(!empty($customerskype))
					{
						$contactdetails .= ' | <a href="skype:'.$customerskype.'?chat">Skype</a>';
					}

					// add contact data
					$emailbody .= '
						<p>
							'.qa_lang('booker_lang/contactclient').':
							'.$contactdetails.'
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
					$emailbody .= csstablestyles();
					$emailbody .= cssemailstyles();

					$bcclist = explode(';', qa_opt('booker_mailcopies'));
					q2apro_send_mail(array(
								'fromemail' => q2apro_get_sendermail(),
								'fromname'  => qa_opt('booker_mailsendername'),
								// 'toemail'   => $toemail,
								'senderid'	=> 1, // for log
								'touserid'  => $contractorid,
								'toname'    => $contractorname,
								'bcclist'   => $bcclist,
								'subject'   => $subject,
								'body'      => $emailbody,
								'html'      => true
					));


					// MAIL TO CUSTOMER
					// $subject = str_replace('~~~', $contractorname, qa_lang('booker_lang/appts_reserved'));
					// $customermailmsg = '';
					// if(!$needstopay)
					$subject = str_replace('~~~', $contractorname, qa_lang('booker_lang/appts_booked'));
					/*
					$customermailmsg = '
					<p>
						'.qa_lang('booker_lang/bookconfirmed_deposit').'
					</p>
					';
					*/
					$customermailmsg = '
					<p>
						'.strtr( qa_lang('booker_lang/cansendmsg'), array(
										'^1' => '<a href="'.q2apro_site_url().'mbmessages?to='.$contractorid.'">',
										'^2' => '</a>'
										) ).'
					</p>
					';

					/*
					else
					{
						$customermailmsg = '
						<p>
							'.str_replace('~~~', $paymentshort, qa_lang('booker_lang/pleasepayamount')).'
						</p>
						<p>
						'.
							strtr( qa_lang('booker_lang/paymentalways'), array(
							'^1' => '<a href="'.q2apro_site_url().'pay?bookid='.$bookid.'">',
							'^2' => '</a>'
							)).
						'
						</p>
						';
					}
					*/

					$emailbody = '';
					$emailbody .= '
						<p>
							'.qa_lang('booker_lang/hello').' '.$customername.',
						</p>
						<p>
							'.str_replace('~name~', '<a href="'.q2apro_site_url().'booking?contractorid='.$contractorid.'">'.$contractorname.'</a>', qa_lang('booker_lang/bookedby')).'
							'.str_replace('~bookid~', $bookid, qa_lang('booker_lang/booking_bookid')).'
						</p>'.
							getbookingtable($bookid).
						'<p>
						</p>
						'.
						$customermailmsg.
						'
						<p>
							'.qa_lang('booker_lang/mail_greetings').'
							<br />
							'.qa_lang('booker_lang/mail_greeter').'
						</p>
					';

					$emailbody .= booker_mailfooter();
					$emailbody .= csstablestyles();
					$emailbody .= cssemailstyles();

					$bcclist = explode(';', qa_opt('booker_mailcopies'));
					q2apro_send_mail(array(
								'fromemail' => q2apro_get_sendermail(),
								'fromname'  => qa_opt('booker_mailsendername'),
								// 'toemail'   => $toemail,
								'senderid'	=> 1, // for log
								'touserid'  => $userid,
								'toname'    => $customername,
								'bcclist'   => $bcclist,
								'subject'   => $subject,
								'body'      => $emailbody,
								'html'      => true,
					));

				} // END booker_get_status_booking($bookid)==MB_EVENT_OPEN


				// prepare contact string
				$contactdetails = '
				<a target="_blank" class="defaultbutton buttonthin btn_orange" href="'.q2apro_site_url().'mbmessages?to='.$contractorid.'">
					<i class="fa fa-comments fa-lg"></i> '.qa_lang('booker_lang/directmessage').'
				</a>
				';

				$contractorphone = booker_get_userfield($contractorid, 'phone');
				if(!empty($contractorphone))
				{
					$contactdetails .= '
					<a class="defaultbutton buttonthin btn_orange" href="tel:'.$contractorphone.'">
						<i class="fa fa-phone fa-lg"></i> '.qa_lang('booker_lang/telephone_abbr').' '.$contractorphone.'
					</a>
					';
				}

				$contractorskype = booker_get_userfield($contractorid, 'skype');
				if(!empty($contractorskype))
				{
					$contactdetails .= '
					<a class="defaultbutton buttonthin btn_orange" href="skype:'.$contractorskype.'?chat">
						<i class="fa fa-skype fa-lg"></i> Skype: '.$contractorskype.'
					</a>
					';
				}

				// change page title
				$qa_content['title'] = qa_lang('booker_lang/booking_success');
				$qa_content['custom'] .= '
				<div class="paidbydepositbox">
					<p>
						'.qa_lang('booker_lang/booking_thanks').'
						'.
						strtr( qa_lang('booker_lang/appts_activated'), array(
						'^1' => '<a target="_blank" href="'.qa_path('clientschedule').'">',
						'^2' => '</a>'
						)).
						'
						'.
						qa_lang('booker_lang/confirmwassent').
						'
						'.str_replace('~~~',
										'<a target="_blank" href="'.qa_path('mbmessages').'?to='.$contractorid.'">'.$contractorname.'</a>',
										qa_lang('booker_lang/con_willcontact')).'
					</p>
					<p>
						'.qa_lang('booker_lang/put_details').'
						<a class="defaultbutton" style="padding:3px 10px;margin:10px 0 0 5px;" target="_blank" href="'.qa_path('clientschedule').'">'.qa_lang('booker_lang/apptdetails').'</a>
					</p>
					<p style="margin-top:30px;">
						'.qa_lang('booker_lang/con_contactbefore').':
					</p>
					<p>
						'.$contactdetails.'
					</p>
					<p>
						'.qa_lang('booker_lang/goodluck').'
					</p>
				</div> <!-- paidbydepositbox -->
				';

				$qa_content['custom'] .= '
					<style type="text/css">
						.qa-main {
							min-height:600px;
						}
						.paidbydepositbox {
							max-width:780px;
							margin-top:40px;
						}
						.paidbydepositbox p {
							line-height:150%;
						}
						.pinitial {
							margin-top:40px;
						}
						.payamount {
							padding:0 5px;
							color:#000000;
							font-size:20px;
							font-weight:bold;
						}

					</style>
				';

				// release bookings, does logging too
				// booker_set_status_booking($bookid, MB_EVENT_PAID);

				// set status to reserved (1) after the emails - does LOG too
				booker_set_status_booking($bookid, MB_EVENT_RESERVED);

				// register payment type for all bookid events
				// booker_change_payment($bookid, $post_paymethod);

				return $qa_content;
			} // end PAYMENT POST


			// default inital page request
			$bookid = qa_get('bookid');

			if(empty($bookid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker pay');
				$qa_content['title'] = qa_lang('booker_lang/confirmbooking');
				$qa_content['error'] = qa_lang('booker_lang/nobookingchosen');
				return $qa_content;
			}

			$userid = qa_get_logged_in_userid();

			// get booking data
			$bookdata = qa_db_read_all_assoc(
							qa_db_query_sub('SELECT eventid, bookid, offerid, created, contractorid, starttime, endtime, customerid, unitprice, status FROM ^booking_orders
												WHERE bookid = #
												ORDER BY starttime ASC',
												$bookid)
											);
			// check for any value
			if(!isset($bookdata[0]['contractorid']))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker pay');
				$qa_content['title'] = qa_lang('booker_lang/confirmbooking');
				$qa_content['error'] = qa_lang('booker_lang/nobookingfound');
				return $qa_content;
			}

			$eventid = $bookdata[0]['eventid'];
			$contractorid = $bookdata[0]['contractorid'];
			$contractorname = booker_get_realname($contractorid);
			$offerid = $bookdata[0]['offerid'];
			// take price from time of booking, in case the contractor changes the offer data meanwhile
			$offerprice = $bookdata[0]['unitprice'];
			$starttime = $bookdata[0]['starttime'];
			$endtime = $bookdata[0]['endtime'];

			$isoffer = !empty($offerid);


			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker pay');
			$qa_content['title'] = qa_lang('booker_lang/confirmbooking');

			// init
			$qa_content['custom'] = '';

			$qa_content['custom'] .= '
				<p class="pcontractor">
					'.qa_lang('booker_lang/forcontractor').': '.$contractorname.'
				</p>
			';

			$qa_content['custom'] .= '
				<div class="checkout-wrap">
			';

			if($isoffer)
			{
				// get offer data
				$offerdata = qa_db_read_one_assoc(
								qa_db_query_sub('SELECT created, userid, title, price, duration, end, description, flags, status FROM `^booking_offers`
												WHERE offerid = #
												',
												$offerid));
				$eventtime = '';
				if(is_null($offerdata['duration']))
				{
					// get starttime from booked event/offer
					$eventtime = helper_geteventstartday($eventid);
				}
				else
				{
					$eventtime = helper_geteventtimes($eventid, false);
				}

				$qa_content['custom'] .= '
					<h3 style="line-height:150%;">
						'.qa_lang('booker_lang/chosenoffer').':
					</h3>

					<div class="offeroverview">
						<p>
							<span>'.qa_lang('booker_lang/title').': </span>
							<span>'.$offerdata['title'].'</span>
						</p>
						<p>
							<span>'.qa_lang('booker_lang/time').': </span>
							<span>'.$eventtime.'</span>
						</p>
						<p style="font-weight:bold;">
							<span>'.qa_lang('booker_lang/price').':</span>
							<span>'.helper_format_currency($offerprice).' '.qa_opt('booker_currency').' '.qa_lang('booker_lang/incl_taxes').'</span>
						</p>
					</div> <!-- offeroverview -->

				';
			}
			else
			{
				$qa_content['custom'] .= '
					<h3>
						'.qa_lang('booker_lang/chosentimes').'
					</h3>

					<div class="bookingtablewrap-checkout">
						'.getbookingtable($bookid).'
					</div> <!-- bookingtablewrap-checkout -->
				';
			}

			// get total price
			$totalprice = 0;
			foreach($bookdata as $bookitem)
			{
				$totalprice += booker_get_eventprice($bookitem['eventid']);
			}

			$qa_content['custom'] .= '
				<div class="bookmeadvice">
					<p>
						'.qa_lang('booker_lang/paymentwithprovider').'
					</p>
					<p>
						'.qa_lang('booker_lang/providergetscontact').'
					</p>
				</div>
			';

			/*
			$qa_content['custom'] .= '
				<div class="bookmeadvice">
					<p>
						'.qa_lang('booker_lang/paywithinday').'
					</p>
				</div>
			';
			*/

			/*
			$qa_content['custom'] .= '
				<h3>
					'.qa_lang('booker_lang/verification').'
				</h3>
			';
			$qa_content['custom'] .= '
				<div class="userverification">
					<p>
						'.qa_lang('booker_lang/verification_hint1').'
					</p>
					<p>
						'.qa_lang('booker_lang/verification_hint2').'
					</p>
					<input type="text" id="ccnumber" style="background:#FFF;width:270px;border:1px solid #77F;" placeholder="'.qa_lang('booker_lang/verification_placeholder').'" autofocus>

					<div class="bookmeholder">
						<button id="bookme" class="defaultbutton bookbutton" type="submit">'.qa_lang('booker_lang/btn_booknow').'</button>
					</div>
				</div> <!-- userverification -->
			';
			*/

			$qa_content['custom'] .= '
				<form method="post">
					<input type="hidden" id="bookid" name="bookid" value="'.$bookid.'">
					<div class="bookmeholder">
						<button id="bookme" class="defaultbutton" type="submit">'.qa_lang('booker_lang/btn_booknow').'</button>
					</div>
				</form>
			';

			$qa_content['custom'] .= '
				</div> <!-- checkout-wrap -->
			';

			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main {
					width:100%;
					max-width:800px;
					font-size:13px;
				}
				.qa-main p {
					line-height:150%;
				}
				.qa-main h3 {
					font-size:18px;
					font-weight:normal;
					margin-top:50px;
				}
				.checkout-wrap {
					display:inline-block;
					background:#fff;
					padding:20px 40px;
					border:3px solid #F5F7F9;
				}
				.pcontractor {
					font-size:15px;
				}
				.offeroverview {
					display:table;
					width:100%;
					max-width:300px;
					font-size:15px;
				}
				.offeroverview p {
					display:table-row;
				}
				.offeroverview span {
					display:table-cell;
					padding:3px 0;
				}
				.offeroverview span:nth-child(1) {
					width:20%;
				}
				.offeroverview span:nth-child(2) {
					width:80%;
				}

				.qa-sidepanel {
					display:none;
				}
				#calendar {
					max-width: 880px;
					margin: 0 0 20px 0;
				}
				.fc-agendaWeek-button {
					display:none;
				}
				.bookingtablewrap-checkout {
				}
				.bookingtablewrap, .summarytablewrap {
					display:block;
					width:100%;
					max-width:640px;
					text-align:right;
				}
				.summarytable, #bookingtable {
					display:inline-block;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
				}
				#bookingtable td:nth-child(3),
				.summarytable td:nth-child(1) {
					width:140px;
				}
				#bookingtable td:nth-child(4),
				.summarytable td:nth-child(2) {
					width:90px;
					text-align:right;
				}
				.summarytable {
					margin-top:30px;
				}
				.summarytable td, .summarytable th,
				#bookingtable td, #bookingtable th {
					border:1px solid #CCC;
					font-size:15px;
				}
				.summarytable td {
					line-height: 30px;
				}
				.summarytable td, .summarytable th,
				#bookingtable td, #bookingtable th {
					padding:3px 5px;
					border:1px solid #DDD;
					font-weight:normal;
				}
				#bookingtable th {
					font-size:15px;
					font-weight:bold;
					text-align:left;
				}
				#bookingtable td {
					text-align:left;
				}
				#event0 {
					display:none;
				}

				.bookingtablewrap-checkout #bookingtable {
					display:table;
					background:#FFF;
					width:100%;
					max-width:640px;
				}
				.bookingtablewrap-checkout #bookingtable tr {
					width:100%;
				}

				.bookmeholder {
					display:block;
					width:80%;
					margin-top:30px;
				}
				.booktime, .bookcalc, .booktimeabs, .booktimehours {
				}
				#sumrow td {
					font-weight:bold !important;
				}
				#bookme {
					background:#49F;
					margin:20px 0 100px 0;
					padding:15px 25px;
				}
				.bookmeadvice {
					max-width:600px;
					color:#00F;
					margin-top:40px;
					font-size: 14px !important;
				}
			</style>';


			// Adwords tracking
			/*
			$qa_content['custom'] .= '
				<!-- Adwords Code for Order Page (before payment) Conversion Page -->
				<script type="text/javascript">
				/ * <![CDATA[ * /
				var google_conversion_id = 1024906094;
				var google_conversion_language = "en";
				var google_conversion_format = "3";
				var google_conversion_color = "ffffff";
				var google_conversion_label = "XGCGCK75-WEQ7qbb6AM";
				var google_remarketing_only = false;
				/ * ]]> * /
				</script>
				<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
				</script>
				<noscript>
				<div style="display:inline;">
				<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/1024906094/?label=XGCGCK75-WEQ7qbb6AM&amp;guid=ON&amp;script=0"/>
				</div>
				</noscript>
			';
			*/

			return $qa_content;
		}

	}; // end class booker_page_pay


/*
	Omit PHP closing tag to help avoid accidental output
*/

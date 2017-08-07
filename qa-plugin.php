<?php

/*
	Plugin Name: q2apro Booker
	Plugin URI: http://www.q2apro.com/plugins/bookingsystem
	Plugin Description: A complete booking system for q2a.
	Plugin Version: 1.0
	Plugin Date: 2016-04-10
	Plugin Author: q2apro.com
	Plugin Author URI: http://www.q2apro.com
	Plugin Minimum Question2Answer Version: 1.7
	Plugin Update Check URI:

	Licence: Copyright © q2apro.com - All rights reserved

*/

	// don't allow this page to be requested directly from browser
	if (!defined('QA_VERSION'))
	{
		header('Location: ../../');
		exit;
	}

	// LOCKS all userdata, not visible to public
	define('MB_SECRETMODE', false);

	// event status constants
	define('MB_EVENT_OPEN', 0); 		// ST - booked but payment not yet triggered by client
	define('MB_EVENT_RESERVED', 1);		// ST - pay button clicked but payment pending by client
	define('MB_EVENT_PAID', 2); 		// ST - event paid by client
	define('MB_EVENT_ACCEPTED', 3);		// TUT - accepted by contractor
	define('MB_EVENT_FINDTIME', 4);		// TUT/ST - find new time
	define('MB_EVENT_REJECTED', 5);		// TUT - contractor cannot teach
	define('MB_EVENT_NEEDED', 6);		// TUT - find new contractor
	define('MB_EVENT_COMPLETED', 7);	// TUT - contractor enters needs + protocol

	// user service (bitwise flags)
	define('MB_SERVICELOCAL', 1);		// user service is not online
	define('MB_SERVICEONLINE', 2);		// user service is available online
	define('MB_SERVICEATCUSTOMER', 4);	// user service is at place of customer
	// define('MB_SERVICENEXT', 8);		// ...

	// user status
	define('MB_USER_STATUS_DEFAULT', 0);
	define('MB_USER_STATUS_APPROVED', 1);
	define('MB_USER_STATUS_DISAPPROVED', -1);

	// request status (user classifieds)
	define('MB_REQUEST_CREATED', 0);		// request created
	define('MB_REQUEST_APPROVED', 1);		// request got approved
	define('MB_REQUEST_DISAPPROVED', 2);	// request got disapproved
	define('MB_REQUEST_DELETED', 3);		// request got deleted (survives in db)

	// offer status (bitwise flags) - if flag is not set, it means the opposite
	define('MB_OFFER_APPROVED', 1);		// offer approved (admin)
	define('MB_OFFER_ACTIVE', 2);		// offer active (user can deactivate), also used for checked by admin
	define('MB_OFFER_DELETED', 4);		// offer deleted (admin|user) (survives in db)
	// define('MB_OFFER_ARCHIVED', 8);		// offer got archived and does not appear frontend anymore (however, still survives in db)

	// user premium status types
	define('MB_USER_DEFAULT', 0);
	define('MB_USER_PREMIUM', 1);
	define('MB_USER_VIP', 2);


	// language file
	qa_register_plugin_phrases('booker-lang-*.php', 'booker_lang');

	// admin
	qa_register_plugin_module('module', 'booker-admin.php', 'booker_admin', 'booker Admin');

	// layer
	qa_register_plugin_layer('booker-layer.php', 'booker Layer');

	// layer for userprofile
	qa_register_plugin_layer('booker-layer-userforumprofile.php', 'booker Layer UserForumProfile');

	// page for admin: all honorars + payments
	// qa_register_plugin_module('page', 'booker-page-adminbalance.php', 'booker_page_adminbalance', 'booker Page Admin Balance');

	// page for admin: all bookings + all payments
	qa_register_plugin_module('page', 'booker-page-adminschedule.php', 'booker_page_adminschedule', 'booker Page Admin Book');

	// page for admin: all bookings timeplan
	qa_register_plugin_module('page', 'booker-page-admincalendar.php', 'booker_page_admincalendar', 'booker Page Admin Calendar');

	// page for admin: edit single events
	qa_register_plugin_module('page', 'booker-page-admineventedit.php', 'booker_page_admineventedit', 'booker Page Admin Event Edit');

	qa_register_plugin_module('page', 'booker-page-adminmessages.php', 'booker_page_adminmessages', 'booker Page Admin Messages');

	// qa_register_plugin_module('page', 'booker-page-adminpayments.php', 'booker_page_adminpayments', 'booker Page Admin Payments');

	qa_register_plugin_module('page', 'booker-page-adminratings.php', 'booker_page_adminratings', 'booker Page Admin Ratings');

	qa_register_plugin_module('page', 'booker-page-adminclients.php', 'booker_page_adminclients', 'booker Page Admin clients');

	qa_register_plugin_module('page', 'booker-page-admincontractors.php', 'booker_page_admincontractors', 'booker Page Admin contractors');

	qa_register_plugin_module('page', 'booker-page-adminlogview.php', 'booker_page_adminlogview', 'booker Page Admin logview');

	qa_register_plugin_module('page', 'booker-page-adminoffers.php', 'booker_page_adminoffers', 'booker Page Admin offers');

	qa_register_plugin_module('page', 'booker-page-adminrequests.php', 'booker_page_adminrequests', 'booker Page Admin Requests');

	qa_register_plugin_module('page', 'booker-page-adminsearchtrack.php', 'booker_page_adminsearchtrack', 'booker Page Admin Searchtrack');

	qa_register_plugin_module('page', 'booker-page-ajaxhandler.php', 'booker_ajaxhandler', 'Ajax Handler Page');

	qa_register_plugin_module('page', 'booker-page-ajaxsearch.php', 'booker_ajaxsearch', 'Ajax Search Page');

	qa_register_plugin_module('page', 'booker-page-ajaxrequestsearch.php', 'booker_ajaxrequestsearch', 'Ajax Request Search Page');

	qa_register_plugin_module('page', 'booker-page-bid.php', 'booker_page_bid', 'booker Page Bid');

	qa_register_plugin_module('page', 'booker-page-booking.php', 'booker_page_booking', 'booker Page Booking');

	qa_register_plugin_module('page', 'booker-page-bookingbutton.php', 'booker_page_bookingbutton', 'booker Page bookingbutton');

	qa_register_plugin_module('page', 'booker-page-businesscard.php', 'booker_page_businesscard', 'booker Page businesscard');

	qa_register_plugin_module('page', 'booker-page-premium.php', 'booker_page_premium', 'booker Page premium');

	qa_register_plugin_module('page', 'booker-page-premiumhandler.php', 'booker_page_premiumhandler', 'booker Page premiumhandler');

	qa_register_plugin_module('page', 'booker-page-premiumpay.php', 'booker_page_premiumpay', 'booker Page premiumpay');
	
	qa_register_plugin_module('page', 'booker-page-premiumpayments.php', 'booker_page_premiumpayments', 'booker Page premiumpayments');

	qa_register_plugin_module('page', 'booker-page-eventhistory.php', 'booker_page_eventhistory', 'booker Page Event History');

	qa_register_plugin_module('page', 'booker-page-eventmanager.php', 'booker_page_eventmanager', 'booker Page Event Manager');

	// ics page to provide ics calendars
	qa_register_plugin_module('page', 'booker-page-ics.php', 'booker_page_ics', 'booker Page ICS');

	// obsolete: ipn page to receive paypal notifications for paid booking
	// qa_register_plugin_module('page', 'booker-page-ipn.php', 'booker_page_ipn', 'booker Page IPN');

	qa_register_plugin_module('page', 'booker-page-ipnpremium.php', 'booker_page_ipnpremium', 'booker Page ipnpremium');

	qa_register_plugin_module('page', 'booker-page-payserahandler.php', 'booker_page_payserahandler', 'booker Page payserahandler');

	// page for client and contractor to send messages (not revealing the emails)
	qa_register_plugin_module('page', 'booker-page-mbmessages.php', 'booker_page_mbmessages', 'booker Page Messages');

	// payment page (customer pays by bank or paypal)
	qa_register_plugin_module('page', 'booker-page-pay.php', 'booker_page_pay', 'booker Page Pay');

	// payment page (customer pays by creditcard)
	qa_register_plugin_module('page', 'booker-page-paymill.php', 'booker_page_paymill', 'booker Page Paymill');

	// ajax page for tracking of searchterms on page contractorlist
	qa_register_plugin_module('page', 'booker-page-searchtrack.php', 'booker_page_searchtrack', 'booker Page Searchtrack');

	// client page for payment statements
	// qa_register_plugin_module('page', 'booker-page-clientbalance.php', 'booker_page_client_balance', 'booker Page Client Balance');

	// client page for deposit payments (Guthaben)
	// qa_register_plugin_module('page', 'booker-page-clientdeposit.php', 'booker_page_clientdeposit', 'booker Page Client Deposit');

	// client page for week table
	qa_register_plugin_module('page', 'booker-page-clientcalendar.php', 'booker_page_clientcalendar', 'booker Page Client Dates');

	// clients page intro, videos
	// qa_register_plugin_module('page', 'booker-page-clientinfo.php', 'booker_page_clientinfo', 'booker Page Client Info');

	// client page for listing requests
	qa_register_plugin_module('page', 'booker-page-clientrequests.php', 'booker_page_clientrequests', 'booker Page Client Requests');

	// client page for entering job details
	qa_register_plugin_module('page', 'booker-page-clientschedule.php', 'booker_page_clientschedule', 'booker Page Client schedule');

	// obsolete: contractor page for editing profile data
	// qa_register_plugin_module('page', 'booker-page-clientprofile.php', 'booker_page_clientprofile', 'booker Page Client Profile');

	// client page for ratings of contractors
	qa_register_plugin_module('page', 'booker-page-clientratings.php', 'booker_page_clientratings', 'booker Page Client Ratings');

	// contractor info page: information before registering as contractor
	// qa_register_plugin_module('page', 'booker-page-contractorapply.php', 'booker_page_contractorapply', 'booker Page Contractor Apply');

	// page for contractors: list all bookings
	qa_register_plugin_module('page', 'booker-page-contractorschedule.php', 'booker_page_contractorschedule', 'booker Page Contractor Bookings');

	// page for contractors: contractor calendar
	qa_register_plugin_module('page', 'booker-page-contractorcalendar.php', 'booker_page_contractorcalendar', 'booker Page Contractor Calendar');

	// contractor page for fees (honorar)
	qa_register_plugin_module('page', 'booker-page-contractorbalance.php', 'booker_page_contractorbalance', 'booker Page Contractor Honorar');

	// contractors page schulung, infos, faq, videos
	qa_register_plugin_module('page', 'booker-page-contractorinfo.php', 'booker_page_contractorinfo', 'booker Page Contractor Info');

	// contractors page for customer to see who is available
	qa_register_plugin_module('page', 'booker-page-contractorlist.php', 'booker_page_contractorlist', 'booker Page Contractor List');

	// listing all client requests
	qa_register_plugin_module('page', 'booker-page-requestlist.php', 'booker_page_requestlist', 'booker Page requestlist');

	// listing all contractor offers
	qa_register_plugin_module('page', 'booker-page-offerlist.php', 'booker_page_offerlist', 'booker Page offerlist');

	// contractors page for offers
	qa_register_plugin_module('page', 'booker-page-contractoroffers.php', 'booker_page_contractoroffers', 'booker Page Contractor Offers');

	// contract page - obsolete
	// qa_register_plugin_module('page', 'booker-page-contract.php', 'booker_page_contract', 'booker Page Contract');

	// contractor page to create offers
	qa_register_plugin_module('page', 'booker-page-offercreate.php', 'booker_page_offercreate', 'booker Page offercreate');

	// contractor page to create offers
	qa_register_plugin_module('page', 'booker-page-requestcreate.php', 'booker_page_requestcreate', 'booker Page requestcreate');

	// page central userprofile
	qa_register_plugin_module('page', 'booker-page-userprofile.php', 'booker_page_userprofile', 'booker Page User Profile');

	// page central userprofile
	qa_register_plugin_module('page', 'booker-page-userrecommend.php', 'booker_page_userrecommend', 'booker Page User Recommend');

	// client page for ratings of contractors
	qa_register_plugin_module('page', 'booker-page-contractorratings.php', 'booker_page_contractorratings', 'booker Page Contractor Ratings');

	// contractor page for editing week table
	qa_register_plugin_module('page', 'booker-page-contractorweek.php', 'booker_page_contractorweek', 'booker Page Contractor Week');

	// upload page for client attachments ./mbupload
	qa_register_plugin_module('page', 'booker-page-mbupload.php', 'booker_page_mbupload', 'booker Page Upload');

	// page: contakt
	qa_register_plugin_module('page', 'booker-page-mbcontact.php', 'booker_page_mbcontact', 'booker Page Kontakt');

	// page: contakt
	qa_register_plugin_module('page', 'booker-page-shortlink.php', 'booker_page_shortlink', 'booker Page Shortlink Profile');

	// vcard download
	qa_register_plugin_module('page', 'booker-page-vcard.php', 'booker_page_vcard', 'booker Page vcard');

	// language file
	qa_register_plugin_phrases('booker-lang-*.php', 'q2apro_booker');

	// core function overrides
	// qa_register_plugin_overrides('booker-overrides.php');


	/* custom functions */

	// used for links in emails
	function q2apro_site_url()
	{
		if(strpos($_SERVER['SERVER_NAME'], 'localhost') !== false)
		{
			return 'http://localhost/kvanto/';
		}
		// should be the domain the site is running on, now HTTPS
		return 'https://'.$_SERVER['SERVER_NAME'].'/';
	}

	function q2apro_get_sendermail()
	{
		$sender = qa_opt('booker_email');
		if(empty($sender))
		{
			$sender = qa_opt('feedback_email');
		}
		return $sender;
	}

	function booker_mailfooter()
	{
		return html_entity_decode(qa_opt('booker_mailsenderfooter'));
	}

	function contractorhasweekplan($userid)
	{
		if(!isset($userid)) {
			return false;
		}

		if(qa_opt('booker_showwithnoevents'))
		{
			return true;
		}

		// check if contractor has set dates for the week schedule
		$eventcount = qa_db_read_one_value(
							qa_db_query_sub('SELECT COUNT(*) FROM `^booking_week`
												WHERE `userid` = #
												;', $userid), true );
		return ($eventcount>0);
	}

	function contractorisavailable($userid)
	{
		if(!isset($userid))
		{
			return false;
		}
		// check if exclusive contractor
		$isavailable = booker_get_userfield($userid, 'available');
		return ($isavailable==1);
	}

	function contractorisapproved($userid)
	{
		if(!isset($userid))
		{
			return false;
		}
		$approved = booker_get_userfield($userid, 'approved');
		return ($approved==MB_USER_STATUS_APPROVED);
	}

	function contractorhasskype($userid)
	{
		if(!isset($userid))
		{
			return false;
		}

		$skype = booker_get_userfield($userid, 'skype');

		return !empty($skype);
	}

	function contractorhasphoto($userid)
	{
		if(!isset($userid))
		{
			return false;
		}
		/*
		// not reliable
		$userflags = qa_db_read_one_value(
						qa_db_query_sub('SELECT flags FROM `^users`
											WHERE `userid` = #
											;', $userid), true );

		$avatarexists = ($userflags & QA_USER_FLAGS_SHOW_AVATAR) || ($userflags & QA_USER_FLAGS_SHOW_GRAVATAR);
		*/

		$hasavatar = qa_db_read_one_value(
						qa_db_query_sub('SELECT avatarblobid FROM `^users`
											WHERE `userid` = #
											;', $userid), true );

		$avatarexists = !is_null($hasavatar);
		return $avatarexists;
	}

	function contractorisallowed_bypoints($userid)
	{
		return true;

		/*
		if(!isset($userid)) {
			return false;
		}

		// special some users are directly allowed (but not listed in contractors list)
		$contractor_addallowed = qa_opt('booker_allowedcontractorids');
		if(strpos($contractor_addallowed, (String)($userid)) !== false) {
			return true;
		}

		$minpoints = qa_opt('booker_contractorminpoints');

		// check if exclusive contractor
		$userscore = qa_db_read_one_value(
						qa_db_query_sub('SELECT points FROM `^userpoints`
											WHERE `userid` = #
											;', $userid), true);
		return ($userscore > $minpoints);
		*/
	}

	function getbookingtable($bookid)
	{
		// if is offer, then only one day booked, one bookid is connected to one event only
		if(booker_bookingisoffer($bookid))
		{
			$bookdata = qa_db_read_one_assoc(
							qa_db_query_sub('SELECT eventid, bookid, offerid, created, contractorid, starttime, endtime, customerid, unitprice, status FROM ^booking_orders
															WHERE bookid = #
															',
															$bookid));
			$output = '';
			$output .= '<table id="bookingtable">';
			$output .= '
				<thead>
					<tr>
						<th>'.qa_lang('booker_lang/date').'</th>
						<th>'.qa_lang('booker_lang/price').'</th>
					</tr>
				</thead>
				<tbody>
			';

			$weekdays = helper_get_weekdayname_array();
			$eventprice = 0;

			$date = date(qa_lang('booker_lang/date_format_php'), strtotime($bookdata['starttime']));
			$weekday = $weekdays[ date('w',strtotime($bookdata['starttime'])) ];
			$eventprice = booker_get_eventprice($bookdata['eventid'], true);

			$output .= '
					<tr>
						<td class="booktime">'.$weekday.', '.$date.'</td>
						<td class="bookcalc">'.$eventprice.' '.qa_opt('booker_currency').'</td>
					</tr>
			';

			$output .= '
					<tr id="sumrow">
						<td>'.qa_lang('booker_lang/sum').':</td>
						<td id="booksum">'.$eventprice.' '.qa_opt('booker_currency').'</td>
					</tr>

				</tbody>
			</table>';

			// special css
			$output .= '
			<style type="text/css">
				#bookingtable th, #bookingtable td {
					text-align:left !important;
				}
			</style>
			';
		}
		else
		{
			// time based booking, then one bookid can contain several events
			$bookdata = qa_db_read_all_assoc(
							qa_db_query_sub('SELECT eventid, bookid, offerid, created, contractorid, starttime, endtime, customerid, unitprice, status FROM ^booking_orders
															WHERE bookid = #
															ORDER BY starttime ASC',
															$bookid));
			if(!isset($bookdata))
			{
				return qa_lang('booker_lang/no_bookings');
			}

			$output = '';
			$output .= '<table id="bookingtable">';
			$output .= '
				<thead>
					<tr>
						<th>'.qa_lang('booker_lang/date').'</th>
						<th>'.qa_lang('booker_lang/time').'</th>
						<th>'.qa_lang('booker_lang/timespan').'</th>
						<th>'.qa_lang('booker_lang/price').'</th>
					</tr>
				</thead>
				<tbody>
			';

			$totalprice = 0;
			$weekdays = helper_get_weekdayname_array();
			foreach($bookdata as $bookitem)
			{
				$date = date(qa_lang('booker_lang/date_format_php'), strtotime($bookitem['starttime']));
				$weekday = $weekdays[ date('w',strtotime($bookitem['starttime'])) ];
				$starttime = date('H:i',strtotime($bookitem['starttime']));
				$endtime = date('H:i',strtotime($bookitem['endtime']));
				$timespan = (strtotime($bookitem['endtime']) - strtotime($bookitem['starttime']))/60;
				$eventprice = booker_get_eventprice($bookitem['eventid']);
				$totalprice += $eventprice;

				$output .= '
						<tr>
							<td class="booktime">'.$weekday.', '.$date.'</td>
							<td class="booktimehours">'.$starttime.' '.qa_lang('booker_lang/ev_until').' '.$endtime.' '.qa_lang('booker_lang/timeabbr').'</td>
							<td class="booktimeabs">'.$timespan.' min</td>
							<td class="bookcalc">'.number_format($eventprice,2,',','.').' '.qa_opt('booker_currency').'</td>
						</tr>
				';
			}

			$output .= '
					<tr id="sumrow">
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="background:#FFC;">'.qa_lang('booker_lang/sum').':</td>
						<td id="booksum" style="background:#FFC;">'.number_format($totalprice,2,',','.').' '.qa_opt('booker_currency').'</td>
					</tr>

				</tbody>
			</table>';

		}

		return $output;
	} // end getbookingtable($bookid)

	function csstablestyles()
	{
			return '
				<style type="text/css">
					.bookingtablewrap {
						width:70%;
						display:block;
						text-align:right;
					}
					#bookingtable {
						display:inline-block;
						border-collapse:collapse;
						margin-top:5px;
						line-height:140%;
					}
					#bookingtable th {
						font-weight:normal;
					}
					#bookingtable td, #bookingtable th {
						border:1px solid #CCC;
					}
					#bookingtable td, #bookingtable th {
						padding:3px 5px;
						border:1px solid #DDD;
						font-weight:normal;
					}
					#bookingtable td {
						text-align:center;
					}
					.bookingtablewrap-checkout #bookingtable, .bookingtablewrap-email #bookingtable {
						display:table;
						background:#FFF;
						width:40%;
						max-width:650px;
					}
					.bookingtablewrap-checkout #bookingtable tr, .bookingtablewrap-email #bookingtable tr {
						width:100%;
					}

				</style>
				';
	} // end csstablestyles()

	function cssemailstyles()
	{
			return '
			<style type="text/css">
				.msgstatus, .msgtime {
					display:block;
				}
				.msgmeta {
					display:block;
					margin:5px 0 10px 0;
					border:1px solid #DDF;
					max-width:600px;
				}
				.msgmeta p:first-child {
					margin:0;
					background:#F5F5FA;
					padding:7px;
				}
				.msgmeta .msgcontent {
					margin-left:5px;
				}
				.defaultbutton {
					display:inline-block;
					padding:10px 15px;
					margin:0;
					font-size:14px;
					color:#FFF;
					background:#44E;
					border:1px solid #33E;
					border-radius:0px;
					cursor:pointer;
					text-decoration:none !important;
				}
				.defaultbutton:hover {
					background: #33E;
					color:#FFF;
				}
			</style>
		';
	} // end cssemailstyles

	// also unlocks events
	function booker_set_status_booking($bookid, $status, $paymethod=null)
	{
		// should be only MB_EVENT_RESERVED or MB_EVENT_PAID

		if(!is_null($paymethod))
		{
			// set status of all events that belong to the booking
			qa_db_query_sub('UPDATE `^booking_orders` SET status = #, payment = #
								WHERE bookid = #',
								$status, $paymethod, $bookid);
		}
		else
		{
			// set status of all events that belong to the booking
			qa_db_query_sub('UPDATE `^booking_orders` SET status = #
								WHERE bookid = #',
								$status, $bookid);
		}

		// get all events that belong to bookid for logging
		$events = qa_db_read_all_assoc(
						qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, needs, unitprice, payment, status FROM ^booking_orders
								WHERE bookid = #
								;',
								$bookid)
					);

		$totalprice = booker_get_booking_totalprice($bookid);
		foreach($events as $event)
		{
			$userid = $event['customerid'];
			$eventid = $event['eventid'];
			$eventname = booker_get_eventname($status);
			$params = array(
				'bookid' => $bookid,
				'bookamount' => $totalprice,  // *** amount should actually reflect the real payment
				'paymethod' => $paymethod
			);
			booker_log_event($userid, $eventid, $eventname, $params);
		}

		// *** alternatively we could:
		// 1. gather all events that belong to bookid
		// 2. use booker_set_status_event() in a foreach

	} // end booker_set_status

	function booker_get_status_booking($bookid)
	{
		$status = qa_db_read_one_value(
					qa_db_query_sub('SELECT status FROM `^booking_orders`
									WHERE bookid = #',
									$bookid), true);
		return $status;
	}

	// calculate eventprice by start and end time and unitprice, formatted is readable, otherwise float
	function booker_get_eventprice($eventid, $formatted=false)
	{
		$eventdata = qa_db_read_one_assoc(
						qa_db_query_sub('SELECT offerid, starttime, endtime, unitprice FROM ^booking_orders
											WHERE eventid = #
											LIMIT 1',
											$eventid), true
										);
		if(!is_null($eventdata))
		{
			// time based event, no offer
			if(is_null($eventdata['offerid']))
			{
				$eventduration = strtotime($eventdata['endtime']) - strtotime($eventdata['starttime']);
				$eventduration /= 60; // minutes
				$eventprice = round($eventduration/60 * $eventdata['unitprice'], 2);
				if($formatted)
				{
					// return number_format($eventprice, 2, ',', '.');
					return helper_format_currency($eventprice, 2, true);
				}
				else
				{
					return (float)$eventprice;
				}
			}
			else
			{
				// return price of offer which is unitprice
				return helper_format_currency($eventdata['unitprice'], 2, true);
			}
		}

		return null;
	} // end booker_get_eventprice

	function booker_get_booking_totalprice($bookid)
	{
		$bookdata = qa_db_read_all_assoc(
						qa_db_query_sub('SELECT eventid FROM ^booking_orders
											WHERE bookid = #
											ORDER BY starttime ASC',
											$bookid)
										);
		$totalprice = 0;
		foreach($bookdata as $bookitem)
		{
			$totalprice += booker_get_eventprice($bookitem['eventid']);
		}

		return $totalprice;
	} // end booker_get_booking_totalprice

	function booker_set_status_event($eventid, $status)
	{
		// set new status of event
		qa_db_query_sub('UPDATE `^booking_orders` SET status = #
							WHERE eventid = #',
							$status, $eventid);
	}

	function booker_get_status_event($eventid)
	{
		$status = qa_db_read_one_value(
					qa_db_query_sub('SELECT status FROM `^booking_orders`
									WHERE eventid = #',
									$eventid), true);
		return $status;
	}

	function booker_change_payment($bookid, $paymethod)
	{
		// only changes paymethod
		qa_db_query_sub('UPDATE ^booking_orders SET payment = #
							WHERE bookid = #
							', $paymethod, $bookid);
	} // end booker_change_payment

	// GET time done in Minutes
	function booker_get_timediff($start, $end)
	{
		$starttime = strtotime($start);
		$endtime = strtotime($end);
		return ($endtime - $starttime)/60;
	} // end booker_get_timediff

	function booker_register_payment_booking($bookid, $amount=null)
	{
		$bookedevent = qa_db_read_one_assoc(
							qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, needs, unitprice, payment, status FROM `^booking_orders`
								WHERE bookid = #
								;', $bookid)
						);

		$contractorid = $bookedevent['contractorid'];
		$customerid = $bookedevent['customerid'];
		$customername = booker_get_userfield($bookedevent['customerid'], 'realname');
		$needs = $bookedevent['needs'];
		$payment = $bookedevent['payment'];

		// get *all* events to calculate the price
		$bookdata = qa_db_read_all_assoc(
						qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, needs, unitprice, payment, status FROM ^booking_orders
								WHERE bookid = #
								;',
								$bookid)
					);
		$totalprice = 0;
		foreach($bookdata as $bookitem)
		{
			$totalprice += booker_get_eventprice($bookitem['eventid']);
		}

		// in case we have a partly payment, the function will be called with the 2nd parameter $amount
		if(isset($amount))
		{
			$totalprice = $amount;
		}

		// write payment to database
		qa_db_query_sub('INSERT INTO `^booking_payments` (paytime, bookid, userid, paymethod, amount)
							VALUES(NOW(), #, #, #, #)',
							$bookid, $customerid, $payment, $totalprice);

		// LOG *** go over each event?
		$eventid = null;
		$eventname = booker_get_eventname(MB_EVENT_PAID); // *** eventname should actually be "booking_paid" or "order_paid"
		$params = array(
			'bookid' => $bookid,
			'bookamount' => $totalprice,
			'paymethod' => $payment
		);
		booker_log_event($customerid, $eventid, $eventname, $params);
	} // end booker_register_payment


	function booker_register_payment_premium($orderid, $userid, $amount, $premiumtype, $paymethod)
	{
		$realname = booker_get_userfield($userid, 'realname');

		// write payment to database
		qa_db_query_sub('INSERT INTO `^booking_payments` (paytime, orderid, bookid, userid, paymethod, amount)
							VALUES(NOW(), #, #, #, #, #)',
							$orderid, null, $userid, $paymethod, $amount);

		// LOG
		$eventid = null;
		$eventname = 'premium_paid';
		$params = array(
			'type' => booker_get_premiumname($premiumtype),
			'amount' => $amount,
			'paymethod' => $paymethod,
			'username' => $realname
		);
		booker_log_event($userid, $eventid, $eventname, $params);
	} // end booker_register_payment_premium


	function booker_get_premiumname($premiumtype)
	{
		if($premiumtype==1)
		{
			return 'premium';
		}
		if($premiumtype==2)
		{
			return 'vip';
		}
		else
		{
			return 'unknown premiumtype';
		}
	} // booker_get_premiumname

	function booker_get_orderid($premiumtype)
	{
		$userid = qa_get_logged_in_userid();

		$orderid = $userid.'_'.$premiumtype.'_'.time();
		/*$orderid = qa_db_read_one_value(
							qa_db_query_sub('
								SELECT payid FROM `^booking_payments`
								ORDER BY payid DESC
								'), true
						);
		// if empty table
		if(empty($orderid))
		{
			$orderid = 1;
		}
		else
		{
			$orderid = (int)$orderid;
			$orderid++;
		}
		*/

		return $orderid;
	} // booker_get_orderid

	function get_contractorweektimes($weekschedule)
	{
		$weekdays = helper_get_weekdayname_array(); // 0 to 6
		$weekoutput = '';
		// $weekoutput .= '<p class="contractoravailable"><b>Zu diesen Zeiten verfügbar:</b><br /> ';
		$weekoutput .= '
				<p class="contractoravailable">'.
					qa_lang('booker_lang/prefered_times').':
				</p>
				<div class="timetable">
			';
		if(count($weekschedule)==0)
		{
			$weekoutput .= qa_lang('booker_lang/prefered_times_none').'<br />';
		}
		else
		{
			$eventcount = 0;
			$recentday = '';
			foreach($weekschedule as $times)
			{
				$eventcount++;
				// several times the same day
				if($recentday == $times['weekday'])
				{
					$weekoutput .= ', '.substr($times['starttime'],0,5).' - '.substr($times['endtime'],0,5).' '.qa_lang('booker_lang/timeabbr');
				}
				else
				{
					// new day
					if($eventcount>1)
					{
						$weekoutput .= '
							</div> <!-- daytimes -->
						</div> <!-- dayline -->
						';
					}
					$weekoutput .= '
						<div class="dayline">
							<div class="dayinweek">'.$weekdays[$times['weekday']].'</div>
							<div class="daytimes">'.substr($times['starttime'],0,5).' - '.substr($times['endtime'],0,5).' '.qa_lang('booker_lang/timeabbr');
				}
				$recentday = $times['weekday'];
			}

			$weekoutput .= '
							</div> <!-- daytimes -->
						</div> <!-- dayline -->
						';
			// remove ":00" for full hours
			$weekoutput = str_replace(':00', '', $weekoutput);

		}
		$weekoutput .= '</div> <!-- timetable -->';
		return $weekoutput;
	} // end get_contractorweektimes

	function booker_sendmail_customer_paid_bookid($bookid)
	{
		if(!isset($bookid))
		{
			return;
		}

		$bookedevent = qa_db_read_one_assoc(
							qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, needs, unitprice, payment, status FROM `^booking_orders`
								WHERE bookid = #
								;', $bookid)
						);

		$contractorid = $bookedevent['contractorid'];
		$customerid = $bookedevent['customerid'];

		$contractor_skype = booker_get_userfield($contractorid, 'skype');

		$contractorname = booker_get_realname($contractorid);

		$customername = booker_get_realname($customerid);

		$subject = qa_lang('booker_lang/appts_with').' '.$contractorname.' '.qa_lang('booker_lang/appt_paid');
		$emailbody = '';
		$emailbody .= '
			<p>
				'.qa_lang('booker_lang/hello').' '.$customername.',
			</p>
			<p>
				'.qa_lang('booker_lang/mail_thxpaid').' <a href="'.q2apro_site_url().'mbmessages?to='.$contractorid.'">'.$contractorname.'</a> '.qa_lang('booker_lang/mail_willcontact').'
			</p>
			<p>
				'.qa_lang('booker_lang/mail_askdetails').'
			</p>
			<p>
				<a href="'.q2apro_site_url().'clientschedule" class="defaultbutton">'.qa_lang('booker_lang/mail_btndetails').'</a>
			</p>
			<p>
				'.qa_lang('booker_lang/mail_yourappts').'
			</p>
		';
		$emailbody .= '<div class="bookingtablewrap-email">'.
						getbookingtable($bookid).
					  '</div>';
		$emailbody .= '
					<p>
						'.
						strtr( qa_lang('booker_lang/mail_canmessage'), array(
						'^1' => '<a href="'.q2apro_site_url().'mbmessages?to='.$contractorid.'">',
						'^2' => '</a>'
						)).
						'
					</p>
					';
		$emailbody .= '
					<p>
						'.qa_lang('booker_lang/mail_greetings').'
						<br />
						'.qa_lang('booker_lang/mail_greeter').'
					</p>';
		$emailbody .= booker_mailfooter();
		$emailbody .= csstablestyles();
		$emailbody .= cssemailstyles();

		require_once QA_INCLUDE_DIR.'qa-app-emails.php';

		$bcclist = explode(';', qa_opt('booker_mailcopies'));
		q2apro_send_mail(array(
					'fromemail' => q2apro_get_sendermail(),
					'fromname'  => qa_opt('booker_mailsendername'),
					// 'toemail'   => $toemail,
					'senderid'	=> 1, // for log
					'touserid'  => $customerid,
					'toname'    => $customername,
					'bcclist'   => $bcclist,
					'subject'   => $subject,
					'body'      => $emailbody,
					'html'      => true
		));
	} // end booker_sendmail_customer_paid_bookid


	function booker_sendmail_contractor_bookidpaid($bookid)
	{
		if(!isset($bookid))
		{
			return;
		}

		$bookedevent = qa_db_read_one_assoc(
							qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, needs, unitprice, payment, status FROM `^booking_orders`
								WHERE bookid = #
								;', $bookid)
						);

		$contractorid = $bookedevent['contractorid'];
		$customerid = $bookedevent['customerid'];

		$customername = booker_get_userfield($bookedevent['customerid'], 'realname');
		$customerskype = booker_get_userfield($bookedevent['customerid'], 'skype');
		$customerphone = booker_get_userfield($bookedevent['customerid'], 'phone');

		$needs = (empty($bookedevent['needs']) ? qa_lang('booker_lang/notmentioned') : $bookedevent['needs']);
		$contractorname = booker_get_realname($contractorid);

		// prepare contact string
		$contactdetails = '<a href="'.q2apro_site_url().'mbmessages?to='.$customerid.'">'.qa_lang('booker_lang/directmessage').'</a>';
		if(!empty($customerphone))
		{
			$contactdetails .= ' | '.qa_lang('booker_lang/telephone').' '.$customerphone;
		}
		if(!empty($customerskype))
		{
			$contactdetails .= ' | <a href="skype:'.$customerskype.'?chat">Skype</a>';
		}

		// EMAIL
		$subject = qa_lang('booker_lang/confirm_appts').' ('.$customername.')';
		$emailbody = '';
		$emailbody .= '
			<p>
				'.qa_lang('booker_lang/hello').' '.$contractorname.',
			</p>
			<p>
				'.
				str_replace('~~~',
							'<a href="'.q2apro_site_url().'mbmessages?to='.$customerid.'">'.$customername.'</a>',
							qa_lang('booker_lang/mailtocon_custopaid')).
				' '.
				strtr( qa_lang('booker_lang/mailtocon_doconfirm'), array(
					'^1' => '<a href="'.q2apro_site_url().'contractorschedule">',
					'^2' => '</a>'
				)).
				'
			</p>
			<p>
				<a href="'.q2apro_site_url().'contractorschedule" class="defaultbutton">'.qa_lang('booker_lang/confirm_appts').'</a>
			</p>
			<p>
				'.qa_lang('booker_lang/mailtocon_contactcusto').' '
				.$contactdetails.'
			</p>
			<p>
				'.
				str_replace('~~~', $bookid, qa_lang('booker_lang/mailtocon_bookdetails')).
				'
			</p>
			<div class="bookingtablewrap-email">'.
				getbookingtable($bookid).
			'</div>
			<p>
				'.qa_lang('booker_lang/mail_greetings').'
				<br />
				'.qa_lang('booker_lang/mail_greeter').'
			</p>
		';

		$emailbody .= booker_mailfooter();
		$emailbody .= csstablestyles();
		$emailbody .= cssemailstyles();

		require_once QA_INCLUDE_DIR.'qa-app-emails.php';

		$bcclist = explode(';', qa_opt('booker_mailcopies'));
		q2apro_send_mail(array(
					'fromemail' => q2apro_get_sendermail(),
					'fromname'  => qa_opt('booker_mailsendername'),
					'senderid'	=> 1, // for log
					'touserid'  => $contractorid,
					'toname'    => $contractorname,
					'bcclist'   => $bcclist,
					'subject'   => $subject,
					'body'      => $emailbody,
					'html'      => true
		));

	} // end booker_sendmail_contractor_bookidpaid

	function booker_sendmail_contractors_available_events($eventid)
	{
		if(!isset($eventid))
		{
			return;
		}

		$bookedevent = qa_db_read_one_assoc(
							qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, needs, unitprice, payment, status FROM `^booking_orders`
								WHERE eventid = #
								;', $eventid)
						);

		$contractorid = $bookedevent['contractorid'];
		$customerid = $bookedevent['customerid'];

		// list all contractorids
		$allcontractors = booker_get_alternative_contractors_for_event($eventid);
		// DEV
		return;


		// only consider contractorids in separate array
		$allcontractorids = array();
		foreach($allcontractors as $con)
		{
			array_push($allcontractorids, $con['userid']);
		}

		// remove contractor from email list that rejected the event
		if(($key = array_search($contractorid, $allcontractorids)) !== false)
		{
			unset($allcontractorids[$key]);
		}

		foreach($allcontractorids as $tutid)
		{
			$contractorname = booker_get_realname($tutid);

			// EMAIL
			$subject = qa_lang('booker_lang/eventsavailable');
			$emailbody = '';
			$emailbody .= '
				<p>
					'.qa_lang('booker_lang/hello').' '.$contractorname.',
				</p>
				<p>
					'.qa_lang('booker_lang/mail_neweventsav').'
				</p>
				<p>
					<a href="'.q2apro_site_url().'eventmanager" class="defaultbutton">'.qa_lang('booker_lang/mail_gotoevents').'</a>
				</p>
				<p>
					'.qa_lang('booker_lang/mail_greetings').'
					<br />
					'.qa_lang('booker_lang/mail_greeter').'
				</p>
			';
			$emailbody .= booker_mailfooter();
			$emailbody .= cssemailstyles();

			require_once QA_INCLUDE_DIR.'qa-app-emails.php';

			$bcclist = explode(';', qa_opt('booker_mailcopies'));
			q2apro_send_mail(array(
						'fromemail' => q2apro_get_sendermail(),
						'fromname'  => qa_opt('booker_mailsendername'),
						'senderid'	=> 1, // for log
						'touserid'  => $tutid,
						'toname'    => $contractorname,
						'bcclist'   => $bcclist,
						'subject'   => $subject,
						'body'      => $emailbody,
						'html'      => true,
						'notrack'	=> true
			));
		} // end foreach

	} // end booker_sendmail_contractors_available_events

	// contractor rejected the event, tell the client about it by email
	function booker_sendmail_client_contractorrejected($eventid)
	{
		if(!isset($eventid))
		{
			return;
		}

		$bookedevent = qa_db_read_one_assoc(
							qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, needs, unitprice, payment, status FROM `^booking_orders`
								WHERE eventid = #
								;', $eventid)
						);

		$contractorid = $bookedevent['contractorid'];
		$contractorname = booker_get_realname($bookedevent['contractorid']);
		$customerid = $bookedevent['customerid'];
		$customername = booker_get_realname($bookedevent['customerid']);

		$rejectreason = qa_db_read_one_value(
							qa_db_query_sub('SELECT params FROM `^booking_logs`
												WHERE `eventid` = #
												AND userid = #
												AND eventname = #
												;', $eventid, $contractorid, 'contractor_rejected'),
											true);
		$reason = '';
		if(!empty($rejectreason))
		{
			$toURL = str_replace("\t","&", $rejectreason);
			parse_str($toURL, $params);
			$reason = $params['reason'];
		}

		// EMAIL
		$subject = str_replace('~~~', $contractorname, qa_lang('booker_lang/mail_eventrejected_subject'));
		$emailbody = '';
		$emailbody .= '
			<p>
				'.qa_lang('booker_lang/hello').' '.$customername.',
			</p>
			<p>
				'.str_replace('~~~', $contractorname, qa_lang('booker_lang/mail_eventrejected')).'
				'.str_replace('~~~', $reason, qa_lang('booker_lang/mail_rejectreason')).'
			</p>
			<p>
				'.qa_lang('booker_lang/mail_othersinformed').'
			</p>
			<p>
				'.qa_lang('booker_lang/mail_greetings').'
				<br />
				'.qa_lang('booker_lang/mail_greeter').'
			</p>
		';
		$emailbody .= booker_mailfooter();
		$emailbody .= cssemailstyles();

		require_once QA_INCLUDE_DIR.'qa-app-emails.php';

		$bcclist = explode(';', qa_opt('booker_mailcopies'));
		q2apro_send_mail(array(
					'fromemail' => q2apro_get_sendermail(),
					'fromname'  => qa_opt('booker_mailsendername'),
					'senderid'	=> 1, // for log
					'touserid'  => $customerid,
					'toname'    => $customername,
					'bcclist'   => $bcclist,
					'subject'   => $subject,
					'body'      => $emailbody,
					'html'      => true
		));

	} // end booker_sendmail_client_contractorrejected

	// inform client when a contractor proposes new event times
	function booker_sendmail_client_eventtime_suggestion($eventid)
	{
		if(!isset($eventid))
		{
			return;
		}

		$bookedevent = qa_db_read_one_assoc(
							qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, needs, unitprice, payment, status FROM `^booking_orders`
								WHERE eventid = #
								;', $eventid)
						);

		$contractorid = $bookedevent['contractorid'];
		$customerid = $bookedevent['customerid'];
		$customername = booker_get_realname($bookedevent['customerid']);

		// EMAIL
		$subject = qa_lang('booker_lang/mail_newtime_subject');
		$emailbody = '';
		$emailbody .= '
			<p>
				'.qa_lang('booker_lang/hello').' '.$customername.',
			</p>
			<p>
				'.qa_lang('booker_lang/mail_newtime').'
			</p>
			<p>
				<a href="'.q2apro_site_url().'eventmanager?id='.$eventid.'" class="defaultbutton">'.qa_lang('booker_lang/mail_gotoevents').'</a>
			</p>
			<p>
				'.qa_lang('booker_lang/mail_greetings').'
				<br />
				'.qa_lang('booker_lang/mail_greeter').'
			</p>
		';
		$emailbody .= booker_mailfooter();
		$emailbody .= cssemailstyles();

		require_once QA_INCLUDE_DIR.'qa-app-emails.php';

		$bcclist = explode(';', qa_opt('booker_mailcopies'));
		q2apro_send_mail(array(
					'fromemail' => q2apro_get_sendermail(),
					'fromname'  => qa_opt('booker_mailsendername'),
					'senderid'	=> 1, // for log
					'touserid'  => $customerid,
					'toname'    => $customername,
					'bcclist'   => $bcclist,
					'subject'   => $subject,
					'body'      => $emailbody,
					'html'      => true
		));

	} // end booker_sendmail_client_eventtime_suggestion

	// contractor accepted the event, tell the client about it by email
	function booker_sendmail_client_contractoraccepted($eventid)
	{
		if(!isset($eventid))
		{
			return;
		}

		$bookedevent = qa_db_read_one_assoc(
							qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, needs, unitprice, payment, status FROM `^booking_orders`
								WHERE eventid = #
								;', $eventid)
						);

		$contractorid = $bookedevent['contractorid'];
		$contractorname = booker_get_realname($bookedevent['contractorid']);
		$customerid = $bookedevent['customerid'];
		$customername = booker_get_realname($bookedevent['customerid']);

		// EMAIL
		$subject = str_replace('~~~', $contractorname, qa_lang('booker_lang/mail_eventaccepted_subject'));
		$emailbody = '';
		$emailbody .= '
			<p>
				'.qa_lang('booker_lang/hello').' '.$customername.',
			</p>
			<p>
				'.str_replace('~~~', $contractorname, qa_lang('booker_lang/mail_eventaccepted')).'
			</p>
			<p>
				'.qa_lang('booker_lang/mail_seeyourschedule').':
			</p>
			<p>
				<a href="'.q2apro_site_url().'clientschedule" class="defaultbutton">'.qa_lang('booker_lang/myappts').'</a>
			</p>
			<p>
				'.qa_lang('booker_lang/mail_greetings').'
				<br />
				'.qa_lang('booker_lang/mail_greeter').'
			</p>
		';
		$emailbody .= booker_mailfooter();
		$emailbody .= cssemailstyles();

		require_once QA_INCLUDE_DIR.'qa-app-emails.php';

		$bcclist = explode(';', qa_opt('booker_mailcopies'));
		q2apro_send_mail(array(
					'fromemail' => q2apro_get_sendermail(),
					'fromname'  => qa_opt('booker_mailsendername'),
					'senderid'	=> 1, // for log
					'touserid'  => $customerid,
					'toname'    => $customername,
					'bcclist'   => $bcclist,
					'subject'   => $subject,
					'body'      => $emailbody,
					'html'      => true
		));

	} // end booker_sendmail_client_contractoraccepted

	function helper_gethandle($userid)
	{
		return qa_db_read_one_value(
				qa_db_query_sub('SELECT handle FROM `^users`
										WHERE `userid` = #
										;', $userid),
								true);
	}

	function helper_getemail($userid)
	{
		return qa_db_read_one_value(
				qa_db_query_sub('SELECT email FROM `^users`
										WHERE `userid` = #
										;', $userid),
								true);
	}

	// returns start and end time of event, human readable or as timestamp
	function helper_geteventtimes($eventid, $linebreak=true)
	{
		$event = qa_db_read_one_assoc(
						qa_db_query_sub('SELECT starttime,endtime FROM `^booking_orders`
											WHERE eventid = #
											', $eventid), true
										  );
		if(!isset($event))
		{
			return;
		}

		if(count($event)==0)
		{
			return; // no times
		}

		$start = $event['starttime'];
		$end = $event['endtime'];
		$weekdays = helper_get_weekdayarray();
		// same day
		if( substr($start,0, 10) == substr($end,0, 10) )
		{
			$timestring = $weekdays[ (int)(date('N', strtotime($event['starttime'])))-1 ].', '.date(qa_lang('booker_lang/date_format_php'), strtotime($event['starttime'])).
							($linebreak?'<br />':'').substr($start,10,6).' - '.substr($end,10,6);
			return $timestring;
		}
		else
		{
			// over night
			$timestring = date(qa_lang('booker_lang/date_format_php'), strtotime($event['starttime'])).' '.substr($start,10,6).' - '.
							($linebreak?'<br />':'').date(qa_lang('booker_lang/date_format_php'), strtotime($event['endtime'])).' '.substr($end,10,6);
			return $timestring;
		}
	} // end helper_geteventtimes

	function helper_geteventstartday($eventid)
	{
		$starttime = qa_db_read_one_value(
						qa_db_query_sub('SELECT starttime FROM `^booking_orders`
											WHERE eventid = #
											', $eventid), true
										  );
		if(count($starttime)==0)
		{
			return; // no times
		}

		$weekdays = helper_get_weekdayarray();
		$timestring = $weekdays[ (int)(date('N', strtotime($starttime)))-1 ].', '.date(qa_lang('booker_lang/date_format_php'), strtotime($starttime));
		return $timestring;
	} // end helper_geteventday

	function helper_get_pseudobookings($weekid, $contractorid)
	{
		// $durations = array(30, 60, 90, 120);
		// more probabilitiy for 60 and 90 min
		$durations = array(30, 30, 60, 60, 60, 90, 90, 90, 120);

		// we produce some fixed "random" here
		$helper = $weekid*$contractorid; // - $weekid%3;
		$weekday_one = $helper%7; // result 0...6
		$weekday_two = ($weekday_one + $contractorid)%7;

		$hours_one = $helper%10 + 10; // result 10...20
		$hours_two = ($helper + $contractorid + $weekid)%10 + 10; // result 10...20
		$duration_one = $durations[$helper%9];
		$duration_two = $durations[$hours_two%9];

		$time_one = 0;
		$time_two = 0;

		// get first day of week from recent year
		$recentyear = date('Y');
		$weekid_lz = str_pad($weekid, 2, '0', STR_PAD_LEFT); // leading zero, e.g. 1 becomes 01
		// $startdate = date('M d',strtotime($recentyear.'W'.$weekid_lz)); // 2015W34
		$startdate = strtotime($recentyear.'W'.$weekid_lz); // 2015W34
		$time_one = $startdate + $weekday_one*24*60*60 + $hours_one*60*60;
		$time_one_end = $time_one + $duration_one*60;
		$time_two = $startdate + $weekday_two*24*60*60 + $hours_two*60*60;
		$time_two_end = $time_two + $duration_two*60;

		$times = array();
		// $pseu_start = '2015-10-16 10:00:00'; $pseu_end = '2015-10-16 11:00:00';

		// FIRST
		array_push($times, array( 'start' => date("Y-m-d H:i:s",$time_one), 'end' => date("Y-m-d H:i:s",$time_one_end)) );

		// SECOND
		// array_push($times, array( 'start' => date("Y-m-d H:i:s",$time_two), 'end' => date("Y-m-d H:i:s",$time_two_end)) );

		// 3 events if even week, otherwise only 2
		/*
		if($weekid%2==0)
		{
			$weekday_three = ($weekid + $weekday_one + $contractorid)%7;
			$hours_three = ($hours_two + $weekid%5)%10 + 10; // result 10...20
			$duration_three = $durations[$hours_three%9];
			$time_three = 0;

			$time_three = $startdate + $weekday_three*24*60*60 + $hours_three*60*60;
			$time_three_end = $time_three + $duration_three*60;
			// THIRD
			array_push($times, array( 'start' => date("Y-m-d H:i:s",$time_three), 'end' => date("Y-m-d H:i:s",$time_three_end)) );
		}
		*/
		return $times;
	}

	function roundandcomma($amount, $digits=2)
	{
		return number_format($amount, $digits, ',', '.');
	}

	// can be either deposit (Guthaben) or clearance (Kontoausgleich)
	function booker_sendmail_customer_paid_deposit($payid, $isclearance=false)
	{
		if(!isset($payid))
		{
			return;
		}

		$payment = qa_db_read_one_assoc(
							qa_db_query_sub('SELECT paytime, bookid, userid, paymethod, amount, onhold FROM `^booking_payments`
								WHERE payid = #
								;', $payid)
						);

		$customerid = $payment['userid'];

		$username = booker_get_realname($customerid);

		$amount_show = number_format($payment['amount'],2,',','.').' '.qa_opt('booker_currency');

		if($isclearance)
		{
			$subject = qa_lang('booker_lang/mail_clearancesuccess');
			$emailbody = '';
			$emailbody .= '
				<p>
					'.str_replace('~~~', $amount_show, qa_lang('booker_lang/mail_clearanceintro')).'
				</p>
				<p>
					'.qa_lang('booker_lang/mail_greetings').'
					<br />
					'.qa_lang('booker_lang/mail_greeter').'
				</p>
				';
		}
		else
		{
			$subject = qa_lang('booker_lang/mail_depositsuccess_subject');
			$emailbody = '';
			$emailbody .= '
				<p>
					'.str_replace('~~~', $amount_show, qa_lang('booker_lang/mail_clearanceintro')).'
				</p>
				<p>
					'.
					strtr( qa_lang('booker_lang/mail_bookcontractors'), array(
					'^1' => '<a href="'.q2apro_site_url().'contractorlist">',
					'^2' => '</a>'
					)).
					'
				</p>
				<p>
					'.qa_lang('booker_lang/mail_greetings').'
					<br />
					'.qa_lang('booker_lang/mail_greeter').'
				</p>
				';
		}
		$emailbody .= booker_mailfooter();

		require_once QA_INCLUDE_DIR.'qa-app-emails.php';

		$bcclist = explode(';', qa_opt('booker_mailcopies'));
		q2apro_send_mail(array(
					'fromemail' => q2apro_get_sendermail(),
					'fromname'  => qa_opt('booker_mailsendername'),
					'senderid'	=> 1, // for log
					'touserid'  => $customerid,
					'toname'    => $username,
					'bcclist'   => $bcclist,
					'subject'   => $subject,
					'body'      => $emailbody,
					'html'      => true
		));

	} // end booker_sendmail_customer_paid_deposit

	// get balance of customer, take all completed events into consideration
	// if $considerall, then all future events are also considered
	function booker_get_balance($userid, $considerall=false)
	{
		// if $considerall then all reserved and booked events will be considered, see page-pay for Guthaben calculation

		$saldo = 0;
		$userpayments = qa_db_read_all_assoc(
							qa_db_query_sub('SELECT paytime, bookid, userid, paymethod, amount FROM `^booking_payments`
													WHERE `userid` = #
													AND `onhold` IS NULL
													AND `ishonorar` IS NULL
													ORDER BY paytime ASC
													;', $userid) );
		foreach($userpayments as $pay)
		{
			$saldo += $pay['amount'];
		}

		// only completed events
		$statusqu = 'AND status = '.MB_EVENT_COMPLETED;
		if($considerall)
		{
			$statusqu = 'AND status > '.MB_EVENT_RESERVED;
		}


		$bookedevents = qa_db_read_all_assoc(
							qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, unitprice, status FROM `^booking_orders`
													WHERE customerid = #
													'.$statusqu.'
													ORDER BY starttime
													;', $userid)
						);

		foreach($bookedevents as $event)
		{
			// if($considerall)
			$timedone = booker_get_timediff($event['starttime'], $event['endtime']);
			$saldo -= $timedone/60*$event['unitprice'];
		}

		return $saldo;

	} // end booker_get_balance

	// get balance of customer, take all completed events into consideration
	// if $considerall, then all future events are also considered
	function booker_get_bookingvolume($userid, $considerall=false)
	{
		// if $considerall then all reserved and booked events will be considered, see page-pay for Guthaben calculation

		$volume = 0;

		// only completed events
		$statusqu = 'AND status = '.MB_EVENT_COMPLETED;
		if($considerall)
		{
			$statusqu = 'AND status > '.MB_EVENT_RESERVED;
		}


		$bookedevents = qa_db_read_all_assoc(
							qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, unitprice, status FROM `^booking_orders`
													WHERE customerid = #
													'.$statusqu.'
													ORDER BY starttime
													;', $userid)
						);

		foreach($bookedevents as $event)
		{
			$timedone = booker_get_timediff($event['starttime'], $event['endtime']);
			$volume += $timedone/60*$event['unitprice'];
		}

		return $volume;

	} // end booker_get_bookingvolume

	// checks if user has creditnote already
	/*
	function booker_userhasgot_creditnote($userid)
	{
		$usercredit = qa_db_read_one_value(
							qa_db_query_sub('SELECT paytime FROM `^booking_payments`
													WHERE `userid` = #
													AND `paymethod` = "creditnote"
													AND `onhold` IS NULL
													AND `ishonorar` IS NULL
													;', $userid), true);
		return isset($usercredit);
	}
	*/

	// thx to https://github.com/amiyasahu/q2a-email-notification/blob/master/qa-email-notifications-event.php#L144
	function q2apro_send_mail($params)
	{
		require_once QA_INCLUDE_DIR . 'qa-class.phpmailer.php';

		$mailer = new PHPMailer();
		$mailer->CharSet = 'utf-8';

		$mailer->From = $params['fromemail'];
		$mailer->Sender = $params['fromemail'];
		$mailer->FromName = $params['fromname'];

		$usermail = isset($params['toemail']) ? $params['toemail'] : null;
		$touserid = isset($params['touserid']) ? $params['touserid'] : null;
		if(isset($usermail))
		{
			$mailer->AddAddress($usermail, $params['toname']);
		}
		else if(isset($touserid))
		{
			// get usermail from userid
			$usermail = qa_db_read_one_value(
							qa_db_query_sub('SELECT email FROM ^users
												WHERE userid = #',
												$touserid), true);
			if(isset($usermail))
			{
				$mailer->AddAddress($usermail, $params['toname']);
			}
			else {
				return false;
			}
		}
		else
		{
			// cannot send mail, no addressee
			return false;
		}

		$mailer->Subject = $params['subject'];
		$mailer->Body = $params['body'];
		if(isset($params['bcclist']))
		{
			foreach($params['bcclist'] as $email)
			{
				$mailer->AddBCC($email);
			}
		}

		if($params['html'])
		{
			$mailer->IsHTML(true);
		}

		if(qa_opt('smtp_active'))
		{
			$mailer->IsSMTP();
			$mailer->Host = qa_opt('smtp_address');
			$mailer->Port = qa_opt('smtp_port');

			if(qa_opt('smtp_secure'))
			{
				$mailer->SMTPSecure = qa_opt('smtp_secure');
			}

			if(qa_opt('smtp_authenticate'))
			{
				$mailer->SMTPAuth = true;
				$mailer->Username = qa_opt('smtp_username');
				$mailer->Password = qa_opt('smtp_password');
			}
		}

		// we could save the message into qa_mbmessages for documentation
		$eventid = null;
		$eventname = 'mail_sent';
		$senderid = isset($params['senderid']) ? $params['senderid'] : 1;
		$paramslog = array(
			// 'senderid'  => $senderid,
			'touserid'  => $touserid,
			// 'toname'    => $params['toname'],
			'subject'   => $params['subject'],
			'body'      => preg_replace('!\s+!', ' ', strip_tags($params['body'])) // merges whitespaces
		);
		// notrack flag
		if(!isset($params['notrack']))
		{
			booker_log_event($senderid, $eventid, $eventname, $paramslog);
		}

		return $mailer->Send();
	} // end q2apro_send_mail

	// contractor is only the user who agreed to the contract
	function booker_iscontracted($userid)
	{
		if(empty($userid))
		{
			return false;
		}
		$contracttime = booker_get_userfield($userid, 'contracted');
		return !is_null($contracttime);

		/*
		$contractorprice = booker_get_userfield($userid, 'bookingprice');

		if($approved)
		{
			$isapproved = booker_get_userfield($userid, 'approved') == MB_USER_STATUS_APPROVED;
			// approved and contractorprice set, should definitely be contractor
			return $isapproved && ($contractorprice>0);
		}
		else
		{
			// contractorprice set, should be contractor
			return ($contractorprice>0);
		}
		*/
	} // end booker_iscontracted

	function booker_ispremium($userid)
	{
		if(empty($userid))
		{
			return false;
		}
		$premium = booker_get_userfield($userid, 'premium'); // 0, 1, 2
		return ($premium==MB_USER_PREMIUM || $premium==MB_USER_VIP);
	}

	function booker_getpremium($userid)
	{
		if(empty($userid))
		{
			return false;
		}
		$premium = booker_get_userfield($userid, 'premium');
		return $premium; // 0, 1, 2
	}

	function booker_getpremiumend($userid)
	{
		if(empty($userid))
		{
			return false;
		}
		$premiumend = booker_get_userfield($userid, 'premiumend');
		return $premiumend; // datetime
	}

	// type premium==1, type vip==2, $premiumend is timestamp (if not set we assign one month from today)
	function booker_setpremium($userid, $premiumtype=1, $premiumend=null)
	{
		// *** should also have premiumtype 0 to go back to non-premium

		if(empty($userid))
		{
			return false;
		}
		booker_set_userfield($userid, 'premium', $premiumtype);
		booker_set_userfield($userid, 'commission', 0);
		// back to default membership
		if($premiumtype==0)
		{
			$commission_default = qa_opt('booker_commission');
			booker_set_userfield($userid, 'commission', $commission_default);
		}

		if(is_null($premiumend))
		{
			// today in one month
			$today = date("Y-m-d");
			$start = new DateTime($today, new DateTimeZone("UTC"));
			$month_later = clone $start;
			$month_later->add(new DateInterval("P1M"));
			$premiumend = $month_later->format("Y-m-d");
		}
		booker_set_userfield($userid, 'premiumend', $premiumend);

		$realname = booker_get_realname($userid);

		// LOG
		$eventid = null;
		$eventname = 'premium_activated';
		$params = array(
			'premiumtype' => $premiumtype,
			'premiumname' => booker_get_premiumname($premiumtype),
			'premiumend' => $premiumend,
			'username' => $realname
		);
		booker_log_event($userid, $eventid, $eventname, $params);

		return;
	}

	// type premium==1, type vip==2
	function booker_displaypremium_success($userid)
	{
		if(empty($userid))
		{
			return false;
		}

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

		$output = '
		<script>
			$(document).ready(function(){
				$(".success-image").addClass("success-animate");
			});
		</script>
		<div class="premiumnotify-wrap">
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
		</div> <!-- premiumnotify-wrap -->
		';
		return $output;
	}

	// get field from table qa_booking_users
	function booker_get_userfield($userid, $field)
	{
		return qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT '.$field.' FROM ^booking_users
						WHERE `userid` = #
					',
					$userid),
				true);
	}

	// ***
	// function booker_set_userfields($userid, $fields, $values)
	// INSERT INTO table (id, name, age) VALUES(1, "A", 19) ON DUPLICATE KEY UPDATE name="A", age=19

	// get field from table qa_booking_users
	function booker_set_userfield($userid, $field, $value)
	{
		qa_db_query_sub(
			'INSERT INTO ^booking_users (userid, '.$field.') VALUES(#, $) ON DUPLICATE KEY UPDATE `'.$field.'`=$',
				$userid, $value, $value
		);
	}

	// get username (either of contractor or client - realname set in table qa_booking_users)
	// if username is not specified and fallback is true, then get userhandle from forum
	function booker_get_realname($userid, $fallback=false)
	{
		if(empty($userid))
		{
			return false;
		}

		$username = booker_get_userfield($userid, 'realname');
		if(empty($username) && $fallback)
		{
			$username = helper_gethandle($userid);
		}
		return $username;
	} // end booker_get_realname

	function q2apro_booker_list_ratings($limit=10, $userid=null)
	{
		$ratinglevels = helper_get_ratinglevels();
		$ratingsymbols = helper_get_ratingsymbols();

		$dbquerylimit = 20; // need more in case we skip ratings

		if(is_null($userid))
		{
			// get existing ratings of user
			$existingratings = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT created, customerid, contractorid, eventid, rating, text FROM `^booking_ratings`
													 WHERE text != ""
													 ORDER BY created DESC
													 LIMIT #
													 ', $dbquerylimit)
													);
		}
		else
		{
			// get existing ratings of user
			$existingratings = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT created, customerid, contractorid, eventid, rating, text FROM `^booking_ratings`
													WHERE text != ""
													AND contractorid = #
													ORDER BY created DESC
													LIMIT #
													', $userid, $dbquerylimit)
												   );
		}

		$ratinglist = '';

		if(count($existingratings)>0)
		{
			$ratinglist .= '
			<div class="ratinglisttablewrap">
				<table class="ratinglisttable">
			';

			$formerrating = '';
			$ratecount = 0;
			foreach($existingratings as $rating)
			{
				// in case a user writes the same rating twice, do not display
				if($formerrating==$rating['text'])
				{
					continue;
				}
				$formerrating = $rating['text'];

				$customerid = $rating['customerid'];
				$contractorid = $rating['contractorid'];
				$eventid = (int)$rating['eventid'];
				// remember eventid so we know which one is rated

				$customername = booker_get_realname($customerid);

				// only list if name exists and if rating is positive
				if(!empty($customername) && $rating['rating']>3)
				{
					$custo = explode(" ", $customername);
					$customername = qa_lang('booker_lang/anonymous');
					if(isset($custo[0]) && isset($custo[1][0]))
					{
						// only first name and surname abbreviated
						$customername = $custo[0].' '.$custo[1][0].'.';
					}

					// $contractorname = booker_get_realname($contractorid);

					/*$eventtimes = helper_geteventtimes($eventid);
						<td>'.$eventtimes.'</td>*/

					$ratinglist .= '
					<tr>
						<td>
							<img src="/?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size=50" alt="Avatar" class="ratingavatar" style="margin-right:5px;border:1px solid #EEE;display:inline-block;vertical-align:top;" />
							<div class="ratingavatarsub" style="display:inline-block;max-width:70px;">
								'.$customername.'
							</div>
						</td>
						<td>'.
							$ratinglevels[$rating['rating']].
							'<br />'.
							$ratingsymbols[$rating['rating']].
						'</td>
						<td>“'.$rating['text'].'”</td>
					</tr>';
				}

				$ratecount++;
				if($ratecount>=$limit)
				{
					break;
				}
			}
			$ratinglist .= '</table> <!-- ratingstable -->
				</div> <!-- ratingstablewrap -->
			';

		} // end count $existingratings
		else
		{
			$ratinglist = '
			<p>
				'.qa_lang('booker_lang/noratings').'
			</p>';
		}

		return $ratinglist;
	} // end q2apro_booker_list_ratings

	function q2apro_ratings_count($userid)
	{
		if(is_null($userid))
		{
			return null;
		}
		else
		{
			// get existing ratings of user
			$existingratings = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT created, customerid, contractorid, eventid, rating, text FROM `^booking_ratings`
													WHERE text != ""
													AND contractorid = #
													ORDER BY created DESC
													', $userid)
												   );
		}

		return count($existingratings);
	} // end q2apro_ratings_count

	// contact link in forum
	function booker_getcontactlink()
	{
		$contactlink = qa_path('mbcontact'); // './mbcontact';
		$userid = qa_get_logged_in_userid();
		if(isset($userid))
		{
			// mb message system
			$contactlink = qa_path('mbmessages').'?to=1'; // './mbmessages?to=1';
		}
		return $contactlink;
	} // end booker_getcontactlink

	function booker_log_event($userid, $eventid, $eventname, $params=null)
	{
		// if no userid than it is probably an admin action
		if(empty($userid))
		{
			$userid = qa_get_logged_in_userid();
		}

		$paramstring='';
		if(!is_null($params))
		{
			foreach($params as $key => $value)
			{
				$paramstring.=(strlen($paramstring) ? "\t" : '').$key.'='.helper_value_to_text($value);
			}
		}

		qa_db_query_sub('INSERT INTO `^booking_logs` (datetime, userid, eventid, eventname, params)
						VALUES(NOW(), #, #, #, #)',
						$userid, $eventid, $eventname, $paramstring);
	} // END booker_log_event

	function booker_get_eventname($status)
	{
		if($status==MB_EVENT_OPEN)
		{
			// ST - booked but payment not yet triggered by client
			// as long as the client does not hit the pay button we do not log the event (could be trash)
			return 'client_openevent';
		}
		else if($status==MB_EVENT_RESERVED)
		{
			// ST - pay button clicked but payment pending by client
			return 'client_reserved';
		}
		else if($status==MB_EVENT_PAID)
		{
			return 'client_paid';
		}
		else if($status==MB_EVENT_ACCEPTED)
		{
			return 'contractor_accepted';
		}
		else if($status==MB_EVENT_FINDTIME)
		{
			return 'contractor_findtime';
		}
		else if($status==MB_EVENT_REJECTED)
		{
			return 'contractor_rejected';
		}
		else if($status==MB_EVENT_COMPLETED)
		{
			return 'contractor_completed';
		}
		else if($status==MB_EVENT_NEEDED)
		{
			return 'contractor_needed';
		}
		else
		{
			return '';
		}
	} // end booker_get_eventname

	function booker_get_eventname_lang($status)
	{
		if($status==MB_EVENT_OPEN)
		{
			// ST - booked but payment not yet triggered by client
			// as long as the client does not hit the pay button we do not log the event (could be trash)
			return qa_lang('booker_lang/evname_openevent2');
		}
		else if($status==MB_EVENT_RESERVED)
		{
			return qa_lang('booker_lang/reserved');
		}
		else if($status==MB_EVENT_PAID)
		{
			return qa_lang('booker_lang/paid');
		}
		else if($status==MB_EVENT_ACCEPTED)
		{
			return qa_lang('booker_lang/accepted');
		}
		else if($status==MB_EVENT_FINDTIME)
		{
			return qa_lang('booker_lang/evname_findtime2');
		}
		else if($status==MB_EVENT_REJECTED)
		{
			return qa_lang('booker_lang/evname_rejected2');
		}
		else if($status==MB_EVENT_COMPLETED)
		{
			return qa_lang('booker_lang/completed');
		}
		else if($status==MB_EVENT_NEEDED)
		{
			return qa_lang('booker_lang/evname_conneeded2');
		}
		else
		{
			return '';
		}
	} // end booker_get_eventname

	// one version for showing humanreadable events and alternative version for status
	function booker_get_logeventname($status, $langactive=true)
	{
		if($status=='client_openevent')
		{
			return $langactive ? qa_lang('booker_lang/evname_openevent') : qa_lang('booker_lang/evname_openevent2');
		}
		if($status=='client_reserved')
		{
			return $langactive ? qa_lang('booker_lang/evname_reserved') : qa_lang('booker_lang/evname_reserved2');
		}
		else if($status=='client_paid')
		{
			return $langactive ? qa_lang('booker_lang/evname_paid') : qa_lang('booker_lang/event_paid');
		}
		else if($status=='eventtime_changed')
		{
			return qa_lang('booker_lang/evname_changed');
		}
		else if($status=='client_setdetails')
		{
			return $langactive ? qa_lang('booker_lang/evname_setdetails') : qa_lang('booker_lang/evname_setdetails2');
		}
		else if($status=='contractor_setdetails')
		{
			return $langactive ? qa_lang('booker_lang/evname_consetdetails') : qa_lang('booker_lang/evname_consetdetails2');
		}
		else if($status=='contractor_findtime')
		{
			return $langactive ? qa_lang('booker_lang/evname_findtime') : qa_lang('booker_lang/evname_findtime2');
		}
		else if($status=='contractor_accepted')
		{
			return $langactive ? qa_lang('booker_lang/evname_findtime') : qa_lang('booker_lang/evname_findtime2');
		}
		else if($status=='contractor_available')
		{
			return $langactive ? qa_lang('booker_lang/evname_available') : qa_lang('booker_lang/evname_available');
		}
		else if($status=='contractor_rejected')
		{
			return $langactive ? qa_lang('booker_lang/evname_rejected') : qa_lang('booker_lang/evname_rejected2');
		}
		else if($status=='contractor_needed')
		{
			return $langactive ? qa_lang('booker_lang/evname_conneeded') : qa_lang('booker_lang/evname_conneeded2');
		}
		else if($status=='contractor_completed')
		{
			return $langactive ? qa_lang('booker_lang/evname_completed') : qa_lang('booker_lang/evname_completed2');
		}
		else if($status=='client_rated')
		{
			return $langactive ? qa_lang('booker_lang/evname_rated') : qa_lang('booker_lang/evname_rated2');
		}
		else if($status=='mail_sent')
		{
			return qa_lang('booker_lang/mail_sent');
		}
		else if($status=='msg_sent')
		{
			return qa_lang('booker_lang/msg_sent');
		}
		else if($status=='msg_deleted')
		{
			return qa_lang('booker_lang/msg_deleted');
		}
		else if($status=='offer_created')
		{
			return qa_lang('booker_lang/offer_created');
		}
		else if($status=='offer_activated')
		{
			return qa_lang('booker_lang/offer_activated');
		}
		else if($status=='offer_deactivated')
		{
			return qa_lang('booker_lang/offer_deactivated');
		}
		else if($status=='offer_edited')
		{
			return qa_lang('booker_lang/offer_edited');
		}
		else if($status=='offer_deleted')
		{
			return qa_lang('booker_lang/offer_deleted');
		}
		else if($status=='offer_disapproved')
		{
			return qa_lang('booker_lang/offer_disapproved');
		}
		else if($status=='offer_approved')
		{
			return qa_lang('booker_lang/offer_approved');
		}
		else if($status=='premium_paid')
		{
			return qa_lang('booker_lang/premium_paid');
		}
		else if($status=='premium_activated')
		{
			return qa_lang('booker_lang/premium_activated');
		}
		else if($status=='request_created')
		{
			return qa_lang('booker_lang/request_created');
		}
		/*else if($status=='request_activated')
		{
			return qa_lang('booker_lang/request_activated');
		}
		else if($status=='request_deactivated')
		{
			return qa_lang('booker_lang/request_deactivated');
		}*/
		else if($status=='request_edited')
		{
			return qa_lang('booker_lang/request_edited');
		}
		else if($status=='request_deleted')
		{
			return qa_lang('booker_lang/request_deleted');
		}
		else if($status=='request_disapproved')
		{
			return qa_lang('booker_lang/request_disapproved');
		}
		else if($status=='request_approved')
		{
			return qa_lang('booker_lang/request_approved');
		}
		else if($status=='profile_edit')
		{
			return qa_lang('booker_lang/profile_edited');
		}
		else if($status=='profile_created')
		{
			return qa_lang('booker_lang/profile_created');
		}
		else if($status=='avatar_uploaded')
		{
			return qa_lang('booker_lang/avatar_uploaded');
		}
		else if($status=='avatar_removed')
		{
			return qa_lang('booker_lang/avatar_removed');
		}
		else if($status=='bid_posted')
		{
			return qa_lang('booker_lang/bid_posted');
		}
		else if($status=='payment_deleted')
		{
			return qa_lang('booker_lang/payment_deleted');
		}
		else if($status=='contractor_weekupdated')
		{
			return qa_lang('booker_lang/contractor_weekupdated');
		}
		else if($status=='client_depositorder')
		{
			return qa_lang('booker_lang/client_depositorder');
		}
		else if($status=='contractor_paid')
		{
			return qa_lang('booker_lang/contractor_paid');
		}
		else if($status=='deposit_release')
		{
			return qa_lang('booker_lang/deposit_release');
		}
		else if($status=='event_deleted')
		{
			return qa_lang('booker_lang/event_deleted');
		}
		else if($status=='client_clearance')
		{
			return qa_lang('booker_lang/client_clearance');
		}
		else if($status=='contractor_contracted')
		{
			return qa_lang('booker_lang/contractor_contracted');
		}
		else if($status=='contractor_recommended')
		{
			return qa_lang('booker_lang/contractor_recommended');
		}
		else if($status=='user_registered')
		{
			return qa_lang('booker_lang/user_registered');
		}
		return '';
	} // end booker_get_logeventname

	function booker_get_logeventparams($params, $status, $eventid, $userid=null)
	{
		// workaround: convert tab jumps to & to be able to use query function
		// memo: don't use ' but only " for str_replace (will not work otherwise!)
		$toURL = str_replace("\t","&",$params); // we get e.g. parentid=4523&parent=array(65)&postid=4524&answer=array(40)
		parse_str($toURL, $paramsa);  // parse URL to associative array $paramsa
		// now we can access the following variables in array $paramsa if they exist in toURL

		// init
		$string = '';

		// can be event but also offer
		if($status=='client_openevent')
		{
			// bookid=101 contractorid=3308 starttime=2016-02-05 12:30:00 endtime=2016-02-05 14:00:00 needs= eventprice=36 contractorprice=12
			// OR: bookid=25	offerid=2	contractorid=9	starttime=2016-07-01 00:00:00	endtime=2016-07-01 00:00:00	price=800.00
			$eventduration = strtotime($paramsa['endtime']) - strtotime($paramsa['starttime']);
			$eventduration /= 60; // minutes
			$string .= qa_lang('booker_lang/bookingnr').' '.$paramsa['bookid'].'<br />';
			if(!empty($paramsa['eventprice']))
			{
				$string .= qa_lang('booker_lang/price').': '.$paramsa['eventprice'].' '.qa_opt('booker_currency');
			}
			$string .= ' | '.qa_lang('booker_lang/length').' '.$eventduration.' min <br />';
			$string .= '<a href="'.qa_path('mbmessages').'?to='.$paramsa['contractorid'].'">'.qa_lang('booker_lang/contractor').': '.booker_get_realname($paramsa['contractorid']).'</a><br />';
			$string .= qa_lang('booker_lang/start').': '.helper_get_readable_date_from_time($paramsa['starttime'], true).'<br />';
			$string .= qa_lang('booker_lang/end').': '.helper_get_readable_date_from_time($paramsa['endtime'], true);
			if(!empty($paramsa['needs']))
			{
				// maybe shorten
				$string .= ' | '.qa_lang('booker_lang/eventdetails').': '.$paramsa['needs'];
			}
		}
		else if($status=='client_reserved')
		{
			// bookid=119 bookamount=12
			$string .= qa_lang('booker_lang/bookingnr').' '.$paramsa['bookid'].'<br />';
			$string .= qa_lang('booker_lang/sum').': '.$paramsa['bookamount'].' '.qa_opt('booker_currency');
		}
		else if($status=='client_paid')
		{
			// bookid=101 bookamount=36
			$string .= qa_lang('booker_lang/bookingnr').' '.$paramsa['bookid'];
			$string .= ' | '.qa_lang('booker_lang/bookingtotal').': '.$paramsa['bookamount'].' '.qa_opt('booker_currency');
		}
		else if($status=='eventtime_changed')
		{
			// former_s=2016-02-05 12:30:00 former_e=2016-02-05 14:00:00 new_s=2016-02-05 11:00:00 new_e=2016-02-05 12:30:00
			$string .= qa_lang('booker_lang/ev_former').': &emsp;'.helper_get_readable_date_from_time($paramsa['former_s'], true);
			$string .= ' '.qa_lang('booker_lang/ev_until').' '.helper_get_readable_date_from_time($paramsa['former_e'], true).'<br />';
			$string .= qa_lang('booker_lang/ev_new').': &ensp;'.helper_get_readable_date_from_time($paramsa['new_s'], true);
			$string .= ' '.qa_lang('booker_lang/ev_until').' '.helper_get_readable_date_from_time($paramsa['new_e'], true);
		}
		else if($status=='contractor_accepted')
		{
			$contractorname = '';
			if(isset($paramsa['contractorid']))
			{
				$contractorname = booker_get_realname($paramsa['contractorid']);
			}
			$string .= qa_lang('booker_lang/contractor').': '.$contractorname;
		}
		else if($status=='contractor_available')
		{
			if($paramsa['available']==1)
			{
				$string .= 'now available';
			}
			else
			{
				$string .= 'not available';
			}
		}
		else if($status=='contractor_rejected')
		{
			$contractorname = '';
			if(isset($paramsa['contractorid']))
			{
				$contractorname = booker_get_realname($paramsa['contractorid']);
			}
			$string .= qa_lang('booker_lang/contractor').': '.$contractorname;

			// reason
			if(isset($paramsa['reason']))
			{
				$string .= ' | '.qa_lang('booker_lang/reason').': „'.$paramsa['reason'].'“';
			}
		}
		else if($status=='contractor_findtime')
		{
			// contractorid=134    starttime_former=2016-03-31 16:00:00    starttime_new=2016-03-25 15:00:00    duration=60
			$contractorname = '';
			if(isset($paramsa['contractorid']))
			{
				$contractorname = booker_get_realname($paramsa['contractorid']);
			}
			$string .= qa_lang('booker_lang/contractor').': '.$contractorname;

			// reason
			if(isset($paramsa['starttime_new']))
			{
				$string .= ' | '.qa_lang('booker_lang/eventproposal').': '.helper_get_readable_date_from_time($paramsa['starttime_new']);
			}
		}
		else if($status=='client_setdetails' || $status=='contractor_setdetails' || $status=='contractor_completed')
		{
			// needs=Abiturklasur 2008 HT03 Ht05 protocol=https://docs.google.com/document/d/1ILAHxfpGqxBEtiu5N8tEWobuJ3CrkgpMyxDHn0tChIw/edit?usp=sharing
			if(!empty($paramsa['needs']))
			{
				$string .= qa_lang('booker_lang/eventdetails').': '.$paramsa['needs'];
			}
			if(!empty($paramsa['protocol']))
			{
				$string .= '<br />'.qa_lang('booker_lang/protocol').': '.$paramsa['protocol'];
			}
		}
		else if($status=='client_rated')
		{
			// contractorid=3308 rating=5 text=
			$ratingsymbols = helper_get_ratingsymbols();
			$string .= qa_lang('booker_lang/rating').': '.$ratingsymbols[$paramsa['rating']];
			if(!empty($paramsa['text']))
			{
				$string .= '<br />"'.$paramsa['text'].'"';
			}
		}
		else if($status=='mail_sent')
		{
			// touserid=1	subject=Naujas skelbimas sukurtas: Šarvuoti seifai	body= Naujas skelbimas Vardas: (3505) Man reikalingas: ...
			$string .= 'Mail to: <b>'.booker_get_realname($paramsa['touserid']).'</b>
						<br />
						Subject: <b>'.$paramsa['subject'].'</b>
						<p>
							Body:
							<span style="font-size:11px;color:#555;">
								'.$paramsa['body'].'
							</span>
						</p>
						';
		}
		else if($status=='avatar_uploaded')
		{
			$string .= '<img src="/?qa=image&qa_blobid='.$paramsa['blobid'].'&qa_size=200" />';
		}
		else if($status=='contractor_recommended')
		{
			// recommender=1	realname=Laurynas Milinis	usermail=laurynasmilinis@gmail.com	birthdate=1992.09.27	address=Klaipeda	phone=+370 60213462	skype=	service=Garso operatorius (Audio Engineer)	portfolio=Šešerių metų patirtis renginių įgarsinime. Baigti ACCU.lt garso režisūros kursai.    Aptarnauti tokie renginiai kaip:    Atviras Lietuvos konkūrų čempionatas (2010-2016m.)    Andriaus Mam…	serviceflags=4
			$string .= '
				Recommended by: <a href="'.qa_path('user').'/'.helper_gethandle($paramsa['recommender']).'">'.booker_get_realname($paramsa['recommender'], true).'</a>
				<br />
				<span style="font-size:11px;color:#555;">
					Name: '.$paramsa['realname'].', Mail: '.$paramsa['usermail'].', Birthdate: '.$paramsa['birthdate'].', Address: '.$paramsa['address'].', phone: '.$paramsa['phone'].', Skype: '.$paramsa['skype'].', Service: '.$paramsa['service'].', Portfolio: '.helper_shorten_text($paramsa['portfolio']).'
				</span>
			';
		}
		else if($status=='profile_created' || $status=='profile_edit')
		{
			// realname=Rugile Rarelyte	portfolio=	service=	bookingprice=0	skype=	payment=	birthdate=1994 05 06	address=	phone=	serviceflags=0
			$string .= '
				Name: <a href="'.qa_path('user').'/'.helper_gethandle($userid).'">'.$paramsa['realname'].'</a>
				<br />
				<span style="font-size:11px;color:#555;">
					Name: '.$paramsa['realname'].', portfolio: '.@$paramsa['portfolio'].', service: '.@$paramsa['service'].', skype: '.@$paramsa['skype'].', payment: '.@$paramsa['payment'].', birthdate: '.@$paramsa['birthdate'].', address: '.@$paramsa['address'].'
				</span>
			';
		}
		else if($status=='premium_paid')
		{
			$string .= 'Premium type: '.$paramsa['type'];
			$string .= ' | '.qa_lang('booker_lang/payamount').': '.$paramsa['amount'].' '.qa_opt('booker_currency');
			$string .= ' | '.qa_lang('booker_lang/paymethod').': '.$paramsa['paymethod'];
			$string .= ' | '.qa_lang('booker_lang/name').': '.$paramsa['username'];
		}
		else if($status=='premium_activated')
		{
			$string .= 'Premium type: '.$paramsa['premiumname'];
			$string .= ' | Premium end: '.$paramsa['premiumend'];
			$string .= ' | Username: '.$paramsa['username'];
		}

		return $string;
	} // end booker_get_logeventparams

	// used in eventmanager to list all available events
	function booker_list_findcontractor_events()
	{
		$events = qa_db_read_all_assoc(
						qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, unitprice, status, needs, attachment FROM ^booking_orders
												WHERE status = #
												ORDER BY starttime ASC',
												MB_EVENT_NEEDED));
		if(count($events)==0)
		{
			return '';
		}

		$output = '';
		$output .= '
		<table id="bookingtable">
			<thead>
				<tr>
					<th>'.qa_lang('booker_lang/date').'</th>
					<th>'.qa_lang('booker_lang/time').'</th>
					<th>'.qa_lang('booker_lang/client').'</th>
					<th>'.qa_lang('booker_lang/timespan').'</th>
					<th>'.qa_lang('booker_lang/fee').'</th>
					<th>'.qa_lang('booker_lang/eventdetails').'</th>
					<th>'.qa_lang('booker_lang/attachments').'</th>
				</tr>
			</thead>
			<tbody>
		';
		foreach($events as $ev)
		{
			$date = date(qa_lang('booker_lang/date_format_php'),strtotime($ev['starttime']));
			$weekday = helper_get_weekday($date, true);
			$clientname = booker_get_realname($ev['customerid']);
			$starttime = date('H:i',strtotime($ev['starttime']));
			$endtime = date('H:i',strtotime($ev['endtime']));
			$timespan = (strtotime($ev['endtime']) - strtotime($ev['starttime']))/60;
			$eventprice = booker_get_eventprice($ev['eventid'], true);

			$attachment = $ev['attachment'];
			$attachment_show = helper_attachstring_to_list($attachment);

			$output .= '
					<tr>
						<td class="eventtime">'.$weekday.', '.$date.'</td>
						<td class="eventtimehours">'.$starttime.' bis '.$endtime.' '.qa_lang('booker_lang/timeabbr').'</td>
						<td class="clientname">'.$clientname.'</td>
						<td class="eventtimeabs">'.$timespan.' min</td>
						<td class="eventcalc">'.number_format($eventprice,2,',','.').' '.qa_opt('booker_currency').'</td>
						<td class="eventneeds">'.$ev['needs'].'</td>
						<td class="eventattachment">'.$attachment_show.'</td>
						<td class="eventaction" style="padding:10px;">
							<a class="defaultbutton buttongreenish" style="padding:2px 5px;" href="'.qa_path('eventmanager').'?id='.$ev['eventid'].'">'.qa_lang('booker_lang/view_appt').'</a>
						</td>
					</tr>
			';
		}
		$output .= '
			</tbody>
		</table>
		';
		$output .= csstablestyles();
		$output .= '
		<style type="text/css">
			th {
				text-align:left;
				background:#FFC;
			}
			#bookingtable td {
				text-align:left;
			}
			#bookingtable tr:hover {
				background:#EFE;
			}
		</style>
		';
		return $output;
	} // end booker_list_findcontractor_events

	function booker_list_client_findtime_events($userid)
	{
		$events = qa_db_read_all_assoc(
						qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, unitprice, status, needs, attachment FROM ^booking_orders
												WHERE status = #
												AND customerid = #
												ORDER BY starttime ASC',
												MB_EVENT_FINDTIME, $userid));
		if(count($events)==0)
		{
			return '';
		}

		$output = '';
		$output .= '
		<table id="bookingtable">
			<thead>
				<tr>
					<th>'.qa_lang('booker_lang/date').'</th>
					<th>'.qa_lang('booker_lang/time').'</th>
					<th>'.qa_lang('booker_lang/contractor').'</th>
					<th>'.qa_lang('booker_lang/timespan').'</th>
					<th>'.qa_lang('booker_lang/eventdetails').'</th>
					<th>'.qa_lang('booker_lang/attachments').'</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
		';
		foreach($events as $ev)
		{
			$date = date(qa_lang('booker_lang/date_format_php'),strtotime($ev['starttime']));
			$weekday = helper_get_weekday($date, true);
			$contractorname = booker_get_realname($ev['contractorid']);
			$starttime = date('H:i',strtotime($ev['starttime']));
			$endtime = date('H:i',strtotime($ev['endtime']));
			$timespan = (strtotime($ev['endtime']) - strtotime($ev['starttime']))/60;
			$eventprice = booker_get_eventprice($ev['eventid'], true);

			$attachment = $ev['attachment'];
			$attachment_show = helper_attachstring_to_list($attachment);

			$output .= '
					<tr>
						<td class="eventtime">'.$weekday.', '.$date.'</td>
						<td class="eventtimehours">'.$starttime.' bis '.$endtime.' '.qa_lang('booker_lang/timeabbr').'</td>
						<td class="clientname">'.$contractorname.'</td>
						<td class="eventtimeabs">'.$timespan.' min</td>
						<td class="eventneeds">'.$ev['needs'].'</td>
						<td class="eventattachment">'.$attachment_show.'</td>
						<td class="eventaction" style="padding:10px;">
							<a class="defaultbutton buttongreenish" style="padding:2px 5px;" href="'.qa_path('eventmanager').'?id='.$ev['eventid'].'">'.qa_lang('booker_lang/view_appt').'</a>
						</td>
					</tr>
			';
		}
		$output .= '
			</tbody>
		</table>
		';
		$output .= csstablestyles();
		$output .= '
		<style type="text/css">
			th {
				text-align:left;
				background:#FFC;
			}
			#bookingtable td {
				text-align:left;
			}
			#bookingtable tr:hover {
				background:#EFE;
			}
		</style>
		';
		return $output;
	} // end booker_list_client_findtime_events

	function helper_value_to_text($value)
	{
		$maxlength = 200;
		if (is_array($value))
			$text='array('.count($value).')';
		elseif (strlen($value)>$maxlength)
			$text=substr($value, 0, $maxlength-2).'…';
		else
			$text=$value;

		return strtr($text, "\t\n\r", '   ');
	}

	// expects datetime format, e.g. "2016-02-05 13:14:45"
	function helper_get_readable_date_from_time($datetime, $keephourmin=true, $addhourstring=true)
	{
		if(empty($datetime))
		{
			return null;
		}

		// *** German format from 2016-02-05 to 05.02.2016
		// $datestring = substr($datetime, 8, 2).'.'.substr($datetime, 5, 2).'.'.substr($datetime, 0, 4);
		$datestring = helper_get_date_localized( substr($datetime,0,10) );

		if($keephourmin)
		{
			// add hours
			$datestring .= ' '.substr($datetime, 11, 5);
			if($addhourstring)
			{
				$datestring .= ' '.qa_lang('booker_lang/timeabbr');
			}
		}

		return $datestring;
	}

	// expects datetime, e.g. "2016-02-05", and transforms it according to localisation
	function helper_get_date_localized($date)
	{
		if(empty($date))
		{
			return null;
		}

		$datestring = date_create_from_format('Y-m-d', $date);
		return $datestring->format( qa_lang('booker_lang/date_format_php') );
	}

	function helper_get_weekday($datetime, $abbr=false)
	{
		$weekdays = helper_get_weekdayname_array(); // 0 to 6
		if($abbr)
		{
			$weekdays = array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'); // 0 to 6
		}
		$weekday = $weekdays[ date('w', strtotime($datetime)) ];
		return $weekday;
	}

	function helper_attachstring_to_list($attachment)
	{
		$attachment_show = '';
		if(!empty($attachment))
		{
			$attlinks = explode(';', $attachment);
			$count = 0;
			foreach($attlinks as $link)
			{
				$count++;
				$attachment_show .= '• <a class="fileexists" title="'.$link.'" href="'.$link.'" target="_blank">'.qa_lang('booker_lang/file').' '.$count.'</a><br />';
			}
		}
		else {
			$attachment_show .= '';
		}
		return $attachment_show;
	}

	// all paid status, excluding completed status
	function booker_event_is_paid($eventid)
	{
		$status = booker_get_status_event($eventid);

		// without 'MB_EVENT_COMPLETED'
		$paidstatus = array(MB_EVENT_PAID, MB_EVENT_ACCEPTED, MB_EVENT_FINDTIME, MB_EVENT_REJECTED, MB_EVENT_NEEDED);

		return in_array($status, $paidstatus);
	}

	function booker_get_all_contractors($start=0, $size=20, $needapproval=true, $needprice=true, $contracted=false, $oderby='realname ASC', $filterbyletter=null)
	{
		$queryconditions = '';

		if($needprice)
		{
			$queryconditions = 'AND `bookingprice` > 0 ';
		}

		if($needapproval)
		{
			$queryconditions .= 'AND `approved` = '.MB_USER_STATUS_APPROVED.' ';
		}

		if($contracted)
		{
			$queryconditions .= 'AND `contracted` IS NOT NULL ';
		}

		if(isset($filterbyletter))
		{
			$queryconditions .= 'AND `realname` LIKE "'.$filterbyletter.'%" ';
		}
		$contractordata = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT userid, realname, birthdate, address, phone, skype, service, portfolio, bookingprice,
													payment, available, absent, externalcal, registered, contracted, approved, flags, premium
														FROM `^booking_users`
														WHERE
														(
															`service` IS NOT NULL
															'.$queryconditions.'
														)
														ORDER BY '.$oderby.'
														LIMIT #, #
														', $start, $size)
												  );
		return $contractordata;
	} // end booker_get_all_contractors

	// profession match, we filter the contractors with the same profession/service and inform them about the available job
	function booker_get_alternative_contractors_for_event($eventid)
	{
		if(empty($eventid))
		{
			return null;
		}

		$rejectingcontractor = qa_db_read_one_value(
									qa_db_query_sub('SELECT contractorid FROM `^booking_orders`
													 WHERE `eventid` = #
													 ', $eventid));

		$servicename = qa_db_read_one_value(
							qa_db_query_sub('SELECT service FROM `^booking_users`
												WHERE `userid` = $
												', $rejectingcontractor), true);
		if(empty($servicename))
		{
			return null;
		}

		// remove last two letters (trick to regard male and female, e.g. kepejas becomes %kepej% or Friseurin becomes Friseur)
		// make sure we have enough letters
		if(strlen($servicename)>7)
		{
			$servicename = substr($servicename, 0, strlen($servicename)-2);
		}

		$searchstring = '%'.$servicename.'%';

		// maximal number of contractors to inform by email
		$maxlimit = 100;

		// e.g. SELECT userid, realname, service FROM qa_booking_users
			// WHERE `approved` = "1" AND `service` LIKE "%Kepejas%" AND `userid` != "3499" ORDER BY realname ASC LIMIT 0, 100
		$contractors = qa_db_read_all_assoc(
						  qa_db_query_sub('SELECT userid, realname, service FROM ^booking_users
											WHERE
											`contracted` IS NOT NULL
											AND `approved` = "'.MB_USER_STATUS_APPROVED.'"
											AND `available` = "1"
											AND `service` LIKE $
											AND `userid` != #
											ORDER BY realname ASC
											LIMIT 0, #
											', $searchstring, $rejectingcontractor, $maxlimit)
										);

		/*foreach($contractors as $con)
		{
			error_log($con['userid'].' -> '.$con['realname'].' -> '.$con['service']);
			error_log('---');
		}*/

		return $contractors;

	} // end booker_get_alternative_contractors_for_event

	function booker_get_approved_contractors($available=false, $needprice=false, $orderby='realname ASC', $start=0, $size=20, $contracted)
	{
		if(is_null($start))
		{
			$start = 0;
		}
		if(is_null($size))
		{
			$size = 20;
		}

		$queryconditions = '';

		if($needprice)
		{
			$queryconditions .= 'AND `bookingprice` > 0 ';
		}
		if($available)
		{
			$queryconditions .= 'AND `available` = "1" ';
		}

		if($contracted)
		{
			$queryconditions .= 'AND `contracted` IS NOT NULL ';
		}

		// *** for later, live mode: `service` IS NOT NULL should be: `contracted` IS NOT NULL
		$contractors = qa_db_read_all_assoc(
						  qa_db_query_sub('SELECT userid, realname FROM ^booking_users
											WHERE
											`service` IS NOT NULL
											AND `approved` = "'.MB_USER_STATUS_APPROVED.'"
											'.$queryconditions.'
											ORDER BY '.$orderby.'
											LIMIT #, #
											', $start, $size)
										);
		return $contractors;
	}

	function booker_event_rejected_by_contractor($eventid, $contractorid)
	{
		$contractorid_check = qa_db_read_one_value(
							qa_db_query_sub('SELECT userid FROM `^booking_logs`
												WHERE `eventid` = #
												AND userid = #
												AND eventname = "contractor_rejected"
												;', $eventid, $contractorid), true );
		return isset($contractorid_check);
	}

	// abbreviations of weekdays
	function helper_get_weekdayarray()
	{
		return array(qa_lang('booker_lang/mon'), qa_lang('booker_lang/tue'), qa_lang('booker_lang/wed'), qa_lang('booker_lang/thu'), qa_lang('booker_lang/fri'), qa_lang('booker_lang/sat'), qa_lang('booker_lang/sun'));
	}

	function helper_get_weekdayname_array()
	{
		return array(qa_lang('booker_lang/sunday'), qa_lang('booker_lang/monday'), qa_lang('booker_lang/tuesday'), qa_lang('booker_lang/wednesday'), qa_lang('booker_lang/thursday'), qa_lang('booker_lang/friday'), qa_lang('booker_lang/saturday')); // 0 to 6
	}

	function helper_get_ratingsymbols()
	{
		return array('', '★', '★★', '★★★', '★★★★', '★★★★★');
	}

	function helper_get_ratinglevels()
	{
		return array('', qa_lang('booker_lang/rat_negativ'), qa_lang('booker_lang/rat_neutral'),
						qa_lang('booker_lang/rat_good'), qa_lang('booker_lang/rat_verygood'),
						qa_lang('booker_lang/rat_excellent'));
	}

	function helper_shorten_text($text, $length=110)
	{
		if(empty($text))
		{
			return '';
		}

		$textcleaned = strip_tags($text);
		$textready = mb_substr($textcleaned, 0, $length, 'utf-8'); // shorten question title, needs UTF-8 substring as 2-byte-char could be cut
		if(strlen($textready) < strlen($textcleaned))
		{
			// remove last 2 and add the dots
			$textready = mb_substr($textcleaned, 0, $length-2, 'utf-8');
			// add hellip
			$textready .= '…';
		}
		return $textready;
	}


	function booker_get_contractordata_box($contractorid)
	{
		// ***
		// $folderpath = 'http://localhost/kvanto/qa-plugin/q2apro-booker/';
		$folderpath = qa_path('qa-plugin/q2apro-booker/');

		// booker userdata
		$buserdata = booker_getfulluserdata($contractorid);

		$contractorname = $buserdata['realname'];
		$portfolio = $buserdata['portfolio'];
		$service = $buserdata['service'];
		$bookingprice = (float)$buserdata['bookingprice'];
		$available  = ($buserdata['available']==1);

		$location  = $buserdata['address'];
		$locationcity = '';
		$userflags = $buserdata['flags'];

		if(!empty($location))
		{
			$locationsplit = explode(',', $location);
			$locationcity = trim($locationsplit[count($locationsplit)-1]);
			// sometimes postal code is after the cityname, check that
			if(is_numeric($locationcity))
			{
				// take second last as city
				$locationcity = $locationsplit[count($locationsplit)-2];
			}
			else
			{
				// remove numbers, e.g. from 92346 Klaipeda
				$locationcity = preg_replace('/[0-9]+/', '', $locationcity);
				// remove LT- part
				$locationcity = str_replace('LT-', '', $locationcity);
			}
			// trim again
			$locationcity = trim($locationcity);
		}

		// check if online service
		$serviceonline = false;
		$serviceonline_label = '';
		if($userflags&MB_SERVICEONLINE)
		{
			$serviceonline = true;

			// if only-online then do not show address but "online" for location
			if(!($userflags&MB_SERVICELOCAL))
			{
				$locationcity = '';
				$serviceonline_label = '<span class="serviceonline">'.qa_lang('booker_lang/serviceonline').'</span>';
			}
			else
			{
				$serviceonline_label = '+ <span class="serviceonline">'.qa_lang('booker_lang/serviceonline').'</span>';
			}
		}

		// user data
		$userdata = qa_db_read_one_assoc(
			qa_db_query_sub('SELECT avatarblobid FROM ^users
								WHERE userid = #',
								$contractorid
								)
							);

		if(isset($userdata['avatarblobid']))
		{
			$avatar = '/?qa=image&qa_blobid='.$userdata['avatarblobid'].'&qa_size=200';
		}
		else
		{
			$avatar = '/?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size=200';
		}

		// votes generated from userid
		// $voteCnt = (int)substr($contractorid, -1);
		$userratingdata = booker_get_contractor_rating($contractorid);
		$userrating_value = $userratingdata['ratingvalue'];
		$userrating_count = $userratingdata['ratingcount'];
		if(is_null($userrating_value))
		{
			$userrating_value = '';
		}

		if(is_null($userrating_count))
		{
			$userrating_title = qa_lang('booker_lang/noratings');
		}
		else
		{
			$userrating_title = $userrating_count.' '.qa_lang('booker_lang/ratings');
			if($userrating_count==1)
			{
				$userrating_title = $userrating_count.' '.qa_lang('booker_lang/rating');
			}
		}

		$userrating_html = '';
		if ($userrating_value != ''){
			$userrating_html = '
						<div class="contractorvotes tooltip" title="'.$userrating_title.'">
                            <i class="fa fa-star fa-lg"></i> <span class="userrating">'.$userrating_value.'</span>
                        </div>';
		}

		// *** a nice idea: each contractor can present himself by a short youtube video
		// (!) could also be embeded in portfolio, filter out
		$contractorvideolink = '';
		/*
		$contractorvideo = booker_get_userfield($contractorid, 'contractorvideo');
		if(!empty($contractorvideo))
		{
		// $contractorvideolink = '<a target="_blank" href="'.$contractorvideo.'" class="contractorvideo tooltip">Beispiel-Video ►</a>';
		$contractorvideolink = '
		<a href="'.$contractorvideo.'" class="contractorvideo tooltipE venobox_custom" data-type="youtube" data-autoplay="true">
			<img src="'.$this->urltoroot.'images/icon-play.svg" class="contractorplayicon" alt="contractorplayicon" />
		</a>
		';
		}
		*/

		$bookingprice_show = '';
		if($available && $bookingprice > 0)
		{
			$bookingprice_show = (round($bookingprice)==$bookingprice ? $bookingprice : number_format($bookingprice, 2, ',', '.'));
			// $bookingprice_show = qa_lang('booker_lang/bookfor').' '.$bookingprice_show.' '.qa_opt('booker_currency');
			$bookingprice_show = $bookingprice_show.' '.qa_opt('booker_currency').'/'.qa_lang('booker_lang/hourabbr');
		}
		else
		{
			$bookingprice_show = '- '.qa_lang('booker_lang/notavailable').' -';
		}

		$searchstring = $service;
		// helper_shorten_text($service, 26)
		// take max two words for search
		$searchstring = str_replace(array('/', '\\'), ' ' , $searchstring);
		$searchpre = explode(' ', $searchstring);
		if(count($searchpre)>1)
		{
			$searchstring = $searchpre[0].' '.$searchpre[1];
		}
		else
		{
			$searchstring = $service;
		}
		// remove /,;:
		$searchstring = str_replace(array('.', ',', '`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_', '=', '+', '/', '\\'), ' ' , $searchstring);
		$searchstring = rawurlencode(trim($searchstring));

		$orderbutton = '
						<a class="btn btn-primary" target="_blank" href="'.qa_path('booking').'?contractorid='.$contractorid.'">
                            '.qa_lang('booker_lang/order_for').' '.$bookingprice_show.'
                        </a>
						';
		if($bookingprice==0)
		{
			$orderbutton = '
							<a class="btn btn-primary" target="_blank" href="'.qa_path('booking').'?contractorid='.$contractorid.'">
								'.qa_lang('booker_lang/nopriceset').'
							</a>
							';
		}
		// $voteCnt.' <img src="'.$folderpath.'images/thumbs-up-mini.png" alt="upvotes" />
		$contractordata = '
			<div class="contractorBox">
					<div class="avatarwrap">
						<a class="contractorbooklink" target="_blank" href="'.qa_path('booking').'?contractorid='.$contractorid.'">
							<i class="fa fa-shopping-cart fa-5x orderme"></i>
							<div class="contractoravatar" style="background-image:url('.$avatar.');">
							</div>
						</a>
                        '.$userrating_html.'
					</div>
					'.$contractorvideolink.'
					<div class="contractor-content">
					    <div class="imagecap">
					        <a class="contractorname" href="'.qa_path('booking').'?contractorid='.$contractorid.'" target="_blank">'.helper_shorten_text($contractorname, 22).'</a>
					        <a class="contractorservice" title="'.qa_lang('booker_lang/clickmoreservices').'" href="'.qa_path('contractorlist').'?s='.$searchstring.'">'.helper_shorten_text($service, 26).'</a>
					    </div>
					    <div class="contractormetaloc">
					        <div class="contractorlocation">
					            <span class="locationdata" data-location="'.$location.'" data-locationcity="'.$locationcity.'" data-online="'.$serviceonline.'">
					                '.$locationcity.'
					            </span>
					            '.$serviceonline_label.'
					        </div>
					    </div>
					    <div class="contractormeta">
					        <span class="contractorshortdesc">
					            '.helper_shorten_text($portfolio, 124).'
					        </span>
					    </div>
						'.$orderbutton.'
					    <span class="fulldescription">'.helper_shorten_text($contractorname,1000).' - '.helper_shorten_text($service,1000).' - '.helper_shorten_text($portfolio, 8000).'</span>
					</div>
				</div>
			';
		return $contractordata;
	} // end booker_get_contractordata_box

	function booker_get_contractorcount($approved = true, $contracted = false)
	{
		$queryconditions = '';
		if($approved)
		{
			$queryconditions .= 'AND `approved` = "'.MB_USER_STATUS_APPROVED.'" ';
		}
		if($contracted)
		{
			$queryconditions .= 'AND `contracted` IS NOT NULL ';
		}
		return qa_db_read_one_value(
						qa_db_query_sub('SELECT COUNT(userid) FROM ^booking_users
											WHERE
											`userid` IS NOT NULL
											'.$queryconditions
								));
	}

	function booker_get_contractorcount_priceset($approved = true, $contracted = false, $filterbyletter=null)
	{
		$queryconditions = '';
		if($approved)
		{
			$queryconditions .= 'AND `approved` = "'.MB_USER_STATUS_APPROVED.'" ';
		}
		if($contracted)
		{
			$queryconditions .= 'AND `contracted` IS NOT NULL ';
		}
		if(isset($filterbyletter))
		{
			$queryconditions .= 'AND `realname` LIKE "'.$filterbyletter.'%" ';
		}
		return qa_db_read_one_value(
						qa_db_query_sub('SELECT COUNT(userid) FROM ^booking_users
											WHERE `bookingprice` > 0
											'.$queryconditions
								));
	}

	function booker_get_clientcount()
	{
		return qa_db_read_one_value(
						qa_db_query_sub('SELECT COUNT(userid) FROM ^booking_users
											WHERE `approved` = "'.MB_USER_STATUS_DEFAULT.'"
											AND (
												`bookingprice` = 0
												OR
												`bookingprice` IS NULL
											)
											'
								));
	}

	function booker_getfulluserdata($userid)
	{
		return qa_db_read_one_assoc(
					qa_db_query_sub('SELECT realname, company, birthdate, address, phone, skype, service, portfolio, bookingprice, payment,
										available, absent, externalcal, registered, contracted, approved, kmrate, flags, premium, premiumend
										FROM `^booking_users`
										WHERE userid = #',
								$userid
								), true
							);
	}

	function booker_get_offers($userid, $onlyactive=false)
	{
		$addquery = '';
		if($onlyactive)
		{
			$addquery = 'AND (end > NOW() OR end IS NULL)';
		}
		return qa_db_read_all_assoc(
					qa_db_query_sub('SELECT offerid, created, userid, title, price, end, description, flags, status FROM `^booking_offers`
											WHERE userid = #
											'.$addquery.'
											ORDER BY title ASC
											',
											$userid));
	}

	function booker_get_requests($userid, $dayinterval=null, $includedeleted=true)
	{
		$addquery = '';
		if(empty($dayinterval))
		{
			$addquery .= ' AND (`end` > NOW() OR `end` IS NULL) ';
		}
		else
		{
			$addquery .= ' AND `created` >= DATE_ADD(CURDATE(), INTERVAL -'.$dayinterval.' DAY) ';
		}

		if(!$includedeleted)
		{
			$addquery .= ' AND `status` != '.MB_REQUEST_DELETED.' ';
		}

		return qa_db_read_all_assoc(
					qa_db_query_sub('SELECT requestid, created, userid, title, price, end, description, location, status FROM `^booking_requests`
											WHERE userid = #
											'.$addquery.'
											ORDER BY title ASC
											',
											$userid));
	}

	function booker_get_all_requests()
	{
		return qa_db_read_all_assoc(
					qa_db_query_sub('SELECT requestid, created, userid, title, price, end, description, location, status FROM `^booking_requests`
											WHERE
											(end > NOW() OR end IS NULL)
											AND
											(status = #)
											ORDER BY end ASC
											', MB_REQUEST_APPROVED)
									);
	}

	/*
	function booker_assign_creditnote($userid, $params)
	{
		$voucherval = qa_opt('booker_vouchervalue');
		$amount = filter_var($voucherval, FILTER_SANITIZE_NUMBER_INT);

		// INSERT INTO DB
		qa_db_query_sub('INSERT INTO ^booking_payments (paytime, bookid, userid, paymethod, amount, onhold)
										VALUES (NOW(), NULL, #, #, #, NULL)',
												$userid, 'creditnote', $amount);

		// check set data
		$realname = isset($params['realname']) ? $params['realname'] : '';
		$address = isset($params['address']) ? $params['address'] : '';
		$birthdate = isset($params['birthdate']) ? $params['birthdate'] : '';
		$phone = isset($params['phone']) ? $params['phone'] : '';
		$skype = isset($params['skype']) ? $params['skype'] : '';

		// LOG starting credit
		$eventname = 'client_creditnote';
		$eventid = null;
		$paramslog = array(
			'type' => 'startingcredit',
			'amount' => $amount,
			'name' => $params['realname']
		);
		booker_log_event($userid, $eventid, $eventname, $paramslog);

		// inform admin
		$emailbody = '
			<p>
				'.qa_lang('booker_lang/startcredit_activated').':
			</p>
			<p>
				Userid: '.$userid.' <br />
				'.qa_lang('booker_lang/name').': '.$realname.' <br />
				'.qa_lang('booker_lang/address').': '.$address.' <br />
				'.qa_lang('booker_lang/birthdate').': '.$birthdate.' <br />
				'.qa_lang('booker_lang/telephone').': '.$phone.' <br />
				'.qa_lang('booker_lang/skype').': '.$skype.'
			</p>
		';
		q2apro_send_mail(array(
					'fromemail' => q2apro_get_sendermail(),
					'fromname'  => qa_opt('booker_mailsendername'),
					'touserid'  => 1,
					'toname'    => qa_opt('booker_mailsendername'),
					'subject'   => qa_lang('booker_lang/startcredit_activated').' '.$params['realname'],
					'body'      => $emailbody,
					'html'      => true
		));

	} // END booker_assign_creditnote
	*/

	function booker_log_userregistration($userid, $params)
	{
		$registered = booker_get_userfield($userid, 'registered');
		if(empty($registered))
		{
			$registertime = date("Y-m-d H:i:s");
			booker_set_userfield($userid, 'registered', $registertime);

			// LOG
			$eventid = null;
			$eventname = 'user_registered';
			booker_log_event($userid, $eventid, $eventname, $params);

			$realname = isset($params['realname']) ? $params['realname'] : '';
			$address = isset($params['address']) ? $params['address'] : '';
			$birthdate = isset($params['birthdate']) ? $params['birthdate'] : '';
			$phone = isset($params['phone']) ? $params['phone'] : '';
			$skype = isset($params['skype']) ? $params['skype'] : '';

			// inform admin
			$emailbody = '
			<p>
				'.qa_lang('booker_lang/user_registered').':
			</p>
			<p>
				Userid: '.$userid.' <br />
				'.qa_lang('booker_lang/name').': '.$realname.' <br />
				'.qa_lang('booker_lang/birthdate').': '.$birthdate.' <br />
				'.qa_lang('booker_lang/address').': '.$address.' <br />
				'.qa_lang('booker_lang/telephone').': '.$phone.' <br />
				Skype: '.$skype.'
			</p>
			';
			q2apro_send_mail(array(
						'fromemail' => q2apro_get_sendermail(),
						'fromname'  => qa_opt('booker_mailsendername'),
						'senderid'	=> 1, // for log
						'touserid'  => 1,
						'toname'    => qa_opt('booker_mailsendername'),
						'subject'   => qa_lang('booker_lang/user_registered').': '.$realname,
						'body'      => $emailbody,
						'html'      => true
			));
		} // END empty($registered)
	} // END booker_log_userregistration

	function booker_clienthasevents($userid)
	{
		return qa_db_read_one_value(
						qa_db_query_sub('SELECT COUNT(eventid) FROM ^booking_orders
											WHERE `customerid` = #
											AND `status` > #
											', $userid, MB_EVENT_OPEN), true
								);
	}

	function booker_service_onlyonline($contractorid)
	{
		$userflags = booker_get_userfield($contractorid, 'flags');
		return ($userflags & MB_SERVICEONLINE) && !($userflags & MB_SERVICELOCAL) && !($userflags & MB_SERVICEATCUSTOMER);
	}

	// return default commission or custom commission
	function booker_getcommission($userid=null)
	{
		if(isset($userid))
		{
			$commission = qa_db_read_one_value(
							qa_db_query_sub('SELECT commission FROM ^booking_users
												WHERE `userid` = #
												', $userid), true
								);
			if(is_null($commission))
			{
				// default value
				return qa_opt('booker_commission');
			}
			else
			{
				return $commission;
			}
		}
		else
		{
			// default value
			return qa_opt('booker_commission');
		}
	} // booker_getcommission

	function helper_getweekdaynr_from_weekdayname($chunk)
	{
		// we save the weekday as weeknumber backend
		if(mb_strtolower($chunk, 'UTF-8')==mb_strtolower(qa_lang('booker_lang/mon'), 'UTF-8'))
		{
			$chunk = 1;
		}
		else if(mb_strtolower($chunk, 'UTF-8')==mb_strtolower(qa_lang('booker_lang/tue'), 'UTF-8'))
		{
			$chunk = 2;
		}
		else if(mb_strtolower($chunk, 'UTF-8')==mb_strtolower(qa_lang('booker_lang/wed'), 'UTF-8'))
		{
			$chunk = 3;
		}
		else if(mb_strtolower($chunk, 'UTF-8')==mb_strtolower(qa_lang('booker_lang/thu'), 'UTF-8'))
		{
			$chunk = 4;
		}
		else if(mb_strtolower($chunk, 'UTF-8')==mb_strtolower(qa_lang('booker_lang/fri'), 'UTF-8'))
		{
			$chunk = 5;
		}
		else if(mb_strtolower($chunk, 'UTF-8')==mb_strtolower(qa_lang('booker_lang/sat'), 'UTF-8'))
		{
			$chunk = 6;
		}
		else if(mb_strtolower($chunk, 'UTF-8')==mb_strtolower(qa_lang('booker_lang/sun'), 'UTF-8'))
		{
			$chunk = 7;
		}
		return $chunk;
	} // helper_getweekdaynr_from_weekdayname

	function helper_getweekdayname_from_weekdaynr($daynr)
	{
		$weekdays = helper_get_weekdayarray();
		return $weekdays[$daynr-1];
	}

	function helper_weekdays_to_numbers($dates_string)
	{
		$absentarray = explode(',', $dates_string);
		foreach($absentarray as &$chunk)
		{
			// if contains no number, then should be weekday
			if(strpbrk($chunk, '1234567890') === false)
			{
				$chunk = helper_getweekdaynr_from_weekdayname($chunk);
			}
		}
		$dates_string = implode(',',$absentarray);
		return $dates_string;
	}

	function helper_weeknumbers_to_days($string)
	{
		$absentarray = explode(',',$string);
		foreach($absentarray as &$chunk)
		{
			if(strlen($chunk)==1)
			{
				$chunk = helper_getweekdayname_from_weekdaynr($chunk);
			}
		}
		$string = implode(',',$absentarray);
		return $string;
	}

	// returns iso format, seconds get zerod
	function helper_day_to_datetime($datestring)
	{
		// if format is Y-m-d
		$d = DateTime::createFromFormat('Y-m-d', $datestring);
		if($d && $d->format('Y-m-d') === $datestring)
		{
			return $d->format('Y-m-d H:i:00');
		}

		// default: parse datestring according to admin set format
		$date = date_create_from_format(qa_lang('booker_lang/date_format_php'), $datestring);
		return $date->format('Y-m-d H:i:00');

		// memo: http://php.net/manual/de/function.checkdate.php
	}

	// equals strtotime: expects iso format, returns seconds from 1970
	function helper_datetime_to_seconds($datetime)
	{
		$date = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
		return $date->format('U');
	}

	// expects e.g. "12.06.2016 13:17" and returns "2016-06-12 13:17:00"
	function helper_localized_datetime_to_iso($datetime)
	{
		// default: parse datestring according to localized format
		$date = date_create_from_format(qa_lang('booker_lang/datetime_format_php'), $datetime);
		return $date->format('Y-m-d H:i:00');
	}

	function helper_format_currency($value, $digits=2, $leavezeros=false)
	{
		if($leavezeros)
		{
			return number_format($value, $digits, ',', '.');
		}
		// removes the ,00
		return str_replace(',00', '', number_format($value, $digits, ',', '.'));
	}

	function loadlocationservice($origin, $target)
	{
			$output = '';
			$output .= '
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places&language='.qa_opt('site_language').'&key='.qa_opt('booker_gmapsapikey').'"></script>
			';
			$output .= "
	<script type=\"text/javascript\">
        var source, destination;

		var directionsDisplay;
		var directionsService = new google.maps.DirectionsService();
		google.maps.event.addDomListener(window, 'load', function () {
			new google.maps.places.SearchBox(document.getElementById('txtSource'));
			new google.maps.places.SearchBox(document.getElementById('txtDestination'));
			directionsDisplay = new google.maps.DirectionsRenderer({ 'draggable': true });
		});

		$(document).ready(function()
		{
			// get distance and travel time at startup
			source = document.getElementById('txtSource').value;
			destination = document.getElementById('txtDestination').value;
			var service = new google.maps.DistanceMatrixService();

			function calculateDistDur()
			{
				// DISTANCE AND DURATION
				service.getDistanceMatrix({
					origins: [source],
					destinations: [destination],
					travelMode: google.maps.TravelMode.DRIVING,
					unitSystem: google.maps.UnitSystem.METRIC,
					avoidHighways: false,
					avoidTolls: false
				}, function (response, status) {
					if (status == google.maps.DistanceMatrixStatus.OK && response.rows[0].elements[0].status != 'ZERO_RESULTS') {
						var distance = response.rows[0].elements[0].distance.text;
						var duration = response.rows[0].elements[0].duration.text;
						$('#dvDistance #distance').text(distance);
						$('#dvDistance #duration').text(duration);
					}
					else
					{
						alert('Unable to find the distance via road.');
					}
				});
			}

			// at start up
			calculateDistDur();

			var mapshowing = false;
			$('.locationlink').click( function(e)
			{
				e.preventDefault();

				mapshowing = !mapshowing;
				if(mapshowing)
				{
					$(this).text('".qa_lang('booker_lang/hidemap')."');
					$('.gmapstable, #dvMap').show();
					$('#routebtn').trigger('click');
				}
				else
				{
					$(this).text('".qa_lang('booker_lang/showmap')."');
					$('.gmapstable, #dvMap').hide();
				}
			});

			$('#routebtn').click( function()
			{

				source = document.getElementById('txtSource').value;
				destination = document.getElementById('txtDestination').value;

				// LOAD MAP
				var startcenter = new google.maps.LatLng(52.5200, 13.4050); // Berlin
				var mapOptions = {
					zoom: 7,
					// center: startcenter
				};
				map = new google.maps.Map(document.getElementById('dvMap'), mapOptions);
				directionsDisplay.setMap(map);
				// directionsDisplay.setPanel(document.getElementById('dvPanel'));

				// DIRECTIONS AND ROUTE
				var request = {
					origin: source,
					destination: destination,
					travelMode: google.maps.TravelMode.DRIVING
				};
				directionsService.route(request, function (response, status) {
					if (status == google.maps.DirectionsStatus.OK) {
						directionsDisplay.setDirections(response);
					}
				});

				calculateDistDur();

			}); // end click

	}); // end ready
    </script>
			";

			$output .= '
    <table class="gmapstable">
        <tr>
            <td>
                '.qa_lang('booker_lang/start').':
            </td>
			<td>
				<input type="text" id="txtSource" value="'.$origin.'" />
			</td>
		</tr>
		<tr>
            <td>
                '.qa_lang('booker_lang/target').':
            </td>
            <td>
                <input type="text" id="txtDestination" value="'.$target.'"/>
            </td>
        </tr>
		<tr>
            <td>
            </td>
            <td style="text-align:right;">
				<span id="routebtn">'.qa_lang('booker_lang/updatebtn').'</span>
            </td>
        </tr>
    </table> <!-- gmapstable -->

    <div id="dvMap"></div>

	<!-- <div id="dvPanel"></div> -->

			';
			return $output;
	} // END loadlocationservice

	function booker_bookingisoffer($bookid)
	{
		if(empty($bookid))
		{
			return false;
		}
		$offerid = qa_db_read_one_value(
							qa_db_query_sub('SELECT offerid FROM `^booking_orders`
												WHERE `bookid` = #
												LIMIT 1
												;', $bookid), true );

		return !is_null($offerid);
	} // end booker_bookingisoffer

	function booker_get_all_offers($start=0, $size=20, $orderby='created')
	{
		$queryconditions = '';

		$offerdata = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT offerid, created, userid, title, price, duration, end, description, flags, status
														FROM `^booking_offers`
														WHERE
														(
															`userid` IS NOT NULL
															'.$queryconditions.'
															 AND (status & #) = #
															 AND (status & #) = #
														)
														ORDER BY '.$orderby.' ASC
														LIMIT #, #
														',
														MB_OFFER_APPROVED, MB_OFFER_APPROVED,
														MB_OFFER_ACTIVE, MB_OFFER_ACTIVE,
														$start, $size)
												  );
		return $offerdata;
	}

	function booker_get_all_offers_admin($start=0, $size=20)
	{
		$offerdata = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT offerid, created, userid, title, price, duration, end, description, flags, status
														FROM `^booking_offers`
														WHERE
														(
															`userid` IS NOT NULL
														)
														ORDER BY created ASC
														LIMIT #, #
														',
														$start, $size)
												  );
		return $offerdata;
	}

	function booker_get_offercount_total($countall=false)
	{
		// flags: MB_OFFER_APPROVED, MB_OFFER_ACTIVE, MB_OFFER_DELETED
		$queryconditions = '
			WHERE (status & '.MB_OFFER_APPROVED.') = '.MB_OFFER_APPROVED.'
			AND (status & '.MB_OFFER_ACTIVE.') = '.MB_OFFER_ACTIVE.'
		';
		
		if($countall)
		{
			$queryconditions = '';
		}
		
		return qa_db_read_one_value(
						qa_db_query_sub('SELECT COUNT(offerid) FROM ^booking_offers
											'.$queryconditions
								));
	}

	function helper_extract_urls_from_text($string)
	{
		//$regex = '/https?\:\/\/[^\" ]+/i';
		$regex = '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';
		preg_match_all($regex, $string, $matches);
		return $matches[0];
	}

	function helper_get_imagelinks_from_htmlstring($string)
	{
		$dom = new DOMDocument();
		$html = $dom->loadHTML($string);
		$links = $dom->getElementsByTagName('img');
		$imagelinks = [];
		foreach($links as $imgurl)
		{
			$imagelinks[] = $imgurl->getAttribute('src');
		}
		// only links that hold "blobid"
		return $imagelinks;
	}

	function booker_requests_to_table($allrequests, $pluginroot)
	{
		$clirequests = '';

		$clirequests .= '
		<div class="requeststablewrap">
			<table id="requeststable">
			<tr>
				<th>'.qa_lang('booker_lang/requests').'</th>
				<th>'.qa_lang('booker_lang/end').'</th>
			</tr>
		';

		$imageexts = array('jpg', 'jpeg', 'png', 'gif');

		foreach($allrequests as $request)
		{
			// default image
			// $requestimage = '<img class="requestimg listnoimage" src="'.$this->urltoroot.'images/icon-noimage.png" alt="no image" />';
			$requestimage = $pluginroot.'images/icon-noimage.png';

			if(!empty($request['description']))
			{
				$text = $request['description'];
				// check if image tag in post, must be in upload folder
				$uploadfolder = qa_opt('booker_uploadurl');
				if(strpos($text, $uploadfolder) !== false)
				{
					// get all URLs
					$urls = helper_get_imagelinks_from_htmlstring($text);
					foreach($urls as $url)
					{
						$ext = pathinfo($url, PATHINFO_EXTENSION);
						if(in_array($ext,$imageexts))
						{
							// found blobid add link to array
							$requestimage = $url;
						}
					}
				}
			}

			$endtime = DateTime::createFromFormat('Y-m-d H:i:s', $request['end']);
			$now = new DateTime('now', new DateTimeZone( qa_opt('booker_timezone') ));
			$diffsecs = $endtime->format('U') - $now->format('U');

			$requestlocation = '';
			if(!empty($request['location']))
			{
				$requestlocation = qa_lang('booker_lang/location').': '.$request['location'];
			}

			$clirequests .= '
			<tr>
				<td>
					<a class="requestlistimage" href="'.qa_path('bid').'?requestid='.$request['requestid'].'"
						style="background-image:url(\''.$requestimage.'\')">
					</a>
					<a href="'.qa_path('bid').'?requestid='.$request['requestid'].'" class="requesttitle tooltipW">
						'.$request['title'].'
					</a>
					<p class="requestpreviewtext">
						'.helper_shorten_text($request['description'], 100).'
					</p>
					<p class="requestlocation">
						'.$requestlocation.'
					</p>
				</td>
				<td>
					<span>
						'.booker_get_time_to_end($diffsecs).'
					</span>
					<br />
					<span class="requesttime">
						'.(empty($request['end']) ? '-' : helper_get_readable_date_from_time($request['end'], true, false)).'
					</span>
				</td>
			</tr>
			';
		}
		/*		<td>
					'.roundandcomma($request['price']).' '.qa_opt('booker_currency').'
				</td>
		*/
		// <td>'.$request['status'].'</td>
		// <td>'.$request['description'].'</td>
		$clirequests .= '
				</table> <!-- requeststable -->
			</div> <!-- requeststablewrap -->
		';

		return $clirequests;

	} // end booker_requests_to_table


	function booker_offers_to_table($alloffers, $pluginroot)
	{
		$conoffers = '';

		$conoffers .= '
		<div class="offerstablewrap">
			<table id="offerstable">
			<tr>
				<th>'.qa_lang('booker_lang/offers').'</th>
				<th>'.qa_lang('booker_lang/end').'</th>
			</tr>
		';

		$imageexts = array('jpg', 'jpeg', 'png', 'gif');

		foreach($alloffers as $offer)
		{
			// default image
			// $offerimage = '<img class="offerimg listnoimage" src="'.$this->urltoroot.'images/icon-noimage.png" alt="no image" />';
			$offerimage = $pluginroot.'images/icon-noimage.png';

			if(!empty($offer['description']))
			{
				$text = $offer['description'];
				// check if image tag in post, must be in upload folder
				$uploadfolder = qa_opt('booker_uploadurl');
				if(strpos($text, $uploadfolder) !== false)
				{
					// get all URLs
					$urls = helper_get_imagelinks_from_htmlstring($text);
					foreach($urls as $url)
					{
						$ext = pathinfo($url, PATHINFO_EXTENSION);
						if(in_array($ext,$imageexts))
						{
							// found blobid add link to array
							$offerimage = $url;
						}
					}
				}
			}

			$limitedoffer = '';
			if(!empty($offer['end']))
			{
				$endtime = null;
				// catch Y-m-d
				if(strlen($offer['end'])<=10)
				{
					$endtime = DateTime::createFromFormat('Y-m-d', $offer['end']);
				}
				else
				{
					$endtime = DateTime::createFromFormat('Y-m-d H:i:s', $offer['end']);
				}
				$now = new DateTime('now', new DateTimeZone( qa_opt('booker_timezone') ));
				$diffsecs = $endtime->format('U') - $now->format('U');
				$limitedoffer = booker_get_time_to_end($diffsecs);
			}

			$offerlocation = '';
			/*
			if(!empty($offer['location']))
			{
				$offerlocation = qa_lang('booker_lang/location').': '.$offer['location'];
			}
			*/
			$offerprice = '<a class="defaultbutton btn_green buttonthin" href="'.qa_path('booking').'?offerid='.$offer['offerid'].'">'.helper_format_currency($offer['price'], 0).' '.qa_opt('booker_currency').'</a>';

			$conoffers .= '
			<tr>
				<td>
					<a class="offerlistimage" href="'.qa_path('booking').'?offerid='.$offer['offerid'].'"
						style="background-image:url(\''.$offerimage.'\')">
					</a>
					<a href="'.qa_path('booking').'?offerid='.$offer['offerid'].'" class="offertitle tooltipW">
						'.$offer['title'].'
					</a>
					<p class="offerpreviewtext">
						'.helper_shorten_text($offer['description'], 100).'
					</p>
					<p class="offerlocation">
						'.$offerlocation.'
					</p>
					<p class="offerprice">
						'.$offerprice.'
					</p>
				</td>
				<td>
					<span>
						'.$limitedoffer.'
					</span>
					<br />
					<span class="offertime">
						'.(empty($offer['end']) ? '' : helper_get_readable_date_from_time($offer['end'], true, false)).'
					</span>
				</td>
			</tr>
			';
		}
		/*		<td>
					'.roundandcomma($offer['price']).' '.qa_opt('booker_currency').'
				</td>
		*/
		// <td>'.$offer['status'].'</td>
		// <td>'.$offer['description'].'</td>
		$conoffers .= '
				</table> <!-- offerstable -->
			</div> <!-- offerstablewrap -->
		';

		return $conoffers;

	} // end booker_offers_to_table


	function booker_get_bids($requestid)
	{
		$output = '';
		$existingbids = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT bidid, created, userid, price, comment FROM `^booking_bids`
														WHERE requestid = #
														',
														$requestid)
											);
		if(!empty($existingbids))
		{
			$output .= '<ul class="bidtable">';
			foreach($existingbids as $bid)
			{
				$output .= '
					<li>
						<a href="#" title="'.$bid['comment'].'" class="tooltip">
							'.roundandcomma($bid['price']).' '.qa_opt('booker_currency').' '.booker_get_realname($bid['userid']).'
						</a>
					</li>
				';
			}
			$output .= '</ul>';
		}

		return $output;
	} // end booker_get_bids

	function booker_get_time_to_end($seconds)
	{
		$output = '';
		$helper = $seconds;

		// only days if one day or more
		if($helper>=24*60*60)
		{
			// days
			$days = floor($helper/(24*60*60));
			if($days > 0)
			{
				if($days==1)
				{
					$output .= qa_lang('main/1_day').' ';
				}
				else
				{
					$output .= qa_lang_sub('main/x_days', $days).' ';
				}
			}
			$helper -= $days*(24*60*60);
		}
		// only hours if one hour or more
		else if($helper>=1*60*60)
		{
			$hours = floor($helper/(1*60*60));
			if($hours > 0)
			{
				if($hours==1)
				{
					$output .= qa_lang('main/1_hour').' ';
				}
				else
				{
					$output .= qa_lang_sub('main/x_hours', $hours).' ';
				}
			}
		}
		// only minutes if one minute or more
		else if($helper>=1*60)
		{
			$minutes = floor($helper/(1*60));
			if($minutes > 0)
			{
				if($minutes==1)
				{
					$output .= qa_lang('main/1_minute').' ';
				}
				else
				{
					$output .= qa_lang_sub('main/x_minutes', $minutes).' ';
				}
			}
		}
		// only seconds
		else
		{
			$seconds = floor($helper/(1*60));
			if($seconds > 0)
			{
				if($seconds==1)
				{
					$output .= $seconds.' '.qa_lang('main/1_minute').' ';
				}
				else
				{
					$output .= qa_lang_sub('main/x_seconds', $seconds).' ';
				}
			}
		}

		return qa_lang('booker_lang/endsin').' '.$output;

	} // end booker_get_time_to_end

	function booker_get_requeststatus($requestid)
	{
		if(empty($requestid))
		{
			return false;
		}
		$requestid = qa_db_read_one_value(
							qa_db_query_sub('SELECT status FROM `^booking_requests`
												WHERE `requestid` = #
												', $requestid), true);

		return $requestid;
	}

	function booker_get_offerstatus($offerid)
	{
		if(empty($offerid))
		{
			return false;
		}
		$offerid = qa_db_read_one_value(
							qa_db_query_sub('SELECT status FROM `^booking_offers`
												WHERE `offerid` = #
												', $offerid), true);

		return $offerid;
	}

	function booker_get_contractor_rating($contractorid)
	{
		// get existing ratings of user
		$userratings = qa_db_read_all_assoc(
							qa_db_query_sub('SELECT created, customerid, eventid, rating, text FROM `^booking_ratings`
												 WHERE contractorid = #
												 ', $contractorid)
											);
		$ratcount = count($userratings);
		if($ratcount==0)
		{
			return array(
				'ratingvalue' => null,
				'ratingcount' => null
			);
		}
		else
		{
			$ratsum = 0;
			foreach($userratings as $rat)
			{
				// rating is 1 to 5
				$ratsum += $rat['rating'];
			}
			// since user ratings are 1 to 5, multiply by 2
			// actually doing so will lead to scores from 2 to 10
			$rataverage = 2*$ratsum/$ratcount;
			return array(
				'ratingvalue' => roundandcomma($rataverage, 1),
				'ratingcount' => $ratcount
			);
		}
	}

	function helper_get_all_followers($userid)
	{
		return qa_db_read_all_assoc(
				qa_db_query_sub('SELECT userid FROM `^userfavorites`
									WHERE `entityid` = #
									AND `entitytype` = "U"
									', $userid) );
	}

	function helper_get_all_userisfollowing($userid)
	{
		return qa_db_read_all_assoc(
				qa_db_query_sub('SELECT entityid as userid FROM `^userfavorites`
									WHERE `userid` = #
									AND `entitytype` = "U"
									', $userid) );
	}

	function helper_listfollowers($userfollowers)
	{
		$output = '';
		// $procontent .= '<ol>';
		foreach($userfollowers as $follower)
		{
			// get userdata
			$user = qa_db_read_one_assoc(qa_db_query_sub('SELECT handle, avatarblobid FROM ^users
															WHERE userid = #',
															$follower['userid']));
			if(isset($user['avatarblobid']))
			{
				$avatar = './?qa=image&qa_blobid='.$user['avatarblobid'].'&qa_size=100';
			}
			else
			{
				$avatar = './?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size=100';
			}
			$userprofilelink = qa_path_html('user/'.$user['handle']);
			$handledisplay = qa_html($user['handle']);
			$realname = booker_get_realname($follower['userid'], true);

			// user item
			$output .= '
				<a class="xavatar2" style="background:url(\''.$avatar.'\') no-repeat top;background-size:cover;" href="'.$userprofilelink.'">
					<span class="follower-user-link">'.$realname.'</span>
				</a>
			';
		}
		$output .= '<div style="clear:both;"></div>';
		return $output;
	} // END count($userfollowers)>0

	function helper_get_calendar_instructions()
	{
		$output = '
					<ul>
						<li>
							<a target="_blank" href="https://support.google.com/calendar/answer/37100?hl='.qa_opt('booker_language').'">
								'.qa_lang('booker_lang/calguide_google').'
							</a>
						</li>
		';
		if(qa_opt('booker_language')=='lt')
		{
			$output .= '
						<li>
							<a target="_blank" href="https://support.office.com/lt-lt/article/-Outlook-com-arba-internetin%c4%97-Outlook-kalendoriaus-importavimas-arba-CFF1429C-5AF6-41EC-A5B4-74F2C278E98C?omkt=lt-LT&ui=lt-LT&rs=lt-LT&ad=LT">
								'.qa_lang('booker_lang/calguide_outlook').'
							</a>
						</li>
						<li>
							<a target="_blank" href="https://support.mozilla.org/lt/kb/creating-new-calendars#w_icalendar-ics">
								'.qa_lang('booker_lang/calguide_thunderbird').'
							</a>
						</li>
			';
		}
		else if(qa_opt('booker_language')=='de')
		{
			$output .= '
						<li>
							<a target="_blank" href="https://support.office.com/de-de/article/Importieren-oder-Abonnieren-eines-Kalenders-in-Outlook-im-Web-CFF1429C-5AF6-41EC-A5B4-74F2C278E98C?omkt=de-DE&ui=de-DE&rs=de-DE&ad=DE">
								'.qa_lang('booker_lang/calguide_outlook').'
							</a>
						</li>
						<li>
							<a target="_blank" href="https://support.mozilla.org/de/kb/neue-kalender-erstellen#w_icalendar-ics">
								'.qa_lang('booker_lang/calguide_thunderbird').'
							</a>
						</li>
			';
		}
		else
		{
			$output .= '
						<li>
							<a target="_blank" href="https://support.office.com/en-us/article/Import-or-subscribe-to-a-calendar-in-Outlook-com-or-Outlook-on-the-web-CFF1429C-5AF6-41EC-A5B4-74F2C278E98C?ui=en-US&rs=en-US&ad=US">
								'.qa_lang('booker_lang/calguide_outlook').'
							</a>
						</li>
						<li>
							<a target="_blank" href="https://support.mozilla.org/en-US/kb/creating-new-calendars#w_icalendar-ics">
								'.qa_lang('booker_lang/calguide_thunderbird').'
							</a>
						</li>
			';
		}
		$output .= '
					</ul>
		';

		return $output;
	} // end helper_get_calendar_instructions

	function helper_get_calendar_hints($urltoroot)
	{
		$output = '
					<div class="ghints">
						<h2>
							'.qa_lang('booker_lang/hints').'
						</h2>
						<ol>
							<li>
								'.qa_lang('booker_lang/gcal_hint1').'
								<br />
								<span class="screenshotlabel">
									'.qa_lang('booker_lang/screenshot').':
								</span>
								<img class="manual_image" src="'.$urltoroot.'images/google-calendar-settings.png" alt="google calendar settings" />
							</li>
							<li>
								'.qa_lang('booker_lang/gcal_hint2').'
								<br />
								<span class="screenshotlabel">
									'.qa_lang('booker_lang/screenshot').':
								</span>
								<img class="manual_image" src="'.$urltoroot.'images/google-calendar-address.png" alt="google calendar address" />
								<span style="display:block;margin:10px 0 0 20px;">
									'.qa_lang('booker_lang/or').':
								</span>
								<img class="manual_image" src="'.$urltoroot.'images/google-calendar-address-mail.png" alt="google calendar address mail" />
							</li>
							<li>
								'.qa_lang('booker_lang/gcal_hint3').'
								<br />
								<span class="screenshotlabel">
									'.qa_lang('booker_lang/screenshot').':
								</span>
								<img class="manual_image" src="'.$urltoroot.'images/google-calendar-busy.png" alt="google calendar busy" />
							</li>
						</ol>
					</div>
		';

		return $output;
	} // END helper_get_calendar_hints

	function booker_get_booking_count($userid, $upcoming=false, $dayinterval=30)
	{
		$timeframe = '';
		if(!$upcoming)
		{
			// last 30 days
			$timeframe = 'AND `starttime` >= DATE_ADD(CURDATE(), INTERVAL -'.$dayinterval.' DAY) ';
		}
		else
		{
			// future
			$timeframe = 'AND `starttime` >= NOW() ';
		}

		if(booker_iscontracted($userid))
		{
			return qa_db_read_one_value(
							qa_db_query_sub('SELECT COUNT(eventid) FROM ^booking_orders
												WHERE `contractorid` = #
												'.$timeframe.'
												AND `status` > #
												', $userid, MB_EVENT_OPEN), true
									);
		}
		else
		{
			return qa_db_read_one_value(
							qa_db_query_sub('SELECT COUNT(eventid) FROM ^booking_orders
												WHERE `customerid` = #
												'.$timeframe.'
												AND `status` > #
												', $userid, MB_EVENT_OPEN), true
									);
		}

	} // end booker_get_booking_count


	function booker_get_booking_sum($userid, $dayinterval=30, $considerall=false)
	{
		$timeframe = '';
		if(!$considerall)
		{
			// last 30 days
			$timeframe = 'AND `starttime` >= DATE_ADD(CURDATE(), INTERVAL -'.$dayinterval.' DAY) ';
		}

		if(booker_iscontracted($userid))
		{
			$bookdata = qa_db_read_all_assoc(
							qa_db_query_sub('SELECT eventid FROM ^booking_orders
												WHERE `contractorid` = #
												'.$timeframe.'
												AND `status` >= #
												', $userid, MB_EVENT_ACCEPTED)
									);
		}
		else
		{
			$bookdata = qa_db_read_all_assoc(
							qa_db_query_sub('SELECT eventid FROM ^booking_orders
												WHERE `customerid` = #
												'.$timeframe.'
												AND `status` > #
												', $userid, MB_EVENT_OPEN)
									);
		}

		$totalprice = 0;

		foreach($bookdata as $bookitem)
		{
			$totalprice += booker_get_eventprice($bookitem['eventid']);
		}

		return $totalprice;

	} // end booker_get_booking_sum


	function booker_get_accountbalance($userid, $eventstatus=MB_EVENT_COMPLETED, $daylimit=null)
	{
		$daylimit_query = '';
		if(isset($daylimit))
		{
			// last x days
			$daylimit_query = 'AND `starttime` >= DATE_ADD(CURDATE(), INTERVAL -'.$daylimit.' DAY) ';
		}

		$bookdata = qa_db_read_all_assoc(
						qa_db_query_sub('SELECT eventid FROM ^booking_orders
											WHERE `contractorid` = #
											'.$daylimit_query.'
											AND `status` = #
											', $userid, $eventstatus)
								);
		$totalprice = 0;
		foreach($bookdata as $bookitem)
		{
			$totalprice += booker_get_eventprice($bookitem['eventid']);
		}

		return $totalprice;

	} // end booker_get_booking_sum

	function booker_become_premium_notify($notifytype='')
	{
		$notify_message = qa_lang('booker_lang/needpremium');

		if($notifytype=='offers_exceeded')
		{
			$notify_message = qa_lang('booker_lang/needpremium_offers');
		}
		else if($notifytype=='send_messages')
		{
			$notify_message = qa_lang('booker_lang/needpremium_messages');
		}

		return '
			<div class="becomepremium-notify">
				<p>
					'.$notify_message.'
				</p>
				<a href="'.qa_path('premium').'" class="btn btn-primary becomepremium-button">
					'.qa_lang('booker_lang/become_premiummember').'
				</a>
			</div>
		';
	}

	function helper_strip_urls($url)
	{
		$U = explode(' ',$url);

		$W = array();
		foreach ($U as $k => $u)
		{
			if (stristr($u,".")) //only preg_match if there is a dot
			{
				if(containsTLD($u) === true)
				{
					// unset($U[$k]);
					$U[$k] = '<a href="'.qa_path('premium').'" title="'.qa_lang('booker_lang/needpremium_links').'">***</a>';
					return helper_strip_urls(implode(' ',$U));
				}
			}
		}
		return implode(' ',$U);
	} // end helper_strip_urls($url)

	function containsTLD($string)
	{
		preg_match(		"/(AC($|\/)|\.AD($|\/)|\.AE($|\/)|\.AERO($|\/)|\.AF($|\/)|\.AG($|\/)|\.AI($|\/)|\.AL($|\/)|\.AM($|\/)|\.AN($|\/)|\.AO($|\/)|\.AQ($|\/)|\.AR($|\/)|\.ARPA($|\/)|\.AS($|\/)|\.ASIA($|\/)|\.AT($|\/)|\.AU($|\/)|\.AW($|\/)|\.AX($|\/)|\.AZ($|\/)|\.BA($|\/)|\.BB($|\/)|\.BD($|\/)|\.BE($|\/)|\.BF($|\/)|\.BG($|\/)|\.BH($|\/)|\.BI($|\/)|\.BIZ($|\/)|\.BJ($|\/)|\.BM($|\/)|\.BN($|\/)|\.BO($|\/)|\.BR($|\/)|\.BS($|\/)|\.BT($|\/)|\.BV($|\/)|\.BW($|\/)|\.BY($|\/)|\.BZ($|\/)|\.CA($|\/)|\.CAT($|\/)|\.CC($|\/)|\.CD($|\/)|\.CF($|\/)|\.CG($|\/)|\.CH($|\/)|\.CI($|\/)|\.CK($|\/)|\.CL($|\/)|\.CM($|\/)|\.CN($|\/)|\.CO($|\/)|\.COM($|\/)|\.COOP($|\/)|\.CR($|\/)|\.CU($|\/)|\.CV($|\/)|\.CX($|\/)|\.CY($|\/)|\.CZ($|\/)|\.DE($|\/)|\.DJ($|\/)|\.DK($|\/)|\.DM($|\/)|\.DO($|\/)|\.DZ($|\/)|\.EC($|\/)|\.EDU($|\/)|\.EE($|\/)|\.EG($|\/)|\.ER($|\/)|\.ES($|\/)|\.ET($|\/)|\.EU($|\/)|\.FI($|\/)|\.FJ($|\/)|\.FK($|\/)|\.FM($|\/)|\.FO($|\/)|\.FR($|\/)|\.GA($|\/)|\.GB($|\/)|\.GD($|\/)|\.GE($|\/)|\.GF($|\/)|\.GG($|\/)|\.GH($|\/)|\.GI($|\/)|\.GL($|\/)|\.GM($|\/)|\.GN($|\/)|\.GOV($|\/)|\.GP($|\/)|\.GQ($|\/)|\.GR($|\/)|\.GS($|\/)|\.GT($|\/)|\.GU($|\/)|\.GW($|\/)|\.GY($|\/)|\.HK($|\/)|\.HM($|\/)|\.HN($|\/)|\.HR($|\/)|\.HT($|\/)|\.HU($|\/)|\.ID($|\/)|\.IE($|\/)|\.IL($|\/)|\.IM($|\/)|\.IN($|\/)|\.INFO($|\/)|\.INT($|\/)|\.IO($|\/)|\.IQ($|\/)|\.IR($|\/)|\.IS($|\/)|\.IT($|\/)|\.JE($|\/)|\.JM($|\/)|\.JO($|\/)|\.JOBS($|\/)|\.JP($|\/)|\.KE($|\/)|\.KG($|\/)|\.KH($|\/)|\.KI($|\/)|\.KM($|\/)|\.KN($|\/)|\.KP($|\/)|\.KR($|\/)|\.KW($|\/)|\.KY($|\/)|\.KZ($|\/)|\.LA($|\/)|\.LB($|\/)|\.LC($|\/)|\.LI($|\/)|\.LK($|\/)|\.LR($|\/)|\.LS($|\/)|\.LT($|\/)|\.LU($|\/)|\.LV($|\/)|\.LY($|\/)|\.MA($|\/)|\.MC($|\/)|\.MD($|\/)|\.ME($|\/)|\.MG($|\/)|\.MH($|\/)|\.MIL($|\/)|\.MK($|\/)|\.ML($|\/)|\.MM($|\/)|\.MN($|\/)|\.MO($|\/)|\.MOBI($|\/)|\.MP($|\/)|\.MQ($|\/)|\.MR($|\/)|\.MS($|\/)|\.MT($|\/)|\.MU($|\/)|\.MUSEUM($|\/)|\.MV($|\/)|\.MW($|\/)|\.MX($|\/)|\.MY($|\/)|\.MZ($|\/)|\.NA($|\/)|\.NAME($|\/)|\.NC($|\/)|\.NE($|\/)|\.NET($|\/)|\.NF($|\/)|\.NG($|\/)|\.NI($|\/)|\.NL($|\/)|\.NO($|\/)|\.NP($|\/)|\.NR($|\/)|\.NU($|\/)|\.NZ($|\/)|\.OM($|\/)|\.ORG($|\/)|\.PA($|\/)|\.PE($|\/)|\.PF($|\/)|\.PG($|\/)|\.PH($|\/)|\.PK($|\/)|\.PL($|\/)|\.PM($|\/)|\.PN($|\/)|\.PR($|\/)|\.PRO($|\/)|\.PS($|\/)|\.PT($|\/)|\.PW($|\/)|\.PY($|\/)|\.QA($|\/)|\.RE($|\/)|\.RO($|\/)|\.RS($|\/)|\.RU($|\/)|\.RW($|\/)|\.SA($|\/)|\.SB($|\/)|\.SC($|\/)|\.SD($|\/)|\.SE($|\/)|\.SG($|\/)|\.SH($|\/)|\.SI($|\/)|\.SJ($|\/)|\.SK($|\/)|\.SL($|\/)|\.SM($|\/)|\.SN($|\/)|\.SO($|\/)|\.SR($|\/)|\.ST($|\/)|\.SU($|\/)|\.SV($|\/)|\.SY($|\/)|\.SZ($|\/)|\.TC($|\/)|\.TD($|\/)|\.TEL($|\/)|\.TF($|\/)|\.TG($|\/)|\.TH($|\/)|\.TJ($|\/)|\.TK($|\/)|\.TL($|\/)|\.TM($|\/)|\.TN($|\/)|\.TO($|\/)|\.TP($|\/)|\.TR($|\/)|\.TRAVEL($|\/)|\.TT($|\/)|\.TV($|\/)|\.TW($|\/)|\.TZ($|\/)|\.UA($|\/)|\.UG($|\/)|\.UK($|\/)|\.US($|\/)|\.UY($|\/)|\.UZ($|\/)|\.VA($|\/)|\.VC($|\/)|\.VE($|\/)|\.VG($|\/)|\.VI($|\/)|\.VN($|\/)|\.VU($|\/)|\.WF($|\/)|\.WS($|\/)|\.XN--0ZWM56D($|\/)|\.XN--11B5BS3A9AJ6G($|\/)|\.XN--80AKHBYKNJ4F($|\/)|\.XN--9T4B11YI5A($|\/)|\.XN--DEBA0AD($|\/)|\.XN--G6W251D($|\/)|\.XN--HGBK6AJ7F53BBA($|\/)|\.XN--HLCJ6AYA9ESC7A($|\/)|\.XN--JXALPDLP($|\/)|\.XN--KGBECHTV($|\/)|\.XN--ZCKZAH($|\/)|\.YE($|\/)|\.YT($|\/)|\.YU($|\/)|\.ZA($|\/)|\.ZM($|\/)|\.ZW)/i",
		$string,
		$M);
		$has_tld = (count($M) > 0) ? true : false;
		return $has_tld;
	} // end containsTLD($string)

	function helper_remove_email_phone($string)
	{
		// remove email
		$replace_email = '<a href="'.qa_path('premium').'" title="'.qa_lang('booker_lang/needpremium_email').'">***</a>';
		$string = preg_replace('/([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/', $replace_email, $string);

		// remove phone
		$replace_phone = '<a href="'.qa_path('premium').'" title="'.qa_lang('booker_lang/needpremium_phone').'">***</a>';
		// $string = preg_replace('/([0-9]+[\- ]?[0-9]+)/', $replace_phone, $string);
		// remove all numbers longer than 7 chars
		$string = preg_replace('/(\d{7,})/', $replace_phone, $string);
		// need min length, see http://stackoverflow.com/a/19984624/1066234

		return $string;
	}

	function booker_loginform_output($request)
	{
		$string = '';
		if(!qa_is_logged_in())
		{
			$topath = qa_get('to');
			$userlinks = qa_get_login_links(qa_path_to_root(), isset($topath) ? $topath : qa_path($request, $_GET, ''));

			$string .= '
			<h3>
				'.qa_lang('main/nav_login').'
			</h3>
			<div id="loginform" style="max-width:400px;">
					<form method="post" action="'.qa_html(@$userlinks['login']).'" id="loginForm">
						<fieldset id="loginbody">
							<p id="form-login-username">
								<label>'.qa_lang('booker_lang/lang_mailorusername').'</label>
								<input name="emailhandle" value="" type="text" size="18" class="inputbox" required>
							</p>
							<p id="form-login-password">
								<label>'.qa_lang('booker_lang/lang_password').'</label>
								<input name="password" type="password" value="" size="18" class="inputbox" required>
							</p>
							<input type="submit" value="'.qa_lang('booker_lang/lang_login').'" class="btnblue prisijungti">
							<p id="form-login-remember">
								<input name="remember" type="checkbox" value="1" checked="checked" id="checkRemember" class="inputbox" >
								<label for="checkRemember" class="remember-label">'.qa_lang('booker_lang/lang_rememberme').'</label>
							</p>
						</fieldset>
						<input type="hidden" name="dologin" value="1">
						<input type="hidden" name="code" value="'.qa_get_form_security_code('login').'">
					</form>
			</div>

			<div class="qa-warning" style="margin-top:50px;">
				'.qa_insert_login_links(qa_lang('booker_lang/needregisterlogin')).'
			</div>

			';
		}

		return $string;
	} // booker_loginform_output($request)


	function booker_get_offercount($contractorid, $approved = false, $onlyactive = false)
	{
		// booker_get_offers($userid, $onlyactive=false)
		return count(booker_get_offers($contractorid, $onlyactive));
		
		/*
		$queryconditions = '';
		if($approved)
		{
			$queryconditions .= 'AND `approved` = "'.MB_OFFER_APPROVED.'" ';
		}
		if($active)
		{
			$queryconditions .= 'AND `contracted` IS NOT NULL ';
		}
		return qa_db_read_one_value(
						qa_db_query_sub('SELECT COUNT(userid) FROM ^booking_users
											WHERE
											`userid` IS NOT NULL
											'.$queryconditions
								));
		*/
	}

	function helper_youtube_links_to_embeds($string)
	{
		return preg_replace(
			"/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i",
			"<iframe src=\"//www.youtube.com/embed/$2\" data-youtube-id=\"$2\" frameborder=\"0\" allowfullscreen style=\"width: 560px; height: 315px;\"></iframe>",
			$string
		);
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/

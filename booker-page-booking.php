<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_booking
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
					'title' => 'booker Booking Page', // title of page
					'request' => 'booking', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='booking')
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
				qa_set_template('booker booking');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			// AJAX: customer is booking, save events to db
			$transferString = qa_post_text('bookdata'); // holds array
			if(isset($transferString))
			{
				header('Content-Type: application/json; charset=UTF-8');

				$userid = qa_get_logged_in_userid();

				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				if(count($newdata)==0)
				{
					header('HTTP/1.1 500 Internal Server Error');
					echo 'No data received.';
					return;
				}

				// in case a guest is booking, create an account for him
				if(is_null($userid))
				{
					require_once QA_INCLUDE_DIR.'app/users.php';
					require_once QA_INCLUDE_DIR.'app/users-edit.php';

					$email = $newdata[0]['email'];
					$inpassword = $newdata[0]['password'];
					$realname = $newdata[0]['customername'];

					if(empty($email) || empty($inpassword) || empty($realname))
					{
						header('HTTP/1.1 500 Internal Server Error');
						echo 'Es gab ein Problem mit deinen Daten für E-Mail und Passwort. Leer.';
						return;
					}

					// generate handle, will lead to e.g. kvanto7
					$inhandle = $this->q2apro_generate_userhandle('client');

					// validate data
					// core validation
					$errors = array_merge(
						qa_handle_email_filter($inhandle, $email),
						qa_password_validate($inpassword)
					);

					if(empty($errors))
					{
						// create user with userid and handle and password
						// qa_create_new_user($email, $password, $handle, $level=QA_USER_LEVEL_BASIC, $confirmed=false)
						$userid = qa_create_new_user($email, $inpassword, $inhandle, QA_USER_LEVEL_BASIC, true);
						qa_set_logged_in_user($userid, $inhandle, true);

						// save customer data: realname, address and birthdate
						booker_set_userfield($userid, 'realname', $newdata[0]['customername']);
						booker_set_userfield($userid, 'address', $newdata[0]['address']);
						booker_set_userfield($userid, 'birthdate', $newdata[0]['birthdate']);

						$params = array(
							'realname' => $newdata[0]['customername'],
							'address' => $newdata[0]['address'],
							'birthdate' => $newdata[0]['birthdate']
						);

						// log registration
						booker_log_userregistration($userid, $params);

						// grant Guthaben in assigning a gutschrift
						if(!booker_userhasgot_creditnote($userid))
						{
							booker_assign_creditnote($userid, $params);
						}
					}
					else
					{
						header('HTTP/1.1 500 Internal Server Error');
						echo 'Es gab ein Problem mit deinen Daten für E-Mail und Passwort.';
						return;
					}
				} // end is_null($userid)
				// logged-in user
				else
				{
					// check if usermeta is given, input fields are displayed fronted if missing
					// in case the customer data is set, save it to db
					if(isset($newdata[0]['customername']))
					{
						booker_set_userfield($userid, 'realname', $newdata[0]['customername']);
					}
					if(isset($newdata[0]['address']))
					{
						booker_set_userfield($userid, 'address', $newdata[0]['address']);
					}
					if(isset($newdata[0]['birthdate']))
					{
						booker_set_userfield($userid, 'birthdate', $newdata[0]['birthdate']);
					}
				}

				// Events: go over event objects and check if times are included
				foreach($newdata as $event)
				{
					if(!(isset($event['start']) && isset($event['contractorid'])))
					{
						header('HTTP/1.1 500 Internal Server Error');
						echo 'Error: Start-End-Data missing';
						return;
					}
				}

				$contractorid = $newdata[0]['contractorid'];
				if(isset($newdata[0]['offerid']))
				{
					$offerid = $newdata[0]['offerid'];
				}

				$isoffer = isset($offerid);

				// everything fine, save events in database with status

				// get hour price of contractor, $contractorid
				$contractorprice = (float)(booker_get_userfield($contractorid, 'bookingprice'));
				$totalprice = 0;

				// get latest global bookid
				$bookid = qa_db_read_one_value(
									qa_db_query_sub('SELECT bookid FROM `^booking_orders`
														ORDER BY bookid DESC
														;'), true );
				if(empty($bookid))
				{
					$bookid = 0;
				}
				$bookid = (int)($bookid)+1;
				$needs = null;

				if($isoffer)
				{
					// insert events into database
					foreach($newdata as $event)
					{
						// start is e.g. "2015-05-30T08:00:00.000Z", we need 2015-05-30 08:00:00
						$starttime = substr($event['start'],0,10).' '.substr($event['start'],11,8);

						// get offer data
						$offerdata = qa_db_read_one_assoc(
										qa_db_query_sub('SELECT created, userid, title, price, duration, end, description, flags, status FROM `^booking_offers`
														WHERE offerid = #
														',
														$offerid));

						$offerprice = $offerdata['price'];
						$contractorid = $offerdata['userid'];
						$offerduration = $offerdata['duration'];
						if(empty($offerduration))
						{
							$endtime = $starttime;
						}
						else
						{
							// calculate endtime by offerdata
							$endtime = date("Y-m-d H:i:s", strtotime($starttime) + $offerduration*60*60);
						}

						// check that time of date is in the future otherwise ignore
						if(strtotime($event['start']) < time())
						{
							continue;
						}

						$commission = booker_getcommission($contractorid);

						qa_db_query_sub('INSERT INTO ^booking_orders (bookid, offerid, created, contractorid, starttime, endtime, customerid, needs, unitprice, commission, payment, status)
														VALUES (#, #, NOW(), #, #, #, #, #, #, #, #, #)',
																$bookid, $offerid, $contractorid, $starttime, $endtime, $userid, $needs, $offerprice, $commission, null, MB_EVENT_OPEN);
						// get last insert id which is eventid
						// LOG
						$eventid = qa_db_last_insert_id();
						$eventname = booker_get_eventname(MB_EVENT_OPEN); // client initiates payment of negative balance
						$params = array(
							'bookid' => $bookid,
							'offerid' => $offerid,
							'contractorid' => $contractorid,
							'starttime' => $starttime,
							'endtime' => $endtime,
							'price' => $offerprice
						);
						booker_log_event($userid, $eventid, $eventname, $params);
					}

					// ajax return success
					$arrayBack = array(
						'offerid' => $offerid,
						'totalprice' => $totalprice,
						'bookid' => $bookid
					);
				}
				// time booked, no offer
				else
				{
					// insert events into database
					foreach($newdata as $event)
					{
						// start is e.g. "2015-05-30T08:00:00.000Z", we need 2015-05-30 08:00:00
						$starttime = substr($event['start'],0,10).' '.substr($event['start'],11,8);
						$endtime = substr($event['end'],0,10).' '.substr($event['end'],11,8);

						$eventduration = strtotime($event['end']) - strtotime($event['start']);
						$eventprice = $contractorprice * $eventduration/60/60;
						$eventprice = $contractorprice * $eventduration/60/60;
						$totalprice += $eventprice;

						// check that time of date is in the future otherwise ignore
						if(strtotime($event['start']) < time())
						{
							continue;
						}

						$commission = booker_getcommission($contractorid);

						qa_db_query_sub('INSERT INTO ^booking_orders (bookid, created, contractorid, starttime, endtime, customerid, needs, unitprice, commission, payment, status)
														VALUES (#, NOW(), #, #, #, #, #, #, #, #, #)',
																$bookid, $contractorid, $starttime, $endtime, $userid, $needs, $contractorprice, $commission, null, MB_EVENT_OPEN);
						// get last insert id which is eventid
						// LOG
						$eventid = qa_db_last_insert_id();
						$eventname = booker_get_eventname(MB_EVENT_OPEN); // client initiates payment of negative balance
						$params = array(
							'bookid' => $bookid,
							'contractorid' => $contractorid,
							'starttime' => $starttime,
							'endtime' => $endtime,
							'needs' => $needs,
							'eventprice' => $eventprice,
							'contractorprice' => $contractorprice
						);
						booker_log_event($userid, $eventid, $eventname, $params);
					}

					// ajax return success
					$arrayBack = array(
						'totalprice' => $totalprice,
						'bookid' => $bookid
					);
				}

				echo json_encode($arrayBack);

				exit();
			} // END AJAX RETURN (bookdata)


			$userid = qa_get_logged_in_userid();
			$contractorid = null;

			// coming by subdomain, see .htaccess (subdomain becomes GET parameter)
			$contractorhandle = qa_get('handle');
			if(!empty($contractorhandle))
			{
				$contractorid = qa_db_read_one_value(
									qa_db_query_sub('SELECT userid FROM `^users`
														WHERE `handle` = #
														', $contractorhandle), true );

				if(empty($contractorid))
				{
					$qa_content = qa_content_prepare();
					qa_set_template('booker booking');
					$qa_content['error'] = 'This site does not exist.';
					return $qa_content;
				}
			}

			if(empty($contractorid))
			{
				$contractorid = qa_get('contractorid');
			}

			$offerid = qa_get('offerid');
			$isoffer = !empty($offerid);

			if(empty($contractorid) && empty($offerid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker booking');
				$qa_content['title'] = qa_lang('booker_lang/bookservice');
				$qa_content['error'] = qa_lang('booker_lang/noservice');
				return $qa_content;
			}

			// if(!booker_iscontracted($contractorid))
			if(MB_SECRETMODE && !qa_is_logged_in())
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker booking');
				$qa_content['title'] = qa_lang('booker_lang/bookservice');
				$qa_content['error'] = qa_lang('booker_lang/notallowed');
				return $qa_content;
			}

			// check if offer status is correct
			$offer_status = booker_get_offerstatus($offerid);
			if($offer_status==0)
			{
				$qa_content['error'] = qa_lang('booker_lang/offer_tobechecked');
			}
			else if($offer_status & MB_OFFER_DELETED)
			{
				$qa_content['error'] = qa_lang('booker_lang/offer_gotdeleted');
				return $qa_content;
			}
			else if( !($offer_status & MB_OFFER_APPROVED) )
			{
				$qa_content['error'] = qa_lang('booker_lang/offer_gotdisapproved');
				return $qa_content;
			}
			else if( !($offer_status & MB_OFFER_ACTIVE) )
			{
				$qa_content['error'] = qa_lang('booker_lang/offer_gotdeactivated');
				return $qa_content;
			}

			if($isoffer)
			{
				// get contractorid from $offerid
				$contractorid = qa_db_read_one_value(qa_db_query_sub('SELECT userid FROM ^booking_offers
																WHERE offerid = #',
																$offerid), true);
			}

			$isavailable = contractorisavailable($contractorid);

			/* start */
			$qa_content = qa_content_prepare();
			$addtemplate = $isoffer ? ' isoffer' : '';
			qa_set_template('booker booking'.$addtemplate);

			$hide_booktablewrap = false;
			if(!contractorisapproved($contractorid) || !booker_iscontracted($contractorid) || !$isavailable)
			{
				$qa_content['title'] = qa_lang('booker_lang/bookservice');
				$qa_content['error'] = qa_lang('booker_lang/con_notavailable');
				// do not show contractor to user if not logged-in
				if(!isset($userid))
				{
					return $qa_content;
				}
				$hide_booktablewrap = true;
			}

			// hide booking options if user sees his own profile
			if($contractorid == $userid)
			{
				$hide_booktablewrap = true;
			}

			$isadmin = qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN;


			// init
			$qa_content['custom'] = '';

			// if not registered
			if(empty($userid))
			{
				$qa_content['title'] = qa_lang('booker_lang/bookservice');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needreglogin_service'));
				$qa_content['custom'] = '
				<div class="qa-error qa-notify qa-notify-fullwidth">
					<i class="fa fa-exclamation-triangle"></i>
					'.qa_insert_login_links(qa_lang('booker_lang/needreglogin_service')).'
				</div>
				';
			}

			// get handle and avatarblobid of contractor
			$condata = qa_db_read_one_assoc(qa_db_query_sub('SELECT handle,avatarblobid FROM ^users
																WHERE userid = #',
																$contractorid), true);
			if(empty($condata))
			{
				// ERROR
				$qa_content['title'] = qa_lang('booker_lang/bookservice');
				$qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/contractor_notexist'));
				return $qa_content;
			}

			$imgsize = 250;
			if(isset($condata['avatarblobid']))
			{
				$avatar = './?qa=image&qa_blobid='.$condata['avatarblobid'].'&qa_size='.$imgsize;
			}
			else
			{
				$avatar = './?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size='.$imgsize;
			}
			// points to forum
			$userprofilelink = qa_path('user').'/'.$condata['handle'];
			$contractorname = booker_get_realname($contractorid);

			$qa_content['title'] = qa_lang('booker_lang/order_imp').' '.$contractorname; // as page title


			$contractorprice = (float)(booker_get_userfield($contractorid, 'bookingprice'));

			// $service = booker_get_userfield($contractorid, 'service');

			// no blue booking times for offers - could happen that a fixed hour offer does not fit into a fixed time
			$weekevents = '';
			$earliesttime = 0;
			if(!$isoffer)
			{
				// favorite times of contractor
				$weekschedule = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT weekday, starttime, endtime FROM `^booking_week`
															WHERE `userid` = #
															ORDER BY weekday
															;', $contractorid) );

				// get week schedule
				$weekdays = helper_get_weekdayname_array();

				foreach($weekschedule as $times)
				{
					// sunday should become 7
					$wkday = $times['weekday'];
					// 01 september is monday, so we can insert the weekday as day number
					// see also http://fullcalendar.io/docs/event_ui/eventConstraint/ for other implementation
					$weekevents .= "
					{
						title: '',
						start: '".$times['starttime']."',
						end: '".$times['endtime']."',
						dow: [".$wkday."], // repeat same weekday
						rendering: 'background',
						// color: '#0A0'
						color: '#6BA5C2'
					},

					";

					// compare earliest hours
					if(empty($earliesttime))
					{
						$earliesttime = (int)(substr($times['starttime'],0,2));
					}
					else if((int)(substr($times['starttime'],0,2))<$earliesttime)
					{
						$earliesttime = (int)(substr($times['starttime'],0,2));
					}
				}
			}

			// $bookedx stores all events for js full calendar
			$bookedx = '';

			$contractorabsent = booker_get_userfield($contractorid, 'absent');

			// semikolon
			$contractorabsenttimes = explode(';', $contractorabsent);
			// comma
			if(strpos($contractorabsent, ',')!==false)
			{
				$contractorabsenttimes = explode(',', $contractorabsent);
			}

			$momentformat = qa_lang('booker_lang/date_format_momentjs');

			foreach($contractorabsenttimes as $time)
			{
				if(empty($time))
				{
					continue;
				}
				// single day or period, check by - or length
				else if(strlen($time)>10)
				{
					// time period, make sure we have a minus -
					if(strpos($time, '-') !== false)
					{
						$ta = explode('-', $time);
						$absentstart = $ta[0];
						$absentend = $ta[1];
						$bookedx .= "
						{
							title: '".qa_lang('booker_lang/absent')."',
							start: moment('".$absentstart."', '".$momentformat."').format('YYYY-MM-DDT00:00:00'),
							end: moment('".$absentend."', '".$momentformat."').format('YYYY-MM-DDT24:00:00'),
							editable: false,
							color: 'rgba(50,50,50,0.5)',
							borderColor: '#AAA',
							className: 'event-notavailable',
						},

						";
					}
				}
				else if(strlen($time)==1)
				{
					// $time is weekday number, block weekday in week for absent
					$weekdaynr = (int)($time);
					if($weekdaynr==7)
					{
						$weekdaynr = 0;
					}

					$bookedx .= "
					{
						title: '".qa_lang('booker_lang/notavailable')."',
						start: '00:00',
						end: '24:00',
						editable: false,
						ranges: [{
							start: moment().startOf('week'),
							end: moment().endOf('week').add(7,'d'),
						}],
						dow: [".$weekdaynr."], // repeat same weekday
						color: 'rgba(50,50,50,0.5)',
						borderColor: '#AAA',
						className: 'event-notavailable',
					},

					";
				}
				else
				{
					// single day
					$bookedx .= "
					{
						title: '".qa_lang('booker_lang/absent')."',
						start: moment('".$time."', '".$momentformat."').format('YYYY-MM-DDT00:00:00'),
						end: moment('".$time."', '".$momentformat."').format('YYYY-MM-DDT24:00:00'),
						editable: false,
						color: 'rgba(50,50,50,0.5)',
						borderColor: '#AAA',
						className: 'event-notavailable',
					},

					";
				}
			}

			// do not show pseudobookings for contractor himself
			/*
			if($userid != $contractorid)
			{
				// create 2 pseudo events for each week for each user (remember, nobody eats in an empty restaurant)
				$thisweek = date("W"); // week number
				$weekcount = $thisweek+10; // 6 weeks in the future
				for($weekid=$thisweek; $weekid<$weekcount; $weekid++)
				{
					$pseudotimes = helper_get_pseudobookings($weekid, $contractorid); // [0] first event, [1] second event (both with starttime+endtime)

					for($i=0;$i<count($pseudotimes);$i++)
					{
						$bookedx .= "
						{
							title: '·".qa_lang('booker_lang/reserved')."·',
							start: '".$pseudotimes[$i]['start']."',
							end: '".$pseudotimes[$i]['end']."',
							editable: false,
							color: '#F77'
						},

						";
						// earliest time to show in calendar
						if((int)(substr($pseudotimes[$i]['start'],11,2))<$earliesttime)
						{
							$earliesttime = (int)(substr($pseudotimes[0]['start'],11,2));
						}
					}
				}
			}
			*/

			// show only today and future events of contractor
			$bookedevents = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT eventid, bookid, offerid, created, starttime, endtime, customerid, unitprice, status FROM `^booking_orders`
														WHERE `contractorid` = #
														AND status != #
														AND starttime >= CURDATE()
														ORDER BY starttime
														;', $contractorid, MB_EVENT_OPEN)
												);
														// AND created >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
														// AND created >= DATE(NOW()) - INTERVAL 7 DAY

			foreach($bookedevents as $event)
			{
				// customer handles in calendar
				// $eventcustomer = booker_get_realname($event['customerid']);

				// if(time()-$event['created']) {
				// open
				$eventcolor = '#777';
				$eventtitle = '';
				if($event['status']==MB_EVENT_OPEN)
				{
					// open, excluded by mysql
					// $eventtitle = 'eventuell vergeben';
					// $eventcolor = '#777';
				}
				// reserved (payment will be processed)
				else if($event['status']==MB_EVENT_RESERVED)
				{
					// $eventtitle = 'reserviert\nvon '.$eventcustomer;
					$eventtitle = qa_lang('booker_lang/already_reserved');
					$eventcolor = '#F77';
				}
				// paid
				// else if($event['status']==MB_EVENT_PAID)
				else if(booker_event_is_paid($event['eventid']))
				{
					// $eventtitle = 'gebucht\nvon '.$eventcustomer;
					$eventtitle = qa_lang('booker_lang/booked');
					$eventcolor = '#89C';
				}
				// completed
				else if($event['status']==MB_EVENT_COMPLETED)
				{
					$eventtitle = qa_lang('booker_lang/completed');
					$eventcolor = '#89C';
				}


				// js full calendar
				$bookedx .= "
				{
					title: '".$eventtitle."',
					start: '".$event['starttime']."',
					end: '".$event['endtime']."',
					editable: false,
					color: '".$eventcolor."'
				},

				";
				// rendering background: // http://fullcalendar.io/docs/event_rendering/Background_Events/

				// compare earliest hours
				if(empty($earliesttime))
				{
					$earliesttime = (int)(substr($event['starttime'],11,2));
				}
				else if((int)(substr($event['starttime'],11,2))<$earliesttime)
				{
					$earliesttime = (int)(substr($event['starttime'],11,2));
				}
			} // end foreach($bookedevents)


			$offerdurationset = null;

			// check if the client has events already and display them in the calendar, prevents overlapping events
			if(isset($userid))
			{
				$bookedevents_cal = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, unitprice, status FROM `^booking_orders`
															WHERE customerid = #
															AND status > #
															AND starttime >= CURDATE()
															ORDER BY starttime
															;', $userid, MB_EVENT_OPEN)
								);

				$eventtitle = '';

				foreach($bookedevents_cal as $event)
				{
					// calculate from starttime and endtime (status == reserviert)
					$eventduration = booker_get_timediff($event['starttime'], $event['endtime']);

					// customer handles in calendar
					$ev_contractorhandle = booker_get_realname($event['contractorid']);

					$classname = '';
					$clicklink = '';

					// open, excluded by mysql
					/*if($event['status']==MB_EVENT_OPEN)
					{
						// will not be displayed
						$eventtitle = 'eventuell';
						$eventcolor = '#777';
					}
					*/
					// reserved (payment will be processed)
					if($event['status']==MB_EVENT_RESERVED)
					{
						$eventstatus = qa_lang('booker_lang/reserved');
						// $eventtitle = str_replace('~~~', $ev_contractorhandle, qa_lang('booker_lang/notpaid_click'));
						$eventtitle = str_replace('~~~', $ev_contractorhandle, qa_lang('booker_lang/appts_reserved'));
						$eventcolor = '#28F'; // #448
						$classname = 'orderreserved';
						// $clicklink = 'url: "'.qa_path('pay').'?bookid='.$event['bookid'].'", ';
					}
					// paid
					// else if($event['status']==MB_EVENT_PAID)
					else if(booker_event_is_paid($event['eventid']))
					{
						$eventstatus = qa_lang('booker_lang/paid');
						$eventtitle = qa_lang('booker_lang/yourapptwith').' '.$ev_contractorhandle;
						$eventcolor = '#667';
						$classname = 'orderpaid';
						$clicklink = 'url: "'.qa_path('clientschedule').'", ';
					}
					/*
					// completed, should not show up, is past
					else if($event['status']==MB_EVENT_COMPLETED)
					{
						$eventstatus = 'completed';
						$eventtitle = 'completed';
						$eventcolor = '#89C';
						$classname = 'orderdone';
					}*/

					// js full calendar
					$bookedx .= "
					{
						title: '".$eventtitle."',
						start: '".$event['starttime']."',
						end: '".$event['endtime']."',
						editable: false,
						color: '".$eventcolor."',
						className: '".$classname."',
						".$clicklink."
					},

					";
				} // end foreach $bookedevents
			}

			// Example data *** booking page: Kai Noack
			if($contractorid==9)
			{
				/*
				Colors:
				777 // default
				F77 // reserved
				89C // paid
				89C // completed
				28F // orderreserved
				667 // orderpaid
				*/
				$bookedx .= "
				{
					title: '".qa_lang('booker_lang/completed')."',
					start: '2016-10-12 13:00',
					end: '2016-10-12 13:30',
					editable: false,
					color: '#777',
					className: '',
				},
				{
					title: '".qa_lang('booker_lang/reserved')."',
					start: '2016-10-13 19:00',
					end: '2016-10-13 20:30',
					editable: false,
					color: '#89C',
					className: 'orderpaid',
				},
				{
					title: '".qa_lang('booker_lang/paid')."',
					start: '2016-10-14 13:00',
					end: '2016-10-14 14:00',
					editable: false,
					color: '#667',
					className: 'orderreserved',
				},
				";

				/*
				// single day
				$bookedx .= "
				{
					title: '".qa_lang('booker_lang/absent')."',
					start: moment('".$time."', '".$momentformat."').format('YYYY-MM-DDT00:00:00'),
					end: moment('".$time."', '".$momentformat."').format('YYYY-MM-DDT24:00:00'),
					editable: false,
					color: 'rgba(50,50,50,0.5)',
					borderColor: '#AAA',
					className: 'event-notavailable',
				},

				";
				*/
			}

			// output profile box on right
			$contractorhandle = helper_gethandle($contractorid);

			// RECOMMEND USER
			// important for booker-layer-userforumprofile.php
			$qa_content['raw']['booking']['realname'] = $contractorname;

			if(isset($userid) && ($contractorid != $userid))
			{
				$favoritemap = qa_get_favorite_non_qs_map();
				$favorite = @$favoritemap['user'][$contractorid];
				$qa_content['favorite'] = qa_favorite_form(QA_ENTITY_USER, $contractorid, $favorite,
											qa_lang_sub($favorite ? 'main/remove_x_favorites' : 'users/add_user_x_favorites', $contractorname));

			}

			$followerstub = '';
			$userfollowers = helper_get_all_followers($contractorid);
			$recommendation_count = count($userfollowers);
			if($recommendation_count>0)
			{
				$followerlist = '';
				foreach($userfollowers as $follower)
				{
					if(empty($followerlist))
					{
						$followerlist .= booker_get_realname($follower['userid']);
					}
					else
					{
						$followerlist .= ', '.booker_get_realname($follower['userid']);
					}
				}
				$followerstub = '
				<p class="followerstub">
					<!-- <i class="fa fa-thumbs-up fa-2x followerstubicon"></i> -->
					<a class="followerstublink tooltipE" href="'.qa_path('user').'/'.$contractorhandle.'#fans" title="'.str_replace('~username~', $contractorname, qa_lang('booker_lang/recommendedby')).': '.$followerlist.'">
						'.$recommendation_count.' '.($recommendation_count==1 ? qa_lang('booker_lang/recommendation') : qa_lang('booker_lang/recommendations')).'
					</a>
				</p>
				';
			}

			$viewerispremium = booker_ispremium($userid);

			$conispremium = booker_ispremium($contractorid);
			$vcardlink = '';
			if($conispremium)
			{
					$vcardlink = '
						<a class="vcardlink tooltip btn btn-small" href="'.qa_path('vcard').'?userid='.$contractorid.'">
						<i class="fa fa-phone fa-lg"></i>
						<span class="vcardspan">'.qa_lang('booker_lang/vcardlink').'</span>
						</a>
					';
			}

			$sharelink = q2apro_site_url().'booking?contractorid='.$contractorid;
			$shareoptions = '
				<div class="shareoptions">
					<a class="fbsharelink btn btn-small" data-shareurl="'.$sharelink.'">
						<i class="fa fa-facebook-square fa-lg"></i>
						<span class="fbsharespan">'.qa_lang('booker_lang/share').'</span>
					</a>
					<a class="forumprofilelink tooltip btn btn-small" title="'.qa_lang('booker_lang/forum_profile').'" target="_blank" href="'.qa_path('user').'/'.$contractorhandle.'">
						<i class="fa fa-external-link-square fa-lg"></i>
						<span class="forumlinkspan">'.qa_lang('booker_lang/forum_short').'</span>
					</a>
					'.$vcardlink.'
				</div> <!-- shareoptions -->
			';

			$shorturl = q2apro_site_url().'u/'.$contractorid;
			$bookpageshortlink = '
				<a title="'.qa_lang('booker_lang/shortlink_profile').':<br />'.str_replace('https://www.','',$shorturl).'" href="'.$shorturl.'" class="bookershortlink tooltip">
					<i class="fa fa-link"></i>
				</a>
			';

			$adminlink = '';
			if($isadmin)
			{
				// link to userprofile
				$adminlink = '
					<a class="defaultbutton btn_graylight buttonthin" id="adminbtn" href="'.qa_path('userprofile').'?userid='.$contractorid.'">'.qa_lang('booker_lang/profile').'</a>
				';
			}

/***
			<p class="contractorrealname">
				<a class="conbooklink" href="'.qa_path('booking').'?contractorid='.$contractorid.'">'.$contractorname.'</a>
			</p>

			'.$shareoptions.'
			'.$bookpageshortlink.'
			'.$forumprofilelink.'

			'.$adminlink.'

			'.$followerstub.'
***/

			// insert google adsense for non-premium members
			$ads = '';

			$conoffers = '';
			$conoffer_single = '';
			$service = '';
			$portfolio = '';
			$offerduration = 0;
			$offerdurationshow = '';
			$offerprice = 0;
			$offerendshow = '';
			$flagsout = '';

			if(!$isoffer)
			{
				$service = booker_get_userfield($contractorid, 'service');
				$portfolio = booker_get_userfield($contractorid, 'portfolio');
				
				// parse youtube links 
				$portfolio = helper_youtube_links_to_embeds($portfolio);
				
				// if not premium, remove links, images, videos from portfolio
				// but show data if viewer is premium!
				if(!$conispremium && !$viewerispremium)
				{
					/*
					// only text, allow p and br
					$portfolio = strip_tags($portfolio, '<br><p>');
					
					// remove URLs
					$regex = "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?).*$)@";
					$portfolio = preg_replace($regex, ' ', $portfolio);
					
					$portfolio = helper_strip_urls($portfolio);
					
					// remove emails and phones
					$portfolio = helper_remove_email_phone($portfolio);
					*/

					$ads = '
					<div class="adholder-mid" style="margin:10px 0 40px 0;">
						<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
						<!-- kvanto 728x90 Middle -->
						<ins class="adsbygoogle"
							 style="display:inline-block;width:728px;height:90px"
							 data-ad-client="ca-pub-6679343814337183"
							 data-ad-slot="5575579959"></ins>
						<script>
						(adsbygoogle = window.adsbygoogle || []).push({});
						</script>
					</div>
					';
				}

				// get special offers of contractor
				$existingoffers = booker_get_offers($contractorid, true);

				if(count($existingoffers)>0)
				{
					$conoffers = '
					<div class="offerwrap">
						<h2 class="specialoffershead">
							'.qa_lang('booker_lang/specialoffers').':
						</h2>
					';
					$conoffers .= '<ul class="specialoffers">';

					foreach($existingoffers as $offer)
					{
						$conending = date(qa_lang('booker_lang/date_format_php'), strtotime($offer['end']));
						$description = strip_tags($offer['description']);
						$description = preg_replace('!\s+!', ' ', $description);

						$conoffers .= '
							<li class="specialoffer contractorBox">
								<a class="" href="'.qa_path('booking').'?offerid='.$offer['offerid'].'" title="'.$description.'">
									<span class="contractor-content">
										<span class="offertitel contractorname">'.$offer['title'].'</span>
										<span class="contractorshortdesc">'.$description.'</span>
										<span class="offerprice btn">'.qa_lang('booker_lang/only').' '.helper_format_currency($offer['price'], 2).' '.qa_opt('booker_currency').'</span>
									</span>
								</a>
								<br />
								<span class="offerending" style="display:none;">'.$conending.'</span>
							</li>
						';
					}
					$conoffers .= '
							</ul> <!-- specialoffers  -->
							<div style="clear:both;"></div>
					</div> <!-- offerwrap -->
					';
				}
			} // END !$isoffer
			else
			{
				// get single offer data
				$offerdata = qa_db_read_one_assoc(
								qa_db_query_sub('SELECT offerid, created, userid, title, price, duration, end, description, flags, status FROM `^booking_offers`
												WHERE offerid = #
												',
												$offerid));

				$contractorid = $offerdata['userid'];
				$service = $offerdata['title']; // offertitle
				$offerprice = $offerdata['price'];
				$offerduration = $offerdata['duration'];
				$offerend = $offerdata['end'];
				$portfolio = $offerdata['description']; // offer description

				$qa_content['title'] = $service.' - '.$contractorname; // as page title

				if(is_null($offerduration))
				{
					$offerdurationset = false;
					$offerduration = 24; // default, needed for js
					$offerdurationshow = ''; // qa_lang('booker_lang/notspecified');
				}
				else if($offerduration>0)
				{
					$offerdurationset = true;
					$offerdurationshow = qa_lang('booker_lang/executiontime').' '.qa_lang('booker_lang/approx').' '.$offerduration.' '.qa_lang('booker_lang/hourabbr');
				}

				$offerendshow = '';
				if(strtotime($offerend)>time())
				{
					$offerendshow = qa_lang('booker_lang/offer_validuntil').' '.helper_get_readable_date_from_time($offerend, false);
				}

				$flags = $offerdata['flags'];
				$flagsout = '';
				if($flags&MB_SERVICELOCAL)
				{
					$flagsout .= '- '.qa_lang('booker_lang/servicelocal').'<br />';
				}
				if($flags&MB_SERVICEONLINE)
				{
					$flagsout .= '- '.qa_lang('booker_lang/serviceonline').'<br />';
				}
				if($flags&MB_SERVICEATCUSTOMER)
				{
					$flagsout .= '- '.qa_lang('booker_lang/serviceatcustomer').'<br />';
				}

				$flagsout = '
						<div class="contractorexecution">
							<p>
								'.qa_lang('booker_lang/execution').':
								<br />
								'.$flagsout.'
							</p>
						</div>
				';

				$conoffer_single = '
						<div class="pricewrap">
							<button type="submit" class="defaultbutton revealcalendar">
								'.qa_lang('booker_lang/bookofferfor').'
								<br />
								'.qa_lang('booker_lang/only').'
								&nbsp;<span class="offerprice">'.helper_format_currency($offerprice).' '.qa_opt('booker_currency').'</span>
							</button>
						</div> <!-- pricewrap -->
				';
			} // END isoffer

			$location = booker_get_userfield($contractorid, 'address');
			$locationstring = '';

			$service_onlyonline = booker_service_onlyonline($contractorid);
			if($service_onlyonline)
			{
				$locationstring = qa_lang('booker_lang/onlineonly');
			}
			else
			{
				if(empty($location))
				{
					$locationstring = qa_lang('booker_lang/nolocationset');
				}
				else
				{
					$locationstring = '
						<i class="fa fa-map-marker fa-lg"></i>
						<a class="tooltipW locationlink" target="_blank" href="https://maps.google.com/?q='.urlencode($location).'" title="'.qa_lang('booker_lang/admin_open_gmaps').'">
							'.$location.'
						</a>
					';

					// make sure user is not looking onto his own profile
					if($userid != $contractorid && isset($userid))
					{
						$clientlocation = booker_get_userfield($userid, 'address');
						if(!empty($clientlocation))
						{
							$locationstring .= '
								<div id="dvDistance">
									'.qa_lang('booker_lang/distance').': <span id="distance"></span>
									'.qa_lang('booker_lang/time').': <span id="duration" title="'.qa_lang('booker_lang/timebycar').'"></span>
									<!--
									<span class="showfullmap defaultbutton buttonthin btn_graylight">
										<i class="fa fa-map-marker" style="margin-right:3px;"></i>
										'.qa_lang('booker_lang/showmap').'
									</span>
									-->
								</div>
							';
							$locationstring .= loadlocationservice($clientlocation, $location);
						}
					}
				}
			} // end service not online

			$headline = '
						<h1 class="contractorname">
							<a href="'.qa_path('booking').'?contractorid='.$contractorid.'">
								'.$contractorname.'
							</a>
						</h1>
						<h2 class="contractorservice">
							<a href="/?s='.$service.'" target="_blank">
								'.$service.'
								<span class="fa fa-search"></span>
							</a>
						</h2>
			';
			if($isoffer)
			{
				// we put the offer at first and then the name
				$headline = '
						<p class="serviceheadline">
							'.$service.' -
							<a href="'.qa_path('booking').'?contractorid='.$contractorid.'" class="contractorservice">'.$contractorname.'</a>
						</p>
				';
			}


			$premium_badge = '';
			if(booker_ispremium($contractorid))
			{
				$premium_badge = '
					<div class="premium-badge"><span class="icon-badge tooltip" title="'.qa_lang('booker_lang/member_premium').'"></span></div>
				';
			}

			// default is no price set 
			$contractorprice_out = qa_lang('booker_lang/nopriceset');
			if(!empty($contractorprice))
			{
				$contractorprice_out = '
					<span>
						'.qa_lang('booker_lang/book_service_btn').'
					</span>
					<span class="contractorprice" data-original-price="'.$contractorprice.'">
						'.number_format($contractorprice,0,',','.').' '.qa_opt('booker_currency').'/'.qa_lang('booker_lang/hourabbr').'
					</span>
				';
			}
			
			$qa_content['custom'] .= '
				<div class="entireprofile clearfix">
					'.$premium_badge.'
					<div class="profilebox">
						<div class="profilewrap">
							<div class="q2apro_hs_avatar">
								<!-- <img class="profile-avatarimg" src="'.$avatar.'" alt="'.$contractorname.'" /> -->
								<div class="profile-avatarimg" style="background-image:url('.$avatar.')"></div>
							</div>

							<a class="bookingbutton defaultbutton">
								'.$contractorprice_out.'
							</a>

							'.$followerstub.'

						</div> <!-- profilewrap -->
					</div> <!-- profilebox -->
					';
					// <i class="fa fa-check-square-o fa-lg"></i> </a>
					// <i class="fa fa-linkedin-square fa-lg"></i> </a>

			$qa_content['custom'] .= '
				'.$adminlink.'
				<div class="portfoliowrap">
					'.$headline.'
					<div class="locationbox">
						'.$locationstring.'
					</div>
					<div class="portfoliobox">

						'.$portfolio.'

						'.$flagsout.'

						<div class="offerduration">
							'.$offerdurationshow.'
						</div>

						<div class="offerend">
							'.$offerendshow.'
						</div>

					</div> <!-- portfoliobox -->

					'.$conoffer_single.'

					'.$shareoptions.'

				</div> <!-- portfoliowrap -->

			</div> <!-- entireprofile -->

			';

			$qa_content['custom'] .= $conoffers;

			/*
			// *** rating output disabled for now
			if(q2apro_ratings_count($contractorid)>0)
			{
				$qa_content['custom'] .= '
				<div class="ratingswrap">
					<h3>
						'.qa_lang('booker_lang/customerratings').'
					</h3>
					'.q2apro_booker_list_ratings(5, $contractorid).'
				</div> <!-- ratingswrap -->
				';
			}
			*/

			// show google adsense if non-premium
			$qa_content['custom'] .= $ads;

			$qa_content['custom'] .= '
			<div class="bcalendar-wrap">
				<div class="calselhelpwrap" id="cal">
					<h2>
						'.($isoffer ?
								($offerdurationset ?
									qa_lang('booker_lang/choosedaytime') :
									qa_lang('booker_lang/chooseday')
								) :
								qa_lang('booker_lang/bookservice'))
						.'
					</h2>
					<p class="calchoosetime">
						'. qa_lang('booker_lang/preferedtimes_short') .' &nbsp;
						<span id="calselecthelp">
							<i class="fa fa-info-circle fa-lg"></i>
							'.qa_lang('booker_lang/howtimesbymouse').'
						</span>
					</p>
					<div class="selectionpreview">
						<div class="selectionpreivew_imghold">
							<img style="border:1px solid #CCC;" src="'.$this->urltoroot.'images/calendar-selection-customer.gif" alt="preview time choice" />
						</div>
					</div>

				</div> <!-- calselhelpwrap -->

				<div style="clear:both;"></div>

				<div id="calendar"></div>

				<div class="bookingtablewrap">
					<table id="bookingtable">
						<thead>
							<tr>
								<th>'.qa_lang('booker_lang/date').'</th>
								<th>'.qa_lang('booker_lang/time').'</th>
								<th>'.qa_lang('booker_lang/timespan').'</th>
								<th>'.qa_lang('booker_lang/price').'</th>
								<th style="display:none;"></th>
							</tr>
						</thead>

					<tbody>
						<tr id="event0" class="bookmehold">
							<td class="booktime">&nbsp;</td>
							<td class="booktimehours">&nbsp;</td>
							<td class="booktimeabs">&nbsp;</td>
							<td class="bookcalc">&nbsp;</td>
							<td class="bookdel">
								<a class="deletebookevent" title="'.qa_lang('booker_lang/delete').'">
									<i class="fa fa-remove"></i>
								</a>
							</td>
						</tr>
						<tr id="sumrow">
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td style="background:#FFC;">'.qa_lang('booker_lang/sum').':</td>
							<td id="booksum" style="background:#FFC;">&nbsp;</td>
							<td style="display:none;"></td>
						</tr>
					</tbody>
				</table>
			</div> <!-- bcalendar-wrap -->
			';

			// *** only output for NEW customer, if existing customer skip (who already did a booking and entered the data)
			$clientrealname = booker_get_realname($userid);
			$address = booker_get_userfield($userid, 'address');
			$birthdate = booker_get_userfield($userid, 'birthdate');

			$qa_content['custom'] .= '
			<div class="condata_wrap">
			';

			if(empty($clientrealname))
			{
				$qa_content['custom'] .= '
				<div class="needsholder">
					<h3 style="margin-top:20px;">
						'.ucfirst(qa_lang('booker_lang/name')).'
					</h3>
					<p>
						'.ucfirst(qa_lang('booker_lang/specify_fullname')).':
					</p>
					<input type="text" value="'.$clientrealname.'" placeholder="'.qa_lang('booker_lang/firstlastname').'" name="customername" id="customername" />
				</div> <!-- needsholder -->
				';
			}

			if(empty($address))
			{
				$qa_content['custom'] .= '
				<div class="needsholder">
					<h3 style="margin-top:20px;">
						'.ucfirst(qa_lang('booker_lang/address')).'
					</h3>
					<p>
						'.qa_lang('booker_lang/specify_address').':
					</p>
					<p>
						<input type="text" value="'.$address.'" placeholder="'.qa_lang('booker_lang/placeholder_addressexample').'" name="address" id="address" />
					</p>
				</div>
				';
			}
			if(empty($birthdate))
			{
				$qa_content['custom'] .= '
				<div class="needsholder">
					<h3 style="margin-top:20px;">
						'.ucfirst(qa_lang('booker_lang/birthdate')).'
					</h3>
					<p>
						'.qa_lang('booker_lang/specify_birthdate_page').':
					</p>
					<p>
						<input type="text" value="'.$birthdate.'" placeholder="'.qa_lang('booker_lang/placeholder_dateexample').'" name="birthdate" id="birthdate" />
					</p>
				</div>
				';
			}

			if(empty($userid))
			{
				// guest, provide register fields
				$qa_content['custom'] .= '
				<div class="loginholder">
					<h3 style="margin-top:50px;">
						'.qa_lang('booker_lang/createlogin').'
					</h3>
					<p style="display:inline-block;margin-right:20px;">
						'.qa_lang('booker_lang/email').':
						<br />
						<input type="text" value="" placeholder="'.qa_lang('booker_lang/email').'" name="email" id="email" />
					</p>
					<p style="display:inline-block;">
						'.qa_lang('booker_lang/password').':
						<br />
						<input type="password" value="" placeholder="'.qa_lang('booker_lang/password').'" name="password" id="password" />
					</p>
				</div>
				';
			}
			$qa_content['custom'] .= '
			</div> <!-- condata_wrap -->
			';

			if($isavailable && !$hide_booktablewrap)
			{
				// registered contains time of terms acceptance
				if(empty(booker_get_userfield($userid, 'registered')))
				{
					$qa_content['custom'] .= '
						<p id="agbholder" style="margin:30px 0 0 0;">
							<label for="agbcheck">
								<input type="checkbox" name="agbcheck" id="agbcheck">
								'.
								strtr( qa_lang('booker_lang/acceptterms'), array(
									'^1' => '<a target="_blank" href="'.qa_path('terms').'">',
									'^2' => '</a>'
								)).
								'
							</label>
						</p>
					';
				}
				$buttonlabel = qa_lang('booker_lang/btn_bookandpay');
				if($isoffer)
				{
					$buttonlabel = qa_lang('booker_lang/btn_bookoffer');
				}
				$qa_content['custom'] .= '
					<div class="bookmeholder">
						<button id="bookme" class="defaultbutton" type="submit">'.$buttonlabel.'</button>
						<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
					</div>
				';
			}

			$qa_content['custom'] .= '</div> <!-- bookingtablewrap -->';

			if($earliesttime>13)
			{
				$earliesttime = 13;
			}
			if($earliesttime<1)
			{
				$earliesttime = 1;
			}

			// todays weekday
			// $firstdaytoshow = date('N', time() - 60*60*24);
			$firstdaytoshow = date('N', time());

			// $weekstartday = date("Y-m-d", strtotime('monday this week')); // problem with php5.2
			$weekstartday = date('Y-m-d', strtotime(date('o-\\WW')));

			// jquery additions
			$mailpwchecks = '';
			$mailpwdata = '';
			if(empty($userid))
			{
				$mailpwchecks = "
					// check if birthdate is set
					if($('#email').val()=='') {
						alert('".qa_lang('booker_lang/specify_email')."');
						$('#email').focus();
						return;
					}
					var email = $('#email').val();
					if(!isEmail(email))
					{
						alert('".qa_lang('booker_lang/specify_validemail')."');
						$('#email').focus();
						return;
					}

					// check if birthdate is set
					if($('#password').val()=='') {
						alert('".qa_lang('booker_lang/specify_password')."');
						$('#password').focus();
						return;
					}
					var password = $('#password').val();
				";

				$mailpwdata = "
					'email': email,
					'password': password,
				";
			}

			$offereventsettings = '';
			if($isoffer)
			{
				$offereventsettings = "
					defaultTimedEventDuration: (fixedduration+':00:00'), // hours:min:sec ***
					forceEventDuration: true,
					// defaultEventMinutes: fixedduration*60,
					durationEditable: false,
				";
			}

			$calendarview = 'agendaWeek';
			$isoffermonthview = false;

			if($offerdurationset===false)
			{
				$calendarview = 'month';
				$isoffermonthview = true;
			}

			$eventsource = '
				events: [
					'.$weekevents.'
					'.$bookedx.'
				],
			';

			// does contractor have google calendar set up
			$gcalurl_contractor = booker_get_userfield($contractorid, 'externalcal');
			$gcalsource = '';
			if(!empty($gcalurl_contractor))
			{
				// http://fullcalendar.io/docs/event_data/eventSources/
				// google calendar events are not displayed in calendar if month view and background-rendering
				$gcalsource .= "
						{
							googleCalendarId: '".$gcalurl_contractor."',
							".($isoffermonthview ? '' : 'rendering: \'background\', ')."
							title: '".qa_lang('booker_lang/notavailable')."', // not appearing
							editable: false,
							color: 'rgba(50,50,50,0.5)',
							borderColor: '#AAA',
							className: 'gcal-event',
						},
				";
			}
			// does visitor user have google calendar set up
			$gcalurl_user = booker_get_userfield($userid, 'externalcal');
			if(!empty($gcalurl_user) && $userid != $contractorid)
			{
				// google calendar events are not displayed in calendar if month view and background-rendering
				$gcalsource .= "
						{
							googleCalendarId: '".$gcalurl_user."',
							".($isoffermonthview ? '' : 'rendering: \'background\', ')."
							title: '".qa_lang('booker_lang/notavailable')."', // not appearing
							editable: false,
							color: 'rgba(50, 150, 50, 0.5)',
							borderColor: 'rgb(170, 170, 170)',
							className: 'gcal-event',
						},
				";
			}

			// we have google calendar data
			if(!empty($gcalsource))
			{
				// http://fullcalendar.io/docs/event_data/eventSources/
				// google calendar events are not displayed in calendar if month view and background-rendering
				$eventsource = "
					googleCalendarApiKey: '".qa_opt('booker_gmapsapikey')."',
					eventSources: [
						"
						.$gcalsource.
						"
						{
							events: [
								".$weekevents."
								".$bookedx."
							],
						}
					],
				";
			}
			else
			{

			}

			$ismobile = qa_is_mobile_probably();

			$offerjquery = '';
			if($isoffer)
			{
				$offerjquery = "
					// change position of shareoptions
					// var shareop = $('.shareoptions').detach();
					// $(shareop).appendTo('.profilewrap');
				";
			}
			// jquery
			$qa_content['custom'] .= "
<script>

	$(document).ready(function()
	{
		// hide button 'service buchen'
		$('.btn-info').parent().hide();

		$('#calselecthelp').click(function()
		{
			$('.selectionpreview').toggle();
		});

		$('.bookingbutton, .revealcalendar').click(function()
		{
			// $('.calselhelpwrap, #calendar, .bookingtablewrap').show();
			// scroll down
			$('html,body').animate({
			   scrollTop: $('.calselhelpwrap').offset().top
			});
		});

		// hide nav bar when scrolling down
		$(window).scroll(function (event) {
			var scroll = $(window).scrollTop();
			if(scroll>100)
			{
				$('.navbar-fixed-top').hide();
			}
			else {
				$('.navbar-fixed-top').show();
			}
		});

		var isoffer = ".($isoffer ? 'true' : 'false').";
		var fixedduration = ".($isoffer ? $offerduration : '24')."; // hours

		var allevents = new Array();
		var eventcount = 1;
		var bookedtime = 0;
		var bookedstarttime = 0;
		var bookedendtime = 0;
		var totalsum = 0;
		var firstscrolled = false;

		$('#calendar').fullCalendar(
		{
			/*
			customButtons: {
				myCustomButton: {
					text: 'Help',
					click: function() {
						$('#calselecthelp').trigger('click');
					}
				}
			},
			*/

			header: {
				left: 'today', // month
				center: 'prev, title, next',
				right: '',
				// right: 'agendaWeek', // month
				// right: 'myCustomButton',
			},
			// defaultDate: '".$weekstartday."', // first day in calendar
			editable: false,
			// eventLimit: true, // allow more link when too many events
			// firstDay: (new Date().getDay()),
			// firstDay: ".$firstdaytoshow.",
			defaultView: '".$calendarview."',
			timeFormat: 'HH:mm',
			locale: '".qa_opt('booker_language')."',
			columnFormat: 'ddd D.M.',
			titleFormat: 'YYYY MMMM',
			allDaySlot: false,
			slotDuration: '00:30:00', // default is 30 min
			minTime: '".($earliesttime-1).":00:00',
			// maxTime: '23:00:00',
			contentHeight: 600,
			"
			.$offereventsettings.
			"
			"
			.$eventsource.
			"
			// user can add more booking events
			selectable: true,
			selectHelper: true,
			longPressDelay: 0,

			// user choses event
			select: function (start, end, jsEvent, view)
			{
				// only add event if it is onto free contractor timeslots
				if(!isValidFutureEvent(start,end))
				{
					var check = start._d.toJSON().slice(0,10);
					var today = new Date().toJSON().slice(0,10);
					if(check==today)
					{
						alert('".qa_lang('booker_lang/earliesttomorrow')."');
					}
					else
					{
						alert('".qa_lang('booker_lang/notpast')."');
					}
					$('#calendar').fullCalendar('unselect');
					return;
				}
				"
				.
					($isoffermonthview ? '' :
						"
						else if(!isValidBackgroundEvent(start,end))
						{
							alert('".qa_lang('booker_lang/onlyfixedtimes')."');
							$('#calendar').fullCalendar('unselect');
							return;
						}
						"
					)
				.
				"
				if(!isoffer)
				{
					// quick calc
					var diffmin = (new Date(end).getTime()/1000 - new Date(start).getTime()/1000)/60;
					// var title = diffmin+' min';
					var title = diffmin+' min'+'\\n'+moment(start).format('ddd ".$momentformat."');
					var eventData;

					if(title)
					{
						// special: some users click 1 slot, then the following, so we have 2 events each 30 min instead of 60 min
						// merge both events into one
						var eventmerge = false;
						$.each(allevents, function( index, eventitem )
						{
							if(eventitem!==null && typeof(eventitem) != 'undefined')
							{
								// if start time of new event (2nd slot) is end time of existing event (1st slot)
								if( moment(start).format('YYYY-MM-DD HH:mm') == moment(eventitem.end).format('YYYY-MM-DD HH:mm') )
								{
									eventmerge = true;
									// existing event gets end data of new merging event
									eventitem.end = end;
								}
								// if end time of new event (1st slot) is start time of existing event (2nd slot)
								else if( moment(end).format('YYYY-MM-DD HH:mm') == moment(eventitem.start).format('YYYY-MM-DD HH:mm') )
								{
									eventmerge = true;
									// existing event gets start data of new merging event
									eventitem.start = start;
								}

								if(eventmerge)
								{
									// recalculate
									var diffmin = (new Date(eventitem.end).getTime()/1000 - new Date(eventitem.start).getTime()/1000)/60;
									eventitem.title = diffmin+' min'+'\\n'+moment(start).format('ddd ".$momentformat."');

									// copy to eventData
									eventData = eventitem;

									// find event object in calendar
									var eventsarr = $('#calendar').fullCalendar('clientEvents');
									$.each(eventsarr, function(key, eventobj)
									{
										if(eventobj._id == eventitem.id)
										{
											console.log('merging events');
											eventobj.start = eventitem.start;
											eventobj.end = eventitem.end;
											eventobj.title = eventitem.title;
											$('#calendar').fullCalendar('updateEvent', eventobj);
										}
									});

									// break each loop
									return false;
								}
							}
						});

						if(!eventmerge)
						{
							console.log('adding event id: '+eventcount);
							eventData = {
								id: eventcount, // identifier
								title: title,
								start: start,
								end: end,
								color: '#00AA00',
								editable: true,
								eventDurationEditable: true,
							};

							// register event in array
							allevents[eventcount] = eventData;
							eventcount++;

							$('#calendar').fullCalendar('renderEvent', eventData, true);
						}

						// console.log(eventData.start, eventData.end);
						setTimePrice(eventData);
					}
				} // end !isoffer
				// IS OFFER
				else
				{
					// in case the user has set already an event, remove it (we only allow the booking of one package offer)
					$.each(allevents, function( index, eventitem )
					{
						if(eventitem!==null && typeof(eventitem) != 'undefined')
						{
							// remove all former set events
							$('#calendar').fullCalendar('removeEvents', eventitem.id);

							// remove from prices
							$('#event'+eventitem.id).remove();

							// remove from array
							allevents[eventitem.id] = null;
						}
					});

					// label event with date
					title = moment(start).format('ddd ".$momentformat."');

					// console.log('adding event id: '+eventcount);
					eventData = {
						id: eventcount, // identifier
						title: title,
						start: start,
						".($offerdurationset ? '' : "end: end, // MUST HAVE no end if fixedduration is set ")."
						color: '#00AA00',
						editable: true,
					};

					/*
					console.log('Start: ');
					console.log(start);
					console.log('End: ');
					console.log(end);
					*/

					// register event in array
					allevents[eventcount] = eventData;
					eventcount++;

					$('#calendar').fullCalendar('renderEvent', eventData, true);

					// console.log(start, end);
					// console.log('selected');
					setTimePriceOffer(eventData);
				}

				".(!$ismobile ?
					"$('#calendar').fullCalendar('unselect');"
					: ""
				  ).
				"
				// scroll down to table after selection, wait shortly
				if(!firstscrolled)
				{
					setTimeout(scrolltotable, 500);
				}

			}, // end select
			selectOverlap: function(event)
			{
				return event.rendering === 'background';
			},
			timezone: ('".qa_opt('booker_timezone')."'), // false, local, UTC, ('Europe/Berlin')
			eventRender: function(event, element, view )
			{
				// console.log(event);

				// google calendar events: add labels to background times
				if(typeof(event.source)!=='undefined' && event.source.rendering === 'background')
				{
					// hide background event if in the past
					if(event.start <= moment().endOf('day'))
					{
						element.hide();
					}
					else
					{
						// add text or html to the event element
						element.append( event.start.format('HH:mm') + ' - ' + event.end.format('HH:mm'));
					}
				}

				// events: add time labels to background times
				if(typeof(event.rendering)!=='undefined' && event.rendering === 'background')
				{
					// hide background event if in the past
					if(event.start <= moment().endOf('day'))
					{
						element.hide();
					}
					else
					{
						// add text or html to the event element
						element.append( event.start.format('HH:mm') + ' - ' + event.end.format('HH:mm'));
					}
				}
				if( (window.location.href).indexOf('localhost')==-1)
				{
					// className is set for client events only
					if(event.className[0]=='orderpaid' || event.className[0]=='orderreserved')
					{
						var etooltip = (event.title).replace(/\\n/g, '<br />');
						element.attr('title', etooltip);
						element.tipsy({ gravity:'s', html:true });
					}
				}
			},
			// event time changes
			eventDrop: function(event, delta, revertFunc, jsEvent, ui, view)
			{
				if(!isValidBackgroundEvent(event.start,event.end))
				{
					revertFunc();
					return;
				}

				if(!isoffer)
				{
					setTimePrice(event);
				}
				else
				{
					// console.log('dropped');
					setTimePriceOffer(event);
				}
			},
			// user changes event time by dragging the handle
			eventResize: function(event, delta, revertFunc, jsEvent, ui, view)
			{
				// alert(event.title + ' end is now ' + event.end.format());
				if(!isValidBackgroundEvent(event.start,event.end))
				{
					revertFunc();
					return;
				}
				setTimePrice(event);
			},
			// click removes event
			eventClick: function(event)
			{
				if(event.editable == false || event.source.editable == false)
				{
					return;
				}

				// remove from prices
				$('#event'+event.id).remove();

				// remove from array
				// allevents.splice(event.id, 1);
				// eventcount--;
				allevents[event.id] = null;

				// recalculate total sum
				calculateTotal();

				// remove from calendar
				$('#calendar').fullCalendar('removeEvents',event._id);
			}

		}); // end fullCalendar

		// if sunday then set view to monday
		if((new Date()).getDay()==0)
		{
			var nextmonday = moment().day(8).format('YYYY-MM-DD');
			$('#calendar').fullCalendar('gotoDate', nextmonday);
			console.log('shifting');
		}

		// check if event is in past
		var isValidFutureEvent = function(start,end)
		{
			// http://stackoverflow.com/a/29832834/1066234
			var check = start._d.toJSON().slice(0,10);
			var today = new Date().toJSON().slice(0,10);

			// only allow booking for next days, not today and not past
			if(check > today)
			{
				// if after 12:00 today, only allow booking of day after tomorrow
				if( moment().endOf('day').add(1, 'days').isAfter(start._d) )
				{
					console.log('not allowed');
				}

				return true;
			}
			else
			{
				// previous days, will be unselectable
				return false;
			}

			/*
			return $('#calendar').fullCalendar('clientEvents', function (event) {
				console.log('event id: '+event.start._d);
				return (event.rendering != 'background' &&
							(
								(start.isBefore(event.start) && end.isBefore(event.start)) ||
								(start.isAfter(event.end) && end.isAfter(event.end))
							)
						);
			}).length > 0;
			*/
		};

		// only add it if event is onto free contractor timeslots
		var isValidBackgroundEvent = function(start,end)
		{
			".
			(
				empty($weekevents) ?
					" return true;"
					:
				"
			return $('#calendar').fullCalendar('clientEvents', function (event) {
				return (
					( (typeof(event.rendering)!=='undefined' && event.rendering === 'background')
					  ||
					  (typeof(event.source)!=='undefined' && event.source.rendering === 'background')
					)
					&&
						(start.isAfter(event.start) || start.isSame(event.start,'minute')) &&
						(end.isBefore(event.end) || end.isSame(event.end,'minute')));
			}).length > 0;
				"
			)
			."
		};

		function scrolltotable()
		{
			$('html,body').animate({
				scrollTop: $('.bookingtablewrap').offset().top
			});
			firstscrolled = true;
		}
		function calculateTotal()
		{
			totalsum = 0;
			$.each(allevents, function( index, eventitem ) {
				if(eventitem!==null && typeof eventitem != 'undefined') {
					totalsum += eventitem.price;
				}
			});
			$('#booksum').text( String(totalsum.toFixed(2)).replace('.',',')+' ".qa_opt('booker_currency')."' );
		}

		function setTimePrice(event)
		{
			// console.log('event id: '+event.id+' | start: '+event.start._d+' | start: '+event.end._d);
			if( $('#event'+event.id).length==0 )
			{
				// clone and append
				$('.bookmehold:last').clone().insertAfter('.bookmehold:last');
				$('.bookmehold:last').attr('id', 'event'+event.id);
			}
			bookedtime = (new Date(event.end).getTime()/1000 - new Date(event.start).getTime()/1000)/60;

			var timestart = moment(event.start).format('HH:mm');
			var timeend = moment(event.end).format('HH:mm');

			// var daydate = ('0'+bdate.getDate()).slice(-2)+'.'+('0'+(bdate.getMonth()+1)).slice(-2)+'.'+bdate.getFullYear();
			var weekday = moment(event.start, 'D_M_YYYY').format('ddd');
			var daydate = moment(event.end).format('".$momentformat."');

			$('#event'+event.id+' .booktime').text(weekday+', '+daydate);
			$('#event'+event.id+' .booktimehours').text(timestart+' - '+timeend+' ".qa_lang('booker_lang/timeabbr')."');
			$('#event'+event.id+' .booktimeabs').text( bookedtime+' ".qa_lang('booker_lang/min')."');
			var conpriceset = Number($('.contractorprice').data('original-price'));
			var pricetotal = 0;
			if($.isNumeric(conpriceset))
			{
				pricetotal = bookedtime/60 * conpriceset;					
			}

			// save our data in the event object
			event.price = pricetotal;

			// update in array (seems not to get referenced otherwise)
			allevents[event.id] = event;

			// write frontend
			$('#event'+event.id+' .bookcalc').text(String(pricetotal.toFixed(2)).replace('.',',')+' ".qa_opt('booker_currency')."');

			// label event
			event.title = bookedtime+' ".qa_lang('booker_lang/min')."'+'\\n'+moment(event.start).format('ddd ".$momentformat."');

			// write start and endtime as unixtime into input fields
			// $('input#starttime').val(bookedstarttime);
			// $('input#endtime').val(bookedendtime);

			calculateTotal();
		} // end setTimePrice

		function setTimePriceOffer(event)
		{
			// console.log(event);
			// console.log(event.end);
			// event.end = moment(event.start).add(".$offerduration.", 'hours');
			// console.log(event.end);

			if( $('#event'+event.id).length==0 )
			{
				// clone and append
				$('.bookmehold:last').clone().insertAfter('.bookmehold:last');
				$('.bookmehold:last').attr('id', 'event'+event.id);
			}

			var weekday = moment(event.start, 'D_M_YYYY').format('ddd');
			var daydate = moment(event.start).format('".$momentformat."');

			$('#event'+event.id+' .booktime').text(weekday+', '+daydate);

			// save our data in the event object
			event.price = ".$offerprice.";

			// update in array (seems not to get referenced otherwise)
			allevents[event.id] = event;

			$('#event'+event.id+' .bookcalc').text(String((".$offerprice.").toFixed(2)).replace('.',',')+' ".qa_opt('booker_currency')."');

			// $('#booksum').text( '".qa_lang('booker_lang/intotal')." ' + String(event.price.toFixed(2)).replace('.',',')+' ".qa_opt('booker_currency')."' );

			// label event
			event.title = moment(event.start).format('ddd ".$momentformat."');

			$('#calendar').fullCalendar('refresh');
			// $('#calendar').fullCalendar('updateEvent', event);

		} // end setTimePriceOffer

		// Highlight current day in Booking Calendar
		var cal = $('#calendar');
		if ( cal.length ){
			// Formating current day date into 'yyyy-mm-dd'
			var curr_date = new Date();
			var str = '' + (curr_date.getMonth()+1);
			var pad = '00';
			var curr_month = pad.substring(0, pad.length - str.length) + str;
			var curr_day = curr_date.getFullYear()+'-'+curr_month+'-'+curr_date.getDate();
			console.log(curr_day);

			// Finding this date header and applying class
			cal.find('.fc-head-container .fc-day-header[data-date=\"'+curr_day+'\"]').addClass('fc-today');
		}

		$(document.body).on('click', '.deletebookevent', function(e)
		{
			// event1, event2, ...
			var eventid = $(this).parent().parent().attr('id');
			eventid = eventid.replace('event','');

			// remove from prices
			$('#event'+eventid).remove();

			// remove from array
			allevents[eventid] = null;

			// recalculate total sum
			calculateTotal();

			// remove from calendar
			$('#calendar').fullCalendar('removeEvents',eventid);
		});

		$('#bookme').click( function(e)
		{
			e.preventDefault();

			// check if customername is available
			if($('#customername').val()=='') {
				alert('".qa_lang('booker_lang/specify_fullname')."');
				$('#customername').focus();
				return;
			}
			var customername = $('#customername').val();

			// check if address is set
			if($('#address').val()=='') {
				alert('".qa_lang('booker_lang/specify_address')."');
				$('#address').focus();
				return;
			}
			var address = $('#address').val();

			// check if birthdate is set
			if($('#birthdate').val()=='') {
				alert('".qa_lang('booker_lang/specify_birthdate')."');
				$('#birthdate').focus();
				return;
			}
			var birthdate = $('#birthdate').val();

			"
			.$mailpwchecks.
			"

			if($('#agbcheck').length>0) {
				if(!$('#agbcheck').prop('checked')) {
					alert('".qa_lang('booker_lang/askforterms')."')
					return;
				}
			}

			var gdata = [];
			var indx = 0;
			$.each(allevents, function( index, eventitem )
			{
				if(eventitem!==null && typeof(eventitem) != 'undefined')
				{
					gdata[indx] =
					{
						'start': eventitem.start._d,
						'end': ".($offerdurationset ? "null, " : "eventitem.end._d, ")."
						'contractorid': ".$contractorid.",
						".(!empty($offerid) ? "'offerid': ".$offerid.", " : "").
						"'customername': customername,
						'address': address,
						'birthdate': birthdate,
						".$mailpwdata."
					};
					indx++;
				}
			});
			if(gdata.length==0)
			{
				alert('".qa_lang('booker_lang/choosecaltime')."');
				return;
			}

			// show loading indicator
			$('.qa-waiting').show();

			var senddata = JSON.stringify(gdata);
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { bookdata:senddata },
				dataType: 'json',
				cache: false,
				success: function(data) {
					console.log('server returned: totalprice='+ data['totalprice']+' bookid='+data['bookid']);
					// redirect to payment page
					window.location.href = '".qa_path('pay')."?bookid='+data['bookid'];
					$('.qa-waiting').hide();
				},
				error: function(xhr, status, error) {
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
					$('.qa-waiting').hide();
				}

			}); // end ajax

		}); // end #bookme click

		// http://stackoverflow.com/questions/15800398/can-i-prevent-events-with-conflict-time
		function checkOverlap(event)
		{
			// DEV test: no overlap if offermode and month showing
			/*
			if(".$isoffermonthview.")
			{
				return true;
			}
			*/

			var start = new Date(event.start);
			var end = new Date(event.end);

			var overlap = $('#calendar').fullCalendar('clientEvents', function(ev) {
				if(ev == event)
				{
					return false;
				}
				var estart = new Date(ev.start);
				var eend = new Date(ev.end);

				return (Math.round(estart)/1000 < Math.round(end)/1000 && Math.round(eend) > Math.round(start));
			});

			if(overlap.length && event.title!='".qa_lang('booker_lang/absent')."')
			{
				// console.log(event.rendering == 'background');
				// console.log('removing overlapping events: \"'+event.title+'\" Time: '+moment(event._start).format('YYYY-MM-DD HH:mm'));
				$('#calendar').fullCalendar('removeEvents', event._id);

				// remove from prices
				$('#event'+event._id).remove();

				// remove from array
				allevents[event._id] = null;
			}
		}

		var eventsarr = $('#calendar').fullCalendar('clientEvents');
		$.each(eventsarr, function(key, eventobj) {
			checkOverlap(eventobj);
		});

		// recalculate total sum
		calculateTotal();


		// scale images on mouseclick
		$('.portfoliobox img').click( function()
		{
			if($(this).hasClass('imgmaxsize'))
			{
				$(this).removeClass('imgmaxsize');
			}
			else
			{
				$(this).addClass('imgmaxsize');
			}
		});

		function isEmail(email) {
			var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			return regex.test(email);
		}

		// in case we have a video and small screen, shrink it
		// there is no CSS solution yet, since we cannot set a videowrap
		if($(window).width()<500 && $(\"iframe[src*='www.youtube.com'], iframe[src*='player.vimeo.com']\").length>0)
		{
			var allVideos = $(\"iframe[src*='www.youtube.com'], iframe[src*='player.vimeo.com']\");

			// figure out and save aspect ratio for each video
			allVideos.each(function() {
				$(this)
				.data('aspectRatio', this.height / this.width)
				.removeAttr('height')
				.removeAttr('width');
			});
			
			$(window).resize(function() {
				// Resize all videos according to their own aspect ratio
				allVideos.each(function() {
					var elem = $(this);
					// Get parent width of this video
					var newWidth = elem.parent().width();
					elem
					.width(newWidth)
					.height(newWidth * elem.data('aspectRatio'));
				});
				// Kick off one resize to fix all videos on page load
			}).resize();
		} // end shrink

		// recommmend button
		$('.qa-favorite-button, .qa-unfavorite-button').click( function()
		{
			var clicked = $(this);

			// insert loading indicator
			clicked.parent().after('<span id=\"qa-waiting-template\" class=\"qa-waiting\" style=\"display:inline-block;\">...</span>');

			clicked.parent().hide();

			// wait 1 sec to make sure the core ajax call goes through
			setTimeout( function()
			{
				clicked.remove();
				// reload page
				window.location.href = '".qa_self_html()."';
			}, 1000);
		});

		// change position of favorite button
		var favbtn = $('.favoriteform').detach();
		// $(favbtn).appendTo('.shareoptions');
		$(favbtn).prependTo('.shareoptions');
		$('.qa-favorite-button, .qa-unfavorite-button').before('<i class=\"fa fa-star\"></i>');

		".$offerjquery."

		$('.fbsharelink').click( function()
		{
			var shareurl = $(this).data('shareurl');
			window.open('https://www.facebook.com/sharer/sharer.php?u='+escape(shareurl)+'&t='+document.title, '',
			'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=300,width=600');
			return false;
		});


	}); // END READY

</script>
			";


			// hide calendar if user booking but not hour price specified - obsolete, now allowed for 0 €
			/*
			if($contractorprice==0 && !$isoffer)
			{
				$qa_content['custom'] .= '
				<style type="text/css">
					.calselhelpwrap,
					#calendar,
					.bookingtablewrap
					{
						display:none !important;
					}
				';
			}
			*/

			$qa_content['custom'] .= '
			<style type="text/css">
				.bookingtablewrap {
					'.($hide_booktablewrap?'display:none;':'').'
				}
				/* fixes */
				body.booking #bookingtable td:nth-child(1)
				{
					text-align:left;
				}
				body.booking .portfoliobox img {
					cursor: default;
				}
			</style>';


			return $qa_content;
		}

		function x_week_range($date)
		{
			$ts = strtotime($date);
			$start = (date('w', $ts) == 0) ? $ts : strtotime('last monday', $ts);
			return array(date('Y-m-d', $start),
					date('Y-m-d', strtotime('next sunday', $start)));
		}

		function q2apro_generate_userhandle($name)
		{
			// remove all whitespaces
			$inhandle = preg_replace('/\s+/', '', $name);
			// remove special characters
			$inhandle = preg_replace("/[^a-zA-Z0-9]/", "", $inhandle);
			// maximal length of 18 chars, see db qa_users
			$inhandle = substr($inhandle, 0, 18);
			// all small letters
			$inhandle = strtolower($inhandle);
			// check if username does exist already
			$getusername = $this->q2apro_findusername($inhandle);
			// if exists then change last letter to number and check again
			if(!is_null($getusername))
			{
				$replacenr = 2;
				$isunique = false;
				while(!$isunique)
				{
					// replace last char by number
					$inhandle = preg_replace("/[^a-zA-Z]/", "", $inhandle);
					$inhandle .= $replacenr;
					// check again if does exist
					$getusername = $this->q2apro_findusername($inhandle);
					$isunique = is_null($getusername);
					$replacenr++;
				}
			}
			return $inhandle;
		}

		function q2apro_findusername($inhandle)
		{
			return qa_db_read_one_value(
								qa_db_query_sub(
									'SELECT handle FROM ^users WHERE handle=#',
									$inhandle
								),
							true);
		}

	}; // end class


/*
	Omit PHP closing tag to help avoid accidental output
*/

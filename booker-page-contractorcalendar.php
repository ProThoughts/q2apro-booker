<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_contractorcalendar
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
					'title' => 'booker Page Contractor Calendar', // title of page
					'request' => 'contractorcalendar', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='contractorcalendar') 
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
				qa_set_template('booker contractorcalendar');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			$isadmin = qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN;
			
			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker contractorcalendar');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			$level = qa_get_logged_in_level();
			
			
			// AJAX: user is submitting Google Calender Link
			$transferString = qa_post_text('gcaldata'); // holds array
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
				
				$userid = qa_get_logged_in_userid();
				
				// is admin editing another profile and posting the data then take userid from newdata
				// otherwise take userid from logged-in user
				if($isadmin && isset($newdata['userid'])) 
				{
					$userid = $newdata['userid'];
				}
				error_log('userid: '.$userid);
				// can be string such as "2016-07-27, sa, so"
				$gcallink = empty($newdata['gcallink']) ? null : $newdata['gcallink'];
				if(isset($gcallink))
				{
					// remove all spaces
					$gcallink = trim(str_replace(' ', '', $gcallink));
				}
				error_log($gcallink);
				// *** if we support outlook.com later on, we have to store both urls divided by ;
				
				// save absent time data
				booker_set_userfield($userid, 'externalcal', $gcallink);
				
				/*
				// LOG (later)
				$eventid = null;
				$eventname = 'contractor_weekupdated';
				$params = array(
					'times' => $logstring
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				*/
				
				// ajax return success
				$arrayBack = array(
					'updated' => '1'
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END ajax dataabsent

			// AJAX: user is submitting his absenttime data
			$transferString = qa_post_text('dataabsent'); // holds array
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				$userid = qa_get_logged_in_userid();
				
				// is admin editing another profile and posting the data then take userid from newdata
				// otherwise take userid from logged-in user
				if($isadmin && isset($newdata['userid'])) 
				{
					$userid = $newdata['userid'];
				}
				
				// can be string such as "2016-07-27, sa, so"
				$contractorabsent = empty($newdata['contractorabsent']) ? null : $newdata['contractorabsent'];
				if(isset($contractorabsent))
				{
					// remove all spaces
					$contractorabsent = trim(str_replace(' ', '', $contractorabsent));
					// $contractorabsent = helper_weekdays_to_numbers($contractorabsent);
				}
				
				// add weekdays to absent data
				$weekdays = empty($newdata['weekdays']) ? null : $newdata['weekdays'];
				if(isset($weekdays))
				{
					// remove all spaces
					$weekdays = trim(str_replace(' ', '', $weekdays));
					$contractorabsent .= (empty($contractorabsent) ? $weekdays : ','.$weekdays);
				}
				
				// save absent time data
				booker_set_userfield($userid, 'absent', $contractorabsent);
				
				/*
				// LOG (later)
				$eventid = null;
				$eventname = 'contractor_weekupdated';
				$params = array(
					'times' => $logstring
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				*/
				
				// ajax return success
				$arrayBack = array(
					'updated' => '1'
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END ajax dataabsent

			$transferString = qa_post_text('weekdata'); // holds array
			if(isset($transferString))
			{
				date_default_timezone_set('UTC'); // important for correct hours
				header('Content-Type: application/json; charset=UTF-8');
				
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
				
				// go over event objects and check if times included
				foreach($newdata as $event)
				{
					if(! (isset($event['start']) && isset($event['end'])) ) 
					{
						header('HTTP/1.1 500 Internal Server Error');
						echo 'Error: Start-End-Data missing';
						return;
					}
					// error_log($event['start'].' -- '.$event['end']);
				}
				
				$userid = qa_get_logged_in_userid();
				
				// is admin editing another profile and posting the data then take userid from newdata
				// otherwise take userid from logged-in user
				if($isadmin && isset($event['userid'])) 
				{
					$userid = $event['userid'];
				}
				
				// only registered users
				if(!isset($userid))
				{
					header('HTTP/1.1 500 Internal Server Error');
					echo 'only for registered users';
					return;
				}

				// *** could LOG the existing entries of week before delete
				
				// remove all existing entries
				qa_db_query_sub('DELETE FROM ^booking_week 
									WHERE userid = #', $userid);
				
				$logstring = '';
				// insert all new weekdata entries
				foreach($newdata as $event)
				{
					// start is e.g. "1 12:00" (Monday 12:00h)
					$weekday = (int)(substr($event['start'],0,1));
					$dstart = substr($event['start'],2,8).':00';
					$dend = substr($event['end'],2,8).':00';
					// error_log($weekday.' '.$dstart.' '.$dend);
					qa_db_query_sub('INSERT INTO ^booking_week (userid,weekday,starttime,endtime)  
													VALUES (#, #, #, #)', 
															$userid, $weekday, $dstart, $dend);
					$logstring .= (empty($logstring)?'':';').$weekday.' '.$dstart.' '.$dend;
				}

				// LOG
				$eventid = null;
				$eventname = 'contractor_weekupdated';
				$params = array(
					'times' => $logstring
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				// ajax return success
				$arrayBack = array(
					'updated' => '1'
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX RETURN (weekdata)

			

			// CHANGE Event
			/*			
			$transferString = qa_post_text('bookingtimes'); // holds array of changed events
			if(isset($transferString))
			{
				header('Content-Type: application/json; charset=UTF-8');
				
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
				
				// go over event objects and check if times included
				foreach($newdata as $event) 
				{
					if(! (isset($event['start']) && isset($event['end'])) ) 
					{
						header('HTTP/1.1 500 Internal Server Error');
						echo 'Error: Start-End-Data missing';
						return;
					}
					// error_log($event['start'].' -- '.$event['end']);
				}
				
				// insert all new bookingtimes entries
				foreach($newdata as $event) 
				{
					$eventid = $event['eventid'];
					// dont rely on strtotime, timezone problems, just process the time string we get
					// start is e.g. "2015-05-30T08:00:00.000Z", we need 2015-05-30 08:00:00
					$starttime = substr($event['start'],0,10).' '.substr($event['start'],11,8);
					$endtime = substr($event['end'],0,10).' '.substr($event['end'],11,8);
					
					$timedone = strtotime($endtime) - strtotime($starttime);
					$timedone = $timedone/60; // in min
					
					// get former times and save them in log
					$formerevent = qa_db_read_one_assoc(
										qa_db_query_sub('SELECT contractorid, starttime, endtime FROM `^booking_orders` 
														WHERE eventid = # 
														;', $eventid) 
									);
					// changer, change-date, starttime, endtime
					$changer = $formerevent['contractorid'];
					if($userid==1)
					{
						// modified by admin
						$changer = 1;
					}
					// update event with new times
					qa_db_query_sub('UPDATE ^booking_orders 
										SET starttime=#, endtime=# 
											WHERE eventid = #
											', 
											$starttime, $endtime, $eventid
									);
									// SET starttime=FROM_UNIXTIME(#), endtime=FROM_UNIXTIME(#)
									
					// LOG
					$eventname = 'eventtime_changed';
					// only log if really new time set
					if($formerevent['starttime'] != $starttime || $formerevent['endtime'] != $endtime)
					{
						$params = array(
							'former_s' => $formerevent['starttime'],
							'former_e' => $formerevent['endtime'],
							'new_s' => $starttime, 
							'new_e' => $endtime
						);
						booker_log_event($changer, $eventid, $eventname, $params);
					}
				}

				// ajax return success
				$arrayBack = array(
					'updated' => '1'
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX RETURN (bookingtimes)
			*/
			
			// super admin can have view of others for profile if adding a userid=x to the URL
			$isadmin = qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN;
			if($isadmin) 
			{
				$userid = qa_get('userid');
				if(empty($userid)) 
				{
					$userid = qa_get_logged_in_userid();
				}
			}

			
			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker contractorcalendar');
			$qa_content['title'] = qa_lang('booker_lang/concal_title');
			
			$weekdays = helper_get_weekdayarray();
			
			// $weekstartday = date("Y-m-d", strtotime('monday this week')); // problem with php5.2
			$weekstartday = date('Y-m-d',strtotime(date('o-\\WW'))); 

			// only future events and last 30 days events
			$bookedevents = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, unitprice, status FROM `^booking_orders` 
														WHERE contractorid = # 
														AND created >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)														
														ORDER BY starttime
														;', $userid) 
							);
			
			$hasevents = count($bookedevents)>0;
			
			$conabswday = $this->getcontractorabsent($userid);
			$contractorabsent = $conabswday['absent'];
			$absentweekdays = $conabswday['weekdays'];
			
			// get week schedule
			$weekschedule = qa_db_read_all_assoc( 
							qa_db_query_sub('SELECT weekday, starttime, endtime FROM `^booking_week` 
														WHERE `userid` = #
														ORDER BY weekday
														;', $userid) );
			$weekdays = helper_get_weekdayname_array();
			$weekevents = '';
			$weeksched = '<div class="weekbox">'.get_contractorweektimes($weekschedule).'</div>';
			
			// * memo: can happen that $times['endtime'] < $times['starttime'] (00:00 < 20:00)
			foreach($weekschedule as $times) 
			{
				$wkday = $times['weekday'];
				// 01 september is monday, so we can insert the weekday as day number
				// js full calendar
				$weekevents .= "
				{
					title: '',
					start: '".$times['starttime']."',
					end: '".$times['endtime']."',
					dow: [".$wkday."], // repeat same weekday
					className: 'event-favoritetime',
					// rendering: 'background', 
					// color: '#0A0'
					// color: '#6BA5C2'
				},

				";
				// rendering background: // http://fullcalendar.io/docs/event_rendering/Background_Events/
			}
			
			$calinstructions = helper_get_calendar_instructions();
			

			$ispremium = booker_ispremium($userid);
			
			$googlecalendar_connect = '
				<div>
					<input type="text" placeholder="'.qa_lang('booker_lang/googlecal_link').'" class="gcal_url" value="'.booker_get_userfield($userid, 'externalcal').'" />
					<br>
					<div class="gcalbuttonholder">
						<button id="gcalbutton" class="defaultbutton" style="padding:8px 12px;">
							'.qa_lang('booker_lang/save_btn').'
						</button>
						<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
					</div>
				</div>
			';
			
			if(!$ispremium)
			{
				$googlecalendar_connect = booker_become_premium_notify();
			}
			
			
			// init
			$qa_content['custom'] = '';
			
			$qa_content['custom'] .= '
				<div class="buttonlistwrap">
					<a class="defaultbutton" id="showcalendar">
						<i class="fa fa-calendar"></i>
						'.qa_lang('booker_lang/calendar').'
					</a>
					<a class="defaultbutton btn_graylight" id="showimport">
						<i class="fa fa-google"></i>
						'.qa_lang('booker_lang/google_connect').'
					</a>
					<a class="defaultbutton btn_graylight" id="showexport">
						<i class="fa fa-share-alt"></i>
						'.qa_lang('booker_lang/embedcal').'
					</a>
					<a class="defaultbutton btn_graylight" id="showtimesetup">
						<i class="fa fa-ban"></i>
						'.qa_lang('booker_lang/block_times').'
					</a>
					<a class="defaultbutton btn_graylight" id="showfavoritetimes">
						<i class="fa fa-lock"></i>
						'.qa_lang('booker_lang/favoritetimes').'
					</a>
				</div> <!-- buttonlistwrap -->
				
				<div class="box_import">
					<h2>
						'.qa_lang('booker_lang/google_connect').'
					</h2>
					<p>
						'.
						strtr( qa_lang('booker_lang/google_hint'), array( 
							'^1' => '<a target="_blank" href="https://www.google.com/calendar/">',
							'^2' => '</a>'
						)).
						'
					</p>
					<p>
						'.qa_lang('booker_lang/google_nodetails').'
					</p>
					
					'.$googlecalendar_connect.'
					
					'.helper_get_calendar_hints($this->urltoroot).'
					
				</div> <!-- box_import -->
				
				<div class="box_export">
					<h2>
						'.qa_lang('booker_lang/embedcal').'
					</h2>
					<p>
						'.qa_lang('booker_lang/embedcalendar').': 
						<br />
					</p>
					<p>
						<input type="text" value="'.q2apro_site_url().'ics?userid='.$userid.'" id="icscalurl" />
					</p>
					
					<p style="margin-top:15px;">
						'.qa_lang('booker_lang/embedcalendar_2').'
					</p>
					<div class="" style="color:#AAA !important;">
						<h3>
							'.qa_lang('booker_lang/instructions').':
						</h3>
						'.$calinstructions.'
					</div>
				</div> <!-- box_export -->
				
				<div class="box_timesetup">
					<div class="contractorholidays">
						<h2>
							'.qa_lang('booker_lang/admin_tab_absent').'
						</h2>
						<p>
							<input type="text" name="contractorabsent" id="contractorabsent" value="'.$contractorabsent.'" placeholder="'.qa_lang('booker_lang/absent_placeholder').'" /> 
						</p>
						<p>
							<span class="contractorabsenthint">'.
								qa_lang('booker_lang/absent_tooltip').
							'</span>
						</p>
					</div>
					
					<h2 style="margin:30px 0 20px 0;">
						'.qa_lang('booker_lang/weekdays_head').'
					</h2>
					
					<div class="weekdaysavailable">
						<label>
							<input class="weekdaycheckbox" type="checkbox" name="mon" value="1" '.(strpos($absentweekdays,'1')!==false ? 'checked' : '').' />
							'.qa_lang('booker_lang/mon').'
						</label>
						<label>
							<input class="weekdaycheckbox" type="checkbox" name="tue" value="2" '.(strpos($absentweekdays,'2')!==false ? 'checked' : '').' />
							'.qa_lang('booker_lang/tue').'
						</label>
						<label>
							<input class="weekdaycheckbox" type="checkbox" name="wed" value="3" '.(strpos($absentweekdays,'3')!==false ? 'checked' : '').' />
							'.qa_lang('booker_lang/wed').'
						</label>
						<label>
							<input class="weekdaycheckbox" type="checkbox" name="thu" value="4" '.(strpos($absentweekdays,'4')!==false ? 'checked' : '').' />
							'.qa_lang('booker_lang/thu').'
						</label>
						<label>
							<input class="weekdaycheckbox" type="checkbox" name="fri" value="5" '.(strpos($absentweekdays,'5')!==false ? 'checked' : '').' />
							'.qa_lang('booker_lang/fri').'
						</label>
						<label>
							<input class="weekdaycheckbox" type="checkbox" name="sat" value="6" '.(strpos($absentweekdays,'6')!==false ? 'checked' : '').' />
							'.qa_lang('booker_lang/sat').'
						</label>
						<label>
							<input class="weekdaycheckbox" type="checkbox" name="sun" value="7" '.(strpos($absentweekdays,'7')!==false ? 'checked' : '').' />
							'.qa_lang('booker_lang/sun').'
						</label>
						
						<p style="margin-top:50px;" class="absentbuttonholder">
							<button class="defaultbutton" id="contractorabsentbutton">'.qa_lang('booker_lang/save_btn').'</button>
							<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
						</p>
					</div>
				</div> <!-- box_timesetup -->
				
				<div class="box_favoritetimes">
				
					<h2>
						'.qa_lang('booker_lang/favoritetimes').'
					</h2>
					<p>
						'.qa_lang('booker_lang/week_hint0').'
					</p>
					<p>
						<i class="fa fa-exclamation-triangle fa-lg" style="color:#F33;"></i>
						'.qa_lang('booker_lang/week_hint1').'
					</p>
					<p>
						<i class="fa fa-lightbulb-o fa-lg" style="margin:0 3px;"></i>
						'.qa_lang('booker_lang/week_hint2').'
					</p>
					<p style="margin:30px 0 30px 0;font-size:11px;">
						'.qa_lang('booker_lang/week_hint3').'
						<br />
						'.qa_lang('booker_lang/week_hint4').'
						<br />
						'.qa_lang('booker_lang/week_hint5').'
					</p>

					<div id="calendar-favorite"></div>
					
					<div class="favoritemeholder">
						<button id="favoriteme" class="defaultbutton" type="submit">'.qa_lang('booker_lang/saveweek_btn').'</button>
						<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
					</div>
					
				</div> <!-- box_favoritetimes -->
			';
			
			// $bookedx stores all events for fullcalendar
			$bookedx = '';
			
			$clientids = array();
			
			// get week schedule for all customer bookings
			foreach($bookedevents as $event)
			{
				// customer handles in calendar
				$eventcustomer = booker_get_realname($event['customerid']);
				
				// calculate from starttime and endtime (status == reserviert)
				$eventduration = booker_get_timediff($event['starttime'], $event['endtime']);
				
				$eventtitle = '';
				
				// open
				if($event['status']==MB_EVENT_OPEN)
				{
					// open, excluded by mysql
					// $eventtitle = 'eventuell - '.$eventcustomer;
					// $eventcolor = '#777';
				}
				// reserved (payment will be processed)
				else if($event['status']==MB_EVENT_RESERVED)
				{
					$eventtitle = qa_lang('booker_lang/notpaidyet').'\n'.qa_lang('booker_lang/from').' '.$eventcustomer;
					$eventcolor = '#F77';
				}
				// paid
				// else if($event['status']==MB_EVENT_PAID)
				else if(booker_event_is_paid($event['eventid']))
				{
					$eventtitle = qa_lang('booker_lang/paid').'\n'.qa_lang('booker_lang/from').' '.$eventcustomer;
					$eventcolor = '#66F';
				}
				// completed
				else if($event['status']==MB_EVENT_COMPLETED)
				{
					$eventtitle = qa_lang('booker_lang/completed').'\n'.$eventcustomer;
					$eventcolor = '#89C';
				}
				$eventtitle .= '\n'.qa_lang('booker_lang/price').': '.number_format($event['unitprice']/60*$eventduration,2,',','.').' '.qa_opt('booker_currency');
				
				if($event['status']>MB_EVENT_RESERVED)
				{
					array_push($clientids, $event['customerid']);
				}
				
				// show all but open events
				if($event['status']!=MB_EVENT_OPEN) 
				{
					$editable = 'false';
					
					// all events starting from last 48 hours are editable if not yet "completed"
					if(strtotime($event['starttime']) > time()-60*60*48 && $event['status']==MB_EVENT_RESERVED || booker_event_is_paid($event['eventid']))
					{
						// var_dump( $event['starttime'].' -> '.strtotime($event['starttime']) .'>'. time().' -> '.date('Y-m-d H:m:s', time()) );
						$editable = 'true';						
					}
					// js full calendar
					$bookedx .= "
					{
						id: '".$event['eventid']."',
						title: '".$eventtitle."',
						start: '".$event['starttime']."',
						end: '".$event['endtime']."',
						editable: ".$editable.",
						color: '".$eventcolor."',
					},

					";
				}
			} // end foreach $bookedevents
			
			// Absent times
			$contractorabsent = booker_get_userfield($userid, 'absent');
			
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
							// url: '".qa_path('contractorweek')."',
							// rendering: 'background', 
							className: 'event-notavailable',
						},
						
					";
				}
				// time period, check by - or length
				else if(strlen($time)>10)
				{
					// time period, make sure we have a minus as time span divider 
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
							// rendering: 'background', 
							// url: '".qa_path('contractorweek')."',
						},

						";
					}
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
							// url: '".qa_path('contractorweek')."',
							// rendering: 'background', 
							className: 'event-notavailable',
						},
						
					";
				}
			}
		
			// output 
			$qa_content['custom'] .= '
			<div class="box_calendar">
			
				<div id="calendar"></div>
				
				<h3 style="margin-top:50px;">
					'.qa_lang('booker_lang/eventtypes').':
				</h3>
				
				<!--
				<div class="fc-event-container" style="display:inline-block;min-width:120px;margin-bottom:10px;">
					<a class="fc-day-grid-event fc-h-event fc-event fc-start fc-end" style="background-color:rgba(50,50,50,0.5);border-color:#AAA;padding:2px 10px;">
						<div class="fc-content">
							<span class="fc-title">
								'.qa_lang('booker_lang/absent').' 
							</span>
						</div>
					</a>
				</div>
				-->
				
				<div class="fc-event-container" style="display:inline-block;margin-bottom:10px;">
					<a class="fc-day-grid-event fc-h-event fc-event fc-start fc-end" style="background-color:rgba(50,50,50,0.5);border-color:#AAA;padding:2px 10px;">
						<div class="fc-content">
							<span class="fc-title">
								'.qa_lang('booker_lang/notavailable').'
							</span>
						</div>
					</a>
				</div>
				
				<div class="fc-event-container" style="display:inline-block;margin-bottom:10px;">
					<a class="fc-day-grid-event fc-h-event fc-event fc-start fc-end" style="background-color:rgba(50,150,50,0.5);border-color:#AAA;padding:2px 10px;">
						<div class="fc-content">
							<span class="fc-title">
								Google '.qa_lang('booker_lang/calendar').'
							</span>
						</div>
					</a>
				</div>
				
				<div class="fc-event-container" style="display:inline-block;margin-bottom:10px;">
					<a class="fc-day-grid-event fc-h-event fc-event fc-start fc-end" style="background-color:#F77;border-color:#F77;padding:2px 10px;">
						<div class="fc-content">
							<span class="fc-title">
								'.qa_lang('booker_lang/notpaidyet').'
							</span>
						</div>
					</a>
				</div>
				
				<div class="fc-event-container" style="display:inline-block;margin-bottom:10px;">
					<a class="fc-day-grid-event fc-h-event fc-event fc-start fc-end" style="background-color:#66F;border-color:#66F;padding:2px 10px;">
						<div class="fc-content">
							<span class="fc-title">
								'.qa_lang('booker_lang/paid').'
							</span>
						</div>
					</a>
				</div>
				
				<div class="fc-event-container" style="display:inline-block;margin-bottom:10px;">
					<a class="fc-day-grid-event fc-h-event fc-event fc-start fc-end" style="background-color:#89C;border-color:#89C;padding:2px 10px;">
						<div class="fc-content">
							<span class="fc-title">
								'.qa_lang('booker_lang/completed').'
							</span>
						</div>
					</a>
				</div>
				
			';

			/*
			if($hasevents)
			{
				$qa_content['custom'] .= '
					<div class="changemeholder">
						<button id="changeme" class="defaultbutton" type="submit">'.qa_lang('booker_lang/btnupdate_appt').'</button>
					</div>
				';
			}
			*/
			
			if(count($clientids)>0)
			{
				// remove duplicats
				$clientids = array_unique($clientids);
				
				// list of recent clients to contact
				$qa_content['custom'] .= '
					<h3 style="margin-top:50px;">
						'.qa_lang('booker_lang/contact_clients').'
					</h3>
				';
				
				$contactlist = '
					<ol>
				';
				
				foreach($clientids as $studid)
				{
					$contactlist .= '
						<li>
							<a target="_blank" href="'.qa_path('mbmessages').'?to='.$studid.'">'.booker_get_realname($studid).'</a>
						</li>
						';
				}
				$contactlist .= '
					</ol>
				';
				
				$qa_content['custom'] .= $contactlist;
			}
			
			$qa_content['custom'] .= '
						</div> <!-- box_calendar -->
			';
			
			// todays weekday
			$weekdaytoday = date('N', time());

			$defaultview = 'month'; // 'agendaWeek' -- *** weekList see http://jsfiddle.net/nomatteus/dVGN2/3/ --- https://rawgit.com/nomatteus/6902265/raw/db7d6e9ff68815ea6c40215f73b933d3a23e3da3/fullcalendar.js
			
			// OR http://stackoverflow.com/questions/26376681/full-calendar-week-display-vertical-view
			
			
			$contractorprice = (float)(booker_get_userfield($userid, 'bookingprice'));
			
			$eventsource = '
				events: [
					'.$bookedx.'
				],
			';
			
			// does user have google calendar set up
			$gcalurl_user = booker_get_userfield($userid, 'externalcal');
			if(!empty($gcalurl_user))
			{
				// http://fullcalendar.io/docs/event_data/eventSources/
				$eventsource = "
					googleCalendarApiKey: 'AIzaSyAchBgxfHp3LU0Ga8mB5srsU_0nE_U8f6I',
					eventSources: [
						{
							googleCalendarId: '".$gcalurl_user."',
							title: 'Google Calendar', // not showing, guess need append
							editable: false,
							color: 'rgba(50,150,50,0.5)',
							borderColor: '#AAA',
							className: 'gcal-event',
						},
						{
							events: [
								".$bookedx."
							],
						}
					],
				";
			}
			
			

			
			// jquery
			$qa_content['custom'] .= "
<script>

	$(document).ready(function()
	{
		// hide update button at startup
		// $('#changeme').hide();
		
		var allevents = new Array();
		var changedevents = new Array();
		var eventcount = 1;
		var contractorprice = ".$contractorprice.";
		
		$('#calendar').fullCalendar(
		{
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'agendaDay,agendaWeek,month'
			},
			// defaultDate: '".$weekstartday."',
			// editable: false,
			// eventLimit: true, // allow more link when too many events
			// firstDay: (new Date().getDay()),
			// firstDay: ".$weekdaytoday.",
			defaultView: '".$defaultview."',
			timeFormat: 'HH:mm',
			locale: '".qa_opt('booker_language')."',
			// columnFormat: 'dddd D.M.', // weekdays for agendaWeek see 'views' below
			allDaySlot: false,
			slotDuration: '00:30:00', // default is 30 min
			contentHeight: 600,
	
			"
			.$eventsource.
			"
			
			// contractor cannot add more booking events
			/*
			selectable: true,
			selectHelper: true,
			select: function (start, end, jsEvent, view) {
				// only add it if event is onto free expert timeslots
				if(!isValidBackgroundEvent(start,end) || !isValidEvent(start,end)) {
					$('#calendar').fullCalendar('unselect');
					return;
				}

				// quick calc
				var diffmin = (new Date(end).getTime()/1000 - new Date(start).getTime()/1000)/60;
				var title = '".qa_lang('booker_lang/booktimespan')." ('+diffmin+' min)';
				var eventData;
				if(title) {
					eventData = {
						id: eventcount, // identifier
						title: title,
						start: start,
						end: end,
						color: '#00AA00',
						editable: true,
						eventDurationEditable: true,
					};
					
					allevents[eventcount] = eventData;
					eventcount++;

					$('#calendar').fullCalendar('renderEvent', eventData, true);
					
				}
				$('#calendar').fullCalendar('unselect');
			},
			*/
			
			timezone: ('".qa_opt('booker_timezone')."'), // false, local, UTC, ('Europe/Berlin')
			
/***
2016 gegužės
pirmadienis	antradienis	trečiadienis	ketvirtadienis	penktadienis	šeštadienis	sekmadienis

2016 m. gegužės 23 — 29 d.
pirmadienis 23.5.	antradienis 24.5.	trečiadienis 25.5.	ketvirtadienis 26.5.	penktadienis 27.5.	šeštadienis 28.5.	sekmadienis 29.5.

2016 m. gegužės 23 d.
pirmadienis
***/
			// http://momentjs.com/docs/#/displaying/format/
			views: {
				month: {
					titleFormat: 'YYYY MMMM', // 2016 geguze, MMMM not working
					columnFormat: 'ddd'
				},
				agendaWeek: {
					titleFormat: 'YYYY MMM D.',
					columnFormat: 'ddd D.M.' // pirmadienis 23 — sekmadienis 29.5
				},
				day: {
					titleFormat: 'YYYY MMM D',
					columnFormat: 'ddd D.M.'  // * dddd not working
				},
			},
	
			eventRender: function(event, element, view )
			{
				// tooltip with all information
				// var etooltip = (event.title).replace(/\\n/g, '<br />');
				// element.attr('title', etooltip);
				
				// highlight dates holding events
				if(view.name=='agendaWeek') 
				{
					var weekdays = new Array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
					var eventweekday = $.fullCalendar.moment(event.start).format('d');
					// $('.fc-'+weekdays[eventweekday]).css('background-color', '#FFC');
				}
				// tooltip for event hover
				if( (window.location.href).indexOf('localhost')==-1)
				{
					element.tipsy({ gravity:'s', html:true });
				}
			},
			
			// event time changes
			eventDrop: function(event, delta, revertFunc, jsEvent, ui, view)
			{
				eventchanged = true;
				
				// add to array
				// changedevents.push(event);
				changedevents[event.id] = event;
				
				/*if(!isValidBackgroundEvent(event.start,event.end)){
					revertFunc();
					return;
				}
				*/
				$('#changeme').show(); // on any action
			},
			
			// event time changes
			eventResize: function(event, delta, revertFunc, jsEvent, ui, view)
			{
				eventchanged = true;
				
				// add to array
				// changedevents.push(event);
				// console.log('added: '+event.id);
				changedevents[event.id] = event;
				
				/*
				if(!isValidBackgroundEvent(event.start,event.end)){
					revertFunc();
					return;
				}
				*/
				// could use 'delta' here
				var newtime = (new Date(event.end).getTime()/1000 - new Date(event.start).getTime()/1000)/60;
				event.title = '".qa_lang('booker_lang/ev_new').": '+newtime+' min\\n'+'".qa_lang('booker_lang/price').": '+roundandcomma(newtime/60*contractorprice)+' ".qa_opt('booker_currency')."';
				$('#changeme').show(); // on any action
			},
			
		}); // end fullCalendar

		// check if event is in past
		var isValidEvent = function(start,end)
		{
			// *** not working yet
			// http://stackoverflow.com/a/29832834/1066234
			var check = start._d.toJSON().slice(0,10);
			var today = new Date().toJSON().slice(0,10);
			
			if(check >= today)
			{
				return true;
			}
			else 
			{
				// Previous Day. show message if you want otherwise do nothing.
				// So it will be unselectable
				return false;
			}
	
			/*
			return $('#calendar').fullCalendar('clientEvents', function (event) {
				console.log(event.start._d);				
				return (event.rendering != 'background' &&	
							(
								(start.isBefore(event.start) && end.isBefore(event.start)) || 
								(start.isAfter(event.end) && end.isAfter(event.end))
							)
						);
			}).length > 0;
			*/
		};
		
		// only add it if event is onto free expert timeslots
		var isValidBackgroundEvent = function(start,end)
		{
			return $('#calendar').fullCalendar('clientEvents', function (event) {
				return (event.rendering === 'background' && //Add more conditions here if you only want to check against certain events
						(start.isAfter(event.start) || start.isSame(event.start,'minute')) &&
						(end.isBefore(event.end) || end.isSame(event.end,'minute')));
			}).length > 0;
		};

		$('#showcalendar, #showimport, #showexport, #showtimesetup, #showfavoritetimes').click( function(e) 
		{
			e.preventDefault();
			$('#showcalendar, #showimport, #showexport, #showtimesetup, #showfavoritetimes').addClass('btn_graylight');
			$(this).removeClass('btn_graylight');
			
			$('.box_calendar, .box_import, .box_export, .box_timesetup, .box_favoritetimes').hide();
			
			var clickid = $(this).attr('id');
			if(clickid=='showcalendar')
			{
				$('.box_calendar').show();
			}
			else if(clickid=='showimport')
			{
				$('.box_import').show();
			}
			else if(clickid=='showexport')
			{
				$('.box_export').show();
			}
			else if(clickid=='showtimesetup')
			{
				$('.box_timesetup').show();
			}
			else if(clickid=='showfavoritetimes')
			{
				$('.box_favoritetimes').show();
			}
			
			// remember choice in URL
			window.history.pushState('menu', '', '".$request."#'+clickid);
		});
		
		/**
		$('#changeme').click( function(e)
		{
			e.preventDefault();
			var gdata = [];
			var indx = 0;
			
			// remove empty slots from array
			changedevents.clean(undefined);
			// console.log(changedevents);
			
			if(changedevents.length==0) {
				alert('".qa_lang('booker_lang/nochanges')."');
				return;
			}
			$.each(changedevents, function( index, eventitem ) {
				// console.log( 'id: '+eventitem.id+' -> '+new Date(eventitem.start).getHours() +' - '+new Date(eventitem.end).getHours());
				// careful: new Date() considers timezone of recent user (browser)!
				gdata[indx] = {
					'eventid': eventitem.id, 
					'start': eventitem.start._d,
					'end': eventitem.end._d,
				};
				indx++;
			});
			console.log(gdata);
			
			var senddata = JSON.stringify(gdata);
			console.log(senddata);
			
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { bookingtimes:senddata },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					$('.qa-waiting').hide();
					console.log('server returned: '+data);
					if(data['updated']=='1')
					{
						$('.changemeholder').prepend('<p class=\'smsg\'>".qa_lang('booker_lang/updatesuccess')."<br />".qa_lang('booker_lang/reloadingpage')."</p>');
						$('.smsg').fadeOut(3000, function() 
						{
							window.location.href = '".qa_self_html()."';
						});
						eventchanged = false; // no warn on leave
					}
				},
				error: function(xhr, status, error) {
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
				}
				
			}); // end ajax
		}); // end changeme click
		**/
		
		function roundandcomma(val) 
		{
			// var precision = 2;
			// val = Math.round(val*precision)/precision;
			// x,00
			return String(val.toFixed(2) ).replace(/\./g, ',');
		}
		
		// FAVORITE TIMES CALENDAR
		var eventcount_fav = 1;
		var allevents_fav = new Array();
		var firstrundone = false;

		$('#calendar-favorite').fullCalendar(
		{
			header: {
				left: '', 	// 'prev,next today',
				center: '', // 'title',
				right: '' 	// 'agendaWeek'
			},
			defaultDate: '".$weekstartday."',
			editable: true,
			// eventLimit: true, // allow more links if too many events
			firstDay: 1,
			defaultView: 'agendaWeek',
			timeFormat: 'HH:mm',
			locale: '".qa_opt('booker_language')."',
			columnFormat: 'dddd',
			allDaySlot: false,
			slotDuration: '00:30:00', // default is 30 min
			contentHeight: 600,
			events: [
				".$weekevents."
			],
			
			// user can add more booking events
			selectable: true,
			selectHelper: true,
			
			// user choses event
			select: function (start, end, jsEvent, view) 
			{
				// quick calc
				var diffmin = (new Date(end).getTime()/1000 - new Date(start).getTime()/1000)/60;
				var title = diffmin+' min';
				var eventData;
				if(title)
				{
					// special: some users click 1 slot, then the following, so we have 2 events each 30 min instead of 60 min
					// merge both events into one
					var eventmerge = false;
					$.each(allevents_fav, function( index, eventitem ) 
					{
						if(eventitem!==null && typeof eventitem != 'undefined')
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
								eventitem.title = diffmin+' min';
								
								// copy to eventData
								eventData = eventitem;
								
								// find event object in calendar
								var eventsarr = $('#calendar-favorite').fullCalendar('clientEvents');
								$.each(eventsarr, function(key, eventobj) { 
									if(eventobj._id == eventitem.id) {
										console.log('merging events');
										eventobj.start = eventitem.start;
										eventobj.end = eventitem.end;
										eventobj.title = eventitem.title;
										$('#calendar-favorite').fullCalendar('updateEvent', eventobj);
									}
								});

								// break each loop
								return false;
							}
						}
					});
					if(!eventmerge)
					{
						// console.log('adding event id: '+eventcount_fav);
						eventData = {
							id: eventcount_fav, // identifier
							title: title,
							start: start,
							end: end,
							// color: '#00AA00',
							editable: true,
							eventDurationEditable: true,
						};
						
						// register event in array
						allevents_fav[eventcount_fav] = eventData;
						eventcount_fav++;
						// console.log(allevents_fav);
						$('#calendar-favorite').fullCalendar('renderEvent', eventData, true);
					}

					// console.log(start, end);
					// setTimePrice(eventData);
				}
				$('#calendar-favorite').fullCalendar('unselect');
			},
			
			selectOverlap: function(event) 
			{
				return event.rendering === 'background';
			},
			
			// timezone: 'local',
			eventAfterAllRender: function()
			{
				// *** get existing events and put them into allevents_fav for merging purposes
				// allevents_fav = $('#calendar-favorite').fullCalendar('clientEvents');
				// eventcount_fav = allevents_fav.length;
			},
			
			// event time changes
			eventDrop: function(event, delta, revertFunc, jsEvent, ui, view)
			{
				if(!isValidBackgroundEvent_fav(event.start,event.end))
				{
					revertFunc();
					return;
				}
				// setTimePrice(event);
				eventchanged_fav = true;
			},
			
			// event time changes
			eventResize: function(event, delta, revertFunc, jsEvent, ui, view) 
			{
				// alert(event.title + ' end is now ' + event.end.format());
				if(!isValidBackgroundEvent_fav(event.start,event.end))
				{
					revertFunc();
					return;
				}
				// setTimePrice(event);
				eventchanged_fav = true;
			},
			
			// click removes event
			eventClick: function(event){
				if(event.editable == false) {
					return;
				}
				
				// remove from prices
				$('#event'+event.id).remove();
				
				// remove from array
				// allevents_fav.splice(event.id, 1);
				// eventcount_fav--;
				allevents_fav[event.id] = null;
				
				// recalculate total sum
				// calculateTotal();
				
				// remove from calendar
				$('#calendar-favorite').fullCalendar('removeEvents',event._id);
				eventchanged_fav = true;
			},
			
			// hide after page load and fullcalendar has initialized
			eventAfterAllRender: function(view)
			{
				if(!firstrundone)
				{
					firstrundone = true;
					$('.box_favoritetimes').hide();					
				}
			}

		}); // end fullCalendar favorite
		
		// only add it if event is onto free contractor timeslots
		var isValidBackgroundEvent_fav = function(start,end)
		{
			return true;
		};

		// weekdata
		$('#favoriteme').click( function(e) 
		{
			e.preventDefault();
			var gdata = [];
			var indx = 0;
			var weekevents = $('#calendar-favorite').fullCalendar('clientEvents');
			$.each(weekevents, function( index, eventitem )
			{
				if(eventitem!==null && typeof eventitem != 'undefined')
				{
					gdata[indx] = {
						'start': moment(eventitem.start).format('d HH:mm'),
						'end': moment(eventitem.end).format('d HH:mm'),
						'userid': ".$userid.",
					};
					indx++;
				}
			});
			
			// console.log(gdata);
			var senddata = JSON.stringify(gdata);
			console.log('Sending to Server: '+senddata);
			
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { weekdata:senddata },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					$('.qa-waiting').hide();
					console.log('server returned: '+data);
					if(data['updated']=='1')
					{
						$('.favoritemeholder').prepend('<p class=\'smsg\'>".qa_lang('booker_lang/week_updatesuccess')."<br />".qa_lang('booker_lang/reloadpage')."</p>');
						$('.smsg').fadeOut(3000, function()
						{
							window.location.href = '".qa_self_html()."';							 
						});
						eventchanged_fav = false; // no warn on leave
					}
				},
				error: function(xhr, status, error)
				{
					$('.qa-waiting').hide();
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
				}
				
			}); // end ajax
		});// end favoriteme click
		
		// absent times 
		$('#contractorabsentbutton').click( function(e) 
		{
			e.preventDefault();
			
			// show loading indicator
			$('.qa-waiting').show();
			
			var weekdays = '';
			$('.weekdaycheckbox').each( function() 
			{
				if($(this).prop('checked'))
				{
					weekdays += (weekdays.length==0?'':',')+$(this).val();
				}
			});
			
			var gdata = {
				weekdays: weekdays,
				contractorabsent: $('#contractorabsent').val(),
				userid: ".$userid.",
			};
			console.log(gdata);
			var senddata = JSON.stringify(gdata);
			
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { dataabsent:senddata },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					$('.qa-waiting').hide();
					console.log('server returned: '+data+' | message: '+data['updated']);
					if(data['updated']=='1')
					{
						$('.absentbuttonholder').prepend('<p class=\'smsg\'>".qa_lang('booker_lang/data_updated').". ".qa_lang('booker_lang/reloadingpage')."</p>');
						$('.smsg').fadeOut(3000, function()
						{
							window.location.href = '".qa_self_html()."';
						});
						eventchanged = false; // no warn on leave
					}
				},
				error: function(xhr, status, error)
				{
					$('.qa-waiting').hide();
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
				}
			}); // end ajax
		}); // end contractorabsentbutton

		var eventchanged_fav = false;
		$(window).bind('beforeunload', function(e){
			if(eventchanged_fav) {
				// $('.favoritemeholder').focus();
				$('html,body').animate({
				   scrollTop: $('.favoritemeholder').offset().top
				});

				return '".qa_lang('booker_lang/week_warnonleave')."';
			};
		});
		
		
		// google calender button 
		$('#gcalbutton').click( function(e) 
		{
			e.preventDefault();
			$('.qa-waiting').show();
			
			// show loading indicator
			$('.qa-waiting').show();
			
			var gdata = {
				gcallink: $('.gcal_url').val(),
				userid: ".$userid.",
			};
			console.log(gdata);
			var senddata = JSON.stringify(gdata);
			
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { gcaldata:senddata },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					$('.qa-waiting').hide();
					console.log('server returned: '+data+' | message: '+data['updated']);
					if(data['updated']=='1')
					{
						$('.gcalbuttonholder').prepend('<p class=\'smsg\'>".qa_lang('booker_lang/data_updated')." ".qa_lang('booker_lang/reloadingpage')."</p>');
						$('.smsg').fadeOut(3000, function()
						{
							window.location.href = '".qa_self_html()."';
						});
					}
				},
				error: function(xhr, status, error)
				{
					$('.qa-waiting').hide();
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
				}
			}); // end ajax
		}); // end contractorabsentbutton

		$('#copytoclip').click( function(e) { 
			e.preventDefault();
			copytoclipboard( $('#icscalurl').val() );
			// success message
			$(this).parent().append('<p class=\'smsg\' style=\'margin-top:10px;\'>".qa_lang('booker_lang/clipboard_copied')."</p>');
			$('.smsg').fadeOut(10000, function()
			{
				$(this).remove();
			});
		});
		
		
		function copytoclipboard(copytext) 
		{
			var temp = $('<input>')
			$('body').append(temp);
			temp.val(copytext).select();
			document.execCommand('copy');
			temp.remove();
		}
		
		// start up, check for anchor
		var hash = window.location.hash.substring(1);
		if(hash.length>0)
		{
			$('#'+hash).trigger('click');
			window.scrollTo(0, 0);
		}
		
	}); // end jquery ready

	Array.prototype.clean = function(deleteValue) {
	  for (var i = 0; i < this.length; i++) {
		if (this[i] == deleteValue) {         
		  this.splice(i, 1);
		  i--;
		}
	  }
	  return this;
	};
	
	var eventchanged = false;
	$(window).bind('beforeunload', function(e){
		if(eventchanged) {
			// $('.changemeholder').focus();
			$('html,body').animate({
			   scrollTop: $('.changemeholder').offset().top
			});

			return '".qa_lang('booker_lang/cal_warnonleave')."';
		};
	});


</script>
			";

			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main h2 {
					padding-top:0;
				}
				.qa-main h3 {
					margin:30px 0 10px 0;
				}
				.weekdayhighlight {
					background:#FFC;
				}
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:100%;
				}
				#icscalurl {
					min-width:240px;
				}
				#contractorabsent {
					min-width:300px;
				}
				.qa-main p {
					line-height:150%;
				}
				.box_import, .box_export, .box_timesetup {
					display:none;
				}
				#copytoclip {
					
				}
				
				.weekdaysavailable {
					display:inline-block;
					max-width:550px;
				}
				.weekdaysavailable label {
					margin-right:5px;
					background:#F5F5F7;
					padding:7px 5px 5px 5px;
					line-height:100%;
					border-radius:2px;
					border: 1px solid #EEE;
				}
				.weekdaysavailable label:hover {
					background:#DEF;
				}
				
				.calchoosetime {
					font-size:14px;
					margin-bottom:20px;
					max-width:740px;
				}
				.mousehints {
					margin-bottom:30px;
				}
				.generalweek {
					display:inline-block;
					margin-left:10px;
				}
				#calendar {
					max-width: 880px;
					margin: 0 0 20px 0;
				}
				.fc-view-container {
					background:#FFF;					
				}
				/*.fc-agendaWeek-button {
					display:none;
				}*/
				.bookingtablewrap {
					display:block;
					width:92%;
					font-size:13px;
				}
				#bookingtable {
					display:table;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
					width:90%;
					max-width:860px;
				}
				#bookingtable th {
					font-weight:normal;
					background:#FFC;
					text-align:left;
				}
				#bookingtable td, #bookingtable th
				{
					padding:10px 5px;
					border:1px solid #DDD;
					font-weight:normal;
				}
				.bookmehold, .bookmeholder {
					/*display:block;*/
					width:100%;
					text-align:right;
				}
				.booktime, .bookcalc, .booktimeabs, .booktimehours {
				}
				#sumrow td {
					font-weight:bold !important;
				}
				#bookme {
					display:inline-block;
					padding:10px 20px;
					margin:20px 0 100px 0;
					font-size:14px;
					color:#FFF;
					background:#0A0;
					border:1px solid #EEE;
					border-radius:0px;
					cursor:pointer;
				}
				#bookme:hover {
					background:#080;
				}
				.fc-event {
					font-size:0.95em;
				}
				#calendar .fc-past {
					background:#EEE;
				}
				.event-notavailable .fc-time {
					display:none;
				}
				
				.buttonlistwrap {
					margin:20px 0 50px 0;
					width:100%;
					font-size:13px;
					overflow: hidden;
				}
				.buttonlistwrap .defaultbutton {
					display: block;
					float: left;
					margin: 0 15px 5px 0;
					padding: 7px 15px;
				}
				
				.changeshint {
					color:#00F;
					font-size:15px;
				}
				.changemeholder {
					width:92%;
					text-align:right;
					margin-bottom:50px;
				}
				#changeme {
					padding:10px 20px;
				}
				.smsg {
					color:#00F;
				}
				.smsg-red {
					color:#F00;
					margin-top:4px;
				}
				
				#calendar-favorite {
					max-width: 880px;
					margin: 0 0 20px 0;
				}
				#calendar-favorite .fc-agendaWeek-button {
					display:none;
				}
				/* do not highlight today */
				#calendar-favorite .fc-today {
					background: #FFF !important;
				}
				
				.favoritemeholder {
					width:92%;
					text-align:right;
					margin-bottom:50px;
				}
				
				.gcal_url {
					width:350px;
					margin:10px 0 20px 0;
					padding:5px;					
				}
				.manual_image {
					border:1px solid #CCC;
					margin:10px 0 0 20px;
					padding:10px;
				}
				.ghints {
					margin-top:150px;
				}
				.ghints ol li {
					margin:30px 0;
				}
				.screenshotlabel {
					display:none;
					margin:10px 0 0 0;
				}

				/* smartphones */
				@media only screen and (max-width:480px) {
					.qa-main {
						width:95%;
					}
					.buttonlistwrap {
						width:90%;
					}
					.gcal_url {
						width:90%;
					}
					.manual_image {
						margin:10px 0 0 0;
					}
				}
				
			</style>';
			

			return $qa_content;
		} // END process_request
		
		
		function getcontractorabsent($userid)
		{
			// load userdata for display frontend
			$user = booker_getfulluserdata($userid);
			$contractorabsent = $user['absent'];
			$absentperiods = '';
			$absentweekdays = '';
			
			// in case we have weekday numbers, transform them to weekday name abbreviations
			if(!empty($contractorabsent))
			{
				$conabsdata = explode(',', $contractorabsent);
				foreach($conabsdata as $absent)
				{
					// weeknumber
					if(is_numeric($absent))
					{
						$absentweekdays .= $absent; // e.g. 167 which are Mon, Sat, Sun
					}
					else
					{
						if(empty($absentperiods))
						{
							$absentperiods .= $absent;
						}
						else
						{
							$absentperiods .= ', '.$absent;							
						}
					}
				}
				$contractorabsent = $absentperiods;
			}
			
			return array(
				'absent' => $contractorabsent, 
				'weekdays' => $absentweekdays
			);
		} // end getcontractorabsent

		/*
					<p>
						<a href="#" id="copytoclip">'.qa_lang('booker_lang/copylink').'</a>
						&ensp; | &ensp; 
						<a href="'.qa_path('ics').'?userid='.$userid.'">'.qa_lang('booker_lang/openlink').'</a>
					</p>
		*/
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
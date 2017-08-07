<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_eventmanager
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
					'title' => 'booker Page Event Manager', // title of page
					'request' => 'eventmanager', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='eventmanager')
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
				qa_set_template('booker eventmanager');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if(!isset($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker eventmanager');
				$qa_content['title'] = qa_lang('booker_lang/evman_title');
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}

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
			qa_set_template('booker eventmanager');
			$qa_content['title'] = qa_lang('booker_lang/evman_title');
			
			// transferred value
			$eventid = qa_get('id');
			
			if(empty($eventid))
			{
				if(booker_iscontracted($userid))
				{
					$eventlist = booker_list_findcontractor_events();

					$qa_content['title'] = qa_lang('booker_lang/events_available_title');
					$qa_content['custom'] = '';
					if(empty($eventlist))
					{
						$qa_content['custom'] .= '
						<p>
							'.qa_lang('booker_lang/events_allocated').'
						</p>
						';
					}
					else
					{
						$qa_content['custom'] .= '
						<p>
							'.qa_lang('booker_lang/events_available').'
						</p>
						';
						$qa_content['custom'] .= $eventlist;
					}
					return $qa_content;
				}
				else
				{
					// students cannot see list of available events, however, show their own events with findtime status 
					$qa_content['title'] = qa_lang('booker_lang/events_coordinate');

					$eventlist = booker_list_client_findtime_events($userid);

					$qa_content['custom'] = '';
					if(empty($eventlist))
					{
						$qa_content['custom'] .= '
						<p>
							'.qa_lang('booker_lang/events_allocated_client').'							
						</p>
						';
					}
					else
					{
						$qa_content['custom'] .= '
						<p>
							'.qa_lang('booker_lang/events_findtime').'
						</p>
						';
						$qa_content['custom'] .= $eventlist;
					}
					return $qa_content;
				}
			}
			
			// action!
			$eventaction = qa_get('action');
			if(!empty($eventaction))
			{
				$pagetitle = '';
				$actionoutput = '';
				
				// accept by contractor
				if($eventaction=='accept')
				{
					// check if already accepted, this prevents double logging
					$status = booker_get_status_event($eventid);
					if($status!=MB_EVENT_ACCEPTED)
					{
						booker_set_status_event($eventid, MB_EVENT_ACCEPTED);
						
						// assign the event to the contractor
						qa_db_query_sub('UPDATE `^booking_orders` SET contractorid = #
											WHERE eventid = #', 
											$userid, $eventid);
						
						$pagetitle = qa_lang('booker_lang/event_accepted_title');
						$actionoutput = '
							<p>
								'.str_replace('~eventid~', $eventid, qa_lang('booker_lang/event_accepted')).'
							</p>
							<p>
								<a class="defaultbutton" href="'.qa_path('contractorschedule').'">'.qa_lang('booker_lang/backtoeventlist').'</a>
							</p>
						';
						// log
						$eventname = booker_get_eventname(MB_EVENT_ACCEPTED);
						$params = array(
							'contractorid' => $userid
						);
						booker_log_event($userid, $eventid, $eventname, $params);
						
						// inform client by email that contractor accepted
						booker_sendmail_client_contractoraccepted($eventid);						
					}
					else
					{
						$pagetitle = qa_lang('booker_lang/event_hint_title');
						// add contractor name, the one who accepted the event (could also use the contractorid in table bookings)
						$eventlog = qa_db_read_one_value(
											qa_db_query_sub('SELECT params FROM `^booking_logs` 
																WHERE `eventid` = #
																AND eventname = #
																;', $eventid, 'contractor_accepted'),
															true);
						$toURL = str_replace("\t","&", $eventlog);
						parse_str($toURL, $params);
						
						$contractorname = booker_get_realname($params['contractorid']);
						
						$hintline = qa_lang('booker_lang/event_hint');
						$hintline = str_replace('~eventid~', $eventid, $hintline); 
						$hintline = str_replace('~name~', $contractorname, $hintline); 
						$hintline = strtr($hintline, array( 
									'^1' => '<a href="'.qa_path('mbmessages').'?to='.$params['contractorid'].'">',
									'^2' => '</a>'
									));
						
						$actionoutput = '
							<p>
								'.$hintline.'
							</p>
							<p>
								<a class="defaultbutton" href="'.qa_path('eventmanager').'?id='.$eventid.'">'.qa_lang('booker_lang/event_backdetails').'</a>
							</p>
						';
					}
				} // END $eventaction accept
				
				// reject by contractor
				else if($eventaction=='reject')
				{
					// check if already rejected by this contractor, this prevents double logging
					$alreadyrejected = booker_event_rejected_by_contractor($eventid, $userid);
					
					// if not yet rejected (or another contractor rejects), process the reject
					if(!$alreadyrejected)
					{
						$rejectreason = qa_post_text('rejectreason');
						$rejectreason = trim($rejectreason);
						if(empty($rejectreason))
						{
							$pagetitle = qa_lang('booker_lang/event_rejectreason');
							$actionoutput = '
								<form action="'.qa_self_html().'" method="post">
									<p>
										<input type="text" id="rejectreason" name="rejectreason" placeholder="'.qa_lang('booker_lang/rejectplaceholder').'" style="width:300px;" autofocus />
									</p>
									<button type="submit" class="defaultbutton">'.qa_lang('booker_lang/reject_btn').'</button>
								</form>
							';
						}
						else
						{
							// check if reject event is in the logs for this event, then do not inform other contractors by mail again
							$rejectcheck = qa_db_read_one_value(
												qa_db_query_sub('SELECT params FROM `^booking_logs` 
																	WHERE `eventid` = #
																	AND eventname = #
																	;', $eventid, 'contractor_rejected'),
																true);

							// log with reason
							$eventname = booker_get_eventname(MB_EVENT_REJECTED);
							$params = array(
								'contractorid' => $userid,
								'reason' => $rejectreason
							);
							booker_log_event($userid, $eventid, $eventname, $params);
							
							$pagetitle = qa_lang('booker_lang/event_rejected_title');
							$actionoutput = '
								<p>
									'.str_replace('~eventid~', $eventid, qa_lang('booker_lang/event_rejected')).'
								</p>
								<p>
									<a class="defaultbutton" href="'.qa_path('contractorschedule').'?id='.$eventid.'">'.qa_lang('booker_lang/backtoeventlist').'</a>
								</p>
							';
							
							if(is_null($rejectcheck))
							{
								// inform other contractors by email that there is an event availabe
								booker_sendmail_contractors_available_events($eventid);
								
								// inform client by email that contractor cannot hold the order
								booker_sendmail_client_contractorrejected($eventid);
								
								// set status MB_EVENT_NEEDED
								booker_set_status_event($eventid, MB_EVENT_NEEDED);
							}							
						}
					} // end !$alreadyrejected
					else 
					{
						$pagetitle = qa_lang('booker_lang/event_hint_title');
						$actionoutput = '
							<p>
								'.str_replace('~eventid~', $eventid, qa_lang('booker_lang/already_rejected')).'
							</p>
							<p>
								<a class="defaultbutton" href="'.qa_path('eventmanager').'?id='.$eventid.'">'.qa_lang('booker_lang/event_backdetails').'</a>
							</p>
						';
					}
				} // END $eventaction reject
				
				// change by contractor
				else if($eventaction=='change')
				{
					// make sure that the event is not completed yet but paid
					// only paid events can be modified in time!
					if(booker_event_is_paid($eventid))
					{
						// get post data, e.g. '17.03.2016 17:00;18.03.2016 18:00' or '2016.03.17 11:00'
						$proposedtimes = qa_post_text('proposedtimes');
						
						if(empty($proposedtimes))
						{
							$pagetitle = qa_lang('booker_lang/newtimeproposed_title');

							$eventdata = qa_db_read_one_assoc(
											qa_db_query_sub('SELECT starttime, endtime, unitprice FROM ^booking_orders 
																WHERE eventid = #
																', 
																$eventid), true
															);
							$eventdate = helper_get_readable_date_from_time($eventdata['starttime'], false);
							$eventtime = substr($eventdata['starttime'],11,5);
							$eventduration = (strtotime($eventdata['endtime']) - strtotime($eventdata['starttime']))/60; // min
							$actionoutput = '
								<p style="margin:40px 0 20px 0;">
									'.str_replace('~eventid~', $eventid, qa_lang('booker_lang/proposenewtime')).'
								</p>
								<p style="margin:0 0 30px 0;">
									'.str_replace('~eventduration~', $eventduration, qa_lang('booker_lang/eventduration')).'
								</p>
								
								<form action="" method="post">
									<div class="eventdataitem">
										<p>
											<span style="display:inline-block;width:70px;">'.qa_lang('booker_lang/date').':</span>
											<input type="text" id="proposeddate" placeholder="'.qa_lang('booker_lang/date').'" style="width:100px;" value="'.$eventdate.'" autofocus />
											<span style="display:inline-block;width:70px;margin-left:20px;">'.qa_lang('booker_lang/starttime').':</span>
											<input type="text" id="proposedtime" placeholder="'.qa_lang('booker_lang/starttime').'" style="width:60px;" value="'.$eventtime.'" autofocus />
										</p>
									</div>
									
									<input type="hidden" name="proposedtimes" id="proposedtimes" />
									
									<p style="margin-top:20px;">
										<a id="moreproposals" href="#">+ '.qa_lang('booker_lang/addmoreproposals').'</a>
									</p>
									
									<button type="submit" class="defaultbutton" style="margin-top:30px;">'.qa_lang('booker_lang/propose_btn').'</button>
								</form>
								
								<script type="text/javascript">
									$(document).ready(function(){
										$("#moreproposals").click( function(e)
										{
											e.preventDefault();
											$(".eventdataitem:last").clone().insertAfter(".eventdataitem:last");
										});
										$(document).on("change keyup", ".eventdataitem input", function(e)
										{
											var collect = "";
											$(".eventdataitem input").each( function()
											{ 
												if(collect.length==0)
												{
													collect += $(this).val();
												}
												else
												{
													if($(this).prop("id") == "proposeddate")
													{
														collect += ";"+$(this).val();	
													}
													else if($(this).prop("id") == "proposedtime")
													{
														// add time
														collect += " "+$(this).val();
													}
												}
											});
											$("#proposedtimes").val(collect);
										});
									});
								</script>
							';
						} // END empty($proposedtimes)
						else
						{
							// mark event so we know that time finding is active
							booker_set_status_event($eventid, MB_EVENT_FINDTIME);
							
							$eventdata = qa_db_read_one_assoc(
											qa_db_query_sub('SELECT starttime, endtime, unitprice FROM ^booking_orders 
																WHERE eventid = #
																', 
																$eventid), true
															);
							$starttime_former = $eventdata['starttime'];
							$eventduration = (strtotime($eventdata['endtime']) - strtotime($eventdata['starttime']))/60; // min
							
							// combine date and time to use them in one foreach
							$ptimes = explode(';', $proposedtimes);
							
							// go over all proposals
							foreach($ptimes as $ptime)
							{
								/*
								$cleandate = date('Y-m-d', strtotime($ptime));
								$cleantime = date('H:i', strtotime($ptime)).':00';
								$starttime_new = $cleandate.' '.$cleantime;
								*/
								$starttime_new = $cleandate = helper_localized_datetime_to_iso($ptime);
								
								// log proposal data
								$eventname = booker_get_eventname(MB_EVENT_FINDTIME);
								$params = array(
									'contractorid' => $userid,
									'starttime_former' => $starttime_former, 
									'starttime_new' => $starttime_new, 
									'duration' => $eventduration
								);
								booker_log_event($userid, $eventid, $eventname, $params);
							}
							
							$pagetitle = qa_lang('booker_lang/event_proposed_title');
							$actionoutput = '
								<p>
									'.str_replace('~eventid~', $eventid, qa_lang('booker_lang/event_proposed')).'
								</p>
								<p>
									<a class="defaultbutton" href="'.qa_path('contractorschedule').'?id='.$eventid.'">'.qa_lang('booker_lang/backtoeventlist').'</a>
								</p>
							';
							
							// inform client by email
							booker_sendmail_client_eventtime_suggestion($eventid);
							
						}						
					} // end booker_event_is_paid($eventid)
					else
					{
						$pagetitle = qa_lang('booker_lang/event_hint_title');
						$status = booker_get_status_event($eventid);
						if($status==MB_EVENT_COMPLETED)
						{
							$actionoutput = '
								<p>
									'.str_replace('~eventid~', $eventid, qa_lang('booker_lang/event_iscompleted')).'
								</p>
								<p>
									<a class="defaultbutton" href="'.qa_path('eventmanager').'?id='.$eventid.'">'.qa_lang('booker_lang/event_backdetails').'</a>
								</p>
							';
						}
						else if($status==MB_EVENT_OPEN || $status==MB_EVENT_RESERVED)
						{
							$actionoutput = '
								<p>
									'.str_replace('~eventid~', $eventid, qa_lang('booker_lang/event_notpaidyet')).'
								</p>
								<p>
									<a class="defaultbutton" href="'.qa_path('eventmanager').'?id='.$eventid.'">'.qa_lang('booker_lang/event_backdetails').'</a>
								</p>
							';
						}
					}
					
				} // END $eventaction change
				
				// accept by client
				else if($eventaction=='clientaccept')
				{
					// check if already accepted, this prevents double logging
					$status = booker_get_status_event($eventid);
					if($status!=MB_EVENT_ACCEPTED)
					{
						booker_set_status_event($eventid, MB_EVENT_ACCEPTED);

						$eventtime = qa_get('time');
						$contractorid = qa_get('contractorid');
						
						// assign the event to the contractor
						qa_db_query_sub('UPDATE `^booking_orders` SET contractorid = #
											WHERE eventid = #', 
											$contractorid, $eventid);
						
						$eventdata = qa_db_read_one_assoc(
										qa_db_query_sub('SELECT starttime, endtime FROM ^booking_orders 
															WHERE eventid = #
															', 
															$eventid), true
														);
						$starttime_former = $eventdata['starttime'];
						$eventduration = (strtotime($eventdata['endtime']) - strtotime($eventdata['starttime']))/60; // min

						$cleandate = date('Y-m-d', strtotime($eventtime));
						$cleantime = date('H:i', strtotime($eventtime)).':00';
						$starttime_new = $cleandate.' '.$cleantime;
						$endtime = strtotime($starttime_new)+$eventduration*60;
						$endtime_new = date('Y-m-d H:i', $endtime).':00';
						
						// log
						$eventname = booker_get_eventname(MB_EVENT_ACCEPTED);
						$params = array(
							'clientid' => $userid, 
							'contractorid' => $contractorid,
							'starttime_former' => $starttime_former, 
							'starttime_new' => $starttime_new, 
							'duration' => $eventduration
						);
						booker_log_event($userid, $eventid, $eventname, $params);
						
						// finally update the starttime in the eventdata
						qa_db_query_sub('UPDATE `^booking_orders` SET starttime = #, endtime = #
											WHERE eventid = #', 
											$starttime_new, $endtime_new, $eventid);
						
						$pagetitle = qa_lang('booker_lang/event_accepted_title');
						$actionoutput = '
							<p>
								'.str_replace('~eventid~', $eventid, qa_lang('booker_lang/event_accepted')).'
							</p>
							<p>
								<a class="defaultbutton" href="'.qa_path('clientschedule').'">'.qa_lang('booker_lang/backtoeventlist').'</a>
							</p>
						';
					}
					else
					{
						$pagetitle = qa_lang('booker_lang/event_hint_title');
						$actionoutput = '
							<p>
								'.str_replace('~eventid~', $eventid, qa_lang('booker_lang/event_alreadyaccepted')).'
							</p>
							<p>
								<a class="defaultbutton" href="'.qa_path('eventmanager').'?id='.$eventid.'">'.qa_lang('booker_lang/event_backdetails').'</a>
							</p>
						';
					}
				} // END $eventaction accept

				// output confirmation
				$qa_content = qa_content_prepare();
				qa_set_template('booker eventmanager');
				$qa_content['title'] = $pagetitle;
				$qa_content['custom'] = $actionoutput;
				return $qa_content;
			} // END !empty($eventaction)
			
			$qa_content['title'] = qa_lang('booker_lang/eventid').' '.$eventid;
			
			// init
			$qa_content['custom'] = '';
			
			// table of all entries of event
			$eventoutput = '';
			
			// get the event data
			$eventdata = qa_db_read_one_assoc( 
								qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, unitprice, needs, 
															attachment, payment, status, protocol
													FROM `^booking_orders` 
													WHERE eventid = #
													LIMIT 1
												;', $eventid), true
											);
			if(empty($eventdata))
			{
				$qa_content['error'] = qa_lang('booker_lang/event_notexist');
				return $qa_content;
			}
			$contractor_realname = booker_get_realname($eventdata['contractorid']);
			$contractor_skype = booker_get_userfield($eventdata['contractorid'], 'skype');
			$client_realname = booker_get_realname($eventdata['customerid']);
			$client_skype = booker_get_userfield($eventdata['customerid'], 'skype');
			
			$eventprice = booker_get_eventprice($eventid);
			
			$displayeventtime = '';
			$ev_start_date = helper_get_readable_date_from_time($eventdata['starttime'], false);
			$ev_start_time = helper_get_readable_date_from_time($eventdata['starttime'], true);
			$ev_end_date = helper_get_readable_date_from_time($eventdata['endtime'], false);
			$ev_end_time = helper_get_readable_date_from_time($eventdata['endtime'], true);
			// same day, then show only time of endtime
			if($ev_start_date == $ev_end_date)
			{
				$displayeventtime = '<b>'.helper_get_weekday($ev_start_date, true).' '.$ev_start_date.'</b><br />'.substr($ev_start_time, 11, 9).' - '.substr($ev_end_time, 11, 9);
			}
			
			$contractorout = '';
			if(booker_iscontracted($userid))
			{
				if($eventdata['contractorid']==$userid && $eventdata['status']<MB_EVENT_ACCEPTED)
				{
					$contractorout = '
						<label>
							<span>'.qa_lang('booker_lang/contractor_firstbooked').':</span>
							<span><a href="'.qa_path('mbmessages').'?to='.$eventdata['contractorid'].'">'.$contractor_realname.'</a></span>
						</label>
					';
				}
				else if($eventdata['status']==MB_EVENT_ACCEPTED)
				{
					$contractorout = '
						<label>
							<span>'.qa_lang('booker_lang/contractor').': </span>
							<span><a href="'.qa_path('mbmessages').'?to='.$eventdata['contractorid'].'">'.$contractor_realname.'</a></span>
						</label>
					';
				}
			}
			// is client
			else
			{
				$contractorout = '
					<label>
						<span>'.qa_lang('booker_lang/contractor').': </span>
						<span><a href="'.qa_path('mbmessages').'?to='.$eventdata['contractorid'].'">'.$contractor_realname.'</a></span>
					</label>
				';
			}
			
			// CSS for status headline
			if($eventdata['status']==MB_EVENT_ACCEPTED)
			{
				$eventoutput .= '
					<style type="text/css">
						.eventstatus {
							background:#A7FFA7 !important;
						}
					</style>
				';
			}
			else if($eventdata['status']==MB_EVENT_COMPLETED)
			{
				$eventoutput .= '
					<style type="text/css">
						.eventstatus {
							background:#575FA7 !important;
							color:#FFF;
						}
					</style>
				';
			}
			
			$eventoutput .= '
				<div class="eventinputs">
					<label class="eventstatus">
						<span>'.qa_lang('booker_lang/status').': </span>
						<span id="status" name="status">'.booker_get_logeventname(booker_get_eventname($eventdata['status']), false).'</span>
					</label>
					
					<label>
						<span>'.qa_lang('booker_lang/client').': </span>
						<span><a href="'.qa_path('mbmessages').'?to='.$eventdata['customerid'].'">'.$client_realname.'</a></span>
					</label>
					'.$contractorout.'
					<label>
						<span>'.qa_lang('booker_lang/eventtime').': </span>
						<span id="displayeventtime" name="displayeventtime">'.$displayeventtime.'</span>
					</label>
					<label>
						<span>'.qa_lang('booker_lang/eventdetails').': </span>
						<span id="needs" name="needs">'.$eventdata['needs'].'</span>
					</label>
			';
			
			if(!empty($eventdata['attachment']))
			{
				$eventoutput .= '
					<label>
						<span>'.qa_lang('booker_lang/attachments').': </span>
						<span id="attachment" name="attachment">'.helper_attachstring_to_list($eventdata['attachment']).'</span>
					</label>
				';
			}
			// show prices only to contractor
			if(booker_iscontracted($userid))
			{
				$eventoutput .= '
						<label>
							<span>'.qa_lang('booker_lang/fee').': </span>
							<span id="unitprice" name="unitprice">'.number_format($eventdata['unitprice'],2,',','.').' '.qa_opt('booker_currency').' '.qa_lang('booker_lang/per_hour').'</span>
						</label>
						<label>
							<span>Gesamt: </span>
							<span id="price" name="price">'.number_format($eventprice,2,',','.').' '.qa_opt('booker_currency').'</span>
						</label>
				';
			}
			$eventoutput .= '
				</div> <!-- eventinputs -->
			';

			$showbuttons = true;
			// if event has been accepted (does not matter who is the contractor), do not show action buttons
			$status = booker_get_status_event($eventid);
			if(booker_iscontracted($userid) && $status != MB_EVENT_ACCEPTED && $status != MB_EVENT_COMPLETED && $status != MB_EVENT_OPEN)
			{
				// if contractor has already rejected or accepted, do not show action buttons but event history
				$contractorevents = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT eventname FROM `^booking_logs` 
														WHERE `eventid` = #
														AND userid = #
														;', $eventid, $userid)
													);
				foreach($contractorevents as $tutev)
				{
					if($tutev['eventname']=='contractor_rejected' || $tutev['eventname']=='contractor_accepted')
					{
						$showbuttons = false;
						break;
					}
				}
				
				// protect from other contractor access if event is not yet decided by original contractor
				$eventcontractorid = qa_db_read_one_value(
									qa_db_query_sub('SELECT contractorid FROM `^booking_orders` 
														WHERE `eventid` = #
														', $eventid),
													true);

				if($userid != $eventcontractorid && $status <= MB_EVENT_FINDTIME) 
				{
					$showbuttons = false;
				}
				
				if($showbuttons)
				{
					// action buttons
					$eventoutput .= '
						<div class="actionbuttons">							
							<a class="defaultbutton buttongreenish" href="'.qa_path('eventmanager').'?id='.$eventid.'&action=accept">
								<i class="fa fa-check fa-lg"></i> '.qa_lang('booker_lang/accept_event_btn').'
							</a>
							<a class="defaultbutton buttongrayish" href="'.qa_path('eventmanager').'?id='.$eventid.'&action=change">
								<i class="fa fa-calendar fa-lg"></i> '.qa_lang('booker_lang/propose_event_btn').'
							</a>
							<a class="defaultbutton btn_red" style="border:1px solid #F00;" href="'.qa_path('eventmanager').'?id='.$eventid.'&action=reject">
								<i class="fa fa-times-circle fa-lg"></i> '.qa_lang('booker_lang/reject_event_btn').'
							</a>
						</div> <!-- actionbuttons -->
					';
				}
			}
			
			$qa_content['custom'] .= $eventoutput;

			
			// all logs to this eventid
			$eventlogs = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT datetime, userid, eventid, eventname, params
													FROM `^booking_logs` 
													WHERE eventid = #
													AND eventname IN("client_reserved", "client_paid", "contractor_accepted", "contractor_findtime", "contractor_rejected", "contractor_completed", "contractor_needed")
												;', $eventid)
											);
			
			// if user is client, display Terminvorschläge and accept option
			$clientact = '';
			if(!booker_iscontracted($userid) && ($status==MB_EVENT_NEEDED || $status==MB_EVENT_FINDTIME))
			{
				// get all event proposals
				$eventproposals = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT params FROM `^booking_logs` 
														WHERE `eventid` = #
														AND eventname = #
														;', $eventid, 'contractor_findtime')
													);
				$proposallist = '';
				if(!empty($eventproposals))
				{
					foreach($eventproposals as $evprop)
					{
						$toURL = str_replace("\t","&", $evprop['params']);
						parse_str($toURL, $params);
						// now we can access the following variables in array $params if they exist in toURL
						// $params['contractorid'], $params['starttime_former'], $params['starttime_new'], $params['duration']
						
						$contractorname = booker_get_realname($params['contractorid']);
						
						// only proposals of contractor who accepted
						$proposallist .= '<a href="'.qa_path('eventmanager').'?id='.$eventid.'&action=clientaccept&contractorid='.$params['contractorid'].'&time='.$params['starttime_new'].'" class="defaultbutton" style="margin-bottom:10px;">'.
											helper_get_weekday($params['starttime_new'],true).', '.helper_get_readable_date_from_time($params['starttime_new']).
										 '<br />
										 <span style="font-size:12px;">'.qa_lang('booker_lang/admincal_by').' '.$contractorname.'</span>'.
										 '</a>';
					}
				}
				
				$clientact .= '
					<h3>
						'.qa_lang('booker_lang/eventproposals').'
					</h3>
					<p style="color:#00F;margin-bottom:30px;">
						'.qa_lang('booker_lang/clickconfirm').': 
					</p>
				';
				$clientact .= $proposallist;
			}
			
			$logged = '';			
			foreach($eventlogs as $log)
			{
				$logged .= '
				<p class="eventline">'.
					'<b>'.booker_get_logeventname($log['eventname']).'</b> - '.
					'<span class="eventlogtime">'.helper_get_readable_date_from_time($log['datetime'], true).'</span> - <a class="eventloguser" target="_blank" href="'.qa_path('mbmessages').'?to='.$log['userid'].'">'.booker_get_realname($log['userid']).'</a> <br />'.
					'<span>'.booker_get_logeventparams($log['params'], $log['eventname'], $log['eventid']).'</span> 
				</p>
				';
			}
			if(!empty($logged))
			{
				$logged = '
					<h3>
						'.qa_lang('booker_lang/evhis_title').'
					</h3>'.$logged;
			}
			
			$qa_content['custom'] .= $clientact;
			$qa_content['custom'] .= $logged;
			

			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main {
					min-height:600px;
				}
				.eventstatus {
					background:#FFC;
				}
				.eventline {
					display:block;
					padding:10px;
					border:1px solid #DDD;
				}
				.eventline:nth-child(even) {
					background:#FFFFFF;
				}
				.eventline:nth-child(odd) {
					background:#F5F5F5;
				}
				.eventline:hover {
					background:#FE9;
				}
				.eventlogtime, .eventloguser {
					font-size:12px;
					color:#999 !important;
				}
				h1 {
					margin-bottom:40px;					
				}
				h3 {
					margin-top:50px;
				}
				.eventinputs label span {
					vertical-align:top;
				}
				.eventinputs label span:first-child {
					display:inline-block;
					width:120px;
				}
				.eventinputs label span:nth-child(2) {
					display:inline-block;
					width:250px;
				}
				.eventinputs label {
					display:block;
					max-width:450px;
					padding:10px;
					border:1px solid #CCC;
					border-bottom:0;
				}
				.eventinputs label:last-child {
					border-bottom:1px solid #CCC;
				}
				.eventinputs input:focus {
					background:#FFD;
				}
				.inputdisabled {
					pointer-events: none;
					background:#DDD;
				}
				.actionbuttons {
					margin:30px 0 30px 0;
				}
				input#rejectreason {
					width:300px;
					margin-top:10px;
				}
			</style>';
			
			
			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
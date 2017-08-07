<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_eventhistory
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
					'title' => 'booker Page Event History', // title of page
					'request' => 'eventhistory', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='eventhistory')
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
				qa_set_template('booker eventhistory');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if(!isset($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker eventhistory');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}

			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker eventhistory');
			$qa_content['title'] = qa_lang('booker_lang/evhis_title');
			
			// transferred value
			$eventid = qa_get('id');
			
			if(empty($eventid))
			{
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/put_eventid');
				return $qa_content;
			}
			
			// init
			$qa_content['custom'] = '';
			
			// all logs to event
			$eventlogs = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT datetime, userid, eventid, eventname, params
													FROM `^booking_logs` 
													WHERE eventid = #
												;', $eventid)
											);

			$qa_content['title'] = str_replace('~eventid~', $eventid, qa_lang('booker_lang/eventhistoryfor'));
						
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
			
			$qa_content['custom'] .= $logged;
			
			// table of all entries of event
			$eventoutput = '';
			
			// only 1 event
			$eventdata = qa_db_read_one_assoc( 
								qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, unitprice, needs, 
															attachment, payment, status, protocol
													FROM `^booking_orders` 
													WHERE eventid = #
													LIMIT 1
												;', $eventid)
											);

			$contractor_realname = booker_get_realname($eventdata['contractorid']);
			$contractor_skype = booker_get_userfield($eventdata['contractorid'], 'skype');
			$client_realname = booker_get_realname($eventdata['customerid']);
			$client_skype = booker_get_userfield($eventdata['customerid'], 'skype');
			
			$eventprice = booker_get_eventprice($eventid);
			
			$eventoutput .= '
				<div class="eventinputs">
					<label>
						<span>eventid</span>
						<span id="eventid" name="eventid">'.$eventdata['eventid'].'</span>
					</label>
					<label>
						<span>bookid</span>
						<span id="bookid" name="bookid">'.$eventdata['bookid'].'</span>
					</label>
					<label>
						<span>created</span>
						<span id="created" name="created">'.helper_get_readable_date_from_time($eventdata['created'], true).'</span>
					</label>
					<label>
						<span>contractorid</span>
						<span id="contractorid" name="contractorid">'.$eventdata['contractorid'].'</span>
						<span>&ensp;<a href="'.qa_path('mbmessages').'?to='.$eventdata['contractorid'].'">'.$contractor_realname.'</a></span>
					</label>
					<label>
						<span>starttime</span>
						<span id="starttime" name="starttime">'.helper_get_readable_date_from_time($eventdata['starttime'],true).'</span>
					</label>
					<label>
						<span>endtime</span>
						<span id="endtime" name="endtime">'.helper_get_readable_date_from_time($eventdata['endtime'],true).'</span>
						&ensp;<span id="duration" style="font-weight:bold;">'.
							((strtotime($eventdata['endtime'])-strtotime($eventdata['starttime']))/60).' '.qa_lang('booker_lang/min').'</span>
					</label>
					<label>
						<span>customerid</span>
						<span id="customerid" name="customerid">'.$eventdata['customerid'].'</span>
						<!--  &ensp;<span><a href="skype:'.$client_skype.'?chat">'.$client_realname.'</a></span> -->
						<span>&ensp;<a href="'.qa_path('mbmessages').'?to='.$eventdata['customerid'].'">'.$client_realname.'</a></span>
					</label>
					<label>
						<span>unitprice</span>
						<span id="unitprice" name="unitprice">'.$eventdata['unitprice'].' '.qa_opt('booker_currency').' '.qa_lang('booker_lang/per_hour').'</span>
						&ensp;<span id="price" name="price" style="font-weight:bold;">'.$eventprice.' '.qa_opt('booker_currency').' '.qa_lang('booker_lang/intotal').'</span>
					</label>
					<label>
						<span>details</span>
						<span id="needs" name="needs">'.$eventdata['needs'].'</span>
					</label>
					<label>
						<span>attachment</span>
						<span id="attachment" name="attachment">'.$eventdata['attachment'].'</span>
					</label>
					<label>
						<span>payment</span>
						<span id="payment" name="payment">'.$eventdata['payment'].'</span>
					</label>
					<label>
						<span>status</span>
						<span id="status" name="status">'.$eventdata['status'].'</span>
					</label>
					<label>
						<span>protocol</span>
						<span id="protocol" name="protocol">'.$eventdata['protocol'].'</span>
					</label>
				</div>
				';

			$qa_content['custom'] .= '
				<h3>
					'.qa_lang('booker_lang/recent_eventdata').'
				</h3>
			';
			$qa_content['custom'] .= $eventoutput;

			$qa_content['custom'] .= '
			<style type="text/css">
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
					margin-top:70px;
				}
				.eventinputs label span:first-child {
					display:inline-block;
					width:100px;
				}
				.eventinputs label span:nth-child(2) {
					display:inline-block;
					width:140px;
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
			</style>';
			
			
			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
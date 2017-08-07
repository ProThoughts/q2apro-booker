<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_admincalendar 
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
					'title' => 'booker Page AdminCalendar', // title of page
					'request' => 'admincalendar', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='admincalendar')
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
				qa_set_template('booker admincalendar');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker admincalendar');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}

			
			$transferString = qa_post_text('bookingtimes'); // holds array of changed events
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
				
				// go over event objects and check if times included
				foreach($newdata as $event) 
				{
					if(! (isset($event['start']) && isset($event['end'])) ) {
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
					// start is e.g. "2015-05-30T08:00:00.000Z", we need 2015-05-30 08:00:00
					$starttime = substr($event['start'],0,10).' '.substr($event['start'],11,8);
					$endtime = substr($event['end'],0,10).' '.substr($event['end'],11,8);
					
					$timedone = strtotime($endtime) - strtotime($starttime);
					$timedone = $timedone/60; // in min
					
					// get former event times
					$formerevent = qa_db_read_one_assoc(
										qa_db_query_sub('SELECT contractorid, starttime, endtime FROM `^booking_orders` 
														WHERE eventid = # 
														;', $eventid) 
									);
					// changer, change-date, starttime, endtime
					$changer = $userid; // ADMIN
					/*
					// obsolete
					$eventmeta = $changer.';'.date('Y-m-d').';'.$formerevent['starttime'].';'.$formerevent['endtime'].'\t';
					
					// append to meta field *** OBSOLETE
					qa_db_query_sub('UPDATE ^booking_orders 
										SET meta = CONCAT(IFNULL(meta,""), #) 
										WHERE eventid = #
										', 
										$eventmeta, $eventid 
									);
					*/
					// update event with new times
					qa_db_query_sub('UPDATE ^booking_orders 
										SET starttime=#, endtime=# 
											WHERE eventid = #
											', 
											$starttime, $endtime, $eventid
									);
									// SET starttime=FROM_UNIXTIME(#), endtime=FROM_UNIXTIME(#), timedone=# 
					
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
				} // end foreach

				// ajax return success
				$arrayBack = array(
					'updated' => '1'
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX RETURN (bookingtimes)


			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker admincalendar');
			$qa_content['title'] = qa_lang('booker_lang/admincal_title');
			
			// $weekstartday = date("Y-m-d", strtotime('monday this week')); // problem with php5.2
			$weekstartday = date('Y-m-d', strtotime(date('o-\\WW'))); 

			$bookedevents = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, unitprice, status FROM `^booking_orders` 
														WHERE starttime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
														ORDER BY starttime
														;') 
							);
												// WHERE status != 0
												// AND starttime >= CURDATE()
												// AND created >= DATE(NOW()) - INTERVAL 7 DAY
			
			// init
			$qa_content['custom'] = '';
			
			// get week schedule for all customer bookings
			$bookedx = '';
			foreach($bookedevents as $event) 
			{
				// customer handles in calendar
				// $eventcustomer = booker_get_realname($event['customerid']);
				$eventcustomer = booker_get_realname($event['customerid']);
				
				// calculate from starttime and endtime (status == reserviert)
				$eventduration = booker_get_timediff($event['starttime'], $event['endtime']);
				
				$eventcolor = '#FFF';
				
				// if(time()-$event['created']) {
				// open
				if($event['status']==MB_EVENT_OPEN)
				{
					$eventtitle = qa_lang('booker_lang/admincal_perhaps').' - '.$eventcustomer;
					$eventcolor = '#777';
				}
				// reserved (payment will be processed)
				else if($event['status']==MB_EVENT_RESERVED)
				{
					$eventtitle = qa_lang('booker_lang/admincal_unpaid').'\n'.qa_lang('booker_lang/admincal_from').' '.$eventcustomer; // reserviert
					$eventcolor = '#F77';
				}
				// paid
				// else if($event['status']==MB_EVENT_PAID || $event['status']==MB_EVENT_ACCEPTED)
				else if(booker_event_is_paid($event['eventid']))
				{
					$eventduration = (strtotime($event['endtime']) - strtotime($event['starttime']))/60;
					$eventtitle = qa_lang('booker_lang/booked').' ('.$eventduration.' min)\n'.qa_lang('booker_lang/admincal_from').' '.$eventcustomer;
					$eventcolor = '#66F';
				}
				// completed
				else if($event['status']==MB_EVENT_COMPLETED)
				{
					$eventtitle = qa_lang('booker_lang/completed').'\n'.$eventcustomer;
					$eventcolor = '#89C';
				}
				else 
				{
					$eventtitle = 'undefined status';
					$eventcolor = '#F00';
				}
				$eventtitle .= '\n'.qa_lang('booker_lang/admincal_by').' '.booker_get_realname($event['contractorid']);
				$eventtitle .= '\n'.qa_lang('booker_lang/price').': '.booker_get_eventprice($event['eventid'], true).' '.qa_opt('booker_currency');
				
				$skypeurl = '';
				if($event['status']>MB_EVENT_RESERVED) 
				{
					$clientskype = booker_get_userfield($event['customerid'], 'skype');
					// $eventtitle .= '\nSkype: '.$clientskype;
					$skypeurl = "url: './mbmessages?to=".$event['customerid']."', ";
				}
				
				// dont show eventuell dates
				if($event['status']!=MB_EVENT_OPEN)
				{
					$editable = 'false';
					
					// all events starting from last 48 hours are editable if not yet "completed"
					// if(strtotime($event['starttime']) > time()) {
					// if(strtotime($event['starttime']) > time()-60*60*48 && $event['status']==MB_EVENT_RESERVED || $event['status']==MB_EVENT_PAID )
					if(strtotime($event['starttime']) > time()-60*60*48 && $event['status']==MB_EVENT_RESERVED || booker_event_is_paid($event['eventid']) )
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
						".$skypeurl."
					},

					";
				}
				
			} // end foreach $bookedevents

			$qa_content['custom'] .= '<div id="calendar"></div>';
			
			$qa_content['custom'] .= '
				<div class="changemeholder">
					<button id="changeme" class="defaultbutton" type="submit">'.qa_lang('booker_lang/btnupdate_appt').'</button>
				</div>
			';
			
			
			// todays weekday
			$weekdaytoday = date('N', time());

			$defaultview = 'month'; // 'agendaWeek';
			
			$contractorprice = (float)(booker_get_userfield($userid, 'bookingprice'));
			
			// jquery
			$qa_content['custom'] .= "
<script>

	$(document).ready(function() {
		
		var allevents = new Array();
		var changedevents = new Array();
		var eventcount = 1;
		var contractorprice = ".$contractorprice.";
		
		$('#calendar').fullCalendar({
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
			/*
			minTime: '01:00:00',
			maxTime: '23:00:00',
			*/
			contentHeight: 600,
			events: [
				".$bookedx."
			],
			
			timezone: ('".qa_opt('booker_timezone')."'), // false, local, UTC, ('Europe/Berlin')
			
			views: {
				agendaWeek: {
					columnFormat: 'dddd D.M.' // display weekdays as e.g. Freitag 11.5.
				},
				month: {
					columnFormat: 'dddd'
				},				
			},
	
			eventRender: function(event, element, view ){
				// tooltip with all information
				var etooltip = (event.title).replace(/\\n/g, '<br />');
				element.attr('title', etooltip);
				// highlight dates holding events
				if(view.name=='agendaWeek') {
					var weekdays = new Array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
					var eventweekday = $.fullCalendar.moment(event.start).format('d');
					$('.fc-'+weekdays[eventweekday]).css('background-color', '#FFC');
					// $('.fc-'+weekdays[eventweekday]).addClass('weekdayhighlight');
				}
				if( (window.location.href).indexOf('localhost')==-1) {
					element.tipsy({ gravity:'s', html:true });
				}
			},
			// event time changes
			eventDrop: function(event, delta, revertFunc, jsEvent, ui, view) {
				eventchanged = true;
				
				// add to array
				// changedevents.push(event);
				changedevents[event.id] = event;
				
				/*if(!isValidBackgroundEvent(event.start,event.end)){
					revertFunc();
					return;
				}
				*/
			},
			// event time changes
			eventResize: function(event, delta, revertFunc, jsEvent, ui, view) {
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
				event.title = 'Neu: '+newtime+' min\\n'+'Preis: '+roundandcomma(newtime/60*contractorprice)+' ".qa_opt('booker_currency')."';
			},
			// click removes event
			eventClick: function(event){
				// no action on click
				return;
			}

		}); // end fullCalendar

		// check if event is in past
		var isValidEvent = function(start,end) {
			// *** not working yet
			// http://stackoverflow.com/a/29832834/1066234
			var check = start._d.toJSON().slice(0,10);
			var today = new Date().toJSON().slice(0,10);
			
			if(check >= today) {
				return true;
			}
			else {
				// Previous Day. show message if you want otherwise do nothing.
				// So it will be unselectable
				return false;
			}
		};
		
		// only add it if event is onto free expert timeslots
		var isValidBackgroundEvent = function(start,end){
			return $('#calendar').fullCalendar('clientEvents', function (event) {
				return (event.rendering === 'background' && //Add more conditions here if you only want to check against certain events
						(start.isAfter(event.start) || start.isSame(event.start,'minute')) &&
						(end.isBefore(event.end) || end.isSame(event.end,'minute')));
			}).length > 0;
		};

		
		// bookingtimes
		$('#changeme').click( function(e) {
			e.preventDefault();
			var gdata = [];
			var indx = 0;
			
			// remove empty slots from array
			changedevents.clean(undefined);
			// console.log(changedevents);
			
			if(changedevents.length==0) {
				alert('".qa_lang('booker_lang/admincal_nochanges')."');
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
			console.log('Sending to Server: ');
			console.log(senddata);
			
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { bookingtimes:senddata },
				dataType: 'json',
				cache: false,
				success: function(data) {
					console.log('server returned: '+data);
					// console.log('x' +data['updated']);
					if(data['updated']=='1') {
						$('.changemeholder').prepend('<p class=\'smsg\'>".qa_lang('booker_lang/updatesuccess')." <br />".qa_lang('booker_lang/reload')."</p>');
						$('.smsg').fadeOut(3000, function() { 
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
		});// end changeme click

		function roundandcomma(val) {
			// var precision = 2;
			// val = Math.round(val*precision)/precision;
			// x,00
			return String(val.toFixed(2) ).replace(/\./g, ',');
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
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:95%;
				}
				.qa-main p {
					line-height:150%;
				}
				#calendar {
					max-width: 880px;
					margin: 30px 0 20px 0;
				}
				.fc-view-container {
					background:#FFF;					
				}
				/*.fc-agendaWeek-button {
					display:none;
				}*/
				.bookingtablewrap {
					width:92%;
					display:block;
					text-align:right;
				}
				#bookingtable, #contractortable, #paymentstable {
					display:inline-block;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
				}
				#bookingtable th, #contractortable th, #paymentstable th {
					font-weight:normal;
					background:#FFC;
				}
				#bookingtable td, #bookingtable th, 
				#contractortable td, #contractortable th, 
				#paymentstable td, #paymentstable th
				{
					border:1px solid #CCC;
					/*font-size:14px;*/
					padding:3px 5px;
					border:1px solid #DDD;
					font-weight:normal;
					line-height:140%;
				}
				#bookingtable td {
					text-align:right;
				}
				#contractortable td:nth-child(3) {
					text-align:center;
				}
				#contractortable td:nth-child(4) {
					text-align:center;
				}
				#contractortable td:nth-child(5) {
					text-align:right;
				}
				.contractor_available {
					background:#FFF;
				}
				#paymentstable td:nth-child(6) {
					text-align:right;
				}
				
				.payonhold {
					background:#FCF;
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
				.fc-past {
					background:#EEE;
				}
				
				.adminlist {
					margin:20px 0 50px 0;
				}
				.adminlist caption {
					text-align:left;
					font-size:17px;
					margin:10px 0;
				}
				.adminlist th, .adminlist td {
					padding:2px 20px 2px 5px;
					border:1px solid #EEE;
					background:#FFF;
				}
				.adminlist .bookingcompleted td {
					background:#CCC;
					color:#999;
				}
				.adminlist .bookingcompleted td a {
					color:#999;
				}
				.adminlist th {
					background:#FF9;
				}
				.adminactions {
					font-size:10px;
				}
				.adminactions .paid, .adminactions .delete {
					cursor:pointer;				
				}
				.adminactions .paid, .adminactions .delete, .adminactions .completed {
					margin:0 5px 5px 5px;
					padding:5px;
					cursor:pointer;
					display:block;
				}
				.adminactions .paid:hover, .adminactions .delete:hover, .adminactions .completed:hover {
					background:#99F;
				}
				.adminactions .paid {
					background:#5C5;					
				}
				.adminactions .delete {
					background:#FCC;
				}
				.adminactions .completed {
					background:#DDF;
				}
				
				.gmfuserlink {
					font-size:10px;
					color:#555;
					cursor:pointer;
				}
				
				.ev_unitprice {
					font-size:10px;
				}
				.forumname {
					font-size:10px;
					color:#999 !important;
				}
				.skypelink {
					font-size:10px;
				}
				.contractormail, .postaladdress, .contractorservice {
					font-size:10px;
					color:#9A9;
				}
				
				.changemeholder {
					width:92%;
					text-align:right;
					margin-bottom:30px;
				}
				#changeme {
					padding:10px 20px;
				}

			</style>';
			

			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
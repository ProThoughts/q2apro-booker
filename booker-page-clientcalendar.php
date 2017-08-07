<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_clientcalendar 
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
					'title' => 'booker Page Client Dates', // title of page
					'request' => 'clientcalendar', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='clientcalendar')
			{
				return true;
			}
			return false;
		}

		function process_request($request) {
		
			if(!qa_opt('booker_enabled'))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker clientcalendar');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker clientcalendar');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker clientcalendar');
			$qa_content['title'] = qa_lang('booker_lang/clical_title');

			// super admin can have view of others for profile if adding a userid=x to the URL
			if(qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN)
			{
				$userid = qa_get('userid');
				if(empty($userid))
				{
					$userid = qa_get_logged_in_userid();
				}
			}
			
			$calinstructions = helper_get_calendar_instructions();
			
			
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
				</div> <!-- buttonlistwrap -->
				
				<div class="box_import">
					<h2>
						'.qa_lang('booker_lang/google_connect').'
					</h2>
					<p>
						'.
						strtr( qa_lang('booker_lang/google_hint_client'), array( 
							'^1' => '<a target="_blank" href="https://www.google.com/calendar/">',
							'^2' => '</a>'
						)).
						'
					</p>
					<p>
						'.qa_lang('booker_lang/google_nodetails').'
					</p>
					<div>
						<input type="text" placeholder="'.qa_lang('booker_lang/googlecal_link').'" class="gcal_url" value="'.booker_get_userfield($userid, 'externalcal').'" />
						<br />
						<div class="gcalbuttonholder">
							<button id="gcalbutton" class="defaultbutton" style="padding:8px 12px;">
								'.qa_lang('booker_lang/save_btn').'
							</button>
							<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
						</div>
					</div>
					
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
				
			';
			
			
			
			/*
			$qa_content['custom'] .= '
				<p>
					'.qa_lang('booker_lang/embedcalendar').': <a href="'.q2apro_site_url().'ics?userid='.$userid.'">'.q2apro_site_url().'ics?userid='.$userid.'</a>
				</p>
			';
			*/

			$weekdays = helper_get_weekdayarray();

			// get all events of the client (reserved and paid)
			$bookedevents_cal = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, unitprice, status FROM `^booking_orders` 
														WHERE customerid = # 
														AND status > #
														ORDER BY starttime
														;', $userid, MB_EVENT_OPEN)
							);

			// get week schedule for all customer bookings
			$bookedx = '';
			$contractorids = array();
			foreach($bookedevents_cal as $event)
			{
				// customer handles in calendar
				// $eventcustomer = booker_get_realname($event['customerid']);
				// $eventcustomer = booker_get_userfield($event['customerid'], 'realname');
				
				// calculate from starttime and endtime (status == reserviert)
				$eventduration = booker_get_timediff($event['starttime'], $event['endtime']);

				// css for highlighting row 
				$hcss = '';
				// open
				if($event['status']==MB_EVENT_OPEN)
				{
					// open, excluded by mysql
					// $eventtitle = 'eventuell vergeben';
					// $eventcolor = '#777';
				}
				// reserved (payment will be processed)
				else if($event['status']==MB_EVENT_RESERVED)
				{
					$eventtitle = qa_lang('booker_lang/notpaid_pay');
					$eventcolor = '#F77';
					$hcss = ' class="orderreserved"';
				}
				// paid
				// else if($event['status']==MB_EVENT_PAID)
				else if(booker_event_is_paid($event['eventid']))
				{
					$eventtitle = qa_lang('booker_lang/booked');
					$eventcolor = '#66F';
					$hcss = ' class="orderpaid"';
				}
				// completed
				else if($event['status']==MB_EVENT_COMPLETED)
				{
					$eventtitle = qa_lang('booker_lang/event_completed');
					$eventcolor = '#89C';
					$hcss = ' class="orderdone"';
				}
				
				// customer handles in calendar
				$contractorname = booker_get_realname($event['contractorid']);
				
				$eventtitle .= '\n'.qa_lang('booker_lang/contractor').': '.$contractorname;
				$eventtitle .= '\n'.qa_lang('booker_lang/price').': '.booker_get_eventprice($event['eventid'], true).' '.qa_opt('booker_currency');
				
				/*if(booker_event_is_paid($event['eventid']) || $event['status']==MB_EVENT_COMPLETED)
				{
					$eventtitle .= '\nSkype: '.booker_get_userfield($event['contractorid'], 'skype');
				}*/
				
				$clicklink = '';
				// not paid yet
				if($event['status']==MB_EVENT_RESERVED)
				{
					$clicklink = 'url: "'.q2apro_site_url().'pay?bookid='.$event['bookid'].'", ';
				}
				else if ($event['status']>MB_EVENT_RESERVED) 
				{
					// paid, so link to contact
					// $clicklink = 'url: "skype:'.booker_get_userfield($event['contractorid'], 'skype').'?chat", ';
					/*
					$clicklink = 'url: "./mbmessages?to='.$event['contractorid'].'", ';
					*/
					array_push($contractorids, $event['contractorid']);
				}
				
				
				// js full calendar
				$bookedx .= "
				{
					title: '".$eventtitle."',
					start: '".$event['starttime']."',
					end: '".$event['endtime']."',
					editable: false,
					color: '".$eventcolor."',
					".$clicklink."
				},

				";
			} // end foreach $bookedevents
			
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
			
			if(count($contractorids)>0)
			{
				// remove duplicats
				$contractorids = array_unique($contractorids);
				
				// list of recent clients to contact
				$qa_content['custom'] .= '
					<h3>
						'.qa_lang('booker_lang/listclients').'
					</h3>
				';
				
				$contactlist = '
					<ol>
				';
				
				foreach($contractorids as $tutid)
				{
					$contactlist .= '
						<li>
							<a target="_blank" href="'.qa_path('mbmessages').'?to='.$tutid.'">'.booker_get_realname($tutid).'</a>
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
			
			// $weekstartday = date("Y-m-d", strtotime('monday this week')); // problem with php5.2
			$weekstartday = date('Y-m-d',strtotime(date('o-\\WW'))); 

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
		
		var allevents = new Array();
		var eventcount = 1;
		
		$('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'agendaDay,agendaWeek,month'
			},
			// defaultDate: '".$weekstartday."',
			editable: false,
			// eventLimit: true, // allow more link when too many events
			// firstDay: (new Date().getDay()),
			// firstDay: ".$weekdaytoday.",
			defaultView: '".$defaultview."',
			timeFormat: 'HH:mm',
			locale: '".qa_opt('booker_language')."',
			// columnFormat: 'dddd D.M.',
			allDaySlot: false,
			slotDuration: '00:30:00', // default is 30 min
			/*
			minTime: '01:00:00',
			maxTime: '23:00:00',
			*/
			contentHeight: 600,
 			"
			.$eventsource.
			"
			
			// user can add more booking events
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
				var title = 'Diesen Zeitraum buchen ('+diffmin+' min)';
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
			
			timezone: 'local',
			// timezone: ('Europe/Berlin'), // false, local, UTC, ('Europe/Berlin')
			
			views: {
				agendaWeek: {
					columnFormat: 'dddd D.M.' // display weekdays as e.g. Freitag 11.5.
				},
				month: {
					columnFormat: 'dddd'
				},				
			},
	
			eventRender: function(event, element, view ){
				// add time labels to background times
				if(event.rendering === 'background'){
					// add some text or html to the event element.
					element.append( event.start.format('HH:mm')+' - '+event.end.format('HH:mm'));
				}
				// tooltip with all information
				// var etooltip = (event.title).replace(/\\n/g, '<br />');
				// element.attr('title', etooltip);
				
				// highlight dates holding events
				if(view.name=='agendaWeek')
				{
					var weekdays = new Array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
					var eventweekday = $.fullCalendar.moment(event.start).format('d');
					// $('.fc-'+weekdays[eventweekday]).css('background-color', '#FFC');
					// $('.fc-'+weekdays[eventweekday]).addClass('weekdayhighlight');
				}
				// tooltip for event hover
				if( (window.location.href).indexOf('localhost')==-1) 
				{
					element.tipsy({ gravity:'s', html:true });
				}
			},
			// event time changes
			eventDrop: function(event, delta, revertFunc, jsEvent, ui, view) {
				if(!isValidBackgroundEvent(event.start,event.end)){
					revertFunc();
					return;
				}
			},
			// event time changes
			eventResize: function(event, delta, revertFunc, jsEvent, ui, view) {
				// alert(event.title + ' end is now ' + event.end.format());
				if(!isValidBackgroundEvent(event.start,event.end)){
					revertFunc();
					return;
				}
			},
			// click removes event
			eventClick: function(event){
				if(event.editable == false) {
					return;
				}
				
				// remove from prices
				$('#event'+event.id).remove();
				
				// remove from array
				// allevents.splice(event.id, 1);
				// eventcount--;
				allevents[event.id] = null;
				
				// remove from calendar
				$('#calendar').fullCalendar('removeEvents',event._id);
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
		var isValidBackgroundEvent = function(start,end){
			return $('#calendar').fullCalendar('clientEvents', function (event) {
				return (event.rendering === 'background' && //Add more conditions here if you only want to check against certain events
						(start.isAfter(event.start) || start.isSame(event.start,'minute')) &&
						(end.isBefore(event.end) || end.isSame(event.end,'minute')));
			}).length > 0;
		};

		$('#showcalendar, #showimport, #showexport').click( function(e) 
		{
			e.preventDefault();
			$('#showcalendar, #showimport, #showexport').addClass('btn_graylight');
			$(this).removeClass('btn_graylight');
			
			$('.box_calendar, .box_import, .box_export').hide();
			
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
			
			// remember choice in URL
			window.history.pushState('menu', '', '".$request."#'+clickid);
		});
		
		// start up, check for anchor
		var hash = window.location.hash.substring(1);
		if(hash.length>0)
		{
			$('#'+hash).trigger('click');
			window.scrollTo(0, 0);
		}
		
	}); // end jquery ready

</script>
			";

			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main h3 {
					margin:50px 0 10px 0;
				}
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:100%;
				}
				.qa-main p {
					line-height:150%;
				}
				#icscalurl {
					min-width:240px;
				}
				.box_import, .box_export, .box_timesetup {
					display:none;
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
				.bookingtablewrap, .honorartablewrap, .paytablewrap {
					display:block;
					width:92%;
					font-size:13px;
				}
				#bookingtable, #honorartable, #paytable {
					display:inline-block;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
					width:100%;
					max-width:800px;
				}
				#bookingtable th, #honorartable th, #paytable th {
					font-weight:normal;
					background:#FFC;
				}
				#bookingtable td, #bookingtable th, 
				#honorartable td, #honorartable th,
				#paytable td, #paytable th {
					padding:3px 5px;
					border:1px solid #DDD;
					font-weight:normal;
					line-height:150%;
				}
				#honorartable td:nth-child(3), #honorartable td:nth-child(4), #honorartable td:nth-child(5), 
				#honorartable td:nth-child(6) {
					text-align:right;
				}
				
				#paytable td:nth-child(3) {
					text-align:right;
				}
				
				.orderreserved {
					/*background:#EEF0F0;*/
					background:#FFF;
				}
				.orderpaid {
					/*background:#FFC;*/
					background:#FFF;
				}
				.orderdone {
					background:#F5F5E5;
				}
				.payline {
					background:#FFF;
				}
				
				.buttonlistwrap {
					margin:20px 0 50px 0;
					width:100%;
					font-size:13px;
				}
				.buttonlistwrap .defaultbutton {
					padding:7px 15px;
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
		}

	};
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
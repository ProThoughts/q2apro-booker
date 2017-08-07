<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_contractorweek
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
					'title' => 'booker Page Contractor Week', // title of page
					'request' => 'contractorweek', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='contractorweek') 
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
				qa_set_template('booker contractorweek');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$isadmin = qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN;
			
			$userid = qa_get_logged_in_userid();
			
			// only gmf members can book
			if(empty($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker contractorweek');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			// super admin can have view of others for profile if adding a userid=x to the URL
			if($isadmin) 
			{
				$userid = qa_get('userid');
				if(empty($userid))
				{
					$userid = qa_get_logged_in_userid();
				}
			}
			
			// get userdata
			$userdata = qa_db_read_one_assoc(qa_db_query_sub('SELECT handle,avatarblobid FROM ^users 
																WHERE userid = #', 
																$userid));
			$imgsize = 250;
			if(isset($userdata['avatarblobid']))
			{
				$avatar = './?qa=image&qa_blobid='.$userdata['avatarblobid'].'&qa_size='.$imgsize;
			}
			else 
			{
				$avatar = './?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size='.$imgsize;
			}
			$userprofilelink = qa_path('user/'.$userdata['handle']);
			$contractorname = booker_get_realname($userid);
			
			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker contractorweek');
			$qa_content['title'] = qa_lang('booker_lang/conweek_title');

			// init
			$qa_content['custom'] = '';
			// $qa_content['custom'] .= $errormsg;

			$weekschedule = qa_db_read_all_assoc( 
							qa_db_query_sub('SELECT weekday, starttime, endtime FROM `^booking_week` 
														WHERE `userid` = #
														ORDER BY weekday
														;', $userid) );
			
			// get week schedule
			$weekdays = helper_get_weekdayname_array();
			$weekevents = '';
			$weeksched = '<div class="weekbox">'.get_contractorweektimes($weekschedule).'</div>';
			$earliesttime = 0;
			
			// * memo: can happen that $times['endtime'] < $times['starttime'] (00:00 < 20:00)
			foreach($weekschedule as $times) 
			{
				$wkday = $times['weekday'];
				// 01 september is monday, so we can insert the weekday as day number
				// js full calendar
				$weekevents .= "
				{
					title: '',
					start: '2014-09-0".$wkday."T".$times['starttime']."',
					end: '2014-09-0".$wkday."T".$times['endtime']."',
					dow: [".$wkday."], // repeat same weekday
					// rendering: 'background', 
					// color: '#0A0'
					// color: '#6BA5C2'
				},

				";
				// rendering background: // http://fullcalendar.io/docs/event_rendering/Background_Events/
				
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
			
			// $weekstartday = date("Y-m-d", strtotime('monday this week')); // problem with php5.2
			$weekstartday = date('Y-m-d',strtotime(date('o-\\WW'))); 

			$qa_content['custom'] .= '
				<div class="dividerline"></div>
			';
			
			
			$qa_content['custom'] .= '
				<h2>
					'.qa_lang('booker_lang/favoritetimes').'
				</h2>
			';
			$qa_content['custom'] .= $weeksched;

			$qa_content['custom'] .= '
				<p>
					'.qa_lang('booker_lang/week_hint1').'
					<br />
					'.qa_lang('booker_lang/week_hint2').'
					<br />
					'.qa_lang('booker_lang/week_hint3').'
				</p>
				';
			$qa_content['custom'] .= '
				<div class="selectionpreview">
					
				</div>
				';
			$qa_content['custom'] .= '<div id="calendar2"></div>';
				
			$qa_content['custom'] .= '
				<div class="changemeholder">
					<button id="changeme" class="defaultbutton" type="submit">'.qa_lang('booker_lang/saveweek_btn').'</button>
				</div>
			';

			// jquery
			$qa_content['custom'] .= "
<script>

	$(document).ready(function() {
		
		var eventcount = 1;
		var allevents = new Array();
		
		$('#calendar').fullCalendar(
		{
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'agendaWeek'
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
			/*
			minTime: '00:00:00',
			maxTime: '23:00:00',
			*/
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
				// only add event if it is onto free contractor timeslots
				if(!isValidBackgroundEvent(start,end)) 
				{
					$('#calendar').fullCalendar('unselect');
					return;
				}
				// quick calc
				var diffmin = (new Date(end).getTime()/1000 - new Date(start).getTime()/1000)/60;
				var title = diffmin+' min';
				var eventData;
				if(title)
				{
					// special: some users click 1 slot, then the following, so we have 2 events each 30 min instead of 60 min
					// merge both events into one
					var eventmerge = false;
					$.each(allevents, function( index, eventitem ) 
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
								var eventsarr = $('#calendar').fullCalendar('clientEvents');
								$.each(eventsarr, function(key, eventobj) { 
									if(eventobj._id == eventitem.id) {
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
						// console.log('adding event id: '+eventcount);
						eventData = {
							id: eventcount, // identifier
							title: title,
							start: start,
							end: end,
							// color: '#00AA00',
							editable: true,
							eventDurationEditable: true,
						};
						
						// register event in array
						allevents[eventcount] = eventData;
						eventcount++;
						// console.log(allevents);
						$('#calendar').fullCalendar('renderEvent', eventData, true);
					}

					// console.log(start, end);
					// setTimePrice(eventData);
				}
				$('#calendar').fullCalendar('unselect');
			},
			selectOverlap: function(event) 
			{
				return event.rendering === 'background';
			}, 
			// timezone: 'local',
			eventAfterAllRender: function()
			{
				// *** get existing events and put them into allevents for merging purposes
				// allevents = $('#calendar').fullCalendar('clientEvents');
				// eventcount = allevents.length;
			},
			
			// event time changes
			eventDrop: function(event, delta, revertFunc, jsEvent, ui, view) {
				if(!isValidBackgroundEvent(event.start,event.end))
				{
					revertFunc();
					return;
				}
				// setTimePrice(event);
				eventchanged = true;
			},
			
			// event time changes
			eventResize: function(event, delta, revertFunc, jsEvent, ui, view) {
				// alert(event.title + ' end is now ' + event.end.format());
				if(!isValidBackgroundEvent(event.start,event.end))
				{
					revertFunc();
					return;
				}
				// setTimePrice(event);
				eventchanged = true;
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
				
				// recalculate total sum
				// calculateTotal();
				
				// remove from calendar
				$('#calendar').fullCalendar('removeEvents',event._id);
				eventchanged = true;
			}

		}); // end fullCalendar
		
		// only add it if event is onto free contractor timeslots
		var isValidBackgroundEvent = function(start,end)
		{
			return true;
		};

		// weekdata
		$('#changeme').click( function(e) 
		{
			e.preventDefault();
			var gdata = [];
			var indx = 0;
			var weekevents = $('#calendar').fullCalendar('clientEvents');
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
				success: function(data) {
					console.log('server returned: '+data);
					console.log('x' +data['updated']);
					if(data['updated']=='1') {
						$('.changemeholder').prepend('<p class=\'smsg\'>".qa_lang('booker_lang/week_updatesuccess')."<br />".qa_lang('booker_lang/reloadpage')."</p>');
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
				url: '".qa_path('contractorweek')."',
				data: { dataabsent:senddata },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					$('.qa-waiting').hide();
					console.log('server returned: '+data+' | message: '+data['message']);
					if(data['message']=='updated')
					{
						$('#contractorabsent').val(data['contractorabsent']);
						$('<p class=\"smsg\">✓ ".qa_lang('booker_lang/data_updated')."</p>').insertAfter('.contractorabsentbutton');
						$('.smsg').fadeOut(2000, function()
						{ 
							$(this).remove();
							window.scrollTo(0, 0);
						});
					}
				},
				error: function(xhr, status, error) {
					$('.qa-waiting').hide();
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
				}
			}); // end ajax
		}); // end contractorabsentbutton


		var eventchanged = false;
		$(window).bind('beforeunload', function(e){
			if(eventchanged) {
				// $('.changemeholder').focus();
				$('html,body').animate({
				   scrollTop: $('.changemeholder').offset().top
				});

				return '".qa_lang('booker_lang/week_warnonleave')."';
			};
		});
		
	}); // end jquery ready

</script>
			";
			// <div class=\"contractortooltip\" title=\"19 % Umsatzsteuer für den Staat\">1,19 USt</div> · 
			
			// memo: toISOString(); https://reinteractive.net/posts/29-javascript-fullcalendar-time-zone-support-with-rails
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.weekbox {
					float:right;
					margin-right:80px;
					border:1px solid #EEE;
					padding:10px;
					background:#FFF;
				}
				.fc-toolbar {
					display:none;
				}
				.smsg {
					color:#00F;
				}
				.smsg-red {
					color:#F00;
					margin-top:4px;
				}
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:100%;
				}
				.qa-main input {
					padding:5px;
					border:1px solid #DDD;
					background:#FFF;
				}
				.qa-main p {
					line-height:150%;
				}
				
				.contractorholidays, .weekdaysavailable {
					display:inline-block;
					max-width:410px;
				}
				.weekdaysavailable label {
					margin-right:5px;
					background:#F5F5F7;
					padding:7px 5px 5px 5px;
					line-height:100%;
					border-radius:2px;
				}
				.weekdaysavailable label:hover {
					background:#DEF;
				}
				.dividerline {
					padding-bottom:50px;
					margin-bottom:30px;
					border-bottom:4px solid #EEE;
				}
				#contractorabsent {
					width:90%;
					min-width:200px;
					margin-right:10px;
				}
				.showselpreview {
					display:inline-block;
					margin-top:5px;
					color:#55F;
					border-bottom:1px solid #BBF;
					line-height:120%;
					cursor:pointer;
				}
				
				.generalweek {
					display:inline-block;
					vertical-align:top;
					/*margin-left:50px;*/
					float:right;
					margin:0 100px 20px 0;
					padding: 10px 15px 0px 15px;
					background:#FFF8AB; /* #FDFCEA;*/
					border: 1px solid #EEE;
				}
				#calendar {
					max-width: 880px;
					margin: 0 0 20px 0;
				}
				.fc-agendaWeek-button {
					display:none;
				}
				.changemeholder {
					width:92%;
					text-align:right;
					margin-bottom:50px;
				}
				.booktime, .bookcalc, .booktimeabs, .booktimehours {
				}
				#sumrow td {
					font-weight:bold !important;
				}
				.booklistbtnholder {
					position:absolute;
					right:40px;
					top:0;
					text-align:right;
				}
				.booklistbutton {
					display:inline-block;
					padding:10px 20px;
					margin:10px 0 0 0;
					font-size:14px;
					color:#FFF;
					background:#0A0;
					border:1px solid #EEE;
					border-radius:0px;
					cursor:pointer;
				}
				.booklistbutton {
					/*float:right;
					margin:0 80px 0 0;
					*/
				}

				#changeme {
					padding:10px 20px;
				}
				.fc-view-container {
					background:#FFF;
				}
				/* do not highlight today */
				.fc-unthemed .fc-today {
					background: #FFF;
				}
				.selectionpreview {
					margin:20px 0 40px 0;
				}
				
				.contractortooltip {
					border-bottom:1px solid #CCC;
					display:inline;
					cursor:help;
				}
				
				.qa-main h3 {
					font-size:22px;
					font-weight:normal;
				}

				.contractorbutton {
					display:inline-block;
					padding:7px 14px;
					margin-right: 20px;
					font-size:14px;
					color:#FFF;
					background:#38F;
					border:1px solid #EEE;
					border-radius:0px;
				}
				
				.contractorabsenthint {
					font-size:12px;
					color:#999;
				}
				
				/* smartphones */
				@media only screen and (max-width:480px) {
					.qa-main {
						width:95%;
					}
					.booklistbutton {
						float:none;
						margin:10px 0;
					}
					.generalweek {
						float:none;
					}
					.booklistbtnholder {
						position:static;
						text-align:left;
					}
					#service {
						width:200px;
					}
					.calchoosetime {
						width:95%;
					}
				}

			</style>';

			return $qa_content;
		}
		
	}; // end class booker_page_contractorweek
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
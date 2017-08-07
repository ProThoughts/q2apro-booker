<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_contractorschedule
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
					'title' => 'booker Page Contractor schedule', // title of page
					'request' => 'contractorschedule', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='contractorschedule')
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
				qa_set_template('booker contractorschedule');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			$userid = qa_get_logged_in_userid();

			// only members can access
			if(empty($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker contractorschedule');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
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


			// HANDLE AJAX - confirm complete button
			$transferString = qa_post_text('completedata');
			if(isset($transferString))
			{
				header('Content-Type: application/json; charset=UTF-8');

				$newdata = json_decode($transferString, true);
				$event = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				// go over event objects and save data
				if(isset($event['eventid']))
				{
					$eventid = $event['eventid'];
					$event_details = !empty($event['needs']) ? trim($event['needs']) : NULL;
					$event_protocol = !empty($event['protocol']) ? trim($event['protocol']) : NULL;

					// can only mark as completed if event was paid and job details + protocol are set
					if(!booker_event_is_paid($eventid) || empty($event_details) || empty($event_protocol))
					{
						// throw error
						// ajax return success
						$arrayBack = array(
							'updated' => 'error'
						);
						echo json_encode($arrayBack);
					}
					else
					{
						qa_db_query_sub('UPDATE `^booking_orders` SET needs = #, protocol = #
											WHERE eventid = #
											',
											$event_details, $event_protocol, $eventid);
						$contractorid = $userid;
						booker_set_status_event($eventid, MB_EVENT_COMPLETED);
						$this->booker_sendmail_order_completed($eventid, $contractorid);

						// LOG
						$eventname = booker_get_eventname(MB_EVENT_COMPLETED);
						$params = array(
							'details' => $event_details,
							'protocol' => $event_protocol
						);
						booker_log_event($contractorid, $eventid, $eventname, $params);

						// ajax return success
						$arrayBack = array(
							'updated' => '1'
						);
						echo json_encode($arrayBack);
					}
				}

				exit();
			} // END AJAX RETURN (completedata)



			// HANDLE AJAX
			$transferString = qa_post_text('orderdata');
			if(isset($transferString))
			{
				header('Content-Type: application/json; charset=UTF-8');

				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				// go over event objects and save data
				foreach($newdata as $event)
				{
					if(isset($event['eventid']))
					{
						$eventid = $event['eventid'];
						$event_details = !empty($event['needs']) ? trim($event['needs']) : NULL;
						$event_protocol = !empty($event['protocol']) ? trim($event['protocol']) : NULL;

						// only update job details if NOT empty
						if(!empty($event_details))
						{
							qa_db_query_sub('UPDATE `^booking_orders` SET needs = #
												WHERE eventid = #
												',
												$event_details, $eventid);
						}
						// only update protocol if NOT empty
						if(!empty($event_protocol))
						{
							qa_db_query_sub('UPDATE `^booking_orders` SET protocol = #
												WHERE eventid = #
												',
												$event_protocol, $eventid);
						}

						// check if event status not completed (paid)
						$event_status = booker_get_status_event($eventid);

						// mark as completed and inform client by email
						// if($event_status==MB_EVENT_PAID && !empty($event_details) && !empty($event_protocol))
						if(booker_event_is_paid($eventid) && !empty($event_details) && !empty($event_protocol))
						{
							$contractorid = $userid;
							booker_set_status_event($eventid, MB_EVENT_COMPLETED);
							$this->booker_sendmail_order_completed($eventid, $contractorid);

							// LOG
							$eventname = booker_get_eventname(MB_EVENT_COMPLETED);
							$params = array(
								'details' => $event_details,
								'protocol' => $event_protocol
							);
							booker_log_event($contractorid, $eventid, $eventname, $params);
						}
						else
						{
							// LOG as setdetails
							$eventname = 'contractor_setdetails';
							$params = array(
								'details' => $event_details,
								'protocol' => $event_protocol
							);
							booker_log_event($userid, $eventid, $eventname, $params);
						}
					}
				}

				// ajax return success
				$arrayBack = array(
					'updated' => '1'
				);
				echo json_encode($arrayBack);

				exit();
			} // END AJAX RETURN (orderdata)


			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker contractorschedule');
			$qa_content['title'] = qa_lang('booker_lang/consched_title');

			// filter by eventid
			$eventidfilter = qa_get('eventid');

			$weekdays = helper_get_weekdayarray();
			$ratingsymbols = helper_get_ratingsymbols();

			// $weekstartday = date("Y-m-d", strtotime('monday this week')); // problem with php5.2
			$weekstartday = date('Y-m-d', strtotime(date('o-\\WW')));

			// init
			$qa_content['custom'] = '';

			if(empty($eventidfilter))
			{
				$qa_content['custom'] .= '
					<p>
						'.qa_lang('booker_lang/add_details').'
					</p>
					<p style="margin-bottom:30px;">
						'.qa_lang('booker_lang/apptcompleted_hint').' '.qa_lang('booker_lang/clientmail_auto').'
					</p>
					';
			}
			else
			{
				$qa_content['title'] = qa_lang('booker_lang/singleappt');
				$qa_content['custom'] .= '
					<p style="margin:10px 0 30px 2px;font-size:13px;">
						'.
						strtr( qa_lang('booker_lang/onlysingleappt'), array(
							'^1' => '<a href="'.qa_path('contractorschedule').'">',
							'^2' => '</a>'
						  ))
						 .
					'</p>';
			}

			// init
			$orderlist = '';

			if(empty($eventidfilter))
			{
				// only future events and last 14 days events, also all open paid events
				$bookedevents = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, unitprice, needs, attachment,
																payment, status, protocol
															FROM `^booking_orders`
															WHERE contractorid = #
															AND (
																created >= DATE_SUB(CURDATE(), INTERVAL 200 DAY)
																AND status != #
															)
															ORDER BY starttime DESC
															;', $userid, MB_EVENT_OPEN)
								);
													// AND starttime >= CURDATE()
													// AND created >= DATE(NOW()) - INTERVAL 7 DAY
			}
			else
			{
				// security
				$eventidfilter = preg_replace('/\D/', '', $eventidfilter);
				// get specific order
				$bookedevents = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, unitprice, needs, attachment,
																payment, status, protocol
															FROM `^booking_orders`
															WHERE contractorid = #
															AND eventid = #
															;', $userid, $eventidfilter)
								);
			}

			$hasevents = count($bookedevents)>0;

			if($hasevents)
			{
				// save button on top if long list
				/*
				if(count($bookedevents)>5)
				{
					$orderlist .= '
						<div class="savefix">
							<p class="smsg">'.qa_lang('booker_lang/savesuccess').'</p>
							<button class="defaultbutton savebutton">'.qa_lang('booker_lang/saveall_btn').'</button>
						</div>
					';
				}
				*/

				$orderlist .= '
				<div class="ordertablewrap">
					<table id="ordertable">
					<thead>
					<tr>
						<th>'.qa_lang('booker_lang/appt').'</th>
						<th>'.qa_lang('booker_lang/client').'</th>
						<th>'.qa_lang('booker_lang/eventdetails').'</th>
						<th>'.qa_lang('booker_lang/attachments').'</th>
					</tr>
					</thead>
				';

				$inputrequired = false;

				foreach($bookedevents as $event)
				{
					$attachment = $event['attachment'];
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

					$eventstatus = '';
					$unpaidstatus = '';
					$eventaction = '';
					$eventaction_accept = '';
					$eventhint = '';
					$ratingspan = '';

					if($event['status']==MB_EVENT_OPEN)
					{
						// do not show
						continue;
					}
					else if($event['status']==MB_EVENT_COMPLETED)
					{
						$eventstatus = '<p style="margin:10px 0 0 1px;color:#3AA;">
											<i class="fa fa-check-circle-o fa-lg"></i> '.qa_lang('booker_lang/event_completed').'
										</p>';

						// do we have a rating of the session
						$rating = qa_db_read_one_assoc(
											qa_db_query_sub('SELECT created, customerid, contractorid, rating, text FROM `^booking_ratings`
																  WHERE eventid = #
																  LIMIT 1
																 ',
																$event['eventid']), true);

						if(count($rating)>0)
						{
							$ratingscore = $rating['rating'];
							$ratingtext = $rating['text'];

							$ratingspan .= '
								'.qa_lang('booker_lang/rating').':
								<a href="'.qa_path('contractorratings').'" target="_blank" class="tooltip ratingspan" title="'.$rating['text'].'">'.$ratingsymbols[$rating['rating']].'</a>
							';
						}
					}
					// is not paid, if paid status is MB_EVENT_PAID or higher
					// else if($event['status']==MB_EVENT_RESERVED)
					else
					{
						// $unpaidstatus = '<span style="color:#F00;"><i class="fa fa-exclamation-triangle fa-lg"></i> '.qa_lang('booker_lang/notpaidyet').'</span>';

						// bezahlt
						/*
						$eventstatus = '<p style="margin:10px 0 0 1px;color:#3C3;">
												<i class="fa fa-check-circle-o fa-lg"></i> '.qa_lang('booker_lang/paid').'
										</p>';
						*/

						if($event['status']==MB_EVENT_ACCEPTED)
						{
							// confirmed
							$eventstatus = '<p style="margin:10px 0 0 1px;color:#3C3;">
												<i class="fa fa-check-circle-o fa-lg"></i> '.qa_lang('booker_lang/confirmed').'
											</p>';
							if(strtotime($event['endtime']) < time())
							{
								// completed (as in past)
								$eventstatus = '<p style="margin:10px 0 0 1px;color:#3C3;">
													<i class="fa fa-check-circle-o fa-lg"></i> '.qa_lang('booker_lang/completed').'
												</p>';
							}
						}

						// if not yet accepted by contractor, add link to eventmanager
						if($event['status']!=MB_EVENT_ACCEPTED)
						{
							$eventaction_accept = '
							<p>
								<a href="'.qa_path('eventmanager').'?id='.$event['eventid'].'" class="defaultbutton btn_orange managebtn">
									'.qa_lang('booker_lang/manage_event_btn').'
								</a>
							</p>';
						}
						// event is in past, now contractor needs to confirm the completion
						else if(strtotime($event['endtime']) < time())
						{
							/*
							$eventaction = '
							<p style="text-align:right;">
								<button class="defaultbutton btn_orange confirmcomplete_btn" style="padding:5px 7px;margin:0;">'.qa_lang('booker_lang/confirmcomplete_btn').'</button>
							</p>';
							*/

							/*
							$eventaction = '
							<p>
								'.qa_lang('booker_lang/apptinpast').'
							</p>
							';
							*/

							if($event['status']!=MB_EVENT_COMPLETED)
							{
								$eventaction = '
								<p style="color:#00F;">
									<i class="fa fa-exclamation-triangle fa-lg"></i> '.qa_lang('booker_lang/customerconfirmneeded').'
								</p>
								';
							}



							// check if details and protocol data exist
							/*
							if(empty($event['needs']) && empty($event['protocol']))
							{
								$eventhint = '<p class="hintincomplete"><i class="fa fa-exclamation-triangle fa-lg"></i> '.qa_lang('booker_lang/hintincomplete1').'</p>';
							}
							else if(empty($event['needs']))
							{
								$eventhint = '<p class="hintincomplete"><i class="fa fa-exclamation-triangle fa-lg"></i> '.qa_lang('booker_lang/hintincomplete2').'</p>';
							}
							*/

							/*
							else if(empty($event['protocol']))
							{
								$eventhint = '<p class="hintincomplete"><i class="fa fa-exclamation-triangle fa-lg"></i> '.qa_lang('booker_lang/hintincomplete3').'</p>';
							}
							*/
						}
					}

					$eventdetailsfield = '<textarea class="eventdetailsinput" placeholder="'.qa_lang('booker_lang/apptdetails').'">'.$event['needs'].'</textarea>';
					$protocolfield = ''; // '<input type="text" class="protocolfield" placeholder="'.qa_lang('booker_lang/hintincomplete3').'" value="'.$event['protocol'].'" />';
					$protocoltext = '';

					// to complete an order the contractor must 1. specify the needs 2. insert the completion details (protocol) -
					// only then the order can be marked as completed and will not be changable anymore by the contractor
					// so we can assume that if the protocol is given and the needs are specified too, that the event can be flagged completed
					if(!empty($event['protocol']) && !empty($event['needs']))
					{
						// leave empty hidden field and textarea for form data!
						$eventdetailsfield = '
							<textarea class="eventdetailsinput" style="display:none;">'.$event['needs'].'</textarea>
							<span class="detailstext">'.qa_lang('booker_lang/order').': '.$event['needs'].'</span>
						';
						$protocolfield = '';
						$protocoltext = '
							<p class="protocoltext">
								<input type="hidden" class="protocolfield" value="'.$event['protocol'].'" />
								<span>
									'.qa_lang('booker_lang/protocol').':
									'.$event['protocol'].'
								</span>
							</p>
						';
					}

					if(empty($event['protocol']) || empty($event['needs']))
					{
						$inputrequired = true;
					}

					$customerid = $event['customerid'];
					// $clienthandle = booker_get_realname($customerid);
					$clientskype = booker_get_userfield($customerid, 'skype');

					$trline = '<tr class="ordertrdone" id="'.$event['eventid'].'">';
					if($inputrequired)
					{
						$trline = '<tr class="ordertr" id="'.$event['eventid'].'">';
					}

					$skypeline = '';
					if($event['status']>MB_EVENT_RESERVED)
					{
						$skypeline = '· <a title="'.$clientskype.'" href="skype:'.$clientskype.'?chat" style="font-size:11px;">Skype</a>';
					}

					// in case the event was recently rejected, do not display anymore
					if( !booker_event_rejected_by_contractor($event['eventid'], $event['contractorid']) )
					{
						$eventvalue = '<span class="eventvalue">'.qa_lang('booker_lang/value').': '.booker_get_eventprice($event['eventid'], true).' €</span>';
						$orderlist .= $trline.'
								<td>'.
									'<span title="'.qa_lang('booker_lang/eventid').': '.$event['eventid'].'">'.$weekdays[ (int)(date('N', strtotime($event['starttime'])))-1 ].', '.date(qa_lang('booker_lang/date_format_php'), strtotime($event['starttime'])).'</span><br />'.
									substr($event['starttime'],10,6).' - '.substr($event['endtime'],10,6).
									'<br />'.
									$eventvalue.
									$eventstatus.
									'
								<br />
								'
								.$unpaidstatus.
								'
								'.$eventaction_accept.'
								</td>
								<td style="line-height:150%;">
									<span>'.booker_get_realname($customerid).'</span>
									<div class="contractorlinksml">
										<a href="'.qa_path('mbmessages').'?to='.$customerid.'" style="font-size:11px;">'.qa_lang('booker_lang/message').'</a>
										'.$skypeline.'
									</div>
								</td>
								<td>
									'.$eventdetailsfield.'
									'.$protocolfield.'
									'.$protocoltext.'
									'.$eventhint.'
									'.$eventaction.'
									<p>
										'.$ratingspan.'
									</p>
								</td>
								<td>
									'.$attachment_show.'
								</td>
							</tr>
						';
					}

				} // end foreach $userpayments

				$orderlist .= '</table> <!-- ordertable -->';

				$orderlist .= '
					<p class="smsg">'.qa_lang('booker_lang/savesuccess').'</p>
					<button class="defaultbutton savebutton">'.qa_lang('booker_lang/saveall_btn').'</button>
					';

				$orderlist .= '
					</div> <!-- ordertablewrap -->
				';

				// only add save button if we have visible input fields
				/*
				if(!$inputrequired)
				{
					$orderlist .= '
					<style type="text/css">
						.savebutton, .smsg {
							display:none !important;
						}
					</style>
					';
				}
				*/

				// output
				$qa_content['custom'] .= $orderlist;
			} // end $hasevents
			else
			{
				if(empty($eventidfilter))
				{
					$qa_content['custom'] .= '
					<p class="qa-error">
						'.qa_lang('booker_lang/noappts').'
					</p>
					';
				}
				else
				{
					$qa_content['custom'] .= '
					<p class="qa-error">
						'.qa_lang('booker_lang/noeventsfound').'
					</p>
					';
				}
			}

			// todays weekday
			$weekdaytoday = date('N', time());

			$defaultview = 'month'; // 'agendaWeek';

			$contractorprice = (float)(booker_get_userfield($userid, 'bookingprice'));

			// jquery
			$qa_content['custom'] .= "
<script>

	$(document).ready(function()
	{

		var allevents = new Array();
		var changedevents = new Array();
		var eventcount = 1;
		var contractorprice = ".$contractorprice.";

		$('.confirmcomplete_btn').click( function(e)
		{
			e.preventDefault();

			var btnclicked = $(this);
			// make sure there are no more clicks
			btnclicked.prop('disabled', true);

			// get eventid
			var ev_eventid = $(this).parent().parent().parent().attr('id');
			// check if job details and protocolfield are not empty
			var ev_details = $(this).parent().parent().find('.eventdetailsinput').val().trim();
			var ev_protocol = $(this).parent().parent().find('.protocolfield').val().trim();
			/*
			if(ev_details.length == 0)
			{
				alert('".qa_lang('booker_lang/hintincomplete2')."');
				return;
			}
			else if(ev_protocol.length == 0)
			{
				alert('".qa_lang('booker_lang/hintincomplete3')."');
				return;
			}
			*/

			gdata = {
				'eventid': ev_eventid,
				'needs': ev_details,
				'protocol': ev_protocol,
			};

			console.log(gdata);
			var senddata = JSON.stringify(gdata);
			console.log('Sending to Server: '+senddata);

			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { completedata: senddata },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					console.log('server returned: '+data);
					console.log('server message: ' +data['updated']);
					if(data['updated']=='1')
					{
						var tdcell = btnclicked.parent().parent();
						btnclicked.parent().append('<p class=\'smsg\'>".qa_lang('booker_lang/updatesuccess')."</p>');
						var tmp_details = tdcell.find('.eventdetailsinput').val();
						var tmp_protocol = tdcell.find('.protocolfield').val();
						// inputfield to text
						tdcell.prepend('<p class=\"protocoltext\">".qa_lang('booker_lang/protocol').": '+tmp_protocol+'</p>');
						// textarea to text
						tdcell.prepend('<span class=\"detailstext\">".qa_lang('booker_lang/order').": '+tmp_details+'</span>');
						// remove
						tdcell.find('.eventdetailsinput').remove();
						tdcell.find('.protocolfield').remove();
						// tdcell.find('.hintincomplete').remove();
						btnclicked.parent().remove();

						$('.smsg').fadeOut(3000);
					}
					else if(data['updated']=='error')
					{
						btnclicked.parent().append('<p class=\'smsg\'>".qa_lang('booker_lang/error')."</p>');
						$('.smsg').fadeOut(2000, function()
						{
						});
					}
					$('.savebutton').prop('disabled', false);
				},
				error: function(xhr, status, error)
				{
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
					$('.savebutton').prop('disabled', false);
				}
			}); // end ajax
		});

		// orderdata
		$('.savebutton').click( function(e)
		{
			e.preventDefault();
			var gdata = [];
			var indx = 0;

			var savebtn = $(this);

			// make sure there are no more clicks
			$('.savebutton').prop('disabled', true);

			// could be improved by only sending the once that have been altered
			// set class on tr if altered or save id in js arry
			$('#ordertable tr.ordertr').each( function(index) {
				gdata[indx] = {
					'eventid': $(this).attr('id'),
					'needs': $(this).find('.eventdetailsinput').val(),
					'attachment': $(this).find('.orderlink').val(),
					'protocol': $(this).find('.protocolfield').val(),
				};
				indx++;
			});

			console.log(gdata);
			var senddata = JSON.stringify(gdata);
			console.log('Sending to Server: '+senddata);

			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { orderdata: senddata },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					console.log('server returned: '+data);
					console.log('x' +data['updated']);
					savebtn.prepend('<p class=\'smsg\'>".qa_lang('booker_lang/savesuccess')."</p>');

					$('.smsg').fadeOut(3000, function()
					{
						// reload page
						window.location.href = '".qa_self_html()."';
					});
					$('.savebutton').prop('disabled', false);
				},
				error: function(xhr, status, error)
				{
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
					$('.savebutton').prop('disabled', false);
				}
			}); // end ajax
		}); // end savebutton click

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
				.weekdayhighlight {
					background:#FFC;
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
				.defaultbutton {
					font-size:13px;
				}
				.ordertablewrap {
					display:block;
					width:92%;
					font-size:13px;
				}
				#ordertable {
					display:table;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
					width:100%;
					max-width:1080px;
				}
				#ordertable {
					margin-bottom:50px;
				}
				#ordertable th {
					font-weight:normal;
					background:#FFC;
					text-align:left;
				}
				#ordertable td, #ordertable th {
					padding:5px 5px 15px 5px;
					border:1px solid #DDD;
					font-weight:normal;
					line-height:150%;
					vertical-align:top;
					text-align:left;
				}
				#ordertable th {
					padding:7px 5px;
				}
				#ordertable tr:nth-child(even) {
					background:#EEE;
				}
				#ordertable tr:nth-child(odd) {
					background:#FAFAFA;
				}
				#ordertable tr td {
					vertical-align:top;
				}
				#ordertable td:nth-child(1) {
					width:15%;
					background:#F5F5FE;
				}
				#ordertable td:nth-child(2) {
					width:15%;
					background:#F5F5FE;
				}
				#ordertable td:nth-child(3) {
					width:60%;
				}
				#ordertable td:nth-child(4) {
					width:10%;
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

				.hinthold {
					margin:20px 0 50px 2px;
					width:80%;
					font-size:13px;
				}
				.changeshint {
					color:#00F;
					font-size:15px;
					/*padding: 10px 10px;
					margin: 0px 0px 15px 0;
					background: #FF5;
					border:1px solid #CC0;
					display:inline-block;
					*/
				}
				.changemeholder {
					width:92%;
					text-align:right;
					margin-bottom:50px;
				}
				#changeme {
					padding:10px 20px;
				}
				.smsg-red {
					color:#F00;
					margin-top:4px;
				}
				.smsg {
					display:block;
					margin:15px 0 0 0;
					color:#00F;
					text-align:right;
				}
				.smsg {
					display:none;
				}

				.completed {
					padding:5px 10px;
				}

				.eventdetailsinput {
					width:100%;
					padding:5px;
					border:1px solid #DDD;
				}
				.materialinput {
					width:90%;
					padding:5px;
					border:1px solid #DDD;
				}
				.eventdetailsinput:focus, .protocolfield:focus, .materialinput:focus {
					background:#FFE;
				}

				/* make button right aligned to table bottom */
				tfoot tr, tfoot td {
					background:transparent !important;
					border:1px solid transparent !important;
					text-align: right;
				}
				.savebutton {
					margin:10px 0px 30px 0;
					float:right;
				}
				.savefix {
					position:fixed;
					bottom:20px;
					right:20px;
				}
				.savefix .savebutton {
					margin:0;
				}
				.successmsg {
					color:#0A0;
				}
				.override {
					color:#00F;
					cursor:pointer;
				}
				.override:hover {
					text-decoration:underline;
				}
				.fileexistsholder {
					display:block;
				}

				.protocolfield {
					width:100%;
					padding:2px 5px;
					font-size:12px;
				}
				.protocoltext {
					margin:10px 0 0 0;
				}
				.hintincomplete {
					margin-top:10px;
					color:#F00;
					font-size:12px;
				}
				.orderdonecm {
					text-align:center;
					display:inline-block;
					width:20px;
					height:20px;
					border-radius:10px;
					background:#7C3;
					background:rgba(119, 174, 57, 0.8);
					color:#FFF;
					font-size:10px;
					cursor:default;
				}
				.ratingspan {
					display:inline-block;
					padding-top:5px;
					color:#123;
					text-decoration:none !important;
				}
				.managebtn {
					display:inline-block;
					padding:5px 10px;
					font-size:12px;
					margin-top:5px;
					margin-right:0;
					background: #34F; /* #E1E1E1;*/
					color: #EEE; /*#333;*/
					border-color: #CCC;
				}

				/* smartphones */
				@media only screen and (max-width:480px) {
					.qa-main {
						width:95%;
					}
					.hinthold {
						width:90%;
					}
				}

			</style>';


			return $qa_content;
		}

		// send mail to customer
		function booker_sendmail_order_completed($eventid, $contractorid)
		{
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';

			// make sure contractor is only marking his own event as completed
			$eventcomp = qa_db_read_one_assoc(
								qa_db_query_sub('SELECT customerid, contractorid, starttime, protocol FROM `^booking_orders`
														WHERE eventid = #
														AND contractorid = #
														', $eventid, $contractorid), true
							);

			if(!empty($eventcomp['contractorid']))
			{
				$customerid = $eventcomp['customerid'];
				$contractorid = $eventcomp['contractorid'];
				$protocol = $eventcomp['protocol'];
				$customername = booker_get_realname($customerid);
				$contractorname = booker_get_realname($contractorid);

				$protocoltext = '';
				if(isset($eventcomp['protocol']))
				{
					$protocoltext = '
						<p>
							'.qa_lang('booker_lang/protocol_note').': <span>„'.$eventcomp['protocol'].'“</span>
						</p>
						';
				}

				// send email to customer with protocol link and ask to rate the session
				$subject = qa_lang('booker_lang/bookingfrom').' '.date(qa_lang('booker_lang/date_format_php').' H:i', strtotime($eventcomp['starttime'])).' '.qa_lang('booker_lang/timeabbr').' '.qa_lang('booker_lang/event_completed');
				$emailbody = '';
				$emailbody .= '
					<p>
						'.qa_lang('booker_lang/hello').' '.$customername.',
					</p>
					<p>
						'.$contractorname.' '.qa_lang('booker_lang/mail_concompleted').'
					</p>
						'.$protocoltext.'
					<p>
						'.qa_lang('booker_lang/please_rate').': <a href="'.q2apro_site_url().'clientratings">'.qa_lang('booker_lang/torating').'</a>
					</p>
					<p>
						'.qa_lang('booker_lang/mail_greetings').'
						<br />
						'.qa_lang('booker_lang/mail_greeter').'
					</p>
				';
				$emailbody .= booker_mailfooter();

				$bcclist = explode(';', qa_opt('booker_mailcopies')); // could add more
				q2apro_send_mail(array(
							'fromemail' => q2apro_get_sendermail(), // qa_opt('from_email')
							'fromname'  => qa_opt('booker_mailsendername'),
							// 'toemail'   => $toemail,
							'senderid'	=> $contractorid, // for log
							'touserid'  => $customerid,
							'toname'    => $customername,
							'bcclist'   => $bcclist,
							'subject'   => $subject,
							'body'      => $emailbody,
							'html'      => true
				));

				return;
			}
		} // END booker_sendmail_order_completed

	}; // END booker_page_contractorschedule


/*
	Omit PHP closing tag to help avoid accidental output
*/

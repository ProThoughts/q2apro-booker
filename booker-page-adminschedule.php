<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_adminschedule 
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
					'title' => 'booker Page adminschedule', // title of page
					'request' => 'adminschedule', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='adminschedule')
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
				qa_set_template('booker adminschedule');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminschedule');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}

			// AJAX: admin is deleting
			$transferString = qa_post_text('deleteevent');
			if(isset($transferString))
			{
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
				
				// delete event
				$eventid = (int)($newdata['deleteid']);
				
				// LOG
				// get main event data
				$params = qa_db_read_one_assoc(
								qa_db_query_sub('SELECT bookid, created, contractorid, starttime, endtime, customerid, needs, unitprice, commission, payment, status FROM `^booking_orders` 
									WHERE eventid = #
									;', $eventid) 
							);
				$eventname = 'event_deleted';
				booker_log_event($userid, $eventid, $eventname, $params);
		
				qa_db_query_sub('DELETE FROM `^booking_orders` 
									WHERE eventid = #', 
									$eventid);
				
				// ajax return success
				echo json_encode('booking deleted');
				exit(); 
			} // END AJAX RETURN (delete)

			
			$weekdays = helper_get_weekdayarray();
			
			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker adminschedule');
			$qa_content['title'] = qa_lang('booker_lang/adminsched_title');
			// init 
			$qa_content['custom'] = '';
			
			// only future events and last 14 days events, also all open paid events
			$bookedevents = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, unitprice, commission, needs, attachment, 
															payment, status, protocol 
														FROM `^booking_orders` 
														WHERE (
															created >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
															OR status < #
														)
														ORDER BY starttime DESC
														;', MB_EVENT_COMPLETED)
							);
			
			$hasevents = count($bookedevents)>0;

			$qa_content['custom'] .= '
				<p style="font-size:12px !important;color:#777;margin:-10px 0 20px 2px;">
					'.qa_lang('booker_lang/apptcount').': '.count($bookedevents).'
				</p>
			';
			
			// init 
			$orderlist = '';
			
			$orderlist .= '
			<div class="ordertablewrap">
				<table id="ordertable">
				<thead>
				<tr>
					<th>'.qa_lang('booker_lang/adminsched_id').'</th>
					<th>'.qa_lang('booker_lang/appt').'</th>
					<th>'.qa_lang('booker_lang/adminsched_price').'</th>
					<th>'.qa_lang('booker_lang/adminsched_contractor').'</th>
					<th>'.qa_lang('booker_lang/adminsched_client').'</th>
					<th>'.qa_lang('booker_lang/adminsched_needs').'</th>
					<th>'.qa_lang('booker_lang/adminsched_state').'</th>
				</tr>
				</thead>
			';
			
			$ratingsymbols = helper_get_ratingsymbols();
			$inputrequired = false;
			
			foreach($bookedevents as $event) 
			{
				$attachment = $event['attachment'];
				$attachment_show = '';
				$eventcompleted = '';
				if(!empty($attachment))
				{
					$attlinks = explode(';', $attachment);
					$attachment_show = '<span class="fileexistsholder">';
					$count = 0;
					foreach($attlinks as $link) {
						/*if($count>0) {
							$attachment_show .= ', ';
						}*/
						$count++;
						$attachment_show .= '
							• <a class="fileexists" title="'.$link.'" href="'.$link.'" target="_blank">'.qa_lang('booker_lang/file').' '.$count.'</a>
							<br />
							';
					}					
				}
				else 
				{
					$attachment_show .= '';					
				}
				
				$ratingspan = '';
				$eventaction = '';
				if($event['status']==MB_EVENT_RESERVED)
				{
					$eventcompleted = '
						<i title="'.qa_lang('booker_lang/notpaidyet').'" class="fa fa-hourglass-2 fa-lg"></i>
						<span style="font-size:10px;">'.$event['payment'].'</span>
					';
				}
				else if(booker_event_is_paid($event['eventid']))
				{
					// bezahlt
					if($event['payment']=='paypal')
					{
						$eventcompleted = '<i title="'.qa_lang('booker_lang/adminsched_paidpaypal').'" class="fa fa-cc-paypal fa-2x"></i>';
					}
					else if($event['payment']=='bank')
					{
						$eventcompleted = '<i title="'.qa_lang('booker_lang/adminsched_paidbank').'" class="fa fa-money fa-2x"></i>';						
					}
					else if($event['payment']=='deposit')
					{
						$eventcompleted = '<i title="'.qa_lang('booker_lang/adminsched_paiddeposit').'" class="fa fa-cart-arrow-down fa-2x"></i>';						
					}
					/*
					else if($event['payment']=='creditnote')
					{
						$eventcompleted = '<i title="bezahlt via Gutschrift" class="fa fa-cloud fa-2x"></i>';
					}*/
					else
					{
						// memo: fa-cc-visa
						$eventcompleted = '<i title="'.qa_lang('booker_lang/paid').'" class="fa fa-smile-o fa-2x"></i>';
					}
					
					
					// event should actually be completed
					if(strtotime($event['endtime']) < time())
					{
						// check if needs and protocol exist
						if(empty($event['needs']) && empty($event['protocol']))
						{
							$eventaction = '<span class="hintincomplete">'.qa_lang('booker_lang/hintincomplete1').'</span>';
						}
						else if(empty($event['needs'])) {
							$eventaction = '<span class="hintincomplete">'.qa_lang('booker_lang/hintincomplete2').'</span>';
						}
						else if(empty($event['protocol']))
						{
							$eventaction = '<span class="hintincomplete">'.qa_lang('booker_lang/hintincomplete3').'</span>';
						}
						else
						{
							$eventaction = '';
						}
					}
				}
				else if($event['status']==MB_EVENT_COMPLETED)
				{
					$eventcompleted = '<i title="'.qa_lang('booker_lang/event_completed').'" class="fa fa-check-circle fa-2x"></i>';
					
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
							<a href="'.qa_path('contractorratings').'" target="_blank" class="tooltip ratingspan" title="'.$rating['text'].'">'.$ratingsymbols[$rating['rating']].'</a>
						';
					}
				}
				else if($event['status']==MB_EVENT_OPEN || $event['status']==MB_EVENT_RESERVED)
				{
					$eventcompleted = '<i title="'.qa_lang('booker_lang/notpaidyet').'" class="fa fa-opencart fa-2x"></i>';
				}
				
				// add search icon if event was rejected
				if($event['status']==MB_EVENT_NEEDED)
				{
					$eventcompleted = '<i title="'.qa_lang('booker_lang/evname_conneeded2').'" class="fa fa-search fa-2x"></i> '.$eventcompleted;
				}
				

				$needsfield = '<p class="needsinput">'.$event['needs'].'</p>';
				$protocolfield = '<p class="urltoprotocol">'.$event['protocol'].'</p>';
				$protocoltext = '';

				// to complete an order the contractor must 1. specify the needs and 2. insert the completion details (protocol)
				// only then the order will be marked as completed and will not be changable anymore by the contractor
				// so we can assume that if the protocol is given and the needs are specified too, that the event can be flagged completed
				if(isset($event['protocol']) && isset($event['needs']))
				{
					// leave empty hidden field and textarea for form data!
					$needsfield = '
						<textarea class="needsinput" style="display:none;">'.$event['needs'].'</textarea>
						<span class="needstext">'.qa_lang('booker_lang/order').': '.$event['needs'].'</span>
					';
					$protocolfield = '';
					$protocoltext = '
						<p class="protocoltext">
							<input type="hidden" class="urltoprotocol" value="'.$event['protocol'].'" />
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
				$username = booker_get_realname($customerid);
				$clientskype = booker_get_userfield($customerid, 'skype');
				$contractorname = booker_get_realname($event['contractorid']);
				$contractorskype = booker_get_userfield($event['contractorid'], 'skype');
				
				$eventstatus = '<p>'.booker_get_logeventname(booker_get_eventname($event['status']), false).'</p>';
				
				$orderlist .= '
					<tr id="eventid_'.$event['eventid'].'" class="ordertr">
						<td title="'.qa_lang('booker_lang/adminsched_bookedon').' '.$event['created'].' | '.qa_lang('booker_lang/bookingnr').': '.$event['bookid'].'" >'.
							'<a target="_blank" href="'.qa_path('eventhistory').'?id='.$event['eventid'].'">ID '.$event['eventid'].'</a>'.
							'<p style="font-size:11px !important;">BID: '.$event['bookid'].'</p>'.
							$eventcompleted.
						'</td>
						<td>'.
							$weekdays[ (int)(date('N', strtotime($event['starttime'])))-1 ].', '.date(qa_lang('booker_lang/date_format_php'), strtotime($event['starttime'])).'<br />'.
							substr($event['starttime'],10,6).' - '.substr($event['endtime'],10,6).
							'
						</td>
						<td>
							<span class="ev_totalprice">'.booker_get_eventprice($event['eventid'], true).' '.qa_opt('booker_currency').'</span><br />
							<span class="ev_unitprice">'.number_format($event['unitprice'],2,',','.').' '.qa_opt('booker_currency').'/'.qa_lang('booker_lang/hourabbr').'</span>
						</td>
						<td style="line-height:150%;">
							<span>'.$contractorname.'</span>
							<div class="contractorlinksml">
								<a href="'.qa_path('userprofile').'?userid='.$event['contractorid'].'">'.qa_lang('booker_lang/profile').'</a> ·
								<a href="'.qa_path('contractorschedule').'?userid='.$event['contractorid'].'">'.qa_lang('booker_lang/appts').'</a> ·
								<a href="'.qa_path('mbmessages').'?to='.$event['contractorid'].'">'.qa_lang('booker_lang/message').'</a> ·
								<a href="'.qa_path('contractorbalance').'?userid='.$event['contractorid'].'">'.qa_lang('booker_lang/accountbalance').'</a>
							</div>
						</td>
						<td style="line-height:150%;">
							<span>'.booker_get_realname($customerid).'</span> 
							<div class="contractorlinksml">
								<a href="'.qa_path('userprofile').'?userid='.$event['customerid'].'">'.qa_lang('booker_lang/profile').'</a> ·
								<a href="'.qa_path('clientschedule').'?userid='.$event['customerid'].'">'.qa_lang('booker_lang/appts').'</a> ·
								<a href="'.qa_path('mbmessages').'?to='.$event['customerid'].'">'.qa_lang('booker_lang/message').'</a> ·
								<a href="'.qa_path('clientschedule').'?userid='.$event['customerid'].'">'.qa_lang('booker_lang/bookingvolume').'</a>
							</div>
						</td>
						<td>
							'.$needsfield.'
							'.$attachment_show.'
							'.$protocolfield.'
							'.$protocoltext.'
						</td>
						<td id="'.$event['eventid'].'">
						'.
						$eventstatus.
						$eventaction.
						$ratingspan.
						'<a class="defaultbutton admineditbtn" href="'.qa_path('admineventedit').'?id='.$event['eventid'].'">edit</a>'.
						'<span class="defaultbutton ev_delete">del</span>'.
					'</tr>
				';
			} // end foreach $userpayments
			
			$orderlist .= '</table> <!-- ordertable -->';
			
			$orderlist .= '
				</div> <!-- ordertablewrap -->
			';
			
			// only add save button if we have visible input fields
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
			
			$qa_content['custom'] .= $orderlist;
			
			// JQUERY
			$qa_content['custom'] .= "
			<script>
				$(document).ready(function(){
				
					$('.ev_delete').click( function(e){
						var deleteid = $(this).parent().attr('id'); // eventid
						var adminokay = confirm('".qa_lang('booker_lang/adminsched_confirmdelete')." (".qa_lang('booker_lang/eventid')." '+deleteid+')?');
						if(!adminokay) { return; }
						var admindata = { 
							'deleteid': deleteid, 
						};
						console.log(admindata);
						var senddata = JSON.stringify(admindata);
						$.ajax({
							type: 'POST',
							url: '".qa_self_html()."',
							data: { deleteevent: senddata },
							dataType: 'json',
							cache: false, 
							success: function(data) {
								console.log(data);
								$('#eventid_'+deleteid).hide();
							},
							error: function(xhr, status, error) {
								console.log('problem with server:');
								console.log(xhr.responseText);
								console.log(error);
							}
						});
					}); // end ev_delete click

				}); // end ready admin
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
				.contractorlinksml {
					font-size:10px;
					color:#555;
					cursor:pointer;
				}
				.ev_unitprice {
					font-size:10px;
					line-height:100%;
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
					padding:5px 5px 25px 5px;
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
					width:5%;
					background:#F5F5FE;
					line-height:110%;
				}
				#ordertable td:nth-child(2) {
					width:10%;
					background:#F5F5FE;
				}
				#ordertable td:nth-child(3) {
					width:9%;
				}
				#ordertable td:nth-child(4) {
					width:15%;
				}
				#ordertable td:nth-child(5) {
					width:15%;
				}
				#ordertable td:nth-child(6) {
					width:20%;
				}
				#ordertable td:nth-child(7) {
					width:12%;
				}
				#ordertable td:nth-child(7) {
					width:7%;
				}
				.fileexistsholder {
					display:block;
				}
				
				.urltoprotocol {
					width:100%;
					padding:2px 5px;
					font-size:12px;
				}
				.protocoltext {
					margin:10px 0 0 0;
				}
				.hintincomplete {
					display:block;
					color:#F00;
					font-size:12px;
				}
				.orderdonecm {
					text-align:center;
					display:block;
					width:20px;
					height:20px;
					border-radius:10px;
					background:#7C3;
					background:rgba(119, 174, 57, 0.8);
					color:#FFF;
					font-size:10px;
					cursor:default;
					margin-bottom:10px;
				}
				.ratingspan {
					display:block;
					padding-top:5px;
					color:#123;
				}
				textarea.needsinput {
					width:100%;
					padding:5px;
					border:1px solid #DDD;
				}
				.materialinput {
					width:90%;
					padding:5px;
					border:1px solid #DDD;
				}
				.needsinput:focus, .urltoprotocol:focus, .materialinput:focus {
					background:#FFE;
				}
				.admineditbtn, .ev_delete {
					display:inline-block;
					padding:5px 10px;
					font-size:12px;
					margin-top:5px;
					margin-right:0;
					background: #E1E1E1;
					border-color: #CCC;
					color: #333;
				}
				.ev_delete, .completed {
					display:inline-block;
					margin:2px;
					padding:5px;
					cursor:pointer;
					font-size:12px;
				}
				.ev_delete:hover, .completed:hover {
					background:#99F;
				}
				.ev_delete {
					background:#FCC;
				}
				.completed {
					background:#DDF;
				}
				.ev_paymethod {
					font-size:12px;
					color:#33F;
				}
				#ordertable i.fa {
					display:block;
					cursor:default;
				}
				#ordertable .fa-hourglass-2 {
					color:#F77;
				}
				#ordertable .fa-cc-paypal, 
				#ordertable .fa-money,
				#ordertable .fa-smile-o,
				#ordertable fa-cart-arrow-down
				{
					color:#00F;
				}
				#ordertable .fa-check-circle {
					color:#0A0;
				}
				#ordertable .fa-search {
					color:#E33;
					display:block;
					margin-bottom:5px;
				}

			</style>';
			
			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
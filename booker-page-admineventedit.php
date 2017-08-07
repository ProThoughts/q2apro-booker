<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_admineventedit
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
					'title' => 'booker Page Admin Event Edit', // title of page
					'request' => 'admineventedit', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='admineventedit')
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
				qa_set_template('booker admineventedit');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker admineventedit');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}
			
			// form post
			$gotevent = qa_post_text('eventid');

			$eventupdate = false;
			if(isset($gotevent))
			{
				$eventid = qa_post_text('eventid');
				$bookid = qa_post_text('bookid');
				$created = qa_post_text('created');
				$contractorid = qa_post_text('contractorid');
				$starttime = qa_post_text('starttime');
				$endtime = qa_post_text('endtime');
				$customerid = qa_post_text('customerid');
				$unitprice = qa_post_text('unitprice');
				$needs = qa_post_text('needs');
				$attachment = qa_post_text('attachment');
				$payment = qa_post_text('payment');
				$status = qa_post_text('status');
				$protocol = qa_post_text('protocol');
				// set necessary null fields if empty 
				if(empty($unitprice)) { $unitprice = null; }
				if(empty($needs)) { $needs = null; }
				if(empty($attachment)) { $attachment = null; }
				if(empty($payment)) { $payment = null; }
				if(empty($protocol)) { $protocol = null; }

				qa_db_query_sub('UPDATE `^booking_orders` 
									SET bookid = #, created = #, contractorid = #, starttime = #, endtime = #, customerid = #, unitprice = #, 
									needs = #, attachment = #, payment = #, status = #, protocol = # 
									WHERE eventid = #
								', $bookid, $created, $contractorid, $starttime, $endtime, $customerid, $unitprice, $needs, $attachment, $payment, $status, $protocol, $eventid);

				$eventupdate = true;
				
				// NO LOGGING yet
			}

			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker admineventedit');
			$qa_content['title'] = qa_lang('booker_lang/admineved_title');
			
			// transferred value
			$eventid = qa_get('id');
			
			if(empty($eventid))
			{
				$qa_content['error'] = qa_lang('booker_lang/admineved_missingid');
				return $qa_content;
			}
			
			// init
			$qa_content['custom'] = '';
			
			if($eventupdate)
			{
				$qa_content['custom'] = '
				<p class="qa-success">
					'.qa_lang('booker_lang/admineved_success').'
					<a href="'.qa_path('adminschedule').'#eventid_'.$eventid.'">'.qa_lang('booker_lang/admineved_back').'</a>
				</p>
				';
			}
			
			$qa_content['custom'] .= '
			<style type="text/css">
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
					margin:20px 0;
				}
				.eventinputs input {
					padding:4px;
				}
				.eventinputs input:focus {
					background:#FFD;
				}
				.inputdisabled {
					pointer-events: none;
					background:#DDD;
				}
			</style>';
			
			
			// table of all entries of event
			$eventoutput = '';
			
			// only 1 event
			$eventdata = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, unitprice, needs, 
															attachment, payment, status, protocol 
													FROM `^booking_orders` 
													WHERE eventid = #
													LIMIT 1
												;', $eventid)
											);

			foreach($eventdata as $dat)
			{
				$contractor_realname = booker_get_realname($dat['contractorid']);
				$client_realname = booker_get_realname($dat['customerid']); // booker_get_realname($dat['customerid']);
				$client_skype = booker_get_userfield($dat['customerid'], 'skype');
				// $customer_skype = '<a class="skypelink" href="skype:'.$customer_skype.'?chat">'.$customer_skype.'</a>';
				$statusname = booker_get_eventname($dat['status']);
				
				$eventoutput .= '
					<form class="eventinputs" method="post" action="'.qa_self_html().'">
						<label>
							<span>eventid</span>
							<input type="text" class="inputdisabled" id="eventid" name="eventid" value="'.$dat['eventid'].'">
						</label>
						<label>
							<span>bookid</span>
							<input type="text" id="bookid" name="bookid" value="'.$dat['bookid'].'">
						</label>
						<label>
							<span>created</span>
							<input type="text" id="created" name="created" value="'.$dat['created'].'">
						</label>
						<label>
							<span>contractorid</span>
							<input type="text" id="contractorid" name="contractorid" value="'.$dat['contractorid'].'">
							<span>
								&ensp;<a href="'.qa_path('userprofile').'?userid='.$dat['contractorid'].'">'.$contractor_realname.'</a>
								| <a href="'.qa_path('mbmessages').'?to='.$dat['contractorid'].'">✉</a>
							</span>
						</label>
						<label>
							<span>starttime</span>
							<input type="text" id="starttime" name="starttime" value="'.$dat['starttime'].'">
						</label>
						<label>
							<span>endtime</span>
							<input type="text" id="endtime" name="endtime" value="'.$dat['endtime'].'">
						</label>
						<label>
							<span>customerid</span>
							<input type="text" id="customerid" name="customerid" value="'.$dat['customerid'].'">
							&ensp;<a href="'.qa_path('userprofile').'?userid='.$dat['customerid'].'">'.$client_realname.'</a>
							| <a href="'.qa_path('mbmessages').'?to='.$dat['customerid'].'">✉</a>
						</label>
						<label>
							<span>unitprice</span>
							<input type="text" id="unitprice" name="unitprice" value="'.$dat['unitprice'].'">
						</label>
						<label>
							<span>needs</span>
							<input type="text" id="needs" name="needs" value="'.$dat['needs'].'">
						</label>
						<label>
							<span>attachment</span>
							<input type="text" id="attachment" name="attachment" value="'.$dat['attachment'].'">
						</label>
						<label>
							<span>payment</span>
							<input type="text" id="payment" name="payment" value="'.$dat['payment'].'">
						</label>
						<label>
							<span>status</span>
							<input type="text" id="status" name="status" value="'.$dat['status'].'">
							<span>&ensp;'.$statusname.'</span>
						</label>
						<label>
							<span>protocol</span>
							<input type="text" id="protocol" name="protocol" value="'.$dat['protocol'].'">
						</label>
						
						<button type="submit" class="defaultbutton">Update Event</button>
					</form>
						';
			} // end foreach
			$qa_content['custom'] .= $eventoutput;

			// extra
			$qa_content['custom'] .= '
				<p style="margin-top:100px;font-weight:bold;">
					Status-Übersicht
				</p>
				<table class="statustable">
					<tr>
						<td>
							'.MB_EVENT_OPEN.'
						</td>
						<td>
							'.booker_get_eventname(MB_EVENT_OPEN).'
						</td>
					</tr>
					<tr>
						<td>
							'.MB_EVENT_RESERVED.'
						</td>
						<td>
							'.booker_get_eventname(MB_EVENT_RESERVED).'
						</td>
					</tr>
					<tr>
						<td>
							'.MB_EVENT_PAID.'
						</td>
						<td>
							'.booker_get_eventname(MB_EVENT_PAID).'
						</td>
					</tr>
					<tr>
						<td>
							'.MB_EVENT_ACCEPTED.'
						</td>
						<td>
							'.booker_get_eventname(MB_EVENT_ACCEPTED).'
						</td>
					</tr>
					<tr>
						<td>
							'.MB_EVENT_FINDTIME.'
						</td>
						<td>
							'.booker_get_eventname(MB_EVENT_FINDTIME).'
						</td>
					</tr>
					<tr>
						<td>
							'.MB_EVENT_REJECTED.'
						</td>
						<td>
							'.booker_get_eventname(MB_EVENT_REJECTED).'
						</td>
					</tr>
					<tr>
						<td>
							'.MB_EVENT_NEEDED.'
						</td>
						<td>
							'.booker_get_eventname(MB_EVENT_NEEDED).'
						</td>
					</tr>
					<tr>
						<td>
							'.MB_EVENT_COMPLETED.'
						</td>
						<td>
							'.booker_get_eventname(MB_EVENT_COMPLETED).'
						</td>
					</tr>
				</table>
				
				<style type="text/css">
					.statustable {
						border-collapse:collapse;
						width:300px;
					}
					.statustable td:nth-child(1) {
						width:50px;
					}
					.statustable td {
						border:1px solid #EEE;
						padding:5px 10px;
					}
				</style>
			';
			
			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
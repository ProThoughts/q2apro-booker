<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_adminclients
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
					'title' => 'booker Page Admin clients', // title of page
					'request' => 'adminclients', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='adminclients')
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
				qa_set_template('booker adminclients');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminclients');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}

			// AJAX: admin approves contractor
			$transferString = qa_post_text('approvecontractor');
			if(isset($transferString)) 
			{
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
				
				$contractorid = (int)($newdata);
				
				booker_set_userfield($contractorid, 'approved', '1');
				
				// ajax return success
				echo json_encode('contractor '.$contractorid.' approved');
				exit(); 
			} // END AJAX RETURN (approve)

			
			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker adminclients');
			$qa_content['title'] = qa_lang('booker_lang/admincli_title');
			
			// init
			$qa_content['custom'] = '';
			
			$start = qa_get('start');
			if(empty($start))
			{
				$start = 0;				
			}
			$pagesize = 50; // clients to show per page
			$count = booker_get_clientcount(); // total
			$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, true); // last parameter is prevnext
			
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
				.bookingtablewrap {
					width:92%;
					display:block;
					text-align:right;
				}
				#bookingtable, .membertable, #paymentstable {
					width:100%;
					max-width:950px;
					display:table;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
				}
				#bookingtable th, .membertable th, #paymentstable th {
					font-weight:normal;
					background:#FFC;
				}
				#bookingtable td, #bookingtable th, 
				.membertable td, .membertable th, 
				#paymentstable td, #paymentstable th
				{
					vertical-align:top;
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
				.membertable td {
					font-size:14px;
				}
				.membertable th {
					text-align:left;
				}
				.membertable td:nth-child(1) {
					width:15%;
				}
				.membertable td:nth-child(2) {
					text-align:right;
				}
				.membertable td:nth-child(3) {
					text-align:right;
				}
				.membertable td:nth-child(5),
				.membertable td:nth-child(6)
				{
					font-size:12px;
				}

				.ev_unitprice {
					font-size:10px;
				}
				.forumname {
					color:#999 !important;
				}
				.skypelink, .contractormail, .postaladdress, .contractorservice {
					color:#898;
				}
				
				.contractorlinksml {
					font-size:10px;
					color:#555;
					cursor:pointer;
				}

			</style>';			
		

			// CUSTOMERS
			// table of all contractors below
			$customerlist = '';
			// $customerlist .= '<h2 id="customers" style="margin-top:50px;">Kunden</h2>';
			$customerlist .= '<table class="membertable">';
			$customerlist .= '
			<tr>
				<th>'.qa_lang('booker_lang/name').'</th>
				<th>'.qa_lang('booker_lang/appts').'</th>
				<th>'.qa_lang('booker_lang/bookingvolume').'</th>
				<th>'.qa_lang('booker_lang/admin_tab_userdata').'</th>
				<th>'.qa_lang('booker_lang/admin_tab_birthdate').'</th>
				<th>'.qa_lang('booker_lang/admin_tab_address').'</th>
			</tr>';
	
			// get all customers, i.e. users that filled out their clientprofile
			// contractor could actually be client too, we ignore those
			// another way would be to check the bookings table for booked events
			$allCustomers = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT userid FROM `^booking_users`
														WHERE 
														`contracted` IS NULL
														AND `bookingprice` = 0
														AND `realname` IS NOT NULL
														GROUP BY userid
														;')
													);
			$customercount = 0;
			$totalbal = 0; 
			$emailcollect = '';
			
			foreach($allCustomers as $member) 
			{
				$customerid = $member['userid'];
				$clienthandle = helper_gethandle($customerid);
				
				$clientname = booker_get_realname($customerid);
				$clientskype = booker_get_userfield($customerid, 'skype');
				$clientbirthdate = booker_get_userfield($customerid, 'birthdate');
				$clientaddress = booker_get_userfield($customerid, 'address');
				
				/*
				// done by mysql query above
				if(booker_iscontracted($customerid))
				{
					continue;
				}
				*/
				
				$customercount++;
				
				if(empty($clientskype))
				{
					$clientskype = ''; // '<span style="font-size:10px;color:#C00;">no skype</span>';
				}
				else
				{
					$clientskype = '<a class="skypelink" href="skype:'.booker_get_userfield($customerid, 'skype').'?chat" title="Skype: '.booker_get_userfield($customerid, 'skype').'">Skype</a> · ';
				}
				
				$client_email = helper_getemail($customerid);
				$emailcollect .= $client_email.', ';
				$clientmail = '<a class="contractormail" href="mailto:'.$client_email.'">'.qa_lang('booker_lang/email').'</a>';
				
				$bookvolume = booker_get_bookingvolume($customerid);
				$totalbal += $bookvolume; 
				
				// get number of schedule the client enjoyed
				$scheduledone = qa_db_read_one_value( 
									qa_db_query_sub('SELECT COUNT(eventid) FROM `^booking_orders`
															WHERE customerid = # 
															AND status = #
													', $customerid, MB_EVENT_COMPLETED), true
													);
				
				$customerlist .= '
				<tr>
					<td>
						<a href="'.qa_path('userprofile').'?userid='.$customerid.'">'.$clientname.'</a><br />
						<span class="smalllink">Userid: '.$customerid.'</span> 
						<a class="smalllink" href="'.qa_path('mbmessages').'?to='.$customerid.'" title="'.qa_lang('booker_lang/admin_sendmsg').'">( ✉ )</a>
					</td>
					<td>
						'.$scheduledone.'
					</td>
					<td>
						<a target="_blank" href="'.qa_path('clientschedule').'?userid='.$customerid.'">'.$bookvolume.' '.qa_opt('booker_currency').'</a>
					</td>
					<td>
						<div class="contractorlinksml">
							<a href="'.qa_path('user').'/'.$clienthandle.'" title="'.$clienthandle.'">'.qa_lang('booker_lang/forum_profile').'</a> · 
							<a href="'.qa_path('userprofile').'?userid='.$customerid.'">'.qa_lang('booker_lang/profile').'</a> ·
							<a href="'.qa_path('clientschedule').'?userid='.$customerid.'">'.qa_lang('booker_lang/appts').'</a> ·
							<a href="'.qa_path('mbmessages').'?to='.$customerid.'">'.qa_lang('booker_lang/message').'</a> · 
							'.$clientskype.' 
							'.$clientmail.'
						</div>
					</td>
					<td>
						'.$clientbirthdate.'
					</td>
					<td>
						<a title="'.qa_lang('booker_lang/admin_open_gmaps').'" target="_blank" href="https://maps.google.com/?q='.$clientaddress.'">'.$clientaddress.'</a>
					</td>
				</tr>';

			} // end foreach
			$customerlist .= '</table>';
			
			$qa_content['custom'] .= '
			<p style="font-size:17px !important;margin:10px 0 20px 0;">
				'.qa_lang('booker_lang/bookingvolume').': '.$totalbal.' '.qa_opt('booker_currency').'
			</p>';

			$qa_content['custom'] .= $customerlist;
		
			$qa_content['custom'] .= "
			<script type=\"text/javascript\">
			$(document).ready(function(){
			
				$('th').click(function(){
					var table = $(this).parents('table').eq(0)
					var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
					this.asc = !this.asc
					if (!this.asc){rows = rows.reverse()}
					for (var i = 0; i < rows.length; i++){table.append(rows[i])}
				})
				function comparer(index) {
					return function(a, b) {
						var valA = getCellValue(a, index), valB = getCellValue(b, index)
						return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.localeCompare(valB)
					}
				}
				function getCellValue(row, index){ return $(row).children('td').eq(index).text() }

				$('th:first').trigger('click');
	
			}); // end ready
			</script>
			";
			
			$qa_content['title'] = qa_lang('booker_lang/admincli_title').' ('.$customercount.')';

			$qa_content['custom'] .= '
				<p style="margin-top:50px;font-weight:bold;">'.qa_lang('booker_lang/admin_maillist').':</p>
				<p>'.$emailcollect.'</p>';
		
			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
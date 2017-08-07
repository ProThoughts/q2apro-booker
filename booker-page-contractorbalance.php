<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_contractorbalance
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
					'title' => 'booker Page Contractor Honorar', // title of page
					'request' => 'contractorbalance', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='contractorbalance') 
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
				qa_set_template('booker contractorbalance');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			// only gmf members can access
			if(empty($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker contractorbalance');
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

			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker contractorbalance');
			$qa_content['title'] = qa_lang('booker_lang/conbal_title');
			
			$weekdays = helper_get_weekdayarray();
			
			// init output
			$qa_content['custom'] = '';

			$contractorname = booker_get_realname($userid);
			
			// check if bookings exist
			$schedule = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT eventid, starttime, endtime, customerid, contractorid, needs, attachment FROM `^booking_orders` 
														WHERE `contractorid` = #
														ORDER BY starttime ASC
														;', $userid) );
			if(count($schedule)==0)
			{
				$qa_content['custom'] .= '<p style="color:#00F;">'.qa_lang('booker_lang/con_noapptyet').'</p>';
				return $qa_content;
			}

			// $weekstartday = date("Y-m-d", strtotime('monday this week')); // problem with php5.2
			$weekstartday = date('Y-m-d', strtotime(date('o-\\WW'))); 

			$bookedEvents = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT eventid, bookid, contractorid, created, starttime, endtime, customerid, unitprice, commission, status FROM `^booking_orders` 
														WHERE contractorid = # 
														ORDER BY contractorid
														;', $userid) 
							);

			// copy key 'paytime' to 'starttime' so we can sort the merged pay-array by 'starttime'
			/*
			foreach($contractorPayments as &$pay)
			{
				$pay['starttime'] = $pay['paytime'];
			};
			*/
			
			// merge outgoing payments and all bookings
			// $paylist = array_merge($contractorPayments, $bookedEvents);
			/*
			function starttimeCmp($a, $b) 
			{
				return strtotime($a['starttime']) - strtotime($b['starttime']);
			}
			usort($paylist, 'starttimeCmp');
			*/
			
			$honorartable = '';
			$honorartable .= '<table id="honorartable">
				<thead>
					<tr>
						<th>'.qa_lang('booker_lang/date').'</th>
						<th>'.qa_lang('booker_lang/time').'</th>
						<th>'.qa_lang('booker_lang/client').'</th>
						<th>'.qa_lang('booker_lang/timespan').'</th>
						<th>'.qa_lang('booker_lang/fee').'</th>
						<th>'.qa_lang('booker_lang/commission').'</th>
						<th>'.qa_lang('booker_lang/income').'</th>
						<th style="background:#d1ffd2;">'.qa_lang('booker_lang/accountbalance').'</th>
					</tr>
				</thead>
			';

			$turnover = 0;
			$honorarsum = 0;
			$honorarpaid = 0;
			$total_turnover = 0;
			$total_honorarsum = 0;
			$total_commission = 0;
			
			foreach($bookedEvents as $event)
			{
				// paid contractorevent
				/*
				if(isset($event['payid']))
				{
					$honorarsum += $event['amount'];
					$honorarpaid += $event['amount'];
					
					$weekdayx = $weekdays[ (int)(date('N', strtotime($event['starttime'])))-1 ];				
					$honorartable .= '
						<tr id="payid_'.$event['payid'].'" style="color:#003;background:#DFD;">
							<td>'.
								$weekdayx.', '.date(qa_lang('booker_lang/date_format_php'), strtotime($event['starttime'])).
							'</td>
							<td> </td>
							<td>
								'.qa_lang('booker_lang/feepay').'
							</td>
							<td> </td>
							<td>
								'.number_format($event['amount'], 2, ',', '.').' '.qa_opt('booker_currency').'
							</td>
							<td>
								'.number_format($honorarsum, 2, ',', '.').' '.qa_opt('booker_currency').'
							</td>
						</tr>
					';
				}
				else
				{
				*/
					// event is completed
					if($event['status']==MB_EVENT_COMPLETED)
					{
						$eventduration = (strtotime($event['endtime']) - strtotime($event['starttime']))/60;
						
						$commission = $event['commission'];
						if(is_null($commission))
						{
							$commission = qa_opt('booker_commission');
						}
						
						$unitvalue = $eventduration/60*$event['unitprice'];
						$total_turnover += $unitvalue;
						$honorarsum = $unitvalue*(1-$commission);
						$total_honorarsum += $honorarsum;
						$turnover += $unitvalue;
						$total_commission += -$unitvalue*$commission;
						
						// output contractor name for admin
						$customername = booker_get_realname($event['customerid']);
						$weekdayx = $weekdays[ (int)(date('N', strtotime($event['starttime'])))-1 ];
						$honorartable .= '
							<tr id="'.$event['eventid'].'" class="orderdone">
								<td>
									<a href="'.qa_path('contractorschedule').'?eventid='.$event['eventid'].'">'.
									$weekdayx.', '.date(qa_lang('booker_lang/date_format_php'), strtotime($event['starttime'])).
								'</td>
								<td>
								'.date('H:i', strtotime($event['starttime'])).' - '.date('H:i', strtotime($event['endtime'])).'
								</td>
								<td style="line-height:150%;">
									'.$customername.'
								</td>
								<td>
									'.$eventduration.' min
								</td>
								<td>
									'.helper_format_currency($unitvalue, 2, true).' '.qa_opt('booker_currency').'
								</td>
								<td>
									'.helper_format_currency(-$unitvalue*$commission, 2, true).' '.qa_opt('booker_currency').'
								</td>
								<td>
									'.helper_format_currency($honorarsum, 2, true).' '.qa_opt('booker_currency').'
								</td>
								<td>
									'.helper_format_currency($total_honorarsum, 2, true).' '.qa_opt('booker_currency').'
								</td>
							</tr>
						';
					// } // end $event status completed
				} // end else 
			} // end foreach $bookedEvents
			
			// output 			
			$qa_content['custom'] .= '
				<div class="honorartablewrap">
				<p style="margin:20px 0;">
					'.qa_lang('booker_lang/apptcompleted_hint').'
				</p>
				';
			$qa_content['custom'] .= $honorartable;
			$qa_content['custom'] .= '
			<tr>
				<td></td> 
				<td></td> 
				<td></td> 
				<td></td> 
				<td>
					'.helper_format_currency($total_turnover, 2, true).' '.qa_opt('booker_currency').'				
				</td> 
				<td>
					'.helper_format_currency($total_commission, 2, true).' '.qa_opt('booker_currency').'
				</td> 
				<td>
					'.helper_format_currency($total_honorarsum, 2, true).' '.qa_opt('booker_currency').'
				</td> 
				<td style="background:#d1ffd2;">
					<span style="text-align:right;">
						'.helper_format_currency($total_honorarsum, 2, true).' '.qa_opt('booker_currency').'
					</span>
				</td> 
			</tr>';
			$qa_content['custom'] .= '</table>';
			$qa_content['custom'] .= '</div> <!-- honorartablewrap -->';
			
			// data only for admin
			if($isadmin)
			{
				$qa_content['custom'] .= '
					<div class="adminbox">
						<p>
						'.qa_lang('booker_lang/conbal_onlyadmin').':
						</p>
						<p>
							'.qa_lang('booker_lang/conbal_turnover').': '.helper_format_currency($turnover).' '.qa_opt('booker_currency').'
						</p>
						<p>
							'.qa_lang('booker_lang/conbal_fees').': '.helper_format_currency($honorarpaid).' '.qa_opt('booker_currency').'</b>
						</p>
						<p>
							<b>'.qa_lang('booker_lang/conbal_earnings').': '.helper_format_currency($turnover+$honorarpaid).' '.qa_opt('booker_currency').'</b>
						</p>
					</div>
				';
			}
			
			// todays weekday
			$weekdaytoday = date('N', time());

			$contractorprice = (float)(booker_get_userfield($userid, 'bookingprice'));
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:100%;
					min-height:600px;
				}
				.qa-main p {
					line-height:150%;
				}
				.honorartablewrap {
					display:block;
					width:92%;
					font-size:13px;
				}
				h2#honorar {
					margin-top:50px;
				}
				#honorartable {
					display:inline-block;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
					width:100%;
					max-width:800px;
				}
				#honorartable th {
					font-weight:normal;
					background:#FFC;
				}
				#honorartable td, #honorartable th {
					padding:10px 5px;
					border:1px solid #DDD;
					font-weight:normal;
				}
				#honorartable td:nth-child(1) {
					width:15%;
				}
				#honorartable td:nth-child(2) {
					width:15%;
				}
				#honorartable td:nth-child(3) {
					width:20%;
				}
				#honorartable td:nth-child(4), 
				#honorartable td:nth-child(5), 
				#honorartable td:nth-child(6),
				#honorartable td:nth-child(7),
				#honorartable td:nth-child(8) {
					text-align:right;
					width:10%;
				}
				#honorartable td:nth-child(8) {
					background:#d1ffd2;
				}
				#honorartable tr:nth-child(even) {
					background:#EEE;
				}
				#honorartable tr:nth-child(odd) {
					background:#FAFAFA;
				}
				#honorartable tr:hover {
					background:#FFE;
				}
				.adminbox {
					display:inline-block;
					margin:50px 0;
					padding:20px 30px 10px 20px;
					background:#FFF;
					text-align:right;
					border:1px solid #DDD;					
				}
			</style>';
			

			return $qa_content;
		}
		
	}; // END class booker_page_contractorbalance
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
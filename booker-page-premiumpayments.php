<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_premiumpayments
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
					'title' => 'booker Page Premium Payments', // title of page
					'request' => 'premiumpayments', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='premiumpayments') 
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
				qa_set_template('booker premiumpayments');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			// only gmf members can access
			if(empty($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker premiumpayments');
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
			qa_set_template('booker premiumpayments');
			$qa_content['title'] = qa_lang('booker_lang/premiumpayments');
			
			$weekdays = helper_get_weekdayarray();
			
			// init output
			$qa_content['custom'] = '';

			$contractorname = booker_get_realname($userid);
			
			// check if payments exist
			$payments = qa_db_read_all_assoc( 
								qa_db_query_sub('SELECT payid, paytime, orderid, bookid, userid, paymethod, amount, onhold, ishonorar FROM `^booking_payments` 
														WHERE `userid` = #
														ORDER BY paytime ASC
														;', $userid) );
			if(count($payments)==0)
			{
				$qa_content['custom'] .= '<p style="color:#00F;">'.qa_lang('booker_lang/no_premium_payments').'</p>';
				return $qa_content;
			}

			$paymentstable = '';
			$paymentstable .= '<table id="paymentstable">
				<thead>
					<tr>
						<th>'.qa_lang('booker_lang/date').'</th>
						<th>'.qa_lang('booker_lang/paymethod').'</th>
						<th>'.qa_lang('booker_lang/payamount').'</th>
					</tr>
				</thead>
			';

			foreach($payments as $paym)
			{
				$weekdayx = $weekdays[ (int)(date('N', strtotime($paym['paytime'])))-1 ];
				$paymentstable .= '
					<tr id="'.$paym['payid'].'">
						<td>
							'.$weekdayx.', '.date(qa_lang('booker_lang/date_format_php'), strtotime($paym['paytime'])).'
						</td>
						<td>
							'.$paym['paymethod'].'
						</td>
						<td>
							'.helper_format_currency($paym['amount'], 2, true).' '.qa_opt('booker_currency').'
						</td>
					</tr>
				';
			} // end foreach $bookedEvents
			
			// output 			
			$qa_content['custom'] .= '
				<div class="paymentstablewrap">
			';
			$qa_content['custom'] .= $paymentstable;
			$qa_content['custom'] .= '</table>';
			$qa_content['custom'] .= '</div> <!-- paymentstablewrap -->';
			
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
				.paymentstablewrap {
					display:block;
					width:92%;
					font-size:13px;
				}
				h2#honorar {
					margin-top:50px;
				}
				#paymentstable {
					display:inline-block;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
					width:100%;
					max-width:800px;
				}
				#paymentstable th {
					font-weight:normal;
					background:#FFC;
				}
				#paymentstable td, #paymentstable th {
					padding:10px 5px;
					border:1px solid #DDD;
					font-weight:normal;
				}
				#paymentstable td:nth-child(1) {
					width:35%;
				}
				#paymentstable td:nth-child(2) {
					width:35%;
				}
				#paymentstable td:nth-child(3) {
					width:30%;
					text-align:right;
				}
				#paymentstable tr {
					min-width:300px;
				}
				#paymentstable tr:nth-child(even) {
					background:#EEE;
				}
				#paymentstable tr:nth-child(odd) {
					background:#FAFAFA;
				}
				#paymentstable tr:hover {
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
		
	}; // END class booker_page_premiumpayments
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_adminratings {
		
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
					'title' => 'booker Page Admin Ratings', // title of page
					'request' => 'adminratings', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='adminratings')
			{
				return true;
			}
			return false;
		}

		function process_request($request) 
		{
		
			if(!qa_opt('booker_enabled')) {
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminratings');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$ratinglevels = helper_get_ratinglevels();
			$ratingsymbols = helper_get_ratingsymbols();

			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminratings');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker adminratings');
			$qa_content['title'] = qa_lang('booker_lang/adminrat_title');

			// super admin can have view of others for profile if adding a userid=x to the URL
			if(qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN) 
			{
				$userid = qa_get('userid');
				if(empty($userid)) {
					$userid = qa_get_logged_in_userid();
				}
			}
			
			// init
			$qa_content['custom'] = '';
			
			// get existing ratings of user
			$existingratings = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT created, customerid, contractorid, eventid, rating, text FROM `^booking_ratings` 
													  ORDER BY created DESC
													 ')
													 );
			
			$eventsrated = array();
			$ratingsdone = '';
			
			if(count($existingratings)>0) 
			{
				$ratingsdone .= '
				<div class="ratingstablewrap">
					<table id="ratingstable">
					<tr>
						<th>'.qa_lang('booker_lang/appt').'</th>
						<th>'.qa_lang('booker_lang/adminrat_tab_client').'</th>
						<th>'.qa_lang('booker_lang/adminrat_tab_contractor').'</th>
						<th>'.qa_lang('booker_lang/rating').'</th>
						<th>'.qa_lang('booker_lang/ratingtext').'</th>
					</tr>
				';
				
				foreach($existingratings as $rating) 
				{
					$customerid = $rating['customerid'];
					$contractorid = $rating['contractorid'];
					$eventid = (int)$rating['eventid'];
					// remember eventid so we know which one is rated
					array_push($eventsrated, $eventid);
					
					$customername = booker_get_realname($customerid);
					$contractorname = booker_get_realname($contractorid);
					
					$eventtimes = helper_geteventtimes($eventid);
					
					$ratingsdone .= '
					<tr>
						<td>'.$eventtimes.'</td>
						<td>'.$customername.'</td>
						<td>'.$contractorname.'</td>
						<td>'.
							$ratinglevels[$rating['rating']].
							'<br />'.
							$ratingsymbols[$rating['rating']].
						'</td>
						<td>'.$rating['text'].'</td>
					</tr>';
				}
				$ratingsdone .= '</table> <!-- ratingstable -->
					</div> <!-- ratingstablewrap -->
				';

				// remove duplicates
				$eventsrated = array_unique($eventsrated);
			} // end count $existingratings
			else 
			{
				$qa_content['custom'] .= '
				<p class="qa-error">
					'.qa_lang('booker_lang/noratings').'
				</p>';
			}


			$qa_content['custom'] .= $ratingsdone;
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.eventtime {
					padding:15px 0;
				}
				.infoboxcontact {
					float:right;
					width:250px;
					padding:15px 20px;
					background:#F5F5F5;
					border:1px solid #DDD;
					margin:-20px 0 0 40px;
					text-align:center;
				}
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:95%;
					font-size:13px;
				}
				.qa-main p {
					line-height:150%;
					font-size:13px;
				}
				.ratingbox {
					position: relative;
					display: block;
					width: 100%;
					max-width: 470px;
					margin: 20px 0px 40px;
					background: #F0F0F0;
					border: 1px solid #DDD;
					padding: 5px 15px 45px 15px;
				}
				.ratingtext {
					display:block;
					width:100%;
					max-width:450px;
					height:70px;
					border:1px solid #DDD;
					padding:5px;
				}

				.ratingstablewrap {
					display:block;
					width:92%;
					text-align:right;
					margin-top:30px;
				}
				#ratingstable {
					display:table;
					width:100%;
					max-width:900px;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
				}
				#ratingstable th {
					font-weight:normal;
					background:#FFC;
					border:1px solid #CCC;
				}
				#ratingstable td {
					background:#FFF;
					border:1px solid #CCC;
				}
				#ratingstable td, #ratingstable th {
					padding:3px 5px;
					border:1px solid #DDD;
					font-weight:normal;
					text-align:left;
					vertical-align:top;
				}
				#ratingstable td:nth-child(1) {
					width:15%;
				}
				#ratingstable td:nth-child(2) {
					width:15%;
				}
				#ratingstable td:nth-child(3) {
					width:15%;
				}
				#ratingstable td:nth-child(4) {
					width:15%;
				}
				#ratingstable td:nth-child(5) {
					width:55%;
				}
				
			</style>';
			

			return $qa_content;
			
		} // end process_request

	};
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
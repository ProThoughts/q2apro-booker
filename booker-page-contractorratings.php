<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_contractorratings 
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
					'title' => 'booker Page Contractor Ratings', // title of page
					'request' => 'contractorratings', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='contractorratings') 
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
				qa_set_template('booker contractorratings');
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
				qa_set_template('booker contractorratings');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker contractorratings');
			$qa_content['title'] = qa_lang('booker_lang/conrat_title');

			// super admin can have view of others for profile if adding a userid=x to the URL
			if(qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN) 
			{
				$userid = qa_get('userid');
				if(empty($userid)) 
				{
					$userid = qa_get_logged_in_userid();
				}
			}
			
			// init
			$qa_content['custom'] = '';
			
			$qa_content['custom'] .= '
				<p>
					'.qa_lang('booker_lang/conrat_tellclients').'
				</p>';
			
			// get existing ratings of user
			$existingratings = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT created, customerid, contractorid, eventid, rating, text FROM `^booking_ratings` 
													  WHERE contractorid = #
													  ORDER BY eventid
													 ', 
													$userid));
			
			$eventsrated = array();
			$ratingsdone = '';
			
			if(count($existingratings)>0) 
			{
				$ratingsdone .= '
				<div class="ratingstablewrap">
					<table id="ratingstable">
					<tr>
						<th>'.qa_lang('booker_lang/event').'</th>
						<th>'.qa_lang('booker_lang/client').'</th>
						<th>'.qa_lang('booker_lang/rating').'</th>
						<th>'.qa_lang('booker_lang/ratingtext').'</th>
					</tr>
				';
				
				foreach($existingratings as $rating) 
				{
					$customerid = $rating['customerid'];
					$eventid = (int)$rating['eventid'];
					
					// remember eventid so we know which one is rated
					array_push($eventsrated, $eventid);
					
					$customername = booker_get_realname($customerid);
					
					$eventtimes = helper_geteventtimes($eventid);
					
					$ratingsdone .= '
					<tr>
						<td>'.$eventtimes.'</td>
						<td>'.$customername.'</td>
						<td>'.
							$ratinglevels[$rating['rating']].'<br />'.
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
			
			// jquery
			$qa_content['custom'] .= "
<script>
	$(document).ready(function() 
	{
		
	}); // end jquery ready	
</script>
			";

			// remove ./ from this->urltoroot and add domain in front
			// $imagepath = q2apro_site_url().substr($this->urltoroot, 2, strlen(($this->urltoroot)));
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-sidepanel {
					display:none;
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
				.alreadyrated {
					color:#00D;
				}
				.ratingtext {
					display:block;
					width:100%;
					max-width:450px;
					height:70px;
					border:1px solid #DDD;
					padding:5px;
				}
				.submitrating {
					float:right;
					margin:0;
					padding: 5px 15px;
				}
				.profileimage {
					display: inline-block;
					width:95px;
					vertical-align: top;
					padding: 20px 20px 10px 20px;
					margin: 0px 0px 0px;
					border: 1px solid #DDE;
					background: none repeat scroll 0% 0% #FFF;
					text-align: center;
				}
				.profileimage img {
					max-width:170px;
				}
				.smsg {
					color:#00F;
					display:inline;
					margin:5px;
				}
				.smsg-red {
					color:#F00;
					margin-top:4px;
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
					max-width:800px;
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
					width:55%;
				}
			</style>';
			

			return $qa_content;
			
		} // end process_request

	};
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
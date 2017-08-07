<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_clientratings 
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
					'title' => 'booker Page Client Ratings', // title of page
					'request' => 'clientratings', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='clientratings') 
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
				qa_set_template('booker clientratings');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$ratinglevels = helper_get_ratinglevels();
			$ratingsymbols = helper_get_ratingsymbols();
			
			// only members can access
			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker clientratings');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			$transferString = qa_post_text('ratedata'); // holds one rating array
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
				
				// go over rating and check if time is included
				if(! (isset($newdata['customerid']) && isset($newdata['eventid']) && isset($newdata['rating'])) )
				{
					header('HTTP/1.1 500 Internal Server Error');
					echo 'Error: Start-End-Data missing';
					return;
				}

				$eventid = $newdata['eventid'];
				$customerid = $newdata['customerid'];
				$contractorid = $newdata['contractorid'];
				$rating = $newdata['rating'];
				$text = trim($newdata['text']);
				
				// insert rating in db
				qa_db_query_sub('INSERT INTO ^booking_ratings (created, customerid, contractorid, eventid, rating, text) 
												VALUES (NOW(), #, #, #, #, $)', 
														$customerid, $contractorid, $eventid, $rating, $text);

				// LOG
				$eventname = 'client_rated';
				$params = array(
					'contractorid' => $contractorid,
					'rating' => $rating,
					'text' => $text
				);
				booker_log_event($customerid, $eventid, $eventname, $params);
				
				// inform contractor by email
				$subject = qa_lang('booker_lang/clirat_mail');
				$customername = booker_get_realname($userid);
				$contractorname = booker_get_realname($newdata['contractorid']);
				
				// get session data
				$event = qa_db_read_one_assoc(
								qa_db_query_sub('SELECT starttime,endtime,needs FROM `^booking_orders` 
													WHERE eventid = # 
													AND status = #
													', 
													$eventid, MB_EVENT_COMPLETED), true);
				$needs = '';
				if(isset($event['needs']))
				{
					$needs = qa_lang('booker_lang/eventdetails').': '.helper_shorten_text($event['needs'],150);
				}
				$now = date(qa_lang('booker_lang/date_format_php').' H:i');
				$hasratedstring = qa_lang('booker_lang/clirat_content');
				$hasratedstring = str_replace('~name~', $customername, $hasratedstring);
				$hasratedstring = str_replace('~date~', $now, $hasratedstring);
				
				$emailbody = '';
				$emailbody .= '
					<p>
						'.qa_lang('booker_lang/hello').' '.$contractorname.', 
					</p>
					<p>
						'.$hasratedstring.'
					</p>
					<div style="margin-left:30px;color:#333;">
						<p>
							'.qa_lang('booker_lang/appt_from').' '.date(qa_lang('booker_lang/date_format_php').' H:i', strtotime($event['starttime'])).'
							<br />'
							.$needs.
						'</p>
						<p>
							'.qa_lang('booker_lang/rating').': '.$ratinglevels[$newdata['rating']].'
						</p>
						<p>
							'.qa_lang('booker_lang/comment').': '.$newdata['text'].'
						</p>
					</div>
					<p>
						'.qa_lang('booker_lang/see_ratings').': <a href="'.q2apro_site_url().'contractorratings">'.q2apro_site_url().'contractorratings</a>
					</p>
				';
				$emailbody .= booker_mailfooter();
				
				require_once QA_INCLUDE_DIR.'qa-app-emails.php';
				
				$bcclist = explode(';', qa_opt('booker_mailcopies'));
				q2apro_send_mail(array(
							'fromemail' => q2apro_get_sendermail(),
							'fromname'  => qa_opt('booker_mailsendername'),
							// 'toemail'   => $toemail,
							'senderid'	=> $userid, // for log
							'touserid'  => $newdata['contractorid'],
							'toname'    => $contractorname,
							'bcclist'   => $bcclist,
							'subject'   => $subject,
							'body'      => $emailbody,
							'html'      => true
				));
				
				
				// ajax return success
				$arrayBack = array(
					'updated' => '1'
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX RETURN (ratedata)
			
			
			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker clientratings');
			$qa_content['title'] = qa_lang('booker_lang/clirat_title');

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
					'.qa_lang('booker_lang/clirat_dorate').'
				</p>';
			
			// get existing ratings of user
			$existingratings = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT created, customerid, contractorid, eventid, rating, text FROM `^booking_ratings` 
													  WHERE customerid = #
													  ORDER BY eventid
													 ', 
													$userid));
			
			$eventsrated = array();
			$ratingsdone = '';
			
			if(count($existingratings)>0) 
			{
				$ratingsdone = '
					<h2 style="margin-top:50px;">
						'.qa_lang('booker_lang/clirat_prevratings').'
					</h2>
				';
				$ratingsdone .= '
				<div id="ratingstablewrap">
					<table id="ratingstable">
					<tr>
						<th>'.qa_lang('booker_lang/appt').'</th>
						<th>'.qa_lang('booker_lang/contractor').'</th>
						<th>'.qa_lang('booker_lang/rating').'</th>
						<th>'.qa_lang('booker_lang/ratingtext').'</th>
					</tr>
				';
				
				foreach($existingratings as $rating) 
				{
					$contractorid = $rating['contractorid'];
					$eventid = (int)$rating['eventid'];
					// remember eventid so we know which one is rated
					array_push($eventsrated, $eventid);
					
					// $realname = booker_get_userfield($contractorid, 'realname');
					$contractorname = booker_get_realname($contractorid);
					
					$eventtimes = helper_geteventtimes($eventid);
					
					$ratingsdone .= '
					<tr>
						<td>
							'.$eventtimes.'
						</td>
						<td>
							<a target="_blank" href="'.qa_path('booking').'?contractorid='.$contractorid.'">'.$contractorname.'</a>
						</td>
						<td>
							'.($this->get_select_ratings_options($rating['rating'])).'
							<span class="ratingsub">
								'.$ratinglevels[$rating['rating']].'
							</span>
						</td>
						<td>
							'.$rating['text'].'
						</td>
					</tr>
					';
					// $ratingsymbols[$rating['rating']]
				}
				$ratingsdone .= '</table> <!-- ratingstable -->
					</div> <!-- ratingstablewrap -->
				';

				// remove duplicates
				$eventsrated = array_unique($eventsrated);
			} // end count $existingratings

			// get completed booking data (status = 3)
			$events = qa_db_read_all_assoc(
							qa_db_query_sub('SELECT eventid,starttime,contractorid FROM `^booking_orders` 
													  WHERE customerid = # 
													  AND status = #
													  ORDER BY starttime', 
																$userid, MB_EVENT_COMPLETED));
			if(count($events)>0) 
			{
				foreach($events as $event) 
				{
					$contractorid = $event['contractorid'];
					$eventid = (int)$event['eventid'];
					/*
					if(!contractorisapproved($contractorid)) 
					{
						continue;
					}*/
					
					if(!in_array($eventid, $eventsrated)) 
					{
						/*
						$contractordata = qa_db_read_one_assoc(
										qa_db_query_sub('SELECT handle,avatarblobid FROM ^users 
																			WHERE userid = #', 
																			$contractorid));
						$contractorname = $contractordata['handle'];
						
						$imgsize = 100;
						if(isset($contractordata['avatarblobid'])) 
						{
							$avatar = './?qa=image&qa_blobid='.$contractordata['avatarblobid'].'&qa_size='.$imgsize;
						}
						else 
						{
							$avatar = './?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size='.$imgsize;
						}
						*/

						// $hintrated = '<p class="alreadyrated">Diese Session wurde bereits von dir bewertet. Erneut bewerten?</p>';
						
						$contractorname = booker_get_realname($contractorid);
						
						$qa_content['custom'] .= '
						<div class="ratingbox">
						
							<div class="eventtime">
								'.qa_lang('booker_lang/appt_from').' '.date(qa_lang('booker_lang/date_format_php').' H:i', strtotime($event['starttime'])).' 
								'.qa_lang('booker_lang/admincal_by').' 
								<a target="_blank" href="./booking?contractorid='.$contractorid.'">'.$contractorname.'</a> 
							</div>
							
							<textarea class="ratingtext" placeholder="'.qa_lang('booker_lang/clirat_placeholder_rat').'"></textarea> 
							
							<div class="ratingarea">
								<div class="box-body">
									<select class="barratings" name="rating">
										<option value="1">'.qa_lang('booker_lang/rat_negativ').'</option>
										<option value="2">'.qa_lang('booker_lang/rat_neutral').'</option>
										<option value="3">'.qa_lang('booker_lang/rat_good').'</option>
										<option value="4">'.qa_lang('booker_lang/rat_verygood').'</option>
										<option value="5">'.qa_lang('booker_lang/rat_excellent').'</option>
									</select>
								</div>
							</div> <!-- ratingarea -->

							<button id="'.$eventid.'" class="defaultbutton submitrating" data-contractorid="'.$contractorid.'">
								Absenden
							</button>
								
						</div> <!-- ratingbox -->
						';
					}
					
				} // end foreach $events
				
				/*
						<div class="profileimage">
							<img src="'.$avatar.'" />
							<p style="margin-top:10px;">
								<a target="_blank" href="'.q2apro_site_url().'booking?contractorid='.$contractorid.'">'.$contractorname.'</a>
							</p>
						</div>
				*/
			}
			else 
			{
				$qa_content['custom'] .= '
				<p style="color:#00F;">
					'.qa_lang('booker_lang/noapptyet').'
				</p>';
			}
			
			$qa_content['custom'] .= $ratingsdone;
			
			$qa_content['custom'] .= '
				<div class="infoboxcontact">
					<p>
						'.qa_lang('booker_lang/clirat_contactus').': 
					</p>
					<a class="defaultbutton" href="./mbmessages?to=1" style="padding: 13px 20px;margin-right:0px;">
						'.qa_lang('booker_lang/sendmsg_btn').'
					</a>
				</div>
			';

			// jquery
			$qa_content['custom'] .= "
<script>
	$(document).ready(function() 
	{
		$('.barratings_table').barrating({ 
			theme: 'css-stars', 
			readonly: true, 
			showSelectedRating: false,
		});
		
		$('.barratings').barrating({ 
			theme: 'css-stars', 
			hoverState: false, 
		});
		// $('select').barrating('set', 3);
	
		// ratedata
		$('.submitrating').click( function(e) {
			e.preventDefault();
			var gdata = {
				'customerid': ".$userid.",
				'contractorid': $(this).data('contractorid'),
				'eventid': $(this).attr('id'), 
				'rating': $(this).parent().find('.barratings option:selected').val(),
				'text': $(this).parent().find('.ratingtext').val(),
			};

			var senddata = JSON.stringify(gdata);
			console.log('Sending to Server: '+senddata);
			var clicked = $(this);
			
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { ratedata:senddata },
				dataType: 'json',
				cache: false,
				success: function(data) 
				{
					console.log('server returned: '+data);
					clicked.parent().fadeOut(1000, function()
					{ 
						clicked.parent().parent().append('<p class=\"smsg\">".qa_lang('booker_lang/ratingsaved')."</p>');
						// reload page
						$('.smsg').fadeOut(3000, function() 
						{
							window.location.href = '".qa_self_html()."';
						});
					});
				},
				error: function(xhr, status, error) 
				{
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
				}
				
			}); // end ajax
		});// end changeme click

	}); // end jquery ready
	
</script>
			";

			$qa_content['custom'] .= '
			<style type="text/css">
				.ratingsub {
					font-size:12px;
					color:#FC9;
				}
				.eventtime {
					/*font-size:17px;*/
					padding:15px 0;
				}
				.infoboxcontact {
					width:250px;
					padding:15px 20px;
					background:#F5F5F5;
					border:1px solid #DDD;
					margin:50px 0 0 0;
					text-align:center;
				}
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:80%;
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
				.ratingarea {
					display:block;
					width:40%;
					max-width:235px;
					margin-top:10px;
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
				}
				#ratingstable {
					display:table;
					width:100%;
					max-width:700px;
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
					width:20%;
				}
				#ratingstable td:nth-child(3) {
					width:20%;
				}
				#ratingstable td:nth-child(4) {
					width:45%;
				}
				
				/* barrating plugin */
				.box-body {
					float:left;
				}
				.br-wrapper {					
				}
				
					/*
					.br-wrapper .br-widget {
					  height: 25px;
					}
					.br-wrapper .br-widget a {
					  display: block;
					  width: 20px;
					  height: 20px;
					  float: left;
					  background-color: #FFF; 
					  margin: 1px;
					  outline:0;
					}
					.br-wrapper .br-widget a.br-active,
					.br-wrapper .br-widget a.br-selected {
					  background-color: #0A3; 
					  outline:0;
					}
					.br-wrapper .br-widget .br-current-rating {
					  line-height: 1;
					  float: left;
					  padding: 1px 20px 0 20px;
					  color: #0A3; 
					  font-size: 17px;
					}
					.br-wrapper .br-readonly a.br-active,
					.br-wrapper .br-readonly a.br-selected {
					  background-color: #0A3; 
					}
					.br-wrapper {
					  width: 300px;
					  margin: 0;
					}
					*/
					
					/*
					.br-theme-bars-horizontal .br-widget {
					  width:120px;
					  display:inline-block;
					}
					.br-theme-bars-horizontal .br-widget a {
					  display: block;
					  width: 120px;
					  height: 15px;
					  background-color: #fbedd9;
					  margin: 1px;
					}
					.br-theme-bars-horizontal .br-widget a.br-active,
					.br-theme-bars-horizontal .br-widget a.br-selected {
					  background-color: #edb867;
					}
					.br-theme-bars-horizontal .br-widget .br-current-rating {
					  width: 120px;
					  font-size: 17px;
					  font-weight: 600;
					  line-height: 2;
					  text-align: center;
					  color: #edb867;
					}
					.br-theme-bars-horizontal .br-readonly a.br-active,
					.br-theme-bars-horizontal .br-readonly a.br-selected {
					  background-color: #edb867;
					}
					*/

				.br-theme-css-stars .br-widget {
				  height: 28px;
				  white-space: nowrap;
				}
				.br-theme-css-stars .br-widget a {
				  text-decoration: none;
				  height: 18px;
				  width: 18px;
				  float: left;
				  font-size: 23px;
				  margin-right: 5px;
				}
				.br-theme-css-stars .br-widget a:after {
				  content: "\2605";
				  color: #d2d2d2;
				}
				.br-theme-css-stars .br-widget a.br-active:after {
				  color: #ffa200;
				}
				.br-theme-css-stars .br-widget a.br-selected:after {
				  color: #ffa200;
				}
				.br-theme-css-stars .br-widget .br-current-rating {
				  display:inline-block;
				  margin-left:10px;
				  width: auto;
				  font-size: 17px;
				  line-height: 2;
				  text-align: center;
				  color: #ffa200;
				}
				.br-theme-css-stars .br-readonly a {
				  cursor: default;
				}
				
			</style>';
			

			return $qa_content;
			
		} // end process_request

		private function get_select_ratings_options($rateval) {
			return '
				<select class="barratings_table" name="rating">
					<option value="1"'.($rateval==1?'selected':'').'>'.qa_lang('booker_lang/rat_negativ').'</option>
					<option value="2"'.($rateval==2?'selected':'').'>'.qa_lang('booker_lang/rat_neutral').'</option>
					<option value="3"'.($rateval==3?'selected':'').'>'.qa_lang('booker_lang/rat_good').'</option>
					<option value="4"'.($rateval==4?'selected':'').'>'.qa_lang('booker_lang/rat_verygood').'</option>
					<option value="5"'.($rateval==5?'selected':'').'>'.qa_lang('booker_lang/rat_excellent').'</option>
				</select>
			';
		}
	};
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
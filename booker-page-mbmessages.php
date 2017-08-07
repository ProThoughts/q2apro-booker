<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_mbmessages
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
					'title' => 'booker Page Messages', // title of page
					'request' => 'mbmessages', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='mbmessages')
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
				qa_set_template('booker mbmessages');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker mbmessages');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			$transferString = qa_post_text('msgdata'); // holds one rating array
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
				
				// go over data and check if complete
				if(! (isset($newdata['touserid']) && isset($newdata['content'])) )
				{
					header('HTTP/1.1 500 Internal Server Error');
					echo 'Error: Start-End-Data missing';
					return;
				}

				$touserid = $newdata['touserid'];
				$content = $newdata['content'];
				
				// insert rating in db
				qa_db_query_sub('INSERT INTO ^booking_messages (created, fromuserid, touserid, content) 
												VALUES (NOW(), #, #, #)', 
														$userid, $touserid, $content);
				$messageid = qa_db_last_insert_id();
				
				// LOG
				$eventid = null;
				$eventname = 'msg_sent';
				$params = array(
					// 'fromuserid'  => $userid,
					'messageid'	=> $messageid,
					'touserid'	=> $touserid,
					// merge whitespaces
					'content'	=> preg_replace("/\r|\n/", "", $content)
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				// parse links in $content to html anchors
				$content = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $content);
				
				// get sendername
				$sendername = booker_get_realname($userid);
				// get receivername
				$receivername = booker_get_realname($touserid);
				
				// in case admin is sending a message, use MB name
				/*
				if($sendername=='mathelounge')
				{
					$sendername = qa_opt('booker_mailsendername');
				}
				*/
				
				// send mail to receiver
				$subject = qa_lang('booker_lang/msgfrom').' '.$sendername;
				
				$emailbody = '';

				$emailbody .= '
					<div class="msgmeta">
						<p>
							<span class="msgstatus">'.qa_lang('booker_lang/msgfrom').': <a href="'.q2apro_site_url().'mbmessages?to='.$userid.'">'.$sendername.'</a><span>
							<span class="msgstatus">'.qa_lang('booker_lang/msgto').': '.$receivername.'<span>
							<span class="msgtime">'.date(qa_lang('booker_lang/date_format_php').' H:i').' h</span>
						</p>
						<p class="msgcontent">'.
							$content.'
						</p>
					</div>
					<p style="margin-bottom:100px;">
						<a href="'.q2apro_site_url().'mbmessages?to='.$userid.'" class="defaultbutton">'.qa_lang('booker_lang/msganswer').'</a>
					</p>
				';
				
				$emailbody .= booker_mailfooter();
				$emailbody .= cssemailstyles();
				
				require_once QA_INCLUDE_DIR.'qa-app-emails.php';
				
				$bcclist = explode(';', qa_opt('booker_mailcopies'));
				// do not send admin if admin gets the message anyways
				if($touserid==1)
				{
					$bcclist = array();
				}
				q2apro_send_mail(array(
							'fromemail' => q2apro_get_sendermail(),
							'fromname'  => qa_opt('booker_mailsendername'),
							// 'toemail'   => $toemail,
							'senderid'	=> $userid, // for log
							'touserid'  => $touserid,
							'toname'    => $receivername,
							'bcclist'   => $bcclist,
							'subject'   => $subject,
							'body'      => $emailbody,
							'html'      => true
				));
				
				
				// ajax return success
				$arrayBack = array(
					'updated' => '1',
					'timestamp' => date('Y-m-d H:i'),
					'content' => $content
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX RETURN (msgdata)
			
			
			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker mbmessages');
			$qa_content['title'] = qa_lang('booker_lang/msg_title');

			// init
			$qa_content['custom'] = '';
			
			// super admin can have view of others
			if(qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN) 
			{
				$userid = qa_get('userid');
				if(empty($userid)) 
				{
					$userid = qa_get_logged_in_userid();
				}
			}
			
			$ispremium = booker_ispremium($userid);
			$iscontractor = booker_iscontracted($userid);
			
			if($iscontractor && !$ispremium)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker mbmessages');
				$qa_content['custom'] = booker_become_premium_notify('send_messages');
				return $qa_content;
			}
			
			// get touserid from URL
			$touserid = qa_get('to');
			
			if(empty($touserid))
			{
				// LIST ALL MESSAGES
				// get existing messages of our user
				$existingmessages = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT messageid,created,fromuserid,touserid,content FROM `^booking_messages` 
														  WHERE fromuserid = # OR touserid = #
														  ORDER BY created DESC
														 ', 
														$userid, $userid));
				
				$messagessent = '';
				if(count($existingmessages)>0) 
				{
					// $messagessent = '<h2 style="margin-top:50px;">Bisherige Nachrichten</h2>';
					$messagessent .= '
						<h3 style="margin-top:50px;">
							'.qa_lang('booker_lang/msglist').'
						</h3>
						<div id="messagestablewrap2">
							<div id="messagestable2">
					';
					
					require_once QA_INCLUDE_DIR.'qa-util-string.php';
					
					foreach($existingmessages as $msg) 
					{
						$html = $msg['content'];
						// parse text links to anchors (see plugins/qa-viewer-basic.php)
						$htmlunlinkeds = array_reverse(preg_split('|<[Aa]\s+[^>]+>.*</[Aa]\s*>|', $html, -1, PREG_SPLIT_OFFSET_CAPTURE)); // start from end so we substitute correctly
						foreach ($htmlunlinkeds as $htmlunlinked) { // and that we don't detect links inside HTML, e.g. <img src="http://...">
							$thishtmluntaggeds=array_reverse(preg_split('/<[^>]*>/', $htmlunlinked[0], -1, PREG_SPLIT_OFFSET_CAPTURE)); // again, start from end
							foreach ($thishtmluntaggeds as $thishtmluntagged) 
							{
								$innerhtml=$thishtmluntagged[0];

								if (is_numeric(strpos($innerhtml, '://'))) 
								{ // quick test first
									$newhtml=qa_html_convert_urls($innerhtml, qa_opt('links_in_new_window'));

									$html=substr_replace($html, $newhtml, $htmlunlinked[1]+$thishtmluntagged[1], strlen($innerhtml));
								}
							}
						}
						
						$sendername = '<a href="'.qa_path('mbmessages').'?to='.$msg['fromuserid'].'">'.booker_get_realname($msg['fromuserid']).'</a>';
						$receivername = '<a href="'.qa_path('mbmessages').'?to='.$msg['touserid'].'">'.booker_get_realname($msg['touserid']).'</a>';
						
						$status = 'An: '.$receivername;
						if($msg['touserid']==$userid)
						{
							$status = 'Von: '.$sendername;
						}
						$messagessent .= '
						<div class="msgmeta">
							<p>'.
								'<span class="msgstatus">'.$status.'<span>'.
								'<span class="msgtime">'.date(qa_lang('booker_lang/date_format_php').' H:i', strtotime($msg['created'])).' Uhr</span>
							</p>
							<p class="msgcontent">'.
								$html.'
							</p>
						</div>';
					}
					$messagessent .= '</div> <!-- messagestable2 -->
						</div> <!-- messagestablewrap2 -->
					';

				} // end count $existingmessages
				else
				{
					$messagessent = '
					<p>
						'.qa_lang('booker_lang/nomessages').'
					</p>';
				}

				
				$qa_content['custom'] .= $messagessent;
					
				// $qa_content['error'] = 'Kein Empfänger angegeben.';
				return $qa_content;
			}
			
			
			if($userid==$touserid)
			{
				$qa_content['error'] = qa_lang('booker_lang/msgselferror');
				return $qa_content;
			}
			
			// CHECK that fromuser and touser have/had an event together, otherwise the sending could be abused by others
			// NEW: Allow messages, admin gets copy
			// not for admin
			/*
			if($userid!=1 && $touserid!=1)
			{
				$gotevent = qa_db_read_one_value(
									qa_db_query_sub('SELECT COUNT(*) FROM `^booking_orders` 
														WHERE 
														(customerid = # AND contractorid = #)
														OR
														(customerid = # AND contractorid = #)
														 ', 
														$userid, $touserid, $touserid, $userid), true
											);
				if(!$gotevent)
				{
					$qa_content['error'] = 'Du kannst keine Nachricht an dieses Mitglied senden.';
					return $qa_content;
				}
			}
			*/
			
			// CHECK if sender is part of booking-system by checking userdata entry
			$senderhasdata = qa_db_read_one_value(
								qa_db_query_sub('SELECT COUNT(*) FROM `^booking_users` 
													WHERE userid = #
													 ', 
													$userid), true
										);
			// CHECK if receiver is part of booking-system by checking userdata entry
			$receiverhasdata = qa_db_read_one_value(
								qa_db_query_sub('SELECT COUNT(*) FROM `^booking_users` 
													WHERE userid = #
													 ', 
													$touserid), true
										);
			if($senderhasdata==0)
			{
				$qa_content['error'] = qa_lang('booker_lang/msgerror1');
				return $qa_content;
			}
			else if($receiverhasdata==0)
			{
				$qa_content['error'] = qa_lang('booker_lang/msgerror2');
				return $qa_content;
			}
			
			// get sendername
			$sendername = booker_get_realname($userid);
			// get receivername
			$receivername = booker_get_realname($touserid);
			if(empty($receivername))
			{
				$receivername = '('.qa_lang('booker_lang/nameunknown').')';
			}
			// CHECK if receiver is client or contractor - identified by stated price (weak check)
			$senderiscontractor = booker_iscontracted($userid); // !is_null( booker_get_userfield($userid, 'bookingprice') );
			$receiveriscontractor = booker_iscontracted($touserid); // !is_null( booker_get_userfield($touserid, 'bookingprice') );
			$receiverstatus = '';
			$receiverlink = '';
			
			// message to admin
			if($receiveriscontractor)
			{
				$receiverstatus = qa_lang('booker_lang/msgtocontractor');
				$receiverlink = '<a href="'.qa_path('booking').'?contractorid='.$touserid.'">'.$receivername.'</a>';
			}
			else
			{
				$receiverstatus = qa_lang('booker_lang/msgtoclient');
				$receiverlink = $receivername;
			}
			
			if(!$senderiscontractor && $touserid!=1)
			{
				$qa_content['custom'] .= '
					<div class="infoboxcontact">
						<p>
							'.qa_lang('booker_lang/clirat_contactus').':
						</p>
						<a class="defaultbutton" href="'.qa_path('mbmessages').'?to=1" style="padding: 13px 20px;margin-right:0px;width:185px;">
							'.qa_lang('booker_lang/sendmsg_btn').'
						</a>
					</div>
				';
			}

			if($touserid!=1)
			{
				$qa_content['title'] = qa_lang('booker_lang/msgto').' '.$receivername;
				$qa_content['custom'] .= '
					<p>
						'.$receiverstatus.' 
						'.qa_lang('booker_lang/emailsecret').' 
						'.qa_lang('booker_lang/copysaved').' 
					</p>
				';
			}
			else
			{
				$qa_content['title'] = qa_lang('booker_lang/msgto').' '.qa_opt('booker_mailsendername');
				$receiverlink = qa_lang('booker_lang/msg_to_us');
				$qa_content['custom'] .= '
					<p>
						'.qa_lang('booker_lang/msgtooperator').'
					</p>
				';
			}
			
			// get existing messages of user
			$existingmessages = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT messageid,created,fromuserid,touserid,content FROM `^booking_messages` 
													  WHERE 
													  (fromuserid = # AND touserid = #)
													  OR
													  (fromuserid = # AND touserid = #)
													  ORDER BY created DESC
													 ', 
													$userid, $touserid, $touserid, $userid));
			
			$messagessent = '';
			if(count($existingmessages)>0) 
			{
				$messagessent = '
					<h2 style="margin-top:50px;">'.
						qa_lang('booker_lang/prevmessages').
					'</h2>
					<div id="messagestablewrap">
						<table id="messagestable">
						<tr>
							<th>'.qa_lang('booker_lang/status').'</th>
							<th>'.qa_lang('booker_lang/date').'</th>
							<th>'.qa_lang('booker_lang/msgcontent').'</th>
						</tr>
				';
				
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
				
				foreach($existingmessages as $msg) 
				{
					$html = $msg['content'];
					// parse text links to anchors (see plugins/qa-viewer-basic.php)
					$htmlunlinkeds = array_reverse(preg_split('|<[Aa]\s+[^>]+>.*</[Aa]\s*>|', $html, -1, PREG_SPLIT_OFFSET_CAPTURE)); // start from end so we substitute correctly
					foreach ($htmlunlinkeds as $htmlunlinked) 
					{ // and that we don't detect links inside HTML, e.g. <img src="http://...">
						$thishtmluntaggeds=array_reverse(preg_split('/<[^>]*>/', $htmlunlinked[0], -1, PREG_SPLIT_OFFSET_CAPTURE)); // again, start from end
						foreach ($thishtmluntaggeds as $thishtmluntagged) 
						{
							$innerhtml=$thishtmluntagged[0];

							if (is_numeric(strpos($innerhtml, '://'))) 
							{ // quick test first
								$newhtml=qa_html_convert_urls($innerhtml, qa_opt('links_in_new_window'));

								$html=substr_replace($html, $newhtml, $htmlunlinked[1]+$thishtmluntagged[1], strlen($innerhtml));
							}
						}
					}
					
					$status = qa_lang('booker_lang/sent');
					if($msg['touserid']==$userid)
					{
						$status = qa_lang('booker_lang/received');
					}
					$messagessent .= '
					<tr>
						<td>'.$status.'</td>
						<td>'.date(qa_lang('booker_lang/date_format_php').' H:i', strtotime($msg['created'])).'</td>
						<td>'.$html.'</td>
					</tr>';
				}
				$messagessent .= '
						</table> <!-- messagestable -->
					</div> <!-- messagestablewrap -->
				';

			} // end count $existingmessages

			
			// from to send a message to touserid
			$qa_content['custom'] .= '
			<div class="messagebox">
			
				<p>
					'.qa_lang('booker_lang/yourmsgto').' '.$receiverlink.': 
				</p>
				
				<textarea class="messagetext" placeholder="'.qa_lang('booker_lang/insertmsg').'"></textarea> 
				
				<button id="msgsendbutton" class="defaultbutton submitmessage">
					'.qa_lang('booker_lang/btn_submit').'
				</button>
					
			</div> <!-- messagebox -->
			';
					
			$qa_content['custom'] .= $messagessent;
			
			// jquery
			$qa_content['custom'] .= "
<script>
	$(document).ready(function() 
	{
	
		// messagedata
		$('.submitmessage').click( function(e) {
			e.preventDefault();
			var gdata = {
				'touserid': ".$touserid.",
				'content': $(this).parent().find('.messagetext').val(),
			};

			var senddata = JSON.stringify(gdata);
			console.log('Sending to Server: '+senddata);
			var clicked = $(this);
			
			// disable button
			$(this).prop('disabled', true);
			
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { msgdata:senddata },
				dataType: 'json',
				cache: false,
				success: function(data) 
				{
					console.log('server returned: '+data['timestamp']+' | '+data['content']);
					clicked.parent().fadeOut(1000, function() { 
						clicked.parent().after('<p class=\"smsg\">".qa_lang('booker_lang/msgsent')."</p>');						
					});
					// create entry in message table
					$('#messagestable tr:first-child').after('<tr style=\"color:#55F;\"><td>".qa_lang('booker_lang/sent')."</td><td>'+data['timestamp']+' Uhr</td><td>'+data['content']+'</td></tr>');
					$(this).prop('disabled', false);
				},
				error: function(xhr, status, error) 
				{
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
					$(this).prop('disabled', false);
				}
				
			}); // end ajax
		});// end changeme click

	}); // end jquery ready
	
</script>
			";

			// remove ./ from this->urltoroot and add domain in front
			// $imagepath = q2apro_site_url().substr($this->urltoroot, 2, strlen(($this->urltoroot)));
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.messagebox {
					position: relative;
					display: block;
					width: 100%;
					max-width: 500px;
					margin: 20px 0px 40px;
					background: #F5F5F5;
					border: 1px solid #DDD;
					padding: 15px 15px 45px 15px;
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
					width:80%;
					font-size:13px;
				}
				.qa-main p {
					line-height:150%;
					font-size:13px;
				}
				.messagetext {
					display:block;
					width:100%;
					max-width:480px;
					min-height:100px;
					border:1px solid #DDD;
					padding:5px;
					margin-bottom:10px;
				}
				.submitmessage {
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
				}
				.smsg-red {
					color:#F00;
					margin-top:4px;
				}
			</style>';
			

			return $qa_content;
			
		} // end process_request

	}; // end class
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
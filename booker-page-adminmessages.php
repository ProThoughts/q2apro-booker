<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_adminmessages
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
					'request' => 'adminmessages', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='adminmessages')
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
				qa_set_template('booker adminmessages');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminmessages');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}
			
			// AJAX to delete msg
			$transferString = qa_post_text('deletemsg');
			if(isset($transferString))
			{
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
				// delete event
				$msgid = (int)($newdata['msgid']);
				
				// LOG message before deleting
				$eventid = null;
				$eventname = 'msg_deleted';
				$params = qa_db_read_one_assoc(
								qa_db_query_sub('SELECT messageid, created, fromuserid, touserid, content FROM `^booking_messages` 
									WHERE messageid = #
									;', $msgid)
							);
				if(isset($params['content']))
				{
					// remove linebreaks
					$params['content'] = preg_replace("/\r|\n/", "", $params['content']);
				}
				booker_log_event($userid, $eventid, $eventname, $params);
				
				qa_db_query_sub('DELETE FROM `^booking_messages` 
									WHERE messageid = #', 
									$msgid);
				// ajax return success
				echo json_encode('message '.$msgid.' deleted');
				exit(); 
			} // END AJAX RETURN (delete)
			
			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker adminmessages');
			$qa_content['title'] = qa_lang('booker_lang/adminmsg_title');

			// init
			$qa_content['custom'] = '';
			
			// LIST ALL MESSAGES
			// get existing messages of our user
			$existingmessages = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT messageid,created,fromuserid,touserid,content FROM `^booking_messages` 
													  ORDER BY created DESC
													 ')
													);
			
			$messagessent = '';
			if(count($existingmessages)>0) 
			{
				$messagessent .= '
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
						foreach ($thishtmluntaggeds as $thishtmluntagged) {
							$innerhtml=$thishtmluntagged[0];

							if (is_numeric(strpos($innerhtml, '://'))) { // quick test first
								$newhtml=qa_html_convert_urls($innerhtml, qa_opt('links_in_new_window'));

								$html=substr_replace($html, $newhtml, $htmlunlinked[1]+$thishtmluntagged[1], strlen($innerhtml));
							}
						}
					}
					
					$sendername = '<a href="'.qa_path('mbmessages').'?to='.$msg['fromuserid'].'">'.booker_get_realname($msg['fromuserid']).'</a>';
					$receivername = '<a href="'.qa_path('mbmessages').'?to='.$msg['touserid'].'">'.booker_get_realname($msg['touserid']).'</a>';
					
					$status = qa_lang('booker_lang/adminmsg_from').': '.$sendername.' → '.qa_lang('booker_lang/adminmsg_to').': '.$receivername;
					
					$messagessent .= '
					<div class="msgmeta" data-messageid="'.$msg['messageid'].'">
						<p>'.
							'<span class="msgstatus">'.$status.'<span>'.
							'<span class="msgtime">'.date(qa_lang('booker_lang/date_format_php').' H:i', strtotime($msg['created'])).' h</span>
						</p>
						<p class="msgcontent">'.
							$html.'
						</p>
						
						<div class="deletemsg">'.qa_lang('booker_lang/delete').'</div>
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
			
/*			
			foreach($existingmessages as $msg) 
			{
					$html = $msg['content'];
					// parse text links to anchors (see plugins/qa-viewer-basic.php)
					$htmlunlinkeds = array_reverse(preg_split('|<[Aa]\s+[^>]+>.*</[Aa]\s*>|', $html, -1, PREG_SPLIT_OFFSET_CAPTURE)); // start from end so we substitute correctly
					foreach ($htmlunlinkeds as $htmlunlinked) { // and that we don't detect links inside HTML, e.g. <img src="http://...">
						$thishtmluntaggeds=array_reverse(preg_split('/<[^>]*>/', $htmlunlinked[0], -1, PREG_SPLIT_OFFSET_CAPTURE)); // again, start from end
						foreach ($thishtmluntaggeds as $thishtmluntagged) {
							$innerhtml=$thishtmluntagged[0];

							if (is_numeric(strpos($innerhtml, '://'))) { // quick test first
								$newhtml=qa_html_convert_urls($innerhtml, qa_opt('links_in_new_window'));

								$html=substr_replace($html, $newhtml, $htmlunlinked[1]+$thishtmluntagged[1], strlen($innerhtml));
							}
						}
					}
					
					$status = 'Gesendet';
					if($msg['touserid']==$userid)
					{
						$status = 'Empfangen';
					}
					$messagessent .= '
					<tr>
						<td>'.$status.'</td>
						<td>'.date(qa_lang('booker_lang/date_format_php').' H:i', strtotime($msg['created'])).' Uhr</td>
						<td>'.$html.'</td>
					</tr>';
			}
			$messagessent .= '</table> <!-- messagestable -->
				</div> <!-- messagestablewrap -->
			';

			$qa_content['custom'] .= $messagessent;
*/

			// jquery
			$qa_content['custom'] .= "
<script>
	$(document).ready(function() 
	{
		// delete message
		$('.deletemsg').click( function(e) {
			e.preventDefault();
			
			var messageid = $(this).parent().data('messageid');
			
			var gdata = {
				'msgid': messageid,
			};

			var senddata = JSON.stringify(gdata);
			console.log('Sending to Server: '+senddata);
			var clicked = $(this);
			
			var confirmdg = confirm('".qa_lang('booker_lang/adminmsg_confirmdel')."');
			if(confirmdg==false)
			{
				return;
			}
			
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { deletemsg:senddata },
				dataType: 'json',
				cache: false,
				success: function(data) 
				{
					console.log('server returned: '+data);
					clicked.parent().fadeOut(500, function() { 
						clicked.parent().after('<p class=\"smsg\">".qa_lang('booker_lang/message_deleted')."</p>');						
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
				.msgmeta {
					position: relative;
				}
				.deletemsg {
					position:absolute;
					bottom:0px;
					right:3px;
					font-size:12px;
					color:#D00;
					cursor:pointer;
				}
				.deletemsg:hover {
					color:#F00;
					text-decoration:underline;
				}
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
					width:95%;
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
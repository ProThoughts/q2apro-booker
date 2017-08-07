<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_adminrequests 
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
					'title' => 'booker Page Admin Requests', // title of page
					'request' => 'adminrequests', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if($request=='adminrequests') 
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
				qa_set_template('booker adminrequests');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminrequests');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}
			
			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker adminrequests');
			$qa_content['title'] = qa_lang('booker_lang/adminrequ_title');

			// init
			$qa_content['custom'] = '';
			
			// get existing requests of user
			$existingrequests = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT requestid, created, userid, title, price, end, description, location, status FROM `^booking_requests` 
													  WHERE status != #
													  ORDER BY end ASC
													', MB_REQUEST_DELETED)
													);
												  // WHERE (end > NOW() OR end IS NULL)
			
			$clirequests = '';
			
			if(count($existingrequests)>0) 
			{
				$clirequests .= '
				<div class="requeststablewrap">
					<table id="requeststable">
					<tr style="display:none;">
						<th>'.qa_lang('booker_lang/requests').'</th>
						<th>'.qa_lang('booker_lang/priceoffers').'</th>
					</tr>
				';
				// <th>'.qa_lang('booker_lang/description').'</th>
				// <th>'.qa_lang('booker_lang/status').'</th>
				// <th>'.qa_lang('booker_lang/options').'</th>
				$imageexts = array('jpg', 'jpeg', 'png', 'gif');
				
				foreach($existingrequests as $request) 
				{
					// default image
					// $requestimage = '<img class="requestimg listnoimage" src="'.$this->urltoroot.'images/icon-noimage.png" alt="no image" />';
					$requestimage = $this->urltoroot.'images/icon-noimage.png';
					
					if(!empty($request['description']))
					{
						$text = $request['description'];
						// check if image tag in post, must be in upload folder
						$uploadfolder = qa_opt('booker_uploadurl');
						if(strpos($text, $uploadfolder) !== false) 
						{
							// get all URLs
							$urls = helper_get_imagelinks_from_htmlstring($text);
							foreach($urls as $url)
							{
								$ext = pathinfo($url, PATHINFO_EXTENSION);
								if(in_array($ext,$imageexts))
								{
									// found blobid add link to array
									$requestimage = $url;
 								}
							}
						}
					}
					
					$existingbids = booker_get_bids($request['requestid']);
					if(!empty($existingbids)) // *** and end time for request reached
					{
						$existingbids .= '
							<a href="#" class="defaultbutton btn_green choosebid_btn">'.qa_lang('booker_lang/choosebid').'</a>
						';
					}
					else
					{
						$existingbids .= '
							<span class="nobidsyet">'.qa_lang('booker_lang/nobidsyet').'</span> 
						';
					}
					
					$requeststatus = $request['status'];
					$statustitle = '';
					$statusshow = '';
					$requesttitlecolor = ' style="color:#777;" ';
					
					$optionlink_edit = '
								<a class="optionlink" href="'.qa_path('requestcreate').'?requestid='.$request['requestid'].'" title="'.qa_lang('booker_lang/edit').'">
									<i class="fa fa-pencil fa-lg"></i>
								</a>
					';
					$optionlink_delete = '
								<a class="optionlink deletelink" data-requestid='.$request['requestid'].'" title="'.qa_lang('booker_lang/delete').'">
									<i class="fa fa-remove fa-lg"></i>
								</a>
					';
					$optionlink_approve = '
								<a class="optionlink approvelink" data-requestid='.$request['requestid'].'" title="'.qa_lang('booker_lang/approve').'">
									<i class="fa fa-check fa-lg"></i>
								</a>
					';
					$optionlink_disapprove = '';
					
					if($requeststatus==MB_REQUEST_CREATED)
					{
						$statustitle = ' title="'.qa_lang('booker_lang/notapprovedyet').'" ';
						$statusshow = '<i class="fa fa-check-square notyetapproved tooltip"></i>';
						$optionlink_disapprove = '
									<a class="optionlink disapprovelink" data-requestid='.$request['requestid'].'" title="'.qa_lang('booker_lang/disapprove').'">
										<i class="fa fa-ban fa-lg"></i>
									</a>
						';
					}
					else if($requeststatus==MB_REQUEST_APPROVED)
					{
						$statustitle = ' title="'.qa_lang('booker_lang/approved').'" ';
						$statusshow = '<i class="fa fa-check-square tooltip"></i>';
						$requesttitlecolor = ' style="color:#0A0;" ';
						$optionlink_approve = '';
					}
					else if($requeststatus==MB_REQUEST_DISAPPROVED)
					{
						$statustitle = ' title="'.qa_lang('booker_lang/disapproved').'" ';
						$statusshow = '<i class="fa fa-ban tooltip"></i>';
						$requesttitlecolor = ' style="color:#A33;" ';
						// $optionlink_approve = '';
						// only show option to delete if request is min. 5 days old (so that user can discover the disapproved status)
						// *** or we implement an email message on status change (approve / disapprove)!
						if(time() < helper_datetime_to_seconds($request['created']) + 5*24*60*60)
						{
							$optionlink_delete = '';							
						}
					}
					else if($requeststatus==MB_REQUEST_DELETED)
					{
						$statustitle = ' title="'.qa_lang('booker_lang/deleted').'" ';
						// $statusshow = '<i class="fa fa-minus-circle tooltip"></i>';
						$statusshow = '<i class="fa fa-trash-o tooltip"></i>';
						$requesttitlecolor = ' style="color:#AAA;" ';
						$optionlink_delete = '';
						$optionlink_approve = '';
						$optionlink_edit = '';
					}
					
					$requestoptions = '
							<div class="requestoptions">
								'
								.$optionlink_approve.
								'
								'
								.$optionlink_disapprove.
								'
								'
								.$optionlink_edit.
								'
								'
								.$optionlink_delete.
								'
							</div>
					';
					/*if($requeststatus==MB_REQUEST_DELETED || $requeststatus==MB_REQUEST_DISAPPROVED)
					{
						$requestoptions = '';
					}
					*/
					
					$contractorname = booker_get_realname($request['userid']);
					
					if(empty($contractorname))
					{
						$contractorname = helper_gethandle($request['userid']).'<br />('.qa_lang('booker_lang/forum_profile').')';
					}
					
					$clirequests .= '
					<tr data-requestid="'.$request['requestid'].'" id="req'.$request['requestid'].'">
						<td>
							<a class="requestlistimage" href="'.qa_path('bid').'?requestid='.$request['requestid'].'" 
								style="background-image:url(\''.$requestimage.'\')">
							</a>
							<p class="requeststatus tooltip" '.$statustitle.'>
								'.$statusshow.'
								<a href="'.qa_path('bid').'?requestid='.$request['requestid'].'" class="requesttitle" '.$requesttitlecolor.'>
									'.$request['title'].'
								</a>
							</p>
							<p class="requestpreviewtext">
								'.helper_shorten_text($request['description'], 100).'
							</p>
							'
							.$requestoptions.
							'
						</td>
						<td>
							<p class="requestend">
								'.qa_lang('booker_lang/end').': 
								'.(empty($request['end']) ? '-' : helper_get_readable_date_from_time($request['end'], true, false)).'
								<br />
								<span style="font-size:9px;color:#999;">
								'.qa_lang('booker_lang/created').': 
								'.(empty($request['created']) ? '-' : helper_get_readable_date_from_time($request['created'], true, false)).'
								</span>
								<a class="requestusername" target="_blank" href="'.qa_path('userprofile').'?userid='.$request['userid'].'">
									'.$contractorname.'
								</a>
							</p>
						</td>
						<td>
							'.$existingbids.'
						</td>
					</tr>
					';
				}
				// <td>'.$request['status'].'</td>
				// <td>'.$request['description'].'</td>
				$clirequests .= '
						</table> <!-- requeststable -->
					</div> <!-- requeststablewrap -->
				';

			} // end count $existingrequests
			else 
			{
				$qa_content['custom'] .= '
				<p class="qa-error">
					'.qa_lang('booker_lang/norequests').'
				</p>';
			}

			$qa_content['custom'] .= $clirequests;
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.requesttitle {
					color:#33F;
				}
				.requeststatus {
					display:inline-block;
					max-width: 70%;
					line-height:125% !important;
					margin-bottom:0;
				}
				.requeststatus a {
					text-decoration:none !important;
				}
				.requeststatus i {
					cursor:default;
				}
				.requestpreviewtext {
					color:#AAA;
					font-size:11px !important;
					margin:0;
				}
				.requestdata {
					line-height:120%;
					margin:0;
				}
				.requestdata a {
					text-decoration:none !important;
				}
				.requestusername {
					display:block;
					font-size:11px;
					color:#35C;
					text-decoration:none !important;
				}
				.requestend {
					color:#333;
					font-size:11px !important;
					float:right;
					text-align:right;
					margin-top:5px;
				}
				.nobidsyet {
					font-size:11px;
					color:#66F;
				}
				.requestlistimage {
					display:inline-block;
					vertical-align:top;
					float:left;
					margin-right:10px;
					width:100px;
					height:100px;
					background-repeat: no-repeat;
					/*background-position: top center;
					background-size: 100% 100%; */
					background-position:50% 50%;
					background-size:cover;
					border-radius:5px;
				}
				.requestoptions .fa-lg {
					cursor:default;
				}
				.optionlink .fa-lg {
					cursor:pointer;
				}
				.requestoptions .fa-check:hover {
					color:#3C3;
				}
				.requestoptions .fa-remove {
					color:#999;
				}
				.requestoptions .fa-remove:hover {
					color:#F33;
				}
				.requestoptions .fa-ban {
					color:#999;
				}
				.requeststatus .fa-check-square, 
				.requestoptions .fa-check-square {
					color:#3A3;
				}
				.requeststatus .fa-ban {
					color:#D33;
				}
				.requestoptions .fa-ban:hover {
					color:#F33;
				}
				.requestoptions .fa-low-vision {
					color:#777;
				}
				.requeststatus .fa-trash,
				.requeststatus .fa-trash-o,
				.requeststatus .fa-minus-circle,
				.requestoptions .fa-minus-square, 
				.requestoptions .fa-minus-circle {
					color:#C77;
				}
				.notyetapproved {
					color:#CCC !important;
				}
				.requestoptions {
					margin-bottom:10px;
				}
				.optionlink {
					color:#999;
					margin:2px 5px 2px 3px;
				}
				.optionlink:hover {
					text-decoration:none;
					color:#44C;
				}
				.editlink {
					font-size:17px;
				}
				.deletelink {
					color:#844;
					cursor:pointer;
				}
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:95%;
					font-size:13px;
					margin-bottom:200px;
				}
				.qa-main p {
					line-height:150%;
					font-size:13px;
				}
				.requestbox {
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
				.requesttext {
					display:block;
					width:100%;
					max-width:450px;
					height:70px;
					border:1px solid #DDD;
					padding:5px;
				}
				.choosebid_btn {
					padding:3px 8px;
					margin:10px 0 0 4px;
					font-size:12px;
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

				.requeststablewrap {
					display:block;
					width:92%;
					text-align:right;
					margin-top:30px;
				}
				#requeststable {
					display:table;
					width:100%;
					max-width:800px;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
				}
				#requeststable th {
					display:none;
				}
				#requeststable tr:nth-child(even) {
					background:#F9F9FA;
				}
				/*
				#requeststable tr:hover {
					background:#F5F5F5 !important;
				}*/
				#requeststable td {
					padding:10px 10px;
					border-bottom:1px solid #EEE;
					font-weight:normal;
					text-align:left;
					vertical-align:top;
				}
				#requeststable td:nth-child(1) {
					width:70%;
				}
				#requeststable td:nth-child(2) {
					width:20%;
					text-align:right;
					vertical-align:top;
				}
				#requeststable td:nth-child(3) {
					width:10%;
					text-align:right;
				}
				/* important */
				.bidtable {
					font-size:11px;
					list-style-type:none;
					padding:0 0 0 3px;
				}
				
			</style>';
			
			// jquery
			$qa_content['custom'] .= "
	<script>
		$(document).ready(function()
		{
			$('.deletelink').click( function(e) 
			{
				e.preventDefault();
				var requesttitle = $(this).parent().parent().parent().find('.requesttitle').text().trim();
				var clicked = $(this);
				if(confirm('".qa_lang('booker_lang/removerequest_confirm')." \\n\"'+requesttitle+'\"'))
				{
					var requestid = $(this).parent().parent().parent().data('requestid');
					var requestdata = {
						requestid: requestid,
						userid: ".$userid." 
					};
					console.log(requestdata);
					$.ajax({
						type: 'POST',
						url: '".qa_path('ajaxhandler')."',
						data: { request_delete: JSON.stringify(requestdata) },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: ');
							console.log(data);
							if(data['status']=='success')
							{
								$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/request_deleted')."</p>').insertAfter(clicked);
								$('.qa-success').fadeOut(1500, function() { 
									$(this).remove();
									clicked.parent().parent().parent().remove();
									// window.scrollTo(0, 0);
									// reload page
									window.location.href = '".qa_self_html()."';
								});
							}
							else
							{
								console.log('Problem with server');
							}
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				}
			}); // end deletelink
			
			$('.approvelink').click( function(e) 
			{
				e.preventDefault();
				var requesttitle = $(this).parent().parent().parent().find('.requesttitle').text().trim();
				var clicked = $(this);
				if(confirm('".ucfirst(qa_lang('booker_lang/approve'))."? \\n\"'+requesttitle+'\"'))
				{
					var requestid = $(this).parent().parent().parent().data('requestid');
					var requestdata = {
						requestid: requestid,
						userid: ".$userid." 
					};
					console.log(requestdata);
					$.ajax({
						type: 'POST',
						url: '".qa_path('ajaxhandler')."',
						data: { request_approve: JSON.stringify(requestdata) },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: ');
							console.log(data);
							if(data['status']=='success')
							{
								$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/request_approved')."</p>').insertAfter(clicked);
								$('.qa-success').fadeOut(1500, function() { 
									$(this).remove();
									// window.scrollTo(0, 0);
									// reload page
									window.location.href = '".qa_self_html()."';
								});
							}
							else
							{
								console.log('Problem with server');
							}
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				}
			}); // end approvelink

			
			$('.disapprovelink').click( function(e) 
			{
				e.preventDefault();
				var requesttitle = $(this).parent().parent().parent().find('.requesttitle').text().trim();
				var clicked = $(this);
				if(confirm('".ucfirst(qa_lang('booker_lang/disapprove'))."? \\n\"'+requesttitle+'\"'))
				{
					var requestid = $(this).parent().parent().parent().data('requestid');
					var requestdata = {
						requestid: requestid,
						userid: ".$userid." 
					};
					console.log(requestdata);
					$.ajax({
						type: 'POST',
						url: '".qa_path('ajaxhandler')."',
						data: { request_disapprove: JSON.stringify(requestdata) },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: ');
							console.log(data);
							if(data['status']=='success')
							{
								$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/request_gotdisapproved')."</p>').insertAfter(clicked);
								$('.qa-success').fadeOut(1500, function() { 
									$(this).remove();
									// window.scrollTo(0, 0);
									// reload page
									window.location.href = '".qa_self_html()."';
								});
							}
							else
							{
								console.log('Problem with server');
							}
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				}
			}); // end disapprovelink

		}); // end jquery ready

	</script>
			";

			return $qa_content;
			
		} // end process_request

	};
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
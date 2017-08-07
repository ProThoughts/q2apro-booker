<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_clientrequests
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
					'title' => 'booker Page Client Requests', // title of page
					'request' => 'clientrequests', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if($request=='clientrequests')
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
				qa_set_template('booker clientrequests');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			$userid = qa_get_logged_in_userid();

			// only members can access
			if(empty($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker clientrequests');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}

			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker clientrequests');
			$qa_content['title'] = qa_lang('booker_lang/clirequ_title');

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
					'.qa_lang('booker_lang/clirequ_intro').'
				</p>';

			// get existing requests of user created within last 3 weeks
			$created_withinlastdays = null; // 21;
			$existingrequests = booker_get_requests($userid, $created_withinlastdays, false);

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

					$statustitle = '';
					$statusshow = '';
					$requeststatus = $request['status'];
					$requesttitlecolor = ' style="color:#777;" ';
					$optionlink_edit = '';
					$optionlink_delete = '
								<a class="optionlink deletelink" data-requestid='.$request['requestid'].'" title="'.qa_lang('booker_lang/delete').'">
									<i class="fa fa-trash-o fa-lg"></i>
								</a>
					';
					$optionlink_approve = '
								<a class="optionlink approvelink" data-requestid='.$request['requestid'].'" title="'.qa_lang('booker_lang/approve').'">
									<i class="fa fa-check fa-lg"></i>
								</a>
					';

					if($requeststatus==MB_REQUEST_CREATED)
					{
						$statustitle = ' title="'.qa_lang('booker_lang/notapprovedyet').'" ';
						$statusshow = '<i class="fa fa-check-square notyetapproved tooltip"></i>';
						$optionlink_edit = '
									<a class="optionlink" href="'.qa_path('requestcreate').'?requestid='.$request['requestid'].'" title="'.qa_lang('booker_lang/edit').'">
										<i class="fa fa-pencil fa-lg"></i>
									</a>
						';
					}
					else if($requeststatus==MB_REQUEST_APPROVED)
					{
						$statustitle = ' title="'.qa_lang('booker_lang/approved').'" ';
						$statusshow = '<i class="fa fa-check-square tooltip"></i>';
						$requesttitlecolor = ' style="color:#0A0;" ';
						$optionlink_delete = '';
					}
					else if($requeststatus==MB_REQUEST_DISAPPROVED)
					{
						$statustitle = ' title="'.qa_lang('booker_lang/disapproved').'" ';
						$statusshow = '<i class="fa fa-ban tooltip"></i>';
						$requesttitlecolor = ' style="color:#A33;" ';
					}
					else if($requeststatus==MB_REQUEST_DELETED)
					{
						$statustitle = ' title="'.qa_lang('booker_lang/deleted').'" ';
						$statusshow = '<i class="fa fa-minus-circle tooltip"></i>';
						$requesttitlecolor = ' style="color:#AAA;" ';
						$optionlink_delete = '';
					}

					$requestoptions = '
							<div class="requestoptions">
								'
								.$optionlink_edit.
								'
								'
								.$optionlink_delete.
								'
							</div>
					';

					$bidlink = qa_path('bid').'?requestid='.$request['requestid'];
					$sharelink = q2apro_site_url().'bid?requestid='.$request['requestid'];
					$shareoptions = '
						<a class="fbsharelink" data-shareurl="'.$sharelink.'">
							<i class="fa fa-facebook-square fa-lg"></i>
							<span class="fbsharespan">'.qa_lang('booker_lang/sharefacebook').'</span>
						</a>
					';

					$clirequests .= '
					<tr data-requestid="'.$request['requestid'].'" id="req'.$request['requestid'].'">
						<td>
							<a class="requestlistimage" href="'.$bidlink.'"
								style="background-image:url(\''.$requestimage.'\')">
							</a>
							<p class="requeststatus tooltip" '.$statustitle.'>
								'.$statusshow.'
								<a href="'.$bidlink.'" class="requesttitle tooltip"'.$requesttitlecolor.'>
									'.$request['title'].'
								</a>
							</p>
							<p class="requestpreviewtext">
								'.helper_shorten_text($request['description'], 100).'
							</p>
							<p class="requestend">
								'.qa_lang('booker_lang/end').':
								'.(empty($request['end']) ? '-' : helper_get_readable_date_from_time($request['end'], true, false)).'
							</p>
							'
							.$requestoptions.
							'
							'
							.$shareoptions.
							'
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

			$qa_content['custom'] .= '
									<p style="margin-top:30px;">
										<a href="'.qa_path('requestcreate').'" class="defaultbutton senddatabtn">'.qa_lang('booker_lang/newrequest_create_btn').'</a>
									</p>
								';

			$qa_content['custom'] .= $clirequests;

			$qa_content['custom'] .= '
			<style type="text/css">
				.requesttitle {
					color:#33F;
				}
				.requeststatus {
					display:inline-block;
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
					color:#777;
					font-size:11px !important;
					margin:0;
				}
				.requestend {
					color:#777;
					font-size:11px !important;
					margin:2px 0;
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
				.fa-lg {
					cursor:default;
				}
				.fbsharelink {
					display:block;
					text-decoration:none !important;
				}
				.fa-facebook-square {
					color:#3B5998;
					cursor:pointer;
				}
				.fbsharespan {
					color:#3B5998;
					cursor:pointer;
					font-size:12px;
				}
				.fbsharespan:hover {
					color:#57E;
				}
				.optionlink .fa-lg {
					cursor:pointer;
				}
				.fa-check:hover {
					color:#3C3;
				}
				.fa-remove {
					color:#999;
				}
				.fa-remove:hover {
					color:#F33;
				}
				.fa-check-square {
					color:#3A3;
				}
				.fa-ban {
					color:#D33;
				}
				.fa-low-vision {
					color:#777;
				}
				.fa-minus-square,
				.fa-minus-circle {
					color:#C77;
				}
				.notyetapproved {
					color:#CCC;
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
					width:75%;
				}
				#requeststable td:nth-child(2) {
					width:25%;
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
									window.scrollTo(0, 0);
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
				else
				{
					// do nothing
				}
			}); // end deletelink

			$('.fbsharelink').click( function()
			{
				var shareurl = $(this).data('shareurl');
				window.open('https://www.facebook.com/sharer/sharer.php?u='+escape(shareurl)+'&t='+document.title, '',
				'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=300,width=600');
				return false;
			});

		}); // end jquery ready

	</script>
			";

			return $qa_content;

		} // end process_request

	};

/*
	Omit PHP closing tag to help avoid accidental output
*/

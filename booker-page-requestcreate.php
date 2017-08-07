<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_requestcreate
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
					'title' => 'booker Page requestcreate', // title of page
					'request' => 'requestcreate', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='requestcreate') 
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
				qa_set_template('booker requestcreate');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker requestcreate');
				$qa_content['title'] = qa_lang('booker_lang/request_create');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			// super admin can have view of others for profile if adding a userid=x to the URL
			if(qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN) 
			{
				$userid = qa_get('userid');
				if(empty($userid)) 
				{
					$userid = qa_get_logged_in_userid();
				}
			}
			
			// AJAX: user is submitting data of request
			$transferString = qa_post_text('requestdata');
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				// only user can post an request
				$requesttitle = empty($newdata['requesttitle']) ? null : trim($newdata['requesttitle']);
				$requestend = empty($newdata['requestend']) ? null : trim($newdata['requestend']);
				$location = empty($newdata['requestlocation']) ? null : trim($newdata['requestlocation']);
				$description = $newdata['description'];
				if(!empty($newdata['userid']) && qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN)
				{
					$userid = $newdata['userid'];
				}
				$requestid = empty($newdata['requestid']) ? null : $newdata['requestid'];
				$isedit = !empty($newdata['isedit']);
				
				if(empty(trim(strip_tags($description))))
				{
					$description = null;
				}
				
				// string to time
				if(isset($requestend))
				{
					$requestend = helper_localized_datetime_to_iso($requestend);
				}
				$realname = booker_get_realname($userid);
				$status = 0;
				
				if($isedit && !empty($requestid))
				{
					// update in database
					qa_db_query_sub('UPDATE ^booking_requests SET userid=#, title=$, end=#, description=$, location=$ 
													WHERE requestid = #
													', 
													$userid, $requesttitle, $requestend, $description, $location, $requestid
													);
				}
				else
				{
					// save into database
					qa_db_query_sub('INSERT INTO ^booking_requests (created, userid, title, end, description, location, status) 
													VALUES (NOW(), #, $, #, $, $, #)
													', 
													$userid, $requesttitle, $requestend, $description, $location, $status 
													);
					$requestid = qa_db_last_insert_id();
				}
				
				// inform admin by email if new request is created
				if(!$isedit)
				{
					// inform admin
					$emailbody = '
					<p>
						<a href="'.q2apro_site_url().'adminrequests#req'.$requestid.'" class="defaultbutton">'.qa_lang('booker_lang/mail_btncheck_request').'</a>
					</p>
					<p>
						'.qa_lang('booker_lang/new_request').'
					</p>
					<table>
						<tr>
							<td>
								'.qa_lang('booker_lang/name').':
							</td>
							<td>
								<a href="'.q2apro_site_url().'userprofile?userid='.$userid.'">'.$realname.'</a> <span style="font-size:12px;color:#AAA;">(userid '.$userid.')</span>
							</td>
						</tr>
						<tr>
							<td>
								'.qa_lang('booker_lang/requesttitle').':
							</td>
							<td>
								 '.$requesttitle.'
							</td>
						</tr>
						<tr>
							<td>
								'.qa_lang('booker_lang/location').':
							</td>
							<td>
								'.$location.'
							</td>
						</tr>
						<tr>
							<td>
								'.qa_lang('booker_lang/requestend').':
							</td>
							<td>
								'.$requestend.'
							</td>
						</tr>
						<tr>
							<td>
								'.qa_lang('booker_lang/description').':
							</td>
							<td>
								<span style="color:#55A;">'.$description.'</span>
							</td>
						</tr>
					</table>
					';
					$emailbody .= cssemailstyles();
					
					$bcclist = explode(';', qa_opt('booker_mailcopies'));
					q2apro_send_mail(array(
								'fromemail' => q2apro_get_sendermail(),
								'fromname'  => qa_opt('booker_mailsendername'),
								'senderid'	=> $userid,
								'touserid'  => 1,
								'toname'    => qa_opt('booker_mailsendername'),
								'bcclist'   => $bcclist,
								'subject'   => qa_lang('booker_lang/newcon_request').': '.$requesttitle,
								'body'      => $emailbody,
								'html'      => true
					));
				} // end (!$isedit)
				
				// LOG
				$eventname = 'request_created';
				$statusmsg = 'success';
				if($isedit)
				{
					$statusmsg = 'editsuccess';
					$eventname = 'request_edited';					
				}
				$eventid = null;
				$params = array(
					'userid' => $userid,
					'realname' => $realname,
					'requesttitle' => $requesttitle,
					'requestend' => $requestend,
					'location' => $location,
					'description' => trim(strip_tags($description)) 
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				$arrayBack = array(
					'status' => $statusmsg,
					'message' => 'request saved and placed.',
					'requestid' => $requestid
				);
				echo json_encode($arrayBack);
				exit();
			} // END AJAX RETURN (requestdata)
			
			// check if user has specified his real name, otherwise do not allow
			$realname = booker_get_realname($userid);
			if(empty(trim($realname)))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker requestcreate');
				$qa_content['title'] = qa_lang('booker_lang/request_pagetitle');
				$qa_content['error'] = strtr( qa_lang('booker_lang/specify_realname_request'), array( 
								'^1' => '<a target="_blank" href="'.qa_path('userprofile').'">',
								'^2' => '</a>'
								));
				return $qa_content;
			}
			
			$requestid = qa_get('requestid');
			$editmode = false;
			
			$requesttitle = '';
			$requestlocation = '';
			$requestdescription = '';
			$description = '';
			
			// $requestend = date(qa_lang('booker_lang/date_format_php').' H:i', strtotime('+1 week'));
			$datetime = new DateTime('now', new DateTimeZone( qa_opt('booker_timezone') ));
			$datetime->modify('+7 days');
			$requestend = $datetime->format(qa_lang('booker_lang/datetime_format_php_fullhour'));
			
			// if requestid is specified it is an existing request to be edited
			if(!empty($requestid))
			{
				$editmode = true;
				
				// get request data
				$requestdata = qa_db_read_one_assoc(
								qa_db_query_sub('SELECT created, userid, title, price, end, description, location, status FROM `^booking_requests` 
												WHERE requestid = # 
												', 
												$requestid));
				$requesttitle = $requestdata['title'];
				$requestlocation = $requestdata['location'];
				$requestdescription = $requestdata['description'];
				if(isset($requestdata['end']))
				{
					$requestend = helper_get_readable_date_from_time($requestdata['end'], true, false);
				}
			}

			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker requestcreate');
			$qa_content['title'] = qa_lang('booker_lang/request_pagetitle');
			if(!empty($requestid))
			{
				$qa_content['title'] = qa_lang('booker_lang/request_edit');
			}
			
			// init
			$qa_content['custom'] = '';
			
			// output 
			$qa_content['custom'] .= '
							<div class="profiledit">
									
								<p>
									'.qa_lang('booker_lang/request_hint_1').'
								</p>
								
								<p>
									'.
									strtr( qa_lang('booker_lang/request_hint_2'), array( 
									'^1' => '<a href="'.qa_path('requestlist').'" target="_blank">',
									'^2' => '</a>'
									)).
									'
								</p>
								
								<div class="contractorfull-wrap">
								
									<div class="requestdata-wrap">
										<div class="profiltable">
											
											<div class="requesttitle_wrap">
												<p>
													'.qa_lang('booker_lang/requesttitle').':
												</p>
												<p>
													<input type="text" name="requesttitle" id="requesttitle" value="'.$requesttitle.'" placeholder="'.qa_lang('booker_lang/requesttitle_placeholder').'" />
												</p>
											</div>
											
											<div class="requestlocation_wrap">
												<p>
													'.qa_lang('booker_lang/location').':
												</p>
												<p>
													<input type="text" name="requestlocation" id="requestlocation" value="'.$requestlocation.'" placeholder="'.qa_lang('booker_lang/optional').'" />
												</p>
											</div>
											
											<div class="requestend_wrap">
												<p>
													'.qa_lang('booker_lang/requestend').':
												</p>
												<p>
													<input type="text" name="requestend" id="requestend" value="'.$requestend.'" placeholder="'.qa_lang('booker_lang/datetime_format').'" />
													<span class="inputhint">'.qa_lang('booker_lang/requestend_hint').'</span>
													<br />
													<span class="inputhint"></span>
												</p>
											</div>
											
										</div>
											
										<div class="contractordescription">
											<p style="margin:0 0 5px 0;font-weight:bold;">
												'.qa_lang('booker_lang/description').': 
											</p>
											<p class="descriptionhint">
												'.qa_lang('booker_lang/yourrequest_hint').' 
											</p>
											
											<textarea name="description" id="description">'.$requestdescription.'</textarea>
											
											<div class="html5image">
												<p>'.qa_lang('booker_lang/upload_img').': 
													<input id="scupload_image_content" name="imgfile" type="file" style="border:0;" /> 
												</p>
												<input id="scupload_imgbtn" type="button" value="Upload" style="display:none;" />
											</div>
											<progress style="display:none;"></progress>
											
											<p id="sceditor_maxlength_warning_content" style="display:none;color:#E33;">
												'.qa_lang('booker_lang/textlength_warn').'
											</p>
										</div>
										
										<p style="margin:30px 0 0 0;">
											'.qa_lang('booker_lang/request_hint_3').'
										</p>
										
										<div class="tfoot">
											<p></p>
											<p style="float:right;text-align:right;">
												<button type="submit" class="defaultbutton senddatabtn">'
													.
													(empty($requestid) ? qa_lang('booker_lang/request_create') : qa_lang('booker_lang/request_savebtn'))
													.
												'</button>
												<br />
												<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
											</p>
										</div>
										
									</div> <!-- requestdata-wrap -->
								</div> <!-- contractorfull-wrap -->

							</div> <!-- profiledit -->
							';
			
			$qa_content['custom'] .= '
				<a id="backtoprofile" href="'.qa_path('clientrequests').'" class="defaultbutton">
					'.qa_lang('booker_lang/back_requests').'
				</a>
				<a id="anotherrequest" href="'.qa_path('requestcreate').'" class="defaultbutton">
					'.qa_lang('booker_lang/another_request').'
				</a>
				<a id="fbsharelink" class="defaultbutton" data-shareurl="">
					<i class="fa fa-facebook-square fa-lg"></i> 
					<span>'.qa_lang('booker_lang/sharefacebook').'</span>
				</a>
				<div style="clear:both;"></div>
			';

			// jquery
			$qa_content['custom'] .= "
	<script>

	var warn_on_leave = false; // global, see sceditor code

	$(document).ready(function()
	{
		// $('#requestend').inputmask('d/m/y', { 'placeholder': '*' });
		$('#requestend').inputmask('".qa_lang('booker_lang/datetime_format_js')."',
									{
										insertMode: false,
									}
								  );
		
		$('.senddatabtn').click( function(e) 
		{
			e.preventDefault();
			var clickedbtn = $(this);
			
			// copy data to textarea
			$('#description').val( $('#description').data('sceditor').val() );
			warn_on_leave = false;

			// check and validate fields
			if($('#requesttitle').val()=='')
			{
				alert('".qa_lang('booker_lang/specify_requesttitle')."');
				$('#requesttitle').focus();
				return;
			}
			
			var edcontent = $('textarea[name=\"description\"]').sceditor('instance').val();
			// strip tags and trim
			edcontent = edcontent.replace( /<.*?>/g, '' ).trim();
			// min of 20 chars
			if(edcontent.length<20)
			{
				alert('".qa_lang('booker_lang/specify_description')."');
				$('.descriptionhint').focus();
				$('.qa-waiting').hide();
				return;
			}
			
			// check specified date
			if($('#requesttitle').val().trim()=='')
			{
				alert('".qa_lang('booker_lang/specify_date')."');
				$('#requesttitle').focus();
				$('.qa-waiting').hide();
				return;
			}
			
			// check date format
			var datetimeFormat = '".qa_lang('booker_lang/datetime_format_js')."';
			var givendate = $('#requestend').val().trim();
			var isvalid = Inputmask.isValid(givendate, { alias: datetimeFormat });
			if(!isvalid)
			{
				alert('".qa_lang('booker_lang/specify_date')." ".qa_lang('booker_lang/format')." ".qa_lang('booker_lang/datetime_format')."');
				$('#requestend').focus();
				$('.qa-waiting').hide();
				return;
			}

			// ready to send
			var gdata = {
				requesttitle: $('#requesttitle').val().trim(),
				requestend: $('#requestend').val().trim(),
				requestlocation: $('#requestlocation').val().trim(),
				description: $('#description').val().trim(),
				userid: ".$userid.",
				isedit: ".($editmode?'true':'false').",
				requestid: ".($editmode?$requestid:'null').",
			};
			console.log(gdata);
			
			// loading indicator
			$('.qa-waiting').show();
			// prevent double clicks/submits 
			clickedbtn.prop('disabled', true);
			
			var senddata = JSON.stringify(gdata);
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { requestdata: senddata },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					console.log('server returned: '+data+' | updated: '+data['status']);
					console.log(data);
					console.log('status: '+data['status']);
					
					if(data['status']=='error')
					{
						$('<p class=\"qa-error\">Fehler: '+data['message']+'</p>').insertAfter('.defaultbutton');
						$('.qa-error').fadeOut(4000, function() { 
							$(this).remove();
						});
					}
					if(data['status']=='success')
					{
						// console.log(data['requestid']);
						$('#fbsharelink').data('shareurl', '".q2apro_site_url()."bid?requestid='+data['requestid']);
						$('#backtoprofile, #anotherrequest, #fbsharelink').show();
						$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/request_gotcreated')."</p>').insertAfter('.profiledit');
						$('.profiledit').fadeOut(100, function() { 
							$(this).remove();
							window.scrollTo(0, 0);
						});
					}
					if(data['status']=='editsuccess')
					{
						window.scrollTo(0, 0);
						// $('#backtoprofile, #anotherrequest').show();
						$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/request_edited')." <br /> <br /> <a class=\"defaultbutton btnsmaller\" href=\'".qa_path('clientrequests')."\'>".qa_lang('booker_lang/back_requests')."</a></p>').insertBefore('.profiledit');
						window.scrollTo(0, 0);
					}
					$('.qa-waiting').hide();
					clickedbtn.prop('disabled', false);
				},
				error: function(xhr, status, error)
				{
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
					$('.qa-waiting').hide();
					clickedbtn.prop('disabled', false);
				}
			}); // end ajax
		}); // end senddatabtn
		
	}); // end jquery ready

</script>
			";
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.btnsmaller {
					padding:5px 10px;
					color:#FFF !important;
				}
				#requestend {
					width:80px;
				}
				#requesttitle {
					width:350px;
				}
				#requestend {
					width:120px;
				}
				.smsg {
					display:block;
					color:#00F;
					padding: 10px 0 0 0 !important;
				}
				.smsg-red {
					color:#F00;
					margin-top:4px;
				}
				.qa-waiting {
					display:none;
				}
				#fbsharelink {
					display:block;
					text-decoration:none !important;
				}
				.fa-facebook-square {
					color:#FFF;
					cursor:pointer;
				}
				
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:100%;
					min-height:640px;
				}
				.qa-main input {
					padding:5px;
					border:1px solid #DDD;
					background:#FFF;
				}
				.qa-main p {
					line-height:150%;
				}
				.profiledit {
					display:inline-block;
					margin-left:2px;
				}
				.profiledit>p {
					margin:20px 0 20px 0;
					max-width:500px;
				}
				.profiledit div {
					max-width:502px;
					line-height:150%;
					margin-bottom:13px;
				}
				.profiltable {
					display:table;
				}
				.profiltable div {
					display:table-row;
				}
				.profiltable p {
					padding:7px 15px 7px 0;
					display:table-cell;
				}
				.profileimage-wrap {
					display: inline-block;
					float: right;
				}
				.profileimage a
				{
					vertical-align:top;
				}
				.profileimage {
					display: inline-block;
					width: auto;
					vertical-align: top;
					padding: 20px 20px 10px 20px;
					margin: 0 0 10px 0;
					border: 1px solid #DDE;
					background: #F5F5F5;
					text-align: left;
				}

				.profiltable div input[type=text] {
					width:260px;
				}
				.inputhint {
					display:inline-block;
					font-size:11px;
					margin:3px 0 0 6px;
					color:#888;
				}
				.colorme {
					color:#359;
				}
				.descriptionhint {
					display:inline-block;
					font-size:11px;
					color:#359;
					margin:0 0 10px 0;
				}
				
				.senddatabtn {
					margin:20px 0 0 0;
				}
				
				.qa-main h3 {
					font-size:22px;
					font-weight:normal;
				}

				.tfoot {
					background:transparent;
					border:1px solid transparent !important;
					text-align: right;
				}
				
				#description {
					width:500px;
					height:240px;
				}
				
				#backtoprofile, #anotherrequest, #fbsharelink {
					display:none;
				}

				/* smartphones */
				@media only screen and (max-width:480px) 
				{
					.qa-main {
						width:95%;
					}
					.profileimage-wrap {
						float: none;
					}
					.q2apro_hs_avatar {
						margin:0;
					}
					.q2apro_hs_avatar img {
						max-width:100px;
					}
					.profiledit {
						margin-top:20px;
					}
					.profiledit>p {
						margin:0 0 10px 0;
					}
					.profileimage {
						float:none;
						margin:20px 0 0 0;
						padding:10px;
					}
					#description {
						width:100%;
						height:auto;
					}
					.profiltable div input[type=text] {
						width:200px;
					}
					#requesttitle {
						width:200px;
					}
				}

			</style>';
			
			// SCEDITOR implementation for textarea
			$sceditorimp = '';
			
			$sceditorimp .= '
				<script src="'.$this->urltoroot.'/sceditor/minified/jquery.sceditor.xhtml.min.js"></script>
				<link rel="stylesheet" type="text/css" href="'.$this->urltoroot.'/sceditor/minified/themes/square.min.css" >
				
				<style type="text/css">
				.sce_char {
					display:inline-block;
					width:30px;
					height:30px;
					background:#FFF;
					margin:2px;
					text-align:center;
					font-size:17px;
					cursor:default;
					vertical-align: middle;
					border-right:1px solid #CCC;
					border-bottom:1px solid #CCC;
				}
				.sce_char:hover {
					background:#FFC;
				}
				.sce_char span {
					display:block;
					padding-top:5px;
				}
				/* changed to bigger color fields */
				.sceditor-color-option {
					height: 15px;
					width: 15px;
				}
				/* hide link description field */
				#des, label[for="des"] {
					display:none !important;
				}				
				#html5image, #scupload_image_content {
					font-size:14px;
				}
				
				.sceditor-container {
					margin-bottom:0 !important;
				}
				#html5image, #scupload_image_content
				{
					font-size:13px;
				}
				/* extra for preventing table-cell paddings with editor */
				.sceditor-container, div.sceditor-toolbar {
					padding:0 !important;
					display:block;
				}
				
				/* smartphones */
				@media only screen and (max-width:480px) {
					.sceditor-container {
						width:100% !important;
					}
					.sceditor-container iframe {
						width:95% !important;
					}
				}
				
				</style>
			';
			
			$sceditorimp .= "
			<script>
				$(document).ready(function()
				{
					
					// in case plugin could not be loaded, wrong path?
					if(typeof $.fn.sceditor == 'undefined') {
						return;
					}
					
					$('#description').sceditor({
						plugins: 'xhtml',
						style: '".$this->urltoroot."/sceditor/minified/jquery.sceditor.default.min.css',
						locale: 'de',
						toolbar: 'bold,italic,underline|color|link|youtube|source',
						fonts: 'Arial,Arial Black,Comic Sans MS,Courier New,Georgia,Impact,Sans-serif,Serif,Times New Roman,Trebuchet MS,Verdana',
						colors: '#000|#F00|#11C11D|#00F|#B700B7|#FF8C00|#008080|#CC0|#808080|#D3D3D3|#FFF',
						resizeEnabled: true,
						autoExpand: false,
						width: 500,
						height: 240,
						emoticonsEnabled: false,
						rtl: false,
						autoUpdate: false,
					});
					
					// detect key down for warn_on_leave feature
					warn_on_leave = false; // defined above
					var warningset = false;
					$('#description').sceditor('instance').keyDown(function(e) {
						if(!warningset) {
							warn_on_leave = true;
							warningset = true;
						}
					});

					// Shortcuts
					$('#description').sceditor('instance').addShortcut('ctrl+l', 'link');
					$('#description').sceditor('instance').addShortcut('ctrl+k', 'link');
					
					// save this editor instance so the iframe can access it
					window.sceditorInstance_content = $('#description').sceditor('instance');
					
					$('#scupload_image_content').change( function() {
						// clear file dialog input because Chrome/Safari do not upload same filename twice
						uploadimgfile();
						$('.html5image input[type=\"file\"]').val(null);
					});
					
					// upload after user has chosen the image
					function uploadimgfile() 
					{
						console.log('submitting file via html5 ajax');
						
						// check for maximal image size
						var maximgsize = 4718592;
						var imgsize = $('#scupload_image_content')[0].files[0].size;
						// console.log(maximgsize + ' | ' + imgsize);
						if(imgsize > maximgsize) { 
							var img_size = (Math.round((imgsize/1024/1024) * 100) / 100);
							var maximg_size = (Math.round((maximgsize / 1024 / 1024) * 100) / 100);
							alert('".qa_lang('booker_lang/filesizewarning_exact')."');
							return;
						}
						
						var imgdata = new FormData();
						// append file to object
						imgdata.append('imgfile', $('#scupload_image_content')[0].files[0] );

						$.ajax({
							url: './mbupload', // server script to process data
							type: 'POST',
							xhr: function() {  
								// custom XMLHttpRequest
								var myXhr = $.ajaxSettings.xhr();
								if(myXhr.upload){ 
									// check if upload property exists
									myXhr.upload.addEventListener('progress',progressHandlingFunction, false); // For handling the progress of the upload
								}
								return myXhr;
							},
							// ajax events
							beforeSend: beforeSendHandler,
							success: completeHandler,
							error: errorHandler,
							// form data
							data: imgdata,
							// options to tell jQuery not to process data or worry about content-type
							cache: false,
							contentType: false,
							processData: false
						});
					};
					function beforeSendHandler(e) {
						$('progress').show();
					}
					function progressHandlingFunction(e){
						if(e.lengthComputable){
							// html5 progressbar
							$('progress').attr({value:e.loaded,max:e.total});
						}
					}
					function completeHandler(e) {
						parent.window.sceditorInstance_content.wysiwygEditorInsertHtml(e);
						$('progress').hide();
						// update preview
						doPreview();
					}
					function errorHandler(e) {
						parent.window.sceditorInstance_content.wysiwygEditorInsertHtml('Upload failed: '+e);
					}

					// let through when submitting
					$('input:submit').click( function() {
						warn_on_leave = false;
						$('#description').val( $('#description').data('sceditor').val() );
						return true;
					});
					// show popup when leaving
					$(window).bind('beforeunload', function() {
						if(warn_on_leave) {
							return 'Dein Text wurde nicht gespeichert. Alle Eingaben gehen verloren!';
						}
					});
					
					$('#sce_sourceb_content').click( function() { 
						$('#description').sceditor('instance').toggleSourceMode();
					});
					
					var edcontent = '';
					var edcontent_former = '';
					
					function doPreview()
					{
						delay(function()
						{
							edcontent = $('#description').sceditor('instance').val();
							// only render if value has changed
							if(edcontent == edcontent_former) {
								return;
							}
							edcontent_former = edcontent;
							
							// display warning if text is more than 8000 chars
							if(edcontent.length>8000) {
								$('#sceditor_maxlength_warning_content').show();
							}
							else {
								$('#sceditor_maxlength_warning_content').hide();
							}
						}, 1500 ); // end delay function
					} // end doPreview
					
					$('#description').sceditor('instance').keyUp(function(e) {
						doPreview();
					});
					
					// jquery keyup() delay
					var delay = ( function() {
						var timer = 0;
						return function(callback, ms) {
							clearTimeout (timer);
							timer = setTimeout(callback, ms);
						};
					})();
					
					$('#fbsharelink').click( function() 
					{
						var shareurl = $(this).data('shareurl');
						window.open('https://www.facebook.com/sharer/sharer.php?u='+escape(shareurl)+'&t='+document.title, '', 
						'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=300,width=600');
						return false;
					});
					

				}); // end ready
			</script>
			";

			$qa_content['custom'] .= $sceditorimp;
			
			return $qa_content;
			
		} // END process_request
		
	}; // END booker_page_contractor
	
	
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
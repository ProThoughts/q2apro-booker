<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_userrecommend
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
					'title' => 'booker Page Contractor Recommend', // title of page
					'request' => 'userrecommend', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='userrecommend') 
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
				qa_set_template('booker userrecommend');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker userrecommend');
				$qa_content['title'] = qa_lang('booker_lang/recommendcontractor');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			// AJAX: user is submitting data to recommend user
			$transferString = qa_post_text('contractordata'); // holds array
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				$recommenderid = qa_get_logged_in_userid();
				if(empty($recommenderid))
				{
					echo 'Userid not specified';
					exit();
				}
				
				// only user can change himself
				$usermail = empty($newdata['usermail']) ? null : $newdata['usermail'];
				$realname = empty($newdata['realname']) ? null : $newdata['realname'];
				$address = empty($newdata['address']) ? null : $newdata['address'];
				$birthdate = empty($newdata['birthdate']) ? null : $newdata['birthdate'];
				$phone = empty($newdata['phone']) ? null : $newdata['phone'];
				$skype = empty($newdata['skype']) ? null : $newdata['skype'];
				$service = empty($newdata['service']) ? null : $newdata['service'];
				$portfolio = $newdata['portfolio'];
				$serviceflags = empty($newdata['serviceflags']) ? 0 : $newdata['serviceflags'];
				
				// check email if correct
				if(empty($usermail))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => 'Please specify the correct email of the recommended user.'
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				if(empty(trim(strip_tags($portfolio))))
				{
					$portfolio = null;
				}
				// ADD recommender to portfolio (no extra field in db, that's why)
				$portfolio .= '
					<p>
						Recommended by: '.booker_get_realname($recommenderid).' (Userid: '.$recommenderid.')
					</p>
				';
				
				// check if user mail exists (then user was already created)
				$getusermail = q2apro_findusermail($usermail);
				if(!is_null($getusermail))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => 'Usermail '.$usermail.' exists already.'
					);
					echo json_encode($arrayBack);
					exit();
				}
				else
				{
					require_once QA_INCLUDE_DIR.'app/users.php';
					require_once QA_INCLUDE_DIR.'app/users-edit.php';
					
					// create handle from realname
					$inhandle = q2apro_generate_userhanlde($realname);
					
					// get next userid from qa_users, should be safe
					$nextuserid = q2apro_getnextuserid();
					
					$inpassword = 'pro'.$nextuserid;
					
					$errors = array_merge(
						qa_handle_email_filter($inhandle, $usermail),
						qa_password_validate($inpassword)
					);

					if(empty($errors)) 
					{
						// create user with userid and handle and password
						// qa_create_new_user($email, $password, $handle, $level=QA_USER_LEVEL_BASIC, $confirmed=false)
						$userid = qa_create_new_user_nonotify($usermail, $inpassword, $inhandle, QA_USER_LEVEL_BASIC, true);
						
						// variable booking price to make it more interesting
						$defaultprice = rand(15,40);
						
						booker_set_userfield($userid, 'realname', $realname);
						booker_set_userfield($userid, 'address', $address);
						booker_set_userfield($userid, 'birthdate', $birthdate);
						booker_set_userfield($userid, 'phone', $phone);
						booker_set_userfield($userid, 'skype', $skype);
						booker_set_userfield($userid, 'service', $service);
						booker_set_userfield($userid, 'portfolio', $portfolio);
						booker_set_userfield($userid, 'flags', $serviceflags);
						booker_set_userfield($userid, 'bookingprice', 50); // by default 50, need price to be listed in contractorlist

						// inform admin
						$emailbody = '
						<p>
							'.qa_lang('booker_lang/newcontractor').'
						</p>
						<p>
							UserID: '.$userid.' <br />
							'.qa_lang('booker_lang/name').': <a href="'.q2apro_site_url().'userprofile?userid='.$userid.'">'.$realname.'</a> <br />
							'.qa_lang('booker_lang/birthdate').': '.$birthdate.' <br />
							'.qa_lang('booker_lang/address').': '.$address.' <br />
							'.qa_lang('booker_lang/telephone').': '.$phone.' <br />
							'.qa_lang('booker_lang/skype').': '.$skype.' <br />
							'.qa_lang('booker_lang/execution').': '.$serviceflags.' <br />
							<b>'.qa_lang('booker_lang/service').': '.$service.'</b> <br />
							'.qa_lang('booker_lang/portfolio').': <br />
								<blockquote>'.$portfolio.'</blockquote>
						</p>
						';
						q2apro_send_mail(array(
									'fromemail' => q2apro_get_sendermail(),
									'fromname'  => qa_opt('booker_mailsendername'),
									'senderid'	=> $recommenderid,
									'touserid'  => 1,
									'toname'    => qa_opt('booker_mailsendername'),
									'subject'   => qa_lang('booker_lang/newcon_suggested').': '.$realname,
									'body'      => $emailbody,
									'html'      => true
						));
						
						// LOG
						$eventname = 'contractor_recommended';
						$eventid = null;
						$params = array(
							'recommender' => $recommenderid,
							'realname' => $realname,
							'usermail' => $usermail,
							'birthdate' => $birthdate,
							'address' => $address,
							'phone' => $phone,
							'skype' => $skype,
							'service' => $service,
							'portfolio' => trim(strip_tags($portfolio)),
							'serviceflags' => $serviceflags
						);
						booker_log_event($userid, $eventid, $eventname, $params);
						
						$arrayBack = array(
							'status' => 'success',
							'message' => 'User has been recommended to us, thank you.'
						);
						echo json_encode($arrayBack);
						exit();
					}
					else
					{
						$arrayBack = array(
							'status' => 'error',
							'message' => 'Error with specified email.'
						);
						echo json_encode($arrayBack);
						exit();
					}
					exit(); 
				}
				exit();
			} // END AJAX RETURN (contractordata)

			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker userrecommend');
			$qa_content['title'] = qa_lang('booker_lang/recommendcontractor');

			// init
			$qa_content['custom'] = '';
			
			$bookinglink = '';
			$turnspublic = '';
			
			$iscontractor = booker_iscontracted($userid);
			
			// user forum data
			$aselecteds = 0;
			$points = 0;
			$upvoteds= 0;
			
			
			// output 
			$qa_content['custom'] .= '
							<div class="profileimage-wrap">
							
								<div class="profileimage">
									<div class="q2apro_hs_avatar">
										<img src="./?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size=250" />
										<br />
									</div>
								</div>

							</div>
								
							<div class="profiledit">
									
								<p>
									'.qa_lang('booker_lang/recommend_hint').'
								</p>
								
								<div class="profiltable">
									<div class="contractorname">
										<p>
											'.qa_lang('booker_lang/firstlastname').':
										</p>
										<p>
											<input type="text" name="realname" id="realname" value="" placeholder="'.qa_lang('booker_lang/firstlastname').'" />
										</p>
									</div>
									<div class="contractormail">
										<p>
											'.qa_lang('booker_lang/email').':
										</p>
										<p>
											<input type="text" name="usermail" id="usermail" value="" placeholder="'.qa_lang('booker_lang/placeholder_email_con').'" />
										</p>
									</div>
									<div class="contractoraddress">
										<p>'.qa_lang('booker_lang/address').':</p>
										<p><input type="text" name="address" id="address" value="" placeholder="'.qa_lang('booker_lang/placeholder_addressexample').'" /></p>
									</div>
									<div class="contractorbirthdate">
										<p>'.qa_lang('booker_lang/birthdate').':</p>
										<p><input type="text" name="birthdate" id="birthdate" value="" placeholder="'.qa_lang('booker_lang/placeholder_dateexample').'" /></p>
									</div>
									<div class="contractorphone">
										<p>'.qa_lang('booker_lang/telephone').':</p>
										<p><input type="text" name="phone" id="phone" value="" placeholder="'.qa_lang('booker_lang/placeholder_phone').'" /></p>
									</div>
									<div class="contractorskype">
										<p>'.qa_lang('booker_lang/skype').':</p>
										<p><input type="text" name="skype" id="skype" value="" placeholder="'.qa_lang('booker_lang/optional').'" /></p>
									</div>
									
								</div>
								
								<div class="contractorfull-wrap">
									<div class="inbetween">
										'.qa_lang('booker_lang/contractor_data').'
									</div>
									
									<div class="contractordata-wrap">
										'.$turnspublic.'
										<div class="profiltable">
											
											<div class="contractorservice">
												<p>
													'.qa_lang('booker_lang/service').':
												</p>
												<p>
													<input type="text" name="service" id="service" value="" placeholder="'.qa_lang('booker_lang/placeholder_service').'" />
												</p>
											</div>
										</div>
											
										<div class="contractorportfolio">
											<p style="margin:0 0 5px 0;font-weight:bold;">
												'.qa_lang('booker_lang/yourservice_desc').': 
											</p>
											<p class="portfoliohint">
												'.qa_lang('booker_lang/yourservice_hint').' 
											</p>
											
											<textarea name="portfolio" id="portfolio"></textarea>
											
											<p id="sceditor_maxlength_warning_content" style="display:none;color:#E33;">
												'.qa_lang('booker_lang/textlength_warn').'
											</p>
										</div>
										
										<div class="profiltable">
											<div class="contractorservice">
												<p>
													'.qa_lang('booker_lang/execution').':
												</p>
												<p class="serviceflagwrap">
													<label>
														<input class="serviceflags" type="checkbox" value="'.MB_SERVICELOCAL .'" />
														'.qa_lang('booker_lang/servicelocal').'
													</label>
													<label>
														<input class="serviceflags" type="checkbox" value="'.MB_SERVICEONLINE .'" />
														'.qa_lang('booker_lang/serviceonline').'
													</label>
													<label>
														<input class="serviceflags" id="atcustomer" type="checkbox" value="'.MB_SERVICEATCUSTOMER .'" />
														'.qa_lang('booker_lang/serviceatcustomer').'
													</label>
												</p>
											</div>
										</div>
										
										<div class="tfoot">
											<p></p>
											<p style="float:right;text-align:right;">
												<button type="submit" class="defaultbutton senddatabtn">'.qa_lang('booker_lang/btn_submit').'</button>
												<br />
												<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
											</p>
										</div>
										
									</div> <!-- contractordata-wrap -->
								</div> <!-- contractorfull-wrap -->

							</div> <!-- profiledit -->
							';
			
			$qa_content['custom'] .= '<a id="backtoservices" href="'.qa_path('contractorlist').'" class="defaultbutton">'.qa_lang('booker_lang/back_services').'</a>';
			
			$qa_content['custom'] .= '<div style="clear:both;"></div>';

			// jquery
			$qa_content['custom'] .= "
	<script>

	var warn_on_leave = false; // global, see sceditor code

	$(document).ready(function()
	{
		$('.senddatabtn').click( function(e) 
		{
			e.preventDefault();
			
			// copy data to textarea
			$('#portfolio').val( $('#portfolio').data('sceditor').val() );
			warn_on_leave = false;

			var flags = 0;
			$('.serviceflags:checked').each(function()
			{
				flags = parseInt(flags) | parseInt($(this).val());
			});
			// console.log('Flags: '+flags);
			
			// check and validate fields
			if($('#usermail').val()=='') {
				alert('".qa_lang('booker_lang/specify_email_con')."');
				$('#usermail').focus();
				return;
			}
			if($('#realname').val()=='') {
				alert('".qa_lang('booker_lang/specify_fullname_con')."');
				$('#realname').focus();
				return;
			}
			if($('#address').val()=='') {
				alert('".qa_lang('booker_lang/specify_address_con')."');
				$('#address').focus();
				return;
			}
			if($('#service').val()=='') {
				alert('".qa_lang('booker_lang/specify_service_con')."');
				$('#service').focus();
				return;
			}
			
			// ready to send
			var gdata = {
				realname: $('#realname').val(),
				usermail: $('#usermail').val(),
				birthdate: $('#birthdate').val(),
				address: $('#address').val(),
				phone: $('#phone').val(),
				skype: $('#skype').val(),
				service: $('#service').val(),
				portfolio: $('#portfolio').val(),
				serviceflags: flags,
			};
			// console.log(gdata);
			
			// loading indicator
			$('.qa-waiting').show();
			
			var senddata = JSON.stringify(gdata);
			$.ajax({
				type: 'POST',
				url: '".qa_path('userrecommend')."',
				data: { contractordata:senddata },
				dataType: 'json',
				cache: false,
				success: function(data) {
					console.log('server returned: '+data+' | updated: '+data['status']);
					console.log(data);
					console.log('status: '+data['status']);
					
					if(data['status']=='error')
					{
						$('<p class=\"qa-error\">".qa_lang('booker_lang/error').": '+data['message']+'</p>').insertAfter('.defaultbutton');
						$('.qa-error').fadeOut(4000, function() { 
							$(this).remove();
						});
					}
					if(data['status']=='success')
					{
						$('#backtoservices').show();
						$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/userrecommended')."</p>').insertAfter('.profiledit');
						$('.profiledit').fadeOut(100, function() { 
							$(this).remove();
							window.scrollTo(0, 0);
						});
					}
					$('.qa-waiting').hide();
				},
				error: function(xhr, status, error) {
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
					$('.qa-waiting').hide();
				}
			}); // end ajax
		}); // end senddatabtn
		
	}); // end jquery ready

</script>
			";
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.changeavatar-wrap {
					display:block;
					font-size:12px;
				}
				#avatarupload {
					max-width:200px;
				}
				.noavatarset {
					color:#F00;
				}
				.avatarset {
					color:#999;
					cursor:pointer;
				}
				
				.q2apro_hs_avatar {
					display:block;
					margin-bottom:10px;
					text-align:center;
				}
				.q2apro_hs_avatar img {
					max-width:200px;
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
				#backtoservices {
					display:none;
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
					margin:20px 0 30px 0;
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
				.contractorprice {
					font-size:12px;
				}

				.profiltable div input[type=text] {
					width:260px;
				}
				.fullnamehint {
					display:inline-block;
					font-size:11px;
					color:#FFF;
					margin:3px 0 0 6px;
				}
				.colorme {
					color:#359;
				}
				.portfoliohint {
					display:inline-block;
					font-size:11px;
					color:#359;
					margin:0 0 10px 0;
				}
				
				
				.senddatabtn {
					margin:20px 0 0 0;
				}
				.contractortooltip {
					border-bottom:1px solid #CCC;
					display:inline;
					cursor:help;
				}
				
				.qa-main h3 {
					font-size:22px;
					font-weight:normal;
				}

				.contractorbutton {
					display:inline-block;
					padding:7px 14px;
					margin-right: 20px;
					font-size:14px;
					color:#FFF;
					background:#38F;
					border:1px solid #EEE;
					border-radius:0px;
				}
				.inbetween {
					max-width:500px;
					font-size:15px;
					background:#2E3C77; /*#F0F0F0*/
					color:#FFF;
					padding:5px 10px;
					margin:40px 0 20px 0 !important;
				}
				.tfoot {
					background:transparent;
					border:1px solid transparent !important;
					text-align: right;
				}
				
				#portfolio {
					width:500px;
					height:240px;
				}
				.conavailable {
					font-weight:normal;
				}
				.serviceflagwrap label {
					display:inline-block;
					width:90%;
					margin:0 0 10px 0;
				}
				#bookingprice {
					text-align:right;
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
						margin:0 0 30px 0;
					}
					#service {
						width:95%;
					}
					.profileimage {
						float:none;
						margin:20px 0 0 0;
						padding:10px;
					}
					#portfolio {
						width:100%;
						height:auto;
					}
					.profiltable div input[type=text] {
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
					
					$('#portfolio').sceditor({
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
					$('#portfolio').sceditor('instance').keyDown(function(e) {
						if(!warningset) {
							warn_on_leave = true;
							warningset = true;
						}
					});

					// Shortcuts
					$('#portfolio').sceditor('instance').addShortcut('ctrl+l', 'link');
					$('#portfolio').sceditor('instance').addShortcut('ctrl+k', 'link');
					
					// save this editor instance so the iframe can access it
					window.sceditorInstance_content = $('#portfolio').sceditor('instance');
					
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
						$('#portfolio').val( $('#portfolio').data('sceditor').val() );
						return true;
					});
					// show popup when leaving
					$(window).bind('beforeunload', function() {
						if(warn_on_leave) {
							return 'Dein Text wurde nicht gespeichert. Alle Eingaben gehen verloren!';
						}
					});
					
					$('#sce_sourceb_content').click( function() { 
						$('#portfolio').sceditor('instance').toggleSourceMode();
					});
					
					var edcontent = '';
					var edcontent_former = '';
					
					function doPreview()
					{
						delay(function()
						{
							edcontent = $('#portfolio').sceditor('instance').val();
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
					
					$('#portfolio').sceditor('instance').keyUp(function(e) {
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
					
				}); // end ready
			</script>
			";

			$qa_content['custom'] .= $sceditorimp;
			
			return $qa_content;
			
		} // END process_request
		
	}; // END booker_page_contractor
	
	
	
	function q2apro_findusermail($usermail)
	{ 
		return qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT email FROM ^users WHERE email=#',
								$usermail
							), 
						true);
	}

	function q2apro_findusername($inhandle)
	{ 
		return qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT handle FROM ^users WHERE handle=#',
								$inhandle
							), 
						true); 
	}

	function q2apro_getnextuserid()
	{
		$tabledata = qa_db_read_one_assoc(
						qa_db_query_sub(
							'SHOW TABLE STATUS LIKE "^users"' 
						));
		return $tabledata['Auto_increment'];
	}

	function q2apro_generate_userhanlde($name)
	{ 
		// remove all whitespaces
		$inhandle = preg_replace('/\s+/', '', $name);
		// transliterate (in case we want an email system later on where the username is the email name or a subdomain for each)
		$inhandle = transliterateString($inhandle);
		// remove special characters
		$inhandle = preg_replace("/[^a-zA-Z0-9]+/", "", $inhandle);
		// maximal length of 18 chars, see db qa_users
		$inhandle = substr($inhandle, 0, 18);
		// all small letters
		$inhandle = strtolower($inhandle);
		// check if username does exist already
		$getusername = q2apro_findusername($inhandle);
		// if exists then change last letter to number and check again
		if(!is_null($getusername))
		{
			$replacenr = 2;
			$isunique = false;
			while(!$isunique)
			{
				// replace last char by number
				$inhandle = substr($inhandle, 0, 17).$replacenr;
				// check again if does exist
				$getusername = q2apro_findusername($inhandle);
				$isunique = is_null($getusername);
				$replacenr++;
			}
		}		
		return $inhandle;
	}

	// http://stackoverflow.com/questions/6837148/change-foreign-characters-to-normal-equivalent
	function transliterateString($txt) 
	{
		$transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja');
		return str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt);
	}


	// this is a copy of qa_create_new_user(), just that we disabled the email notification
	function qa_create_new_user_nonotify($email, $password, $handle, $level=QA_USER_LEVEL_BASIC, $confirmed=false)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR.'db/users.php';
		require_once QA_INCLUDE_DIR.'db/points.php';
		require_once QA_INCLUDE_DIR.'app/options.php';
		require_once QA_INCLUDE_DIR.'app/emails.php';
		require_once QA_INCLUDE_DIR.'app/cookies.php';

		$userid=qa_db_user_create($email, $password, $handle, $level, qa_remote_ip_address());
		qa_db_points_update_ifuser($userid, null);
		qa_db_uapprovecount_update();

		if ($confirmed)
			qa_db_user_set_flag($userid, QA_USER_FLAGS_EMAIL_CONFIRMED, true);

		if (qa_opt('show_notice_welcome'))
			qa_db_user_set_flag($userid, QA_USER_FLAGS_WELCOME_NOTICE, true);

		$custom=qa_opt('show_custom_welcome') ? trim(qa_opt('custom_welcome')) : '';

		if (qa_opt('confirm_user_emails') && ($level<QA_USER_LEVEL_EXPERT) && !$confirmed) {
			$confirm=strtr(qa_lang('emails/welcome_confirm'), array(
				'^url' => qa_get_new_confirm_url($userid, $handle)
			));

			if (qa_opt('confirm_user_required'))
				qa_db_user_set_flag($userid, QA_USER_FLAGS_MUST_CONFIRM, true);

		} else
			$confirm='';

		if (qa_opt('moderate_users') && qa_opt('approve_user_required') && ($level<QA_USER_LEVEL_EXPERT))
			qa_db_user_set_flag($userid, QA_USER_FLAGS_MUST_APPROVE, true);

		/*
		qa_send_notification($userid, $email, $handle, qa_lang('emails/welcome_subject'), qa_lang('emails/welcome_body'), array(
			'^password' => isset($password) ? qa_lang('main/hidden') : qa_lang('users/password_to_set'), // v 1.6.3: no longer email out passwords
			'^url' => qa_opt('site_url'),
			'^custom' => strlen($custom) ? ($custom."\n\n") : '',
			'^confirm' => $confirm,
		));
		*/

		qa_report_event('u_register', $userid, $handle, qa_cookie_get(), array(
			'email' => $email,
			'level' => $level,
		));

		return $userid;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/
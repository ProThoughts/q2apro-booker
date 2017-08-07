<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_offercreate
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
					'title' => 'booker Page offercreate', // title of page
					'request' => 'offercreate', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if($request=='offercreate') 
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
				qa_set_template('booker offercreate');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker offercreate');
				$qa_content['title'] = qa_lang('booker_lang/offer_pagetitle');
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
			
			// AJAX: user is submitting data of offer
			$transferString = qa_post_text('offerdata');
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				// only user can post an offer
				$offertitle = empty($newdata['offertitle']) ? null : $newdata['offertitle'];
				$offerprice = empty($newdata['offerprice']) ? null : $newdata['offerprice'];
				$offerduration = empty($newdata['offerduration']) ? null : $newdata['offerduration'];
				$offerend = empty($newdata['offerend']) ? null : $newdata['offerend'];
				$offerdetails = $newdata['offerdetails'];
				$serviceflags = empty($newdata['serviceflags']) ? 0 : $newdata['serviceflags'];
				if(!empty($newdata['userid']) && qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN)
				{
					$userid = $newdata['userid'];
				}
				$offerid = empty($newdata['offerid']) ? null : $newdata['offerid'];
				$isedit = !empty($newdata['isedit']);
				
				if(empty(trim(strip_tags($offerdetails))))
				{
					$offerdetails = null;
				}
				
				// string to time
				if(isset($offerend))
				{
					$offerend = helper_day_to_datetime($offerend);
				}
				$realname = booker_get_realname($userid);
				$status = 0;
				
				if($isedit && !empty($offerid))
				{
					// update in database
					qa_db_query_sub('UPDATE ^booking_offers SET userid=#, title=$, price=#, duration=#, end=#, description=$, flags=#, status=#
													WHERE offerid = #
													', 
													$userid, $offertitle, $offerprice, $offerduration, $offerend, $offerdetails, $serviceflags, $status,
													$offerid
													);
				}
				else
				{
					// save into database
					qa_db_query_sub('INSERT INTO ^booking_offers (created, userid, title, price, duration, end, description, flags, status) 
													VALUES (NOW(), #, $, #, #, #, $, #, #)
													', 
													$userid, $offertitle, $offerprice, $offerduration, $offerend, $offerdetails, $serviceflags, $status 
													);
					$offerid = qa_db_last_insert_id();
				}
				
				// inform admin by email if new offer is created
				if(!$isedit)
				{
					// inform admin
					$emailbody = '
					<p>
						<a href="'.q2apro_site_url().'adminoffers#off'.$offerid.'" class="defaultbutton">'.qa_lang('booker_lang/mail_btncheck_offer').'</a>
					</p>
					<p>
						'.qa_lang('booker_lang/new_offer').'
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
								'.qa_lang('booker_lang/offertitle').':
							</td>
							<td>
								 '.$offertitle.'
							</td>
						</tr>
						<tr>
							<td>
								'.qa_lang('booker_lang/offerprice').':
							</td>
							<td>
								 '.$offerprice.'
							</td>
						</tr>
						<tr>
							<td>
								'.qa_lang('booker_lang/duration').':
							</td>
							<td>
								 '.$offerduration.'
							</td>
						</tr>
						<tr>
							<td>
								'.qa_lang('booker_lang/offerend').':
							</td>
							<td>
								'.$offerend.'
							</td>
						</tr>
						<tr>
							<td>
								'.qa_lang('booker_lang/flags').':
							</td>
							<td>
								'.$serviceflags.'
							</td>
						</tr>
						<tr>
							<td>
								'.qa_lang('booker_lang/description').':
							</td>
							<td>
								<span style="color:#55A;">'.$offerdetails.'</span>
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
								'subject'   => qa_lang('booker_lang/newcon_offer').': '.$offertitle,
								'body'      => $emailbody,
								'html'      => true
					));
				} // end (!$isedit)
				
				// LOG
				$eventname = 'offer_created';
				$statusmsg = 'success';
				if($isedit)
				{
					$statusmsg = 'editsuccess';
					$eventname = 'offer_edited';					
				}
				$eventid = null;
				$params = array(
					'userid' => $userid,
					'realname' => $realname,
					'offertitle' => $offertitle,
					'offerprice' => $offerprice,
					'offerduration' => $offerduration,
					'offerend' => $offerend,
					'flags' => $serviceflags,
					'offerdetails' => trim(strip_tags($offerdetails)) 
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				$arrayBack = array(
					'status' => $statusmsg,
					'message' => 'Offer saved and placed.',
					'offerid' => $offerid
				);
				echo json_encode($arrayBack);
				exit();
			} // END AJAX RETURN (offerdata)
			
			
			$offerid = qa_get('offerid');
			$editmode = false;
			
			$offertitle = '';
			$offerprice = '';
			$offerduration = '';
			$offerdescription = '';
			$offerend = '';
			$offerdetails = '';
			$serviceflags = '';
			
			// if offerid is specified it is an existing offer to be edited
			if(!empty($offerid))
			{
				$editmode = true;
				
				// get offer data
				$offerdata = qa_db_read_one_assoc(
								qa_db_query_sub('SELECT created, userid, title, price, duration, end, description, flags, status FROM `^booking_offers` 
												WHERE offerid = # 
												', 
												$offerid));
				$offertitle = $offerdata['title'];
				$offerprice = $offerdata['price'];
				$offerduration = $offerdata['duration'];
				$offerdescription = $offerdata['description'];
				if(isset($offerdata['end']))
				{
					$offerend = helper_get_readable_date_from_time($offerdata['end'], false);					
				}
				$serviceflags = $offerdata['flags'];				
			}

			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker offercreate');
			$qa_content['title'] = qa_lang('booker_lang/offer_pagetitle');
			if(!empty($offerid))
			{
				$qa_content['title'] = qa_lang('booker_lang/offer_edit');
			}
			
			$iscontractor = booker_iscontracted($userid);
			
			if(!$iscontractor)
			{
				$qa_content['error'] = 
									strtr( qa_lang('booker_lang/needcontractor'), array( 
									'^1' => '<a href="'.qa_path('userprofile').'?show=contractor&userid='.$userid.'">',
									'^2' => '</a>'
									));
				return $qa_content;
			}
			
			// init
			$qa_content['custom'] = '';
			
			// output 
			$qa_content['custom'] .= '
							<div class="profiledit">
									
								<p>
									'.qa_lang('booker_lang/offer_hint').'
								</p>
								
								<p>
									'.
									strtr( qa_lang('booker_lang/offer_hint_2'), array( 
									'^1' => '<a href="'.qa_path('booking').'?contractorid='.$userid.'">',
									'^2' => '</a>'
									)).
									'
								</p>
								
								<div class="contractorfull-wrap">
								
									<div class="offerdata-wrap">
										<div class="profiltable">
											
											<div class="offertitle_wrap">
												<p>
													'.qa_lang('booker_lang/offertitle').':
												</p>
												<p>
													<input type="text" name="offertitle" id="offertitle" value="'.$offertitle.'" placeholder="'.qa_lang('booker_lang/offertitle_placeholder').'" />
												</p>
											</div>
											
											<div class="offerprice_wrap">
												<p>
													'.qa_lang('booker_lang/offerprice').':
												</p>
												<p>
													<input type="text" name="offerprice" id="offerprice" value="'.$offerprice.'" placeholder="'.qa_lang('booker_lang/offerprice_placeholder').'" />&nbsp; '.qa_opt('booker_currency').'
												</p>
											</div>
											
											<!--
											<div class="contractorcommission">
												<p>'.qa_lang('booker_lang/commission').':</p>
												<p>'.number_format(booker_getcommission($userid)*100,0,',','.').' %</p>
											</div>
											-->
											
											<div class="offerduration_wrap">
												<p>
													'.qa_lang('booker_lang/duration').':
												</p>
												<p>
													<input type="text" name="offerduration" id="offerduration" value="'.$offerduration.'" placeholder="'.qa_lang('booker_lang/duration_placeholder').'" /> '.qa_lang('booker_lang/hours').'
													<span class="inputhint">'.qa_lang('booker_lang/offerduration_hint').'</span>
												</p>
											</div>
											
											<div class="offerend_wrap">
												<p>
													'.qa_lang('booker_lang/offerend').':
												</p>
												<p>
													<input type="text" name="offerend" id="offerend" value="'.$offerend.'" placeholder="'.qa_lang('booker_lang/eg').' '.date(qa_lang('booker_lang/date_format_php'), strtotime('+2 weeks')).'" />
													<span class="inputhint">'.qa_lang('booker_lang/offerend_hint').'</span>
												</p>
											</div>
											
										</div>
											
										<div class="contractorofferdetails">
											<p style="margin:0 0 5px 0;font-weight:bold;">
												'.qa_lang('booker_lang/description').': 
											</p>
											<p class="offerdetailshint">
												'.qa_lang('booker_lang/youroffer_hint').' 
											</p>
											
											<textarea name="offerdetails" id="offerdetails">'.$offerdescription.'</textarea>
											
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
										
										<div class="profiltable">
											<div class="contractorservice">
												<p>
													'.qa_lang('booker_lang/execution').':
												</p>
												<p class="serviceflagwrap">
													<label>
														<input class="serviceflags" type="checkbox" value="'.MB_SERVICELOCAL.'" '.($serviceflags&MB_SERVICELOCAL?'checked':'').' />
														'.qa_lang('booker_lang/servicelocal').'
													</label>
													<label>
														<input class="serviceflags" type="checkbox" value="'.MB_SERVICEONLINE.'" '.($serviceflags&MB_SERVICEONLINE?'checked':'').' />
														'.qa_lang('booker_lang/serviceonline').'
													</label>
													<label>
														<input class="serviceflags" id="atcustomer" value="'.MB_SERVICEATCUSTOMER.'" type="checkbox" '.($serviceflags&MB_SERVICEATCUSTOMER?'checked':'').' />
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
										
									</div> <!-- offerdata-wrap -->
								</div> <!-- contractorfull-wrap -->

							</div> <!-- profiledit -->
							';
			
			$qa_content['custom'] .= '
				<a id="backtoprofile" href="'.qa_path('contractoroffers').'" class="defaultbutton">
					'.qa_lang('booker_lang/back_offers').'
				</a>
				<a id="anotheroffer" href="'.qa_path('offercreate').'" class="defaultbutton">
					'.qa_lang('booker_lang/another_offer').'
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
		$('.senddatabtn').click( function(e) 
		{
			e.preventDefault();
			var clickedbtn = $(this);
			
			// copy data to textarea
			$('#offerdetails').val( $('#offerdetails').data('sceditor').val() );
			warn_on_leave = false;

			var flags = 0;
			$('.serviceflags:checked').each(function()
			{
				flags = parseInt(flags) | parseInt($(this).val());
			});
			// console.log('Flags: '+flags);
			
			// check and validate fields
			if($('#offertitle').val()=='') {
				alert('".qa_lang('booker_lang/specify_service')."');
				$('#offertitle').focus();
				return;
			}
			var bprice = $('#offerprice').val().trim();
			bprice = parseFloat(bprice.replace(',','.').replace(' ',''))
			if(bprice==0 || isNaN(bprice))
			{
				alert('".qa_lang('booker_lang/specify_price')."');
				$('#offerprice').focus();
				$('.qa-waiting').hide();
				return;
			}
			/*
			if($('#offerduration').val()=='') {
				alert('".qa_lang('booker_lang/specify_duration')."');
				$('#realname').focus();
				return;
			}
			*/
			
			var edcontent = $('textarea[name=\"offerdetails\"]').sceditor('instance').val();
			// strip tags and trim
			edcontent = edcontent.replace( /<.*?>/g, '' ).trim();
			// min of 20 chars
			if(edcontent.length<20)
			{
				alert('".qa_lang('booker_lang/specify_offerdetails')."');
				$('.offerdetailshint').focus();
				$('.qa-waiting').hide();
				return;
			}
			
			// ready to send
			var gdata = {
				offertitle: $('#offertitle').val(),
				offerprice: $('#offerprice').val(),
				offerduration: $('#offerduration').val(),
				offerend: $('#offerend').val(),
				offerdetails: $('#offerdetails').val(),
				serviceflags: flags,
				userid: ".$userid.",
				isedit: ".($editmode?'true':'false').",
				offerid: ".($editmode?$offerid:'null').",
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
				data: { offerdata:senddata },
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
						// console.log(data['offerid']);
						$('#fbsharelink').data('shareurl', '".q2apro_site_url()."booking?offerid='+data['offerid']);
						$('#backtoprofile, #anotheroffer, #fbsharelink').show();
						$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/offer_created_thx')."</p>').insertAfter('.profiledit');
						$('.profiledit').fadeOut(100, function() { 
							$(this).remove();
							window.scrollTo(0, 0);
						});
					}
					if(data['status']=='editsuccess')
					{
						window.scrollTo(0, 0);
						// $('#backtoprofile, #anotheroffer').show();
						$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/offer_edited')."<br /> <br /> <a class=\"defaultbutton btnsmaller\" href=\'".qa_path('contractoroffers')."\'>".qa_lang('booker_lang/back_offers')."</a></p>').insertBefore('.profiledit');
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
				#offerprice, #offerend {
					width:80px;
				}
				#offerduration {
					width:55px;
				}
				#offerend {
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
				.contractorprice {
					font-size:12px;
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
				.offerdetailshint {
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
				
				#offerdetails {
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
				
				#backtoprofile, #anotheroffer, #fbsharelink {
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
					#offerdetails {
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
					
					$('#offerdetails').sceditor({
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
					$('#offerdetails').sceditor('instance').keyDown(function(e) {
						if(!warningset) {
							warn_on_leave = true;
							warningset = true;
						}
					});

					// Shortcuts
					$('#offerdetails').sceditor('instance').addShortcut('ctrl+l', 'link');
					$('#offerdetails').sceditor('instance').addShortcut('ctrl+k', 'link');
					
					// save this editor instance so the iframe can access it
					window.sceditorInstance_content = $('#offerdetails').sceditor('instance');
					
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
						$('#offerdetails').val( $('#offerdetails').data('sceditor').val() );
						return true;
					});
					// show popup when leaving
					$(window).bind('beforeunload', function() {
						if(warn_on_leave) {
							return 'Dein Text wurde nicht gespeichert. Alle Eingaben gehen verloren!';
						}
					});
					
					$('#sce_sourceb_content').click( function() { 
						$('#offerdetails').sceditor('instance').toggleSourceMode();
					});
					
					var edcontent = '';
					var edcontent_former = '';
					
					function doPreview()
					{
						delay(function()
						{
							edcontent = $('#offerdetails').sceditor('instance').val();
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
					
					$('#offerdetails').sceditor('instance').keyUp(function(e) {
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
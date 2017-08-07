<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_userprofile
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
					'title' => 'booker Page Contractor Profile', // title of page
					'request' => 'userprofile', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='userprofile')
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
				qa_set_template('booker userprofile');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			$userid = qa_get_logged_in_userid();

			// super admin can have view of others for profile if adding a userid=x to the URL
			$isadmin = qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN;
			if($isadmin)
			{
				$userid = qa_get('userid');
				if(empty($userid))
				{
					$userid = qa_get_logged_in_userid();
				}
			}

			// AJAX call: contractor is uploading an image
			if(is_array($_FILES) && count($_FILES))
			{
				// if admin take userid sent, otherwise of logged in
				$userid_in = qa_post_text('userid');
				if($isadmin && !empty($userid_in))
				{
					$userid = $userid_in;
				}

				require_once QA_INCLUDE_DIR.'db/users.php';
				require_once QA_INCLUDE_DIR.'app/users-edit.php';
				require_once QA_INCLUDE_DIR.'app/limits.php';
				require_once QA_INCLUDE_DIR.'util/image.php';

				qa_limits_increment($userid, QA_LIMIT_UPLOADS);

				$useraccount = qa_db_select_with_pending(qa_db_user_account_selectspec($userid, true));

				$toobig = qa_image_file_too_big($_FILES['imgfile']['tmp_name'], qa_opt('avatar_store_size'));

				$error = '';
				if($toobig)
				{
					$error = qa_lang_sub('main/image_too_big_x_pc', (int) ($toobig * 100));
				}
				elseif(!qa_set_user_avatar($userid, file_get_contents($_FILES['imgfile']['tmp_name']), $useraccount['avatarblobid']))
				{
					$error = qa_lang_sub('main/image_not_read', implode(', ', qa_gd_image_formats()));
				}

				if(empty($error))
				{
					// read new avatarblob
					$avatarblobid = qa_db_read_one_value(
										qa_db_query_sub('SELECT avatarblobid FROM ^users
																WHERE userid = #',
																$userid));
					$avatarurl = qa_get_blob_url($avatarblobid, true);
					$contractorname = booker_get_realname($userid);

					// LOG
					$eventid = null;
					$eventname = 'avatar_uploaded';
					$params = array(
						'blobid' => $avatarblobid,
					);
					booker_log_event($userid, $eventid, $eventname, $params);

					// back to frontend
					echo '<img style="max-width:200px;" src="'.$avatarurl.'" alt="Bild '.$contractorname.'" /> ';
				}
				else
				{
					echo $error;
				}

				exit();
			}

			// AJAX: removeavatar
			$transferString = qa_post_text('removeavatar'); // holds array
			if(isset($transferString))
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				// is admin editing another profile and posting the data then take userid from newdata
				// otherwise take userid from logged-in user
				if($isadmin && isset($newdata['userid']))
				{
					$userid = $newdata['userid'];
				}

				// for log
				$avatarblobid = qa_db_read_one_value(
									qa_db_query_sub('SELECT avatarblobid FROM ^users
														WHERE userid = #',
														$userid), true);
				// remove avatar
				qa_db_query_sub('UPDATE ^users SET avatarblobid = NULL
												WHERE userid = #',
												$userid);
				// LOG
				$eventid = null;
				$eventname = 'avatar_removed';
				$params = array(
					'blobid' => $avatarblobid,
				);
				booker_log_event($userid, $eventid, $eventname, $params);

				// ajax return success
				$arrayBack = array(
					'removed' => '1',
				);
				echo json_encode($arrayBack);

				exit();
			} // END AJAX RETURN (removeavatar)


			// AJAX: setavailable
			$transferString = qa_post_text('setavailable'); // holds array
			if(isset($transferString))
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				// is admin editing another profile and posting the data then take userid from newdata
				// otherwise take userid from logged-in user
				if($isadmin && isset($newdata['userid']))
				{
					$userid = $newdata['userid'];
				}

				$available = ($newdata['available']==1);

				// set available in db
				booker_set_userfield($userid, 'available', $available);

				// LOG
				$eventid = null;
				$eventname = 'contractor_available';
				$params = array(
					'available' => $available,
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				// ajax return success
				$arrayBack = array(
					'updated' => '1',
				);
				echo json_encode($arrayBack);

				exit();
			} // END AJAX RETURN (setavailable)


			// AJAX: user is submitting his data
			$transferString = qa_post_text('userfulldata'); // holds array
			if(isset($transferString))
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				// is admin editing another profile and posting the data then take userid from newdata
				// otherwise take userid from logged-in user
				if($isadmin && isset($newdata['userid']))
				{
					$userid = $newdata['userid'];
				}

				$iscontractor = booker_iscontracted($userid);
				$newcontractor = ($newdata['newcontractor']==1);

				// $newusername = $newdata['name'];
				$newprice = floatval(str_replace(',', '.', $newdata['bookingprice']));
				// if(is_nan($newprice)||$newprice<(float)(qa_opt('booker_minimumprice')))
				if(is_nan($newprice))
				{
					// $newprice = (float)(qa_opt('booker_minimumprice'));
					$newprice = 0;
				}
				$newprice = round($newprice*100)/100;

				// only user can change himself
				$skype = empty($newdata['skype']) ? null : $newdata['skype'];
				$payment = empty($newdata['payment']) ? null : $newdata['payment'];
				$realname = empty($newdata['realname']) ? null : $newdata['realname'];
				$company = empty($newdata['company']) ? null : $newdata['company'];
				$birthdate = empty($newdata['birthdate']) ? null : $newdata['birthdate'];
				$address = empty($newdata['address']) ? null : $newdata['address'];
				$phone = empty($newdata['phone']) ? null : $newdata['phone'];
				$service = empty($newdata['service']) ? null : $newdata['service'];
				$serviceflags = empty($newdata['serviceflags']) ? 0 : $newdata['serviceflags'];
				$kmrate = $newdata['kmrate']; // empty($newdata['kmrate']) ? null : $newdata['kmrate'];
				$newavailable = empty($newdata['available']) ? 1 : $newdata['available']; // default 1
				$portfolio = $newdata['portfolio'];

				if( empty(trim(strip_tags($portfolio))) )
				{
					$portfolio = null;
				}

				// can be string such as "2016-07-27, sa, so"
				$contractorabsent = empty($newdata['contractorabsent']) ? null : $newdata['contractorabsent'];
				if(isset($contractorabsent))
				{
					// remove all spaces
					$contractorabsent = trim(str_replace(' ', '', $contractorabsent));
					$contractorabsent = helper_weekdays_to_numbers($contractorabsent);
				}

				// process serviceflags
				$userflags = booker_get_userfield($userid, 'flags');
				// clear former flags
				$userflags = $userflags & (~ MB_SERVICELOCAL);
				$userflags = $userflags & (~ MB_SERVICEONLINE);
				$userflags = $userflags & (~ MB_SERVICEATCUSTOMER);
				// set new flags
				$userflags = $userflags | $serviceflags;
				booker_set_userfield($userid, 'flags', $userflags);

				// service-at-customer activated
				if($serviceflags & MB_SERVICEATCUSTOMER)
				{
					if(empty($kmrate))
					{
						$kmrate = 0;
					}
					else
					{
						// convert string to float value
						$kmrate = (float)str_replace(',', '.', $kmrate);
					}
				}
				else
				{
					// if not at-customer then null the value
					$kmrate = null;
				}

				// *** MAKE one query only
				booker_set_userfield($userid, 'available', $newavailable);
				booker_set_userfield($userid, 'absent', $contractorabsent);
				booker_set_userfield($userid, 'portfolio', $portfolio);
				booker_set_userfield($userid, 'service', $service);
				booker_set_userfield($userid, 'bookingprice', $newprice);
				booker_set_userfield($userid, 'skype', $skype);
				booker_set_userfield($userid, 'payment', $payment);
				booker_set_userfield($userid, 'realname', $realname);
				booker_set_userfield($userid, 'company', $company);
				booker_set_userfield($userid, 'birthdate', $birthdate);
				booker_set_userfield($userid, 'address', $address);
				booker_set_userfield($userid, 'phone', $phone);
				booker_set_userfield($userid, 'kmrate', $kmrate);

				// *** temporary immediate approval
				// booker_set_userfield($userid, 'approved', MB_USER_STATUS_APPROVED);

				// check if first time registration and inform admin by email if so
				$registered = booker_get_userfield($userid, 'registered');
				$eventname = 'profile_edit';
				if(empty($registered))
				{
					$eventname = 'profile_created';

					$registertime = date("Y-m-d H:i:s");
					booker_set_userfield($userid, 'registered', $registertime);

					// get contractorname
					$realname = booker_get_realname($userid);

					// inform admin (*** could also be client!)
					$emailbody = '
					<p>
						'.qa_lang('booker_lang/newcontractor').'
					</p>
					<p>
						UserID: '.$userid.' <br />
						'.qa_lang('booker_lang/name').': <a href="'.q2apro_site_url().'userprofile?userid='.$userid.'">'.$realname.'</a> <br />
						'.qa_lang('booker_lang/company').': '.$company.' <br />
						'.qa_lang('booker_lang/birthdate').': '.$birthdate.' <br />
						'.qa_lang('booker_lang/address').': '.$address.' <br />
						'.qa_lang('booker_lang/telephone').': '.$phone.' <br />
						'.qa_lang('booker_lang/skype').': '.$skype.' <br />
						'.qa_lang('booker_lang/execution').': '.$serviceflags.' <br />
						'.qa_lang('booker_lang/price').': '.$newprice.' <br />
						'.qa_lang('booker_lang/paypal_iban').': '.$payment.' <br />
						<b>'.qa_lang('booker_lang/service').': '.$service.'</b> <br />
						'.qa_lang('booker_lang/portfolio').': <br />
							<blockquote>'.$portfolio.'</blockquote>
					</p>
					';
					q2apro_send_mail(array(
								'fromemail' => q2apro_get_sendermail(),
								'fromname'  => qa_opt('booker_mailsendername'),
								'senderid'	=> $userid, // for log
								'touserid'  => 1,
								'toname'    => qa_opt('booker_mailsendername'),
								'subject'   => qa_lang('booker_lang/newcon_mailsubject').': '.$realname,
								'body'      => $emailbody,
								'html'      => true
					));

				} // END empty($registered)

				// LOG
				$eventid = null;
				$params = array(
					'realname' => $realname,
					'company' => $company,
					'portfolio' => trim(strip_tags($portfolio)),
					'service' => $service,
					'bookingprice' => $newprice,
					'skype' => $skype,
					'payment' => $payment,
					'birthdate' => $birthdate,
					'address' => $address,
					'phone' => $phone,
					'serviceflags' => $serviceflags
				);
				booker_log_event($userid, $eventid, $eventname, $params);

				// first registration, inform jquery to show success message to user
				$message = 'updated';
				if(empty($registered))
				{
					$message = 'registered';
				}
				// jquery-redirects to contract page frontend
				if($newcontractor==1)
				{
					$message = 'isnewcontractor';
				}

				// ajax return success
				$arrayBack = array(
					'bookingprice' => number_format($newprice,2,',','.'),
					'portfolio' => $portfolio,
					'contractorabsent' => helper_weeknumbers_to_days($contractorabsent),
					'skype' => $skype,
					'payment' => $payment,
					'realname' => $realname,
					'company' => $company,
					'birthdate' => $birthdate,
					'address' => $address,
					'phone' => $phone,
					'service' => $service,
					'serviceflags' => $serviceflags,
					'kmrate' => number_format($kmrate,2,',','.'),
					'available' => $newavailable,
					'message' => $message
				);
				echo json_encode($arrayBack);

				exit();
			} // END AJAX RETURN (userfulldata)

			// if user sends checkboxes, agreements
			$postin = qa_get('completed');
			if(!empty($postin))
			{
				$contracttime = date("Y-m-d H:i:s");
				booker_set_userfield($userid, 'contracted', $contracttime);
				
				// get contractorname
				$realname = booker_get_realname($userid);
				
				// inform admin (*** could also be client!)
				$emailbody = '
				<p>
					'.qa_lang('booker_lang/newcontract').'
				</p>
				<p>
					UserID: '.$userid.' <br />
					'.qa_lang('booker_lang/name').': <a href="'.q2apro_site_url().'userprofile?userid='.$userid.'">'.$realname.'</a> <br />
				</p>
				';
				q2apro_send_mail(array(
							'fromemail' => q2apro_get_sendermail(),
							'fromname'  => qa_opt('booker_mailsendername'),
							'senderid'	=> $userid, // for log
							'touserid'  => 1,
							'toname'    => qa_opt('booker_mailsendername'),
							'subject'   => qa_lang('booker_lang/newcontract_subject').': '.$realname,
							'body'      => $emailbody,
							'html'      => true
				));
				
				// LOG
				$eventid = null;
				$eventname = 'contractor_contracted';
				$params = array(
					'userid' => $userid,
					'realname' => $realname,
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				// Frontend 
				$qa_content = qa_content_prepare();
				qa_set_template('booker contract');
				$qa_content['title'] = qa_lang('booker_lang/registersuccess');
				
				$qa_content['custom'] = '';
				$qa_content['custom'] .= '
				<p class="qa-success">
					'.qa_lang('booker_lang/contract_line_13').'
					<br />
					<br />
					'.qa_lang('booker_lang/contract_line_14').'
					<br />
					<br />
					'.qa_lang('booker_lang/contract_line_15').'
				</p>
				';
				$qa_content['custom'] .= '
				<p>
					<a id="backtoservices" href="'.qa_path('contractorlist').'" class="defaultbutton">'.qa_lang('booker_lang/back_services').'</a>
				</p>
				';
				return $qa_content;
			}

			// only members can access
			if(empty($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker userprofile');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}

			$ispremium = booker_ispremium($userid);

			// get userdata for handle and avatar
			$userdata = qa_db_read_one_assoc(qa_db_query_sub('SELECT handle,avatarblobid FROM ^users
																WHERE userid = #',
																$userid), true);
			if(empty($userdata))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker userprofile');
				$qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/user_notexist'));
				return $qa_content;
			}
			$imgsize = 250;
			if(isset($userdata['avatarblobid']))
			{
				$avatar = './?qa=image&qa_blobid='.$userdata['avatarblobid'].'&qa_size='.$imgsize;
			}
			else
			{
				$avatar = './?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size='.$imgsize;
			}
			$userhandle = $userdata['handle'];
			$userprofilelink = qa_path('user').'/'.$userdata['handle'];
			$contractorname = booker_get_realname($userid);

			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker userprofile');
			$qa_content['title'] = qa_lang('booker_lang/yourprofile');

			// init
			$qa_content['custom'] = '';

			// load userdata for display frontend
			$user = booker_getfulluserdata($userid);
			$contractorprice = (float)($user['bookingprice']);
			$isavailable = $user['available'];
			// if never saved then NULL, enable for first time registrations
			if(is_null($isavailable))
			{
				$isavailable = true;
			}
			$portfolio = $user['portfolio'];
			$contractorabsent = $user['absent'];
			$skype = $user['skype'];
			$payment = $user['payment'];
			$realname = $user['realname'];
			$company = $user['company'];
			$birthdate = $user['birthdate'];
			$address = $user['address'];
			$phone = $user['phone'];
			$service = $user['service'];
			$approveflag = $user['approved']; // account approved by admin
			$userflags = $user['flags'];
			$kmrate = $user['kmrate'];
			if(isset($kmrate))
			{
				// $kmrate = qa_opt('booker_kmrate'); // default
				$kmrate = number_format($kmrate,2,',','.');
			}
			else
			{
				// empty string
				$kmrate = '';
			}

			// in case we have weekday numbers, transform them to weekday name abbreviations
			if(!empty($contractorabsent))
			{
				$contractorabsent = helper_weeknumbers_to_days($contractorabsent);
			}

			// photo upload reminder
			// $avataruploaded = (qa_get_logged_in_flags() & QA_USER_FLAGS_SHOW_AVATAR) || (qa_get_logged_in_flags() & QA_USER_FLAGS_SHOW_GRAVATAR);

			$avatarblobid = qa_db_read_one_value(
								qa_db_query_sub('SELECT avatarblobid FROM ^users
														WHERE userid = #',
														$userid), true);
			$avatarremind = '';
			if(!isset($avatarblobid))
			{
				$avatarremind = '
				<div class="changeavatar-wrap noavatarset">
					<p class="douploadhint">
						'.qa_lang('booker_lang/douploadphoto').'
						<br />
						'.qa_lang('booker_lang/facerequired').'
					</p>
					<p>
						<input id="avatarupload" type="file" />
						<br />
						<progress id="progress_avatar" style="display:none;"></progress>
					</p>
				</div>';
			}
			else
			{
				$avatarremind = '
				<div class="changeavatar-wrap avatarset">
					<p id="changeavatar">
						<i class="fa fa-upload"></i> '.qa_lang('booker_lang/changeavatar').'
					</p>
					<p id="avatarupload-wrap" style="display:none;">
						<input id="avatarupload" type="file" />
						<br />
						<progress id="progress_avatar" style="display:none;"></progress>
					</p>
					<p id="removeavatar">
						<i class="fa fa-trash-o"></i> '.qa_lang('booker_lang/removeavatar').'
					</p>
				</div>';
			}

			// jquery
			$qa_content['custom'] .= "
			<script>
			$(document).ready(function()
			{
				$('#changeavatar').click( function()
				{
					$('#avatarupload-wrap').toggle();
				});

				$('#removeavatar').click( function()
				{
					var confirming = confirm('".qa_lang('booker_lang/removeavatar_confirm')."');
					if(!confirming)
					{
						return;
					}
					var clicked = $(this);
					var removedata = {
						remove: 1,
						userid: ".$userid.",
					};
					console.log(removedata);
					$.ajax({
						type: 'POST',
						url: '".qa_self_html()."',
						data: { removeavatar: JSON.stringify(removedata) },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: ');
							console.log(data);
							if(data['removed']=='1')
							{
								$('.q2apro_hs_avatar img').attr('src', './?qa=image&qa_blobid=".qa_opt('avatar_default_blobid')."&qa_size=".$imgsize."');
								clicked.remove();
							}
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				});

				$('#avatarupload').change( function() {
					uploadimgfile_avatar();
					// clear file dialog input because Chrome/Safari do not upload same filename twice
					$('#avatarupload').val(null);
				});

				// upload after user has chosen the image
				function uploadimgfile_avatar()
				{
					console.log('submitting avatar file via html5 ajax');

					// check for maximal image size
					var maximgsize = ".qa_opt('q2apro_sceditor_upload_max_size').";
					var imgsize = $('#avatarupload')[0].files[0].size;
					// console.log(maximgsize + ' | ' + imgsize);
					if(imgsize > maximgsize)
					{
						var img_size = (Math.round((imgsize/1024/1024) * 100) / 100);
						var maximg_size = (Math.round((maximgsize / 1024 / 1024) * 100) / 100);
						var errormsg = ('".qa_lang('main/max_upload_size_x')."').replace('^', maximg_size+' Mb');
						alert(errormsg);
						return;
					}

					var formdata = new FormData();
					// append file and data to object
					formdata.append('imgfile', $('#avatarupload')[0].files[0] );
					formdata.append('userid', '".$userid."');

					$.ajax({
						url: '".qa_self_html()."', // server script to process image
						type: 'POST',
						xhr: function() {
							// custom XMLHttpRequest
							var myXhr = $.ajaxSettings.xhr();
							if(myXhr.upload){
								// check if upload property exists
								myXhr.upload.addEventListener('progress',progressHandlingFunction_av, false); // for handling the progress of the upload
							}
							return myXhr;
						},
						// ajax events
						beforeSend: beforeSendHandler_av,
						success: completeHandler_av,
						error: errorHandler_av,
						// form data
						data: formdata,
						// options to tell jQuery not to process data or worry about content-type
						cache: false,
						contentType: false,
						processData: false
					});
				};
				function beforeSendHandler_av(e) {
					$('progress#progress_avatar').show();
				}
				function progressHandlingFunction_av(e){
					if(e.lengthComputable){
						// html5 progressbar
						$('progress#progress_avatar').attr({value:e.loaded,max:e.total});
					}
				}
				function completeHandler_av(e) {
					console.log(e);
					// e holds image html
					$('.q2apro_hs_avatar').html(e);
					$('progress#progress_avatar').hide();
					$('.douploadhint, #avatarupload').hide();
				}
				function errorHandler_av(e) {
					alert('Upload failed: '+e);
					console.log(e);
				}


			}); // end ready
			</script>
			";

			$accountstatus = '';
			if($approveflag==MB_USER_STATUS_DEFAULT)
			{
				$accountstatus = '<i class="fa fa-coffee fa-lg"></i> '.qa_lang('booker_lang/we_verify');
			}
			else if($approveflag==MB_USER_STATUS_APPROVED)
			{
				$accountstatus = '<i class="fa fa-check-circle-o fa-lg"></i> '.qa_lang('booker_lang/approved');
			}
			else if($approveflag==MB_USER_STATUS_DISAPPROVED)
			{
				$accountstatus = '<i class="fa fa-ban fa-lg"></i> '.qa_lang('booker_lang/disapproved');
			}

			$bookinglink = '';
			$nonpublic = '';
			$turnspublic = '';

			$iscontractor = booker_iscontracted($userid);

			if($iscontractor)
			{
				$bookinglink = '
							<a class="defaultbutton btn_orange" href="'.qa_path('bookingbutton').'">'.qa_lang('booker_lang/getbookingbutton').'</a>
							<br />
							<a class="defaultbutton btn_green" target="_blank" href="'.qa_path('booking').'?contractorid='.$userid.'">'.qa_lang('booker_lang/bookingsite').'</a>
							<br />
							';
			}
			else
			{
				$nonpublic = '
										<div id="notpublic">
											<p style="font-weight:bold;padding-top:10px;white-space:nowrap;">
												'.qa_lang('booker_lang/nonpublic').':
											</p>
											<p>
											</p>
										</div>
				';
				$turnspublic = '
										<div>
											'.qa_lang('booker_lang/needfulldata').'
										</div>
				';
			}

			// user forum data
			$aselecteds = 0;
			$points = 0;
			$upvoteds= 0;

			$becomecontractor_btn = '';
			$lastbutton = '
						<button type="submit" class="defaultbutton senddatabtn">
							<i class="fa fa-floppy-o"></i> '.qa_lang('booker_lang/save_btn').'
						</button>
						';
			$termscon_checkboxes = '';
			$termscon_jquery = '';
			
			$newcontractorflag = '';
			if(!$iscontractor)
			{
				$newcontractorflag = '<span id="newcontractorregistering"></span>';
				
				$becomecontractor_btn = '
							<p style="text-align:right;">
								<span id="revealcontractorform" class="defaultbutton btn_red">
									<!-- <i class="fa fa-shopping-basket"></i> --> '.qa_lang('booker_lang/contractor_data_qu').'
								</span>
							</p>
								<style type="text/css">
									#revealcontractorform {
										margin-right:0;
									}
									/*
									#revealcontractorform {
										position: absolute;
								     	top: 80px;
								     	left: 530px;
									}
									@media screen and (max-width: 640px) {
										#revealcontractorform {
											position: static;
										}
									}
									*/
								</style>
								';
				$lastbutton = '<button type="submit" class="defaultbutton senddatabtn registerbtn">'.qa_lang('booker_lang/finish_registration_btn').'</button>';
				$termscon_checkboxes = '
					<h3 style="margin-top:50px;">
						'.qa_lang('booker_lang/contract_line_7').': 
					</h3>
					<p>
						<label>
							<input type="checkbox" name="accepted1" id="accepted1" value="1" /> 
							'.qa_lang('booker_lang/contract_line_8').'
						</label>
					</p>
					<p>
						<label>
							<input type="checkbox" name="accepted2" id="accepted2" value="2" /> 
							'.
							strtr( qa_lang('booker_lang/contract_line_9'), array( 
							'^1' => '<a target="_blank" href="'.qa_path('termscon').'">',
							'^2' => '</a>'
							)).
							'
						</label>
					</p>
					<p>
						<label>
							<input type="checkbox" name="accepted3" id="accepted3" value="3" /> 
							'.qa_lang('booker_lang/contract_line_10').'
						</label>
					</p>
					
					<p style="display:none;margin-top:30px;">
						'.qa_lang('booker_lang/contract_line_11').'
					</p>
				';
				
				$termscon_jquery = "
					// check if all checkmarks are ticked
					if(!$('#accepted1').prop('checked') || !$('#accepted2').prop('checked') || !$('#accepted3').prop('checked'))
					{
						alert('".qa_lang('booker_lang/contract_line_12')."');
						$('html,body').animate({
							scrollTop: $('#accepted1').offset().top-100
						}, 700);
						$('#accepted1').focus();
						$('.qa-waiting').hide();
						return;
					}
				";

			}

			$adminblock = '';
			if($isadmin)
			{
				$fulldata = booker_getfulluserdata($userid);
				$contractormail = helper_getemail($userid);

				$adminblock = '
				<div class="adminblock">
					<p>
						<b>
							'.qa_lang('booker_lang/admin').':
						</b>
						'.qa_lang('booker_lang/email').': <a href="mailto:'.$contractormail.'">'.$contractormail.'</a>
						|
						<span>Absent: '.$fulldata['absent'].'</span>
						|
						<span>'.qa_lang('booker_lang/calendar').': '.$fulldata['externalcal'].'</span>
						|
						<span>'.qa_lang('booker_lang/registered').': '.helper_get_readable_date_from_time($fulldata['registered'], false, false).'</span>
						|
						<span>'.qa_lang('booker_lang/contracted').': '.helper_get_readable_date_from_time($fulldata['contracted'], false, false).'</span>
						|
						<span>'.qa_lang('booker_lang/approved').': '.$fulldata['approved'].'</span>
					</p>
				</div>
				';
			}

			$premiumindicator = '';
			if($ispremium)
			{
				$premiumindicator = '
				<div class="youarepremium">
					<p>
						'.qa_lang('booker_lang/membership').':
					</p>
					<p>
						<i class="fa fa-star" style="color:#FA0;"></i> '.qa_lang('booker_lang/youarepremium').'
					</p>
				</div>
				';
			}

			// output
			$qa_content['custom'] .= '
							<div class="profileimage-wrap">

								<div class="profileimage">
									<div class="q2apro_hs_avatar">
										<img style="max-width:200px;" src="'.$avatar.'" alt="'.$contractorname.'" />
										<br />
										<span style="font-size:12px;color:#333;" title="'.$userid.'">'.$contractorname.'</span>
									</div>
									<div class="contractorrealname" style="margin-top:10px;display:block;">
										'.$avatarremind.'
									</div>
								</div>

								<div class="reputationlab">
										'.$bookinglink.'
										<a class="defaultbutton btn_green" target="_blank" href="'.qa_path('user').'/'.$userhandle.'">'.qa_lang('booker_lang/forum_profile').'</a>
								</div>
							</div>

							<div class="profiledit">

								<p class="inbetween" style="display:none;">
									'.qa_lang('booker_lang/basic_data').'
								</p>

								<div class="profiltable basicdata">
								'.$premiumindicator.'
									<div class="contractorname">
										<p>
											'.qa_lang('booker_lang/firstlastname').':
										</p>
										<p>
											<input type="text" name="realname" id="realname" value="'.$realname.'" placeholder="'.qa_lang('booker_lang/firstlastname').'" title="'.qa_lang('booker_lang/fullnamehint').'">
											<br />
											<span class="fullnamehint colorme colorme-red hidden">
												'.qa_lang('booker_lang/nocompanyname').'
											</span>
										</p>
									</div>
									'.$nonpublic.'
									<div class="contractoraddress">
										<p>
											'.qa_lang('booker_lang/address').':
										</p>
										<p>
											<input type="text" name="address" id="address" value="'.$address.'" placeholder="'.qa_lang('booker_lang/placeholder_addressexample').'" />
											<br />
											<span class="addresshint colorme hidden">
												'.qa_lang('booker_lang/addresshint').'
											</span>
										</p>
									</div>
									<div class="contractorbirthdate">
										<p>
											'.qa_lang('booker_lang/birthdate').':
										</p>
										<p>
											<input type="text" name="birthdate" id="birthdate" value="'.$birthdate.'" placeholder="'.qa_lang('booker_lang/placeholder_dateexample').'" />
										</p>
									</div>
									<div class="contractorphone">
										<p>
											'.qa_lang('booker_lang/telephone').':
										</p>
										<p>
											<input type="text" name="phone" id="phone" value="'.$phone.'" placeholder="'.qa_lang('booker_lang/placeholder_phone').'" />
										</p>
									</div>
									<div class="contractorskype">
										<p>
											'.qa_lang('booker_lang/skypename').':
										</p>
										<p>
											<input type="text" name="skype" id="skype" value="'.$skype.'" placeholder="'.qa_lang('booker_lang/optional').'" />
										</p>
									</div>

								</div>

								'.$becomecontractor_btn.'
								
								<p style="text-align:right;">
									<button type="submit" class="defaultbutton senddatabtn" id="senddatabasic">
										<i class="fa fa-floppy-o"></i> '.qa_lang('booker_lang/save_btn').'
									</button>

									<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
								</p>


								<div class="contractorfull-wrap">
									<h3>
										'.qa_lang('booker_lang/become_contractor').'
									</h3>

									<div class="inbetween">
										'.qa_lang('booker_lang/contractor_data').'
									</div>

									<div class="contractordata-wrap">
										'.$turnspublic.'
										<div class="profiltable conservicedesc">

											<div class="contractorservice">
												<p>
													'.qa_lang('booker_lang/yourservice_short').':
												</p>
												<p>
													<input type="text" name="service" id="service" value="'.$service.'" placeholder="'.qa_lang('booker_lang/placeholder_service').'" />
												</p>
												'.
												(!$iscontractor ? '' : '
															<label class="switch switch-flat" title="'.qa_lang('booker_lang/available').'">
																<input class="switch-input" type="checkbox" name="available" id="available" '.($isavailable ? 'checked' : '').'/>
																<span class="switch-label" data-on="On" data-off="Off"></span>
																<span class="switch-handle"></span>
															</label>
												').
												'
											</div>

											<div class="contractorcompany">
												<p>
													'.qa_lang('booker_lang/company').':
												</p>
												<p>
													<input type="text" name="company" id="company" value="'.$company.'" placeholder="'.qa_lang('booker_lang/optional').'" title="'.qa_lang('booker_lang/company').'">
												</p>
											</div>

										</div>

										<div class="contractorportfolio">
											<p style="margin:0 0 5px 0;font-weight:bold;">
												'.qa_lang('booker_lang/yourservice_desc').':
											</p>
											<p class="portfoliohint">
												'.
												strtr( qa_lang('booker_lang/yourservice_hint'), array( 
												'^1' => '<a target="_blank" href="/premium">',
												'^2' => '</a>'
												)).
												'
											</p>

											<textarea name="portfolio" id="portfolio">'.$portfolio.'</textarea>

											<div class="html5image">
												<p>'.qa_lang('booker_lang/insert_img').':
													<input id="scupload_image_content" name="imgfile" type="file" style="border:0;" />
												</p>
												<input id="scupload_imgbtn" type="button" value="Upload" style="display:none;" />
											</div>
											<progress style="display:none;"></progress>

											<p id="sceditor_maxlength_warning_content" style="display:none;color:#E33;">
												'.qa_lang('booker_lang/textlength_warn').'
											</p>
										</div>

										<div class="profiltable conserviceprices">
											<div class="contractorservice">
												<p>
													'.qa_lang('booker_lang/execution').':
												</p>
												<p class="serviceflagwrap">
													<label>
														<input class="serviceflags" type="checkbox" value="'.MB_SERVICELOCAL.'"
															'.($userflags&MB_SERVICELOCAL ? 'checked' : '').'
														/>
														'.qa_lang('booker_lang/servicelocal').'
													</label>
													<label>
														<input class="serviceflags" type="checkbox" value="'.MB_SERVICEONLINE.'"
															'.($userflags&MB_SERVICEONLINE ? 'checked' : '').'
														/>
														'.qa_lang('booker_lang/serviceonline').'
													</label>
													<label>
														<input class="serviceflags" id="atcustomer" type="checkbox" value="'.MB_SERVICEATCUSTOMER.'"
															'.($userflags&MB_SERVICEATCUSTOMER ? 'checked' : '').'
														/>
														'.qa_lang('booker_lang/serviceatcustomer').'
													</label>
													<label class="kmratewrap">
														<input id="kmrate" type="text" value="'.$kmrate.'" placeholder="'.qa_lang('booker_lang/eg').' '.number_format(qa_opt('booker_kmrate'),2,',','.').'" />
														'.qa_opt('booker_currency').' '.qa_lang('booker_lang/perkm').'
														<span class="kmratehint">
															'.qa_lang('booker_lang/kmratehint').'
															<span id="kmrateexample">…</span> '.qa_opt('booker_currency').'
														</span>
													</label>
												</p>
											</div>

											<div class="contractorminprice" style="margin-top:30px;">
												<p>
													'.qa_lang('booker_lang/your_price').':
												</p>
												<p>
													<input style="width:50px;" type="text" value="'.number_format($contractorprice,2,',','.').'" name="bookingprice" id="bookingprice" /> '.qa_opt('booker_currency').' '.qa_lang('booker_lang/per_hour').'
												</p>
											</div>
											'.
											($iscontractor ? '' : '
												<div>
													<p style="padding-top:15px;">'.qa_lang('booker_lang/specialoffers').':</p>
													<p>
														'.qa_lang('booker_lang/offershint').'
													</p>
												</div>
											').
											'

											<!--
											<div class="contractorcommission">
												<p style="padding-top:15px;">'.qa_lang('booker_lang/commission').':</p>
												<p>
													'.number_format(booker_getcommission($userid)*100,0,',','.').' %
												</p>
											</div>
											-->

											<div class="contractorpayout" style="display:none;">
												<p style="padding-top:15px;">'.qa_lang('booker_lang/payout').':</p>
												<p id="contractorpricecalc"></p>
											</div>

											<!--
											<div class="contractorpayment">
												<p>
													'.qa_lang('booker_lang/paypal_iban').':
												</p>
												<p>
													<input type="text" name="payment" id="payment" value="'.$payment.'" placeholder="'.qa_lang('booker_lang/optional').'" />
												</p>
											</div>
											-->

											<div class="accountstatus">
												<p>
													'.qa_lang('booker_lang/accountstatus').':
												</p>
												<p>
													<span class="accountstatus">
													'.$accountstatus.'
													</span>
												</p>
											</div>
										</div> <!-- profiltable -->
										
										<div class="termsconditions">
											<p>
												'.$termscon_checkboxes.'
											</p>
										</div>
										
										<div class="tfoot">
											<p style="float:right;text-align:right;">
												<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
												'.$lastbutton.'
											</p>
											'.$newcontractorflag.'
										</div>
									</div> <!-- contractordata-wrap -->
								</div> <!-- contractorfull-wrap -->

							</div> <!-- profiledit -->
							';

			$qa_content['custom'] .= '

								<div style="clear:both;"></div>
								'.$adminblock;

			// ?show=contractor
			$showcontractorfields = qa_get('show');
			$revealcontractorform = '';
			if($showcontractorfields=='contractor')
			{
				$revealcontractorform = "
				$('#revealcontractorform').trigger('click');
				";
			}

			// jquery
			$qa_content['custom'] .= "
	<script>

	var warn_on_leave = false; // global, see sceditor code

	$(document).ready(function()
	{
		var contractorformvisible = false;

		$('#revealcontractorform').click( function()
		{
			contractorformvisible = true;
			$('.contractorfull-wrap').show();
			$('#notpublic').hide();
			$(this).remove();
			// scroll down to contractor data
			$('html,body').animate({
				// scrollTop: $('.contractorfull-wrap').offset().top-20
				scrollTop: $('.contractorfull-wrap').offset().top-100
			}, 700);
		});

		$('#bookingprice').on('input', function() {
			setPayout();
		});

		$('#atcustomer').click( function()
		{
			if($(this).prop('checked'))
			{
				if($('#kmrate').val().length>0)
				{
					kmrate = Number($('#kmrate').val().toString().replace(/,/g, '.'));
					var kmrateout = (kmrate*10).toFixed(2).replace(/\./g, ',');
					$('#kmrateexample').text(kmrateout);
				}
				$('.kmratewrap').fadeIn();
			}
			else
			{
				$('.kmratewrap').fadeOut();
				// $('#kmrate').val('')
			}
		});

		var kmrateformer = $('#kmrate').val();
		$('#kmrate').keyup( function()
		{
			if(kmrateformer != $('#kmrate').val())
			{
				kmrateformer = $('#kmrate').val();
				$('#kmrateexample').text( get_kmrate_comma() );
			}
		});

		$('#realname').focus( function()
		{
			$('.fullnamehint').show();
		})
		.blur( function()
		{
			$('.fullnamehint').hide();
		});

		$('#address').focus( function()
		{
			$('.addresshint').show();
		})
		.blur( function()
		{
			$('.addresshint').hide();
		});

		// CTRL+Enter because only enter conflicts with textarea
		$('.basicdata').keypress(function(e)
		{
			// if(e.ctrlKey && e.which == 13) {
			if(e.which == 13)
			{
				$('.senddatabtn:first').trigger('click');
				// $('.senddatabtn').focus();
			}
		});

		$('#available').click( function(e)
		{
			var availablechange = $(this).prop('checked');

			var clicked = $(this);
			var availabledata = {
				available: availablechange,
				userid: ".$userid.",
			};
			console.log(availabledata);

			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { setavailable: JSON.stringify(availabledata) },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					console.log('server returned: ');
					console.log(data);
					if(data['updated']=='1')
					{
						// do nothing
					}
				},
				error: function(xhr, status, error) {
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
				}
			}); // end ajax
		});

		$('.senddatabtn').click( function(e)
		{
			e.preventDefault();

			var doregister = $('.senddatabtn:last').hasClass('registerbtn') && $('#newcontractorregistering').length>0 && contractorformvisible;

			// if save button on top is clicked, then only save basic data, do not redirect to contract page
			if($(this).attr('id')=='senddatabasic')
			{
				doregister = false;
			}

			/*
			if( parseFloat( $('#bookingprice').val().replace(',','.') ) <".qa_opt('booker_minimumprice')." ) {
				$('#contractorpricecalc').html('<span style=\"color:red;\">Der Mindestpreis ist ".qa_opt('booker_minimumprice')." ".qa_opt('booker_currency').".</span>');
				$('#bookingprice').val(".qa_opt('booker_minimumprice').");
				return;
			}
			*/

			// copy data to textarea
			$('#portfolio').val( $('#portfolio').data('sceditor').val() );
			warn_on_leave = false;

			var flags = 0;
			$('.serviceflags:checked').each(function()
			{
				flags = parseInt(flags) | parseInt($(this).val());
			});

			// show loading indicator
			$('.qa-waiting').show();

			if(doregister)
			{
				// check for complete data if contractor
				if($('#realname').val().trim()=='') {
					alert('".qa_lang('booker_lang/specify_fullname')."');
					$('#realname').focus();
					$('.qa-waiting').hide();
					return;
				}
				if($('#address').val().trim()=='') {
					alert('".qa_lang('booker_lang/specify_address')."');
					$('#address').focus();
					$('.qa-waiting').hide();
					return;
				}
				
				if($('#service').val()=='') {
					alert('".qa_lang('booker_lang/specify_service')."');
					$('#service').focus();
					$('.qa-waiting').hide();
					return;
				}
				
				/*
				var bprice = parseInt($('#bookingprice').val().trim());
				if(bprice==0 || isNaN(bprice))
				{
					alert('".qa_lang('booker_lang/set_price')."');
					$('#bookingprice').focus();
					$('.qa-waiting').hide();
					return;
				}
				*/

				var edcontent = $('textarea[name=\"portfolio\"]').sceditor('instance').val();
				// strip tags and trim
				edcontent = edcontent.replace( /<.*?>/g, '' ).trim();
				console.log('>>> '+edcontent);
				// min of 20 chars
				if(edcontent.length<20)
				{
					alert('".qa_lang('booker_lang/specify_portfolio')."');
					// $('#service').focus();
					
					// scroll up to editor field
					$('html,body').animate({
						scrollTop: $('.portfoliohint').offset().top-100
					}, 700);
					$('.qa-waiting').hide();
					return;
				}
				
				".$termscon_jquery."
			}

			var gdata = {
				bookingprice: $('#bookingprice').val(),
				portfolio: $('#portfolio').val(),
				contractorabsent: $('#contractorabsent').val(),
				skype: $('#skype').val(),
				payment: $('#payment').val(),
				realname: $('#realname').val(),
				company: $('#company').val(),
				birthdate: $('#birthdate').val(),
				address: $('#address').val(),
				phone: $('#phone').val(),
				service: $('#service').val(),
				serviceflags: flags,
				kmrate: $('#kmrate').val(),
				available: $('#available').is(':checked'),
				newcontractor: doregister,
				userid: ".$userid.",
			};
			console.log(gdata);
			var senddata = JSON.stringify(gdata);
			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { userfulldata:senddata },
				dataType: 'json',
				cache: false,
				success: function(data)
				{
					$('.qa-waiting').hide();
					console.log('server returned: '+data+' | message: '+data['message']);
					if(data['message']=='updated')
					{
						$('#bookingprice').val(data['bookingprice']);
						$('#portfolio').val(data['portfolio']);
						$('#contractorabsent').val(data['contractorabsent']);
						$('#skype').val(data['skype']);
						$('#payment').val(data['payment']);
						$('#realname').val(data['realname']);
						$('#company').val(data['company']);
						$('#birthdate').val(data['birthdate']);
						$('#address').val(data['address']);
						$('#phone').val(data['phone']);
						$('#service').val(data['service']);
						$('#serviceflags').val(data['serviceflags']);
						$('#kmrate').val(data['kmrate']);
						$('#available').prop('checked,', data['available']);
						setPayout();
						$('<p class=\"smsg\">✓ ".qa_lang('booker_lang/data_updated')."</p>').insertAfter('.senddatabtn');
						$('.smsg').fadeOut(2000, function() {
							$(this).remove();
							window.scrollTo(0, 0);
						});
					}
					if(data['message']=='registered')
					{
						window.scrollTo(0, 0);
						$('h1').after('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/profilecreated')."</p>');
					}
					if(data['message']=='isnewcontractor')
					{
						// redirect to contract page after successful save
						// alert('redirecting');
						window.location.href = './userprofile?completed=1&userid=".$userid."';
					}
				},
				error: function(xhr, status, error) {
					$('.qa-waiting').hide();
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
				}
			}); // end ajax
		}); // end senddatabtn

		function setPayout()
		{
			var contractorval = parseFloat( $('#bookingprice').val().replace(',','.') );

			if(isNaN(contractorval)) {
				$('#contractorpricecalc').text('".qa_lang('booker_lang/set_price')."');
				return;
			}

			var commission = ".booker_getcommission($userid).";
			var contractornetperc = 1 - commission;
			var payout = contractornetperc * contractorval;
			// var calcstring = String(payout.toFixed(2)).replace('.',',')+' ".qa_opt('booker_currency')." / ".qa_lang('booker_lang/hourabbr')." = <div style=\"color:#00F;display:inline;\">'+String((payout).toFixed(2)).replace('.',',')+' ".qa_opt('booker_currency')."/".qa_lang('booker_lang/hour')."</div>';

			var calcstring = ((1-commission)*100)+' % · '+String(contractorval.toFixed(2)).replace('.',',')+' ".qa_opt('booker_currency')."/".qa_lang('booker_lang/hourabbr')." = <div style=\"color:#00F;display:inline;\">'+String((payout).toFixed(2)).replace('.',',')+' ".qa_opt('booker_currency')."/".qa_lang('booker_lang/hour')."</div>';

			$('#contractorpricecalc').html(calcstring);

		}

		function get_kmrate_comma()
		{
			var kmrate = Number($('#kmrate').val().toString().replace(/,/g, '.'));
			var kmrateout = (kmrate*10).toFixed(2).replace(/\./g, ',');
			return kmrateout;
		}


		// startup
		if($('#kmrate').val().length>0 && $('#atcustomer').prop('checked'))
		{
			$('#kmrateexample').text(get_kmrate_comma());
			$('.kmratewrap').show();
		}
		else
		{
			$('.kmratewrap').hide();
		}
		setPayout();

		// in case there is already data for service etc., reveal it
		/*
		if($('#service').val()!='')
		{
			// $('#revealcontractorform').trigger('click');
			contractorformvisible = true;
			$('.contractorfull-wrap').show();
			$('#notpublic').hide();
			$('#revealcontractorform').remove();
		}
		*/

		".$revealcontractorform."

	}); // end jquery ready

</script>
			";

			$extracss = '';
			if(!$iscontractor)
			{
				$extracss = '
					.contractorfull-wrap {
						display:none;
					}
				';
			}

			$qa_content['custom'] .= '
			<style type="text/css">
				'.$extracss.'
				.changeavatar-wrap {
					display:block;
					font-size:12px;
				}
				#avatarupload {
					max-width:200px;
				}
				.douploadhint {
					text-align:center;
				}
				.noavatarset {
					color:#F00;
				}
				.avatarset {
					color:#999;
					cursor:pointer;
				}
				#changeavatar, #removeavatar {
					margin:0;
					padding:0;
				}
				#changeavatar {
					margin-bottom:5px;
				}
				#changeavatar:hover, #removeavatar:hover {
					color:#000;
				}

				.q2apro_hs_avatar {
					display:block;
					margin-bottom:10px;
					text-align:center;
				}
				.reputationlab .btn_green,
				.reputationlab .btn_orange {
					max-width:195px;
					padding:7px 10px;
					font-size:12px;
					margin:5px 4px 0 0;
				}
				.btn_red {
					border:1px solid #E33;
					padding:10px 15px;
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
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:100%;
					min-height:640px;
				}
				.qa-main input {
					padding:7px;
					border:0;
					background:#F2F5F7;
				}
				.qa-main p {
					line-height:150%;
				}

				.adminblock {
					display:inline-block;
					margin-top:50px;
					padding:10px 10px 0px 10px;
					background:#FFF7CC;
					border:1px solid #EEC;
					font-size:11px;
					color:#555;
				}
				.adminblock a {
					display:inline-block;
				}
				.profiledit {
					display:inline-block;
					margin-left:2px;
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
				.profiltable.conserviceprices p {
					min-width:120px;
				}
				.profileimage-wrap {
					display: inline-block;
					float: right;
				}
				.profileimage a,
				.reputationlab a
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
				.contractorcompany, .contractorcompany a {
					color: #808080;
				}
				/*
				.bookinglink {
					display:block;
					margin-bottom:5px;
				}
				*/
				.reputationlab {
					text-align: left;
					display:block;
					width: auto;
					padding: 20px 20px 15px 20px;
					margin:0;
					border: 1px solid #DDE;
					background: #F5F5F5;
					clear:both;
				}
				.contractorprice {
					font-size:12px;
				}
				.availabletimes {
					font-weight:bold;
				}
				.accountstatus {
					display:block;
					max-width:400px;
					color:#00F;
					vertical-align:2px;
					margin-bottom:5px;
				}
				#service {
					display:inline-block;
					margin-right:20px;
				}
				.profiltable div input[type=text] {
					width:260px;
				}
				.colorme-red {
					color:#F00 !important;
				}
				#realname, #address {
					margin-bottom:3px;
				}
				/*
				.fullnamehint, .addresshint {
					display:inline-block;
					font-size:11px;
					color:#FFF;
					margin:3px 0 0 5px;
				}
				*/
				.hidden {
					display:none;
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
				.contractorabsenthint {
					display:inline-block;
					max-width:270px;
					margin-top:5px;
					font-size:11px;
					color:#777;
					line-height:130%;
				}
				.serviceflagwrap label {
					display:inline-block;
					width:90%;
					margin:0 0 10px 0;
				}
				#kmrate {
					width:75px;
					text-align:right;
				}
				#bookingprice {
					text-align:right;
				}
				.kmratehint {
					display:block;
					margin:3px 0 0 3px;
					color:#888;
				}
				.hourhint {
					display:inline-block;
					margin-top:4px;
					color:#888;
				}
/* http://www.htmllion.com/css3-toggle-switch-button.html */
.switch {
	position: relative;
	display: inline-block;
	vertical-align: top;
	width: 80px;
	height: 30px;
	padding: 3px;
	margin: 0 10px 10px 0;
	background: linear-gradient(to bottom, #eeeeee, #FFFFFF 25px);
	background-image: -webkit-linear-gradient(top, #eeeeee, #FFFFFF 25px);
	border-radius: 18px;
	box-shadow: inset 0 -1px white, inset 0 1px 1px rgba(0, 0, 0, 0.05);
	cursor: pointer;
}
.switch-input {
	position: absolute;
	top: 0;
	left: 0;
	opacity: 0;
}
.switch-label {
	position: relative;
	display: block;
	height: inherit;
	font-size: 10px;
	text-transform: uppercase;
	background: #eceeef;
	border-radius: inherit;
	box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.12), inset 0 0 2px rgba(0, 0, 0, 0.15);
}
.switch-label:before, .switch-label:after {
	position: absolute;
	top: 50%;
	margin-top: -.5em;
	line-height: 1;
	-webkit-transition: inherit;
	-moz-transition: inherit;
	-o-transition: inherit;
	transition: inherit;
}
.switch-label:before {
	content: attr(data-off);
	right: 11px;
	color: #aaaaaa;
	text-shadow: 0 1px rgba(255, 255, 255, 0.5);
}
.switch-label:after {
	content: attr(data-on);
	left: 11px;
	color: #FFFFFF;
	text-shadow: 0 1px rgba(0, 0, 0, 0.2);
	opacity: 0;
}
.switch-input:checked ~ .switch-label {
	background: #E1B42B;
	box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.15), inset 0 0 3px rgba(0, 0, 0, 0.2);
}
.switch-input:checked ~ .switch-label:before {
	opacity: 0;
}
.switch-input:checked ~ .switch-label:after {
	opacity: 1;
}
.switch-handle {
	position: absolute;
	top: 4px;
	left: 4px;
	width: 28px;
	height: 28px;
	background: linear-gradient(to bottom, #FFFFFF 40%, #f0f0f0);
	background-image: -webkit-linear-gradient(top, #FFFFFF 40%, #f0f0f0);
	border-radius: 100%;
	box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.2);
}
.switch-handle:before {
	content: "";
	position: absolute;
	top: 50%;
	left: 50%;
	margin: -6px 0 0 -6px;
	width: 12px;
	height: 12px;
	background: linear-gradient(to bottom, #eeeeee, #FFFFFF);
	background-image: -webkit-linear-gradient(top, #eeeeee, #FFFFFF);
	border-radius: 6px;
	box-shadow: inset 0 1px rgba(0, 0, 0, 0.02);
}
.switch-input:checked ~ .switch-handle {
	left: 54px;
	box-shadow: -1px 1px 5px rgba(0, 0, 0, 0.2);
}

/* Transition
========================== */
.switch-label, .switch-handle {
	transition: All 0.3s ease;
	-webkit-transition: All 0.3s ease;
	-moz-transition: All 0.3s ease;
	-o-transition: All 0.3s ease;
}

/* Switch Flat
==========================*/
.switch-flat {
	padding: 0;
	background: #FFF;
	background-image: none;
	margin-top:-5px;
}
.switch-flat .switch-label {
	background: #FFF;
	border: solid 2px #eceeef;
	box-shadow: none;
}
.switch-flat .switch-label:after {
	color: #0088cc;
}
.switch-flat .switch-handle {
	left: 6px;
	background: #dadada;
	width: 22px;
	height: 22px;
	box-shadow: none;
}
.switch-flat .switch-handle:before {
	background: #eceeef;
}
.switch-flat .switch-input:checked ~ .switch-label {
	background: #FFF;
	border-color: #0088cc;
}
.switch-flat .switch-input:checked ~ .switch-handle {
	left: 52px;
	background: #0088cc;
	box-shadow: none;
}

				/* smartphones */
				@media only screen and (max-width:480px) {
					.qa-main {
						width:95%;
					}
					.profileimage-wrap {
						float: none;
					}
					.profiledit {
						margin-top:50px;
					}
					#service {
						width:95%;
					}
					.calchoosetime {
						width:95%;
					}
					.profileimage {
						float:none;
						margin:20px 0;
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
					$('input:submit').click( function()
					{
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


/*
	Omit PHP closing tag to help avoid accidental output
*/

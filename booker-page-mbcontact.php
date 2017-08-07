<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_mbcontact 
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
					'title' => 'booker Page Contact', // title of page
					'request' => 'mbcontact', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='mbcontact') 
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
				qa_set_template('booker mbcontact');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';

			$userid = qa_get_logged_in_userid();

			if(isset($userid) && !QA_FINAL_EXTERNAL_USERS) 
			{
				list($useraccount, $userprofile)=qa_db_select_with_pending(
					qa_db_user_account_selectspec($userid, true),
					qa_db_user_profile_selectspec($userid, true)
				);
			}

			$usecaptcha=qa_opt('captcha_on_feedback') && qa_user_use_captcha();


			//	Check feedback is enabled and the person isn't blocked
			if (!qa_opt('feedback_enabled'))
			{
				return include QA_INCLUDE_DIR.'qa-page-not-found.php';				
			}

			if (qa_user_permit_error()) 
			{
				$qa_content=qa_content_prepare();
				$qa_content['error']=qa_lang_html('users/no_permission');
				return $qa_content;
			}


			//	Send the feedback form
			$feedbacksent = false;

			if(qa_clicked('dofeedback'))
			{
				require_once QA_INCLUDE_DIR.'app/emails.php';
				require_once QA_INCLUDE_DIR.'util/string.php';

				$inmessage=qa_post_text('message');
				$inname=qa_post_text('name');
				$inemail=qa_post_text('email');
				$inreferer=qa_post_text('referer');

				if (!qa_check_form_security_code('feedback', qa_post_text('code')))
				{
					$pageerror = qa_lang_html('misc/form_security_again');					
				}

				else
				{
					if (empty($inmessage))
						$errors['message']=qa_lang('misc/feedback_empty');

					if ($usecaptcha)
						qa_captcha_validate_post($errors);

					if (empty($errors)) 
					{
						$subs=array(
							'^message' => $inmessage,
							'^name' => empty($inname) ? '-' : $inname,
							'^email' => empty($inemail) ? '-' : $inemail,
							'^previous' => empty($inreferer) ? '-' : $inreferer,
							'^url' => isset($userid) ? qa_path_absolute('user/'.qa_get_logged_in_handle()) : '-',
							'^ip' => qa_remote_ip_address(),
							'^browser' => @$_SERVER['HTTP_USER_AGENT'],
						);

						$successful = q2apro_send_mail(array(
								'fromemail' => qa_email_validate(@$inemail) ? $inemail : qa_opt('from_email'),
								'fromname'  => $inname,
								// 'toemail'   => $toemail,
								'senderid'	=> 1, // for log
								'touserid'  => 1,
								'toemail' => q2apro_get_sendermail(),
								'toname'    => qa_opt('booker_mailsendername'),
								// 'bcclist'   => $bcclist,
								'subject'   => qa_lang('booker_lang/contactto').' '.qa_opt('booker_mailsendername'),
								'body'      => strtr(qa_lang('emails/feedback_body'), $subs),
								'html'      => false, 
								'notrack' => true 
						));

						if($successful)
						{
							$feedbacksent = true;							
						}
						else
						{
							$pageerror=qa_lang_html('main/general_error');							
						}
						
						qa_report_event('feedback', $userid, qa_get_logged_in_handle(), qa_cookie_get(), array(
							'email' => $inemail,
							'name' => $inname,
							'message' => $inmessage,
							'previous' => $inreferer,
							'browser' => @$_SERVER['HTTP_USER_AGENT'],
						));
					}
				}
			}


			$qa_content = qa_content_prepare();
			qa_set_template('booker mbcontact');

			$qa_content['title'] = qa_lang_html('misc/feedback_title');

			$qa_content['error'] = @$pageerror;
			
			$qa_content['form'] = array(
				'tags' => 'method="post" action="'.qa_self_html().'"',

				'style' => 'tall',

				'fields' => array(
					'message' => array(
						'type' => $feedbacksent ? 'static' : '',
						'label' => qa_lang('booker_lang/contact_intro'),
						'tags' => 'name="message" id="message" placeholder="'.qa_lang('booker_lang/yourmessage').'" style="margin:10px 0;"',
						'value' => qa_html(@$inmessage),
						'rows' => 9,
						'error' => qa_html(@$errors['message']),
					),

					'name' => array(
						'type' => $feedbacksent ? 'static' : '',
						'label' => qa_lang('booker_lang/name').':', // qa_lang_html('misc/feedback_name'),
						'tags' => 'name="name" placeholder="'.qa_lang('booker_lang/yourname').'"',
						'value' => qa_html(isset($inname) ? $inname : @$userprofile['name']),
					),

					'email' => array(
						'type' => $feedbacksent ? 'static' : '',
						'label' => qa_lang('booker_lang/email').':', // qa_lang_html('misc/feedback_email'),
						'tags' => 'name="email" placeholder="'.qa_lang('booker_lang/youremail').'"',
						'value' => qa_html(isset($inemail) ? $inemail : qa_get_logged_in_email()),
						'note' => $feedbacksent ? null : qa_opt('email_privacy'),
					),
				),

				'buttons' => array(
					'send' => array(
						'label' => qa_lang('booker_lang/sendmsg_btn'),
					),
				),

				'hidden' => array(
					'dofeedback' => '1',
					'code' => qa_get_form_security_code('feedback'),
					'referer' => qa_html(isset($inreferer) ? $inreferer : @$_SERVER['HTTP_REFERER']),
				),
			);

			if ($usecaptcha && !$feedbacksent)
				qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors);


			$qa_content['focusid'] = 'message';

			if ($feedbacksent)
			{
				$qa_content['form']['ok'] = qa_lang_html('misc/feedback_sent');
				unset($qa_content['form']['buttons']);
			}

			if ($feedbacksent)
			{
				unset($qa_content['form']['fields']['message']['label']);
			}
			
			// init custom
			$qa_content['custom'] = '';
			
			// css
			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main {
					margin-bottom:100px;
				}
				#message {
					width:90%;
				}
				.qa-form-tall-button {
					display: inline-block;
					padding: 10px 20px;
					margin-top: 20px;
					font-size: 14px;
					color: #FFF;
					background: #44E;
					border: 1px solid #33E;
					border-radius: 0px;
					cursor: pointer;
				}
				.qa-form-tall-button:hover {
					background: #33E;
					box-shadow:none;
				}
				
				.qa-main {
					position:relative;
				}
				#profileimg {
					position: absolute;
					top: 98px;
					left: 550px;
				}
				#profileimg img {
					border-radius: 50%;
					margin-left:10px;
				}
				#profileimg p {
					font-size:12px !important;
					color:#555;
					margin-top:5px;
				}
				
				@media only screen and (max-width:480px) {
					#profileimg {
						display:none;
					}
				}
				
			</style>
			';

			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
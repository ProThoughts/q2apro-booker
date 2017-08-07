<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_contract
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
					'title' => 'booker Page Contract', // title of page
					'request' => 'contract', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='contract') 
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
				qa_set_template('booker contract');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker contract');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
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
			
			// if user sends checkboxes, agreements
			$postin = qa_post_text('accepted1');
			if(!empty($postin) && $postin==1)
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
			
			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker contract');
			$qa_content['title'] = qa_lang('booker_lang/contract_title');

			// init
			$qa_content['custom'] = '';
			
			$qa_content['custom'] .= '
				<p>
					'.qa_lang('booker_lang/contract_hint').'
				</p>
			';
			
			// load userdata for display frontend
			$user = booker_getfulluserdata($userid);
			$contractorprice = (float)($user['bookingprice']);
			$isavailable = $user['available'];
			$portfolio = $user['portfolio'];
			$skype = $user['skype'];
			$payment = $user['payment'];
			$realname = $user['realname'];
			$birthdate = $user['birthdate'];
			$address = $user['address'];
			$phone = $user['phone'];
			$service = $user['service'];
			$approveflag = $user['approved']; // account approved by admin
			
/*
				<p>
					'.qa_lang('booker_lang/contract_line_5').' '.number_format(booker_getcommission($userid)*100,0,',','.').' %.
				</p>
*/
			$qa_content['custom'] .= '
				<h3>
					'.qa_lang('booker_lang/contract_line_1').':
				</h3>
				<p>
					'.qa_opt('booker_operator').'
				</p>
				
				<h3>
					'.qa_lang('booker_lang/contract_line_2').': 
				</h3>
				<p>
					'.$realname.'
					<br />
					'.str_replace(',', '<br />', $address).'
					<br />
				</p>

				<h3 style="margin-top:40px;">
					'.qa_lang('booker_lang/contract_line_3').':
				</h3>
				
				<p>
					'.
					strtr( qa_lang('booker_lang/contract_line_4'), array( 
					'^1' => '<a target="_blank" href="'.qa_path('termscon').'">',
					'^2' => '</a>'
					)).
					'
				</p>
				<p>
					'.qa_lang('booker_lang/contract_line_6').'
				</p>
				<p>
					'.qa_lang('booker_lang/date').': '.date(qa_lang('booker_lang/date_format_php')).'
				</p>
				
			<form action="" method="post">
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
				
				<p style="margin-top:30px;">
					'.qa_lang('booker_lang/contract_line_11').'
				</p>
				<p>
					<button type="submit" class="defaultbutton senddatabtn">'.qa_lang('booker_lang/conclude_contract').'</button>
					<img class="qa-waiting" src="'.$this->urltoroot.'images/loader.gif" alt="Loader" />
				</p>
			</form>
			';
			
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
			
			// check if all checkmarks are ticked
			if(!$('#accepted1').prop('checked') || !$('#accepted2').prop('checked') || !$('#accepted3').prop('checked'))
			{
				alert('".qa_lang('booker_lang/contract_line_12')."');
				return;
			}
			// show loading indicator
			$('.qa-waiting').show();
			
			$('form').submit();
		});

	}); // end jquery ready

</script>
			";
			
			$qa_content['custom'] .= '
			<style type="text/css">
				h1 {
					color:#333;
					font-size:21px;
					margin:10px 0 20px 0;
				}
				.qa-main h3 {
					font-size:17px;
					font-weight:normal;
					margin-top:30px;
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
				.tfoot {
					background:transparent;
					border:1px solid transparent !important;
					text-align: right;
				}
				
				#portfolio {
					width:100%;
					max-width:500px;
					height:auto;
					margin-bottom:20px;
				}
				.conavailable {
					font-weight:normal;
				}
				.serviceflagwrap label {
					display:inline-block;
					width:90%;
					margin:0 0 10px 0;
				}
				#kmrate {
					width:70px;
					text-align:right;
				}
				#bookingprice {
					text-align:right;
				}
				.kmratehint {
					margin-left:30px;
					color:#888;
				}
				
				/* smartphones */
				@media only screen and (max-width:480px) {
					.qa-main {
						width:95%;
					}
					.profiledit {
						margin-top:50px;
					}
					#service {
						width:95%;
					}
				}

			</style>';
			
			return $qa_content;
			
		} // END process_request
		
	}; // END booker_page_contractor
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
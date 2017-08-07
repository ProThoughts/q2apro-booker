<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_businesscard
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
					'title' => 'booker Page businesscard', // title of page
					'request' => 'businesscard', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='businesscard') 
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
				qa_set_template('booker businesscard');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();

			// only members can access
			if(empty($userid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker businesscard');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}
			
			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker businesscard');
			$qa_content['title'] = qa_lang('booker_lang/bcard_title');

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
					'.qa_lang('booker_lang/bcard_intro').'
				</p>
			';
			
			// get: birthdate, address, phone, skype, service, portfolio, bookingprice, payment, available, absent, externalcal, registered, contracted, approved, kmrate, flags
			$contractordata = booker_getfulluserdata($userid);
			$contractormail = helper_getemail($userid);
			
			$contractorskype = '';
			if(!empty($contractordata['skype']))
			{
				$contractorskype = '
					<p>
						<span><i class="fa fa-skype" title="'.qa_lang('booker_lang/skype').'"></i></span>
						<span>'.$contractordata['skype'].'</span>
					</p>
				';
			}
			
			$contractorurl = q2apro_site_url().'u/'.$userid;
			
			// get avatarblobid 
			$avatarblobid = qa_db_read_one_value(
								qa_db_query_sub('SELECT avatarblobid FROM ^users 
													WHERE userid = #', 
													$userid), true);
			$contractoravatar = './?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size=230';
			if(isset($avatarblobid))
			{
				$contractoravatar = './?qa=image&qa_blobid='.$avatarblobid.'&qa_size=230';
			}

			$qa_content['custom'] .= '
			<div class="bcard-wrap">
				<h2>
					'.qa_lang('booker_lang/bcard_design').' A: 
				</h2>
				<div class="bcard-box">
					<div class="bcard-avatar">
					</div>
					<div class="bcard-preview">
						<div class="bcard-data">
							<p class="bcard-name">
								'.$contractordata['realname'].'
							</p>
							<div class="bcard-table">
								<p>
									<span><i class="fa fa-map-marker" title="'.qa_lang('booker_lang/address').'"></i></span>
									<span>'.$contractordata['address'].'</span>
								</p>
								<p>
									<span><i class="fa fa-phone" title="'.qa_lang('booker_lang/telephone').'"></i></span>
									<span>'.$contractordata['phone'].'</span>
								</p>
								<p>
									<span><i class="fa fa-envelope" title="'.qa_lang('booker_lang/email').'"></i></span>
									<span>'.$contractormail.'</span>
								</p>
								'.$contractorskype.'
								<p>
									<span><i class="fa fa-globe" title="'.qa_lang('booker_lang/website').'"></i></span>
									<span>'.$contractorurl.'</span>
								</p>
							</div> <!-- bcard-table -->
						</div>
					</div> <!-- bcard-preview -->
				</div> <!-- bcard-box -->
			</div> <!-- bcard-wrap -->
			';
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main {
					margin-bottom:100px;
					min-height:610px;
				}
				.qa-main h1 {
					margin-bottom:20px;
				}
				.qa-main h2 {
					margin-top:50px;
					font-size:18px;
				}
				.qa-main p {
					margin-top:5px;
				}
				
				.bcard-box {
					display:block;
					width:440px;
					height:225px;
					padding:0 30px;
					border: 2px solid rgba(241,241,241,0.8);
					box-shadow: 4px 4px 4px 3px rgba(100,100,100,0.3);
					background:#FFF;
					font-family:Calibri;
					vertical-align:top;
					position: relative;
					overflow: hidden;
				}
				.bcard-avatar {
					display:block;
					width: 230px;
					height: 230px;
					position:absolute;
					right: -40px;
					top: 0px;
					/*margin: 15px 30px 20px 0;*/
					background: url('.$contractoravatar.') center no-repeat;
					background-size: cover;
					/*border-radius: 50%;*/
					border-top-left-radius: 50%;
					border-bottom-left-radius: 50%;
					box-shadow: 0 0 0 7px rgba(241,241,241,.8);
				}
				.bcard-wrap-v2 .bcard-avatar {
					border-radius: 0;
				}
				/* vertical center */
				.bcard-preview {
					position: relative;
					top: 50%;
					transform: translateY(-50%);
				}
				.bcard-data {
					max-width: 210px;
				}
				.bcard-table {
					display:table;
				}
				.bcard-table p {
					display:table-row;
				}
				.bcard-table p span {
					display:table-cell;
					padding-bottom:10px;
				}
				.bcard-table p span:first-child {
					width:20px;
				}
				.bcard-table, 
				.bcard-table .fa {
					color:#555;
				}
				.bcard-name {
					font-size:20px;
					margin:0 0 10px 0;
					color:#DD1311;
				}
				
				.bookbtnexampleimg {
					border:1px solid #EEE;
				}
				#buttonlabel {
					border:1px solid #99F;
				}
				
			@media only screen and (max-width: 480px) {
				.bcard-box {
					width:100%;
				}
			}
			</style>
			';
			
			// jquery
			$qa_content['custom'] .= "
			<script>
			$(document).ready(function()
			{
				$('#buttonlabel').on('input', function() {
					$('.bookmelabel').text( $(this).val() );
				});
				
				$('.bookbtnexampleimg').click( function() { 
					$('#buttonlabel').focus();
					$('html,body').animate({
					   scrollTop: $('#buttonlabelstart').offset().top - 20
					});
				});
				
				/* duplicate and add css class */
				$('.bcard-wrap').clone().insertAfter('.bcard-wrap:first');
				$('.bcard-wrap:last').addClass('bcard-wrap-v2');
				$('.bcard-wrap:last h2').text('".qa_lang('booker_lang/bcard_design')." B:');
			});
			</script>
			";

			return $qa_content;
			
		} // end process_request

	};
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
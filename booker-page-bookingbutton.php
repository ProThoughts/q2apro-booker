<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_bookingbutton
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
					'title' => 'booker Page bookingbutton', // title of page
					'request' => 'bookingbutton', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='bookingbutton') 
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
				qa_set_template('booker bookingbutton');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();

			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker bookingbutton');
			$qa_content['title'] = qa_lang('booker_lang/bookbtn_title');

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
			
			// warn need registration
			if(empty($userid)) 
			{
				$qa_content['custom'] .= '<p class="qa-error">
											'.qa_insert_login_links(qa_lang('booker_lang/needregisterlogin')).'
										</p>';
				$userid = 0;
			}
			
			$qa_content['custom'] .= '
				<p>
					'.qa_lang('booker_lang/bookbtn_intro').'
				</p>
			';
			
			$qa_content['custom'] .= '
				<p>
					'.qa_lang('booker_lang/bookme_example').':
				</p>
				<img class="bookbtnexampleimg" src="'.$this->urltoroot.'images/bookingbutton-example-'.qa_opt('booker_language').'.png" alt="bookingbutton example" />
			';
			
			$qa_content['custom'] .= '
			<p id="buttonlabelstart" style="margin-top:50px;">
				'.qa_lang('booker_lang/bookbtn_label').': 
			</p>
			<p>
				<input id="buttonlabel" type="text" value="'.qa_lang('booker_lang/bookmenow_btn').'" />
			</p>
			';
			
			$qa_content['custom'] .= '
				<h3>
					'.qa_lang('booker_lang/buttonstyle').' A: 
				</h3>
				<a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="background:#3498DB;border-radius:28px;color:#FFF;font-size:15px;padding:10px 20px;text-decoration:none;"><span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span></a>
				<p>
					<span class="copyme">&lt;a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="background:#3498DB;border-radius:28px;color:#FFF;font-size:15px;padding:10px 20px;text-decoration:none;" &gt;<span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span>&lt;/a&gt;
				</p>

				<h3>
					'.qa_lang('booker_lang/buttonstyle').' B: 
				</h3>
				<a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="background:#3498DB;border-radius:3px;color:#FFF;font-size:15px;padding:10px 15px;text-decoration:none;"><span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span></a>
				<p>
					<span class="copyme">&lt;a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="background:#3498DB;border-radius:3px;color:#FFF;font-size:15px;padding:10px 15px;text-decoration:none;" &gt;<span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span>&lt;/a&gt;
				</p>

				<h3>
					'.qa_lang('booker_lang/buttonstyle').' C: 
				</h3>
				<a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="padding:10px 15px;border-radius:2px 2px;border:1px solid #BBB;background:#EEE;color:#333;text-decoration:none;text-align:center;text-shadow:0px 1px 1px #FFF;"><span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span></a>
				<p>
					<span class="copyme">&lt;a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="padding:10px 15px;border-radius:2px 2px;border:1px solid #BBB;background:#EEE;color:#333;text-decoration:none;text-align:center;text-shadow:0px 1px 1px #FFF;" &gt;<span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span>&lt;/a&gt;
				</p>
				
				<h3>
					'.qa_lang('booker_lang/buttonstyle').' D:
				</h3>
				<a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="padding:10px 15px;color:#FFF;background:#44E;border:1px solid #33E;text-decoration:none;"><span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span></a>

				<p>
					<span class="copyme">&lt;a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="padding:10px 15px;color:#FFF;background:#44E;border:1px solid #33E;text-decoration:none;" &gt;<span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span>&lt;/a&gt;
				</p>
				
				<h3>
					'.qa_lang('booker_lang/buttonstyle').' E:
				</h3>
				<a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="padding:10px 12px;color:#FFF;background:#444;border-radius:4px;text-decoration:none;"><span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span></a>

				<p>
					<span class="copyme">&lt;a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="padding:10px 12px;color:#FFF;background:#444;border-radius:4px;text-decoration:none;" &gt;<span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span>&lt;/a&gt;
				</p>
				
				<h3>
					'.qa_lang('booker_lang/simplelink').':
				</h3>
				<a href="'.q2apro_site_url().'booking?contractorid='.$userid.'" style="text-decoration:underline;"><span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span></a>
				<p>
					<span class="copyme">&lt;a href="'.q2apro_site_url().'booking?contractorid='.$userid.'"&gt;<span class="bookmelabel">'.qa_lang('booker_lang/bookmenow_btn').'</span>&lt;/a&gt;
				</p>
				

				
				<!--
				<h3>
					Angebot verlinken
				</h3>
				<p>
					Sie können auch einzelne Angebote verlinken, hierzu benötigen Sie nur die Angebots-ID.
				</p>
				-->
			';
			
			
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main {
					margin-bottom:100px;
				}
				.qa-main h1 {
					margin-bottom:20px;
				}
				.qa-main h3 {
					display:inline-block;
					margin:75px 10px 20px 0;
					font-size:19px;
				}
				.qa-main p {
					margin-top:5px;
				}
				.bookbtnexampleimg {
					border:1px solid #EEE;
				}
				.copyme {
					background:#EEE;
					padding:5px;
					display: inline-block;
					color:#444;
				}
				#buttonlabel {
					border:1px solid #99F;
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
				
				$('.copyme').click( function() { 
					$(this).select(); 
				});
			});
			</script>
			";

			return $qa_content;
			
		} // end process_request

	};
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
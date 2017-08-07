<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_premium
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
					'title' => 'booker Page premium', // title of page
					'request' => 'premium', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='premium')
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
				qa_set_template('booker premium');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker premium');
			$qa_content['title'] = qa_lang('booker_lang/premium_title');

			$userid = qa_get_logged_in_userid();

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
				<p style="margin:20px 0 50px 0;">
					'.qa_lang('booker_lang/premium_intro').'
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

			// language strings multiple times used
			$lang_yourownpage = strtr( qa_lang('booker_lang/yourownpage'), array(
							'^1' => '<a href="'.qa_path('booking').'?contractorid='.$userid.'" target="_blank">',
							'^2' => '</a>'
							));
			$lang_bookcal_basic = strtr( qa_lang('booker_lang/bookcal_basic'), array(
							'^1' => '<a href="'.qa_path('booking').'?contractorid='.$userid.'#cal" target="_blank">',
							'^2' => '</a>',
							'^3' => '<a href="'.qa_path('contractorcalendar').'#showtimesetup" target="_blank">',
							'^4' => '</a>',
							'^5' => '<a href="'.qa_path('contractorcalendar').'#showexport" target="_blank">',
							'^6' => '</a>'
							));
			$lang_bookbtn_external = strtr( qa_lang('booker_lang/bookbtn_external'), array(
							'^1' => '<a href="'.qa_path('bookingbutton').'" target="_blank">',
							'^2' => '</a>'
							));
			$lang_gcal_integration = strtr( qa_lang('booker_lang/gcal_integration'), array(
							'^1' => '<a href="'.qa_path('contractorcalendar').'#showimport" target="_blank">',
							'^2' => '</a>'
							));
			$lang_sendprivatemessages = strtr( qa_lang('booker_lang/sendprivatemessages'), array(
							'^1' => '<a href="'.qa_path('mbmessages').'" target="_blank">',
							'^2' => '</a>'
							));

			// free premium for invited users
			$premiumtrial_code = "launch"; // qa_get('code');
			$premiumlink = qa_path('premiumpay').'?type=1';
			$premiumlink_vip = qa_path('premiumpay').'?type=2';
			$premiumtrial_output = '';
			if(isset($premiumtrial_code))
			{
				$premiumlink = qa_path('premiumhandler').'?type=1&code='.$premiumtrial_code;

				$premiumtrial_output = '
					<p class="premiumtrial-free">
						'.qa_lang('booker_lang/premiumtrial_promo').'
						'.qa_lang('booker_lang/premiumtrial_click').'
					</p>
					<style type"text/css">
						.premiumtrial-free {
							color:#00F;
							margin:10px 0;
						}
						.pricing_box ul.feature-pricing li:last-child {
							height:160px;
						}
					</style>
					';
			}

			// bring always to login page if not loggedin
			if(empty($userid))
			{
				$premiumlink = qa_path('login').'?to=premium';
				$premiumlink_vip = qa_path('login').'?to=premium';
			}

			$qa_content['custom'] .= '
			<div class="pricetable">

                <!-- Basic -->
				<div class="pricing_box">
					<div class="header">
                        <p>
						'.qa_lang('booker_lang/youarecurrently').'
						</p>
						<h2>
						'.qa_lang('booker_lang/member_basic').'
						</h2>
					</div>
					<ul class="feature-column column_simple">
						<li class="feature-both">
							<span>'.$lang_yourownpage.'</span>
						</li>
                        <li class="feature-both">
                            <span>'.qa_lang('booker_lang/servicedesc_page').'
                                                            <br>
                                '.qa_lang('booker_lang/servicedesc_limited').'</span>
                        </li>
                        <li class="feature-both">
							<span>'.
							    strtr( qa_lang('booker_lang/createoffer_basic'), array(
							    '^1' => '<a class="specialofferlink" href="'.qa_path('contractoroffers').'" target="_blank">',
							    '^2' => '</a>'
							    )).
							    '</span>
                        </li>
						<li class="feature-both">
							<span>'.$lang_bookcal_basic.'</span>
						</li>
						<li class="feature-both">
							<span>'.qa_lang('booker_lang/eventmanagement').'</span>
						</li>
                        <li class="feature-both">
							<span>'.$lang_bookbtn_external.'</span>
                        </li>

						<li class="gopremium flex-center">
                            <span class="go">'.qa_lang('booker_lang/gopremium').'</span>
						</li>
						<li class="gopremium flex-center">
                            <span class="go">'.qa_lang('booker_lang/gopremium').'</span>
						</li>
						<li class="gopremium flex-center">
                            <span class="go">'.qa_lang('booker_lang/gopremium').'</span>
						</li>
						<li class="gopremium flex-center">
                            <span class="go">'.qa_lang('booker_lang/gopremium').'</span>
						</li>
						<li class="gopremium flex-center">
                            <span class="go">'.qa_lang('booker_lang/gopremium').'</span>
						</li>

						<li class="gopremium govip flex-center">
                            <span class="go">'.qa_lang('booker_lang/govip').'</span>
						</li>
						<li class="gopremium govip flex-center">
                            <span class="go">'.qa_lang('booker_lang/govip').'</span>
						</li>
                    	</ul>
	                    <ul class="feature-pricing">
						<li class="feature-price price-monthly">
                            <h4>
								'.qa_lang('booker_lang/monthly_price').'
							</h4>
                            <b>
								'.qa_lang('booker_lang/free').'
							</b>
						</li>
						<li class="flex-center">
                            <span class="go">'.qa_lang('booker_lang/youarecurrently').'</span>
						</li>
					</ul>
				</div>



                <!-- Premium -->
				<div class="pricing_box">
					<div class="header">
                        <p>
							'.qa_lang('booker_lang/become').'
						</p>
						<h2>
							'.qa_lang('booker_lang/member_premium').'
						</h2>
					</div>
					<ul class="feature-column column_premium">
						<li class="feature-both">
							<span>'.$lang_yourownpage.'</span>
						</li>
                        <li class="feature-both">
                            <span>'.qa_lang('booker_lang/servicedesc_page').'
                                                            <br>
                                '.qa_lang('booker_lang/servicedesc_unlimited').'</span>
                        </li>
                        <li class="feature-both">
							<span>'.
							    strtr( qa_lang('booker_lang/createoffer_premium'), array(
							    '^1' => '<a class="specialofferlink" href="'.qa_path('contractoroffers').'" target="_blank">',
							    '^2' => '</a>'
							    )).
							    '</span>
                        </li>
						<li class="feature-both">
							<span>'.$lang_bookcal_basic.'</span>
						</li>
						<li class="feature-both">
							<span>'.qa_lang('booker_lang/eventmanagement').'</span>
						</li>
                        <li class="feature-both">
                            <span>'.$lang_bookbtn_external.'</span>
                        </li>


						<li class="feature-both feature-premium">
							<span>'.qa_lang('booker_lang/subdomain_desc').'</span>
						</li>
						<li class="feature-both feature-premium">
							<span>'.$lang_gcal_integration.'</span>
						</li>'.
                        /*
						<li class="feature-both feature-premium">
							<span>'.qa_lang('booker_lang/payment_integration').'</span>
						</li>
                        */

						'<li class="feature-both feature-premium">
							<span>'.$lang_sendprivatemessages.'</span>
						</li>
                        		<li class="feature-both feature-premium">
				    			<span>'.qa_lang('booker_lang/vcardcontact').'</span>
						</li>
						<li class="feature-both feature-premium">
							<span>'.qa_lang('booker_lang/adfree').'</span>
						</li>

						<li class="gopremium govip flex-center">
				            <span class="go">'.qa_lang('booker_lang/govip').'</span>
						</li>
						<li class="gopremium govip flex-center">
				            <span class="go">'.qa_lang('booker_lang/govip').'</span>
						</li>
					</ul>
	                    <ul class="feature-pricing">
						<li class="feature-price price-monthly">
                            <h4>
								'.qa_lang('booker_lang/monthly_price').'
							</h4>
                            		<b>
								'.qa_opt('booker_pricepremium').' '.qa_lang('booker_lang/eurospermonth').'
							</b>
						</li>
						<li>
							<a class="btn btn-large btn-block btn-primary" href="'.$premiumlink.'&type=1">
								'.qa_lang('booker_lang/become_premiummember').'
							</a>
						   '.$premiumtrial_output.'
						</li>
					</ul>
				</div>



                <!-- VIP -->
				<div class="pricing_box">
					<div class="header">
                        <p>
							'.qa_lang('booker_lang/become').'
						</p>
						<h2>
							'.qa_lang('booker_lang/member_vip').'
						</h2>
					</div>
					<ul class="feature-column column_premium">
						<li class="feature-both">
                            <span>'.$lang_yourownpage.'</span>
						</li>
                        <li class="feature-both">
                            <span>'.qa_lang('booker_lang/servicedesc_page').'
                                                            <br>
                                '.qa_lang('booker_lang/servicedesc_unlimited').'</span>
                        </li>
                        <li class="feature-both">
 							<span>'.
                                strtr( qa_lang('booker_lang/createoffer_vip'), array(
                                '^1' => '<a class="specialofferlink" href="'.qa_path('contractoroffers').'" target="_blank">',
                                '^2' => '</a>'
                                )).
                            '</span>
                        </li>
						<li class="feature-both">
							<span>'.$lang_bookcal_basic.'</span>
						</li>
						<li class="feature-both">
							<span>'.qa_lang('booker_lang/eventmanagement').'</span>
						</li>
                        <li class="feature-both">
                            <span>'.$lang_bookbtn_external.'</span>
                        </li>


						<li class="feature-both feature-premium">
							<span>'.qa_lang('booker_lang/subdomain_desc').'</span>
						</li>
						<li class="feature-both feature-premium">
							<span>'.$lang_gcal_integration.'</span>
						</li>'.
                        /*
						<li class="feature-both feature-premium">
							<span>'.qa_lang('booker_lang/payment_integration').'</span>
						</li>
                        */

						'<li class="feature-both feature-premium">
							<span>'.$lang_sendprivatemessages.'</span>
						</li>
                        		<li class="feature-both feature-premium">
							<span>'.qa_lang('booker_lang/vcardcontact').'</span>
						</li>
						<li class="feature-both feature-premium">
							<span>'.qa_lang('booker_lang/adfree').'</span>
						</li>

						<li class="feature-both feature-premium feature-vip">
							<span>'.qa_lang('booker_lang/payment_integration').'</span>
						</li>
						<li class="feature-both feature-premium feature-vip">
 							<span>'.
                                strtr( qa_lang('booker_lang/getbusinesscards_desc'), array(
                                '^1' => '<a href="'.qa_path('businesscard').'">',
                                '^2' => '</a>'
                                )).
                            '</span>
						</li>
					</ul>
                    <ul class="feature-pricing">
						<li class="feature-price price-monthly">
                            <h4>
								'.qa_lang('booker_lang/monthly_price').'
							</h4>
                            <b>
								'.qa_opt('booker_pricevip').' '.qa_lang('booker_lang/eurospermonth').'
							</b>
						</li>
						<li>
                            		<a class="btn btn-large btn-block btn-primary" href="'.$premiumlink_vip.'">
                                		'.qa_lang('booker_lang/become_vipmember').'
                            		</a>
						</li>
					</ul>
				</div>

			</div>
			';

			// only members can access
			/*
			if(empty($userid))
			{
				$qa_content['custom'] .= booker_loginform_output($request);
				$qa_content['custom'] .= '
<style type="text/css">
	.btn.btn-block {
		display:none;
	}
</style>
				';
			}
			*/

			$qa_content['custom'] .= '

            <div class="centered" style="margin-bottom:20px;">
				'.qa_lang('booker_lang/moneyback_desc').'
            </div>

            <div class="centered" style="margin-bottom:40px;">
		  	'.qa_lang('booker_lang/member_payment_hint').'
            </div>

            <br/>
            <br/>

            <div class="premium-faq">
                <h2>
				'.qa_lang('booker_lang/faq_head').'
			</h2>

                <div class="premium-faq-column">

                    <div class="premium-faq-item">
                        <h3>
						'.qa_lang('booker_lang/faq_01_h1').'
					</h3>
                        <p>
						'.qa_lang('booker_lang/faq_01_content').'
					</p>
		          </div>

										<div class="premium-faq-item">
                        <h3>
													'.qa_lang('booker_lang/faq_02_h1').'
												</h3>
                        <p>
													'.qa_lang('booker_lang/faq_02_content').'
												</p>
                    </div>

										<div class="premium-faq-item">
                        <h3>
													'.qa_lang('booker_lang/faq_03_h1').'
												</h3>
                        <p>
													'.qa_lang('booker_lang/faq_03_content').'
												</p>
                    </div>
                  </div>

									<div class="premium-faq-column">
										<div class="premium-faq-item">
                        <h3>
													'.qa_lang('booker_lang/faq_04_h1').'
												</h3>
                        <p>
													'.qa_lang('booker_lang/faq_04_content').'
												</p>
                    </div>

										<div class="premium-faq-item">
                        <h3>
													'.qa_lang('booker_lang/faq_05_h1').'
												</h3>
                        <p>
													'.qa_lang('booker_lang/faq_05_content').'
												</p>
                    </div>

										<div class="premium-faq-item">
                        <h3>
													'.qa_lang('booker_lang/faq_06_h1').'
												</h3>
                        <p>
													'.qa_lang('booker_lang/faq_06_content').'
												</p>
                    </div>

                </div>
            </div>


			';


			// jquery
			$qa_content['custom'] .= "
			<script>
			$(document).ready(function()
			{
				$('#buttonlabel').on('input', function() {
					$('.bookmelabel').text( $(this).val() );
				});

				$('.printpage').click( function() {
					window.print();
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

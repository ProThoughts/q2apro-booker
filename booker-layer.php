<?php
/*
	Plugin Name: BOOKER
*/

	class qa_html_theme_layer extends qa_html_theme_base
	{

		/*public function doctype()
		{
			$userid = qa_get_logged_in_userid();
			$this->cookieid = isset($userid) ? qa_cookie_get() : qa_cookie_get_create();
			qa_html_theme_base::doctype();
		}
		*/

		var $pluginpages = array(
			'admincalendar',
			'admincontractors',
			'admineventedit',
			'adminlogview',
			'adminmessages',
			'adminratings',
			'adminschedule',
			'adminsearchtrack',
			'bid',
			'booking',
			'bookingbutton',
			'clientcalendar',
			'clientprofile',
			'clientratings',
			'clientschedule',
			'contract',
			'contractorbalance',
			'contractorcalendar',
			'contractorinfo',
			'contractorlist',
			'contractoroffers',
			'contractorprofile',
			'contractorratings',
			'contractorschedule',
			'contractorweek',
			'eventhistory',
			'eventmanager',
			'mbcontact',
			'mbmessages',
			'offercreate',
			'pay',
			'requestcreate',
			'requestlist',
			'userprofile',
			'userrecommend'
		);

		var $shownamepages = array(
			'clientcalendar',
			'clientprofile',
			'clientratings',
			'clientschedule',
			'contractorbalance',
			'contractorcalendar',
			'contractorinfo',
			'contractoroffers',
			'contractorprofile',
			'contractorratings',
			'contractorschedule',
			'contractorweek',
			'userprofile',
		);

		function initialize()
		{
			$userid = qa_get_logged_in_userid();

			if($this->template=='booker contractorlist')
			{
				$this->content['description'] = qa_lang('booker_lang/meta_description');
			}

			// NAVIGATION: add contractor link to main menu
			if(isset($this->content['navigation']['main']))
			{
				// NAV item "Interview" for Kvanto
				if(qa_opt('site_title')=='Kvanto')
				{
					$this->content['navigation']['main']['interview'] = array(
						'url' => qa_path('tag').'/'.qa_lang('booker_lang/interviewtag'),
						'label' => '<i class="icn icon-people"></i> '.qa_lang('booker_lang/interviewlabel')
					);
					// change order: bring "interview" to front
					$hash = $this->content['navigation']['main'];
					if(isset($hash))
					{
						$hash = array('interview' => $hash['interview']) + $hash;
						$this->content['navigation']['main'] = $hash;
					}
				}

				/*** TEMP OFF
				// NAV item "requests"
				$this->content['navigation']['main']['requests'] = array(
					'url' => qa_path('requestlist'),
					//'label' => '<i class="fa fa-newspaper-o fa-lg"></i> '.qa_lang('booker_lang/requests')
					'label' => '<i class="icn icon-notes"></i> '.qa_lang('booker_lang/ask_service')
				);
				// change order: bring "requests" to front
				$hash = $this->content['navigation']['main'];
				if(isset($hash))
				{
					// $hash = array('ask' => $hash['ask']) + $hash;
					// $this->content['navigation']['main'] = $hash;
					$hash = array('requests' => $hash['requests']) + $hash;
					$this->content['navigation']['main'] = $hash;
				}
				*/

				// NAV "Find Service"
				$this->content['navigation']['main']['services'] = array(
					'url' => qa_path('contractorlist'),
					// 'label' => '<i class="fa fa-cogs fa-lg"></i> '.qa_lang('booker_lang/nav_main_contractor') // 'Service buchen', 'Anbieter'
					'label' => '<i class="icn icon-card-user"></i> '.qa_lang('booker_lang/nav_main_contractor') // 'Service buchen', 'Anbieter'
				);
				// change order: bring "services" to front
				$hash = $this->content['navigation']['main'];
				if(isset($hash))
				{
					// $hash = array('ask' => $hash['ask']) + $hash;
					// $this->content['navigation']['main'] = $hash;
					$hash = array('services' => $hash['services']) + $hash;
					$this->content['navigation']['main'] = $hash;
				}

				// rename frage-stellen to forum
				if(isset($this->content['navigation']['main']['questions']))
				{
					$this->content['navigation']['main']['questions']['label'] = '<i class="icn icon-chat-1"></i> '.qa_lang('booker_lang/forum');
				}

				// add to navigation: for contractor and for client
				/*
				if(isset($userid))
				{
					$this->content['navigation']['main']['myaccount'] = array(
						'url' => qa_path('userprofile'),
						//'label' => '<i class="fa fa-user fa-lg" style="margin-right:2px;"></i> '.qa_lang('booker_lang/myaccount') // identifier for dropdown
						'label' => '<i class="icn icon-user" style="margin-right:2px;"></i> '.qa_lang('booker_lang/myaccount') // identifier for dropdown
					);
				}
				*/
			}

			if($this->template=='account')
			{
				unset($this->content['form_profile']['fields']['avatar']);
				unset($this->content['form_profile']['fields']['type']);
				unset($this->content['form_profile']['fields']['mailings']);
				// var_dump($this->content['form_profile']['fields']);
			}

			// default call
			qa_html_theme_base::initialize();
		}

		function head_script()
		{
			// load default scripts
			qa_html_theme_base::head_script();

			// var_dump($this->content);

			if(qa_opt('booker_enabled'))
			{

				// button style below user avatar, question page
				if($this->template == 'question' || $this->template == 'user')
				{
					$this->output('
						<style type="text/css">
							.qa-template-question .bookingcontainer {
								display:block;
							}
							.qa-template-user .bookingcontainer {
								display:block;
								margin:0;
								float:right;
							}

							.bookingbtn {
								display:inline-block;
								padding: 7px 15px;
								overflow: visible;
								margin: 10px 0 10px 0;
								font-size: 12px;
								white-space: nowrap;
								cursor: pointer;
								outline: 0px none;
								border-radius: 0.2em;
								color: #FFF !important;
								text-shadow: none;
								border: 1px solid #33E; /*#FAA*/
								background: #44E; /*#F77*/
							}
							.qa-template-user .bookingbtn {
								margin:0;
								float:right;
							}
							.bookingbtn:hover {
								background: #33E;
								text-decoration:none;
							}


							/* smartphones */
							@media only screen and (max-width:480px) {
								.qa-template-user .bookingcontainer {
									float:none;
								}
							}

						</style>
					');
				}

				// load fullcalendar scripts
				$loadjspages = array('adminschedule', 'admincalendar', 'booking', 'contractorschedule', 'contractorcalendar', 'clientcalendar', 'contractorweek');
				if(in_array($this->request, $loadjspages))
				{
					// $this->urltoroot
					$this->output('<link rel="stylesheet" type="text/css" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/fullcalendar-3.0.0/fullcalendar.min.css">');
					$this->output('<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/momentjs-2.13.0/moment.min.js"></script>');
					$this->output('<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/fullcalendar-3.0.0/fullcalendar.min.js"></script>');
					$this->output('<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/fullcalendar-3.0.0/locale/'.qa_opt('booker_language').'.js"></script>');
					$this->output('<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/fullcalendar-3.0.0/gcal.js"></script>');
				}

				// load fullcalendar scripts
				/*
				$loadonlymomentjs = array('requestcreate');
				if(in_array($this->request, $loadonlymomentjs))
				{
					$this->output('<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/momentjs/moment.min.2.9.0.js"></script>');
				}
				*/

				// load fullcalendar scripts
				$loadinputmask = array('requestcreate');
				if(in_array($this->request, $loadinputmask))
				{
					$this->output('<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/inputmask/jquery.inputmask.bundle.min.js"></script>');
				}

				/*
				$loadcsspages = array(
					'admincalendar',
					'admincontractors',
					'admineventedit',
					'adminmessages',
					'adminpayments',
					'adminratings',
					'adminschedule',
					'adminsearchtrack',
					'booking',
					'clientcalendar',
					'clientprofile',
					'clientratings',
					'clientschedule',
					'contract',
					'contractorbalance',
					'contractorcalendar',
					'contractorinfo',
					'contractorlist',
					'contractoroffers',
					'contractorprofile',
					'contractorratings',
					'contractorschedule',
					'contractorweek',
					'eventhistory',
					'eventmanager',
					'mbcontact',
					'mbmessages',
					'offercreate',
					'pay',
					'userprofile',
					'userrecommend'
					);
				if(in_array($this->request, $loadcsspages))
				{
				*/

				// always load, needed on startpage too
				$this->output('
					<link rel="stylesheet" type="text/css" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/styles.css?v=0.0.10" >
					<link rel="stylesheet" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/font-awesome-4.6.3/css/font-awesome.min.css">
				');

				// load rangeslider on certain pages
				$loadjspages = array('adminschedule', 'admincalendar', 'booking', 'contractorschedule', 'contractorcalendar', 'clientcalendar', 'contractorweek');
				/*
				if($this->request == 'clientdeposit')
				{
					$this->output('<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/rangeslider.min.js"></script>');
					$this->output('<link rel="stylesheet" type="text/css" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/rangeslider.css">');
				}
				*/

				// https://github.com/antennaio/jquery-bar-rating
				if($this->request == 'clientratings')
				{
					$this->output('
						<script src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/bar-rating-1.2.0/jquery.barrating.min.js" type="text/javascript"></script>
					');
				}

				// lightbox plugin to open youtube videos in same window
				if($this->request == 'contractorlist')
				{
					$this->output('
						<script src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/venobox/venobox.min.js" type="text/javascript"></script>
						<link rel="stylesheet" type="text/css" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/venobox/venobox.css">
					');
				}

				// load font-awesome on certain pages
				/*
				$loadfontpages = array('adminschedule', 'admincalendar', 'adminmessages', 'adminpayments', 'adminratings', 'adminclients', 'admincontractors', 'adminoffers', 'adminsearchtrack', 'contractoroffers');
				if(in_array($this->request, $loadfontpages))
				{
				}
				*/


				// Google Adwords conversion tracking
				/*
				if($this->template == 'register')
				{
					$this->output('
						<!-- Adwords Code for Register Page Conversion Page -->
						<script type="text/javascript">
						var google_conversion_id = 1024906094;
						var google_conversion_language = "en";
						var google_conversion_format = "3";
						var google_conversion_color = "ffffff";
						var google_conversion_label = "eiSJCNry62EQ7qbb6AM";
						var google_remarketing_only = false;
						</script>
						<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
						</script>
						<noscript>
						<div style="display:inline;">
						<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/1024906094/?label=eiSJCNry62EQ7qbb6AM&amp;guid=ON&amp;script=0"/>
						</div>
						</noscript>
					');
				}
				*/

			} // end enabled

			// load slicknav for mobiles

            /*
			if(qa_is_mobile_probably())
			{
				$this->output('
					<script src="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/slicknav/jquery.slicknav.min.js" type="text/javascript"></script>
					<link rel="stylesheet" type="text/css" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'css-js/slicknav/slicknav.min.css">
				');
				$this->output("
					<script>
					$(document).ready(function()
					{
						// detach userprofile dropdown and attach to nav-main-list, need to add a tag to show up in mobile menu
						$('.user-nav-avatar-holder').prepend('<a class=\"qa-nav-main-link\" href=\"#\">".qa_lang('booker_lang/profile')."</a>');
						// remove profile image
						$('.user-nav-avatar-holder img').remove();
						// remove CSS styles
						var elem = $('.user-nav-avatar-holder').detach().removeClass('user-nav-avatar-holder').addClass('qa-nav-main-item');
						$('.qa-nav-main-list').append(elem);

						// detach logout link to attach to slickmenu, also remove CSS styles
							//var elem = $('.qa-nav-user-logout').detach().removeClass('qa-nav-user-logout');
							//$('.qa-nav-main-list').append(elem);

						// create mobile menu
						$('.qa-nav-main-list').slicknav({
							label: '',
							// brand: '',
							duration: 0,
							prependTo: '.topbar',
							init: slickbuilt,
						});
						// remove video and programme links in menu for mobile users
						//if($('.slicknav_menu').is(':visible')) {
						//	$('#menu #nav li:nth-child(1) a').attr('href', '');
						//	$('#menu #nav li:nth-child(2) a').attr('href', '');
						//	$('#menu #nav li:nth-child(3) a').attr('href', '');
						//}
						function slickbuilt()
						{
							// if( $('.slicknav_menu').is(':visible') ) {
							// clone login button and searchbar and bring it into slickmenu
							//$('#header #searchform').clone().appendTo('.slicknav_menu');
							//$('.slicknav_menu #searchform').click(function() {
							//	$('.slicknav_menu #searchfield').animate({'width':'143px'}, 300);
							//})
						}
					});
					</script>
				");
				$this->output('
				<style type="text/css">
					.slicknav_menu {
						display:none;
					}
					@media screen and (max-width: 480px) {
						.qa-nav-main, .qa-nav-main-list {
							display:none;
						}
						.qa-nav-user-logout {
							display:none;
						}
						.topbar {
						}
						.qa-logo img {
							max-width:90px;
							margin-top:4px;
						}
						.qa-nav-user-list {
							margin-right:0;
						}
						.user-nav-avatar-holder {
							display:inline-block;
						}
						.slicknav_menu {
							display: block;
						}
						.qa-header {
							display: block;
							width:85%;
							background:#111;
							position: absolute;
							right: 0;
							top: 7px;
							height:30px;
						}
						.qa-search-field {
							width:30px;
						}
						.qa-logged-in {
							display:inline-block;
						}
						.qa-nav-user {
							width:150px;
						}
						.qa-nav-main-item {
							margin:0;
						}
						.slicknav_nav .qa-nav-user-item {
							margin:0;
						}
						.slicknav_btn {
							float: left;
						}
						.slicknav_menu {
							background: #111;
						}
						.slicknav_nav li {
							display: block;
							padding: 0 !important;
						}
						.slicknav_nav li:hover, .slicknav_nav .slicknav_row:hover, .slicknav_nav a:hover {
							background: #333;
							color: #fff;
						}
						.dropdown-menu li a:hover {
							background: #333;
							color: #fff;
							border-radius:none;
						}
						.slicknav_nav a {
							padding: 10px !important;
							margin: 0 !important;
							text-decoration: none !important;
							color: #fff;
						}
						.slicknav_nav .slicknav_row {
							padding:0;
							margin:0;
						}
						.slicknav_btn, .slicknav_nav .slicknav_row:hover, .slicknav_nav a:hover {
							border-radius:none;
						}

					}
				</style>
				');
			}
            */

		} // end head_script

		// add signin logo to login label in main menu
		public function nav_link($navlink, $class)
		{
			if(isset($navlink['label']))
			{
				if($navlink['label']==qa_lang('main/nav_login'))
				{
					//$navlink['label'] = '<i class="fa fa-sign-in"></i> '.$navlink['label'];
					//$navlink['label'] = $navlink['label'];
					$navlink['label'] = qa_lang('booker_lang/nav_login');
				}
			}
			// default
			qa_html_theme_base::nav_link($navlink, $class);
		}

		public function nav_list($navigation, $class, $level=null)
		{
			if($class=='nav-user')
			{
				$fakebook_button = '
					<a class="fbfanpage" href="https://www.facebook.com/kvanto.lt"></a>
				';
				
				// fakebook like button
				/*
				$fakebook_button = '
					<div id="fb-root"></div>
					<script>(function(d, s, id) {
					  var js, fjs = d.getElementsByTagName(s)[0];
					  if (d.getElementById(id)) return;
					  js = d.createElement(s); js.id = id;
					  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.8";
					  fjs.parentNode.insertBefore(js, fjs);
					}(document, \'script\', \'facebook-jssdk\'));</script>
					<div class="fb-like" data-href="https://www.facebook.com/kvanto.lt" data-layout="button_count" data-action="like" data-show-faces="false" data-share="false"></div>
				';
				*/
				
				$this->output($fakebook_button);
			}
			
			qa_html_theme_base::nav_list($navigation, $class, $level=null);
		}
	
		/*
			$this->set_context('nav_type', $navtype);
			$this->nav_list($navigation, 'nav-'.$navtype, $level);
			$this->nav_clear($navtype);
		*/

	
		// override: search button as link
		public function search()
		{
			// important
			$search = $this->content['search'];
			$userid = qa_get_logged_in_userid();

			if(isset($userid))
			{
				$output_topright = '';

				$output_topright .= '
					<div class="qa-search">
				';
				
				$username = booker_get_realname($userid);

				// if too long, only output firstname
				if(strlen($username)>13)
				{
					$username = strtok($username, " ");
				}

				$ispremium = booker_ispremium($userid);

				if(booker_iscontracted($userid))
				{
					// get booking stats
					$bookingcount = booker_get_booking_count($userid, true); // upcoming
					// $bookingamount = booker_get_accountbalance($userid, MB_EVENT_COMPLETED, 30);
					$bookingamount = booker_get_accountbalance($userid);
					$offercount = count(booker_get_offers($userid));
					$offercount_out = '
				        <a href="'.qa_path('contractoroffers').'" class="tooltipN user-info-offers" title="'.$offercount.' '.($offercount==1 ? qa_lang('booker_lang/offer') : qa_lang('booker_lang/offers')).'">
	                            <span class="nav-item-label">
	                                <span class="nav-item-accent">'.$offercount.' <i class="icn fa fa-shopping-basket fa-lg"></i></span>
	                                <!-- <span class="nav-item-title">'.($offercount==1 ? qa_lang('booker_lang/offer') : qa_lang('booker_lang/offers')).'</span>  -->
	                            </span>
	                        </a>
						';

					$output_topright .= '
                        <div class="user-info">
							<a href="'.qa_path('contractorbalance').'" class="tooltipN user-info-balance" title="'.qa_lang('booker_lang/earningbookings').'">
									<span class="nav-item-label">
										<span class="nav-item-accent">'.$bookingamount.' <i class="icn fa fa-euro fa-lg"></i></span>
										<span class="nav-item-title">'.qa_lang('booker_lang/earningbookings').'</span>
									</span>
								</a>


							<a href="'.qa_path('contractorschedule').'" class="tooltipN user-info-appts" title="'.qa_lang('booker_lang/upcomingappts').'">
									<span class="nav-item-label">
										<span class="nav-item-accent">'.$bookingcount.' <i class="icn fa fa-clock-o fa-lg"></i></span>
										<!-- <span class="nav-item-title">'.($bookingcount==1 ? qa_lang('booker_lang/appt') : qa_lang('booker_lang/appts')).'</span>  -->
									</span>
							</a>


							<a href="'.qa_path('contractorcalendar').'" class="tooltipN user-info-calendar" title="'.qa_lang('booker_lang/yourcalendar').'">
									<span class="nav-item-label">
										<span class="nav-item-accent"><i class="icn fa fa-calendar fa-lg"></i></span>
										<span class="nav-item-title">'.qa_lang('booker_lang/yourcalendar').'</span>
									</span>
								</a>
							'.$offercount_out.'
                        </div> <!-- user-info -->
					';
				}
				else
				{
					// get booking stats
					$bookingcount = booker_get_booking_count($userid, true); // upcoming

					$requestcount = count(booker_get_requests($userid, 14, false));
					$requestcount_out = '
						<a href="'.qa_path('clientrequests').'" class="tooltipN user-info-offers" title="'.qa_lang('booker_lang/recentrequests').'">
	                            <span class="nav-item-label">
	                                <span class="nav-item-accent">'.$requestcount.'</span>
	                                <span class="nav-item-title">'.($requestcount==1 ? qa_lang('booker_lang/request') : qa_lang('booker_lang/requests')).'</span>
	                            </span>
	                        	</a>
						';

					$output_topright .= '
						<div class="user-info">
							<a href="'.qa_path('clientschedule').'" class="tooltipN user-info-appts" title="'.qa_lang('booker_lang/upcomingappts').'">
									<span class="nav-item-label">
										<span class="nav-item-accent">'.$bookingcount.'</span>
										<span class="nav-item-title">'.($bookingcount==1 ? qa_lang('booker_lang/appt') : qa_lang('booker_lang/appts')).'</span>
									</span>
							</a>

							<a href="'.qa_path('clientcalendar').'" class="tooltipN user-info-calendar" title="'.qa_lang('booker_lang/yourcalendar').'">
									<span class="nav-item-label">
										<span class="nav-item-accent"><i class="icn fa fa-calendar fa-lg"></i></span>
										<!-- <span class="nav-item-title">'.qa_lang('booker_lang/yourcalendar').'</span> -->
									</span>
									</a>
							'.$requestcount_out.'
                        </div>
					';
				}


				$ispremium = booker_ispremium($userid);
				$premium_icon = $ispremium? '<i class="icn icn-premium fa fa-star fa-2x"></i>' : '';
				$premium_class = $ispremium? 'user-premium' : '';

				$output_topright .= '
                    <div class="dropdown-wrapper">
					<a href="'.qa_path('userprofile').'" class="'.$premium_class.'" title="'.qa_lang('booker_lang/myaccount').'" style="padding: 5px 10px 15px;">
						<i class="icn icon-user fa-2x" style="opacity:0.8;margin-top:-6px;"></i>'.$premium_icon.'
					</a>
				';


				if(booker_iscontracted($userid))
				{
					// output premium link
					$becomepremium = '';
					if(!$ispremium)
					{
						$becomepremium = '
						<li class="becomepremium">
							<a href="'.qa_path('premium').'">'.qa_lang('booker_lang/become_premium').'</a>
						</li>
						';
					}

					$output_topright .= '
					<ul class="dropdown-menu">
						<li>
							<a href="'.qa_path('userprofile').'">
								'.qa_lang('booker_lang/profile').' <span style="font-size:12px;color:#AAA;">'.$username.'</span>
							</a>
						</li>
						'.$becomepremium.'
						<li><a href="'.qa_path('contractoroffers').'">'.qa_lang('booker_lang/offers').'</a></li>
						<li><a href="'.qa_path('contractorschedule').'">'.qa_lang('booker_lang/appts').'</a></li>
						<li><a href="'.qa_path('contractorcalendar').'">'.qa_lang('booker_lang/calendar').'</a></li>
						<li><a href="'.qa_path('contractorratings').'">'.qa_lang('booker_lang/ratings').'</a></li>
						<li><a href="'.qa_path('contractorbalance').'">'.qa_lang('booker_lang/menubtn_con_balance').'</a></li>
						<li><a href="'.qa_path('mbmessages').'">'.qa_lang('booker_lang/topright_msg').'</a></li>
						<li><a href="'.qa_path('newsletter').'">'.qa_lang('booker_lang/newsletter').'</a></li>
						<li><a href="'.qa_path('logout').'">'.qa_lang('main/nav_logout').'</a></li>
					</ul>
					';
				}
				else
				{
					$output_topright .= '
					<ul class="dropdown-menu">
						<li>
							<a href="'.qa_path('userprofile').'">
								'.qa_lang('booker_lang/profile').' <span style="font-size:12px;color:#AAA;">'.$username.'</span>
							</a>
						</li>
						<li class="becomecontractor">
							<a href="'.qa_path('userprofile').'?show=contractor">'.qa_lang('booker_lang/become_contractor').'</a>
						</li>
						<li><a href="'.qa_path('clientrequests').'">'.qa_lang('booker_lang/requests').'</a></li>
						<li><a href="'.qa_path('clientschedule').'">'.ucfirst(qa_lang('booker_lang/appts')).'</a></li>
						<li><a href="'.qa_path('clientcalendar').'">'.qa_lang('booker_lang/calendar').'</a></li>
						<li><a href="'.qa_path('clientratings').'">'.qa_lang('booker_lang/menubtn_cli_ratings').'</a></li>
						<li><a href="'.qa_path('mbmessages').'">'.qa_lang('booker_lang/topright_msg').'</a></li>
						<li><a href="'.qa_path('newsletter').'">'.qa_lang('booker_lang/newsletter').'</a></li>
						<li><a href="'.qa_path('logout').'">'.qa_lang('main/nav_logout').'</a></li>
					</ul>
					';
				}
				$premiumteaser = '';
				if(booker_iscontracted($userid) && !$ispremium)
				{
					$premiumteaser = '
					<a href="'.qa_path('premium').'" class="btn btn-small btn-green">
						'.qa_lang('booker_lang/premium').'
					</a>
					';
				}

				$output_topright .= '
                </div> <!-- dropdown-wrapper -->
			 	'.$premiumteaser.'
			</div> <!-- qa-search -->
				';

				$this->output($output_topright);

			} // end isset($userid)

			/*
			// forum search button
			// $this->output('<input type="submit" value="'.$search['button_label'].'" class="qa-search-button"/>');
			$this->output('<div class="qa-search">');
			// $this->output('<a href="'.qa_path('find').'" title="'.$search['button_label'].'" class="qa-search-button"><i class="fa fa-search"></i></a>');
			$this->output('<a href="/" title="'.$search['button_label'].'" class="qa-search-button"><i class="fa fa-search"></i></a>');
			$this->output('</div> <!-- qa-search -->');
			*/
		}


		// add button below user bar
		function a_item_buttons($a_item)
		{
			if(qa_opt('booker_enabled'))
			{
				if(isset($a_item))
				{
					$contractorid = ($a_item['raw']['userid']); // userid of answerer

					// check if contractor is allowed and available
					if(contractorisapproved($contractorid) && contractorisavailable($contractorid))
					{
						$contractorname = booker_get_realname($contractorid); // ($a_item['raw']['handle']); // handle of answerer
						$this->output('
							<div class="bookingcontainer">
								<a class="bookingbtn" href="'.qa_path('booking').'?contractorid='.$contractorid.'">
									'.qa_lang('booker_lang/book_service_btn').'
								</a>
							</div>
						');
					}
				}
			} // end enabled

			// default
			qa_html_theme_base::a_item_buttons($a_item);
		}

		// booking button on user profile page
		function page_title_error()
		{
			if(!qa_opt('booker_enabled'))
			{
				qa_html_theme_base::page_title_error();
				return;
			}

			if(MB_SECRETMODE && !qa_is_logged_in())
			{
				if($this->template=='user' || $this->request=='bestusers' || $this->request=='contractorlist')
				{
					$this->output('
						<p class="qa-error">
							'.qa_insert_login_links(qa_lang('booker_lang/needregisterlogin')).'
						</p>
					');
					exit;
				}
			} // END SECRETMODE

			// external cannot see userprofiles and best list and users
			if(!qa_is_logged_in())
			{
				if($this->template=='user' || $this->request=='bestusers' || $this->request=='users')
				{
					$this->output('
						<p class="qa-error">
							'.qa_insert_login_links(qa_lang('booker_lang/needregisterlogin')).'
						</p>
					');
					exit;
				}
			}

			$userid = qa_get_logged_in_userid();

			$display_contractormenu = array('bookingbutton', 'contractorprofile', 'contractorinfo', 'contractorbalance', 'contractorweek', 'contractorschedule', 'contractorcalendar', 'contractorratings', 'contractoroffers', 'offercreate');
			$userprofile_contractor = booker_iscontracted($userid) && $this->request=='userprofile';
			$mbmessages_contractor = booker_iscontracted($userid) && $this->request=='mbmessages';

			if(in_array($this->request, $display_contractormenu) || $userprofile_contractor || $mbmessages_contractor)
			{
				$this->output('
					<div class="contractornav">
						<a class="bookermenubutton'.($this->request == 'userprofile' ? ' bmbtnactive' : '').'" href="'.qa_path('userprofile').'">
							<i class="fa fa-male fa-lg"></i> '.qa_lang('booker_lang/profile').'
						</a>
						<a class="bookermenubutton'.($this->request == 'contractorcalendar' ? ' bmbtnactive' : '').'" href="'.qa_path('contractorcalendar').'">
							<i class="fa fa-calendar fa-lg"></i> '.qa_lang('booker_lang/calendar').'
						</a>
						<a class="bookermenubutton'.($this->request == 'contractorschedule' ? ' bmbtnactive' : '').'" href="'.qa_path('contractorschedule').'">
							<i class="fa fa-clock-o fa-lg"></i> '.qa_lang('booker_lang/appts').'
						</a>
						<a class="bookermenubutton'.($this->request == 'contractorbalance' ? ' bmbtnactive' : '').'" href="'.qa_path('contractorbalance').'">
							<i class="fa fa-eur fa-lg"></i> '.qa_lang('booker_lang/menubtn_con_balance').'
						</a>
						<a class="bookermenubutton'.($this->request == 'contractoroffers' || $this->request == 'offercreate' ? ' bmbtnactive' : '').'" href="'.qa_path('contractoroffers').'">
							<i class="fa fa-shopping-basket fa-lg"></i> '.qa_lang('booker_lang/menubtn_con_offers').'
						</a>
						<a class="bookermenubutton'.($this->request == 'contractorratings' ? ' bmbtnactive' : '').'" href="'.qa_path('contractorratings').'">
							<i class="fa fa-smile-o fa-lg"></i> '.qa_lang('booker_lang/ratings').'
						</a>
						<a class="bookermenubutton'.($this->request == 'bookingbutton' ? ' bmbtnactive' : '').'" href="'.qa_path('bookingbutton').'">
							<i class="fa fa-calendar-plus-o fa-lg"></i> '.qa_lang('booker_lang/menu_bookingbutton').'
						</a>
						<a class="bookermenubutton'.($this->request == 'mbmessages' ? ' bmbtnactive' : '').'" href="'.qa_path('mbmessages').'">
							<i class="fa fa-comments fa-lg"></i> '.qa_lang('booker_lang/messages').'
						</a>
					</div>
				');
			}
			else
			{
				$display_clientmenu = array('clientcalendar', 'clientprofile', 'clientratings', 'clientrequests', 'clientschedule', 'requestcreate');
				$userprofile_client = !booker_iscontracted($userid) && $this->request=='userprofile';
				$mbmessages_client = !booker_iscontracted($userid) && $this->request=='mbmessages';

				if(in_array($this->request, $display_clientmenu) || $userprofile_client || $mbmessages_client)
				{
					$this->output('
						<div class="contractornav">
							<a class="bookermenubutton'.($this->request == 'userprofile' ? ' bmbtnactive' : '').'" href="'.qa_path('userprofile').'">
								<i class="fa fa-male fa-lg"></i> '.qa_lang('booker_lang/menubtn_cli_profile').'
							</a>
							<a class="bookermenubutton'.($this->request == 'clientcalendar' ? ' bmbtnactive' : '').'" href="'.qa_path('clientcalendar').'">
								<i class="fa fa-calendar fa-lg"></i> '.qa_lang('booker_lang/calendar').'
							</a>
							<a class="bookermenubutton'.($this->request == 'clientschedule' ? ' bmbtnactive' : '').'" href="'.qa_path('clientschedule').'">
								<i class="fa fa-clock-o fa-lg"></i> '.qa_lang('booker_lang/appts').'
							</a>
							<a class="bookermenubutton'.($this->request == 'clientrequests' || $this->request == 'requestcreate' ? ' bmbtnactive' : '').'" href="'.qa_path('clientrequests').'">
								<i class="fa fa-newspaper-o fa-lg"></i> '.qa_lang('booker_lang/requests').'
							</a>
							<a class="bookermenubutton'.($this->request == 'clientratings' ? ' bmbtnactive' : '').'" href="'.qa_path('clientratings').'">
								<i class="fa fa-smile-o fa-lg"></i> '.qa_lang('booker_lang/menubtn_cli_ratings').'
							</a>
							<a class="bookermenubutton'.($this->request == 'mbmessages' ? ' bmbtnactive' : '').'" href="'.qa_path('mbmessages').'">
								<i class="fa fa-comments fa-lg"></i> '.qa_lang('booker_lang/messages').'
							</a>
						</div>
					');
				}
			}
			/*
				<a class="bookermenubutton'.($this->request == 'mbmessages' ? ' bmbtnactive' : '').'" href="'.qa_path('mbmessages').'" title="'.qa_lang('booker_lang/messages').'">
					<i class="fa fa-comments fa-lg"></i>
				</a>
			*/

			// user should not favorite himself
			if( $this->template=='user' && !qa_clicked('doaccount') )
			{
				if(isset($this->content['raw']['account']['userid']))
				{
					$contractorid = $this->content['raw']['account']['userid'];

					// check if contractor is allowed and available
					if(contractorisapproved($contractorid) && contractorisavailable($contractorid))
					{
						if(isset($contractorid))
						{
							$this->output('
								<div class="bookingcontainer">
									<a class="bookingbtn" href="'.qa_path('booking').'?contractorid='.$contractorid.'">
										'.qa_lang('booker_lang/book_service_btn').'
									</a>
								</div>
							');
						}
					}
				}
			}

			$display_adminmenu = array('admincalendar', 'adminclients', 'admincontractors', 'adminlogview', 'adminmessages', 'adminoffers', 'adminratings', 'adminrequests', 'adminschedule', 'adminsearchtrack');
			if(in_array($this->request, $display_adminmenu) && qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN)
			{
				$this->output('
					<div class="contractornav">
						<a class="bookermenubutton tooltipN'.($this->request == 'admincontractors' ? ' bmbtnactive' : '').'" href="./admincontractors" title="'.qa_lang('booker_lang/contractors').'">
							<i class="fa fa-male fa-lg"></i>
						</a>
						<a class="bookermenubutton tooltipN'.($this->request == 'adminoffers' ? ' bmbtnactive' : '').'" href="./adminoffers" title="'.qa_lang('booker_lang/offers').'">
							<i class="fa fa-shopping-basket fa-lg"></i>
						</a>
						<a class="bookermenubutton tooltipN'.($this->request == 'adminclients' ? ' bmbtnactive' : '').'" href="./adminclients" title="'.qa_lang('booker_lang/clients').'">
							<i class="fa fa-female fa-lg"></i>
						</a>
						<a class="bookermenubutton tooltipN'.($this->request == 'adminrequests' ? ' bmbtnactive' : '').'" href="./adminrequests" title="'.qa_lang('booker_lang/requests').'">
							<i class="fa fa-newspaper-o fa-lg"></i>
						</a>
						<a class="bookermenubutton tooltipN'.($this->request == 'adminschedule' ? ' bmbtnactive' : '').'" href="./adminschedule" title="'.qa_lang('booker_lang/appts').'">
							<i class="fa fa-book fa-lg"></i>
						</a>
						<a class="bookermenubutton tooltipN'.($this->request == 'admincalendar' ? ' bmbtnactive' : '').'" href="./admincalendar" title="'.qa_lang('booker_lang/calendar').'">
							<i class="fa fa-calendar fa-lg"></i>
						</a>
						<a class="bookermenubutton tooltipN'.($this->request == 'adminratings' ? ' bmbtnactive' : '').'" href="./adminratings" title="'.qa_lang('booker_lang/ratings').'">
							<i class="fa fa-smile-o fa-lg"></i>
						</a>
						<a class="bookermenubutton tooltipN'.($this->request == 'adminmessages' ? ' bmbtnactive' : '').'" href="./adminmessages" title="'.qa_lang('booker_lang/messages').'">
							<i class="fa fa-comments fa-lg"></i>
						</a>
						<a class="bookermenubutton tooltipN'.($this->request == 'adminsearchtrack' ? ' bmbtnactive' : '').'" href="./adminsearchtrack" title="'.qa_lang('booker_lang/searchtrack').'">
							<i class="fa fa-search fa-lg"></i>
						</a>
						<a class="bookermenubutton tooltipN'.($this->request == 'adminlogview' ? ' bmbtnactive' : '').'" href="./adminlogview" title="'.qa_lang('booker_lang/adviewlog_title').'">
							<i class="fa fa-eye fa-lg"></i>
						</a>
					</div>
					'
				);
				// '.qa_lang('booker_lang/menubtn_messages').'
				// '.qa_lang('booker_lang/menubtn_admin_searchtrack').'
			}


			// call default
			qa_html_theme_base::page_title_error();

			// add username below headline
			if(in_array($this->request, $this->shownamepages))
			{
				$customername = booker_get_realname($userid);
				$this->output('
					<p class="sub-username">
						'.$customername.'
					</p>
				');
			}

		} // end page_title_error

		// override
		public function nav_item($key, $navlink, $class, $level=null)
		{
			// call default
			// qa_html_theme_base::nav_item($key, $navlink, $class, $level=null);

			$suffix = strtr($key, array( // map special character in navigation key
				'$' => '',
				'/' => '-',
			));

			$this->output('<li class="qa-'.$class.'-item'.(@$navlink['opposite'] ? '-opp' : '').
				(@$navlink['state'] ? (' qa-'.$class.'-'.$navlink['state']) : '').' qa-'.$class.'-'.$suffix.'">');
			$this->nav_link($navlink, $class);

			// add dropdown here
			$dropdown = $this->booker_get_menudropdown($navlink['label']);
			if(!empty($dropdown))
			{
				$this->output($dropdown);
			}

			if (count(@$navlink['subnav']))
				$this->nav_list($navlink['subnav'], $class, 1+$level);

			$this->output('</li>');
		} // end function nav_item

		function booker_get_menudropdown($label)
		{
			$userid = qa_get_logged_in_userid();

			// remove possible html tags
			$label = trim(strip_tags($label));

			// var_dump($label);
			$dropdownmenu = '';
			if($label == qa_lang('booker_lang/nav_main_contractor'))
			{
				$dropdownmenu = '
				<ul class="dropdown-menu">
					<li><a href="'.qa_path('contractorlist').'">'.qa_lang('booker_lang/all_contractors').'</a></li>
					<li><a href="'.qa_path('offerlist').'">'.qa_lang('booker_lang/all_offers').'</a></li>
					<li><a href="'.qa_path('contractoroffers').'">'.qa_lang('booker_lang/my_offers').'</a></li>
					<li><a href="'.qa_path('offercreate').'">'.qa_lang('booker_lang/offer_create_btn').'</a></li>
				</ul>
				';
			}
			/*** TEMP OFF
			else if($label == qa_lang('booker_lang/ask_service'))
			{
				$dropdownmenu = '
				<ul class="dropdown-menu">
					<li><a href="'.qa_path('requestlist').'">'.qa_lang('booker_lang/all_requests').'</a></li>
					<li><a href="'.qa_path('clientrequests').'">'.qa_lang('booker_lang/my_requests').'</a></li>
					<li><a href="'.qa_path('requestcreate').'">'.qa_lang('booker_lang/request_create_btn').'</a></li>
				</ul>
				';
			}
			*/
			else if($label == qa_lang('booker_lang/forum'))
			{
				$toptopicslink = '';
				if($_SERVER['SERVER_NAME']=='www.kvanto.lt')
				{
					$toptopicslink = '<li><a href="'.qa_path('topics').'">'.qa_lang('booker_lang/toptopics').'</a></li>';
				}

				$dropdownmenu = '
				<ul class="dropdown-menu">
					<li><a href="'.qa_path('ask').'">'.qa_lang('main/nav_ask').'</a></li>
					<li><a href="'.qa_path('questions').'">'.qa_lang('main/recent_qs_title').'</a></li>
					<li><a href="'.qa_path('unanswered').'">'.qa_lang('main/unanswered_qs_title').'</a></li>
					'
					.$toptopicslink.
					'
					<li><a href="'.qa_path('liveticker').'">Liveticker</a></li>
					<li><a href="'.qa_path('points').'">'.qa_lang('booker_lang/pointsystem').'</a></li>
				</ul>
				';
				// <li><a href="'.qa_path('bestusers').'">'.qa_lang('booker_lang/monthbest').'</a></li>
			}
			else if($label == qa_lang('booker_lang/myaccount'))
			{
				$username = booker_get_realname($userid);
				// if too long, only output firstname
				if(strlen($username)>13)
				{
					$username = strtok($username, " ");
				}

				// 'label' => '<i class="icn icon-user" style="margin-right:2px;"></i> '.qa_lang('booker_lang/myaccount') // identifier for dropdown

				if(booker_iscontracted($userid))
				{
					$dropdownmenu = '
					<ul class="dropdown-menu">
						<li>
							<a href="'.qa_path('userprofile').'">
								'.qa_lang('booker_lang/profile').' <span style="font-size:12px;color:#AAA;">'.$username.'</span>
							</a>
						</li>
						<li><a href="'.qa_path('contractoroffers').'">'.qa_lang('booker_lang/offers').'</a></li>
						<li><a href="'.qa_path('contractorschedule').'">'.qa_lang('booker_lang/appts').'</a></li>
						<li><a href="'.qa_path('contractorcalendar').'">'.qa_lang('booker_lang/calendar').'</a></li>
						<li><a href="'.qa_path('contractorratings').'">'.qa_lang('booker_lang/ratings').'</a></li>
						<li><a href="'.qa_path('contractorbalance').'">'.qa_lang('booker_lang/menubtn_con_balance').'</a></li>
						<li><a href="'.qa_path('mbmessages').'">'.qa_lang('booker_lang/topright_msg').'</a></li>
						<li><a href="'.qa_path('newsletter').'">'.qa_lang('booker_lang/newsletter').'</a></li>
						<li><a href="'.qa_path('logout').'">'.qa_lang('main/nav_logout').'</a></li>
					</ul>
					';
				}
				else
				{
					$dropdownmenu = '
					<ul class="dropdown-menu">
						<li>
							<a href="'.qa_path('userprofile').'">
								'.qa_lang('booker_lang/profile').' <span style="font-size:12px;color:#AAA;">'.$username.'</span>
							</a>
						</li>
						<li><a href="'.qa_path('clientrequests').'">'.qa_lang('booker_lang/requests').'</a></li>
						<li><a href="'.qa_path('clientschedule').'">'.qa_lang('booker_lang/appts').'</a></li>
						<li><a href="'.qa_path('clientcalendar').'">'.qa_lang('booker_lang/calendar').'</a></li>
						<li><a href="'.qa_path('clientratings').'">'.qa_lang('booker_lang/menubtn_cli_ratings').'</a></li>
						<li><a href="'.qa_path('mbmessages').'">'.qa_lang('booker_lang/topright_msg').'</a></li>
						<li><a href="'.qa_path('newsletter').'">'.qa_lang('booker_lang/newsletter').'</a></li>
						<li><a href="'.qa_path('logout').'">'.qa_lang('main/nav_logout').'</a></li>
					</ul>
					';
				}
			}
			/*
			else if($label == qa_lang('booker_lang/contractor'))
			{
			}
			else if($label == qa_lang('booker_lang/client'))
			{
			}
			*/
			else if($label == qa_lang('main/nav_admin'))
			{
				// should only be visible for admin, check if admin level though
				if(qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN)
				{
					$userhandle = helper_gethandle($userid);
					$dropdownmenu = '
					<ul class="dropdown-menu">
						<li><a href="'.qa_path('admincontractors').'">'.qa_lang('booker_lang/contractors').'</a></li>
						<li><a href="'.qa_path('adminoffers').'">'.qa_lang('booker_lang/offers').'</a></li>
						<li><a href="'.qa_path('adminclients').'">'.qa_lang('booker_lang/clients').'</a></li>
						<li><a href="'.qa_path('adminrequests').'">'.qa_lang('booker_lang/requests').'</a></li>
						<li><a href="'.qa_path('adminschedule').'">'.qa_lang('booker_lang/appts').'</a></li>
						<li><a href="'.qa_path('adminratings').'">'.qa_lang('booker_lang/ratings').'</a></li>
						<li><a href="'.qa_path('adminmessages').'">'.qa_lang('booker_lang/messages').'</a></li>
						<li><a href="'.qa_path('adminsearchtrack').'">'.qa_lang('booker_lang/searchtrack').'</a></li>
						<li><a href="'.qa_path('adminlogview').'">'.qa_lang('booker_lang/adviewlog_title').'</a></li>
						<li><a href="'.qa_path('admin/plugins').'">'.qa_lang('admin/plugins_title').'</a></li>
					</ul>
					';
					// <li><a href="'.qa_path('admin/plugins').'">Plugins</a></li>
				}
			}

			return $dropdownmenu;
		}

		/*
		function nav_user_search()
		{
			qa_html_theme_base::nav_user_search();

			$userid = qa_get_logged_in_userid();
			if(isset($userid))
			{
				$avatarblobid = qa_get_logged_in_user_field('avatarblobid');
				if(empty($avatarblobid))
				{
					$avatarblobid = qa_opt('avatar_default_blobid');
				}
				$userhandle = helper_gethandle($userid);
				$this->output('
				<ul style="padding:0;margin:0;">
					<li class="user-nav-avatar-holder">
						<img src="/?qa=image&qa_blobid='.$avatarblobid.'&qa_size=30" alt="Avatar" class="qa-avatar-image" />
						<ul class="dropdown-menu">
							<li><a href="'.qa_path('userprofile').'">'.qa_lang('booker_lang/profile').'</a></li>
							<li><a href="'.qa_path('mbmessages').'">'.qa_lang('booker_lang/topright_msg').'</a></li>
							<li><a href="'.qa_path('newsletter').'">'.qa_lang('booker_lang/newsletter').'</a></li>
							<li><a href="'.qa_path('user').'/'.$userhandle.'/questions">'.qa_lang('booker_lang/yourquestions').'</a></li>
							<li><a href="'.qa_path('user').'/'.$userhandle.'/answers">'.qa_lang('booker_lang/youranswers').'</a></li>
							<li><a href="'.qa_path('user').'/'.$userhandle.'/activity">'.qa_lang('booker_lang/youractivity').'</a></li>
						</ul>
					</li>
				</ul>
				');
			}
		}
		*/

		// add points | star | upvotes on top right
		/*
		function nav($navtype, $level=null)
		{
			$userid = qa_get_logged_in_userid();

			if(isset($userid))
			{
				// get number of questions and answers
				$userpointsData = qa_db_read_one_assoc( qa_db_query_sub('SELECT points, qposts, aposts, cposts, aselects, aselecteds, upvoteds
								FROM `qa_userpoints`
								WHERE userid = #', $userid), true);

				$answersTotal = @$userpointsData['aposts'];
				$answerCount = qa_html(number_format($answersTotal));
				$answersBest = @$userpointsData['aselecteds'];
				$userpoints = @$userpointsData['points'];
				// $questionsTotal = @$userpointsData['qposts'];
				// $questionCount = qa_html(number_format($questionsTotal));
				// $questionsBestChosen = @$userpointsData['aselects'];

				// acceptance rate A
				$acceptanceString = '';
				if($answersTotal>0)
				{
					$acceptanceString = qa_html(number_format( 100 * $answersBest / $answersTotal, 2, ',', '.')) . ' %';
				}

				// upvoteds
				$receivedUpvotes = qa_html(number_format(@$userpointsData['upvoteds']));

				$userhandle = helper_gethandle($userid);

				// $userpoints = qa_html(number_format($userpointsMonth,0,',','.'));
				// <a href="'.qa_path('userstats').'" title="'.$userpoints.' '.qa_lang('booker_lang/points').'">'.$userpoints.'</a>

				$outputfrontend = '
				<div id="usernavpoints">
					  <a href="'.qa_path('user').'/'.$userhandle.'" title="'.$answersTotal.' '.qa_lang('booker_lang/answers').'">'.$answersTotal.' '.substr(qa_lang('booker_lang/answers'), 0, 1) .'</a>
					| <span title="'.$answersBest.'/'.$answersTotal.' = '.$acceptanceString.' '.qa_lang('booker_lang/bestanswers').'">'.$answersBest.' <div class="ministar"></div></span>
					| <span title="'.$receivedUpvotes.' '.qa_lang('booker_lang/votesreceived').'">'.$receivedUpvotes.' <div class="minithumb"></div></span>
				</div>
				';

				// new element in user-nav
				@$this->content['navigation']['user']['usernavpoints'] = array(
							'label' => $outputfrontend,
							// 'url' => qa_path_html('favorites'),
				);
			}

			// call default method
			qa_html_theme_base::nav($navtype, $level=null);

		} // end nav()
		*/

		// NOTES:
		// nav_main_sub() gets overriden by booker-layer-userforumprofile.php

	} // end class

/*
	Omit PHP closing tag to help avoid accidental output
*/

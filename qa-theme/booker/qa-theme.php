<?php

/*
	File: qa-theme/booker/qa-theme.php
	Date: 2016-10
	Description: Overrides for kvanto.lt Theme + kyga.de
*/

	class qa_html_theme extends qa_html_theme_base
	{

		var $isstartpage = false;
		var $showInstructionsAfterQ = false;
		var $mainlogo = 'logos/kvanto-logo-white-2.png';

		// abstract method: first and safest place to change content and layers
		function initialize()
		{
			// var_dump($this->headtitle);

			if(qa_opt('site_title')=='Kvanto')
			{
				// from default above
			}
			if(qa_opt('site_title')=='Kyga')
			{
				$this->mainlogo = 'logos/kyga-textlogo-white.png';
			}

			$this->isstartpage = ($this->template=='booker contractorlist' || $this->template=='qa' || $this->template=='activity');

			// startpage output
			if($this->isstartpage && !qa_is_logged_in())
			{
				unset($this->content['q_list']);
				// give different page title
				// $this->content['title'] = 'kvanto - Užsisakykite tai ko Jums reikia, kada Jums reikia';
				// $this->content['title'] = qa_lang('booker_lang/conli_title');

				// unset($this->content['title']);
				unset($this->content['sidebar']);
				unset($this->content['sidepanel']);
				unset($this->content['widgets']);
			}

			// NAVIGATION
			// change navigation: bring "ask" to front - now by booker plugin
			/*
			$hash = $this->content['navigation']['main'];
			if(isset($hash))
			{
				$hash = array('ask' => $hash['ask']) + $hash;
				$this->content['navigation']['main'] = $hash;
			}
			*/

			// remove tags from menu
			unset($this->content['navigation']['main']['tag']);

			// ASK page
			if($this->template=='ask' && isset($this->content['form']['fields']['title']['tags']))
			{
				// change
				// string: 'name="title" id="title" autocomplete="off" onchange="qa_title_change(this.value);"'
				// 1. make onchange to onkeyup
				$this->content['form']['fields']['title']['tags'] = str_replace("onchange", "onkeyup", $this->content['form']['fields']['title']['tags']);
				// 2. add placeholder to title field

				// *** $this->content['form']['fields']['title']['tags'] .= ' placeholder="Aufgabe in einem Satz schreiben"';
			}

			// FAVORITE page
			/***
			if($this->template=='favorites')
			{
				$usercount = $this->content['ranking_users']['rows'];
				$this->content['ranking_users']['title'] = $usercount>0 ? 'Mitglieder, bei denen du dich bedankt hast:' : qa_lang_html('misc/no_favorite_users');
			}
			*/

			// FEEDBACK page
			/*if($this->template=='feedback')
			{
				$this->content['error'] = 'Wir antworten nicht auf Fragen, deren Antwort sich bereits in den <a href="'.qa_path('faq').'">häufigen Fragen</a> befindet. Erhältst du von uns innerhalb von 48 Stunden keine Antwort, so ist deine Frage dort beantwortet.';
			}
			*/

			// QUESTION page
			if($this->template=='question')
			{
				// 1. dont load editor for anonymous
				// only answer form if logged in user or if requested via ?state=answer - hack of /page/question.php
				if(!isset($userid) && is_null(qa_get_state()))
				{
					// remove sceditor too
					// http://www.question2answer.org/qa/45957/prevent-loading-of-editor-module-with-advanced-theme
					// ...
				}
				// only comment form if logged in user or if requested
				// unset $a_view['c_form']
				// only comment form if logged in user or if requested
				// unset $qa_content['q_view']['c_form']

				// 2. USER RIGHTS
				// only experts, editors (Moderatoren) and admins can close questions
				// $this->content['q_view']['raw']['closeable'] = false;
				if(qa_get_logged_in_level()<QA_USER_LEVEL_EXPERT)
				{
					unset($this->content['q_view']['form']['buttons']['close']);
				}
				// memo: remove "retagcatbutton"
				unset($this->content['q_view']['form']['buttons']['retagcat']);

				// 3. remove "geschlossen" from question title, see title()
				// http://www.question2answer.org/qa/45978/how-to-remove-new-closed-notice-question-title-question-page
				unset($this->content['q_view']['closed']['state']);

			} // end template question

			// GLOBAL
			// remove anonymous name field "name to display" from all pages
			// *** better to override core function: function qa_set_up_name_field() in app/format

			// ask field
			/*
			if(isset($this->content['form']['fields']['name']))
			{
				unset($this->content['form']['fields']['name']);
			}
			// answer fields
			if(isset($this->content['a_form']['fields']['name']))
			{
				unset($this->content['a_form']['fields']['name']);
			}*/
			// comment fields
			// ...

			// remove silent edit notice (save_silent_label)
			unset($this->content['form_q_edit']['fields']['silent']);
			unset($this->content['a_form']['fields']['silent']);
			// remove for comment fields too
			// *** www.question2answer.org/qa/41325/remove-checkbox-silently-hide-that-this-edited-from-answer

			// remove updates link
			unset($this->content['navigation']['user']['updates']);

		} // end initialize

		public function css_name()
		{
			// own CSS version
			return 'qa-styles.css?v=0.0.011';
		}

		// override to output topbar wrap and set nav_user_search before logo
		public function header()
		{
			$this->output('
				<div class="topbar">
					<div class="qa-header">
			');

			$this->nav_user_search();
			$this->logo();
			$this->nav_main_sub();
			$this->header_clear();

			$this->output('
					</div> <!-- END qa-header -->
				</div> <!-- END topbar -->
			');
		} // end header

		public function logged_in()
		{
			// $this->content['loggedin']['data'] = '';
			// $this->content['loggedin']['prefix'] = qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), 20, 20, 30);
			$this->content['loggedin']['prefix'] = '';
			qa_html_theme_base::logged_in();
		}


		// show only one comment link at the end of all comments
		// http://www.question2answer.org/qa/46063/
		public function c_list($c_list, $class)
		{
            qa_html_theme_base::c_list($c_list, $class);
			if (!empty($c_list['cs'])) {
				// get first element of comment list array
				// $firstcomment = reset($c_list['cs']);
				// memo: dont take first as this could be the expand tag array (if comments get hidden within "show x comments before")

				// get last element of comment list array
				$lastcomment = end($c_list['cs']);

				$parenttype = qa_db_read_one_value( qa_db_query_sub( 'SELECT `type` FROM ^posts
								WHERE `postid` = #', $lastcomment['raw']['parentid']), true );

				if(isset($parenttype))
				{
					$parenttype = strtolower($parenttype);
					// default question
					$namelink = 'q_docomment';
					if($parenttype=='a') {
						$namelink = 'a'.$lastcomment['raw']['parentid'].'_docomment';
					}
					$this->output('
						<input name="'.$namelink.'" onclick="return qa_toggle_element(\'c'.$lastcomment['raw']['parentid'].'\')" value="'.qa_lang_html('question/reply_button').'" title="'.qa_lang_html('question/reply_c_popup').'" type="submit" class="qa-form-light-button qa-form-light-button-comment qa-form-light-button-comment-last">
					');
				}
			}
		} // end c_list

		// reorder buttons on question page
		function form_buttons($form, $columns)
		{
			$keys=array('answer','comment','edit','flag','close','hide');
			if(isset($form['buttons']))
			{
				qa_html_theme_base::form_reorder_buttons($form, $keys);
			}
			qa_html_theme_base::form_buttons($form, $columns);
		} // end form_buttons

		/*
		function nav($navtype, $level=null)
		{
			// call default method
			qa_html_theme_base::nav($navtype, $level=null);
		} // end nav()
		*/
		
		// change navigation
		function nav_user_search()
		{
			// search link is now google search engine, default link is "search", now "ieskoti"
			$this->content['search']['form_tags'] = 'method="get" action="'.qa_path_html('find').'"';
			// added placeholder to search input
			$this->content['search']['field_tags'] = 'name="q" placeholder="'.qa_lang('booker_lang/lang_search').'"';

			// reverse the usual order
			$this->search();
			$this->nav('user');

			// loginbox layer with redirect to recent page ?to=
			if(!qa_is_logged_in())
			{
				$topath = qa_get('to');
				$userlinks = qa_get_login_links(qa_path_to_root(), isset($topath) ? $topath : qa_path($this->request, $_GET, ''));

				// we use booker_lang temporary for the language switch, dont ask me why, i forgot the original reason
				$this->output('
					<div id="loginBox">
						<form method="post" action="'.qa_html(@$userlinks['login']).'" id="loginForm">
							<fieldset id="loginbody">
								<p id="form-login-username">
									<label>'.qa_lang('booker_lang/lang_mailorusername').'</label>
									<input name="emailhandle" value="" type="text" size="18" class="inputbox" required>
								</p>
								<p id="form-login-password">
									<label>'.qa_lang('booker_lang/lang_password').'</label>
									<input name="password" type="password" value="" size="18" class="inputbox" required>
								</p>
								<input type="submit" value="'.qa_lang('booker_lang/lang_login').'" class="btnblue">
								<p id="form-login-remember">
									<input name="remember" type="checkbox" value="1" checked="checked" id="checkRemember" class="inputbox" >
									<label for="checkRemember" class="remember-label">'.qa_lang('booker_lang/lang_rememberme').'</label>
								</p>
								<p class="quicklogin-forgot-link">
									<a href="'.qa_path('forgot').'">'.qa_lang('booker_lang/lang_forgot_link').'</a>
								</p>
							</fieldset>
							<input type="hidden" name="dologin" value="1">
							<input type="hidden" name="code" value="'.qa_get_form_security_code('login').'">
						</form>
				</div>');
			}
		} // END nav_user_search

		// override to remove attribution and add language tag
		function html()
		{
			$this->output('<html lang="'.qa_lang('booker_lang/lang_code').'">');

			$this->head();
			$this->body();

			$this->output('</html>');
		}

		// add font style
		public function head_css()
		{
			$this->output('
				<link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700&subset=latin-ext" rel="stylesheet">
			');
			// call default method output
			qa_html_theme_base::head_css();
		}


		// override sidepanel
		function sidepanel()
		{
			if($this->template=='booker contractorlist')
			{
				$this->output( $this->startpage_hero() );
			}

			// output css element (for background)
			$this->output('<div class="content-wrapper">');

			// moved sub-nav to inner
			$this->nav('sub');

			// sidepanel only for desktop, not for mobiles
			if(!qa_is_mobile_probably())
			{
				$this->output('<div class="qa-sidepanel">');

				$this->widgets('side', 'top');

				$this->sidebar();

				// WIDGET: interview data in sidebar (see new version below)
				/*
				if(qa_opt('q2apro_interview_showstartpage') && ($this->template=='question' || $this->template=='questions'))
				{
					$interviewLink = qa_opt('q2apro_interviewdata_link');
					$interviewee = qa_opt('q2apro_interviewdata_person');
					$interviewBlobID = qa_opt('q2apro_interviewdata_personimgid');
					$interviewHeadline = qa_opt('q2apro_interviewdata_headline');
					$interviewPreview = qa_opt('q2apro_interviewdata_previewtxt');
					$interviewPrize = qa_opt('q2apro_interviewdata_prize');
					$imageSize = 150;
					$imageOrient = qa_opt('q2apro_interviewdata_imgorient');

					$this->output('
					<div style="margin:0 0 35px 0;">
						<a href="'.$interviewLink.'" style="display:block;margin-bottom:10px;color:#05B;">'.$interviewHeadline.'</a>
						<a href="'.$interviewLink.'"> <img src="/?qa=image&qa_blobid='.$interviewBlobID.'&qa_size='.$imageSize.'" alt="'.$interviewee.'" /></a>
						<a href="'.$interviewLink.'" style="display:block;margin:10px 0 0 0;color:#05B;font-size:13px;">'.$interviewPrize.'</a>
					</div>
					');
				} // end interview in sidebar
				*/
				if(qa_opt('q2apro_interview_showstartpage') && $this->template=='question')
				{
					$currentqu = $this->content['q_view']['raw']['postid'];

					// get latest interview question
					$interviewdata = qa_db_read_one_assoc(
									qa_db_query_sub('SELECT title, postid, content FROM `^posts` WHERE `tags` LIKE "%'.qa_lang('booker_lang/interviewtag').'%"
													ORDER BY created DESC
													LIMIT 1
											 	 '), true);
					if(isset($interviewdata))
					{
						$interview_title = $interviewdata['title'];
						$interview_qid = $interviewdata['postid'];
						$interview_link = qa_html(qa_q_path($interview_qid, $interview_title, false)); // , $showtype, $showid

						// dont show interview teaser on the same interview page
						if($currentqu!=$interview_qid)
						{
							// get image url from content
							$interview_blobid = $this->q2apro_extract_blobid_from_string($interviewdata['content']);
							if(is_null($interview_blobid))
							{
								$interview_blobid = qa_opt('avatar_default_blobid');
							}
							$imagesize = 150;

							$this->output('
							<div style="margin:0 0 35px 0;">
								<a href="'.$interview_link.'" style="display:block;margin-bottom:10px;color:#05B;">'.$interview_title.'</a>
								<a href="'.$interview_link.'"> <img src="/?qa=image&qa_blobid='.$interview_blobid.'&qa_size='.$imagesize.'" /></a>
							</div>
							');
						}
					}
				} // end interview in sidebar

				$this->widgets('side', 'high');

				$this->widgets('side', 'low');

				if ($this->template=='qa' || $this->template=='activity' || $this->template=='questions' || $this->template=='question' || $this->request=='liveticker' || $this->template=='unanswered')
				{
					$this->feed();
					$this->widgets('side', 'bottom');
				}

				/*if($this->request=='chat')
				{
					$this->output('<a target="_blank" class="sidebarBtn mtop20" title="Sek tiesiogiai visus klausimus ir atsakymus!" href="/liveticker/">Liveticker &nbsp;&Xi;</a>');
					$this->output('<a target="_blank" class="sidebarBtn mtop5" href="/chathistory/" title="Chat-History">praeities pokalbiai</a>');
				}
				*/

				$this->output('</div> <!-- end sidepanel -->');
			} // end !mobile
		} // end sidepanel



		function startpage_hero()
		{
			$output = '';

			$output .= '
               <div class="section section-hero">

                    <h1>
						'.qa_lang('booker_lang/mainsearchlabel').'
					</h1>

					<section id="search">
						<label for="search-input">
							<i class="fa fa-search" aria-hidden="true"></i><span class="sr-only">'.qa_lang('booker_lang/search_placeholder').'</span>
						</label>

						<input id="search-input" class="form-control input-lg" placeholder="'.qa_lang('booker_lang/search_placeholder').'" autocomplete="off" spellcheck="false" autocorrect="off" tabindex="1" autofocus>

						<a id="search-clear" href="#" class="fa fa-times-circle" aria-hidden="true">
							<span class="sr-only">'.qa_lang('booker_lang/search_clear').'</span>
						</a>

						<div class="q2apro_us_progress"><div>Loading…</div></div>
					</section>

                    <div class="search-suggestion">
                        '.qa_lang('booker_lang/forexample').':
                        <a href="?s='.qa_lang('booker_lang/photograph_link').'">'.qa_lang('booker_lang/photograph_linktext').'</a>,
                        <a href="?s='.qa_lang('booker_lang/dentist_link').'">'.qa_lang('booker_lang/dentist_linktext').'</a>,
                        <a href="?s='.qa_lang('booker_lang/mechanic_link').'">'.qa_lang('booker_lang/mechanic_linktext').'</a>,
                        <a href="?s='.qa_lang('booker_lang/nanny_link').'">'.qa_lang('booker_lang/nanny_linktext').'</a>,
                        <a href="?s='.qa_lang('booker_lang/translator_link').'">'.qa_lang('booker_lang/translator_linktext').'</a>,
                        <a href="?s='.qa_lang('booker_lang/trainer_link').'">'.qa_lang('booker_lang/trainer_linktext').'</a>,
                        <a href="?s='.qa_lang('booker_lang/apartmentrepair_link').'">'.qa_lang('booker_lang/apartmentrepair_linktext').'</a>,
                        <a href="?s='.qa_lang('booker_lang/designer_link').'">'.qa_lang('booker_lang/designer_linktext').'</a>
                    </div>

				<div class="about-whatis">
                        <a href="#">'.qa_lang('booker_lang/whatis').' '.qa_opt('site_title').'?</a>
				</div>

                    <div class="section-bg" style="background-image: url('.$this->rooturl.'images/hero-1.jpg)"></div>

               </div>
          	';



          	$output .= '
               <div class="section ajax_results">
                    <div class="content-wrapper">

                        <section id="searchresults">
                            <div class="locationfilterbox">
                                <span id="q2apro_locationlabel" class="hide">'.qa_lang('booker_lang/searchlocation_label').':</span>
                                <select id="locations">
                                    <option>Klaipėda</option>
                                    <option>Kaunas</option>
                                    <option>Vilnius</option>
                                </select>
                            </div>

                            <div id="ajaxsearch_results_wrap">
                                <h2>
                                    '.qa_lang('booker_lang/search_results').':
                                </h2>
                                <div id="q2apro_ajaxsearch_results"></div>
                            </div>
                        </section>

                    </div>
               </div>
            ';

          	return $output;
		} // END function startpage_hero()

		function startpage_features()
		{
			$forumprofilelink = '/user/Kajus'; // as example
			$userid = qa_get_logged_in_userid();
			if(isset($userid))
			{
				$userhandle = helper_gethandle($userid);
				if(!empty($userhandle))
				{
					$forumprofilelink = '/user/'.$userhandle;
				}
			}

            $output = '';
            $output .= '
                <div class="section section-feature section-odd">
                    <div class="content-wrapper">

                        <div class="columns">

                            <div class="column column-image column-right" style="background-image:url('.$this->rooturl.'features/feat-1.png)">

                            </div>

                            <div class="column column-left">
                                <div class="column-content">
                                    <h2>
										'.qa_lang('booker_lang/findinstantly_h1').'
									</h2>
                                    <p>
										'.qa_lang('booker_lang/findinstantly_text').'
									</p>
                                    <a href="#" id="jumptosearchbar" class="btn btn-outlined btn-primary">
										'.qa_lang('booker_lang/findinstantly_button').'
									</a>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>


                <div class="section section-feature section-even">
                    <div class="content-wrapper">

                        <div class="columns">

                            <div class="column column-image column-left" style="background-image:url('.$this->rooturl.'features/feat-2.png)">

                            </div>

                            <div class="column column-right">
                                <div class="column-content">
                                    <h2>
										'.qa_lang('booker_lang/timeyouneed_h1').'
									</h2>
                                    <p>
										'.qa_lang('booker_lang/timeyouneed_text').'
									</p>
                                    <a href="#" class="btn btn-outlined btn-primary">
										'.qa_lang('booker_lang/timeyouneed_button').'
									</a>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>


                <div class="section section-feature section-odd">
                    <div class="content-wrapper">

						<div class="columns">

							<div class="column column-image column-right" style="background-image:url('.$this->rooturl.'features/feat-3.png)">
							</div>

							<div class="column column-left">
								<div class="column-content">
                                    <h2>
										'.qa_lang('booker_lang/askquestion_h1').'
									</h2>
                                    <p>
										'.qa_lang('booker_lang/askquestion_text').'
									</p>
                                    <a href="'.qa_path('questions').'" class="btn btn-outlined btn-primary">
										'.qa_lang('booker_lang/askquestion_button').'
									</a>
								</div>
							</div>

						</div>

                    </div>
                </div>


                <div class="section section-feature section-even">
                    <div class="content-wrapper">

						<div class="columns">

							<div class="column column-image column-left" style="background-image:url('.$this->rooturl.'features/feat-4.png)">

							</div>

							<div class="column column-right">
								<div class="column-content">
                                    <h2>
										'.qa_lang('booker_lang/becomeprovider_h1').'
									</h2>
                                    <p>
										'.qa_lang('booker_lang/becomeprovider_text').'
									</p>
                                    <a href="'.qa_path('register').'" class="btn btn-outlined btn-primary">
										'.qa_lang('booker_lang/becomeprovider_button').'
									</a>
								</div>
							</div>

						</div>

                    </div>
                </div>


                <div class="section section-feature section-odd">
                    <div class="content-wrapper">

						<div class="columns">

							<div class="column column-image column-right" style="background-image:url('.$this->rooturl.'features/feat-5.png)">
							</div>

							<div class="column column-left">
								<div class="column-content">
                                    <h2>
										'.qa_lang('booker_lang/offerpackages_h1').'
									</h2>
                                    <p>
										'.qa_lang('booker_lang/offerpackages_text').'
									</p>
                                    <a href="'.qa_path('contractoroffers').'" class="btn btn-outlined btn-primary">
										'.qa_lang('booker_lang/offerpackages_button').'
									</a>
								</div>
							</div>
						</div>

                    </div>
                </div>


                <div class="section section-feature section-even">
                    <div class="content-wrapper">

						<div class="columns">

							<div class="column column-image column-left" style="background-image:url('.$this->rooturl.'features/feat-6.png)">
							</div>

							<div class="column column-right">
								<div class="column-content">
                                    <h2>
										'.qa_lang('booker_lang/clientmanagement_h1').'
									</h2>
                                    <p>
										'.qa_lang('booker_lang/clientmanagement_text').'
									</p>
                                    <a href="'.qa_path('contractorcalendar').'" class="btn btn-outlined btn-primary">
										'.qa_lang('booker_lang/clientmanagement_button').'
									</a>
								</div>
							</div>

						</div>

                    </div>
                </div>


                <div class="section section-feature section-odd">
                    <div class="content-wrapper">

						<div class="columns">

							<div class="column column-image column-right" style="background-image:url('.$this->rooturl.'features/feat-7.png)">
							</div>

							<div class="column column-left">
  								<div class="column-content">
                                  <h2>
										'.qa_lang('booker_lang/getpremium_h1').'
									</h2>
                                    <p>
										'.qa_lang('booker_lang/getpremium_text').'
									</p>
                                    <a href="'.qa_path('premium').'" class="btn btn-outlined btn-primary">
										'.qa_lang('booker_lang/getpremium_button').'
									</a>
								</div>
							</div>

						</div>

                    </div>
                </div>


                <div class="section section-feature section-even">
                    <div class="content-wrapper">

						<div class="columns">

							<div class="column column-image column-left" style="background-image:url('.$this->rooturl.'features/feat-8.png)">
							</div>

							<div class="column column-right">
								<div class="column-content">
                                    <h2>
										'.qa_lang('booker_lang/participate_h1').'
									</h2>
                                    <p>
										'.qa_lang('booker_lang/participate_text').'
									</p>
                                    <a href="'.$forumprofilelink.'" class="btn btn-outlined btn-primary">
										'.qa_lang('booker_lang/participate_button').'
									</a>
								</div>
							</div>

						</div>

                    </div>
                </div>

            ';

			if(!qa_is_logged_in())
			{
				$output .= '
					<div class="section section-center" style="background-image:url('.$this->rooturl.'prefooter.jpg)">
						<div class="content-wrapper">
							<h2>
								'.qa_lang('booker_lang/getbookingpage').'
							</h2>
							<a href="/register" class="btn btn-large btn-primary">
								'.qa_lang('booker_lang/signupnow').'
							</a>
						</div>
					</div>

				';
			}

            return $output;
		} // END function startpage_features()




		// override main to output own html elements and more
		function main()
		{
			$content = $this->content;

			// important: identifier for mobiles, needed by jquery
			if(qa_is_mobile_probably())
			{
				$this->output('<div id="agentIsMobile"></div>');
			}

			// important: identifier for anonymous, needed by jquery
			if(!qa_is_logged_in())
			{
				$this->output('<div id="isAnonym"></div>');
			}

			// init string
			$remindbestText = '
						<p style="margin:0;">
						&raquo; '.qa_lang('booker_lang/lang_qufastanswer').'
						<br />
						&raquo; '.qa_lang('booker_lang/lang_followlatest').': <a href="'.qa_path('liveticker').'">Liveticker</a>
						<br />
						&raquo; '.qa_lang('booker_lang/lang_commentanswer').'
						</p>';

			// list all recent questions of user
			/* ANONYMOUS USER */
			if( !qa_is_logged_in() &&
							($this->template=='questions' || $this->template=='question' || $this->template=='unanswered' || $this->template=='ask' )
			  )
			{

				// get cookie of anonymous user
				$cookieid = qa_cookie_get();

				// output teaser on pages: main, questions, question - dont output for logged in users
				// buttons for anonymous user to ask or answer
				$showTeaser = true;

				/* cookie set because of question, list all questions of anonymous */
				if(isset($cookieid))
				{
					// get questions of anonymous user within last 3 days
					$allQu = qa_db_read_all_assoc( qa_db_query_sub('SELECT postid,title,selchildid,closedbyid,acount FROM ^posts
																		WHERE cookieid = #
																		AND `type`="Q"
																		AND created > NOW() - INTERVAL 3 DAY
																		AND `selchildid` IS NULL
																	', $cookieid) ); // don't show question after best answer selected
					// show question list if there is at least one question
					if(count($allQu)>0)
					{
						// list all current questions of user
						$this->output( $this->listAllUserQuestions($allQu) );
						if($this->template=='ask')
						{
							/* TRANS
							$this->output('
								<div class="preventdups">
									<b>Jede Frage nur einmal stellen</b>. Ansonsten wirst du vom Forum geblockt.
								</div>' );
							*/

							// scroll to top on ask page and move the question field below the h1
							$this->output('
							<script type="text/javascript">
								$(document).ready(function(){
									$("#similar").parent().parent().after( $(".preventdups").detach() );
									// $("#similar").parent().parent().after( $(".anonymQuestList").detach() );
									$(".preventdups").after( $(".anonymQuestList").detach() );
									$("html, body").animate({ scrollTop: 0 }, "slow");
								});
							</script>');
						}
						$showTeaser = false;
					}
				}
				/* best-answer-reminder if post is by user (checks cookie) */
				// just show best-answer-reminder after 5 min, before 5 min the "options after questions" are displayed
				else if($this->template=='question' && isset($this->content['q_view']) && (time() - $this->content['q_view']['raw']['created']) > 300)
				{
					// check if the question belongs to the user
					$isbyuser = qa_post_is_by_user($this->content['q_view']['raw'], qa_get_logged_in_userid(), qa_cookie_get());

					if($isbyuser)
					{
						// check if recent question has got the best answer selected, ignore if closed
						$postid = $this->content['q_view']['raw']['postid'];
						$quExists = qa_db_read_one_value( qa_db_query_sub('SELECT postid FROM ^posts
																			WHERE cookieid = #
																			AND `type`="Q"
																			AND `postid`=#
																			AND `selchildid` IS NULL
																			AND `closedbyid` IS NULL
																			LIMIT 1
																		', $cookieid, $postid), true );
						if(isset($quExists))
						{
							// check if there is already an answer to the question
							$answExists = qa_db_read_one_value( qa_db_query_sub('SELECT postid FROM ^posts
																			WHERE `type`="A"
																			AND `parentid`=#
																			LIMIT 1
																		', $postid), true );
							if(is_null($answExists))
							{
								// no answer yet, display patience & rules
								$this->output('<div class="remindbest" style="width:600px;font-size:16px;line-height:200%;">
								'.$remindbestText.'
								</div>');
							}
							else
							{
								// answer exists to question but no best answer selected
								$this->showInstructionsAfterQ = true;
							}

						}

						// set flag to not output teaser
						$showTeaser = false;
						// always hide ask-button below Q if byuser so that he does not ask again the same question
						$this->output('<style type="text/css">.noAnswerJustAsk, #dialog-box { display:none; } </style>');
					}
				}

				// show teaser for all anonymous users that have not posted any question yet
				/*
				if($showTeaser && $this->template!='ask' && $this->template!='activity')
				{
					$this->output('
					<div id="teaser" style="padding-bottom:20px;">
						<a class="btnblue" href="'.qa_path('ask').'">Paklauskit mūsų ekspertų</a>
						<br />
						<span style="font-size:11px;color:#999;margin-left:0px;">greitai ir be registracijos</span>
					</div>
					');
					// Užduok savo klausimą
				}
				*/
				// arba <a class="btnblue" style="margin:0 10px;" href="'.qa_path('unanswered').'">Atsakyk i klausima</a>
			} // end anonymous
			/* LOGGED-IN USER */
			else
			{
				$userid = qa_get_logged_in_userid();

				if( isset($userid) && ($this->template=='questions' || $this->template=='question' || $this->template=='unanswered' || $this->template=='ask') )
				{
					// get questions of registered user within last 3 days, ignore closed questions and questions with best-answer selected
					$allQu = qa_db_read_all_assoc( qa_db_query_sub('SELECT postid,title,selchildid,closedbyid,acount FROM ^posts
																		WHERE userid = #
																		AND `type`="Q"
																		AND created > NOW() - INTERVAL 3 DAY
																	', $userid) );
																	// dont show question after best answer selected
																	// AND `selchildid` IS NULL
																	// dont show closed questions
																	// AND `closedbyid` IS NULL
					// show question list if there is at least one question, not for admin
					if(count($allQu)>0)
					{
						// list all current questions of user
						$this->output( $this->listAllUserQuestions($allQu) );
						if($this->template=='ask')
						{
							// scroll to top on ask page and move the question field below the h1
							$this->output('<script type="text/javascript">
								$(document).ready(function(){
									$("#similar").parent().parent().after( $(".preventdups").detach() );
									// $("#similar").parent().parent().after( $(".anonymQuestList").detach() );
									$(".preventdups").after( $(".anonymQuestList").detach() );
									$("html, body").animate({ scrollTop: 0 }, "slow");
								});
							</script>');
						}
						$showTeaser = false;
					}
				}
				// just show best-answer-reminder after 5 min, before 5 min the "options after questions" are displayed
				else if($this->template=='question' && isset($this->content['q_view']) && (time() - $this->content['q_view']['raw']['created']) > 300)
				{
					// check if the question belongs to the user
					$isbyuser = qa_post_is_by_user($this->content['q_view']['raw'], qa_get_logged_in_userid(), qa_cookie_get());

					if($isbyuser)
					{
						// check if recent question has got the best answer selected, ignore if closed
						$postid = $this->content['q_view']['raw']['postid'];
						$quExists = qa_db_read_one_value( qa_db_query_sub('SELECT postid FROM ^posts
																			WHERE `userid` = #
																			AND `type`="Q"
																			AND `postid`=#
																			AND `selchildid` IS NULL
																			AND `closedbyid` IS NULL
																			LIMIT 1
																		', $userid, $postid), true );
						if(isset($quExists))
						{
							// !is_null($quExists)

							// check if there is already an answer to the question
							$answExists = qa_db_read_one_value( qa_db_query_sub('SELECT postid FROM ^posts
																			WHERE `type`="A"
																			AND `parentid`=#
																			LIMIT 1
																		', $postid), true );
							if(is_null($answExists))
							{
								// no answer yet, display patience & rules
								$this->output('<div class="remindbest" style="width:600px;font-size:16px;line-height:200%;margin-bottom:20px;">
								'.$remindbestText.'
								</div>');
							}
							else
							{
								// answer exists to question but no best answer selected
								$this->showInstructionsAfterQ = true;
							}

						}

						// always hide ask-button below Q if byuser
						$this->output('<style type="text/css">.noAnswerJustAsk, #dialog-box { display:none; } </style>');
					}
				}
			} // END else logged-in-user

			// check for closed duplicate question on question page
			if($this->template=='question' && isset($this->content['q_view']))
			{
				$isDuplicate = (@$this->content['q_view']['closed'] !== null && @$this->content['q_view']['closed']['url'] !== null);
				if($isDuplicate)
				{
					$this->output('
					<div class="remindbest boxv3">
						'.qa_lang('booker_lang/lang_qudelete').'
						<br />
						'.qa_lang('booker_lang/lang_writecomment').'
					</div>
					');
					// hide teaser
					$this->output('<style type="text/css">#teaser { display:none; } </style>');
				}
			}

			// Top Temos buttons on startpage
			// if($this->template=='qa' || $this->template=='activity' || ($this->template=='questions' && $this->request='questions'))
			if($this->template=='qa' || $this->template=='activity' || $this->template=='questions')
			{
				// teaser to sign up
				if(!qa_is_logged_in() && $this->template=='activity')
				{
					// Kvanto.lt is a community of experts helping you and each other. Join us, it only takes a minute:
					$this->output('
					<div id="signupteaser" style="margin-bottom:40px;border: 1px #dfdfdf solid;display:inline-block;font-size:15px;">
						<div style="border: 3px #fff solid;background:rgba(220,220,220,0.1);padding:16px;">
						<p>
							'.qa_lang('booker_lang/lang_whatis').'
						</p>
						<p style="margin-bottom:0;">
							<span style="margin-right:10px;">
								'.qa_lang('booker_lang/lang_registerquickly').':
							</span>
							<a href="'.qa_path('register').'" class="btnblue">'.qa_lang('booker_lang/lang_register').'</a>
						</p>
						</div>
					</div>
					');
				}

			}

			// latest expert interview on startpage
			/*
			if(qa_opt('q2apro_interview_showstartpage') && ($this->template=='qa' || $this->template=='activity'))
			{
				$interviewLink = qa_opt('q2apro_interviewdata_link');
				$interviewee = qa_opt('q2apro_interviewdata_person');
				$interviewBlobID = qa_opt('q2apro_interviewdata_personimgid');
				$interviewHeadline = qa_opt('q2apro_interviewdata_headline');
				$interviewPreview = qa_opt('q2apro_interviewdata_previewtxt');
				$interviewPrize = qa_opt('q2apro_interviewdata_prize');
				$imageSize = qa_opt('q2apro_interviewdata_imgsize');
				$imageOrient = qa_opt('q2apro_interviewdata_imgorient');

				$this->output('<div class="expertinterview">
					<h3>Naujausias interviu su ekspertu</h3>

						<a class="interview_imga" style="background:url(/?qa=image&qa_blobid='.$interviewBlobID.'&qa_size='.$imageSize.') no-repeat '.$imageOrient.' center;" href="'.$interviewLink.'">
							<span class="exp-img-caption">'.$interviewee.'</span>
						</a>

					<div class="interview_txt">
						<a class="intview_link" href="'.$interviewLink.'">'.$interviewHeadline.'</a>
						<p class="int_preview">
							"'.$interviewPreview.'" <a href="'.$interviewLink.'">daugiau...</a>
						</p>'
						.(!empty($interviewPrize) ? '<a class="btnyellow" href="'.$interviewLink.'">'.$interviewPrize.'</a>' : '').
						'<a style="display:block;width:300px;margin-top:30px;color:#05B;font-size:14px;" href="/tag/interviu">Žiūrėti visus ekspertų interviu…</a>
					</div>
				</div>
				');
				//
			}
			*/

			$this->output('<div class="qa-main'.(@$this->content['hidden'] ? ' qa-main-hidden' : '').'">');

			// ask teaser on top
			if($this->template=='question')
			{
				$this->output('
					<p style="display:inline-block;margin-bottom:40px;text-align:center;">
						<a class="btnblue" style="margin-bottom:2px;padding:7px 20px;" href="'.qa_path('ask').'">'.qa_lang('booker_lang/lang_askquestion').'</a> <br />
						<span style="font-size:11px;color:#555;">'.qa_lang('booker_lang/lang_withoutregistration').'</span>
					</p>
				');
			}

			$this->widgets('main', 'top');

			$this->page_title_error();

			$this->widgets('main', 'high');

			$this->main_parts($content);

			if( $this->template=='questions' || $this->template=='qa' || $this->template=='activity' || $this->template=='question' )
			{
				// google adsense responsive 
				if(!qa_is_logged_in())
				{
					$this->output('
					<div class="adholder-end">
						<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
						<!-- Kvanto Banner (before related questions) -->
						<ins class="adsbygoogle"
							 style="display:block"
							 data-ad-client="ca-pub-6679343814337183"
							 data-ad-slot="4195981954"
							 data-ad-format="auto"></ins>
						<script>
						(adsbygoogle = window.adsbygoogle || []).push({});
						</script>
					</div>
					');
				}
			}
			
			$this->widgets('main', 'low');

			$this->page_links();
			$this->suggest_next();

			$this->widgets('main', 'bottom');

			// Feedback page
			/*
			if($this->template=='feedback')
			{
				// output contact details below send button on contact form
				$this->output('
					<div class="contactdata_redundant" style="margin-top:50px;">
						'.htmlspecialchars_decode(qa_opt('booker_mailsenderfooter')).'
					</div> <!-- contactdata_redundant -->
				');
			}
			*/

			$this->output('</div> <!-- END qa-main -->', '');

		} // END main


		// override q_view to add instructions after question
		function q_view($q_view)
		{
			// call default method output
			qa_html_theme_base::q_view($q_view);

			// show instruction to user
			if(!empty($q_view))
			{
				if($this->showInstructionsAfterQ)
				{
					// answer exists to question but no best answer selected
					$this->output('
					<div class="remindbest boxv4">
						Išrink <b>&rsaquo;geriausią atsakymą&lsaquo;</b> į jūsų klausimą.<br />
						Neaišku? Parašyk atsakymo komentarą.
					</div>
					');
				}
			}
		} // end q_view

		// override a_list (on question page) to add adsense after question
		function a_list($a_list)
		{
			// insert adsense
			if(!qa_is_logged_in() && ($this->template!='qa' || $this->template=='activity'))
			{
				// GOOGLE ADSENSE NEW - responsive 
				$this->output('
				<div class="adholder-mid">
					<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
					<!-- Kvanto Banner (after question) -->
					<ins class="adsbygoogle"
						 style="display:block"
						 data-ad-client="ca-pub-6679343814337183"
						 data-ad-slot="1242515554"
						 data-ad-format="auto"></ins>
					<script>
					(adsbygoogle = window.adsbygoogle || []).push({});
					</script>
				</div>
				');
				
				/*
				if(qa_is_mobile_probably())
				{
					// google ads for mobiles
					$this->output('
					<div class="adholder-mid">
						<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
						<!-- kvanto Banner 320x100 Middle - Mobile -->
						<ins class="adsbygoogle"
							 style="display:inline-block;width:320px;height:100px"
							 data-ad-client="ca-pub-6679343814337183"
							 data-ad-slot="2622113553"></ins>
						<script>
						(adsbygoogle = window.adsbygoogle || []).push({});
						</script>
					</div>
					');
				} // end qa_is_mobile_probably
				else
				{
					// google ads for desktop
					$this->output('
					<div class="adholder-mid">
						<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
						<!-- kvanto 728x90 Middle -->
						<ins class="adsbygoogle"
							 style="display:inline-block;width:728px;height:90px"
							 data-ad-client="ca-pub-6679343814337183"
							 data-ad-slot="5575579959"></ins>
						<script>
						(adsbygoogle = window.adsbygoogle || []).push({});
						</script>
					</div>
					');
				}
				*/

				// memo: the adsense banner on the left "Adsense Banner 160x600 Left" is inserted by jquery
			} // end adsense

			// call default method output
			qa_html_theme_base::a_list($a_list);

			if(!empty($a_list))
			{
				if($this->showInstructionsAfterQ)
				{
					// answer exists to question but no best answer selected
					$this->output('
					<div class="remindbest boxv1">
						Išrink <b>&rsaquo;geriausią atsakymą&lsaquo;</b> į jūsų užduotą klausimą.<br />
						Neaišku? Parašyk atsakymo komentarą.
					</div>
					');
				}
			}
		} // end a_list

		// override head_title() to add text to the title
		function head_title()
		{
			$pagetitle=strlen($this->request) ? strip_tags(@$this->content['title']) : '';
			$headtitle=(strlen($pagetitle) ? ($pagetitle.' | ') : '').qa_opt('site_title'); // 'kvanto'

			if($this->template=='qa' || $this->template=='activity' || $this->template=='booker contractorlist')
			{
				// TITLE
				$headtitle = qa_lang('booker_lang/headtitle');
			}
			$this->output('<title>'.$headtitle.'</title>');
		}

		function head_script()
		{
			// insert jquery CDN script
			if (isset($this->content['script']))
			{
				foreach ($this->content['script'] as $scriptline)
				{
					// dont load qa-question.js since the code is included with qa-theme/booker/js/qa-page.js
					if(strpos($scriptline, 'qa-question.js') !== false)
					{
						continue;
					}
					// dont load qa-page.js since the code is included with qa-theme/booker/js/qa-page.js
					if(strpos($scriptline, 'qa-page.js') !== false)
					{
						continue;
					}

					// load CDN instead of local jquery file, with js fallback
					if(strpos($scriptline, 'jquery') === false)
					{
						$this->output_raw($scriptline);
					}
					else
					{
						$this->output_raw('<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js" type="text/javascript"></script>   <script type="text/javascript">window.jQuery || document.write(\'<script src="https://www.matheretter.de/tools/jquery.min.js"><\/script>\')</script>');
					}
				}
				// add custom qa-page.js
				$this->output('<script src="'.$this->rooturl.'js/qa-page.js" type="text/javascript"></script>');
			}
			// no default call
		} // end head_script

		// abstract method
		public function body_suffix()
		{
			// teaser dialog-box to anonymous user to ask question, triggered when scrolled
			// do not show to loggedin users and show only on question pages
			if( !qa_is_logged_in() && ($this->template=='question') )
			{
				$this->output('
				<div id="dialog-box">
					<p style="margin:22px 0 25px 0;">
						Ne tas, kurio ieškai?<br />
						<a class="btnblue" style="margin-bottom:2px;" href="'.qa_path('ask').'">'.qa_lang('booker_lang/lang_askquestion').'</a> <br />
						<span style="font-size:11px;">'.qa_lang('booker_lang/lang_withoutregistration').'</span>
					</p>
					<div id="closeDiv">x</div>
				</div>
				');
			}

			// output lightbox for image popup at end of body, pseudo image data as img src for valid html (causing no server request)
			$this->output('
			<div id="lightbox-popup"> <div id="lightbox-center">  <img id="lightbox-img" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D" alt="Lightbox" />  </div> </div>
			');

			$this->output('
				<div class="madeby">
					<a href="https://www.memelpower.com/lt/showcase/kvanto/">
						<span>Made by</span>
						<strong>Memelpower</strong>
					</a>
				</div>
			');
		} // body_suffix()

		// abstract method
		function head_custom()
		{
			// default call
			qa_html_theme_base::head_custom();

			// TRACKING
			$this->output("
			<script>
			  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

			  ga('create', 'UA-44847862-1', 'auto');
			  ga('send', 'pageview');

			</script>
			");

			$this->output('
            <link href="https://file.myfontastic.com/mMYoqwpeHQdYQGKcotaRZQ/icons.css" rel="stylesheet">
            ');

			$this->output('
			<link rel="apple-touch-icon" sizes="57x57" href="'.qa_path('favicons').'/apple-touch-icon-57x57.png">
			<link rel="apple-touch-icon" sizes="60x60" href="'.qa_path('favicons').'/apple-touch-icon-60x60.png">
			<link rel="apple-touch-icon" sizes="72x72" href="'.qa_path('favicons').'/apple-touch-icon-72x72.png">
			<link rel="apple-touch-icon" sizes="76x76" href="'.qa_path('favicons').'/apple-touch-icon-76x76.png">
			<link rel="apple-touch-icon" sizes="114x114" href="'.qa_path('favicons').'/apple-touch-icon-114x114.png">
			<link rel="apple-touch-icon" sizes="120x120" href="'.qa_path('favicons').'/apple-touch-icon-120x120.png">
			<link rel="apple-touch-icon" sizes="144x144" href="'.qa_path('favicons').'/apple-touch-icon-144x144.png">
			<link rel="apple-touch-icon" sizes="152x152" href="'.qa_path('favicons').'/apple-touch-icon-152x152.png">
			<link rel="apple-touch-icon" sizes="180x180" href="'.qa_path('favicons').'/apple-touch-icon-180x180.png">
			<link rel="icon" type="image/png" href="'.qa_path('favicons').'/favicon-32x32.png" sizes="32x32">
			<link rel="icon" type="image/png" href="'.qa_path('favicons').'/android-chrome-192x192.png" sizes="192x192">
			<link rel="icon" type="image/png" href="'.qa_path('favicons').'/favicon-96x96.png" sizes="96x96">
			<link rel="icon" type="image/png" href="'.qa_path('favicons').'/favicon-16x16.png" sizes="16x16">
			<link rel="manifest" href="'.qa_path('favicons').'/manifest.json">
			<link rel="shortcut icon" href="'.qa_path('favicons').'/favicon.ico">
			<meta name="msapplication-TileColor" content="#da532c">
			<meta name="msapplication-TileImage" content="'.qa_path('favicons').'/mstile-144x144.png">
			<meta name="msapplication-config" content="'.qa_path('favicons').'/browserconfig.xml">
			<meta name="theme-color" content="#ffffff">
			');
			// <link rel="mask-icon" href="'.qa_path('favicons').'/safari-pinned-tab.svg" color="#5bbad5">

			// kvanto: <meta name="verify-paysera" content="692fc163c28721254dc6589f3acab542">
			/*
			$this->output('
				<meta name="verify-paysera" content="d24d3f1e5d8732207a90c54c9ab2844b">
			');
			*/

		} // end head_custom

		// add logo next to title
		public function logo()
		{
			$this->output(
				'<div class="qa-logo">
					<a href="'.qa_path_to_root().'">
						<img src="'.$this->rooturl.$this->mainlogo.'" alt="logo" />
					</a>
				</div>'
			);
		}

		// override to add more META
		function head_metas()
		{
			// call default method output
			qa_html_theme_base::head_metas();

			// ADDED
			// <meta charset="utf-8">
			$this->output('
			<meta name="viewport" content="width=device-width, initial-scale=1" />
			<meta name="robots" content="index,follow" />
			');

			/*
			if($this->template=='activity')
			{
				// meta description startpage
				// *** $this->output('<meta name="description" content="Iškilo klausimas? Ekspertai padės išspręsti iškilusias problemas: sveikata, namai, teisė, vaikai, laisvalaikis, maistas, darbas, grožis, gyvenimas." />');
				$this->output('<meta name="description" content="Buche dir wen du möchtest, wann du möchtest… Finde jederzeit den persönlichen Service, den du brauchst." />');
			}
			*/

		} // end head_metas

		// override to mark duplicate question in questions list
		function q_item_title($q_item)
		{
			// init string
			$titleExtended = '';
			$isDup = false;
			// check for closed question
			$closed = (@$q_item['raw']['closedbyid'] !== null);

			if($closed)
			{
				// check if duplicate
				$closedByQu = qa_db_read_one_value( qa_db_query_sub('SELECT postid FROM `^posts`
																		WHERE `postid` = #
																		AND `type` = "Q"
																		;', $q_item['raw']['closedbyid']), true );
				if(isset($closedByQu))
				{
					// $titleExtended = '<a href="'.qa_path_html(qa_q_request($duprow['postid'], $duprow['title']), null, qa_opt('site_url'), null, null).'">'.$duprow['title'].'</a>';
					$titleExtended = '<span class="qa-q-duplicate">&laquo; '.qa_lang('booker_lang/lang_qu_alreadyexists').'</span>'; // Duplikat
					$isDup = true;
				}
				else
				{
					$titleExtended = '<span class="qa-q-closed" title="'.qa_lang('booker_lang/lang_qu_closed').'"></span>';
				}
			}
			// css to change color of question title
			$xtraCSS = '';
			if($isDup)
			{
				$xtraCSS = ' style="color:#9AB;"';
			}

			// show view count only for admin
			$viewcount = '';
			if(isset($q_item['raw']['views']) && qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN)
			{
				$viewcount = '<span style="font-size:12px;color:#AAA;margin-left:3px;">'.$q_item['raw']['views'].'</span>';
			}

			// output in question list
			if(isset($q_item['url']))
			{
				$this->output('
					<div class="qa-q-item-title">
						<a href="'.$q_item['url'].'"'.$xtraCSS.'>'.$q_item['title'].'</a>'.
						$titleExtended.
						$viewcount.
					'</div>'
				);
			}
			else
			{
				// error_log('# remove from qa_posttags '.$q_item['raw']['postid']);
			}
		} // end q_item_title

		// override for pagination questions on activity page
		function page_links()
		{
			// call default method output
			qa_html_theme_base::page_links();

			// q2apro added: pagination on qa page
			if($this->template=='qa' || $this->template=='activity') {
				$this->output('<div class="qa-page-links">');
				$this->output('
					<span>Daugiau klausimų:</span>
					<ul class="qa-page-links-list" style="margin-top:10px;">
						<li class="qa-page-links-item">
							<a href="./questions?start=0" class="qa-page-link">1</A>
						</li>
						<li class="qa-page-links-item">
							<a href="./questions?start=30" class="qa-page-link">2</A>
						</li>
						<li class="qa-page-links-item">
							<a href="./questions?start=60" class="qa-page-link">3</A>
						</li>
						<li class="qa-page-links-item">
							<a href="./questions?start=90" class="qa-page-link">4</A>
						</li>
						<li class="qa-page-links-item">
							<span class="qa-page-ellipsis">...</span>
						</li>
					</ul>
				');
				$this->page_links_clear();
				$this->output('</div>');
			}
		} // end page_links

		function footer()
		{
			$this->output('
				</div>  <!-- end content-wrapper -->
				<div style="clear:both;"></div>
			');



            if($this->template=='booker contractorlist')
            {
                $this->output( $this->startpage_features() );
            }



			$this->output('
			<div class="footer-wrapper">
				<div class="qa-footer">
					<div class="qa-nav-footer">
						<a href="'.qa_path('about').'">'.qa_lang('booker_lang/lang_footer_about').'</a> |
						<a href="'.qa_path('terms').'">'.qa_lang('booker_lang/lang_footer_terms').'</a> |
						<a href="'.qa_path('legal').'">'.qa_lang('booker_lang/lang_footer_legal').'</a> |
						<a href="'.qa_path('feedback').'">'.qa_lang('booker_lang/lang_footer_contacts').'</a>
						<a href="'.qa_path('premium').'">'.qa_lang('booker_lang/lang_footer_premium').'</a>
					</div>
			');

			$this->footer_clear();

			$this->output('
				</div> <!-- END qa-footer -->
			</div> <!-- END footer-wrapper -->
			');

			// Rotate image feature for Redakteure
			if(qa_get_logged_in_level() >= QA_USER_LEVEL_EDITOR)
			{
				$this->output("
				<script type=\"text/javascript\">
					$(document).ready(function(){
						$('.entry-content img').each( function( index, element ){
							var imgsrc = $(this).attr('src');
							var imglink = imgsrc.substring(imgsrc.indexOf('blobid')+7, imgsrc.length);
							// make sure only images from our website
							if(imgsrc.indexOf('kvanto')>0 || imgsrc.indexOf('kyga.de')>0 ) {
								$(this).wrap('<div class=\"contentimg\"></div>');
								$(this).after('<a target=\"_blank\" class=\"rotateimg\" href=\"".qa_path('rotateimage')."/?id='+imglink+'\">rotate</a>');
							}
						});
					});
				</script>
				");
			}

		} // end footer

		// SHAREBOX below question content
		function q_view_buttons($q_view)
		{
			$shareUrl = qa_q_path($this->content['q_view']['raw']['postid'], $this->content['q_view']['raw']['title'], true);
			$shareUrlEnc = urlencode($shareUrl);

			// qid link only
			$shareUrl = qa_opt('site_url').$q_view['raw']['postid'];

			$this->output('
			<div class="sharebox">
				<a class="shlink tooltipS" title="'.qa_lang('booker_lang/lang_shortlink').'" href="'.$shareUrl.'"></a>
				<a class="shprint tooltipS" title="'.qa_lang('booker_lang/lang_print').'" href="javascript:window.print();"></a>
				<a class="shfb tooltipS" title="'.qa_lang('booker_lang/lang_sharefacebook').'" href="https://www.facebook.com/sharer.php?u='.$shareUrlEnc.'"></a>
				<a class="shgp tooltipS" title="'.qa_lang('booker_lang/lang_sharegoogleplus').'" href="https://plus.google.com/share?url='.$shareUrlEnc.'"></a>
			</div>');


			// default method call
			qa_html_theme_base::q_view_buttons($q_view);


			// display the recent questions of this user to all logged-in users, not anonymous
			if(qa_is_logged_in())
			{
				$usertrackable = isset($q_view['raw']['userid']) || isset($q_view['raw']['createip']);

				if(isset($q_view['raw']['userid']))
				{
					// get questions of logged-in user
					$qs_anonym = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT postid,title,selchildid,closedbyid,acount,created FROM ^posts
														WHERE userid = #
														AND `type`="Q"
														AND created > NOW() - INTERVAL 7 DAY
														ORDER BY created DESC
														LIMIT 7
														', $q_view['raw']['userid'])
													 );
				}
				// anonymous
				else if(isset($q_view['raw']['createip']))
				{
					// get questions of anonymous user, exclude admin
					$qs_anonym = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT postid,title,selchildid,closedbyid,acount,created FROM ^posts
														WHERE createip = INET_ATON(#)
														AND `type`="Q"
														AND created > NOW() - INTERVAL 7 DAY
														ORDER BY created DESC
														LIMIT 7
														', $q_view['raw']['createip'])
													 );
				}

				// show question list if there is at least 2 questions
				if($usertrackable && count($qs_anonym)>1)
				{
					$questionList = '';
					foreach($qs_anonym as $quItem)
					{
						$bestAselected = isset($quItem['selchildid']); // best answer selected
						$closedQ = (isset($quItem['closedbyid'])) ? $quItem['closedbyid'] : ''; // do we have a closed question, probably duplicate
						$qLink = qa_path_html(qa_q_request($quItem['postid'], $quItem['title']), null, qa_opt('site_url'), null, null); // get correct public URL

						$questionList .= '
							<li>
								<a href="'.$qLink.'">'.htmlspecialchars($quItem['title']).'</a>';
						if(!$closedQ)
						{
							$questionList .= ' ('.$quItem['acount'].')';
							if($bestAselected)
							{
								$questionList .= ' ✔'; // checkmark &#x2714;
							}
						}
						else
						{
							$questionList .= ' ('.qa_lang('booker_lang/lang_qu_closed').')';
						}
						// add time
						$qcreatedtime = implode('', qa_when_to_html( strtotime($quItem['created']), qa_opt('show_full_date_days')));
						$questionList .= '&nbsp; <span class="q-ano-item-meta">'.$qcreatedtime.'</span>';
						$questionList .= '
							</li>
							';
					} // end foreach

					$usertitle = isset($q_view['raw']['userid']) ? qa_lang('booker_lang/lang_thismember') : qa_lang('booker_lang/lang_thisguest');

					$this->output('
						<div class="anoqulistholder">
							<p>
								'.$usertitle.' '.qa_lang('booker_lang/lang_wrotethosequ').':
							</p>
							<ul class="anoqulist">
								'.$questionList.'
							</ul>
						</div>
					');
				} // end count($qs_anonym)>0
			} // end qa_is_logged_in()

		} // end q_view_buttons

		function a_count($post)
		{
			// default method call
			qa_html_theme_base::a_count($post);

			// output tiny thumbs class "quvotes" with count next to answer count in questions list
			if(isset($post['vote_view']) && $this->template!='question')
			{
				$quvotes = $post['raw']['upvotes'];
				if($quvotes>0)
				{
					$this->output('<span title="'.qa_lang('booker_lang/lang_pointsforqu').'" class="quvotes">'.$post['raw']['upvotes'].'</span>');
				}
			}
		}

		// override for best answer button
		function a_selection($post)
		{
			$this->output('<div class="qa-a-selection">');

			if (isset($post['select_tags']))
				$this->post_hover_button($post, 'select_tags', qa_lang('booker_lang/lang_bestanswer'), 'qa-a-select'); // eetv added: $value=Beste Antwort? instead '' (empty)
			elseif ( isset($post['unselect_tags']) && (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN) ) // eetv: option to unselect best answer only for admin
				$this->post_hover_button($post, 'unselect_tags', '', 'qa-a-unselect');
			elseif ($post['selected'])
				$this->output('<div class="qa-a-selected">&nbsp;</div>');

			if (isset($post['select_text']))
				$this->output('<div class="qa-a-selected-text">'.@$post['select_text'].'</div>');

			$this->output('</div>');

		} // end a_selection

		// override to add fontawesome icon
		public function error($error)
		{
			if (strlen($error)) {
				$this->output(
					'<div class="qa-error">',
					'<i class="fa fa-exclamation-triangle"></i>',
					$error,
					'</div>'
				);
			}
		}

		public function finish()
		{
			/*if ($this->indent) {
				echo "<!--\nIt's no big deal, but your HTML could not be indented properly. To fix, please:\n".
					"1. Use this->output() to output all HTML.\n".
					"2. Balance all paired tags like <td>...</td> or <div>...</div>.\n".
					"3. Use a slash at the end of unpaired tags like <img/> or <input/>.\n".
					"Thanks!\n-->\n";
			}*/
		}


		/* CUSTOM FUNCTIONS */

		// list all questions of user
		function listAllUserQuestions($allQu)
		{
			$totalQu = count($allQu);
			$countQuSel = 0;

			$questionList = '<ul>';
			foreach($allQu as $quItem)
			{
				$qTitle = (isset($quItem['title'])) ? $quItem['title'] : ''; // each Q should have a title actually
				$bestAselected = isset($quItem['selchildid']); // best answer selected
				$closedQ = (isset($quItem['closedbyid'])) ? $quItem['closedbyid'] : ''; // do we have a closed question, probably duplicate
				$qLink = qa_path_html(qa_q_request($quItem['postid'], $qTitle), null, qa_opt('site_url'), null, null); // get correct public URL

				$questionList .= '
				<li>
					<a href="'.$qLink.'">'.htmlspecialchars($qTitle).'</a>';
				if(!$closedQ)
				{
					// how many answers
					/*$aCount = qa_db_read_one_value( qa_db_query_sub('SELECT COUNT(*) FROM `^posts`
																			WHERE `parentid` = #
																			AND `type` = "A"
																		', $quItem['postid']) );
					*/
					$aString = ($quItem['acount']==1) ? qa_lang('booker_lang/lang_answer') : qa_lang('booker_lang/lang_answers');
					if($bestAselected) {
						$questionList .= '&nbsp;✔'; // checkmark &#x2714;
						$countQuSel++;
					}
					else {
						$questionList .= ' &nbsp;('.$quItem['acount'].' '.$aString.')';
					}
				}
				else
				{
					// check closedby to see if just closed (type=NOTE) or if its duplicate-closed (type=Q)
					$closedByQu = qa_db_read_one_value( qa_db_query_sub('SELECT postid FROM `^posts`
																			WHERE `postid` = #
																			AND `type` = "Q"
																			;', $quItem['closedbyid']), true );
					$questionList .= isset($closedByQu) ? ' — <span style="color:#F00;">'.qa_lang('booker_lang/lang_existsalready').'</span>' : ' — <span style="color:#55F;">'.qa_lang('booker_lang/lang_qu_closed').'</span>';
				}
				$questionList .= '</li>'; // close list item
			}
			$questionList .= '</ul>';

			$hintSelBest = '';

			return '
				<div class="anonymQuestList">
					'.qa_lang('booker_lang/lang_yourquestions').': ' . $questionList . $hintSelBest . '
				</div>
				';
		} // end listAllUserQuestions

		// userid, question count and selected count
		function q2apro_questions_stats($handle)
		{
			$sql_count =
				'SELECT u.userid, count(p.postid) AS qs, count(p.selchildid) AS selected
				 FROM ^users u
				   LEFT JOIN ^posts p ON u.userid=p.userid AND p.type="Q"
				 WHERE u.handle=$';
			$result = qa_db_query_sub($sql_count, $handle);
			$row = qa_db_read_one_assoc($result);

			return array( $row['userid'], $row['qs'], $row['selected'] );
		}

		// userid, answer count and selected count
		private function q2apro_answer_stats($handle)
		{
			$sql_count =
				'SELECT u.userid, COUNT(a.postid) AS qs, SUM(q.selchildid=a.postid) AS selected
				 FROM ^users u
				   LEFT JOIN ^posts a ON u.userid=a.userid AND a.type="A"
				   LEFT JOIN ^posts q ON a.parentid=q.postid AND q.type="Q"
				 WHERE u.handle=$';
			$result = qa_db_query_sub($sql_count, $handle);
			$row = qa_db_read_one_assoc($result);

			if ( $row['selected'] == null )
				$row['selected'] = 0;

			return array( $row['userid'], $row['qs'], $row['selected'] );
		}

		// userid, comment count
		private function q2apro_comments_stats($handle)
		{
			$sql_count =
				'SELECT u.userid, COUNT(a.postid) AS qs
				 FROM ^users u
				   LEFT JOIN ^posts a ON u.userid=a.userid AND a.type="C"
				 WHERE u.handle=$';
			$result = qa_db_query_sub($sql_count, $handle);
			$row = qa_db_read_one_assoc($result);

			return array( $row['userid'], $row['qs'] );
		}

		// http://stackoverflow.com/a/10472259/1066234
		private function fromDecimalToBase($in, $to)
		{
			$in = (string) $in;
			$out = '';

			for ($i = strlen($in) - 1; $i >= 0; $i--)
			{
				$out = base_convert(bcmod($in, $to), 10, $to) . $out;
				$in = bcdiv($in, $to);
			}

			return preg_replace('/^0+/', '', $out);
		}

		private function fromBaseToDecimal($in, $from)
		{
			$in = (string) $in;
			$out = '';

			for ($i = 0, $l = strlen($in); $i < $l; $i++) {
				$x = base_convert(substr($in, $i, 1), $from, 10);
				$out = bcadd(bcmul($out, $from), $x);
			}

			return preg_replace('/^0+/', '', $out);
		}

		private function q2apro_extract_blobid_from_string($text)
		{
			if(empty($text))
			{
				return null;
			}

			$allBlobURLs = [];

			// find qa-blob in content (take first one, that is the profile image of the interview)
			if(strpos($text,'qa_blobid=') !== false)
			{
				// get all URLs
				$urls = $this->helper_get_all_images_from_string($text);
				foreach($urls as $urln)
				{
					if(strpos($urln,'qa_blobid=') !== false)
					{
						// found blobid add link to array
						$allBlobURLs[] = $urln; // str_replace("&amp;", "&", trim($urln));
						// $this->output($urln);
					}
				}
			}
			// we found blobURLs
			if(count($allBlobURLs)>0)
			{
				// remove duplicates from array and go over all items
				foreach($allBlobURLs as $blobURL)
				{
					// extract the blobid from the blobURL
					$urlArray = explode('=',$blobURL);
					$blobid = $urlArray[sizeof($urlArray)-1];

					if(!empty($blobid))
					{
						/*
						$imgURL = '/?qa=image&qa_blobid='.$blobid.'&qa_size=350';
						$imgEmbed = '<img src="/?qa=image&qa_blobid='.$blobid.'&qa_size=250" class="taginterview-img" style="width:auto;border:1px solid #DDD;" />';
						*/
						// stop foreach, we just want the first image
						// break;
						return $blobid;
					}
				}
			}
			/*
			$this->output('
				<a class="interview_imga" href="'.$q_item['url'].'" style="background:url(\''.$imgURL.'\') no-repeat top center;">
					<span class="exp-img-caption" style="font-size:11px;">
						'.$thispost['views'].' perž. &ensp; '.date('Y.m.d', strtotime($thispost['created'])).'
					</span>
				</a>
			');
			*/
			return null;
		} // END q2apro_extract_blobid_from_string

		private function helper_get_urls($string)
		{
			//$regex = '/https?\:\/\/[^\" ]+/i';
			$regex = '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';
			preg_match_all($regex, $string, $matches);
			return $matches[0];
		}

		private function helper_get_all_images_from_string($string)
		{
			$dom = new DOMDocument();
			$html = $dom->loadHTML($string);
			$links = $dom->getElementsByTagName('img');
			$imagelinks = [];
			foreach($links as $imgurl)
			{
				$imagelinks[] = $imgurl->getAttribute('src');
			}
			// only links that hold "blobid"
			return $imagelinks;
		}

	} // END CLASS


/*
	Omit PHP closing tag to help avoid accidental output
*/

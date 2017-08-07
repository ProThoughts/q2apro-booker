<?php
/*
	Plugin Name: BOOKER
*/

	class qa_html_theme_layer extends qa_html_theme_base
	{

		public function favorite_button($tags, $class)
		{
			if( ($this->template=='user' && !qa_clicked('doaccount')) || $this->request=='booking' )
			{
				if(isset($tags) && isset($this->content['raw']['booking']['realname']))
				{
					$username = $this->content['raw']['booking']['realname'];

					$favbuttonlabel = '';
					if($class=='qa-favorite')
					{
						$favbuttonlabel = str_replace('~username~', $username, qa_lang('booker_lang/irecommend'));
					}
					else if($class=='qa-unfavorite')
					{
						$favbuttonlabel = qa_lang('booker_lang/unrecommend');
					}
					$this->output('<input '.$tags.' type="submit" value="'.$favbuttonlabel.'" class="'.$class.'-button"/> ');
				}
			}
			else
			{
				// call default method output
				qa_html_theme_base::favorite_button($tags, $class);
			}
		}

		public function page_title_error()
		{
			$username = '';
			// change headline in forum userprofile pages, set realname instead userhandle
			$handlepages = array('user', 'user-activity', 'user-questions', 'user-answers');
			if(in_array($this->template, $handlepages))
			{
				$handle = $this->q2apro_gethandle_from_url();
				$profileuserid = qa_handle_to_userid($handle);
				$questioncount = 0;
				if(isset($this->content['q_list']['qs']))
				{
					$questioncount = count($this->content['q_list']['qs']);
				}

				if(isset($profileuserid))
				{
					$username = booker_get_realname($profileuserid, true); // $this->content['form_profile']['fields']['name']['value'];
					if(!empty($username))
					{
						// save global
						$this->content['raw']['booking']['realname'] = $username;

						if($this->template=='user' && !qa_clicked('doaccount'))
						{
							// headline becomes username
							$this->content['title'] = $username;
						}
						else if($this->template=='user-activity')
						{
							// replacing username here
							if($questioncount>0)
							{
								$this->content['title'] = qa_lang_html_sub('profile/recent_activity_by_x', $username);
							}
							else
							{
								$this->content['title'] = qa_lang_html_sub('profile/no_posts_by_x', $username);
							}
						}
						else if($this->template=='user-questions')
						{
							if($questioncount>0)
							{
								$this->content['title'] = qa_lang_html_sub('profile/questions_by_x', $username);
							}
							else
							{
								$this->content['title'] = qa_lang_html_sub('profile/no_questions_by_x', $username);
							}
						}
						else if($this->template=='user-answers')
						{
							if($questioncount>0)
							{
								$this->content['title'] = qa_lang_html_sub('profile/answers_by_x', $username);
							}
							else
							{
								$this->content['title'] = qa_lang_html_sub('profile/no_answers_by_x', $username);
							}
						}
					}
				}
			}

			// user should not favorite himself
			if($this->template=='user' && !qa_clicked('doaccount') && !empty($username))
			{
				if(isset($this->content['raw']['account']['userid']))
				{
					$userid = $this->content['raw']['account']['userid'];
					if($userid != qa_get_logged_in_userid())
					{
						$favorite=@$this->content['favorite'];
						if(isset($favorite))
						{
							/*
							if(isset($favorite['favorite_add_tags']))
							{
								// add label to button
								$favorite['favorite_add_tags'] .= ' value="'.$username.' empfehlen"';
							}
							if(isset($favorite['favorite_remove_tags']))
							{
								// add label to button
								$favorite['favorite_remove_tags'] .= ' value="Empfehlung zurückziehen?"';
							}
							*/

							/*
							$this->output('<form '.$favorite['form_tags'].'>');
							$this->output('<div class="qa-favoriting" '.@$favorite['favorite_tags'].'>');
							$this->favorite_inner_html($favorite);
							// security code
							$formhidden = isset($favorite['form_hidden']) ? $favorite['form_hidden'] : null;
							$this->form_hidden_elements($formhidden);
							$this->output('</div>');
							$this->output('</form>');
							*/

							// SPECIAL JS
							$this->output("
							<script type=\"text/javascript\">
							$(document).ready(function()
							{
								$('.qa-favorite-button, .qa-unfavorite-button').click( function()
								{
									var clicked = $(this);

									// insert loading indicator
									clicked.parent().after('<span id=\"qa-waiting-template\" class=\"qa-waiting\" style=\"display:inline-block;\">...</span>');

									clicked.parent().hide();

									// wait 1 sec to make sure the core ajax call goes through
									setTimeout( function()
									{
										clicked.remove();
										// reload page
										window.location.href = '".qa_self_html()."';
									}, 1000);
								});

								// favorite-button is inside h1 element by default, get it out there
								/*var favbtn = $('#favoriting').detach();
								$('h1').prepend(favbtn);
								// $('.qa-nav-sub').before(favbtn);
								*/
								// var favbtn = $('.favoriteform').detach();

							});
							</script>
							");

							// see CSS in styles.css
						} // end isset($favorite)
					} // end is not user himself
				} // end userid exists
			} // end user template


			// default call
			qa_html_theme_base::page_title_error();
		}

		// change title of page from username to realname
		public function head_title()
		{
			// change h1 which is the username
			if($this->template=='user' && !qa_clicked('doaccount'))
			{
				// $userhandle = $this->content['form_profile']['fields']['name']['value'];
				$userid = $this->content['raw']['account']['userid'];
				if(isset($userid))
				{
					$username = booker_get_realname($userid, true);
					if(!empty($username))
					{
						$this->content['title'] = $username;
					}
				}
			}
			// default call
			qa_html_theme_base::head_title();
		}

		function main_parts($content)
		{
			// custom user profile page - NOT for admin (to see all options and fields)

			// check if user profile template and if not clicked on edit
			if($this->template=='user' && !qa_clicked('doaccount'))
			{
				$duration = $content['form_profile']['fields']['duration']['value'];
				$duration_label = $content['form_profile']['fields']['duration']['label'];
				$level = $content['form_profile']['fields']['level']['value'];
				$level_label = $content['form_profile']['fields']['level']['label'];
				$username = '';
				$username_label = '';
				$location = '';
				$location_label = '';
				$website = '';
				$website_label = '';
				$about = '';
				$about_label = '';

				if(isset($content['form_profile']['fields']['name']['value']))
				{
					$username = $content['form_profile']['fields']['name']['value'];
					$username_label = $content['form_profile']['fields']['name']['label'];
				}
				if(isset($content['form_profile']['fields']['location']['value']))
				{
					$location = $content['form_profile']['fields']['location']['value'];
					$location_label = $content['form_profile']['fields']['location']['label'];
				}
				if(isset($content['form_profile']['fields']['about']['value']))
				{
					$about = $content['form_profile']['fields']['about']['value'];
					$about_label = $content['form_profile']['fields']['about']['label'];
				}

				// admin
				// $lastlogin = $content['form_profile']['fields']['lastlogin']['value']; // can be undefined
				// $lastlogin_label = $content['form_profile']['fields']['lastlogin']['label'];
				// $lastwrite = $content['form_profile']['fields']['lastwrite']['value']; // can be undefined
				// $lastwrite_label = $content['form_profile']['fields']['lastwrite']['label'];

				$userid = $content['raw']['account']['userid'];
				$email = $content['raw']['account']['email'];
				$level = $content['raw']['account']['level'];
				$handle = $content['raw']['account']['handle'];
				$created = $content['raw']['account']['created'];
				$flags = $content['raw']['account']['flags'];
				$avatarblobid = $content['raw']['account']['avatarblobid'];
				// $points = $content['raw']['account']['points'];
				// $wallposts = $content['raw']['account']['wallposts'];

				// admin
				$loggedin = $content['raw']['account']['loggedin'];
				$loginip = $content['raw']['account']['loginip'];
				$written = $content['raw']['account']['written'];
				$writeip = $content['raw']['account']['writeip'];
				// stats + points
				$points = $content['raw']['points']['points'];
				$qposts = $content['raw']['points']['qposts'];
				$aposts = $content['raw']['points']['aposts'];
				$cposts = $content['raw']['points']['cposts'];
				$aselects = $content['raw']['points']['aselects'];
				$aselecteds = $content['raw']['points']['aselecteds'];
				$qupvotes = $content['raw']['points']['qupvotes'];
				$qdownvotes = $content['raw']['points']['qdownvotes'];
				$aupvotes = $content['raw']['points']['aupvotes'];
				$adownvotes = $content['raw']['points']['adownvotes'];
				$qvoteds = $content['raw']['points']['qvoteds'];
				$avoteds = $content['raw']['points']['avoteds'];
				$upvoteds = $content['raw']['points']['upvoteds'];
				$downvoteds = $content['raw']['points']['downvoteds'];
				$bonus = $content['raw']['points']['bonus'];
				$rank = $content['raw']['rank'];

				// remove blocks if not admin
				if(qa_get_logged_in_level()<QA_USER_LEVEL_ADMIN)
				{
					unset($content['form_profile']); // also holds edit-my-profile button
					unset($content['raw']['account']);
					unset($content['raw']['points']);
					unset($content['raw']['rank']);
					unset($content['form_activity']); // right stats field
					unset($content['message_list']); // wall field
				}

				// statistic labels
				$aposts_label = $aposts==1 ? qa_lang('booker_lang/answer') : qa_lang('booker_lang/answers');
				$qposts_label = $qposts==1 ? qa_lang('booker_lang/question') : qa_lang('booker_lang/questions');
				$cposts_label = $cposts==1 ? qa_lang('booker_lang/comment') : qa_lang('booker_lang/comments');

				// custom version using table qa_booking_users
				$userdata = booker_getfulluserdata($userid);

				$username = '';
				if(isset($userdata['realname']))
				{
					$username = $userdata['realname'];
				}
				$location = '';
				if(isset($userdata['address']))
				{
					$location = $userdata['address'];
				}
				$service = '';
				if(isset($userdata['service']))
				{
					$service = $userdata['service'];
				}
				$about = '';
				if(isset($userdata['portfolio']))
				{
					$about = $userdata['portfolio'];
					// line breaks to whitespace
					// $about = strip_tags(str_replace('<', ' <', $userdata['portfolio']));
					$about = str_replace('<p>', ' ', $about);
					$about = str_replace('</p>', ' ', $about);
					$about = str_replace('<br>', ' ', $about);
					$about = str_replace('<br />', ' ', $about);
					$about = strip_tags($about);
					$about = helper_shorten_text($about, 250);
				}

				/*
				if($metaItem['title']=='contact')
				{
					$contactdata = $metaItem['content'];

					// extract email from string
					preg_match("/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/i", $contactdata, $matches);
					if(isset($matches[0]))
					{
						$email = $matches[0];
						// check if contact contains email address, replace it if necessary to obfuscate from spambots
						$pattern = '/[^@\s]*@[^@\s]*\.[^@\s]*      /';
						$replacement = str_replace('@', '&#64;', $email); // replace at sign with html representation
						$replacement = ''.str_replace('.', '&#46;', $replacement); // replace period with html representation
						$contact = preg_replace($pattern, $replacement, $contactdata);
					}
				}
				else if($metaItem['title']=='service')
				{
					// klaustukai specific:
					$service = str_replace('"', '', $metaItem['content']); // remove offers!
				}
				*/

				// MORE USER STUFF
				// get last 5 answers a user posted
				$maxansws = 5;
				$userAnswers = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT postid, parentid, content, format, upvotes, created FROM `^posts`
														WHERE `userid` = #
														AND `type` = "A"
														ORDER BY `created` DESC
														LIMIT #
														;', $userid, $maxansws) ); // title,content
				// get parents
				$answlist = '';
				foreach($userAnswers as $answer)
				{
					// get parents == questions
					$quTitle = qa_db_read_one_value( qa_db_query_sub('SELECT title FROM `^posts`
																		WHERE `postid` = #
																		AND `type` = "Q"
																		LIMIT 1',
																		$answer['parentid']), true );
					if(isset($quTitle))
					{
						// $answlist .= qa_q_path($answer['postid'], $answer['title'], true).'<br />';
						// $answlist .= qa_q_path_html($answer['postid'], $answer['title'], true).'<br />';
						$url = qa_path(qa_q_request($answer['parentid'], $quTitle), null, null, null, null);
						$linkToPost = $url.'?show='.$answer['postid'].'#a'.$answer['postid'];

						$previewTxtLen = 250;
						$contentPreview = strip_tags(mb_substr(qa_viewer_text($answer['content'], $answer['format']), 0, $previewTxtLen, 'utf-8')); // shorten question title

						if(strlen($answer['content']) > $previewTxtLen)
						{
							$contentPreview .= '…';
						}

						$whenhtml = qa_html(qa_time_to_string(qa_opt('db_time')-strtotime($answer['created'])));
						$when = qa_lang_html_sub('main/x_ago', $whenhtml);

						$answlist .= '<p class="pro_answitem">
										<span class="upro_answwhen">'.$when.'</span> <br />
										<a class="upro_answl_qutitle" href="'.$linkToPost.'">'.qa_html($quTitle).'</a> <br />
										<span class="upro_answpreview">'.qa_html($contentPreview).'</span>
									</p>';
					}
				}
				if(!empty($answlist))
				{
					$answlist = '<p style="font-size:17px;">'.qa_lang('booker_lang/newestanswers').'</p> '.$answlist;
				}
				else
				{
					$answlist = '<p style="font-size:17px;">'.qa_lang('booker_lang/noansweryet').'</p>';
				}

				// OUTPUT PROFILE
				$procontent = '<div class="custom-userprofile">';

				$isadmin = qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN;
				if($isadmin && !(contractorisapproved($userid) && contractorisavailable($userid)))
				{
					// link to userprofile
					$procontent .= '
						<p style="text-align:right;">
							<a class="defaultbutton btn_graylight buttonthin" style="margin:10px 0 0 0;font-size:11px;" href="'.qa_path('userprofile').'?userid='.$userid.'">'.qa_lang('booker_lang/profile').'</a>
						</p>
					';
				}

				if(is_null($avatarblobid))
				{
					$avatarblobid = qa_opt('avatar_default_blobid');
				}
				$points_label = qa_lang('booker_lang/reputation'); // qa_lang('profile/score');
				$avatarsize = 250; // qa_opt('avatar_profile_size');

				$procontent .= '<div class="upro_left">';
				$procontent .= '<div class="profileimage">

									<img src="/?qa=image&qa_blobid='.$avatarblobid.'&qa_size='.$avatarsize.'" alt="'.$handle.'" class="qa-avatar-image" />

									<p class="fullname" style="display:none;">'.$username.'</p>

									<div class="reputationlab">
										<span class="upro_bestanswers tooltipS" title="'.qa_lang('booker_lang/bestanswer_iconhover').'">
											<img src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/best-answer.png" alt="best answer" style="margin-right:10px;" /><br />
											<span class="upro_bestanswers_count">'.$aselecteds.'</span>
										</span>

										<span class="upro_points">'.number_format($points, 0, ',', '.').'</span> <br />

										<span class="upro_pointslabel">'.$points_label.'</span>

										<span class="upro_upvoteds tooltipS" title="'.qa_lang('booker_lang/thumbsup_iconhover').'">
											<img src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/thumbs-up.png" alt="thumbs up" />
											<span class="upro_upvoteds_count">'.$upvoteds.'</span>
										</span>
									</div>

								</div> <!-- profileimage -->';

				// ICONS
				$location_icon = '<img class="experticon" style="vertical-align:-3px;margin-right:5px;" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/icon-map.png" alt="location" />';
				$website_icon = '<img style="vertical-align:-3px;margin-right:5px;" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/icon-home.png" alt="website" />';
				$contact_icon = '<img style="vertical-align:-3px;margin:0 5px 0 2px;" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/icon-contact.png" alt="contact" />';

				// remove colon ":"
				// $duration_label = str_replace(':', '', $duration_label);
				$duration_icon = '<img style="vertical-align:-6px;margin-right:5px;" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/icon-membership.png" alt="membership" />';

				$admin_userid = '';
				if(qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN)
				{
					$admin_userid = '<br /><span style="color:#393;">userid: '.$userid.'</span>';
				}

				$procontent .= '<div class="user-meta-data">'.
									'<p title="'.$duration_label.' '.$duration.'">'.$duration_icon.' '.$duration.$admin_userid.'</p>'.
									'<p class="upro_stats">'.$duration_icon.' <a href="'.qa_path('user/'.$handle.'/answers').'">'.$aposts.' '.$aposts_label.'</a> | '.
									'<a href="'.qa_path('user/'.$handle.'/questions').'">'.$qposts.' '.$qposts_label.'</a></p>'.
									(!empty($website) ? '<p>'.$website_icon.' '.$website.'</p>' : '').
									(!empty($location) ? '<p>'.$location_icon.' '.$location.'</p>' : '').
									(!empty($contact) ? '<p>'.$contact_icon.' '.$contact.'</p>' : '').
									(!empty($service) ? '<p class="spezializedin"><b>'.qa_lang('booker_lang/service').':</b><br />'.$service.'</p>' : '').
									(!empty($about) ? '<p class="aboutme"><b>'.qa_lang('booker_lang/aboutme').':</b><br />'.$about.'</p>' : '').
								'</div>';

				$procontent .= '</div> <!-- upro_left -->';

				$procontent .= '<div class="upro_right">';
				$procontent .= '<div class="upro_answerlist">'.$answlist.'</div>';
				// $procontent .= '<a class="upro_history btnyellow" href="'.qa_path('user/'.$handle).'?tab=history">'.qa_lang('booker_lang/weekhistory').'</a>';
				if($aposts>0)
				{
					$procontent .= '<a class="upro_history btnyellow" href="'.qa_path('user/'.$handle.'/answers').'">'.qa_lang('booker_lang/all_answers').'</a>';
				}
				$procontent .= '</div> <!-- upro_right -->';

				// edit button for user
				if($userid == qa_get_logged_in_userid())
				{
					$procontent .= '
					<div class="usereditwrap">
						<form method="post" action="">
							<a href="'.qa_path('userprofile').'" class="defaultbutton">'.qa_lang('users/edit_profile').'</a>
						</form>
						<a href="'.qa_path('account').'" class="changeaccountbtn" style="display:inline-block;font-size:11px;color:#777;margin:15px 0 0 1px;">'.qa_lang('booker_lang/changemailpw').'</a>
					</div>
					';
				}

				$procontent .= '</div> <!-- custom-userprofile -->';


				// FANS and RECOMMENDERS
				$userfollowers = helper_get_all_followers($userid);

				// helper_list_all_followers_boxes
				if(count($userfollowers)>0)
				{
					$procontent .= '
					<div class="userfollowers" id="fans">
						<p style="font-size:15px;">
							'.str_replace('~username~', $username, qa_lang('booker_lang/recommendedby')).':
						</p>
					';
					$procontent .= helper_listfollowers($userfollowers);
					$procontent .= '</div>'; // #userfollowers
				}

				// IS FAN OF and RECOMMENDS
				$userisfollowing = helper_get_all_userisfollowing($userid);
				// helper_list_all_followers_boxes
				if(count($userisfollowing)>0)
				{
					$procontent .= '
					<div class="userfollowers" id="isfan">
						<p style="font-size:15px;">
							'.str_replace('~username~', $username, qa_lang('booker_lang/recommends')).':
						</p>
					';
					$procontent .= helper_listfollowers($userisfollowing);
					$procontent .= '</div>';
				}

				// output into theme if not 7-days-history-page
				if(qa_get('tab')!='history')
				{
					$this->output($procontent);
				}
			}

			qa_html_theme_base::main_parts($content);
		}


		// override to add user business meta below user-meta
		function post_avatar_meta($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<br/>')
		{
			// called for answer with: post_avatar_meta($a_item, 'qa-a-item');

			$this->output('<span class="'.$class.'-avatar-meta">');
			$this->post_avatar($post, $class, $avatarprefix);
			$this->post_meta($post, $class, $metaprefix, $metaseparator);
			$this->output('</span>');

			// if post is answer query to get meta
			if($class=='qa-a-item')
			{
				$userid = $post['raw']['userid'];
				// registered user
				if(isset($userid))
				{
					$userdata = booker_getfulluserdata($userid);

					$username = '';
					if(isset($userdata['realname']))
					{
						$username = $userdata['realname'];
					}
					$location = '';
					if(isset($userdata['address']))
					{
						$location = $userdata['address'];
						$locationURL = str_replace(' ', '+', $location);
						$location = '<a target="_blank" href="https://maps.google.com/?q='.$locationURL.'"><img class="experticon tooltipS" title="'.$location.'" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/icon-map.png" alt="location" /></a>';
					}
					$service = '';
					if(isset($userdata['service']))
					{
						$service = $userdata['service'];
					}
					$about = '';
					if(isset($userdata['portfolio']))
					{
						$about = strip_tags($userdata['portfolio']);
					}
					$contact = '';
					if(isset($userdata['phone']))
					{
						$contactdata = strip_tags($userdata['phone']);
						$contact = '<img src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/icon-contact.png" class="experticon tooltipS" title="'.$contactdata.'" />';
					}

					// extract email from string
					//preg_match("/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/i", $contactdata, $matches);
					// if(isset($matches[0])) {
					//	$email = $matches[0];
					//	// check if contact contains email address, replace it if necessary to obfuscate from spambots
					//	$pattern = '/[^@\s]*@[^@\s]*\.[^@\s]*/'; // remove space between * and /
					//	$replacement = str_replace('@', '&#64;', $email); // replace at sign with html representation
					//	$replacement = ''.str_replace('.', '&#46;', $replacement); // replace period with html representation
					//	$contactdata = preg_replace($pattern, $replacement, $contactdata);
					// }

					// if($metaItem['title']=='website')
					/*$url_shown = preg_replace("(https?://)", "", $metaItem['content']); // remove http and https from URL
					$url_shown = rtrim($url_shown,'/'); // remove trailing slash from URL
					$websiteURL = 'http://'.$url_shown; // website URL
					$website = '<a href="'.$websiteURL.'"><img class="experticon tooltipS" title="'.$url_shown.'" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/icon-home.png" alt="puslapis" /></a>';
					*/

					// else if($metaItem['title']=='location')

					$metaOutput = $service.$contact.$location;

					// if only name, then skip
					if(empty($contact) && empty($service))
					{
						$metaOutput = '';
					}
					else
					{
						$this->output('<div style="clear:both;"></div>
						<div class="bmeta">
							'.$metaOutput.'
						</div>');
					}
				} // END if(isset($userid))
			} // END if($class=='qa-a-item')
		} // END function post_avatar_meta

		// replace username with real name from userprofile, on question pages and list pages
		function post_meta_who($post, $class)
		{
			if(isset($post['who']))
			{
				if(isset($post['raw']['handle']) && isset($post['raw']['userid']))
				{
					$userhandle = $post['raw']['handle'];
					$userid = $post['raw']['userid'];
					$htmluserlink = $post['who']['data']; // in tag is userhandle
					$realname = booker_get_userfield($userid, 'realname');
					if(!empty($realname))
					{
						$post['who']['data'] = str_replace('>'.$userhandle.'<', '>'.$realname.'<', $htmluserlink);
					}
				}

				$this->output('<span class="'.$class.'-who">');

				if (strlen(@$post['who']['prefix']))
					$this->output('<span class="'.$class.'-who-pad">'.$post['who']['prefix'].'</span>');

				if (isset($post['who']['data']))
					$this->output('<span class="'.$class.'-who-data">'.$post['who']['data'].'</span>');

				// q2apro added to output best answers and votes in usermetas
				if($this->template == 'question' && $class!='qa-c-item')
				{
					if(isset($post['raw']['points']))
					{
						// get best answer count of user
						if(isset($post['raw']['userid']))
						{
							$userAnswerDat = qa_db_read_one_assoc(
												qa_db_query_sub('SELECT aposts, upvoteds, aselecteds FROM `^userpoints`
																	WHERE userid = #
																	;', $post['raw']['userid']), true);
							$uvotesreceived = $userAnswerDat['upvoteds'];
							$uanswersbest = $userAnswerDat['aselecteds'];
							$this->output('<span class="'.$class.'-who-title">
											<span class="meta-user-ratings-best tooltipS" title="'.qa_lang('booker_lang/bestanswers').': '.$uanswersbest.'">'.$uanswersbest.' <img src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/best-answer.png" alt="best answers" /></span>
											<span class="meta-user-ratings-votes tooltipS" title="'.qa_lang('booker_lang/votesreceived').': '.$uvotesreceived.'">'.$uvotesreceived.' <img src="'.QA_HTML_THEME_LAYER_URLTOROOT.'images/thumbs-up.png" alt="thumbs up" /></span>
											</span>');
							// $userAnswerDat['aposts'].' Ats. '.
						}
					}
				}

				if (isset($post['who']['points']))
				{
					$post['who']['points']['prefix']='('.$post['who']['points']['prefix'];
					$post['who']['points']['suffix'].=')';
					$this->output_split($post['who']['points'], $class.'-who-points');
				}

				if (strlen(@$post['who']['suffix']))
				{
					$this->output('<span class="'.$class.'-who-pad">'.$post['who']['suffix'].'</span>');
				}

				$this->output('</span>');
			}
		} // END post_meta_who

		// override subnavigation
		function nav_main_sub()
		{
			$this->nav('main');

			// sub navigation for user profile page
			if($this->template=='user' || $this->template=='user-questions' || $this->template=='user-answers' || $this->template=='user-activity')
			{
				$handle = $this->q2apro_gethandle_from_url();

				// profile should only be handle, not "Mitglied handle"
				$this->content['navigation']['sub']['profile']['label'] = qa_html($handle);

				// new
				$profile_userid = qa_handle_to_userid($handle);
				$this->content['navigation']['sub']['profile']['label'] = booker_get_realname($profile_userid, true);

				if(mb_strtolower(qa_get_logged_in_handle(), 'UTF-8') == mb_strtolower($handle, 'UTF-8'))
				{
					// add subnavigation 'favorites' and 'options' to user page, must be set before $this->nav('sub');
					$this->content['navigation']['sub']['favorites'] = array(
							'label' => qa_lang_html('misc/nav_my_favorites'),
							'url' => qa_path_html('favorites'),
					);

					// remove account link
					unset($this->content['navigation']['sub']['account']);
					// remove wall link
					unset($this->content['navigation']['sub']['wall']);

					// remove activity link - still using history plugin for 7 days history
					// unset($this->content['navigation']['sub']['activity']);

					unset($this->content['navigation']['sub']['favorites']);
					// bring favorites link to end
					/*
					$favoritesnav = $this->content['navigation']['sub']['favorites'];
					unset($this->content['navigation']['sub']['favorites']);
					$this->content['navigation']['sub']['favorites'] = $favoritesnav;
					*/

					/***
					$this->content['navigation']['sub']['options'] = array(
							'label' => 'Optionen',
							'url' => qa_path_html('options'),
					);
					// bring optionen link to end
					$optionsnav = $this->content['navigation']['sub']['options'];
					unset($this->content['navigation']['sub']['options']);
					$this->content['navigation']['sub']['options'] = $optionsnav;
					*/

					// var_dump($this->content['navigation']['sub']);
				}
			}

			// change subnavigation order of unanswered questions list
			if($this->template=='unanswered')
			{
				// navigation sub array is by default: by-answers, by-selected, by-upvotes
				// we want the following order: by-answers, by-upvotes, by-selected
				// swap elements in associative array
				$cstsub_1st = $this->content['navigation']['sub']['by-answers'];
				$cstsub_2nd = $this->content['navigation']['sub']['by-upvotes'];
				$cstsub_3rd = $this->content['navigation']['sub']['by-selected'];
				// clear sub-navigation
				unset($this->content['navigation']['sub']);
				// set sub-navigation anew
				$this->content['navigation']['sub'] = array(
					'by-answers' => $cstsub_1st,
					'by-upvotes' => $cstsub_2nd,
					'by-selected' => $cstsub_3rd,
				);
			}

			// $this->nav('sub');
		} // end nav_main_sub

		public function q2apro_gethandle_from_url()
		{
			$handle = preg_replace( '#^user/([^/]+)#', "$1", $this->request );
			// remove /questions from $handle
			$handle = str_replace('/questions', '', $handle);
			// remove /answers from $handle
			$handle = str_replace('/answers', '', $handle);
			// remove /activity from $handle
			$handle = str_replace('/activity', '', $handle);
			return $handle;
		}

	} // end qa_html_theme_layer


/*
	Omit PHP closing tag to help avoid accidental output
*/

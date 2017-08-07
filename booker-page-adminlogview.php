<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_adminlogview
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
					'title' => 'booker Page logview', // title of page
					'request' => 'adminlogview', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='adminlogview')
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
				qa_set_template('booker adminlogview');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			$userid = qa_get_logged_in_userid();

			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminlogview');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}

			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker adminlogview');
			$qa_content['title'] = qa_lang('booker_lang/adviewlog_title');

			// init
			$qa_content['custom'] = '';

			$qa_content['custom'] .= '
				<h2>
					Booking Events
				</h2>
			';
			$qa_content['custom'] .= $this->list_all_events_booking_logs();

			$qa_content['custom'] .= '
				<h2 style="margin-top:70px;">
					Favorite and Register Events
				</h2>
			';
			$qa_content['custom'] .= $this->list_all_events_eventlog();

			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main {
					width:100%;
					position:relative;
					margin-bottom:100px;
				}
				.qa-main h2 {
					font-size:17px;
				}
				.eventdatatable th {
					background:#FFC;
					border:1px solid #CCC;
					padding:5px;
				}
				.eventdatatable td {
					border:1px solid #CCC;
					padding:5px;
				}
				.eventlink {
					font-size:10px;
				}
			</style>
			';

			return $qa_content;

		} // end process_request

		function list_all_events_booking_logs()
		{
			$eventlisting = '';

			$eventdata = qa_db_read_all_assoc(
								qa_db_query_sub('
										SELECT datetime, userid, eventid, eventname, params FROM `^booking_logs`
										ORDER BY datetime DESC
										LIMIT 0, 500
										')
										);
			if(count($eventdata)>0)
			{
				$eventlisting .= '
				<table class="eventdatatable">
				<tr>
					<th>
						'.qa_lang('booker_lang/time').'
					</th>
					<th>
						'.qa_lang('booker_lang/user').'
					</th>
					<th>
						'.qa_lang('booker_lang/event').'
					</th>
					<th>
						'.qa_lang('booker_lang/eventname').'
					</th>
					<th>
						'.qa_lang('booker_lang/logdata').'
					</th>
				</tr>';

				foreach($eventdata as $log)
				{
					$eventname = booker_get_logeventname($log['eventname']);
					if(empty($eventname))
					{
						$eventname = '<span style="color:#F00;">Eventname is missing: <b>'.$log['eventname'].'</b></span>';
					}

					$eventlink = '';
					if(isset($log['eventid']))
					{
						$eventlink = '<a class="eventlink" target="_blank" href="'.qa_path('eventmanager').'?id='.$log['eventid'].'">'.qa_lang('booker_lang/event').' '.$log['eventid'].'</a>';
					}

					$eventdata = booker_get_logeventparams($log['params'], $log['eventname'], $log['eventid'], $log['userid']);

					$userrealname = booker_get_realname($log['userid']);
					$userlink = '';
					if(empty($userrealname))
					{
						$userhandle = helper_gethandle($log['userid']);
						$userlink = '<a title="'.qa_lang('booker_lang/forum_profile').'" target="_blank" href="'.qa_path('user').'/'.$userhandle.'">'.$userhandle.'</a> <img src="/favicons/favicon-16x16.png" alt="icon" />';
					}
					else
					{
						$userlink = '
							<a href="/booking?contractorid='.$log['userid'].'">'.booker_get_realname($log['userid']).'</a> 
							<a style="font-size:10px;" href="/user/'.helper_gethandle($log['userid']).'">Forum</a>';
					}

					$eventlisting .= '
					<tr>
						<td>
							'.helper_get_readable_date_from_time($log['datetime']).'
						</td>
						<td>
							'.$userlink.'
						</td>
						<td>
							'.$eventlink.'
						</td>
						<td>
							'.$eventname.'
						</td>
						<td>
							'.$eventdata.'
						</td>
					</tr>
					';
				}

				$eventlisting .= '
					</table>
				';
			}
			else
			{
				$eventlisting .= '
				<p>
					'.qa_lang('booker_lang/nodata').'
				</p>';
			}

			return $eventlisting;
		} // END list_all_events_booking_logs

		function list_all_events_eventlog()
		{
			$evloglisting = '';
			// get favorite and register events which are in table qa_eventlog
			$queryeventlogs = qa_db_read_all_assoc(
									qa_db_query_sub('
										SELECT datetime, ipaddress, userid, handle, cookieid, event, params
										FROM `^eventlog`
										WHERE (`event`="u_favorite" OR `event`="u_unfavorite" OR `event`="u_register")
										AND datetime >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
										ORDER BY datetime DESC
										LIMIT 0,20
										'
										)
									);
			if(count($queryeventlogs)>0)
			{
				$evloglisting .= '<table class="eventdatatable">
				<tr>
					<th>
						'.qa_lang('booker_lang/time').'
					</th>
					<th>
						'.qa_lang('booker_lang/user').'
					</th>
					<th>
						'.qa_lang('booker_lang/eventname').'
					</th>
					<th>
						'.qa_lang('booker_lang/logdata').'
					</th>
				</tr>';

				foreach($queryeventlogs as $log)
				{
					$userlink = '<a href="'.qa_path('user').'/'.$log['handle'].'">'.booker_get_realname($log['userid'], true).'</a>';
					$eventname = '';
					$eventlogdata = '';

					if($log['event']=='u_register')
					{
						$eventname = qa_lang('booker_lang/user_registered');
					}
					else if($log['event']=='u_favorite')
					{
						$eventname = 'favorites';
					}
					else if($log['event']=='u_unfavorite')
					{
						$eventname = 'unfavorites';
					}

					// params is always "userid=123", extract number
					if(strpos($log['params'], 'userid=') !== false)
					{
						$favoriteduserid = str_replace('userid=', '', $log['params']);
						$favoriteduser = booker_get_realname($favoriteduserid, true);
						$eventlogdata = '<a href="'.qa_path('user').'/'.helper_gethandle($favoriteduserid).'">'.$favoriteduser.'</a>';
					}

					$evloglisting .= '
					<tr>
						<td>
							'.helper_get_readable_date_from_time($log['datetime']).'
						</td>
						<td>
							'.$userlink.'
						</td>
						<td>
							'.$eventname.'
						</td>
						<td>
							'.$eventlogdata.'
						</td>
					</tr>
					';
				}
				$evloglisting .= '</table>';
			}
			else
			{
				$evloglisting .= '
				<p>
					'.qa_lang('booker_lang/nodata').'
				</p>';
			}

			return $evloglisting;
		} // END list_all_events_eventlog

	}; // end class

/*
	Omit PHP closing tag to help avoid accidental output
*/

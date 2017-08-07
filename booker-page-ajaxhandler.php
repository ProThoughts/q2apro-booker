<?php

/*
	Plugin Name: q2apro-ajax-usersearch
	Plugin URI: http://www.q2apro.com/plugins/usersearch
*/

	class booker_ajaxhandler
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
					'title' => 'Ajax Usersearch Page', // title of page
					'request' => 'ajaxhandler', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='ajaxhandler')
			{
				return true;
			}
			return false;
		}

		function process_request($request)
		{
			$userid = qa_get_logged_in_userid();
			$isadmin = qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN;
			
			$transferString = qa_post_text('request_delete');
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata);

				// only user can post an offer
				$requestid = empty($newdata['requestid']) ? null : $newdata['requestid'];
				if(empty($requestid))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				// check if request is by ajax-posting user
				// get offer data
				$requestcreator = qa_db_read_one_value(
									qa_db_query_sub('SELECT userid FROM `^booking_requests` 
												WHERE requestid = # 
												', 
												$requestid));
				if($requestcreator != $userid && !$isadmin)
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
				}
				else
				{
					qa_db_query_sub('UPDATE ^booking_requests SET status = # 
													WHERE requestid = # 
													', 
													MB_REQUEST_DELETED, $requestid
													);
					// LOG
					$eventname = 'request_deleted';
					$eventid = null;
					$params = array(
						'requestid' => $requestid,
					);
					booker_log_event($userid, $eventid, $eventname, $params);
					
					$arrayBack = array(
						'status' => 'success',
						'message' => ''
					);
					echo json_encode($arrayBack);
				}
				
				exit(); 
			} // END AJAX request_delete


			$transferString = qa_post_text('request_approve');
			if(isset($transferString)) 
			{
				// only admin can approve requests
				if(!$isadmin)
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata);

				// only user can post an offer
				$requestid = empty($newdata['requestid']) ? null : $newdata['requestid'];
				if(empty($requestid))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				qa_db_query_sub('UPDATE ^booking_requests SET status = # 
												WHERE requestid = # 
												', 
												MB_REQUEST_APPROVED, $requestid
												);

				// LOG
				$eventname = 'request_approved';
				$eventid = null;
				$params = array(
					'requestid' => $requestid,
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				$arrayBack = array(
					'status' => 'success',
					'message' => ''
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX request_approve
			

			$transferString = qa_post_text('request_disapprove');
			if(isset($transferString)) 
			{
				// only admin can approve requests
				if(!$isadmin)
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata);

				// only user can post an offer
				$requestid = empty($newdata['requestid']) ? null : $newdata['requestid'];
				if(empty($requestid))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				qa_db_query_sub('UPDATE ^booking_requests SET status = # 
												WHERE requestid = # 
												', 
												MB_REQUEST_DISAPPROVED, $requestid
												);

				// LOG
				$eventname = 'request_disapproved';
				$eventid = null;
				$params = array(
					'requestid' => $requestid,
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				$arrayBack = array(
					'status' => 'success',
					'message' => ''
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX request_disapprove
			
			
			$transferString = qa_post_text('offer_approve');
			if(isset($transferString)) 
			{
				// only admin can approve offers
				if(!$isadmin)
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata);

				// only user can post an offer
				$offerid = empty($newdata['offerid']) ? null : $newdata['offerid'];
				if(empty($offerid))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				// set approved
				qa_db_query_sub('UPDATE ^booking_offers SET status = (status | #) 
												WHERE offerid = # 
												', 
												MB_OFFER_APPROVED, $offerid
												);

				// set active
				qa_db_query_sub('UPDATE ^booking_offers SET status = (status | #) 
												WHERE offerid = # 
												', 
												MB_OFFER_ACTIVE, $offerid
												);

				// LOG
				$eventname = 'offer_approved';
				$eventid = null;
				$params = array(
					'offerid' => $offerid,
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				$arrayBack = array(
					'status' => 'success',
					'message' => ''
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX offer_approve
			

			$transferString = qa_post_text('offer_disapprove');
			if(isset($transferString)) 
			{
				// only admin can approve offers
				if(!$isadmin)
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata);

				// only user can post an offer
				$offerid = empty($newdata['offerid']) ? null : $newdata['offerid'];
				if(empty($offerid))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				// first set active, which also means checked by admin
				// activate by adding the active flag
				qa_db_query_sub('UPDATE ^booking_offers SET status = (status | #)
												WHERE offerid = # 
												', 
												MB_OFFER_ACTIVE, $offerid
												);
				
				// disapprove by removing the approve flag
				qa_db_query_sub('UPDATE ^booking_offers SET status = (status &~ #)
												WHERE offerid = # 
												', 
												MB_OFFER_APPROVED, $offerid
												);

				// LOG
				$eventname = 'offer_disapproved';
				$eventid = null;
				$params = array(
					'offerid' => $offerid,
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				$arrayBack = array(
					'status' => 'success',
					'message' => ''
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX offer_disapprove
			
			
			$transferString = qa_post_text('offer_deactivate');
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata);

				// only user can post an offer
				$offerid = empty($newdata['offerid']) ? null : $newdata['offerid'];
				if(empty($offerid))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				// deactivate by removing the active flag
				qa_db_query_sub('UPDATE ^booking_offers SET status = (status &~ #)
												WHERE offerid = # 
												', 
												MB_OFFER_ACTIVE, $offerid
												);

				// LOG
				$eventname = 'offer_deactivated';
				$eventid = null;
				$params = array(
					'offerid' => $offerid,
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				$arrayBack = array(
					'status' => 'success',
					'message' => ''
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX offer_deactivate
			

			$transferString = qa_post_text('offer_activate');
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata);

				// only user can post an offer
				$offerid = empty($newdata['offerid']) ? null : $newdata['offerid'];
				if(empty($offerid))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				// activate by adding the active flag
				qa_db_query_sub('UPDATE ^booking_offers SET status = (status | #)
												WHERE offerid = # 
												', 
												MB_OFFER_ACTIVE, $offerid
												);

				// LOG
				$eventname = 'offer_activated';
				$eventid = null;
				$params = array(
					'offerid' => $offerid,
				);
				booker_log_event($userid, $eventid, $eventname, $params);
				
				$arrayBack = array(
					'status' => 'success',
					'message' => ''
				);
				echo json_encode($arrayBack);
				
				exit(); 
			} // END AJAX offer_activate
			
			$transferString = qa_post_text('offer_delete');
			if(isset($transferString)) 
			{
				header('Content-Type: application/json; charset=UTF-8');
				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata);

				// only user can post an offer
				$offerid = empty($newdata['offerid']) ? null : $newdata['offerid'];
				if(empty($offerid))
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
					echo json_encode($arrayBack);
					exit();
				}
				
				// check if offer is by ajax-posting user
				// get offer data
				$offercreator = qa_db_read_one_value(
									qa_db_query_sub('SELECT userid FROM `^booking_offers` 
												WHERE offerid = # 
												', 
												$offerid));
				if($offercreator != $userid && !$isadmin)
				{
					$arrayBack = array(
						'status' => 'error',
						'message' => ''
					);
				}
				else
				{
					qa_db_query_sub('UPDATE ^booking_offers SET status = # 
													WHERE offerid = # 
													', 
													MB_OFFER_DELETED, $offerid
													);
					// LOG
					$eventname = 'offer_deleted';
					$eventid = null;
					$params = array(
						'offerid' => $offerid,
					);
					booker_log_event($userid, $eventid, $eventname, $params);
					
					$arrayBack = array(
						'status' => 'success',
						'message' => ''
					);
					echo json_encode($arrayBack);
				}
				
				exit(); 
			} // END AJAX offer_delete

			
			


			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker ajaxhandler');

			$qa_content['title'] = ''; // page title

			// return if not admin!
			if(qa_get_logged_in_level() < QA_USER_LEVEL_ADMIN)
			{
				$qa_content['error'] = '<p>Access denied</p>';
				return $qa_content;
			}
			else {
				$qa_content['custom'] = '<p>It actually makes no sense to call the Ajax URL directly.</p>';
			}

			return $qa_content;
			
		} // end process_request
		
	}; // end class
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
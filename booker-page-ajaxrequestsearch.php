<?php

/*
	Plugin Name: BOOKER
*/

	class booker_ajaxrequestsearch 
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
					'request' => 'ajaxrequestsearch', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='ajaxrequestsearch')
			{
				return true;
			}
			return false;
		}

		function process_request($request)
		{
		
			// we received post data, it is the ajax call with the searchstring
			$transferString = qa_post_text('ajax');
			$transferString = trim(strip_tags($transferString));
			if(!empty($transferString))
			{
				$searchstring = $transferString;
				$maxshow = 50;
				$searchterm = '%'.$searchstring.'%';
				
				// this is echoed via ajax success data
				$output = '';
				
				// in case the searchstring consists of multiple words
				$searcharray = null;
				if(preg_match('/\s/',$searchstring)>0)
				{
					$searcharray = explode(' ', $searchstring);
					foreach($searcharray as &$a)
					{
						$a = '%'.$a.'%';
					}
				}
				
				//*** improve search by http://stackoverflow.com/a/19327311/1066234
				
				if(count($searcharray)==0)
				{
					// ajax return all users matching service or portfolio
					$allrequests = qa_db_read_all_assoc(
						qa_db_query_sub(
							'SELECT requestid, created, userid, title, price, duration, end, description, location, status FROM ^booking_requests 
							 WHERE 
							 (
								 title LIKE $
								 OR
								 description LIKE $
								 OR
								 location LIKE $
							 )
							 AND (end > NOW() OR end IS NULL)
							 AND `status` = '.MB_REQUEST_APPROVED.'
							 LIMIT #
							',
							$searchterm, $searchterm, $searchterm, $maxshow)
						);
				}
				else
				{
					// ajax return all users matching service or portfolio
					$allrequests = qa_db_read_all_assoc(
						qa_db_query_sub(
							'SELECT requestid, created, userid, title, price, duration, end, description, location, status FROM ^booking_requests 
							 WHERE 
							 (
								 (title LIKE $ OR title LIKE $)
								 OR
								 (description LIKE $ OR description LIKE $)
								 OR
								 (location LIKE $ OR location LIKE $)
							 )
							 AND (end > NOW() OR end IS NULL)
							 LIMIT #
							',
							$searcharray[0], $searcharray[1], 
							$searcharray[0], $searcharray[1], 
							$searcharray[0], $searcharray[1], 
							$maxshow)
						);
				}

				if(count($allrequests)>0)
				{
					$output .= booker_requests_to_table($allrequests, $this->urltoroot);
				}
				
				// send to fronted
				header('Access-Control-Allow-Origin: '.qa_path(null));
				echo $output;
				
				exit(); 
			} // END AJAX RETURN
			else 
			{
				// echo 'Unexpected problem detected. No transfer string.';
				exit();
			}
			
			
			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker ajaxrequestsearch');

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
<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_searchtrack
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
					'title' => 'booker Page searchtrack', // title of page
					'request' => 'searchtrack', // request name
					'nav' => 'M',
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='searchtrack') 
			{
				return true;
			}
			return false;
		}

		function process_request($request) 
		{
		
			/*
			if(!qa_opt('booker_enabled'))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker searchtrack');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			*/
			
			// searchtrack
			$searchterm = qa_post_text('search');
			// error_log('searchterm: '.$searchterm);
			
			// *** could add one db column for place of search: contractorlist, requestlist or offerlist
			if(!empty($searchterm))
			{
				if(strlen($searchterm)>255)
				{
					exit();
				}
				
				$ipaddress = qa_remote_ip_address();
				
				$userid = qa_get_logged_in_userid();
				
				// prevent duplicate tracking
				// check if we have searchterms within last 5 min of this user
				$searchdata = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT time, searchterm, ipaddress FROM `^booking_searchtrack` 
												WHERE ipaddress = # 
												AND time >= NOW() - INTERVAL 5 MINUTE
												', 
												$ipaddress));
				foreach($searchdata as $s)
				{
					// tracked searchterm is in new incoming searchterm included
					if(strpos($searchterm, $s['searchterm'])!==false)
					{
						// error_log($s['time'].' -> '.$s['searchterm']);
						
						// delete former one
						qa_db_query_sub('DELETE FROM `^booking_searchtrack` 
											WHERE time = #
											AND searchterm = $
											AND ipaddress = #
											', 
											$s['time'], $s['searchterm'], $s['ipaddress']
										);
					}
				}
				
				// create new entry
				qa_db_query_sub('INSERT INTO `^booking_searchtrack` (
										time, 
										searchterm, 
										userid, 
										ipaddress
										) 
									VALUE (NOW(), $, #, #)', 
									$searchterm, $userid, $ipaddress
								);
				echo 'success';
			}
			else
			{
				echo 'empty searchterm';
			}
			
			exit();
		} // END process_request
		
	}; // end class booker_page_searchtrack
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
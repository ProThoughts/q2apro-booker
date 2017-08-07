<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_shortlink
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
					'title' => 'booker Page Shortlink Profile', // title of page
					'request' => 'shortlink', // request name
					'nav' => 'M',
				),
			);
		}
		
		function match_request($request)
		{
			// do we have 'u/' in the request
			if(strpos($request,'u/') !== false)
			{
				$urlparts = explode('/', $request);
				// potential number
				// if(!empty($urlparts[1]) && is_numeric(preg_replace('/[^0-9]/', '', $request)))
				if(!empty($urlparts[1]) && is_numeric($urlparts[1]))
				{
					return true;
				}
			}
			return false;
		}

		function process_request($request) 
		{
		
			/*
			if(!qa_opt('booker_enabled'))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker shortlink');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			*/
			
			// get userid from shortlink
			$urlparts = explode('/', $request);
			// potential number
			if(!empty($urlparts[1]) && is_numeric(preg_replace('/[^0-9]/', '', $request)))
			{
				$userid = trim($urlparts[1]);
				// var_dump($userid);
			}
			else
			{
				exit();
			}
			
			if(!empty($userid))
			{
				if(strlen($userid)>10)
				{
					exit();
				}
				$params = array('contractorid' => $userid);
				qa_redirect('booking', $params);
			}
			else
			{
				echo 'no ID';
			}
			
			exit();
		} // END process_request
		
	}; // end class booker_page_shortlink
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
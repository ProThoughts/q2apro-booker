<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_adminsearchtrack
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
					'title' => 'booker Page Searchtrack', // title of page
					'request' => 'adminsearchtrack', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='adminsearchtrack')
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
				qa_set_template('booker adminsearchtrack');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminsearchtrack');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}
			
			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker adminsearchtrack');
			$qa_content['title'] = qa_lang('booker_lang/adminsearch_title');

			// init
			$qa_content['custom'] = '';
			
			$searchdata = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT time, userid, searchterm, ipaddress FROM `^booking_searchtrack` 
													  ORDER BY time DESC
													  LIMIT 0, 500
													 ')
													);
			
			$searchlisting = '';
			if(count($searchdata)>0) 
			{
				$searchlisting .= '
				<table class="searchdatatable">
				<tr>
					<th>'.qa_lang('booker_lang/time').'</th>
					<th>'.qa_lang('booker_lang/searchterm').'</th>
					<th>'.qa_lang('booker_lang/user').'</th>
					<th>'.qa_lang('booker_lang/ipaddress').'</th>
				</tr>';
	
				foreach($searchdata as $data) 
				{
				
				$searchlisting .= '
					<tr>
						<td>
							'.helper_get_readable_date_from_time($data['time']).'
						</td>
						<td>
							'.$data['searchterm'].'
						</td>
						<td>
							<a href="'.qa_path('user').'/'.helper_gethandle($data['userid']).'">'.booker_get_realname($data['userid']).'</a>
						</td>
						<td>
							<a href="'.qa_path('ip').'/'.$data['ipaddress'].'">'.$data['ipaddress'].'</a>
						</td>
					</tr>
					';
				}
				$searchlisting .= '
					</table>
				';

			}
			else
			{
				$searchlisting = '
				<p>
					'.qa_lang('booker_lang/nodata').'
				</p>';
			}

			$qa_content['custom'] .= $searchlisting;
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main {
					width:100%;
					position:relative;
					margin-bottom:100px;
				}
				.searchdatatable th {
					background:#FFC;
					border:1px solid #CCC;
					padding:5px;					
				} 
				.searchdatatable td {
					border:1px solid #CCC;
					padding:5px;
				}
			</style>
			';
			
			return $qa_content;
			
		} // end process_request

	}; // end class
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
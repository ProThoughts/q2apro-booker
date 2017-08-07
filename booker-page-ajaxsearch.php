<?php

/*
	Plugin Name: BOOKER
*/

	class booker_ajaxsearch
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
					'request' => 'ajaxsearch', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='ajaxsearch') {
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
				$maxshow = 100;
				$searchterm = '%'.$searchstring.'%';

				// this is echoed via ajax success data
				$output = '';

				// in case the searchstring consists of multiple words
				$searcharray = null;
				if(preg_match('/\s/', $searchstring) > 0)
				{
					$searcharray = explode(' ', $searchstring);
					foreach($searcharray as &$a)
					{
						// search within field, string can be in the middle
						$a = '%'.$a.'%';
					}
				}

				// ajax return all offers matching searchterm
				$offers = qa_db_read_all_assoc(
					qa_db_query_sub(
						'SELECT offerid, created, userid, title, price, duration, end, description, flags FROM ^booking_offers
						 WHERE
						 (title LIKE $
						 OR
						 description LIKE $)
						 AND (status & #) = #
						 AND (status & #) = #
						 LIMIT #
						',
						$searchterm, $searchterm,
						MB_OFFER_APPROVED, MB_OFFER_APPROVED,
						MB_OFFER_ACTIVE, MB_OFFER_ACTIVE,
						$maxshow)
					);

				if(count($offers)>0)
				{
					$output .= '
					<div class="offerswrap">
						<h3>
							'.qa_lang('booker_lang/specialoffers').':
						</h3>
						<ul class="offerbox">
					';
					foreach($offers as $offer)
					{
						$output .= '
						<li>
							<p>
								<a class="defaultbutton" target="_blank"
									href="'.qa_path('booking').'?offerid='.$offer['offerid'].'" title="'.helper_shorten_text($offer['description'], 130).'">
									'.$offer['title'].' - '.helper_format_currency($offer['price'],2).' '.qa_opt('booker_currency').'
								</a>
								<span class="fulldescription">'.helper_shorten_text($offer['description'], 8000).'</span>
							</p>
						</li>
						';
					}
					$output .= '
						</ul> <!-- offerbox -->
					</div> <!-- offerswrap -->
					';
				}

				//*** improve search by http://stackoverflow.com/a/19327311/1066234

				// single search word
				$potentials = null;
				// searcharray is empty we only have a single searchstring
				if(count($searcharray)==0)
				{
					// ajax return all users matching service or portfolio
					$potentials = qa_db_read_all_values(
						qa_db_query_sub(
							'SELECT userid FROM ^booking_users
							 WHERE
							 `approved` = '.MB_USER_STATUS_APPROVED.'
							 AND `available` = 1
							 AND `contracted` IS NOT NULL
							 AND
							 (
							 `realname` LIKE $
							 OR `company` LIKE $
							 OR `service` LIKE $
							 OR `portfolio` LIKE $
							 OR `address` LIKE $
							 )
							 LIMIT #
							',
							$searchterm, $searchterm, $searchterm, $searchterm, $searchterm, $maxshow)
						);
				}
				// more than one search term
				else
				{
					// need high value, e.g. if kaunas gets searched
					$maxcheck = 5000;

					// search each term separately and merge the matches by same userids
					foreach($searcharray as $sterm)
					{
						if(empty($potentials))
						{
							// get all users matching realname, service, portfolio or address
							$resultarray = qa_db_read_all_values(
											qa_db_query_sub(
												'SELECT userid FROM ^booking_users
												 WHERE
												 `approved` = '.MB_USER_STATUS_APPROVED.'
												 AND `available` = 1
												 AND `contracted` IS NOT NULL
												 AND
												 (
												 `realname` LIKE $
												 OR `company` LIKE $
												 OR `service` LIKE $
												 OR `portfolio` LIKE $
												 OR `address` LIKE $
												 )
												 LIMIT #
												',
												$sterm, $sterm, $sterm, $sterm, $sterm, $maxcheck)
											);
							$potentials = $resultarray;

							// error_log('FIRST QUERY RESULT: '); error_log(join(',', $potentials));
						}
						else
						{
							$useridsfilter = join(',', $potentials);

							// get all users matching realname, service, portfolio or address
							$resultarray = qa_db_read_all_values(
											qa_db_query_sub(
												'SELECT userid FROM ^booking_users
												 WHERE
												 userid IN ('.$useridsfilter.')
												 AND `approved` = '.MB_USER_STATUS_APPROVED.'
												 AND `available` = 1
												 AND `contracted` IS NOT NULL
												 AND
												 (
												 `realname` LIKE $
												 OR `company` LIKE $
												 OR `service` LIKE $
												 OR `portfolio` LIKE $
												 OR `address` LIKE $
												 )
												 LIMIT #
												',
												$sterm, $sterm, $sterm, $sterm, $sterm, $maxcheck)
											);
							// error_log('SECOND QUERY RESULT: '); error_log(join(',', $resultarray));

							$potentials = array_intersect($potentials, $resultarray);

							// get values that are in both arrays
							// $potentials = array_intersect($potentials, $resultarray);
						}
					}
					// remove duplicates
					//
				}

				if(count($potentials)>0)
				{
					$output .= '
					<div class="serviceswrap">
					<p>
						'.count($potentials).' '.qa_lang('booker_lang/search_results').'
					</p>
					';
					/*
						<h3>
							'.qa_lang('booker_lang/contractors').':
						</h3>
					*/
					foreach($potentials as $userid)
					{
						$output .= booker_get_contractordata_box($userid);
					}
					$output .= '
					</div> <!-- serviceswrap -->
					';
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
			qa_set_template('booker ajaxsearch');

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

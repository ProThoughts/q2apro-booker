<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_premiumhandler
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
					'title' => 'booker Page premiumhandler', // title of page
					'request' => 'premiumhandler', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='premiumhandler')
			{
				return true;
			}
			return false;
		}

		function process_request($request)
		{
			/*
			| This premiumhandler handles currently only the trial period,
			| we use it to unlock the trial and display the success message
			*/

			if(!qa_opt('booker_enabled'))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker premiumhandler');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker premiumhandler');
			$qa_content['title'] = qa_lang('booker_lang/payment_success');

			$userid = qa_get_logged_in_userid();

			// super admin can have view of others for profile if adding a userid=x to the URL
			if(qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN)
			{
				$userid = qa_get('userid');
				if(empty($userid))
				{
					$userid = qa_get_logged_in_userid();
				}
			}

			// free premium for invited users
			$premiumtrial_code = qa_get('code');
			$premiumtype = qa_get('type');

			if(empty($premiumtype) || $premiumtype!=1)
			{
				$premiumtype = 1;
			}

			if(empty($premiumtrial_code))
			{
				return 'missing data';
			}

			// today in 6 months
			$today = date("Y-m-d");
			$start = new DateTime($today, new DateTimeZone("UTC"));
			$month_later = clone $start;
			$month_later->add(new DateInterval("P6M"));
			$premiumend = $month_later->format("Y-m-d");

			// unlock the premium for this user
			booker_setpremium($userid, $premiumtype, $premiumend);
			
			// register as 0 € payment
			$orderid = null;
			$amount = 0;
			booker_register_payment_premium($orderid, $userid, $amount, $premiumtype, 'trial');

			// init
			$qa_content['custom'] = '';
			$qa_content['custom'] .= booker_displaypremium_success($userid);

			return $qa_content;

		} // end process_request

	};

/*
	Omit PHP closing tag to help avoid accidental output
*/

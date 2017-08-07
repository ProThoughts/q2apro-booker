<?php
/*
	Plugin Name: BOOKER
*/

	class booker_admin
	{

		function init_queries($tableslc)
		{

			$tablename_booking_bids = qa_db_add_table_prefix('booking_bids');
			if(!in_array($tablename_booking_bids, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE `^booking_bids` (
					  `bidid` int(10) UNSIGNED NOT NULL,
					  `created` datetime NOT NULL,
					  `requestid` int(10) UNSIGNED NOT NULL,
					  `userid` int(10) UNSIGNED NOT NULL,
					  `price` decimal(7,2) NOT NULL,
					  `comment` varchar(8000) DEFAULT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
				);
			}

			$tablename_booking_logs = qa_db_add_table_prefix('booking_logs');
			if(!in_array($tablename_booking_logs, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE `^booking_logs` (
					  `datetime` datetime NOT NULL,
					  `userid` int(10) unsigned NOT NULL,
					  `eventid` int(10) unsigned DEFAULT NULL,
					  `eventname` varchar(22) CHARACTER SET ascii NOT NULL,
					  `params` varchar(800) DEFAULT NULL,
					  KEY `datetime` (`datetime`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
				);
			}

			$tablename_booking_messages = qa_db_add_table_prefix('booking_messages');
			if(!in_array($tablename_booking_messages, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE `^booking_messages` (
					  `messageid` int(10) unsigned NOT NULL AUTO_INCREMENT,
					  `created` datetime NOT NULL,
					  `fromuserid` int(10) unsigned NOT NULL,
					  `touserid` int(10) unsigned NOT NULL,
					  `content` varchar(8000) DEFAULT NULL,
					  PRIMARY KEY (`messageid`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;'
				);
			}

			$tablename_booking_offers = qa_db_add_table_prefix('booking_offers');
			if(!in_array($tablename_booking_offers, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE IF NOT EXISTS `^booking_offers` (
					  `offerid` int(10) unsigned NOT NULL AUTO_INCREMENT,
					  `created` datetime DEFAULT NULL,
					  `userid` int(10) unsigned NOT NULL,
					  `title` varchar(500) DEFAULT NULL,
					  `price` decimal(7,2) DEFAULT NULL,
					  `duration` int(10) unsigned DEFAULT NULL,
					  `end` datetime DEFAULT NULL,
					  `description` varchar(8000) DEFAULT NULL,
					  `flags` smallint(5) unsigned NOT NULL DEFAULT "0",
					  `status` tinyint(1) DEFAULT NULL,
					  PRIMARY KEY (`offerid`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
				);
				// memo: flags means serviceflags
			}

			$tablename_booking_orders = qa_db_add_table_prefix('booking_orders');
			if(!in_array($tablename_booking_orders, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE `^booking_orders` (
					  `eventid` int(10) unsigned NOT NULL AUTO_INCREMENT,
					  `bookid` int(10) unsigned NOT NULL,
					  `offerid` int(10) unsigned DEFAULT NULL,
					  `created` datetime NOT NULL,
					  `contractorid` int(10) unsigned NOT NULL,
					  `starttime` datetime NOT NULL,
					  `endtime` datetime NOT NULL,
					  `customerid` int(10) unsigned NOT NULL,
					  `unitprice` decimal(10,2) DEFAULT NULL,
					  `commission` decimal(5,2) unsigned DEFAULT "0.15",
					  `needs` varchar(8000) DEFAULT NULL,
					  `attachment` varchar(800) DEFAULT NULL,
					  `payment` varchar(255) DEFAULT NULL,
					  `status` int(2) unsigned NOT NULL,
					  `protocol` varchar(1000) DEFAULT NULL,
					  PRIMARY KEY (`eventid`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;'
				);
			}

			$tablename_booking_payments = qa_db_add_table_prefix('booking_payments');
			if(!in_array($tablename_booking_payments, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE `^booking_payments` (
					  `payid` int(10) unsigned NOT NULL AUTO_INCREMENT,
					  `paytime` datetime NOT NULL,
					  `orderid` varchar(40) DEFAULT NULL,
					  `userid` int(10) unsigned NOT NULL,
					  `paymethod` varchar(50) DEFAULT NULL,
					  `amount` decimal(10,2) NOT NULL,
					  `onhold` tinyint(1) DEFAULT NULL,
					  `ishonorar` tinyint(1) DEFAULT NULL,
					  PRIMARY KEY (`payid`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;'
				);
			}

			$tablename_booking_ratings = qa_db_add_table_prefix('booking_ratings');
			if(!in_array($tablename_booking_ratings, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE `^booking_ratings` (
					  `created` datetime NOT NULL,
					  `customerid` int(10) unsigned NOT NULL,
					  `contractorid` int(10) unsigned NOT NULL,
					  `eventid` int(10) unsigned DEFAULT NULL,
					  `rating` int(2) unsigned NOT NULL,
					  `text` varchar(8000) DEFAULT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
				);
			}

			$tablename_booking_requests = qa_db_add_table_prefix('booking_requests');
			if(!in_array($tablename_booking_requests, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE IF NOT EXISTS `^booking_requests` (
					  `requestid` int(10) unsigned NOT NULL AUTO_INCREMENT,
					  `created` datetime DEFAULT NULL,
					  `userid` int(10) unsigned NOT NULL,
					  `title` varchar(500) DEFAULT NULL,
					  `price` decimal(7,2) DEFAULT NULL,
					  `duration` int(10) unsigned DEFAULT NULL,
					  `end` date DEFAULT NULL,
					  `description` varchar(8000) DEFAULT NULL,
					  `location` varchar(2000) DEFAULT NULL,
					  `status` tinyint(1) DEFAULT NULL,
					  PRIMARY KEY (`requestid`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
				);
			}

			$tablename_booking_searchtrack = qa_db_add_table_prefix('booking_searchtrack');
			if(!in_array($tablename_booking_searchtrack, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE `^booking_searchtrack` (
					  `time` timestamp NOT NULL,
					  `searchterm` varchar(255) NOT NULL,
					  `userid` int(10) unsigned DEFAULT NULL,
					  `ipaddress` varchar(255) SET ascii NOT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
				); // *** ipaddress should be VARBINARY(16) later
			}

			$tablename_booking_users = qa_db_add_table_prefix('booking_users');
			if(!in_array($tablename_booking_users, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE IF NOT EXISTS `^booking_users` (
					  `userid` int(10) UNSIGNED NOT NULL,
					  `realname` varchar(500) DEFAULT NULL,
					  `company` varchar(500) DEFAULT NULL,
					  `birthdate` varchar(30) DEFAULT NULL,
					  `address` varchar(1000) DEFAULT NULL,
					  `phone` varchar(30) CHARACTER SET ascii DEFAULT NULL,
					  `skype` varchar(100) DEFAULT NULL,
					  `service` varchar(1000) DEFAULT NULL,
					  `portfolio` varchar(8000) DEFAULT NULL,
					  `bookingprice` decimal(5,2) DEFAULT NULL,
					  `payment` varchar(500) DEFAULT NULL,
					  `available` tinyint(1) DEFAULT "0",
					  `absent` varchar(8000) CHARACTER SET ascii DEFAULT NULL,
					  `externalcal` varchar(1000) CHARACTER SET ascii DEFAULT NULL,
					  `registered` datetime DEFAULT NULL,
					  `contracted` datetime DEFAULT NULL,
					  `approved` tinyint(1) NOT NULL DEFAULT "0",
					  `commission` decimal(5,2) UNSIGNED DEFAULT NULL,
					  `kmrate` decimal(3,2) UNSIGNED DEFAULT NULL,
					  `flags` smallint(5) UNSIGNED NOT NULL DEFAULT "0",
					  `premium` smallint(5) UNSIGNED NOT NULL DEFAULT "0",
					  `premiumend` datetime DEFAULT NULL
					  UNIQUE KEY `userid` (`userid`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
				);
			}


			$tablename_booking_week = qa_db_add_table_prefix('booking_week');
			if(!in_array($tablename_booking_week, $tableslc))
			{
				qa_db_query_sub(
					'CREATE TABLE `^booking_week` (
					  `userid` int(10) unsigned NOT NULL,
					  `weekday` int(1) unsigned NOT NULL,
					  `starttime` time NOT NULL,
					  `endtime` time NOT NULL,
					  KEY `userid` (`userid`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
				);
			}

		} // end init_queries


		// option's value is requested but the option has not yet been set
		function option_default($option)
		{
			switch($option)
			{
				case 'booker_enabled':
					return 1; // true
				case 'booker_permission':
					return QA_PERMIT_ADMINS; // default level to access this page
				case 'booker_currency':
					return '€';
				case 'booker_timezone':
					return 'Europe/Berlin';
				case 'booker_language':
					return 'en';
				case 'booker_email':
					return '';
				case 'booker_paypal':
					return 'IMPORTANT';
				case 'booker_paypal_lc':
					return 'EN';
				case 'booker_paypal_currency':
					return 'USD';
				case 'booker_operator':
					return 'IMPORTANT';
				case 'booker_bankdetails':
					return 'IMPORTANT';
				case 'booker_paysera_paycountry':
					return '';
				case 'booker_paysera_paylanguage':
					return '';
				case 'booker_paysera_paycurrency':
					return 'USD';
				case 'booker_paysera_projectid':
					return '';
				case 'booker_paysera_sign_password':
					return '';
				case 'booker_paymill_public_key':
					return '';
				case 'booker_paymill_private_key':
					return '';
				case 'booker_commission':
					return 0.15;
				case 'booker_vatvalue':
					return 0.21;
				case 'booker_pricepremium':
					return 14;
				case 'booker_pricevip':
					return 19;
				case 'booker_kmrate':
					return 0.4;
				case 'booker_mailcopies':
					return qa_opt('mailing_from_email');
				case 'booker_voucherenabled':
					return 0; // false
				case 'booker_vouchervalue':
					return '10 Euro';
				case 'booker_mailsendername':
					return qa_opt('site_title');
				case 'booker_mailsenderfooter':
					return 'Mail footer';
				case 'booker_uploadpath':
					return 'e. g. /www/12456/forum/bookbin/';
				case 'booker_uploadurl':
					return 'e. g. http://www.yoursite.com/bookbin/';
				case 'booker_gmapsapikey':
					return '';
				default:
					return null;
			}
		}

		function allow_template($template)
		{
			return ($template!='admin');
		}

		function admin_form(&$qa_content)
		{

			// process the admin form if admin hit Save-Changes-button
			$ok = null;
			if (qa_clicked('booker_save'))
			{
				qa_opt('booker_enabled', (bool)qa_post_text('booker_enabled')); // empty or 1
				qa_opt('booker_permission', (int)qa_post_text('booker_permission')); // level
				qa_opt('booker_currency', (String)qa_post_text('booker_currency'));
				qa_opt('booker_timezone', (String)qa_post_text('booker_timezone'));
				qa_opt('booker_language', (String)qa_post_text('booker_language'));
				qa_opt('booker_email', (String)qa_post_text('booker_email'));
				qa_opt('booker_paypal', (String)qa_post_text('booker_paypal'));
				qa_opt('booker_paypal_lc', (String)qa_post_text('booker_paypal_lc'));
				qa_opt('booker_paypal_currency', (String)qa_post_text('booker_paypal_currency'));
				qa_opt('booker_operator', (String)qa_post_text('booker_operator'));
				qa_opt('booker_bankdetails', (String)qa_post_text('booker_bankdetails'));
				qa_opt('booker_paysera_paycountry', (String)qa_post_text('booker_paysera_paycountry'));
				qa_opt('booker_paysera_paylanguage', (String)qa_post_text('booker_paysera_paylanguage'));
				qa_opt('booker_paysera_paycurrency', (String)qa_post_text('booker_paysera_paycurrency'));
				qa_opt('booker_paysera_projectid', (String)qa_post_text('booker_paysera_projectid'));
				qa_opt('booker_paysera_sign_password', (String)qa_post_text('booker_paysera_sign_password'));
				qa_opt('booker_paymill_public_key', (String)qa_post_text('booker_paymill_public_key'));
				qa_opt('booker_paymill_private_key', (String)qa_post_text('booker_paymill_private_key'));
				qa_opt('booker_minimumprice', (float)qa_post_text('booker_minimumprice')); // decimal
				qa_opt('booker_commission', (float)qa_post_text('booker_commission'));
				qa_opt('booker_vatvalue', (float)qa_post_text('booker_vatvalue'));
				qa_opt('booker_pricepremium', (float)qa_post_text('booker_pricepremium'));
				qa_opt('booker_pricevip', (float)qa_post_text('booker_pricevip'));
				qa_opt('booker_kmrate', (float)qa_post_text('booker_kmrate'));
				qa_opt('booker_mailcopies', (String)qa_post_text('booker_mailcopies'));
				qa_opt('booker_voucherenabled', (bool)qa_post_text('booker_voucherenabled')); // empty or 1
				qa_opt('booker_vouchervalue', (String)qa_post_text('booker_vouchervalue'));
				qa_opt('booker_mailsendername', (String)qa_post_text('booker_mailsendername'));
				qa_opt('booker_mailsenderfooter', htmlentities((String)qa_post_text('booker_mailsenderfooter')));
				qa_opt('booker_uploadpath',(String)qa_post_text('booker_uploadpath'));
				qa_opt('booker_uploadurl', (String)qa_post_text('booker_uploadurl'));
				qa_opt('booker_gmapsapikey', (String)qa_post_text('booker_gmapsapikey'));
				$ok = qa_lang('admin/options_saved');
			}

			// form fields to display frontend for admin
			$fields = array();

			$fields[] = array(
				'type' => 'checkbox',
				'label' => 'Plugin enabled',
				'tags' => 'name="booker_enabled"',
				'value' => qa_opt('booker_enabled'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Currency Symbol:',
				'tags' => 'name="booker_currency"',
				'value' => qa_opt('booker_currency'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Timezone:',
				'tags' => 'name="booker_timezone"',
				'value' => qa_opt('booker_timezone'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Language (specify de, en, fr or … used for calendar and others):',
				'tags' => 'name="booker_language"',
				'value' => qa_opt('booker_language'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Main email sender (if empty, then default feedback email is used):',
				'tags' => 'name="booker_email"',
				'value' => qa_opt('booker_email'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => '<b>Paypal:</b> Receiver email for payments (important):',
				'tags' => 'name="booker_paypal"',
				'value' => qa_opt('booker_paypal'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => '<b>Paypal</b> country code:',
				'tags' => 'name="booker_paypal_lc"',
				'value' => qa_opt('booker_paypal_lc'),
				'note' => '<span style="font-size:75%;color:#789;">
								See <kbd>lc</kbd> at <a target="_blank" href="https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/">paypal variables (and country codes)</a>
						  </span>',
			);

			$fields[] = array(
				'type' => 'text',
				'label' => '<b>Paypal</b> currency:',
				'tags' => 'name="booker_paypal_currency"',
				'value' => qa_opt('booker_paypal_currency'),
				'note' => '<span style="font-size:75%;color:#789;">
								See <a target="_blank" href="https://developer.paypal.com/docs/classic/api/currency_codes/#id09A6G0U0GYK">paypal currency codes</a>
						  </span>',
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Legal operator of the website (important):',
				'tags' => 'name="booker_operator"',
				'value' => qa_opt('booker_operator'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Receiver of bank payments (important):',
				'tags' => 'name="booker_bankdetails"',
				'value' => qa_opt('booker_bankdetails'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => '<b>PaySera</b> country of payer (LT, EE, LV, GB, PL, DE):',
				'tags' => 'name="booker_paysera_paycountry"',
				'value' => qa_opt('booker_paysera_paycountry'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => '<b>PaySera</b> payment language (LIT, LAV, EST, RUS, ENG, GER, POL):',
				'tags' => 'name="booker_paysera_paylanguage"',
				'value' => qa_opt('booker_paysera_paylanguage'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => '<b>PaySera</b> Currency (USD, EUR):',
				'tags' => 'name="booker_paysera_paycurrency"',
				'value' => qa_opt('booker_paysera_paycurrency'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => '<b>PaySera</b> project id:',
				'tags' => 'name="booker_paysera_projectid"',
				'value' => qa_opt('booker_paysera_projectid'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => '<b>PaySera</b> sign password for payments:',
				'tags' => 'name="booker_paysera_sign_password"',
				'value' => qa_opt('booker_paysera_sign_password'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Paymill Private Key for creditcard payments:',
				'tags' => 'name="booker_paymill_private_key"',
				'value' => qa_opt('booker_paymill_private_key'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Paymill Public Key for creditcard payments:',
				'tags' => 'name="booker_paymill_public_key"',
				'value' => qa_opt('booker_paymill_public_key'),
			);

			$fields[] = array(
				'type' => 'Number',
				'label' => 'Minimum contractor price per hour:',
				'tags' => 'name="booker_minimumprice"',
				'value' => qa_opt('booker_minimumprice'),
			);

			$fields[] = array(
				'type' => 'Number',
				'label' => 'Default commission for all users (e.g. 15 % = 0.15):',
				'tags' => 'name="booker_commission"',
				'value' => qa_opt('booker_commission'),
			);

			$fields[] = array(
				'type' => 'Number',
				'label' => 'Default VAT of your country (e.g. 21 % = 0.21):',
				'tags' => 'name="booker_vatvalue"',
				'value' => qa_opt('booker_vatvalue'),
			);

			$fields[] = array(
				'type' => 'Number',
				'label' => 'Monthly Price for Premium Account:',
				'tags' => 'name="booker_pricepremium"',
				'value' => qa_opt('booker_pricepremium'),
			);

			$fields[] = array(
				'type' => 'Number',
				'label' => 'Monthly Price for VIP Account:',
				'tags' => 'name="booker_pricevip"',
				'value' => qa_opt('booker_pricevip'),
			);

			$fields[] = array(
				'type' => 'Number',
				'label' => 'Default mileage (kilometer rate) for all users (e. g. 0.40 € per km):',
				'tags' => 'name="booker_kmrate"',
				'value' => number_format(qa_opt('booker_kmrate'), 2, '.', ','),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Send copies of booking emails to (you can add more emails separated by semicolon):',
				'tags' => 'name="booker_mailcopies"',
				'value' => qa_opt('booker_mailcopies'),
			);

			$fields[] = array(
				'type' => 'checkbox',
				'label' => 'Voucher enabled',
				'tags' => 'name="booker_voucherenabled"',
				'value' => qa_opt('booker_voucherenabled'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Voucher value',
				'tags' => 'name="booker_vouchervalue"',
				'value' => qa_opt('booker_vouchervalue'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Sendername for emails',
				'tags' => 'name="booker_mailsendername"',
				'value' => qa_opt('booker_mailsendername'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Text in footer of emails (html allowed)',
				'tags' => 'name="booker_mailsenderfooter"',
				'value' => qa_opt('booker_mailsenderfooter'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Server path for file uploads in portfolio (e. g. /www/12456/forum/bookbin/)',
				'tags' => 'name="booker_uploadpath"',
				'value' => qa_opt('booker_uploadpath'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'URL to upload folder (e. g. http://www.yoursite.com/bookbin/)',
				'tags' => 'name="booker_uploadurl"',
				'value' => qa_opt('booker_uploadurl'),
			);

			$fields[] = array(
				'type' => 'text',
				'label' => 'Your Google Maps Javascript API key, <a href="https://console.developers.google.com/flows/enableapi?apiid=maps_backend,geocoding_backend,directions_backend,distance_matrix_backend,elevation_backend&keyType=CLIENT_SIDE&reusekey=true">get one here</a>',
				'tags' => 'name="booker_gmapsapikey"',
				'value' => qa_opt('booker_gmapsapikey'),
			);

			$fields[] = array(
				'type' => 'static',
				'value' => '<style type="text/css">
							.qa-form-tall-text { background:#EEF; }

							/*.qa-form-tall-table tr:nth-child(2n) { background:#EEE; }*/
							/* input[name="booker_paypal"] { background:#FFB; } */
							</style>',
			);

			return array(
				'ok' => ($ok && !isset($error)) ? $ok : null,
				'fields' => $fields,
				'buttons' => array(
					array(
						'label' => qa_lang('main/save_button'),
						'tags' => 'name="booker_save"',
					),
				),
			);
		}
	} // END class booker_admin


/*
	Omit PHP closing tag to help avoid accidental output
*/

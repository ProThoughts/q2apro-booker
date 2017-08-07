<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_ics 
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
					'title' => 'booker Page ICS', // title of page
					'request' => 'ics', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='ics') 
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
				qa_set_template('booker ics');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			// called with userid ?userid=135
			
			$useridin = qa_get('userid');

			if(isset($useridin))
			{
				// clean userid input
				$userid = (int)preg_replace('/\D/', '', $useridin);

				$wheremysql = booker_iscontracted($userid) ? 'contractorid = #' : 'customerid = #';
				
				// read in all events for this user, last 14 days events and future events
				$bookedevents = qa_db_read_all_assoc( 
									qa_db_query_sub('SELECT eventid, bookid, created, contractorid, starttime, endtime, customerid, unitprice, needs, attachment, 
																payment, status, protocol 
															FROM `^booking_orders` 
															WHERE '.$wheremysql.' 
															AND starttime >= DATE_SUB(NOW(), INTERVAL 14 DAY)
															AND status > #
															ORDER BY starttime DESC
															;', $userid, MB_EVENT_RESERVED)
								);
								// AND starttime >= CURDATE()
								// AND created >= DATE(NOW()) - INTERVAL 7 DAY
				
				$hasevents = count($bookedevents)>0;
			
				$ical = 'BEGIN:VCALENDAR
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-TIMEZONE:Europe/Berlin
';
				foreach($bookedevents as $event) 
				{
					// if userid is contractor (** otherwise client)
					if(booker_iscontracted($userid))
					{
						$eventtitle = booker_get_realname($event['customerid']);
					}
					else
					{
						$eventtitle = 'Nachhilfe bei '.booker_get_realname($event['contractorid']);						
					}
					
					$eventneeds = $event['needs'];
					$eventstart = $event['starttime'];
					$eventend = $event['endtime'];
					
					// we use domain as calendarid, get @domain.xy 
					$domaininfo = parse_url(qa_opt('site_url'));
					$domain = str_replace('www.', '', $domaininfo['host']);
					
					// write all events as ical file (ics format)
					$ical .= 'BEGIN:VEVENT
UID:' . md5(uniqid(mt_rand(), true)) . '@'.$domain.'
DTSTART:'.$this->dateToCal($eventstart).'
DTEND:'.$this->dateToCal($eventend).'
DTSTAMP:' . gmdate('Ymd').'T'. gmdate('His') . 'Z
SUMMARY:'.$this->escapeString($eventtitle).'
DESCRIPTION:'.$this->escapeString($eventneeds).'
END:VEVENT
';
// *** check: reminder seems to be triggered by Thunderbird each time it starts
/*
BEGIN:VALARM
TRIGGER:-PT24H
ACTION:DISPLAY
DESCRIPTION:Reminder
END:VALARM
*/

// additional reminder
/*REPEAT:1
DURATION:PT15M
*/

					// STATUS:CONFIRMED
					// LOCATION:<?= escapeString($address)
					// URL;VALUE=URI:<?= escapeString($uri)
					// memo: PRODID is Business Name//Product Name//Language.
					// PRODID:-//Google Inc//Google Calendar 70.9054//EN
					// PRODID:-//hacksw/handcal//NONSGML v1.0//EN
					// PRODID:Matheboss Nachhilfe//Matheboss//NONSGML v1.0//EN
					
				} // end foreach
				$ical .= 'END:VCALENDAR';
				
				header('Content-type: text/calendar; charset=utf-8');
				header('Content-Disposition: inline; filename=calendar.ics');
				echo $ical;
			}
			
			exit();
			
		} // end process_request
		
		
		function timestampToCal($timestamp) 
		{
			return date('Ymd\THis\Z', $timestamp);
		}
		
		function dateToCal($date) 
		{
			return date('Ymd\THis\Z', strtotime($date));
		}
		
		// Escapes a string of characters
		function escapeString($string) 
		{
			return preg_replace('/([\,;])/','\\\$1', $string);
		}

	}; // end class
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
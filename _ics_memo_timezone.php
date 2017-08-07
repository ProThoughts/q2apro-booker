<?php
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename=calendar.ics');
// do file output and ensure UTF8

// docs http://simplehtmldom.sourceforge.net/manual_api.htm
// helpful http://api.drupal.psu.edu/api/drupal/modules!contrib!simplehtmldom!simplehtmldom!manual!manual.htm/cis7
require_once('simple_html_dom.php');

// create DOM from URL
$html = file_get_html('http://www.kulturosfabrikas.lt/lt/renginiai/renginiai/');

// calendar output
$ical = 'BEGIN:VCALENDAR
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-TIMEZONE:Europe/Vilnius
';
	// go over all events
	foreach($html->find('.renginiai') as $item) 
	{
		$eventlink = $item->find('.img_holder', 0)->children(0)->href;
		$eventimage = $item->find('.img_holder', 0)->children(0)->children(0)->src;
		$eventdate = trim($item->find('.date', 0)->plaintext); // 2016-09-15 19:00
		$eventtitle = $item->find('h1', 0)->children(0)->plaintext;
		// parse quot; etc.
		$eventtitle = htmlspecialchars_decode($eventtitle);
		// not longer than 75 chars
		$eventtitle = substr($eventtitle, 0, 67);
		
		$eventdesc = $eventlink; // actually only 75 chars allowed, http://icalendar.org/iCalendar-RFC-5545/3-1-content-lines.html
		
		// we use domain as calendarid, get @domain.xy 
		$domaininfo = parse_url('http://www.kulturosfabrikas.lt/');
		$domain = str_replace('www.', '', $domaininfo['host']);
		
// write all events as ical file (ics format)
$ical .= 'BEGIN:VEVENT
UID:' . md5(uniqid(mt_rand(), true)) . '@'.$domain.'
DTSTART:'.dateToCal($eventdate).'
DTEND:'.dateToCal($eventdate, 2).'
DTSTAMP:' . gmdate('Ymd').'T'. gmdate('His') . 'Z
LOCATION:Bangu g. 5\, Klaipeda
SUMMARY:'.escapeString($eventtitle).'
DESCRIPTION:'.escapeString($eventdesc).'
END:VEVENT
';
// DTSTAMP is time of ics file creation

// reminder seems to be triggered by Thunderbird each time it starts
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
	
	// validator: http://icalendar.org/validator.html
	echo $ical;
	exit();

	
	function timestampToCal($timestamp) 
	{
		return date('Ymd\THis\Z', $timestamp);
	}
	
	function dateToCal($timestamp, $addhours=null) 
	{
		// timestamp in your local time and your local time zone
		$timezone = new DateTimeZone('Europe/Vilnius');

		// UTC timezone
		$utc = new DateTimeZone('UTC');

		// Create a DateTime object from your timestamp using your local timezone
		$datetime = DateTime::createFromFormat("Y-m-d H:i", $timestamp, $timezone);
		
		// Set the timezone on the object to UTC
		$datetime->setTimezone($utc);

		// date_default_timezone_set('Europe/Vilnius');
		
		if(isset($addhours))
		{
			// memo: Z at the end to mark the timestamp as UTC (!)
			// return date('Ymd\THis\Z', strtotime($timestamp)+$addhours*60*60);
			
			// add hours
			$datetime->modify("+1 hour");
			// time in UTC and use the correct format for ICS
			return $datetime->format('Ymd\THis\Z');
		}
		// return date('Ymd\THis\Z', strtotime($timestamp));
		return $datetime->format('Ymd\THis\Z');
	}
	
	// Escapes a string of characters
	function escapeString($string) 
	{
		return preg_replace('/([\,;])/','\\\$1', $string);
	}

	
/*

/*
<!-- THIS IS WHAT WE PARSE FROM KUFA -->
  <div class="grid-item renginiai">
    <div class="img_holder">
		<a href="http://www.kulturosfabrikas.lt/lt/renginys/639/grock-studijos-koncertas-muzikinis-seimyninis-tuselis">
			<img alt="" src="http://www.kulturosfabrikas.lt/image/events/seimu_tuselis_TIK_I_FB_GALIMA_DETI_NESPAUDAI_2_.PNG" />
		</a>
	</div>
    <div class="grid-item-padding">
        <span class='date'>2016-10-02 12:00</span>
		<br/>
        <h1>
			<a href="http://www.kulturosfabrikas.lt/lt/renginys/639/grock-studijos-koncertas-muzikinis-seimyninis-tuselis">
				Grock studijos koncertas &quot;Muzikinis - šeimyninis tuselis&quot;
			</a>
		</h1>
        www.bilietupasaulis.lt        
        <div class='event_type'>
			Renginiai
		</div>
    </div>
  </div>
*/


?>
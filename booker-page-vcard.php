<?php
/*
Plugin Name: BOOKER
*/

class booker_page_vcard
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
				'title' => 'booker Page vcard Profile', // title of page
				'request' => 'vcard', // request name
				'nav' => 'M',
			),
		);
	}

	// for url query
	function match_request($request)
	{
		if($request=='vcard')
		{
			return true;
		}
		return false;
	}

	function process_request($request)
	{

		$userid = qa_get('userid');
		if(empty($userid))
		{
			exit();
		}

		$ispremium = booker_ispremium($userid);

		if($ispremium)
		{
			$realname = booker_get_realname($userid);
			$filename = 'vcard-'.strtolower(str_replace(' ', '-', $realname));
			if(empty($filename))
			{
				exit();
			}

			/* get user data, process it to vcard format */
			$userdata = booker_getfulluserdata($userid);

 			// *** fields should be optional
			$contractorname = $userdata['realname'];
			$birthday = $userdata['birthdate'];
			$phone = $userdata['phone'];
			$address = ''; // $userdata['address']; // privacy
			$skype = $userdata['skype'];
			$company = '';
			$jobtitle = '';
			$url = '';

			// can go into notes field
			$service = $userdata['service'];
			$portfolio = $userdata['portfolio'];

			$note = $service.' # '.$portfolio;
			if(!empty($note) && strlen($note)>75)
			{
				$note = strip_tags($note);
				// remove whitespaces
				$note = preg_replace("/\s+/", " ", $note);
				$note = trim($note);
				// $note = wordwrap($note, 75, "\n", true);
			}

			// $bookingprice = (float)$userdata['bookingprice'];
			// $available  = ($userdata['available']==1);
			// $userflags = $userdata['flags'];

			$email = qa_db_read_one_value(
			qa_db_query_sub('SELECT email FROM `^users`
				WHERE userid = #
				', $userid), true );

				$nameparts = explode(' ', $contractorname);
				$namepartcount = count($nameparts);

				// in case we have several first names
				$firstname = '';
				for($i=0; $i<$namepartcount-1; $i++)
				{
					$firstname .= $nameparts[$i].' ';
				}
				$firstname = trim($firstname);
				$lastname = $nameparts[$namepartcount-1];

				// init
				$vcard_output = '';

				$vcard_output .= 'BEGIN:VCARD
VERSION:3.0
N:'.$lastname.';'.$firstname.';;;
FN:'.$contractorname.'
TEL;CELL:'.$phone.'
EMAIL;PREF;WORK:'.$email.'
ADR;WORK:;;'.$address.'
ORG:'.$company.'
TITLE:'.$jobtitle.'
URL:'.$url.'
NOTE:'.$note.'
';

				// process user avatar
				/*
				$avatarblobid = qa_db_read_one_value(
				qa_db_query_sub('SELECT avatarblobid FROM ^users
					WHERE userid = #',
					$userid
				));

				if(isset($avatarblobid))
				{
					require_once QA_INCLUDE_DIR.'app/blobs.php';
					$blob = qa_read_blob($avatarblobid);
					$blobcontent = $blob['content'];
					$blobtype = $blob['format'];
					// $base64 = 'data:image/' . $type . ';base64,' . base64_encode($blobdata);
					$base64 = 'PHOTO;ENCODING=BASE64;'.strtoupper($blobtype).':'.base64_encode($blobcontent);
					// max 75 chars per line
					$imageastext = wordwrap($base64, 75, "\n", true);

					// PHOTO;ENCODING=BASE64;JPEG:/9j/4AAQSkZJRgABAQAAAQABAAD ...
					$vcard_output .= $imageastext;
				}
				*/

				// 1981-03-31
				$vcard_output .= '
BDAY:'.$birthday.'
END:VCARD
';

				// http://www.freeformatter.com/mime-types-list.html
				// header('Content-Type: text/x-vcard; charset=utf-8');
				// header('Content-Type: text/x-vcard; charset=windows-1252');
				header('Content-Type: text/x-vcard');
				header('Content-Disposition: inline; filename="'.$filename.'.vcf"');
				echo $vcard_output;
				exit();
			} // end $ispremium

			exit();
		} // END process_request

	}; // end class booker_page_vcard


	/*
	Omit PHP closing tag to help avoid accidental output
	*/

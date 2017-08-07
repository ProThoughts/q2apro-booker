<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_clientschedule
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
					'title' => 'booker Page Client schedule', // title of page
					'request' => 'clientschedule', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='clientschedule')
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
				qa_set_template('booker clientschedule');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			$userid = qa_get_logged_in_userid();

			// only members can access
			if(empty($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker clientschedule');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}

			// super admin can have view of others for profile if adding a userid=x to the URL
			$isadmin = qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN;
			if($isadmin)
			{
				$userid = qa_get('userid');
				if(empty($userid))
				{
					$userid = qa_get_logged_in_userid();
				}
			}

			// HANDLE AJAX
			$transferString = qa_post_text('orderdata');
			if(isset($transferString))
			{
				header('Content-Type: application/json; charset=UTF-8');

				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				// go over event objects and save data
				foreach($newdata as $event)
				{
					if(isset($event['eventid']))
					{
						$eventid = $event['eventid'];
						$event_details = !empty($event['needs']) ? $event['needs'] : null;
						$attachment = !empty($event['attachment']) ? $event['attachment'] : null;

						if(empty($event_details) && empty($attachment))
						{
							// ignore
							continue;
						}

						// only update needs if NOT empty
						if(!empty($event_details))
						{
							qa_db_query_sub('UPDATE `^booking_orders` SET needs = #
												WHERE eventid = #
												',
												$event_details, $eventid);
						}
						// only update protocol if NOT empty
						if(!empty($attachment))
						{
							qa_db_query_sub('UPDATE `^booking_orders` SET attachment = #
												WHERE eventid = #
												',
												$attachment, $eventid);
						}
						// former
						/*qa_db_query_sub('UPDATE `^booking_orders` SET needs = #, attachment = #
											WHERE eventid = #
											',
											$event_details, $attachment, $eventid);*/
						// LOG
						$eventname = 'client_setdetails';
						// *** actually should compare former with new entries and only log if different
						$params = array(
							'details' => $event_details,
							'attachment' => $attachment
						);
						booker_log_event($userid, $eventid, $eventname, $params);
					}
				}

				// ajax return success
				$arrayBack = array(
					'updated' => '1'
				);
				echo json_encode($arrayBack);

				exit();
			} // END AJAX RETURN (orderdata)


			$transferString = qa_get('action');
			if(isset($transferString))
			{
				$action = qa_get('action');
				$eventid = qa_get('eventid');
				if(empty($action) && empty($eventid))
				{
					$qa_content = qa_content_prepare();
					qa_set_template('booker clientschedule');
					$qa_content['error'] = 'Missing data';
					return $qa_content;
				}

				if($action=='completed')
				{
					booker_set_status_event($eventid, MB_EVENT_COMPLETED);
				}

				qa_redirect('clientschedule');
			} // end transfer action


			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker clientschedule');
			$qa_content['title'] = qa_lang('booker_lang/clisched_title');

			// filter by eventid
			$eventidfilter = qa_get('eventid');

			$weekdays = helper_get_weekdayarray();
			$ratingsymbols = helper_get_ratingsymbols();

			// init
			$qa_content['custom'] = '';

			if(empty($eventidfilter))
			{
				$qa_content['custom'] .= '
					<p style="margin:10px 0 30px 2px;font-size:13px;">
						'.qa_lang('booker_lang/specify_needs').'
					</p>';
			}
			else
			{
				$qa_content['title'] = qa_lang('booker_lang/singleappt');
				$qa_content['custom'] .= '
					<p style="margin:10px 0 30px 2px;font-size:13px;">
						'.
						strtr( qa_lang('booker_lang/onlysingleappt'), array(
							'^1' => '<a href="'.qa_path('clientschedule').'">',
							'^2' => '</a>'
						  ))
						 .
					'</p>';
			}


			if(empty($eventidfilter))
			{
				// get all schedule from last 30 days until future AND-OR not confirmed events
				$schedule = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT eventid, bookid, starttime, endtime, contractorid, unitprice, needs, attachment, status, protocol FROM `^booking_orders`
															WHERE `customerid` = #
															AND
															(
															`starttime` >= DATE_ADD(CURDATE(), INTERVAL -30 DAY)
															OR
															`status` >= #
															)
															AND status != #
															ORDER BY starttime DESC
															;', $userid, MB_EVENT_ACCEPTED, MB_EVENT_OPEN)
												);
			}
			else
			{
				// security
				$eventidfilter = preg_replace('/\D/', '', $eventidfilter);
				// get specific order
				$schedule = qa_db_read_all_assoc(
									qa_db_query_sub('SELECT eventid, bookid, starttime, endtime, contractorid, unitprice, needs, attachment, status, protocol FROM `^booking_orders`
															WHERE `customerid` = #
															AND eventid = #
															;', $userid, $eventidfilter)
												);
			}

			if(count($schedule)==0)
			{
				if(empty($eventidfilter))
				{
					$qa_content['custom'] .= '
					<p style="color:#00F;">
						'.qa_lang('booker_lang/no_bookings').'
					</p>
					';
				}
				else
				{
					$qa_content['custom'] .= '
					<p class="qa-error">
						'.qa_lang('booker_lang/noappts').'
					</p>
					';
				}
				return $qa_content;
			}
			$orderlist = '';
			$orderlist .= '
			<div class="ordertablewrap">
				<table id="ordertable">
				<tr>
					<th>'.qa_lang('booker_lang/date').'</th>
					<th>'.qa_lang('booker_lang/time').'</th>
					<th>'.qa_lang('booker_lang/value').'</th>
					<th>'.qa_lang('booker_lang/contractor').'</th>
					<th>'.qa_lang('booker_lang/eventdetails').'</th>
					<th>'.qa_lang('booker_lang/attachments').'</th>
				</tr>
			';
			foreach($schedule as $event)
			{
				$inputrequired = false;
				$attachment = $event['attachment'];
				$attachment_show = '';
				$uploadbuttons = '';

				if(!empty($attachment))
				{
					$attlinks = explode(';', $attachment);
					$count = 0;
					foreach($attlinks as $link)
					{
						$count++;
						$attachment_show .= '• <a class="fileexists" title="'.$link.'" href="'.$link.'" target="_blank">'.qa_lang('booker_lang/file').' '.$count.'</a><br />';
					}
					$uploadbuttons = '<span class="uploadbutton">'.qa_lang('booker_lang/uploadfile_another').'</span>';
				}
				else
				{
					$uploadbuttons .= '<span class="uploadbutton">'.qa_lang('booker_lang/uploadfile').'</span>';
				}

				// $contractorrealname = booker_get_userfield($event['contractorid'], 'realname');
				$contractorname = booker_get_realname($event['contractorid']);

				$contractorfield = $contractorname;

				$contractorskype = booker_get_userfield($event['contractorid'], 'skype');
				if(empty($contractorskype))
				{
					$contractorskype = '';
				}
				else
				{
					$contractorskype = '· <a class="contractorlinksml" href="skype:'.$contractorskype.'?chat">Skype</a>';
				}
				// client cannot change text for order needs if order in past
				if(strtotime($event['endtime']) < time())
				{
					$needsfield = ' <p>'.
										$event['needs'].
									'</p>
								  ';
				}
				else
				{
					$needsfield = '<textarea id="eventdetails_'.$event['eventid'].'" class="needsinput" placeholder="'.qa_lang('booker_lang/apptdetails').'">'.$event['needs'].'</textarea>';
					if($event['status']!=MB_EVENT_COMPLETED)
					{
						$inputrequired = true;
					}
				}

				$status_show = booker_get_eventname_lang($event['status']);
				$protocoltext = '';
				// if($event['status']==MB_EVENT_PAID)
				if(booker_event_is_paid($event['eventid']))
				{
					// $status_show = qa_lang('booker_lang/paid');
				}
				else if($event['status']==MB_EVENT_COMPLETED)
				{
					$status_show = '<span class="orderdonecm tooltip" title="'.qa_lang('booker_lang/completed').'">✔</span>';

					if(!empty($event['needs']))
					{
						$needsfield = '<span class="detailstext">'.qa_lang('booker_lang/order').': '.$event['needs'].'</span>';
					}

					/*
					if(!empty($event['protocol']))
					{
						$protocoltext = '
							<p class="protocoltext">
								'.qa_lang('booker_lang/protocol').': '.$event['protocol'].'
							</p>
							';
					}
					*/
				}

				// if($event['status']==MB_EVENT_PAID || $event['status']==MB_EVENT_COMPLETED)
				// if(booker_event_is_paid($event['eventid']) || $event['status']==MB_EVENT_COMPLETED)
				if(true) // new: always show contact details
				{
					$contractorfield = '
						<a href="'.qa_path('booking').'?contractorid='.$event['contractorid'].'">'.$contractorname.'</a> <br />
						<a class="contractorlinksml" href="'.qa_path('mbmessages').'?to='.$event['contractorid'].'">'.qa_lang('booker_lang/message').'</a> '
						.$contractorskype;
				}

				$fileupload = '';
				if($event['status']!=MB_EVENT_COMPLETED)
				{
					$fileupload =
					'
						<input class="orderlink" type="hidden" name="orderlink" value="'.$attachment.'">
						<input class="materialup" type="file" name="orderfile"> <br />
						<progress style="display:none;"></progress>
						<span class="successmsg">'.qa_lang('booker_lang/uploadsuccess').'</span>
						<span class="errormsg"></span>
					';
				}

				$trline = '<tr class="ordertrdone">';
				if($inputrequired)
				{
					$trline = '<tr id="'.$event['eventid'].'" class="ordertr bookid_'.$event['bookid'].'">';
				}
				else
				{
					$uploadbuttons = '';
				}

				$actionbutton = '';
				// customer must confirm events (with x days, then automatic)
				if(strtotime($event['endtime']) < time() && $event['status']>=MB_EVENT_RESERVED && $event['status']!=MB_EVENT_COMPLETED)
				{
					$actionbutton = '
						<a class="paidreminder" href="'.qa_path('clientschedule').'?action=completed&amp;eventid='.$event['eventid'].'" title="'.qa_lang('booker_lang/completed_tooltip').'">'.qa_lang('booker_lang/completed_button').'</a>
						<br>
					';
				}

				$eventvalue = '<span class="eventvalue">'.booker_get_eventprice($event['eventid'], true).' €</span>';
				$eventduration = booker_get_timediff($event['starttime'], $event['endtime']);

				$orderlist .= $trline.'
						<td>
							<a href="'.qa_path('clientschedule').'?eventid='.$event['eventid'].'">
							'.$weekdays[ (int)(date('N', strtotime($event['starttime'])))-1 ].', '.date(qa_lang('booker_lang/date_format_php'), strtotime($event['starttime'])).'</a>
							<br />
							<span class="eventstatus">'.$status_show.'</span>
						</td>
						<td>
							'.substr($event['starttime'],10,6).' - '.substr($event['endtime'],10,6).'
							<br>
							<span style="font-size:10px;">'.$eventduration.' min</span>
						</td>
						<td>
							'.$eventvalue.'
							<br />
							<span style="color:#789;font-size:10px;">
								'.number_format($event['unitprice'],2,',','.').' '.qa_opt('booker_currency').' / '.qa_lang('booker_lang/hourabbr').'
							</span>
						</td>
						<td>
							'.$contractorfield.'
						</td>
						<td>
							'.
							$needsfield.
							// $protocoltext.
							$actionbutton.
						'</td>
						<td>
							'.$attachment_show.'
							'.$uploadbuttons.'
							'.($inputrequired ? $fileupload : '').'
						</td>
					</tr>
				';
			} // end foreach $userpayments

			$orderlist .= '
					</table> <!-- ordertable -->

					<div style="margin:30px 0;">
						<p class="smsg">'.qa_lang('booker_lang/savesuccess').'</p>
						<button class="defaultbutton savebutton">'.qa_lang('booker_lang/save_btn').'</button>
					</div>
				</div> <!-- ordertablewrap -->
			';


			// output
			$qa_content['custom'] .= $orderlist;

			// jquery
			$qa_content['custom'] .= "
<script>

	$(document).ready(function()
	{

		// attachment per order
		$('.materialup').change( function(e) {
			uploadfile($(this));
		});

		// upload after user has chosen the image
		var activefield;
		function uploadfile(inputf)
		{
			console.log('submitting file via html5 ajax');
			activefield = inputf;

			// check for maximal image size
			var maxfilesize = 8*1024*1024; // MB
			var filesize = inputf[0].files[0].size;
			// console.log(maxfilesize + ' | ' + filesize);
			if(filesize > maxfilesize) {
				var img_size = (Math.round((filesize/1024/1024) * 100) / 100);
				var maximg_size = (Math.round((maxfilesize / 1024 / 1024) * 100) / 100);
				alert('".qa_lang('booker_lang/filesizewarning')."');
				return;
			}

			// append file to object to be sent
			var filedata = new FormData();
			filedata.append('imgfile', inputf[0].files[0]);

			$.ajax({
				url: '".qa_path('mbupload')."', // server script to process data, see booker-page-mbupload.php
				type: 'POST',
				xhr: function() {
					// custom XMLHttpRequest
					var myXhr = $.ajaxSettings.xhr();
					if(myXhr.upload){
						// check if upload property exists, handling the progress of the upload
						myXhr.upload.addEventListener('progress',progressHandlingFunction, false);
					}
					return myXhr;
				},
				// ajax events
				beforeSend: beforeSendHandler,
				success: completeHandler,
				error: errorHandler,
				data: filedata, // form data
				cache: false,
				contentType: false,
				processData: false
			});
		};
		function beforeSendHandler(e)
		{
			activefield.parent().find('progress').show();
		}
		function progressHandlingFunction(e)
		{
			if(e.lengthComputable){
				// html5 progressbar
				activefield.parent().find('progress').attr({value:e.loaded,max:e.total});
			}
		}
		// success
		function completeHandler(e)
		{
			if(e.indexOf('http')>-1)
			{
				// activefield is input field
				activefield.parent().find('progress').hide();
				// in case we have already filelinks in input field
				var links = activefield.parent().find('.orderlink').val();
				if(links.length>0) {
					links += ';'+e;
				}
				else {
					// e holds link to uploaded file
					links = e;
				}
				activefield.parent().find('.orderlink').val(links); // write url to input field
				activefield.parent().find('.successmsg').show().fadeOut(3000);
				activefield.parent().find('.uploadbutton').text('".qa_lang('booker_lang/uploadfile_another')."');

				// add to filelist
				var filesonstage = activefield.parent().find('.fileexists').length;
				if(filesonstage>0) {
					activefield.parent().find('.fileexists:last').after('<br />• <a class=\"fileexists\" href=\"'+e+'\" target=\"_blank\">".qa_lang('booker_lang/file')." '+(filesonstage+1)+'</a>');
				}
				else {
					activefield.parent().find('.uploadbutton').before('• <a class=\"fileexists\" href=\"'+e+'\" target=\"_blank\">".qa_lang('booker_lang/file')." 1</a> <br />');
				}

				// save all data for customer, in case he forgets
				$('.savebutton').trigger('click');
			}
			else
			{
				// show error message
				activefield.parent().find('.errormsg').text(e).show().fadeOut(3000);
				activefield.parent().find('progress').hide();
			}
		}
		function errorHandler(e)
		{
			console.log('Server error: '+e);
		}

		// hide all browse buttons if file already exists
		$('.uploadbutton').parent().find('.materialup').hide();

		var uploadbuttonr = null;
		$('.uploadbutton').click( function() {
			// $(this).parent().find('.materialup').show();
			$(this).parent().find('.materialup').trigger('click');
			// uploadbuttonr = $(this);
		});

		// orderdata
		$('.savebutton').click( function(e) {
			e.preventDefault();
			var gdata = [];
			var indx = 0;
			$('#ordertable tr.ordertr').each( function(index) {
				gdata[indx] = {
					'eventid': $(this).attr('id'),
					'needs': $(this).find('.needsinput').val(),
					'attachment': $(this).find('.orderlink').val(),
				};
				indx++;
			});

			// console.log(gdata);
			var senddata = JSON.stringify(gdata);
			console.log('Sending to Server: '+senddata);

			$.ajax({
				type: 'POST',
				url: '".qa_self_html()."',
				data: { orderdata:senddata },
				dataType: 'json',
				cache: false,
				success: function(data) {
					console.log('server returned: ');
					console.log(data);
					console.log('x' +data['updated']);
					$('.smsg').show().fadeOut(3000, function() { });
				},
				error: function(xhr, status, error) {
					console.log('problem with server:');
					console.log(xhr.responseText);
					console.log(error);
				}
			}); // end ajax
		});// end savebutton click

		// focus field in case it is set in URL
		var focusfieldid = getURLParameter('bookid');
		if( ( typeof(focusfieldid) !== 'undefined') && (focusfieldid != null) && (focusfieldid!='null') && (focusfieldid!='') )
		{
			$('.bookid_'+focusfieldid+' textarea').focus();
		}
	}); // end jquery ready

	function getURLParameter(name) {
		return decodeURI(
			(RegExp(name + '=' + '(.+?)(&|$)').exec(location.search)||[,null])[1]
		);
	}

</script>
			";

			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:90%;
				}
				.qa-main p {
					line-height:150%;
				}
				.smsg {
					display:none;
					font-size:14px;
					margin:10px 0 0 0;
					color:#00F;
					text-align:right;
				}
				.needsinput {
					width:100%;
					padding:5px;
					border:1px solid #DDD;
				}
				.materialinput {
					width:90%;
					padding:5px;
					border:1px solid #DDD;
				}
				.needsinput:focus, .materialinput:focus {
					background:#FFE;
				}
				.bookingtablewrap, .honorartablewrap, .ordertablewrap {
					display:block;
					width:95%;
					font-size:13px;
				}
				#bookingtable, #honorartable, #ordertable {
					display:table;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
					width:100%;
					/*max-width:1080px;*/
				}
				#bookingtable th, #honorartable th, #ordertable th {
					font-weight:normal;
					background:#FFC;
				}
				#bookingtable td, #bookingtable th,
				#honorartable td, #honorartable th,
				#ordertable td, #ordertable th {
					padding:5px 5px 25px 5px;
					border:1px solid #DDD;
					font-weight:normal;
					line-height:150%;
					text-align:left;
				}
				#ordertable th {
					padding:7px 5px;
				}
				#ordertable tr:nth-child(even) {
					background:#EEE;
				}
				#ordertable tr:nth-child(odd) {
					background:#FAFAFA;
				}
				#ordertable tr td {
					vertical-align:top;
				}
				#ordertable td:nth-child(1) {
					width:12%;
				}
				#ordertable td:nth-child(2) {
					width:12%;
				}
				#ordertable td:nth-child(3) {
					width:12%;
				}
				#ordertable td:nth-child(4) {
					width:12%;
				}
				#ordertable td:nth-child(5) {
					width:40%;
				}
				#ordertable td:nth-child(6) {
					width:12%;
				}

				/* make button right aligned to table bottom */
				tfoot tr, tfoot td {
					background:transparent !important;
					border:1px solid transparent !important;
					text-align: right;
				}
				.savebutton {
					margin:10px 0px 30px 0;
					float:right;
				}
				.successmsg {
					display:none;
					color:#390;
				}
				.errormsg {
					display:block;
					visibility:none;
					color:#F00;
				}
				.paidreminder,
				.uploadbutton {
					display:inline-block;
					margin:5px 0;
					padding:5px 10px;
					font-size:12px;
					background:#38F;
					color:#FFF;
					cursor:pointer;
				}
				.uploadbutton:hover {
					text-decoration:underline;
				}
				.paidreminder
				{
					padding:5px 7px;
					background:#F99;
				}
				.paidreminder:hover {
					color:#FFF;
					background:#F55;
				}
				.fileexistsholder {
					display:block;
				}

				.contractorlinksml {
					font-size:10px;
					cursor:pointer;
				}
				.protocoltext {
					margin:10px 0 0 0;
				}
				.orderdonecm {
					text-align:center;
					display:inline-block;
					width:20px;
					height:20px;
					border-radius:10px;
					background:#7C3;
					background:rgba(119, 174, 57, 0.8);
					color:#FFF;
					font-size:10px;
					cursor:default;
				}
				.ratingspan {
					display:block;
					padding-top:5px;
					color:#123;
				}

				/* smartphones */
				@media only screen and (max-width:480px) {
					.qa-main {
						width:95%;
					}
					.ordertable {
						table-layout:fixed;
					}
				}
			</style>';


			return $qa_content;
		} // end process_request

	}; // END class booker_page_clientschedule

/*
	Omit PHP closing tag to help avoid accidental output
*/

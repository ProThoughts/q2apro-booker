<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_bid
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
					'title' => 'booker bid Page', // title of page
					'request' => 'bid', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='bid')
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
				qa_set_template('booker bid');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			// AJAX: contractor posts is bid, save events to db
			$transferString = qa_post_text('biddata'); // holds array
			if(isset($transferString))
			{
				header('Content-Type: application/json; charset=UTF-8');

				$userid = qa_get_logged_in_userid();

				$newdata = json_decode($transferString, true);
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				if(count($newdata)==0)
				{
					header('HTTP/1.1 500 Internal Server Error');
					echo 'No data received.';
					return;
				}

				// $contractorid = $userid;
				// if admin then take from $newdata['contractorid'];

				$requestid = $newdata['requestid'];
				$bidprice = $newdata['bidprice'];
				$bidcomment = $newdata['bidcomment'];

				if(!empty($requestid) && !empty($bidprice))
				{
					qa_db_query_sub('INSERT INTO ^booking_bids (created, requestid, userid, price, comment)
													VALUES (NOW(), #, #, #, $)',
															$requestid, $userid, $bidprice, $bidcomment);
					$bidid = qa_db_last_insert_id();
					// LOG
					$eventid = null;
					// $eventname = booker_get_eventname(MB_EVENT_OPEN);
					$eventname = 'bid_posted';
					$params = array(
						'bidid' => $bidid,
						'requestid' => $requestid,
						'price' => $bidprice,
						'bidcomment' => $bidcomment
					);
					booker_log_event($userid, $eventid, $eventname, $params);

					// ajax return success
					$arrayBack = array(
						'bidid' => $bidid,
						'bidprice' => $bidprice,
						'bidcomment' => $bidcomment,
						'message' => 'posted'
					);
				}
				else
				{
					// ajax return error
					$arrayBack = array(
						'message' => 'error'
					);
				}

				echo json_encode($arrayBack);

				exit();
			} // END AJAX RETURN (biddata)


			$userid = qa_get_logged_in_userid();

			$requestid = qa_get('requestid');

			if(empty($requestid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker bid');
				$qa_content['error'] = qa_lang('booker_lang/noservice');
				return $qa_content;
			}
			// if not registered
			if(empty($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker bid');
				$qa_content['title'] = qa_lang('booker_lang/bid_pagetitle');
				$qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needreglogin_service'));
			}


			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker bid');
			$qa_content['title'] = qa_lang('booker_lang/bid_pagetitle').':';
			qa_set_template('booker bid');

			// init
			$qa_content['custom'] = '';
			$output = '';

			// get data of request
			$requestdata = qa_db_read_one_assoc(
									qa_db_query_sub('SELECT created, userid, title, price, end, description, location, status FROM `^booking_requests`
															WHERE requestid = #
															',
															$requestid), true);

			if(empty($requestdata))
			{
				$qa_content['error'] = qa_lang('booker_lang/request_notexist');
				return $qa_content;
			}

			// Check if user already posted his bid
			$biddata = qa_db_read_one_assoc(
									qa_db_query_sub('SELECT bidid, created, price, comment FROM `^booking_bids`
															WHERE requestid = #
															AND userid = #
															',
															$requestid, $userid), true);

			$userbidalready = false;
			if(!empty($biddata))
			{
				$userbidalready = true;
				$output .= '
				<p class="qa-error">
					'.str_replace('~date~', helper_get_readable_date_from_time($biddata['created'], true), qa_lang('booker_lang/alreadybid')).'
				</p>
				';
			}

			$qa_content['title'] = '<a href="'.qa_self_html().'">'.qa_lang('booker_lang/bid_pagetitle').': '.$requestdata['title'].'</a>';
			$hide_bidfield = false;

			// check if request status is correct
			$request_status = booker_get_requeststatus($requestid);
			if($request_status == MB_REQUEST_CREATED)
			{
				$qa_content['error'] = qa_lang('booker_lang/request_tobechecked');
				$hide_bidfield = true;
			}
			else if($request_status == MB_REQUEST_APPROVED)
			{
				// continue
			}
			else if($request_status == MB_REQUEST_DISAPPROVED)
			{
				$qa_content['error'] = qa_lang('booker_lang/request_gotdisapproved');
				return $qa_content;
			}
			else if($request_status == MB_REQUEST_DELETED)
			{
				$qa_content['error'] = qa_lang('booker_lang/request_gotdeleted');
				return $qa_content;
			}

			// make sure that user cannot bid on his own requests
			if($userid == $requestdata['userid'])
			{
				$hide_bidfield = true;
			}

			// check if request time is valid
			$endstring = '';
			$endtime = DateTime::createFromFormat('Y-m-d H:i:s', $requestdata['end']);
			$timenow = new DateTime('now', new DateTimeZone( qa_opt('booker_timezone') ));
			// $diffsecs = $endtime->format('U') - $timenow->format('U');

			if($endtime < $timenow && $request_status == MB_REQUEST_APPROVED)
			{
				$qa_content['error'] = qa_lang('booker_lang/request_expired');
				$hide_bidfield = true;
			}
			else
			{
				$endtime = DateTime::createFromFormat('Y-m-d H:i:s', $requestdata['end']);
				$now = new DateTime('now', new DateTimeZone( qa_opt('booker_timezone') ));
				$diffsecs = $endtime->format('U') - $now->format('U');
				if(!empty($requestdata['end']))
				{
					$endstring = '
					<div class="requestendtimebox">
						<i class="fa fa-clock-o fa-2x" style="float:left;margin-right:10px;"></i>
						'.booker_get_time_to_end($diffsecs).'
						<br />
						<span style="font-size:12px;color:#AAA;">
							'.helper_get_readable_date_from_time($requestdata['end'], true, false).'
						</span>
					</div>
					';
				}
			}

			$requestlocation = '';
			if(!empty($requestdata['location']))
			{
				$requestlocation = '
					<div class="requestloco">
						'.qa_lang('booker_lang/location').': '.$requestdata['location'].'
					</div>
				';
			}

			// edit link for admin
			if(qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN)
			{
				$output .= '
				<p>
					<a style="float:right;" class="defaultbutton btn_graylight buttonthin" href="'.qa_path('requestcreate').'?requestid='.$requestid.'">'.qa_lang('booker_lang/request_edit').'</a>
				</p>
				';
			}

			$requestername = booker_get_realname($requestdata['userid']);
			$requesterhandle = helper_gethandle($requestdata['userid']);
			$output .= '
			<p>
				'.qa_lang('booker_lang/from').' <a href="'.qa_path('user').'/'.$requesterhandle.'">'.$requestername.'</a> 
			</p>
			';

			$output .= '
			<div class="requestmainwrap">

				<div class="requestdatawrap">

					<div class="requestdescription">
						'.$requestdata['description'].'
					</div>

					'.$requestlocation.'

				</div> <!-- requestdatawrap -->

				'.$endstring.'

				<div style="clear:both;"></div>

			</div> <!-- requestmainwrap -->
			';

			$loginnotice = '';

			if(isset($userid) && !$userbidalready && !$hide_bidfield)
			{
				$output .= '
					<div class="biddatawrap">
						<h2>
							'.qa_lang('booker_lang/yourpricebid').':
						</h2>

						<p style="margin:10px 0 30px 0;">
							'.qa_lang('booker_lang/bid_pricehint').'
						</p>

						<div class="biddatatable">
							<div>
								<span>
									'.qa_lang('booker_lang/yourprice').':
								</span>
								<span>
									<input type="text" name="bidprice" id="bidprice" placeholder="'.qa_lang('booker_lang/yourprice_placeholder').'" />
									&nbsp;'.qa_opt('booker_currency').'
								</span>
							</div>
							<div>
								<span>
									'.qa_lang('booker_lang/yourcomment').':
								</span>
								<span>
									<textarea name="bidcomment" id="bidcomment" placeholder="'.qa_lang('booker_lang/optional').'"></textarea>
								</span>
							</div>

							<div>
								<span>
									&nbsp;
								</span>
								<span style="padding-top:20px;">
									<a class="defaultbutton btn_orange senddatabtn">'.qa_lang('booker_lang/postoffer_btn').'</a>
								</span>
							</div>
						</div> <!-- biddatatable -->
					</div> <!-- biddatawrap -->
					';
			} // end !$userbidalready

			if(empty($userid))
			{
				$loginnotice = '
					<p class="qa-warning">
						'.qa_lang('booker_lang/onlyregistered').'
						'.qa_insert_login_links(qa_lang('booker_lang/needregisterlogin')).'
					</p>
				';
			}

			$qa_content['custom'] .= $output;

			$qa_content['custom'] .= $loginnotice;

			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-main h1 {
					margin-bottom:20px;
				}
				.qa-main h1 a,
				.qa-main h1 a:hover {
					color:#222;
					text-decoration:none;
				}
				.qa-main p {
					line-height:150%;
				}
				.requestmainwrap {
					max-width:650px;
				}
				.requestdatawrap {
					display:block;
					max-width:650px;
					background:#DEF; /*#F5F7FF;*/
					border:1px solid #BBF; /*#EEE;*/
					padding:20px 20px 15px 20px;
				}
				.requestdatawrap h2 {
					margin:0 0 10px 0;
					padding:0;
				}
				.requestdatawrap img {
					max-width:600px;
				}
				.requesttitle {
					color:#33F;
				}
				.requestloco {
					margin-bottom:10px;
				}
				.requestendtimebox {
					float:right;
					text-align:right;
					display:inline-block;
					background:#F5F7FF;
					border:1px solid #EEE;
					padding:10px 15px;
					margin:10px 0;
				}
				.biddatawrap {
					max-width:550px;
					margin:30px 0 50px 0;
					max-width:650px;
					background:#F8FFCC; /* #CCEDFF #FCFCFC */
					border:1px solid #FBAAAA; /* #CCF */
					padding:20px 20px 15px 20px;
				}
				.biddatawrap h2 {
					margin-top:0px;
				}
				.biddatatable {
					display:table;
				}
				.biddatatable div {
					display:table-row;
				}
				.biddatatable div span {
					display:table-cell;
					padding-bottom:15px;
				}
				.biddatatable div span:nth-child(1) {
					min-width:100px;
				}
				.biddatatable div span:nth-child(2) {
					width:400px;
				}
				.biddatatable #bidprice {
					width:70px;
					text-align:right;
				}
				.biddatatable #bidcomment {
					vertical-align:top;
					width:95%;
					max-width:400px;
					padding:5px;
				}
				.biddatatable #bidprice,
				.biddatatable #bidcomment {
					background:#FFF; /* #F5F5FF #F0F0FF #F2F5F7 */
					border:1px solid #CCF;
				}
				.biddatatable #bidprice:focus,
				.biddatatable #bidcomment:focus {
					box-shadow:none;
					border:1px solid #99F;
				}

				.smsg {
					color:#00F;
					display:inline;
					margin:5px;
				}
				.smsg-red {
					color:#F00;
					margin-top:4px;
				}

				/* smartphones */
				@media only screen and (max-width:480px)
				{
					.biddatatable div span:nth-child(2) {
						min-width: auto;
					}
					.biddatatable #bidcomment {
						width: auto;
					}
					.biddatatable div span:nth-child(2) {
						width:auto;
					}
				}

			</style>';

			// jquery
			$qa_content['custom'] .= "
	<script>
		$(document).ready(function()
		{
			$('.senddatabtn').click( function(e)
			{
				e.preventDefault();

				// show loading indicator
				$('.qa-waiting').show();

				// check for complete data
				if($('#bidprice').val()=='')
				{
					alert('".qa_lang('booker_lang/specify_price')."');
					$('#bidprice').focus();
					$('.qa-waiting').hide();
					return;
				}

				var gdata = {
					requestid: '".$requestid."',
					bidprice: $('#bidprice').val(),
					bidcomment: $('#bidcomment').val(),
					userid: ".$userid.",
				};
				console.log(gdata);

				var senddata = JSON.stringify(gdata);
				$.ajax({
					type: 'POST',
					url: '".qa_self_html()."',
					data: { biddata: senddata },
					dataType: 'json',
					cache: false,
					success: function(data)
					{
						$('.qa-waiting').hide();
						console.log('server returned: '+data+' | message: '+data['message']);
						if(data['message']=='posted')
						{
							$('#bidprice').val(data['bidprice']);
							$('#bidcomment').val(data['bidcomment']);
							$('<p class=\"smsg\">✓ ".qa_lang('booker_lang/postsuccess')."</p>').insertAfter('.senddatabtn');
							$('.smsg').fadeOut(2000, function() {
								$(this).remove();
								window.scrollTo(0, 0);
								window.location.reload(false);
								// window.location.href = './contract?userid=".$userid."';
							});
						}
						else if(data['message']=='error')
						{
							$('<p class=\"smsg-red\">✓ ".qa_lang('booker_lang/error')."</p>').insertAfter('.senddatabtn');
							$('.smsg-red').fadeOut(2000, function() {
								$(this).remove();
								// window.scrollTo(0, 0);
								// window.location.href = './contract?userid=".$userid."';
							});
						}
					},
					error: function(xhr, status, error) {
						$('.qa-waiting').hide();
						console.log('problem with server:');
						console.log(xhr.responseText);
						console.log(error);
					}
				}); // end ajax
			}); // end senddatabtn

		}); // end jquery ready

	</script>
			";


			return $qa_content;

		} // end process_request

	}; // end class


/*
	Omit PHP closing tag to help avoid accidental output
*/

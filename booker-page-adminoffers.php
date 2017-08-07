<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_adminoffers
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
					'title' => 'booker Page admin Offers', // title of page
					'request' => 'adminoffers', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='adminoffers')
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
				qa_set_template('booker adminoffers');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}

			$userid = qa_get_logged_in_userid();

			// only members can access
			if(empty($userid))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminoffers');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}

			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker adminoffers');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}

			/* start default page */
			$qa_content = qa_content_prepare();
			qa_set_template('booker adminoffers');
			$qa_content['title'] = qa_lang('booker_lang/adminoffers_title');

			// super admin can have view of others for profile if adding a userid=x to the URL
			if(qa_get_logged_in_level()>QA_USER_LEVEL_ADMIN)
			{
				$userid = qa_get('userid');
				if(empty($userid))
				{
					$userid = qa_get_logged_in_userid();
				}
			}

			// init
			$qa_content['custom'] = '';

			// get existing offers of all user
			$existingoffers = booker_get_all_offers_admin();

			$eventsrated = array();
			$conoffers = '';

			if(count($existingoffers)>0)
			{
				$conoffers .= '
				<div class="offerstablewrap">
					<table id="offerstable">
					<tr>
						<th>'.qa_lang('booker_lang/nr').'</th>
						<th>'.qa_lang('booker_lang/offertitle').'</th>
						<th>'.qa_lang('booker_lang/price').'</th>
						<th>'.qa_lang('booker_lang/contractor').' / '.qa_lang('booker_lang/end').'</th>
						<th>'.qa_lang('booker_lang/execution').'</th>
					</tr>
				';

				foreach($existingoffers as $offer)
				{
					$flags = $offer['flags'];
					$flagsout = '';
					if($flags&MB_SERVICELOCAL)
					{
						$flagsout .= '- '.qa_lang('booker_lang/servicelocal').'<br />';
					}
					if($flags&MB_SERVICEONLINE)
					{
						$flagsout .= '- '.qa_lang('booker_lang/serviceonline').'<br />';
					}
					if($flags&MB_SERVICEATCUSTOMER)
					{
						$flagsout .= '- '.qa_lang('booker_lang/serviceatcustomer').'<br />';
					}


					$status = $offer['status'];
					$statustitle = '';
					$statusshow = '';
					$offertitlecolor = ' style="color:#777;" ';

					$optionlink_edit = '
					<a class="optionlink" href="'.qa_path('offercreate').'?offerid='.$offer['offerid'].'&userid='.$offer['userid'].'" title="'.qa_lang('booker_lang/edit').'">
						<i class="fa fa-pencil fa-lg"></i>
					</a>
					';
					$optionlink_deactivate = '
					<a class="optionlink deactivatelink" title="'.qa_lang('booker_lang/deactivate').'">
						<i class="fa fa-power-off fa-lg"></i>
					</a>
					';
					$optionlink_delete = '
								<a class="optionlink deletelink" data-offerid='.$offer['offerid'].'" title="'.qa_lang('booker_lang/delete').'">
									<i class="fa fa-remove fa-lg"></i>
								</a>
					';
					$optionlink_approve = '
								<a class="optionlink approvelink" data-offerid='.$offer['offerid'].'" title="'.qa_lang('booker_lang/approve').'">
									<i class="fa fa-check fa-lg"></i>
								</a>
					';
					$optionlink_disapprove = '';

					// offer created but not checked yet
					if($status==0)
					{
						$statustitle = ' title="'.qa_lang('booker_lang/ischecked').'" ';
						$statusshow = '<i class="fa fa-check-square notyetapproved tooltip"></i>';
						$offertitlecolor = ' style="color:#555;" ';
						$optionlink_deactivate = '';
						$optionlink_disapprove = '
									<a class="optionlink disapprovelink" data-offerid='.$offer['offerid'].'" title="'.qa_lang('booker_lang/disapprove').'">
										<i class="fa fa-ban fa-lg"></i>
									</a>
						';
					}
					else
					{
						if($status&MB_OFFER_APPROVED)
						{
							$optionlink_approve = '';
							if($status&MB_OFFER_ACTIVE)
							{
								$statustitle = ' title="'.qa_lang('booker_lang/approved').'" ';
								$statusshow = '<i class="fa fa-check-square tooltip"></i>';
								$offertitlecolor = ' style="color:#0A0;" ';
							}
							else
							{
								// deactivated
								$statustitle = ' title="'.qa_lang('booker_lang/deactivated').'" ';
								$statusshow = '<i class="fa fa-power-off"></i>';
								$offertitlecolor = ' style="color:#CCC;" ';
								// make it activatable
								$optionlink_deactivate = '
								<a class="optionlink activatelink" title="'.qa_lang('booker_lang/activate').'">
									<i class="fa fa-power-off fa-lg poweron"></i>
								</a>
								';
							}
						}
						else
						{
							// disapproved
							$statustitle = ' title="'.qa_lang('booker_lang/disapproved').'" ';
							$statusshow = '<i class="fa fa-ban"></i>';
							$offertitlecolor = ' style="color:#A33;" ';
						}
						if($status&MB_OFFER_DELETED)
						{
							$statustitle = ' title="'.qa_lang('booker_lang/deleted').'" ';
							// $statusshow = '<i class="fa fa-minus-circle"></i>';
							$statusshow = '<i class="fa fa-trash-o"></i>';
							$offertitlecolor = ' style="color:#AAA;" ';
							$optionlink_delete = '';
							$optionlink_approve = '';
							$optionlink_edit = '';
						}
					}


					$conoffers .= '
					<tr data-offerid='.$offer['offerid'].'>
						<td>
							<a class="defaultbutton tablebtn" target="_blank" href="'.qa_path('booking').'?offerid='.$offer['offerid'].'">'.$offer['offerid'].'</a>
						</td>
						<td>
							<p class="offerstatus tooltip" '.$statustitle.'>
								'.$statusshow.'
								<a href="'.qa_path('booking').'?offerid='.$offer['offerid'].'" class="offertitle" '.$offertitlecolor.'>
									'.$offer['title'].'
								</a>
							</p>
							<p class="offerpreviewtext">
								'.helper_shorten_text($offer['description'],100).'
							</p>
							<div class="offeroptions">
								'
								.$optionlink_approve.
								'
								'
								.$optionlink_disapprove.
								'
								'
								.$optionlink_edit.
								'
								'
								.$optionlink_delete.
								'
							</div>
						</td>
						<td>'.roundandcomma($offer['price']).' '.qa_opt('booker_currency').'</td>
						<td>
							<a class="offerusername" target="_blank" href="'.qa_path('userprofile').'?userid='.$offer['userid'].'">
								'.booker_get_realname($offer['userid']).'
							</a>
							<span class="offerend">
								'.(empty($offer['end']) ? '-' : helper_get_date_localized($offer['end'])).'
							</span>
						</td>
						<td>'.$flagsout.'</td>
					</tr>
					';
					// <td>'.helper_get_readable_date_from_time($offer['created']).'</td>
				}
				// <td>'.$offer['status'].'</td>
				// <td>'.$offer['description'].'</td>
				$conoffers .= '
						</table> <!-- offerstable -->
					</div> <!-- offerstablewrap -->
				';

				// remove duplicates
				$eventsrated = array_unique($eventsrated);
			} // end count $existingoffers
			else
			{
				$qa_content['custom'] .= '
				<p class="qa-error">
					'.qa_lang('booker_lang/nooffers').'
				</p>';
			}

			$qa_content['custom'] .= $conoffers;

			$qa_content['custom'] .= '
			<style type="text/css">
				.tablebtn {
					display:inline-block;
					padding: 7px 7px;
					line-height:100%;
					margin: 0;
					background:#EEE;
					border:0;
					color:#333;
					font-size:11px;
				}
				.offertitle {
					color:#33F;
				}
				.offerstatus {
					display:inline-block;
					max-width: 70%;
					line-height:125% !important;
					margin-bottom:0;
				}
				.offerstatus a {
					text-decoration:none !important;
				}
				.offerstatus i {
					cursor:default;
				}
				.offerpreviewtext {
					color:#AAA;
					font-size:11px !important;
					margin:0;
				}
				.offerpreviewtext {
					color:#AAA;
					font-size:11px !important;
					margin:0;
				}
				.offerdata {
					line-height:120%;
					margin:0;
				}
				.offerdata a {
					text-decoration:none !important;
				}
				.offerusername {
					display:block;
					font-size:11px;
					color:#35C;
					text-decoration:none !important;
				}
				.offerend {
					color:#333;
					font-size:11px !important;
					margin-top:5px;
				}
				.nobidsyet {
					font-size:11px;
					color:#66F;
				}
				.offerlistimage {
					display:inline-block;
					vertical-align:top;
					float:left;
					margin-right:10px;
					width:100px;
					height:100px;
					background-repeat: no-repeat;
					/*background-position: top center;
					background-size: 100% 100%; */
					background-position:50% 50%;
					background-size:cover;
					border-radius:5px;
				}

				.offeroptions .fa-check:hover {
					color:#3C3;
				}
				.offeroptions .fa-remove {
					color:#999;
				}
				.offeroptions .fa-remove:hover {
					color:#F33;
				}
				.offeroptions .fa-ban {
					color:#999;
				}
				.offerstatus .fa-check-square,
				.offeroptions .fa-check-square {
					color:#3A3;
				}
				.offerstatus .fa-ban {
					color:#D33;
				}
				.offeroptions .fa-ban:hover {
					color:#F33;
				}
				.offeroptions .fa-low-vision {
					color:#777;
				}
				.offerstatus .fa-minus-circle,
				.offeroptions .fa-minus-square,
				.offeroptions .fa-minus-circle {
					color:#C77;
				}
				.fa-power-off,
				.offeroptions .fa-power-off {
					color:#CCC;
				}
				.offeroptions .fa-power-off:hover {
					color:#333;
				}
				.poweron {
					color:#5C5 !important;
				}
				.offeroptions .fa-pencil:hover {
					color:#333;
				}
				.notyetapproved {
					color:#CCC !important;
				}
				.offeroptions {
					margin-bottom:10px;
				}
				.optionlink .fa-lg {
					cursor:pointer;
				}
				.optionlink {
					color:#999;
					margin:2px 5px 2px 3px;
				}
				.optionlink:hover {
					text-decoration:none;
					color:#44C;
				}
				.editlink {
					font-size:17px;
				}
				.deactivatelink {
					color:#844;
				}
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:95%;
					font-size:13px;
					margin-bottom:200px;
				}
				.qa-main p {
					line-height:150%;
					font-size:13px;
				}
				.offerbox {
					position: relative;
					display: block;
					width: 100%;
					max-width: 470px;
					margin: 20px 0px 40px;
					background: #F0F0F0;
					border: 1px solid #DDD;
					padding: 5px 15px 45px 15px;
				}
				.alreadyrated {
					color:#00D;
				}
				.offertext {
					display:block;
					width:100%;
					max-width:450px;
					height:70px;
					border:1px solid #DDD;
					padding:5px;
				}
				.submitoffer {
					float:right;
					margin:0;
					padding: 5px 15px;
				}
				.profileimage {
					display: inline-block;
					width:95px;
					vertical-align: top;
					padding: 20px 20px 10px 20px;
					margin: 0px 0px 0px;
					border: 1px solid #DDE;
					background: none repeat scroll 0% 0% #FFF;
					text-align: center;
				}
				.profileimage img {
					max-width:170px;
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


				.offerstablewrap {
					display:block;
					width:92%;
					text-align:right;
					margin-top:30px;
				}
				#offerstable {
					display:table;
					width:100%;
					max-width:800px;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
				}
				#offerstable th {
					font-weight:normal;
					background:#FFC;
					border:1px solid #CCC;
				}
				#offerstable td {
					background:#FFF;
					border:1px solid #CCC;
				}
				#offerstable td, #offerstable th {
					padding:3px 5px;
					border:1px solid #DDD;
					font-weight:normal;
					text-align:left;
					vertical-align:top;
				}
				#offerstable td:nth-child(1) {
					width:2%;
				}
				#offerstable td:nth-child(2) {
					width:60%;
				}
				#offerstable td:nth-child(3) {
					width:13%;
					text-align:right;
				}
				#offerstable td:nth-child(4) {
					width:12%;
				}
				#offerstable td:nth-child(5) {
					width:15%;
					font-size:11px;
				}
			</style>
			';


			// jquery
			$qa_content['custom'] .= "
	<script>
		$(document).ready(function()
		{
			$('.deletelink').click( function(e)
			{
				e.preventDefault();
				var offertitle = $(this).parent().parent().parent().find('.offertitle').text().trim();
				var clicked = $(this);
				if(confirm('".qa_lang('booker_lang/removeoffer_confirm')." \\n\"'+offertitle+'\"'))
				{
					var offerid = $(this).parent().parent().parent().data('offerid');
					var offerdata = {
						offerid: offerid,
						userid: ".$userid."
					};
					console.log(offerdata);
					$.ajax({
						type: 'POST',
						url: '".qa_path('ajaxhandler')."',
						data: { offer_delete: JSON.stringify(offerdata) },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: ');
							console.log(data);
							if(data['status']=='success')
							{
								$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/offer_deleted')."</p>').insertAfter(clicked);
								$('.qa-success').fadeOut(1500, function() {
									$(this).remove();
									clicked.parent().parent().parent().remove();
									window.scrollTo(0, 0);
									// reload page
									window.location.href = '".qa_self_html()."';
								});
							}
							else
							{
								console.log('Problem with server');
							}
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				}
				else
				{
					// do nothing
				}
			}); // end deletelink

			$('.approvelink').click( function(e)
			{
				e.preventDefault();
				var offertitle = $(this).parent().parent().parent().find('.offertitle').text().trim();
				var clicked = $(this);
				if(confirm('".ucfirst(qa_lang('booker_lang/approve'))."? \\n\"'+offertitle+'\"'))
				{
					var offerid = $(this).parent().parent().parent().data('offerid');
					var offerdata = {
						offerid: offerid,
						userid: ".$userid."
					};
					console.log(offerdata);
					$.ajax({
						type: 'POST',
						url: '".qa_path('ajaxhandler')."',
						data: { offer_approve: JSON.stringify(offerdata) },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: ');
							console.log(data);
							if(data['status']=='success')
							{
								$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/offer_approved')."</p>').insertAfter(clicked);
								$('.qa-success').fadeOut(1500, function() {
									$(this).remove();
									// window.scrollTo(0, 0);
								});
							}
							else
							{
								console.log('Problem with server');
							}
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				}
			}); // end approvelink


			$('.disapprovelink').click( function(e)
			{
				e.preventDefault();
				var offertitle = $(this).parent().parent().parent().find('.offertitle').text().trim();
				var clicked = $(this);
				if(confirm('".ucfirst(qa_lang('booker_lang/disapprove'))."? \\n\"'+offertitle+'\"'))
				{
					var offerid = $(this).parent().parent().parent().data('offerid');
					var offerdata = {
						offerid: offerid,
						userid: ".$userid."
					};
					console.log(offerdata);
					$.ajax({
						type: 'POST',
						url: '".qa_path('ajaxhandler')."',
						data: { offer_disapprove: JSON.stringify(offerdata) },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: ');
							console.log(data);
							if(data['status']=='success')
							{
								$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/offer_disapproved')."</p>').insertAfter(clicked);
								$('.qa-success').fadeOut(1500, function() {
									$(this).remove();
									// window.scrollTo(0, 0);
									// reload page
									window.location.href = '".qa_self_html()."';
								});
							}
							else
							{
								console.log('Problem with server');
							}
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				}
			}); // end disapprovelink

			$('.deactivatelink').click( function(e)
			{
				e.preventDefault();
				var offertitle = $(this).parent().parent().parent().find('.offertitle').text().trim();

				var clicked = $(this);
				if(confirm('".ucfirst(qa_lang('booker_lang/deactivate'))."? \\n\"'+offertitle+'\"'))
				{
					var offerid = $(this).parent().parent().parent().data('offerid');
					var offerdata = {
						offerid: offerid,
						userid: ".$userid."
					};
					console.log(offerdata);
					$.ajax({
						type: 'POST',
						url: '".qa_path('ajaxhandler')."',
						data: { offer_deactivate: JSON.stringify(offerdata) },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: ');
							console.log(data);
							if(data['status']=='success')
							{
								$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/deactivated')."</p>').insertAfter(clicked);
								$('.qa-success').fadeOut(1500, function() {
									$(this).remove();
									// window.scrollTo(0, 0);
									// reload page
									window.location.href = '".qa_self_html()."';
								});
							}
							else
							{
								console.log('Problem with server');
							}
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				}
			}); // end deactivatelink

			$('.activatelink').click( function(e)
			{
				e.preventDefault();
				var offertitle = $(this).parent().parent().parent().find('.offertitle').text().trim();

				var clicked = $(this);
				if(confirm('".ucfirst(qa_lang('booker_lang/activate'))."? \\n\"'+offertitle+'\"'))
				{
					var offerid = $(this).parent().parent().parent().data('offerid');
					var offerdata = {
						offerid: offerid,
						userid: ".$userid."
					};
					console.log(offerdata);
					$.ajax({
						type: 'POST',
						url: '".qa_path('ajaxhandler')."',
						data: { offer_activate: JSON.stringify(offerdata) },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: ');
							console.log(data);
							if(data['status']=='success')
							{
								$('<p class=\"qa-success\">✓ ".qa_lang('booker_lang/activated')."</p>').insertAfter(clicked);
								$('.qa-success').fadeOut(1500, function() {
									$(this).remove();
									// window.scrollTo(0, 0);
									// reload page
									window.location.href = '".qa_self_html()."';
								});
							}
							else
							{
								console.log('Problem with server');
							}
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				}
			}); // end activatelink

		}); // end jquery ready

	</script>
			";

			return $qa_content;

		} // end process_request

	};

/*
	Omit PHP closing tag to help avoid accidental output
*/

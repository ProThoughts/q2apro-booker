<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_admincontractors
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
					'title' => 'booker Page Admincontractors', // title of page
					'request' => 'admincontractors', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='admincontractors') {
				return true;
			}
			return false;
		}

		function process_request($request)
		{
		
			if(!qa_opt('booker_enabled'))
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker admincontractors');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$userid = qa_get_logged_in_userid();
			
			$level = qa_get_logged_in_level();
			if($level <= QA_USER_LEVEL_ADMIN)
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker admincontractors');
				$qa_content['title'] = '';
				$qa_content['error'] = qa_lang('booker_lang/page_noaccess');
				return $qa_content;
			}
		
			// AJAX: admin approves contractor
			$transferString = qa_post_text('approvecontractor');
			if(isset($transferString)) 
			{
				// $newdata = json_decode($transferString, true);
				$newdata = $transferString;
				$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

				$contractorid = (int)preg_replace("/[^0-9]/","",$newdata); // (int)($newdata);
				
				// "disapprove" in userid string if user should be disapproved
				if(strpos($newdata, 'disapprove') !== false)
				{
					booker_set_userfield($contractorid, 'approved', MB_USER_STATUS_DISAPPROVED);
				}
				else if(strpos($newdata, 'removeapprove') !== false)
				{
					booker_set_userfield($contractorid, 'approved', MB_USER_STATUS_DEFAULT);
				}
				else 
				{
					booker_set_userfield($contractorid, 'approved', MB_USER_STATUS_APPROVED);
				}
				
				// ajax return success
				echo json_encode('contractor '.$contractorid.' approved');
				exit(); 
			} // END AJAX RETURN (approve)

			
			/* start */
			$qa_content = qa_content_prepare();
			qa_set_template('booker admincontractors');
			$qa_content['title'] = qa_lang('booker_lang/admincon_title');
			
			// init
			$qa_content['custom'] = '';
			
			// $needapproval = false;
			$needapproval = !is_null(qa_get('approval'));
			$contracted = !is_null(qa_get('contracted'));
			
			// filter by alphabetic letter
			$letter = qa_get('filter');
			
			$start = qa_get('start');
			if(empty($start))
			{
				$start = 0;
			}
			$pagesize = 50; // contractors to show per page
			
			// dont need to be approved, just potential ones
			// $count = booker_get_contractorcount_priceset($needapproval, $contracted, $letter);
			$count = booker_get_contractorcount(false, $contracted);
			$count_contracted = booker_get_contractorcount(false, true);
			$count_approved = booker_get_contractorcount(true, true);
			$params = null;
			if(!empty($letter))
			{
				$params = array(
					'filter' => $letter
				);
			}
			
			$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, true, $params); // last parameter is prevnext
			
			$contractorlist = '';
			
			// table of all contractors below
			$contractorlist = '';
			
			// $contractorlist .= '<h2 id="contractors" style="margin-top:50px;">Anbieter</h2>';
			$contractorlist .= '<table class="membertable">';
			$contractorlist .= '
			<tr>
				<th>'.qa_lang('booker_lang/name').'</th>
				<th>'.qa_lang('booker_lang/admin_tab_price').'</th>
				<th>'.qa_lang('booker_lang/admin_tab_userdata').'</th>
				<th>'.qa_lang('booker_lang/admin_tab_address').'/'.qa_lang('booker_lang/paymethod').'</th>
				<th>'.qa_lang('booker_lang/admin_tab_spec').'/'.qa_lang('booker_lang/admin_tab_portfolio').'</th>
				<th>'.qa_lang('booker_lang/approved').'</th>
			</tr>
			';
	
			// get all contractors, even no-approval, but price set needed
			$allcontractors = booker_get_all_contractors($start, $pagesize, $needapproval, false, $contracted, 'approved ASC, realname ASC', $letter);
			
			$contractorcount = 0;
			$emailcollect = '';
			
			foreach($allcontractors as $contractor) 
			{
				$contractorcount++;
				/*
				$exMetas = qa_db_read_all_assoc( 
									qa_db_query_sub('SELECT userid,points,qposts,aposts,cposts,upvoteds,aselecteds FROM `^userpoints`
															WHERE userid = #
															;', $ex)
														);
														*/
				$contractorid = $contractor['userid'];
				
				// check if member is contractor or client - identified by stated price (weak check)
				// could also be an INNER JOIN in the mysql query above 
				$contractorprice = $contractor['bookingprice'];
				if((empty($contractorprice)))
				{
					$contractorprice = '---';
				}
				
				$contractorname = $contractor['realname'];
				$contractorhandle = helper_gethandle($contractorid);
				
				$contractor_available = $contractor['available'];
				// $contractor_available = $contractor_available==1 ? '<span style="font-size:12px;">aktiv</span>' : '<span style="font-size:12px;color:#345;">off</span>';
				
				$contractorapproved = $contractor['approved'];
				$contractor_approved = '';
				if($contractorapproved==MB_USER_STATUS_APPROVED)
				{
					$contractor_approved = '
						<i class="fa fa-check-square fa-2x"></i>
						<br />
						<a data-original="'.$contractorid.'" class="removeapprovelink">'.qa_lang('booker_lang/removeapprove').'</a>
					';
				}
				else if($contractorapproved==MB_USER_STATUS_DEFAULT)
				{
					$contractor_approved = '
						<i class="fa fa-square-o fa-2x"></i> 
						<br />
						<a data-original="'.$contractorid.'" class="approvelink">'.qa_lang('booker_lang/doapprove').'</a>
						<a data-original="'.$contractorid.'" class="disapprovelink">'.qa_lang('booker_lang/disapprove').'</a>
					';
				}
				else if($contractorapproved==MB_USER_STATUS_DISAPPROVED)
				{
					$contractor_approved = '
						<i class="fa fa-minus-square fa-2x"></i> 
						<br />
						<a data-original="'.$contractorid.'" class="removeapprovelink">'.qa_lang('booker_lang/removedisapprove').'</a>
					';
				}
				else
				{
					$contractor_approved = 'something wrong, contact us';
				}
				
				$contracttime = '<span class="contracted">'.substr($contractor['contracted'],0,10).'</span>';
				
				$contractorskype = $contractor['skype'];
				if(empty($contractorskype))
				{
					$contractorskype = ''; // '<span style="font-size:10px;color:#C00;">no skype</span>';
				}
				else
				{
					$contractorskype = '<a class="skypelink" href="skype:'.$contractor['skype'].'?chat" title="Skype: '.$contractor['skype'].'">Skype</a> · ';
				}
				
				// $contractorbirthdate = $contractor['birthdate'];
				
				$contractor_email = helper_getemail($contractorid);
				$contractormail = '<a class="contractormail" href="mailto:'.$contractor_email.'">Mail</a> · ';
				if($contractorapproved==MB_USER_STATUS_APPROVED)
				{
					$emailcollect .= $contractor_email.', ';
				}
				
				$conchecked = '';
				if($contractorapproved==MB_USER_STATUS_APPROVED && $contractor_available==1)
				{
					$conchecked = '✓';
				}
				
				$contractorabsent = '';
				$contractorabsenttimes = $contractor['absent'];
				if(!empty($contractorabsenttimes))
				{
					$contractorabsent = '-- '.qa_lang('booker_lang/admin_tab_absent').': '.$contractorabsenttimes;
				}
				
				$contractor_available_css = $contractor_available==1 ? '' : 'contractorunavailable';
				
				$offercount = booker_get_offercount($contractorid);
				$offercount_out = '';
				if($offercount>0)
				{
					$offercount_out = '
						<a href="/booking?contractorid='.$contractorid.'" class="offercount">Offers: '.$offercount.'</span>
					';
				}
				$contractorlist .= '
				<tr>
					<td>
						<a title="zur Buchungsseite" href="'.qa_path('booking').'?contractorid='.$contractorid.'">'.$conchecked.' '.$contractorname.'</a> 
						<br />
						<span class="smalllink">Userid: '.$contractorid.'</span> 
						<a class="smalllink" href="'.qa_path('mbmessages').'?to='.$contractorid.'" title="'.qa_lang('booker_lang/admin_sendmsg').'">( ✉ )</a> 
					</td>
					<td>
						<a href="/booking?contractorid='.$contractorid.'" class="contractorprice">'.$contractor['bookingprice'].' '.qa_opt('booker_currency').'</a>
						'.$offercount_out.'						
					</td>
					<td>
						<div class="contractorlinksml">
							<a href="'.qa_path('userprofile').'?userid='.$contractorid.'">'.qa_lang('booker_lang/profile').'</a> ·
							<a href="'.qa_path('user').'/'.$contractorhandle.'">'.qa_lang('booker_lang/forum_profile').'</a> · 
							<a href="'.qa_path('contractorbalance').'?userid='.$contractorid.'">'.qa_lang('booker_lang/accountbalance').'</a> ·
							<a href="'.qa_path('contractorschedule').'?userid='.$contractorid.'">'.qa_lang('booker_lang/appts').'</a> ·
							<a href="'.qa_path('mbmessages').'?to='.$contractorid.'">'.qa_lang('booker_lang/message').'</a> · 
							'.$contractorskype.' 
							'.$contractormail.'
							<a href="'.qa_path('contractorbalance').'?userid='.$contractorid.'">'.qa_lang('booker_lang/fee').'</a>
						</div>
						<div class="contractorabsent">
						'.$contractorabsent.'
						</div>
					</td>
					<td style="font-size:11px;">
						<span class="postaladdress">'.$contractor['address'].'</span>
						<br />
						'.$contractor['payment'].'
					</td>
					<td>
						<span class="contractorservice">'.$contractor['service'].'</span>
						<span class="contractorportfolio">'.helper_shorten_text($contractor['portfolio'], 115).'</span> 
						<br />
					</td>
					<td>
						'.$contractor_approved.'
						<br />
						'.$contracttime.'
					</td>
					</tr>';
			} // end foreach
			$contractorlist .= '</table>';
			
			// $countreal = booker_get_contractorcount();
			$qa_content['title'] = qa_lang('booker_lang/admincon_title').' ('.($start+$contractorcount).'/'.$count.')';
			
			// init
			$qa_content['custom'] = '';
			
			$qa_content['custom'] = '
				<p style="font-size:12px;">
					<a href="/admincontractors">Show all</a> 
					| 
					<a href="/admincontractors?contracted=1">Contracted: '.$count_contracted.' / Approved: '.$count_approved.'</a> 
				</p>
			';
			
			$alphabeticchoice = '
				<div class="alphabeticchoice">
			';
			foreach(range('a', 'z') as $char)
			{
				$alphabeticchoice .= '<a href="'.qa_path('admincontractors').'?filter='.$char.'" class="azbutton">'.$char.'</a>';
			}
			$alphabeticchoice .= '				
				</div> <!-- alphabeticchoice -->
			';
			$qa_content['custom'] .= $alphabeticchoice;
			
			
			$qa_content['custom'] .= $contractorlist;
			
			$qa_content['custom'] .= "
			<script type=\"text/javascript\">
			$(document).ready(function(){
			
				$('th').click(function(){
					var table = $(this).parents('table').eq(0)
					var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
					this.asc = !this.asc
					if (!this.asc){rows = rows.reverse()}
					for (var i = 0; i < rows.length; i++){table.append(rows[i])}
				})
				function comparer(index) {
					return function(a, b) {
						var valA = getCellValue(a, index), valB = getCellValue(b, index)
						return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.localeCompare(valB)
					}
				}
				function getCellValue(row, index){ return $(row).children('td').eq(index).text() }

				$('th:first').trigger('click');
	
	
				
				$('.approvelink, .disapprovelink, .removeapprovelink').click( function(e) 
				{
					var clicked = $(this);
					e.preventDefault();
					
					var userid = $(this).data('original');
					if( $(this).attr('class')=='disapprovelink')
					{
						userid += 'disapprove';
					}
					else if( $(this).attr('class')=='removeapprovelink')
					{
						userid += 'removeapprove';
					}
					console.log('sending: '+userid);
					
					// var senddata = JSON.stringify(userid);
					$.ajax({
						type: 'POST',
						url: '".qa_path('admincontractors')."',
						data: { approvecontractor:userid },
						dataType: 'json',
						cache: false,
						success: function(data) {
							console.log('server returned: '+data);
							clicked.parent().text(' ');
						},
						error: function(xhr, status, error) {
							console.log('problem with server:');
							console.log(xhr.responseText);
							console.log(error);
						}
					}); // end ajax
				});

			}); // end ready
			</script>
			";
			
			$qa_content['custom'] .= '
			<style type="text/css">
				.qa-sidepanel {
					display:none;
				}
				.qa-main {
					width:95%;
				}
				.qa-main p {
					line-height:150%;
				}
				.bookingtablewrap {
					width:92%;
					display:block;
					text-align:right;
				}
				#bookingtable, .membertable, #paymentstable {
					display:inline-block;
					border-collapse:collapse;
					margin-top:5px;
					line-height:140%;
				}
				#bookingtable th, .membertable th, #paymentstable th {
					font-weight:normal;
					background:#FFC;
					text-align:left;
				}
				#bookingtable td, #bookingtable th, 
				.membertable td, .membertable th, 
				#paymentstable td, #paymentstable th
				{
					border:1px solid #CCC;
					padding:3px 5px;
					border:1px solid #DDD;
					font-weight:normal;
					line-height:140%;
					vertical-align:top;
				}
				#bookingtable td {
					text-align:right;
				}
				.smalllink {
					font-size:11px;
				}
				.membertable td:nth-child(1) {
					width:11%;
					font-size:12px;
				}
				.membertable td:nth-child(2) {
					width:5%;
					text-align:right;
				}
				.membertable td:nth-child(4),
				.membertable td:nth-child(5), 
				.membertable td:nth-child(6) {
					font-size:12px;
				}
				.ev_unitprice {
					font-size:10px;
				}
				.offercount {
					font-size:12px;
				}
				.contractor_available {
					background:#FFC;
				}
				.fa-check-square {
					color:#3A3;
				}
				.fa-minus-square {
					color:#A33;
				}
				.forumname {
					font-size:10px;
					color:#999 !important;
				}
				.skypelink, .contractormail, .postaladdress {
					color:#898;
				}
				.contractorlinksml {
					font-size:10px;
					color:#555;
					cursor:pointer;
				}
				.contracted {
					font-size:11px;
					color:#559;
					background:#F5F5F5;
				}
				.approvelink, .disapprovelink, .removeapprovelink {
					display:inline-block;
					padding:0;
					font-size:11px !important;
					color:#55C;
					padding-bottom:0;
					cursor:pointer;
				}
				.approvelink {
					border-bottom:1px dotted #AAF;
				}
				.disapprovelink {
					color:#F55;
					border-bottom:1px dotted #FAA;
				}
				.approvelink:hover, .disapprovelink:hover, .removeapprovelink:hover {
					text-decoration:none;
					color:#123;
				}
				.membertable tr:hover {
					background:#FFC !important;
				}
				.contractorunavailable {
					color:#AAA;
				}
				.contractorprice {
					font-size:12px;
				}
				.contractorportfolio {
					display:block;
					font-size:11px;
					color:#888;
				}
				.contractorabsent {
					display:block;
					font-size:10px;
					color:#888;
				}
				.alphabeticchoice {
					display:block;
					margin:0 0 10px 0;
				}
				.azbutton {
					display:inline-block;
					padding:5px 10px;
					background:#EEE;
					border:1px solid #CCC;
					margin: 0 5px 10px 0;
					color:#555;
					text-transform:uppercase;
					font-size:11px;
				}
				.azbutton:hover {
					background:#DDF;
					color:#223;
					text-decoration:none;
				}
			</style>';			
			
			$qa_content['custom'] .= '
				<p style="margin-top:50px;font-weight:bold;">'.qa_lang('booker_lang/admin_maillist').':</p>
				<p>'.$emailcollect.'</p>';
		
			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
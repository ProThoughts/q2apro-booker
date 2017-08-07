<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_offerlist
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
					'title' => 'booker Page offerlist',
					'request' => 'offerlist',
					'nav' => 'M',
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if($request=='offerlist') 
			{
				return true;
			}
			return false;
		}

		function process_request($request)
		{

			/* start content */
			$qa_content = qa_content_prepare();
			qa_set_template('booker offerlist');

			$userid = qa_get_logged_in_userid();
			$isadmin = (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN);
			
			// search parameter
			$searchstring = qa_get('s');
			if(empty($searchstring))
			{
				$searchstring = '';
			}
			// page title
			$qa_content['title'] = qa_lang('booker_lang/all_offers');
			
			// init
			$qa_content['custom'] = '';

			$start = qa_get('start');
			$pagesize = 20; // offers to show per page
			if(true || isset($start))
			{
				$offercount = booker_get_offercount_total();
				$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $offercount, true);
			}
			else
			{
				$start = 0;
			}
			
			$output = '';

			$output .= '
					<div class="q2apro_usersearch_box">
					
						<span id="q2apro_usersearchlabel">'.qa_lang('booker_lang/offli_search_label').':</span> 
						
						<input id="q2apro_usersearch" name="q2apro_usersearch" type="text" placeholder="'.qa_lang('booker_lang/offli_search_placeholder').'" value="'.$searchstring.'" autofocus /> 
						<div class="q2apro_us_progress"><div>Loading…</div></div>
						
						<div class="locationfilterbox">
							<span id="q2apro_locationlabel">'.qa_lang('booker_lang/searchlocation_label').':</span> 
							<select id="locations">
								<option>Klaipėda</option>
								<option>Kaunas</option>
								<option>Vilnius</option>
							</select>
						</div>
						
					</div> <!-- q2apro_usersearch_box -->
			';
			
			$output .= '
			<div class="" style="float:right;">
				<a href="'.qa_path('offercreate').'" class="defaultbutton senddatabtn">
					'.qa_lang('booker_lang/ownoffer_create_btn').'
				</a>
			</div>
			';
			
			$output .= '
					<div id="ajaxsearch_results_wrap">
						<h2>
							'.qa_lang('booker_lang/search_results').': 
						</h2>
						<div id="q2apro_ajaxsearch_results"></div>
					</div>					
			';
			
			// get all offers
			$alloffers = booker_get_all_offers(0, 100, 'title');

			$metaOutput = '';
			$metacount = count($alloffers);
			
			// contractors
			$output .= '<div class="conlisting">';
			
			$output .= '
				<h2>
					'.qa_lang('booker_lang/offers').': 
				</h2> 
			';

			$contractors = array();
			
			$clioffers = '';
			
			if(count($alloffers)>0) 
			{
				$clioffers .= booker_offers_to_table($alloffers, $this->urltoroot);
			} // end count $alloffers
			else 
			{
				$output .= '
				<p class="qa-error">
					'.qa_lang('booker_lang/nooffers').'
				</p>';
			}

			$output .= $clioffers;
			
			$output .= '
			<style type="text/css">
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
				.offerprice a {
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
					display:none;
				}
				#offerstable tr:nth-child(even) {
					background:#F9F9FA;
				}
				/*
				#offerstable tr:hover {
					background:#F5F5F5 !important;
				}*/
				#offerstable td {
					padding:10px 10px;
					border-bottom:1px solid #EEE;
					font-weight:normal;
					text-align:left;
					vertical-align:top;
				}
				#offerstable td:nth-child(1) {
					width:80%;
				}
				#offerstable td:nth-child(2) {
					width:20%;
					text-align:right;
				}
			</style>';
			

			$output .= '
			</div> <!-- conlisting -->
			
			<div style="clear:both;"></div>				
			';
			

			// JQUERY
			$output .= "
				
				<script type=\"text/javascript\" src=\"".$this->urltoroot."css-js/jquery.quicksearch.min.js\"></script>
				
				<script type=\"text/javascript\">
					$(document).ready(function()
					{
						// for videos opening in overlay
						// $('.venobox_custom').venobox();
						
						/*
						$('.venobox_custom').venobox({
							framewidth: '400px',        // default: ''
							frameheight: '300px',       // default: ''
							border: '10px',             // default: '0'
							bgcolor: '#5dff5e',         // default: '#fff'
							titleattr: 'data-title',    // default: 'title'
							numeratio: true,            // default: false
							infinigall: true            // default: false
						});
						*/
						
						// if url s=searchterm
						var urlQueryS = getURLParameter('s');
						if( ( typeof(urlQueryS) !== 'undefined') && (urlQueryS != null) && (urlQueryS!='null') && (urlQueryS!='') ) 
						{
							// multiple words in URL are divided by + sign, so replace with space
							urlQueryS = urlQueryS.replace(/\+/g, ' ');
							// set search text
							$('#q2apro_usersearch').val(urlQueryS); 
							// trigger search
							$('#q2apro_usersearch').trigger('keyup');
						}
						else 
						{
							urlQueryS = '';
						}
	

						// $('.contractorservice .tooltip').tipsy( { gravity:'s', html:true } )
						
						// hide search elements on start
						$('#ajaxsearch_results_wrap, .locationfilterbox, .q2apro_us_progress').hide();
						
						var servicesearch = '';
						var servicesearch_former = '';
						$('#q2apro_usersearch').keyup( function()
						{
							servicesearch = $(this).val().trim();
							if(servicesearch == servicesearch_former)
							{
								console.log('no changes');
								return;
							}
							servicesearch_former = servicesearch;
							// console.log(servicesearch);
							// console.log(servicesearch.length);
							if(servicesearch!='' && servicesearch.length>1)
							{
								$('.conlisting, .ratings_wrap').hide();
								$('.q2apro_us_progress').show();
								// send searchterm after little delay to save server load
								window.clearTimeout(window.searchTimer);
								window.searchTimer = window.setTimeout(function() { booker_ajaxsearch(servicesearch); }, 300);
							}
							else
							{
								$('.conlisting, .ratings_wrap').show();
								$('#ajaxsearch_results_wrap, .locationfilterbox').hide();
							}
						});
						
						$('#locations').on('change keyup', function() 
						{
							var locselected = $(this).find('option:selected').text();
							if(locselected == '".qa_lang('booker_lang/all_locations')."')
							{
								// show all
								$('#q2apro_ajaxsearch_results #offerstable tr').show();
							}
							else
							{
								$('#q2apro_ajaxsearch_results #offerstable tr').each( function()
								{
									// if(locselected != $(this).find('.contractorlocation').text())
									if(locselected == '".qa_lang('booker_lang/serviceonline')."')
									{
										if($(this).find('.contractorlocation .locationdata').data('online')=='1')
										{
											$(this).show();
										}
										else
										{
											$(this).hide();
										}
									}
									else if(locselected != $(this).find('.contractorlocation .locationdata').data('locationcity'))
									{
										$(this).hide();
									}
									else
									{
										$(this).show();
									}
								});
							}
						});
						
						// quicksearch, https://github.com/riklomas/quicksearch
						var qs = $('#q2apro_usersearch').quicksearch('#q2apro_ajaxsearch_results', {
							// noResults: '#noresults',
							// stripeRows: ['odd', 'even'],
							// loader: '.q2apro_us_progress',
							// delay: 250,
							onBefore: function() { 
								// remove former highlighting
								$('#q2apro_ajaxsearch_results').unhighlight();
							},
							onAfter: function() {
								if($('#q2apro_usersearch').val()!='' && $('#q2apro_usersearch').val().length>1)
								{
									$('#q2apro_ajaxsearch_results').highlight( $('#q2apro_usersearch').val() );
								}
							},
						});
						
						// if we have a value by url trigger search
						if($('#q2apro_usersearch').val()!='')
						{
							$('#q2apro_usersearch').trigger('keyup');
						}
						
						function booker_ajaxsearch(servicesearch_ajax) 
						{
							console.log('searching');
							$('.q2apro_us_progress').show();
							$.ajax({
								 type: 'POST',
								 url: '".qa_path('ajaxoffersearch')."',
								 data: { ajax: servicesearch_ajax },
								 error: function()
								 { 
									console.log('server: ajax error');
									$('#q2apro_ajaxsearch_results').html('bad bad server...');
									$('.q2apro_us_progress').hide();
								 },
								 success: function(htmldata)
								 {
									$('.locationfilterbox, .advantages, .ratings_wrap').show();
									if(htmldata=='')
									{
										htmldata = '<p class=\"qa-error\">".qa_lang('booker_lang/nothing_found')."</p>';
										$('.locationfilterbox, .advantages, .ratings_wrap').hide();
									}
									$('.q2apro_us_progress').hide();
									$('#q2apro_ajaxsearch_results').html( htmldata );
									
									// update quicksearch
									qs.cache();
									
									console.log('showing results');
									$('#q2apro_ajaxsearch_results').show();
									$('#ajaxsearch_results_wrap').show();
									
									// get all locations from visible contractor boxes
									var locs = [];
									$('#q2apro_ajaxsearch_results .contractorlocation .locationdata').each( function()
									{
										if( $(this).data('locationcity').length>0 )
										{
											// locs.push( $(this).text() );
											locs.push( $(this).data('locationcity') );
										}
										if($(this).data('online')=='1')
										{
											locs.push( '".qa_lang('booker_lang/serviceonline')."' );
											console.log(locs);
										}
									});
									locs = unique(locs);
									locs.sort();
									
									// build select for location, clear beforehand
									$('#locations').find('option').remove();
									$('#locations').append('<option>".qa_lang('booker_lang/all_locations')."</option>');
									for(var i=0;i<locs.length;i++)
									{
										$('#locations').append('<option>'+locs[i]+'</option>');
									}
									
									// track search - *** DISABLED
									// sendsearchterm();
								 }
							});
						} // end q2apro_ajaxsearch

						function unique(array) {
							return $.grep(array, function(el, index) {
								return index == $.inArray(el, array);
							});
						}
						
						function sendsearchterm()
						{
							$.ajax({
								type: 'POST',
								url: '".qa_path('searchtrack')."',
								data: { search:servicesearch },
								success: function(e) {
									// servicesearch_former = servicesearch;
									// console.log('received: '+e);
								},
								error:function(e) {
									// servicesearch_former = servicesearch;
								}
							});
							
							/*
							// get searchterm value
							var servicesearch = $.trim($('#q2apro_usersearch').val());
							
							// console.log('sending: '+servicesearch);
							
							// send the data if exists and new and no URL search parameter given
							if(servicesearch!=servicesearch_former && urlQueryS=='')
							{
								$.ajax({
									type: 'POST',
									url: '".qa_path('searchtrack')."',
									data: { search:servicesearch },
									success: function(e) {
										searchvalFormer = servicesearch;
										// console.log('received: '+e);
									},
									error:function(e) {
										searchvalFormer=servicesearch;
									}
								}); 
							}
							*/
							
						} // END sendsearchterm

						function getURLParameter(name) {
							return decodeURI(
								(RegExp(name + '=' + '(.+?)(&|$)').exec(location.search)||[,null])[1]
							);
						}

						
					}); // END ready
				</script>
			";
			
			// EXTRA CSS
			$qa_content['custom'] .= '
			<style type="text/css">
				.content-wrapper {
					max-width:1000px;
				}
				.qa-main {
					width:100%;
					position:relative;
				}
				.qa-main h1 {
					display:none;
				}
				.qa-main h3 {
					font-weight:normal;
					margin-top:30px;
					font-size:16px;
				}
				.offerswrap {
					display:block;
					margin:20px 0 50px 0;
				}
				.offerbox {
					margin:20px 0;
					list-style: none;
					padding: 0;
				}
				.offerbox li {
					display:inline-block;
				}
				.contractorname {
					display:block;
				}
				.contractorportfolio {
					display:block;
					font-size:12px;
					height:72px;
					/*min-height:72px;*/
					/* hyphens:auto; */
				}
				.fulldescription {
					display:none !important;
				}
				.advantages {
					margin:20px 0 50px 0;
				}
				.adviconholer {
					display:inline-block;
					margin-right:20px;
					width:113px;
					text-align:center;
				}
				.advantagestxt {
					display:inline-block;
					width:42%;
					vertical-align:top;	
				}
				.advantages p {
					color:#4C5058;
				}
				.moneybackhead {
					font-size:19px !important;
					color:#39B609 !important;
					font-family:Tahoma,sans-serif;
				}
				.ratings_wrap {
					margin:70px 0;
				}
				
				.clientbutton {
					vertical-align:top;
					margin-top:7px;
				}
				.contractorbutton {
					display:inline-block;
					padding:10px 20px;
					margin-bottom:50px;
					font-size:14px;
					color:#FFF;
					background:#38F;
					border:1px solid #EEE;
					border-radius:0px;
				}
				.contractorsintro {
					font-size:14px;
					line-height:150%;
					margin-bottom:40px;
				}
				.conlisting {
					display:block;
					width:100%;
					/*max-width:800px;*/
					margin-top: 30px;
				}
				.contractorBox {
					position:relative;
					display:inline-block;
					width:auto !important;
					margin:0 20px 50px 0;
					vertical-align:top;
					background:#F5F5F5;
					border:1px solid #DDE;
					padding:15px 15px 5px 15px;
				}
				.contractorname {
					font-size:16px;
					margin-bottom:5px;
				}
				.contractoravatar {
					display:block;
					width:190px;
					height:210px;
					position:relative;
					/*border-top-left-radius:15px;*/
					/*border:1px solid #DDD;*/
					/*box-shadow: inset 0 0 2px #77A;*/
					box-shadow: inset 0 0 1px #AAC;
				}
				.contractormeta {
					display:block;
					position:relative;
					width:190px;
					min-height:70px;
					margin:3px 0 0 0;
					padding:7px;
					background: #F5F5F5;
					vertical-align: middle;
					line-height:130%;
					font-size:11px;
					cursor:default;
				}
				.contractormetaloc {
					display:block;
					position:relative;
					width:190px;
					min-height:25px;
					height:25px;
					margin:3px 0 0 0;
					padding:7px;
					background: #F5F5F5;
					color:#359;
					vertical-align: middle;
					line-height:130%;
					font-size:11px;
					cursor:default;
				}
				.imagecap {
					display:block;
					position:relative;
					width:190px;
					padding:5px 0 5px 8px;
					background:rgba(0,0,0,0.75);
					color:#FFF;
					font-size:12px;
					cursor:default;
				}
				.contractorvotes {
					position: absolute;
					top:0px;
					right:0px;
					width:50px;
					padding:2px;
					color:#FFF;
					text-align:center;
					background:rgba(51,170,153,0.75);
					cursor:default;
				}
				.contractorvideo {
					position: absolute;
					top:30px;
					right:4px;
					width:auto;
					line-height:100%;
					border-radius:60px;
					padding:2px;
					color:#FFF;
					text-align:center;
					background:#99D05A; 
					background:rgba(119, 174, 57, 0.5);
				}
				.contractorvideo:hover {
					background:rgba(119, 174, 57, 0.7);
				}
				.contractorplayicon {
					width:40px;
					height:40px;
				}
				.contractorvotes img {
					width:15px;
					height:15px;
					vertical-align:-2px;
				}
				.serviceonline {
				}
				.contractorActivity,.contractorLevel {
					position:absolute;
					left:0;
					top:201px;
					color:#555;
					cursor:default;
				}
				.contractorActivity img {
					vertical-align:bottom;
				}
				.contractorLevel {
					top:221px;
					font-size: 10px;
					color: #999;
				}
				.bookingbtn {
					display:inline-block;
					width:188px;
					padding: 7px 0;
					text-align:center;
					overflow: visible;
					margin: 5px 0 10px 0;
					font-size: 12px;
					white-space: nowrap;
					cursor: pointer;
					outline: 0px none;
					border-radius: 0.2em;
					color: #FFF !important;
					text-shadow: none;
					border: 1px solid #33E; /*#FAA*/
					background: #44E; /*#F77*/
				}
				.bookingbtn:hover {
					background: #33E;
					text-decoration:none;
				}
				
				/* responsive youtube iframe */
				.contractorsintro .youtubeembed {
					position: relative;
					padding-bottom: 56.25%; /* 16:9 */
					padding-top: 25px;
					height:0;
					margin:20px 0 60px 0;
				}
				.contractorsintro .youtubeembed iframe {
					position: absolute;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
					border:1px solid #CCC;
				}
				
				/* smartphones */
				@media only screen and (max-width:480px) 
				{
					.advantagestxt {
						display:block;
						width:95%;
					}
				}
				
				.footnotex {
					cursor:default;
					padding-bottom:1px;
					border-bottom:1px dotted #AAA;
				}
				
				.fussnoten {
					margin-top:100px;
					font-size:12px !important;
					line-height:200%;
				}
				
				/* SEARCH */
				.q2apro_usersearch_box {
					display:inline-block;
					margin:0px 0 20px 0;
				}
				#q2apro_usersearchlabel {
					display:inline-block;
					font-size: 19px;
					margin-right:10px;
					vertical-align:baseline;
				}
				.locationfilterbox {
					display:inline-block;
					font-size: 15px;
					margin-left:20px;
					vertical-align:baseline;
				}
				#q2apro_locationlabel {
				}
				input#q2apro_usersearch {
					width:300px;
					padding:7px 10px;
					border:1px solid #39F;
					font-size: 17px;
				}
				input#q2apro_usersearch:focus {
					/*box-shadow:none;*/
				}
				#ajaxsearch_results_wrap {
					display:block;
					margin: 30px 0 0 0;
				}
				.q2apro_usersearch_resultfield {
					display:inline-block;
					margin:10px 10px 0 0;
					vertical-align:top;
				}
				.q2apro_us_avatar img {
					border:1px solid #EEE;
				}
				.q2apro_us_link {
					word-wrap:break-word;
				}

				/* CSS spinner by lea.verou.me/2013/11/cleanest-css-spinner-ever/ */
				@keyframes spin {
					to { transform: rotate(1turn); }
				}
				.q2apro_us_progress {
					position: relative;
					display: inline-block;
					width: 5em;
					height: 5em;
					margin: 0 .5em;
					text-indent: 999em;
					overflow: hidden;
					animation: spin 1s infinite steps(8);
					font-size: 3px;
				}
				.q2apro_us_progress:before, .q2apro_us_progress:after, .q2apro_us_progress > div:before, .q2apro_us_progress > div:after {
					content: "";
					position: absolute;
					top: 0;
					left: 2.25em;
					width: .5em;
					height: 1.5em;
					border-radius: .2em;
					background: #eee;
					box-shadow: 0 3.5em #eee; /* container height - part height */
					transform-origin: 50% 2.5em; /* container height / 2 */
				}
				.q2apro_us_progress:before {
					background: #555;
				}
				.q2apro_us_progress:after {
					transform: rotate(-45deg);
					background: #777;
				}
				.q2apro_us_progress > div:before {
					transform: rotate(-90deg);
					background: #999;
				}
				.q2apro_us_progress > div:after {
					transform: rotate(-135deg);
					background: #bbb;
				}
				/* for js quicksearch */
				.highlight {
					background:#9D0;
					/*
					padding:1px 3px;
					border-radius:1px;
					*/
				}
				
				.bp_left {
					display:inline-block;
					width:auto;
					padding:15px;
					color:#FFF; /*#99D05A*/
					background:#FF6300; /*#99D05A;*/
					/*border:3px solid #99D05A;*/
					border-radius:2px;
					text-align:center;
					cursor:default;
					margin-right:10px;
					font-family:Calibri,sans-serif;
				}
				.withholdimage, .bestpriceimage {
					display:inline-block;
					width:80px;	
				}
				.withholdhead, .guaranteehead {
					display:block;
					margin-bottom:5px;
					font-size:19px;
					font-weight:bold;
					line-height:120%;
					text-transform:uppercase;
				}
				.withholdtextwrap, .bestpricetextwrap {
					display:inline-block;
					vertical-align:top;
					margin-top:8px;
				}
				.bestpricebox {
					margin:50px 0;
				}
				.bestprice {
					display:block;
					font-size:19px;
					font-weight:bold;
					line-height:120%;
					text-transform:uppercase;
				}
				.guarantee {
					display:inline-block;
					font-size:14px;
					font-weight:bold;
				}
				.withholdtext, .guaranteetext {
					display:inline-block;
					vertical-align:top;
					margin:0;
					max-width:420px;
					font-family: sans-serif;
					color: #4C5058;
				}
				.guaranteetext {
					max-width:490px;
				}
				.bestpricebadge {
					display:inline-block;
					width:113px;
					text-align:center;
				}

				/* smartphones */
				@media only screen and (max-width:480px) {
					#q2apro_usersearchlabel {
						font-size:20px;
						padding:10px 0;
					}
					input#q2apro_usersearch {
						width:90%;
						padding:10px;
						font-size: 15px;
					}
					.locationfilterbox {
						margin:20px 0 0 0;
					}
					
					.btn_graylight {
						margin-top:-10px;
						padding:5px 10px;
						font-size:12px;
					}
					
					.offerstablewrap {
						width:100%;
					}
					#offerstable tr td:nth-child(1) {
						min-height:120px;
						border-bottom:0;
					}
					#offerstable tr td:nth-child(1), 
					#offerstable tr td:nth-child(2) {
						width:100%;
						display:block;
					}
					.offertime {
						line-height: 120%;
						font-size: 11px;
					}
					#offerstable tr td:nth-child(2) {
						line-height: 120%;
						font-size: 11px;
						padding:5px 10px 20px 10px;
					}
				}

			</style>';
		
			$qa_content['custom'] .= $output;

			return $qa_content;

		} // end process_request
		
	}; // END class
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
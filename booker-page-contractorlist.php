<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_contractorlist
	{

		var $directory;
		var $urltoroot;

		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}

		// for display in admin interface under admin/pages
		function suggest_requests()
		{
			return array(
				array(
					'title' => 'Service buchen', // title of page
					'request' => 'contractorlist', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if($request=='contractorlist')
			{
				return true;
			}
			return false;
		}

		function process_request($request)
		{

			/* start content */
			$qa_content = qa_content_prepare();
			qa_set_template('booker contractorlist');

			$userid = qa_get_logged_in_userid();
			$isadmin = (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN);
			$ismobile = qa_is_mobile_probably();

			// search parameter
			$searchstring = qa_get('s');
			if(empty($searchstring))
			{
				$searchstring = '';
			}
			// page title
			$qa_content['title'] = qa_lang('booker_lang/conli_title');

			// init
			$qa_content['custom'] = '';

			$doalphabetic = false;
			$start = qa_get('start');
			$pagesize = 24; // contractors to show per page
			if(isset($start))
			{
				$doalphabetic = true;
				$count = booker_get_contractorcount(true, !isset($userid)); // total
				$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, true);
			}
			else
			{
				$start = 0;
			}

			$output = '';

			$output .= '
				<div class="row clearfix">
			';

			$output .= '
				<a href="'.qa_path('userrecommend').'" class="defaultbutton btn_orange" style="float:right;margin-right:5px;">
					'.qa_lang('booker_lang/recommendcontractor').'
				</a>
			'; // <i class="fa fa-bullhorn fa-lg" style="margin-right:4px;"></i>

			if(!$doalphabetic)
			{
				$output .= '
					<a class="defaultbutton btn_graylight hide" style="margin-top:10px;clear:both;float:right;" href="'.qa_path('contractorlist').'?start=0">
						<i class="fa fa-users" style="margin-right:4px;"></i> '.qa_lang('booker_lang/showall_contractors_btn').'
					</a>
				';
			}

			$output .= '
				</div> <!-- row -->
			';

			/*
			$output .= '
					<div class="withholdbox">
						<div class="withholdimage">
							<img src="'.$this->urltoroot.'images/moneylock.png" alt="money lock" style="width:70px;" />
						</div>
						<div class="withholdtextwrap">
							<div class="withholdhead">
								'.qa_lang('booker_lang/moneywithhold').'
							</div>
							<p class="withholdtext">
								'.qa_lang('booker_lang/withholdguarantee').'
							</p>
						</div>
					</div> <!-- withholdbox -->
			';
			*/

			if(empty($userid))
			{
				$actiondate = date(qa_lang('booker_lang/date_format_php'), strtotime('next monday'));

				$voucherinfo = '';
				if(qa_opt('booker_voucherenabled'))
				{
					$voucherinfo = '
						<p style="color:#33F;margin-bottom:20px;">
							'.qa_lang('booker_lang/voucher_until').' '.$actiondate.':
							'.str_replace('~~~', qa_opt('booker_vouchervalue'), qa_lang('booker_lang/voucher_detail')).'
						</p>
						<img src="'.$this->urltoroot.'images/payicon_guthaben-50.png" alt="voucher pig" style="width:50px;height:50px;margin-right:10px;" />
					';
				}

				/*
				$output .= '
				<div class="askforregister">
					<p style="color:#33F;margin-top:30px;">
						'.
						strtr( qa_lang('booker_lang/registerforbooking'), array(
						'^1' => '<a href="'.qa_path('register').'?to=userprofile">',
						'^2' => '</a>'
						)).
						'
					</p>
					'.$voucherinfo.'
					<a class="defaultbutton clientbutton" style="display:none;" href="'.qa_path('register').'?to=userprofile">'.qa_lang('booker_lang/registerclient').'</a>
				</div>
				';
				*/
			}

            /**
			$output .= '
					<section id="search">
						<label for="search-input">
							<i class="fa fa-search" aria-hidden="true"></i><span class="sr-only">'.qa_lang('booker_lang/search_placeholder').'</span>
						</label>

						<input id="search-input" class="form-control input-lg" placeholder="'.qa_lang('booker_lang/search_placeholder').'" autocomplete="off" spellcheck="false" autocorrect="off" tabindex="1" autofocus>

						<a id="search-clear" href="#" class="fa fa-times-circle" aria-hidden="true">
							<span class="sr-only">'.qa_lang('booker_lang/search_clear').'</span>
						</a>

						<div class="q2apro_us_progress"><div>Loading…</div></div>

					</section>

					<section id="searchresults">
						<div class="locationfilterbox">
							<span id="q2apro_locationlabel" class="hide">'.qa_lang('booker_lang/searchlocation_label').':</span>
							<select id="locations">
								<option>Klaipėda</option>
								<option>Kaunas</option>
								<option>Vilnius</option>
							</select>
						</div>

						<div id="ajaxsearch_results_wrap">
							<h2>
								'.qa_lang('booker_lang/search_results').':
							</h2>
							<div id="q2apro_ajaxsearch_results"></div>
						</div>

					</section>

			';
            /**/

			// only show contracted users
			$contracted = true; // !isset($userid);
			$needprice = false;
			
			// get all contractors
			if(!$doalphabetic)
			{
				// available, needprice
				$allcontractors = booker_get_approved_contractors(true, $needprice, 'RAND()', $start, 12, $contracted);
			}
			else
			{
				// list all contractors, even not-available, but need price still
				$allcontractors = booker_get_approved_contractors(false, $needprice, 'realname ASC', $start, $pagesize, $contracted);
			}
			$metaOutput = '';
			$metacount = count($allcontractors);

			// contractors
			$output .= '<div class="conlisting clearfix">';

			if(!$doalphabetic)
			{
				$output .= '
					<h2>
						'.qa_lang('booker_lang/random_contractors').':
					</h2>
				';
			}
			else
			{
				$output .= '
					<h2>
						'.qa_lang('booker_lang/alphabetic_contractors').':
					</h2>
				';
			}

			$contractors = array();

			foreach($allcontractors as $contractor)
			{
				$contractorid = $contractor['userid'];

				// check if contractor has week events in his week schedule, otherwise we cannot list him
				// if(contractorisapproved($contractorid) && contractorhasweekplan($contractorid))
				if(contractorisapproved($contractorid))
				{
					$output .= booker_get_contractordata_box($contractorid);
				}
			}

			$output .= '
			</div> <!-- conlisting -->
			';

			// for default page list advantages for client and client ratings
			if(!$doalphabetic)
			{
				/*
				$output .= '
				<div class="advantages">
					<div class="adviconholer">
						<img src="'.$this->urltoroot.'images/moneyback.png" alt="'.qa_lang('booker_lang/moneybackguarantee').'" />
					</div>
					<div class="advantagestxt">
						<p class="moneybackhead">
							'.qa_lang('booker_lang/moneybackguarantee').'
						</p>
						<p>
							'.qa_lang('booker_lang/moneybackdetail').'
						</p>
					</div>
				</div> <!-- advantages -->
				';
				*/

				/*
				$output .= '
						<div class="bestpricebox">
							<div class="bp_left">
								<div class="bestpricebadge">
									<div class="bestprice">
										'.qa_lang('booker_lang/bestprice').'
									</div>
									<span class="guarantee">
										'.qa_lang('booker_lang/guaranteed').'
									</span>
								</div>
							</div>
							<div class="guaranteetext">
								<p class="moneybackhead" style="margin-bottom:10px;display:none;">
									'.qa_lang('booker_lang/bestpriceguarantee').'
								</p>
								<p style="margin-top:10px;">
									'.qa_lang('booker_lang/weguarantee').'
								</p>
							</div>
						</div> <!-- bestpricebox -->
				';
				*/

                /**
				$output .= '
					<div class="bestpricebox">
						<div class="bestpriceimage">
							<img src="'.$this->urltoroot.'images/icon-bestprice.png" alt="icon best price" style="width:70px;" />
						</div>
						<div class="bestpricetextwrap">
							<div class="guaranteehead">
								'.qa_lang('booker_lang/bestprice').'
							</div>
							<p class="guaranteetext">
								'.qa_lang('booker_lang/weguarantee').'
							</p>
						</div>
					</div> <!-- bestpricebox -->
				';
                /**/

				/***
				$output .= '
					<div class="ratings_wrap">
					<h3>
						'.qa_lang('booker_lang/customerratings').'
					</h3>
				';
				$output .= q2apro_booker_list_ratings(7, null);
				$output .= '</div> <!-- ratings_wrap -->';
				***/
			}

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

						$('.about-whatis').click( function()
						{
							$('html,body').animate({
							   scrollTop: $('.section-feature:first').offset().top
							});
						});

						// if url s=searchterm
						var urlQueryS = getURLParameter('s');
						if( ( typeof(urlQueryS) !== 'undefined') && (urlQueryS != null) && (urlQueryS!='null') && (urlQueryS!='') )
						{
							// multiple words in URL are divided by + sign, so replace with space
							urlQueryS = urlQueryS.replace(/\+/g, ' ');
							// set search text
							$('#search-input').val(urlQueryS);
							// trigger search
							$('#search-input').trigger('keyup');
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

						$('#search-input').keyup( function(e)
						{
							// ignore cursor keys
							var code = (e.keyCode || e.which);
							if(code == 37 || code == 38 || code == 39 || code == 40) {
								return;
							}

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
							     $('.conlisting, .ratings_wrap, .headlinespan').hide();
							     $('.q2apro_us_progress, #search-clear').show();
							     // send searchterm after little delay to save server load
							     window.clearTimeout(window.searchTimer);
							     window.searchTimer = window.setTimeout(function() { booker_ajaxsearch(servicesearch); }, 500);
							     // shrink hero
							     $('.section-hero').addClass('search-activated');
							}
							else
							{
							     $('.conlisting, .ratings_wrap, .headlinespan').show();
							     $('#ajaxsearch_results_wrap, .locationfilterbox').hide();
							     // show hero
							     $('.section-hero').removeClass('search-activated');
							}
						});

						$('#locations').on('change keyup', function()
						{
							var locselected = $(this).find('option:selected').text();
							if(locselected == '".qa_lang('booker_lang/all_locations')."')
							{
								// show all
								$('#q2apro_ajaxsearch_results .contractorBox').show();
							}
							else
							{
								$('#q2apro_ajaxsearch_results .contractorBox').each( function()
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
						var qs = $('#search-input').quicksearch('#q2apro_ajaxsearch_results', {
							// noResults: '#noresults',
							// stripeRows: ['odd', 'even'],
							// loader: '.q2apro_us_progress',
							// delay: 250,
							onBefore: function()
							{
								// remove former highlighting
								$('#q2apro_ajaxsearch_results').unhighlight();
							},
							onAfter: function()
							{
								if($('#search-input').val()!='' && $('#search-input').val().length>1)
								{
									$('#q2apro_ajaxsearch_results').highlight( $('#search-input').val() );
								}
							},
						});

						// if we have a value by url trigger search
						if($('#search-input').val()!='')
						{
							$('#search-input').trigger('keyup');
						}

						function booker_ajaxsearch(servicesearch_ajax)
						{
							console.log('searching');
							$('.q2apro_us_progress').show();
							$.ajax({
								 type: 'POST',
								 url: '".qa_path('ajaxsearch')."',
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
										htmldata += '<a class=\"defaultbutton\" href=\"".qa_path('requestcreate')."\">".qa_lang('booker_lang/request_create_btn')."</a>';
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


									// send searchterm after little delay to save server load again
									window.clearTimeout(window.searchTrack);
									window.searchTrack = window.setTimeout(sendsearchterm, 500);
								 }
							}); // END AJAX
						} // end q2apro_ajaxsearch

						function unique(array)
						{
							return $.grep(array, function(el, index) {
								return index == $.inArray(el, array);
							});
						}

						function sendsearchterm()
						{
							console.log('tracking: '+servicesearch);
							$.ajax({
								type: 'POST',
								url: '".qa_path('searchtrack')."',
								data: { search: servicesearch },
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
							var servicesearch = $.trim($('#search-input').val());

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

						$('#search-clear').click( function()
						{
							$('#search-input').val('');
							$('#search-input').trigger('keyup');
						});

					}); // END ready

					function getURLParameter(name) {
						return decodeURI(
							(RegExp(name + '=' + '(.+?)(&|$)').exec(location.search)||[,null])[1]
						);
					}

				</script>
			";

			// EXTRA CSS
            /**
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
				.row {
					display:block;
				}
				.clearfix:after {
					content: ".";
					display: block;
					height: 0;
					clear: both;
					visibility: hidden;
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
				.contractorportfolio {
					display:block;
					height:72px;
					color:#777;
					font-size:11px;
					word-wrap:break-word;
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
					margin-top: 30px;
				}
				.contractorBox {
					position:relative;
					display:inline-block;
					width:auto !important;
					margin:0 20px 50px 0;
					vertical-align:top;
					background:#F9F9F9;
					border:1px solid #DDE;
					padding:0;
					box-sizing: border-box;
					box-shadow: black 0 -60px 50px -90px inset;
				}
				.contractorname {
					display:inline-block;
					font-size:16px;
					margin-bottom:5px;
				}
				.contractoravatar {
					display:block;
					width:220px;
					height:240px;
					background-position:top;
					background-repeat:no-repeat;
					background-size:cover;
					text-align:center;
				}
				.contractoravatar,
				.avatarwrap .orderme {
					-webkit-transition: all 0.2s linear;
					-moz-transition: all 0.2s linear;
					-ms-transition: all 0.2s linear;
					-o-transition: all 0.2s linear;
					transition: all 0.2s linear;
				}
				.avatarwrap:hover {
					background:#000;
				}
				.avatarwrap:hover .contractoravatar {
					opacity:0.5;
				}
				.avatarwrap .orderme {
					display:inline-block;
					position:absolute;
					top:100px;
					left:75px;
					color:#FFF;
					z-index:5001;
					opacity:0;
				}
				.avatarwrap:hover .orderme {
					opacity:0.8;
				}

				.contractormeta {
					position:relative;
					width:220px;
					min-height:70px;
					margin:3px 0 0 0;
					padding: 7px 15px;
					vertical-align: middle;
					line-height:130%;
					cursor:default;
				}
				.contractormetaloc {
					display:block;
					position:relative;
					width:auto;
					min-height:25px;
					height:25px;
					margin:3px 0 0 7px;
					padding:7px;
					color:#359;
					vertical-align: middle;
					line-height:130%;
					font-size:11px;
					cursor:default;
				}
				.imagecap {
					display:block;
					position:relative;
					width:220px;
					padding:7px 0 7px 14px;
					background:rgba(0,0,0,0.75);
					color:#FFF;
					font-size:13px;
					cursor:default;
				}
				.contractorvotes {
					display:inline-block;
					position: absolute;
					top:0px;
					right:0px;
					width:65px;
					padding:2px;
					color:#FFF;
					text-align:center;
					background:rgba(151,170,153,0.75);
					cursor:default;
				}
				.contractorvotes .fa-star {
					opacity:0.9;
				}
				.contractorvotes .userrating {
					vertical-align: middle;
					font-size:18px;
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
					width:auto;
					padding: 7px 10px;
					text-align:center;
					overflow: visible;
					float:right;
					margin:0 -1px -1px 0;
					font-size: 12px;
					white-space: nowrap;
					cursor: pointer;
					outline: 0px none;
					color: #FFF !important;
					text-shadow: none;
					border: 1px solid #33E;
					background: #44E;
					border-radius:3px;
					border-top-right-radius: 0;
					border-bottom-right-radius: 0;
					border-bottom-left-radius: 0;
				}
				.bookingbtn:hover {
					background: #33E;
					text-decoration:none;
				}

				.contractorsintro .youtubeembed {
					position: relative;
					padding-bottom: 56.25%;
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

				@media only screen and (max-width:480px)
				{
					.advantagestxt {
						display:block;
						width:95%;
					}
				}

				#searchresults {
					margin-bottom:70px;
				}
				.locationfilterbox {
					display:block;
					width:250px;
					font-size: 15px;
					margin:20px auto;
					vertical-align:baseline;
					text-align:center;
				}
				#q2apro_locationlabel {
				}

				#ajaxsearch_results_wrap {
					display:block;
					margin: 30px 0 0 0;
				}
				.search-input_resultfield {
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
					box-shadow: 0 3.5em #eee;
					transform-origin: 50% 2.5em;
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
				.highlight {
					background:#9D0;
				}

				.bp_left {
					display:inline-block;
					width:auto;
					padding:15px;
					color:#FFF;
					background:#FF6300;
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

				#search {
					position: relative;
					font-size: 18px;
					padding-top: 40px;
					margin: 20px auto 0;
					max-width:500px;
				}
				#search label {
					display: inline-block;
					max-width: 100%;
					margin-bottom: 5px;
					font-weight: bold;
					position: absolute;
					left: 17px;
					top: 51px;
				}
				.form-control {
					display: block;
					width: 100%;
					height: 34px;
					padding: 6px 12px;
					font-size: 14px;
					line-height: 1.42857143;
					color: #555;
					background-color: #fff;
					background-image: none;
					border: 1px solid #ccc;
					border-radius: 4px;
					-webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,0.075);
					box-shadow: inset 0 1px 1px rgba(0,0,0,0.075);
					-webkit-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
					-o-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
					transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
				}
				.form-control:focus {
					border-color:#66afe9;
					outline:0;
					-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, 0.6);
					box-shadow:inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, 0.6)
				}

				#search #search-clear {
					text-decoration: none;
					position: absolute;
					right: 18px;
					top: 54px;
					color: #b3b3b3;
					display:none;
				}
				.input-lg {
					height: 46px;
					padding: 10px 16px;
					font-size: 18px;
					line-height: 1.3333333;
					border-radius: 6px;
				}
				#search #search-input, #search .hint {
					padding-left: 43px;
					padding-right: 43px;
					border-radius: 23px;
				}

				.sr-only {
					position: absolute;
					width: 1px;
					height: 1px;
					padding: 0;
					margin: -1px;
					overflow: hidden;
					clip: rect(0, 0, 0, 0);
					border: 0;
				}

				@media only screen and (max-width:480px) {
					.locationfilterbox {
						margin:20px 0 0 0;
					}

					.btn_graylight {
						margin-top:-10px;
						padding:5px 10px;
						font-size:12px;
					}
				}

			</style>';
            /**/

			$qa_content['custom'] .= $output;

			return $qa_content;

		} // end process_request

	}; // END class


	function q2apro_exp_sortByOrderPage($a, $b) {
		// return $b['apost'] - $a['apost'];
		// return $b['aselecteds'] - $a['aselecteds'];
		// return $b['points'] - $a['points'];
		return $b['votes'] - $a['votes'];
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/

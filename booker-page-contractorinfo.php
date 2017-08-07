<?php
/*
	Plugin Name: BOOKER
*/

	class booker_page_contractorinfo 
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
					'title' => 'booker Page Contractor Info', // title of page
					'request' => 'contractorinfo', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='contractorinfo') 
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
				qa_set_template('booker contractorinfo');
				$qa_content['error'] = qa_lang('booker_lang/page_deactive');
				return $qa_content;
			}
			
			$contractorid = qa_get_logged_in_userid();
			
			/*
			if(empty($contractorid)) 
			{
				$qa_content = qa_content_prepare();
				qa_set_template('booker contractorinfo');
				// $qa_content['error'] = qa_insert_login_links(qa_lang('booker_lang/needregisterlogin'));
				$qa_content['custom'] = booker_loginform_output($request);
				return $qa_content;
			}*/
			
			/* start content */
			$qa_content = qa_content_prepare();
			qa_set_template('booker contractorinfo');
			$qa_content['title'] = 'Information';
			
			
			$becomecontractoranchor = '';
			if(!qa_is_logged_in()) 
			{
				$becomecontractoranchor = '<li><a href="#join">Anbieter werden</a></li>';
			}
			
			// init
			$output = '';			
			$output .= '
			
			<div id="faq" class="contractorfaq">
				<h2>
					Wichtige Informationen
				</h2>
				<p>
					Im Folgenden wichtige Hinweise und häufige Fragen (FAQ) für alle Anbieter.
				</p>
				<ol class="contractorfaq-ol">
					<li>
						<p style="font-weight:bold;">Was passiert, wenn ich einen Kunde privat übernehme?</p>
						<p>
							Dies wird "Abwerben" genannt. Auch wenn der Kunde dies vorschlägt, ist es nicht gestattet, ihn privat zu übernehmen. Alle über uns akquirierten Kunden müssen bei uns verbleiben. Zur Information: Die 25 % Anteil am Honorar gehen direkt in die Werbung von Neukunden und ins Marketing. Für jeden Kunde wurde bereits im Vorfeld viel Geld investiert. Solltest du Kunde abwerben, so verhinderst du die Refinanzierung von Investitionen und schädigst die Zukunft unseres Projektes und allen unseren Mitgliedern. # Für jeden abgeworbenen Kunden ist eine Geldstrafe von <b>500 EUR</b> zu zahlen. Auch wenn wir in allen Bereichen verständnisvoll und entspannt sind, dies hier ist überlebenswichtig und wird notfalls gerichtlich durchgesetzt. 
						</p>
					</li>
					<li>
						<p style="font-weight:bold;">Wie viel Zeit habe ich, mich auf den Termin vorzubereiten?</p>
						<p>Die Termine werden mindestens 24 Stunden im Voraus gebucht, wenn nicht sogar mehrere Tage vorher. Nachdem der Kunde seine Termine gebucht und bezahlt hat, kann er für jeden Termin die Auftragdetails eingeben und ggf. Dateien hochladen. Auf diese hast du als Anbieter Zugriff auf der Seite <a href="'.qa_path('contractorschedule').'">Termine</a>.</p>
					</li>
					<li>
						<p style="font-weight:bold;">Warum ist mein Profil auch im Forum vorzufinden?</p>
						<p>Das Frage-Antwort-Forum und das Buchungssystem greifen auf das gleiche System zurück. Der Sinn dahinter: Wenn du Fragen auf beantwortest, wird direkt unter deiner Antwort ein Button "Service buchen" angezeigt. So erhöhst du die Chance auf neue Kunden für dich.</p>
					</li>
					<!--
					<li>
						<p style="font-weight:bold;">Wann erhalte ich meinen Vertrag?</p>
						<p>Der Vertrag wird dir übersendet, sobald du deinen ersten Termin absolviert hast. 
						*** Ggf. online abschließen möglich?</p>
					</li>
					-->
					<li>
						<p style="font-weight:bold;">Warum soll ich meinen vollen Namen angeben?</p>
						<p>Aus Kundensicht ist die Antwort einfach: Ein echter Name schafft Vertrauen und mit Vertrauen ist es einfacher, neue Kunden zu gewinnen. Zudem stehen wir für Seriösität und dies zeigen unsere Mitglieder mit Angabe ihrer echten Identität. Darüber hinaus ist es für Neukunden leichter, den Anbieter auch im Forum wiederzufinden und zu buchen.</p>
					</li>
					
				</ol>
			
				<p style="margin-top:30px;">
					Wenn du etwas Wichtiges bei den Informationen ergänzen möchtest, gib uns bitte <a href="'.booker_getcontactlink().'">direkt Bescheid</a>.
				</p>
			</div> <!-- contractorfaq -->
			
			<div id="join">
				<h2>
					Service-Anbieter werden
				</h2>
				<p>
					Nachfolgend das Verfahren, um als Anbieter bei uns aufgenommen zu werden: 
				</p>
				<p>
					1. Auf unserer Webseite <a href="'.qa_path('register').'?to=userprofile">registrieren</a> und das <a href="'.qa_path('userprofile').'">Anbieterprofil</a> mit Foto ausfüllen.
				</p>
				<p>
					2. <a href="#faq">Wichtige Informationen</a> durchlesen.
				</p>
				<p>
					3. Wir prüfen die Daten, schalten Ihren Account frei und Sie werden als <a href="'.qa_path('contractorlist').'">Anbieter gelistet</a>.
				</p>
				
				<a class="defaultbutton" style="margin-top:15px;" href="'.qa_path('userprofile').'">Jetzt Anbieter werden</a>
			</div> <!-- join -->

			';
			
			
			
			// jquery
			$output .= "
			<script type=\"text/javascript\">
				$(document).ready(function()
				{
					// create table of contents
					
					var cntr = 0;
					$('.contractorfaq-ol li').each( function() {
						cntr++;
						// assign ids to li tags
						$(this).attr('id', cntr);
					});
					
					var ordernav = Array();
					$('.contractorfaq-ol li').each( function() { 
						var anchor = $(this).attr('id');
						var desc = $(this).find('p').eq(0).text();
						if(typeof(anchor) != 'undefined' && anchor.length>0) {
							var navitem = {
								text: desc,
								anchor: anchor,
							};
							ordernav.push(navitem);
						}
					});
					
					if(ordernav.length>0) {
						$('.contractorfaq-ol').prepend( $('<p class=\"ordernavhead\">Inhaltsübersicht:</p> <ol class=\"faqjump\"></ol>') );
						$.each(ordernav, function(index, item)
						{
							$('.faqjump').append('<li><a href=\"#'+item.anchor+'\">'+item.text.trim()+'</a></li>');
						});
					}
					
					// nav bar is covering jump to faq#id
					$(window).scroll(function (event) {
						var scroll = $(window).scrollTop();
						if(scroll>100)
						{
							$('.navbar-fixed-top').hide();
						}
						else {
							$('.navbar-fixed-top').show();
						}
					});
					

				});
			</script>
			";

			// css
			$output .= '
			<style type="text/css">
				h1 {
					display:none;
				}
				h2 {
					margin-top:50px;
					/*border-top: 2px dashed #CCC;*/
				}
				.fixmessage {
					display:none;
				}
				.ordernavhead {
				}
				.faqjump {
					display:block;
					margin:10px 0 40px 0;
					padding-left:15px;
				}
				.secrectbox h3 {
					margin-top:0;
				}
				.secrectbox {
					display:inline-block;
					margin:20px 0;
					padding:20px;
					border:1px solid #DDC;
					background:#F5F5F5;
					width:80%;
				}
				.contractorfaq {
					margin-bottom:20px;
				}
				.contractorfaq-ol li {
					margin-bottom:0px;
				}
				#join {
					margin-bottom:70px;
				}
			</style>';

			$qa_content['custom'] = $output;
			
			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
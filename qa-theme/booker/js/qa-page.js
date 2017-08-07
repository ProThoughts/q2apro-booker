// LIT
var lang_can_edit_submit = "Išsiuntus galima dar 60 minutes taisyti tekstą.";
var lang_write_correctly = "Rašykite teisingai. Klausimai didžiosiomis raidėmis bus ištrinami.";
var lang_missing_chars_1 = "Trūksta dar";
var lang_missing_chars_2 = "ženklų.";


var recenturl = window.location.href; // window.location.pathname=="/ask"

	console.log(recenturl);
// DE
if(recenturl.indexOf('kyga.de') !== -1)
{
	lang_can_edit_submit = 'Nach dem Absenden kannst du deinen Beitrag noch 5 min bearbeiten.';
	lang_write_correctly = 'Bitte richtig schreiben. FRAGEN IN GROSSSCHRIFT werden gel%F6scht.';
	lang_missing_chars_1 = "Noch";
	lang_missing_chars_2 = "Zeichen notwendig.";
}


function qa_reveal(elem, type, callback)
{
	if (elem)
		$(elem).slideDown(400, callback);
}

function qa_conceal(elem, type, callback)
{
	if (elem)
		$(elem).slideUp(400);
}

function qa_set_inner_html(elem, type, html)
{
	if (elem)
		elem.innerHTML=html;
}

function qa_set_outer_html(elem, type, html)
{
	if (elem) {
		var e=document.createElement('div');
		e.innerHTML=html;
		elem.parentNode.replaceChild(e.firstChild, elem);
	}
}

function qa_show_waiting_after(elem, inside)
{
	if (elem && !elem.qa_waiting_shown) {
		var w=document.getElementById('qa-waiting-template');

		if (w) {
			var c=w.cloneNode(true);
			c.id=null;

			if (inside)
				elem.insertBefore(c, null);
			else
				elem.parentNode.insertBefore(c, elem.nextSibling);

			elem.qa_waiting_shown=c;
		}
	}
}

function qa_hide_waiting(elem)
{
	var c=elem.qa_waiting_shown;

	if (c) {
		c.parentNode.removeChild(c);
		elem.qa_waiting_shown=null;
	}
}

function qa_vote_click(elem)
{
	var ens=elem.name.split('_');
	var postid=ens[1];
	var vote=parseInt(ens[2]);
	var code=elem.form.elements.code.value;
	var anchor=ens[3];

	qa_ajax_post('vote', {postid:postid, vote:vote, code:code},
		function(lines) {
			if (lines[0]=='1') {
				qa_set_inner_html(document.getElementById('voting_'+postid), 'voting', lines.slice(1).join("\n"));

			} else if (lines[0]=='0') {
				var mess=document.getElementById('errorbox');

				if (!mess) {
					var mess=document.createElement('div');
					mess.id='errorbox';
					mess.className='qa-error';
					mess.innerHTML=lines[1];
					mess.style.display='none';
				}

				var postelem=document.getElementById(anchor);
				var e=postelem.parentNode.insertBefore(mess, postelem);
				qa_reveal(e);

			} else
				qa_ajax_error();
		}
	);

	return false;
}

function qa_notice_click(elem)
{
	var ens=elem.name.split('_');
	var code=elem.form.elements.code.value;

	qa_ajax_post('notice', {noticeid:ens[1], code:code},
		function(lines) {
			if (lines[0]=='1')
				qa_conceal(document.getElementById('notice_'+ens[1]), 'notice');
			else if (lines[0]=='0')
				alert(lines[1]);
			else
				qa_ajax_error();
		}
	);

	return false;
}

function qa_favorite_click(elem)
{
	var ens=elem.name.split('_');
	var code=elem.form.elements.code.value;

	qa_ajax_post('favorite', {entitytype:ens[1], entityid:ens[2], favorite:parseInt(ens[3]), code:code},
		function (lines) {
			if (lines[0]=='1')
				qa_set_inner_html(document.getElementById('favoriting'), 'favoriting', lines.slice(1).join("\n"));
			else if (lines[0]=='0') {
				alert(lines[1]);
				qa_hide_waiting(elem);
			} else
				qa_ajax_error();
		}
	);

	qa_show_waiting_after(elem, false);

	return false;
}

function qa_ajax_post(operation, params, callback)
{
	jQuery.extend(params, {qa:'ajax', qa_operation:operation, qa_root:qa_root, qa_request:qa_request});

	jQuery.post(qa_root, params, function(response) {
		var header='QA_AJAX_RESPONSE';
		var headerpos=response.indexOf(header);

		if (headerpos>=0)
			callback(response.substr(headerpos+header.length).replace(/^\s+/, '').split("\n"));
		else
			callback([]);

	}, 'text').fail(function(jqXHR) { if (jqXHR.readyState>0) callback([]) });
}

function qa_ajax_error()
{
	alert('Unexpected response from server - please try again or switch off Javascript.');
}



/**** Q2APRO SCRIPTING ****/

// tipsy tooltips for jquery, version 1.0.0a, (c) 2008-2010 jason frame [jason@onehackoranother.com], released under the MIT license
(function($){function maybeCall(thing,ctx){return typeof thing=="function"?thing.call(ctx):thing}function Tipsy(element,options){this.$element=$(element);this.options=options;this.enabled=true;this.fixTitle()}Tipsy.prototype={show:function(){var title=this.getTitle();if(title&&this.enabled){var $tip=this.tip();$tip.find(".tipsy-inner")[this.options.html?"html":"text"](title);$tip[0].className="tipsy";$tip.remove().css({top:0,left:0,visibility:"hidden",display:"block"}).prependTo(document.body);var pos=
$.extend({},this.$element.offset(),{width:this.$element[0].offsetWidth,height:this.$element[0].offsetHeight});var actualWidth=$tip[0].offsetWidth,actualHeight=$tip[0].offsetHeight,gravity=maybeCall(this.options.gravity,this.$element[0]);var tp;switch(gravity.charAt(0)){case "n":tp={top:pos.top+pos.height+this.options.offset,left:pos.left+pos.width/2-actualWidth/2};break;case "s":tp={top:pos.top-actualHeight-this.options.offset,left:pos.left+pos.width/2-actualWidth/2};break;case "e":tp={top:pos.top+
pos.height/2-actualHeight/2,left:pos.left-actualWidth-this.options.offset};break;case "w":tp={top:pos.top+pos.height/2-actualHeight/2,left:pos.left+pos.width+this.options.offset};break}if(gravity.length==2)if(gravity.charAt(1)=="w")tp.left=pos.left+pos.width/2-15;else tp.left=pos.left+pos.width/2-actualWidth+15;$tip.css(tp).addClass("tipsy-"+gravity);$tip.find(".tipsy-arrow")[0].className="tipsy-arrow tipsy-arrow-"+gravity.charAt(0);if(this.options.className)$tip.addClass(maybeCall(this.options.className,
this.$element[0]));if(this.options.fade)$tip.stop().css({opacity:0,display:"block",visibility:"visible"}).animate({opacity:this.options.opacity});else $tip.css({visibility:"visible",opacity:this.options.opacity})}},hide:function(){if(this.options.fade)this.tip().stop().fadeOut(function(){$(this).remove()});else this.tip().remove()},fixTitle:function(){var $e=this.$element;if($e.attr("title")||typeof $e.attr("original-title")!="string")$e.attr("original-title",$e.attr("title")||"").removeAttr("title")},
getTitle:function(){var title,$e=this.$element,o=this.options;this.fixTitle();var title,o=this.options;if(typeof o.title=="string")title=$e.attr(o.title=="title"?"original-title":o.title);else if(typeof o.title=="function")title=o.title.call($e[0]);title=(""+title).replace(/(^\s*|\s*$)/,"");return title||o.fallback},tip:function(){if(!this.$tip)this.$tip=$('<div class="tipsy"></div>').html('<div class="tipsy-arrow"></div><div class="tipsy-inner"></div>');return this.$tip},validate:function(){if(!this.$element[0].parentNode){this.hide();
this.$element=null;this.options=null}},enable:function(){this.enabled=true},disable:function(){this.enabled=false},toggleEnabled:function(){this.enabled=!this.enabled}};$.fn.tipsy=function(options){if(options===true)return this.data("tipsy");else if(typeof options=="string"){var tipsy=this.data("tipsy");if(tipsy)tipsy[options]();return this}options=$.extend({},$.fn.tipsy.defaults,options);function get(ele){var tipsy=$.data(ele,"tipsy");if(!tipsy){tipsy=new Tipsy(ele,$.fn.tipsy.elementOptions(ele,
options));$.data(ele,"tipsy",tipsy)}return tipsy}function enter(){var tipsy=get(this);tipsy.hoverState="in";if(options.delayIn==0)tipsy.show();else{tipsy.fixTitle();setTimeout(function(){if(tipsy.hoverState=="in")tipsy.show()},options.delayIn)}}function leave(){var tipsy=get(this);tipsy.hoverState="out";if(options.delayOut==0)tipsy.hide();else setTimeout(function(){if(tipsy.hoverState=="out")tipsy.hide()},options.delayOut)}if(!options.live)this.each(function(){get(this)});if(options.trigger!="manual"){var binder=
options.live?"live":"bind",eventIn=options.trigger=="hover"?"mouseenter":"focus",eventOut=options.trigger=="hover"?"mouseleave":"blur";this[binder](eventIn,enter)[binder](eventOut,leave)}return this};$.fn.tipsy.defaults={className:null,delayIn:0,delayOut:0,fade:false,fallback:"",gravity:"n",html:false,live:false,offset:0,opacity:0.8,title:"title",trigger:"hover"};$.fn.tipsy.elementOptions=function(ele,options){return $.metadata?$.extend({},options,$(ele).metadata()):options};$.fn.tipsy.autoNS=function(){return $(this).offset().top>
$(document).scrollTop()+$(window).height()/2?"s":"n"};$.fn.tipsy.autoWE=function(){return $(this).offset().left>$(document).scrollLeft()+$(window).width()/2?"e":"w"};$.fn.tipsy.autoBounds=function(margin,prefer){return function(){var dir={ns:prefer[0],ew:prefer.length>1?prefer[1]:false},boundTop=$(document).scrollTop()+margin,boundLeft=$(document).scrollLeft()+margin,$this=$(this);if($this.offset().top<boundTop)dir.ns="n";if($this.offset().left<boundLeft)dir.ew="w";if($(window).width()+$(document).scrollLeft()-
$this.offset().left<margin)dir.ew="e";if($(window).height()+$(document).scrollTop()-$this.offset().top<margin)dir.ns="s";return dir.ns+(dir.ew?dir.ew:"")}}})(jQuery);

/* Lazy Load 1.9.3 - MIT license - Copyright 2010-2013 Mika Tuupola */
!function(a,b,c,d){var e=a(b);a.fn.lazyload=function(f){function g(){var b=0;i.each(function(){var c=a(this);if(!j.skip_invisible||c.is(":visible"))if(a.abovethetop(this,j)||a.leftofbegin(this,j));else if(a.belowthefold(this,j)||a.rightoffold(this,j)){if(++b>j.failure_limit)return!1}else c.trigger("appear"),b=0})}var h,i=this,j={threshold:0,failure_limit:0,event:"scroll",effect:"show",container:b,data_attribute:"original",skip_invisible:!0,appear:null,load:null,placeholder:"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAANSURBVBhXYzh8+PB/AAffA0nNPuCLAAAAAElFTkSuQmCC"};return f&&(d!==f.failurelimit&&(f.failure_limit=f.failurelimit,delete f.failurelimit),d!==f.effectspeed&&(f.effect_speed=f.effectspeed,delete f.effectspeed),a.extend(j,f)),h=j.container===d||j.container===b?e:a(j.container),0===j.event.indexOf("scroll")&&h.bind(j.event,function(){return g()}),this.each(function(){var b=this,c=a(b);b.loaded=!1,(c.attr("src")===d||c.attr("src")===!1)&&c.is("img")&&c.attr("src",j.placeholder),c.one("appear",function(){if(!this.loaded){if(j.appear){var d=i.length;j.appear.call(b,d,j)}a("<img />").bind("load",function(){var d=c.attr("data-"+j.data_attribute);c.hide(),c.is("img")?c.attr("src",d):c.css("background-image","url('"+d+"')"),c[j.effect](j.effect_speed),b.loaded=!0;var e=a.grep(i,function(a){return!a.loaded});if(i=a(e),j.load){var f=i.length;j.load.call(b,f,j)}}).attr("src",c.attr("data-"+j.data_attribute))}}),0!==j.event.indexOf("scroll")&&c.bind(j.event,function(){b.loaded||c.trigger("appear")})}),e.bind("resize",function(){g()}),/(?:iphone|ipod|ipad).*os 5/gi.test(navigator.appVersion)&&e.bind("pageshow",function(b){b.originalEvent&&b.originalEvent.persisted&&i.each(function(){a(this).trigger("appear")})}),a(c).ready(function(){g()}),this},a.belowthefold=function(c,f){var g;return g=f.container===d||f.container===b?(b.innerHeight?b.innerHeight:e.height())+e.scrollTop():a(f.container).offset().top+a(f.container).height(),g<=a(c).offset().top-f.threshold},a.rightoffold=function(c,f){var g;return g=f.container===d||f.container===b?e.width()+e.scrollLeft():a(f.container).offset().left+a(f.container).width(),g<=a(c).offset().left-f.threshold},a.abovethetop=function(c,f){var g;return g=f.container===d||f.container===b?e.scrollTop():a(f.container).offset().top,g>=a(c).offset().top+f.threshold+a(c).height()},a.leftofbegin=function(c,f){var g;return g=f.container===d||f.container===b?e.scrollLeft():a(f.container).offset().left,g>=a(c).offset().left+f.threshold+a(c).width()},a.inviewport=function(b,c){return!(a.rightoffold(b,c)||a.leftofbegin(b,c)||a.belowthefold(b,c)||a.abovethetop(b,c))},a.extend(a.expr[":"],{"below-the-fold":function(b){return a.belowthefold(b,{threshold:0})},"above-the-top":function(b){return!a.belowthefold(b,{threshold:0})},"right-of-screen":function(b){return a.rightoffold(b,{threshold:0})},"left-of-screen":function(b){return!a.rightoffold(b,{threshold:0})},"in-viewport":function(b){return a.inviewport(b,{threshold:0})},"above-the-fold":function(b){return!a.belowthefold(b,{threshold:0})},"right-of-fold":function(b){return a.rightoffold(b,{threshold:0})},"left-of-fold":function(b){return!a.rightoffold(b,{threshold:0})}})}(jQuery,window,document);


$(document).ready(function(){

    
    // Set mobile sizes variables
	var phone = false, smart = false, tablet = false, norm = true, large = false;
    var wwidth = $(window).width();
	responsive();
	
	// Responsive - check screen sizes
	function responsive(){
		var ww = $(window).width();
		if ( ww < 481 ) 				{ phone = true, smart = false, tablet = false, norm = false, large = false, response = 'phone'; }
		if ( ww >= 481 && ww < 768 ) 	{ phone = true,  smart = true, tablet = false, norm = false, large = false, response = 'smart'; }
		if ( ww >= 768 && ww < 980 ) 	{ phone = false, smart = false, tablet = true, norm = false, large = false, response = 'tablet'; }
		if ( ww >= 980 && ww <= 1200 ) 	{ phone = false, smart = false, tablet = false, norm = true, large = false, response = 'norm'; }
		if ( ww >= 1200 ) 			    { phone = false, smart = false, tablet = false, norm = false, large = true, response = 'large'; }
		console.log(response);
	}

	// ========================================
	// Top menu mobile toggle
	// ========================================
    
    /*
   1. Move user menu below normal menu
   2. Add toggle button
   3. Add dropdown controls for each item with subitems
   4. Bind click actions to elements: toggle button, dropdown toggles
   5. Hide everything on bigger screens
   */
    
    // Prepare menus for mobile
    function responsive_init(){
        if ( !$('.mobile-menu').length ){
            $('.qa-header .qa-logo').after('<div class="mobile-menu"/>');
        }
        $('.qa-header .qa-nav-main').appendTo('.qa-header .mobile-menu');
        $('.qa-header .qa-search').appendTo('.qa-header .mobile-menu');
        $('.qa-header .qa-nav-user').appendTo('.qa-header .mobile-menu');

        $('.qa-header .qa-nav-main .dropdown-menu').hide();
        if ( !$('.qa-header .menu-toggle').length ){
            $('<a class="menu-toggle" href="#mobile-menu"><span class="icn icon-menu"></span><span class="icn icon-close-round"></span></a>').insertAfter('.qa-header .qa-logo');
        }
        //$('.qa-header .mobile-menu').hide();
    }
    
    // Bind click event on toggle button
    $('.qa-header').on('click', '.menu-toggle', function(e){
        e.preventDefault();
        $(this).toggleClass('menu-open');
        $('.qa-header .mobile-menu').toggleClass('menu-open');
    });
    
    function responsive_destroy(){
        $('.qa-header .qa-nav-user').prependTo('.qa-header');
        $('.qa-header .qa-search').prependTo('.qa-header');
        $('.qa-header .qa-nav-main').appendTo('.qa-header');
        $('.qa-header .qa-nav-main .dropdown-menu').attr('style',''); // Clean "display:none" from before
        $('.qa-header .mobile-menu').removeClass('menu-open');
        $('.qa-header .menu-toggle').removeClass('menu-open');
    }
    
	toggleMenu();
	function toggleMenu(){
		if (phone || smart){
			responsive_init();
		} else {
            responsive_destroy();
        }
	}

	$(window).resize(function() {
        // Fire only if width has changed (workaraund for a bug when mobile scrolling triggers resize event)
        if ( $(window).width() != wwidth ){
            responsive();
            toggleMenu();
        }
	});
    // Top menu mobile toggle END
    
    
    
    var premiumpay = $('.premiumpay-wrap');
    if (premiumpay.length){
        var selection = premiumpay.find('.paychoice label');
        selection.has('input:checked').addClass('checked');
        selection.on('click',function(){
            selection.removeClass('checked');
            $(this).addClass('checked');
        });
    }
    
    
    
	// mobile site
	var ismobile = ($("#agentIsMobile").length != 0);

	// anonymous user: teaser box is shown on question page!
	var anonymousUser = ($("#isAnonym").length != 0);
	
	// lazy load for lazy images
	$('.lazy').lazyload({
		effect: "fadeIn"
	});

	// tipsy wont work on all devices
	if(!ismobile)
	{
		$('.tooltip').tipsy( {gravity: 's', fade: false, offset: 5, html:true } );
		$('.tooltipS').tipsy( {gravity: 's', fade: false, offset: 5, html:true } );
		$('.tooltipN, qa-history-new-event-link').tipsy( {gravity: 'n', fade: false, offset: 5, html:true } );
		$('.tooltipW').tipsy( {gravity: 'w', fade: false, offset: 5, html:true } );
		$('.tooltipE').tipsy( {gravity: 'e', fade: false, offset: 5, html:true } );

		$('.qa-vote-one-button, .qa-vote-first-button, .qa-favorite-button, .qa-unfavorite-button').tipsy( {gravity: 's', fade: false, offset:0 } );
		$('.qa-form-light-button').tipsy( {gravity: 's', fade: true, offset:3 } ); // delayIn:800
		$('.qa-a-select-button').tipsy( {gravity: 's', fade: false, offset:5 } );
		
		$('.badge-title, .badge-bronze-medal, .badge-silver-medal, .badge-gold-medal, .badge-bronze, .badge-silver, .badge-gold').tipsy( {gravity: 's', fade: false, offset:0 } );
		
		$('.shareIT').tipsy( {gravity: 's', fade: false } );
		$('.sidebarBtn, .btngreen, .btnyellow').tipsy( {gravity: 's', offset:5 });

		$('.suggestVideoBtn, .oAQ_share').tipsy( {gravity: 's', offset:5 });

		// info on reward
		$('.rewardlist').tipsy( {gravity: 'e', offset:5 });
		$('.qa-userlist-acceptrate').tipsy( {gravity: 's', offset:5 });
		$('.qa-userlist-upvotes').tipsy( {gravity: 's', offset:5 });
		
		// live-box widget
		$('.liveBox-events a').tipsy( {gravity: 'e', fade: true, offset:5 });

		// admin who voted
		$('.qa-vote-count-net').tipsy();
		
		// expert XI info
		$('.qa-q-view-who-title, .qa-a-item-who-title').tipsy( {gravity: 's', offset:5 });
		$('.xavatar').tipsy( {gravity: 's', offset:5, html:true });
		
		// for plugin usermeta-points-ajax (mouse over username gives points data), ignore loggedin-username on top
		$(".qa-user-link").not($('.qa-logged-in-data').children()).mouseover(function() { // .nickname, .bestusers .qa-user-link
			var recentItem = $(this);
			var username = recentItem.text();
			if(typeof recentItem.attr('data-test') == 'undefined') {
				$.ajax({
					 type: "POST",
					 url: '/usermeta-points-ajax',
					 data: {ajax:username},
					 success: function(data) {
						//recentItem.attr('original-title', data);
						recentItem.attr('title', data);
						recentItem.tipsy( {gravity:'s', fade:true, html:true, offset:0 } );
						// check if mouse has already left username field
						if (recentItem.is(':hover')) {
							recentItem.tipsy('show');
						}
						else {
							recentItem.tipsy('hide');
						}
						// mark element as loaded
						recentItem.attr('data-test', 'loaded');
					 }
				});
			}
		}); 
		// user info (i)
		$(".qa-logged-in-points").mouseover(function() {
			var recentItem = $(this);
			var username = recentItem.attr("title");
			if(typeof recentItem.attr('data-test') == 'undefined') {
				$.ajax({
					 type: "POST",
					 url: '/usermeta-points-ajax',
					 data: {ajax:username},
					 success: function(data) {
						//recentItem.attr('original-title', data);
						recentItem.attr('title', data);
						recentItem.tipsy( {gravity: 'n', offset:5, html:true, fade:true });
						// check if mouse has already left username field
						if (recentItem.is(':hover')) {
							recentItem.tipsy('show');
						}
						else {
							recentItem.tipsy('hide');
						}
						// mark element as loaded
						recentItem.attr('data-test', 'loaded');
					 }
				});
			}
		}); 
		
		// open share links in new window
		$('.shfb,.shgp,.shtw').click( function(e) {
			e.preventDefault();
			window.open($(this).attr('href'),'','width=500,height=400');
		});
	} // end !ismobile
	
	// disable enter key for tags input
    $("#tags").keypress(function(e) {
		if (e.which == 13) { return false; }
		// count tags, if less than 2 tags do not submit form
		/*if( $("#tags").val().split(/\s+/).length-1 < 2) {
			return false;
		}
		else {
			// we have more than 2 tags, no other displaying, submit form (checks for more erros via js)
			$('form[name="ask"]').submit();
		}
		*/
    });

	// obsolete: registration form: must tick checkbox ***
	/*
	if ($("#nutzbed").length > 0){
		var regForm = $(".qa-form-tall-table").parent();
		regForm.submit(function(e) {
			if(!$('input[type=checkbox]:checked').length) {
				e.preventDefault();
				$("#nutzbed").parent().parent().css("background-color","#FF6691");
				alert("Prašom sutikti su mūsų naudojimosi taisyklėmis. Langelį pažymeti kabliuku.");
				window.scrollTo(0,50);
				return false;
			}
			return true;
		});
	}
	
	// obsolete: registration form, add text
	if ($('.qa-template-register').length > 0){
		// Specializacija
		// $('.qa-form-tall-label:contains("Specializacija:")').html('Specializacija (tik ekspertams):');
		$('input[name="field_7"]').attr('placeholder', 'tik ekspertams');
		// Internetinis puslapis
		// $('.qa-form-tall-label:contains("Internetinis puslapis:")').html('Internetinis puslapis (tik jeigu yra):');
		$('input[name="field_5"]').attr('placeholder', 'tik jeigu yra');
		// Kontaktai
		// $('.qa-form-tall-label:contains("Kontaktai:")').html('Kontaktai (el. paštas, telefonas):');
		$('input[name="field_6"]').attr('placeholder', 'el. paštas, telefonas');
	}
	*/
	
	// user must give at least 2 tags (only ask question page)
	if ($("#tags").length > 0){
		var regForm = $(".qa-form-tall-table").parent();
		regForm.submit(function(e) {
		    /*
			var tagsN = 0;
			// count tags
			var matches = $("#tags").val().match(/\b/g);
			if(matches) { tagsN = matches.length*0.5; }
			if(tagsN<2) {
				e.preventDefault();
				$("#tags").focus();
				$("#tags").css("background-color","#FFFFAA");
				alert("Nurodyk nuo 2 iki 5 raktažodžių!");
				return false;
			}
			*/
			
			// get content of ckeditor
			/*
			var editorContent = String(qa_ckeditor_content.getData());
			
			// check if there is a link to an external image, also check if line break follows <br /> or end of paragraph
			//if( editorContent.match(/(https?:\/\/\S+\.(?:jpg|png|gif|jpg<br|png<br|gif<br|jpg<\/p>|png<\/p>|gif<\/p>))\s+/) != null ) {
			if( editorContent.match(/\.(jpg|png|gif)\b/) != null ) {
				alert('Nuorodos į svetimas nuotraukos negalimos. Įdėti nuotrauką.');
				return false;
			}
			*/
			

			// warn if below 80 chars
			/*
			if( editorContent.length < 80 ) {
				alert('Tavo klausimo aprašymas per trumpas. Reikia daugiau duomenų.');
				// hightlight ckeditor
				$("#cke_content").css('background', '#FF5');
				// scroll up to ckeditor
				$('html, body').animate({scrollTop:$('#cke_content').position().top}, 'slow');
				return false;
			}
			*/

			// replace multiple question and explanation marks
			var titleText = $('input[name$="title"]').val().replace(/\?+/g,'?').replace(/\!+/g,'!');
			$('input[name$="title"]').val(titleText);

			return true;
		}); // end form submit
		
		// add tooltip to title input field (ask page)
		/*if($('.qa-template-ask #title').length > 0){
			$('#title').attr('title', 'pavyzdžiui: "Kaip reikia plauti indaplove ekologiskai?"');
			if(!ismobile) {
				$('#title').tipsy( {trigger: 'focus', gravity: 'w', fade: false, offset:0, html:true } );
				// tooltip for tags as well
				//$('#tags').attr('title', 'z. B. Geometrie, Kreis, Umfang');
				//$('#tags').tipsy( {trigger: 'focus', gravity: 'w', fade: false, offset:0, html:true } );
			}
		}
		*/
		
		// scroll to recent error on ask page if exists
		if ($('.qa-form-tall-error').length > 0){
			$('html, body').animate({scrollTop:$('.qa-form-tall-error').position().top}, 'slow');
		}
		
		var isIE11 = !!navigator.userAgent.match(/Trident\/7\./);
		
		/*
		// load jquery pretty tags script for question page, if not mobile, not working in IE, off-cookie not set
		if( !isIE11 && !ismobile && !(/MSIE (\d+\.\d+);/.test(navigator.userAgent)) && getCookie('gmf-autotags')!='false' ) {
			var tagrURL = '/tools/tags.min.js?v=0.1';
			var tagsArray = qa_tags_complete.split(',');
			tagsArray.sort();
			// as soon it is loaded
			$.getScript(tagrURL, function() {
				$('#tags').tagbox({
					// transform tags string into array (plugin needs array)
					url: tagsArray,
					separator: ' ',
				});
				// remove example q2a default tags
				$('#tag_examples_title').hide();
				$('#tag_complete_title').hide();
				$('#tag_hints').hide();
				// kill q2a bring-up tags function by killing the event
				$('#tags').attr('onkeyup',null);
				$('#tags').attr('onmouseup',null);
				$('#title').attr('onkeyup',null);
			});
		}
		*/
	}

	// point link of "meine updates" to user history ***
	$('a[href$="./updates"]:first').attr('href',  $(".qa-user-link:first").attr('href')+"?tab=history" );
	$('a[href$="../updates"]:first').attr('href',  $(".qa-user-link:first").attr('href')+"?tab=history" );

	// add notice to ask question (but not for mobiles)
	if( (window.location.pathname=="/ask") && !(/iPhone|iPod/i.test(navigator.userAgent))) {
		var ftwidth = $('form[name$="ask"] .qa-form-tall-table').css('width');
		$('<p style="width:'+ftwidth+';text-align:center;margin-top:5px;font-size:12px;">'+lang_can_edit_submit+'</p>').insertAfter('form[name$="ask"]');
	}
	
	if(!ismobile) {
		// teaser-dialog-box ask question (not for mobiles)
		$('#closeDiv').click(function () {
			$('#dialog-box').fadeOut('fast', function () {
				$(this).remove();
				setCookie('gmf-dialog-visible', 'false', 14);
			});
		});
		var moved=false;
		var showDialog = (getCookie("gmf-dialog-visible") == null);
		if(showDialog) {
			$(window).scroll(function () { 
				if(!moved) {
					$('#dialog-box').animate({ 
						bottom: "10px",
						right: "10px"
					}, 600 );
					moved = true;
				}
				if( $(window).scrollTop()== 0 ) {
					$('#dialog-box').animate({ 
						bottom: "-180px",
						right: "-180px"
					}, 600 );
					moved = false;
				}
			})
		}
		
		// if best-answer-selected exists then reposition star icon
		if($(".qa-a-item-selected").length > 0){
			// parent of qa-a-selected is qa-a-selection
			$(".qa-a-selected").parent().css('left', 'auto');
			$(".qa-a-selected").parent().css('right', '10px');
			$(".qa-a-selected").parent().css('top', '10px');
			// remove other buttons to chose best answer
			$(".qa-a-select-button").hide();
			
			// only for admin as star gets class qa-a-unselect-button
			if($(".qa-a-unselect-button").length > 0){
				// parent of qa-a-unselect-button is qa-a-selection
				$(".qa-a-unselect-button").parent().css('left', 'auto');
				$(".qa-a-unselect-button").parent().css('right', '10px');
				$(".qa-a-unselect-button").parent().css('top', '10px');
			}
		}
		
		// do not display vote button for anonymous users (as they cannot vote)
		// who can choose best answer, reposition best answer button
		if( anonymousUser && ($(".qa-a-select-button").length > 0) ) {
			$(".qa-vote-buttons-net, .qa-netvote-count").hide();
			$(".qa-a-select-button").css( {"position": "absolute", "top":"-107px", "left":"0"} );
		}
		
		// clicking on Login pops out loginbox
		$('.qa-nav-user-login a').click( function(ev){
			ev.preventDefault();
			$('#loginBox').slideToggle('fast');
		})
	}

	if(anonymousUser) {
		// hide flag-spam button as login is required
		$(".qa-form-light-button-flag").hide();
	}
	

	// now we can assign tipsy to question titles in q-list
	if(!ismobile) {
		$('.qa-q-item-title a span').tipsy( {gravity: 'n', fade: true, offset:10, delayIn:800, html:true } );
	}

	// cookie-option to globally uncheck email notify fields
	if(getCookie('gmf-notify-email')=='false') {
		$("input[name*='notify']").attr('checked',false);
	}
	
	// lightbox effect for images (overlay)
	if(!ismobile) {
		$(".entry-content img").click(function(){
			$("#lightbox-popup").fadeIn("slow");
			$("#lightbox-img").attr("src", $(this).attr("src"));
			// center vertical
			$("#lightbox-center").css("margin-top", ($(window).height() - $("#lightbox-center").height())/2  + 'px');
		});
		$("#lightbox-popup").click(function(){
			$("#lightbox-popup").fadeOut("fast");
		});
		// keylistener ESC to close Lightbox
		$(document).keyup(function(e) {
		  if (e.keyCode == 27) { 
				if( $("#lightbox-popup").is(":visible") ) {
					$("#lightbox-popup").fadeOut("fast");
				}
			}
		});
	}
	else { 
		// open images in new window on mobiles, no lightbox effect
		$(".entry-content img").each(function() {
			var a = $('<a/>').attr('href', this.src);
			$(this).addClass('image').wrap(a);
			//$('.entry-content img').parent('a').attr('target', '_blank');
		});
	}

	// remove height attr from img for proportional display (max-width is set by css)
	$('.entry-content img').each(function(){
		$(this).removeAttr('height');
	});

	if(ismobile) {
		// hide suggest-box for mobiles
		$(".suggestVideoBox").hide();
		// if mobile then remove "Beste Antwort" from select-button as it cannot be displayed on tiny star
		$('.qa-a-select-button').val('');
	}
	
	// add ga-tracking to special anchors
	// $('.qa-nav-main-custom-1 a').attr('onclick', '_gaq.push(["_trackEvent", "GMF-Menu", "Gute-Mathe-Videos"]);');
	
	// PREVENT caps lock on question title field
	$('.qa-template-ask #title').keypress(function(e) { 
		var s = String.fromCharCode( e.which );
		if ( s.toUpperCase() === s && s.toLowerCase() !== s && !e.shiftKey ) {
			alert(unescape(lang_write_correctly));
		}
	});
	// add span-id below #title on ask page
	if($('.qa-template-ask #title').length > 0){
		$('<span id="requireMoreChars" style="display:block;color:#FF9000;"></span>').insertAfter('#title');
	}
	// display necessary number of characters
	$('.qa-template-ask #title').keyup(function(e) { 
	   nchars = (25 - $("#title").val().length);
	   if(nchars<=0) {
	       $('#requireMoreChars').html('');
	   }
	   else {
	       $('#requireMoreChars').html(lang_missing_chars_1+' '+nchars+' '+lang_missing_chars_2);
	   }
	});
	
	// disable unvoting of vote-up
	$(".qa-voted-up-button").prop('disabled', true);

	// user history: remove background color from new-event-tr to indicate that it has been read
	$('#newevent .qa-history-item-title a').click( function() {
		$(this).parent().parent().parent().parent().parent().parent().parent().parent().css('background','transparent');
	});

	// embed editor tutorial on ask page
	// if askpage & nocookie set & not mobile then embed tutorial
	/*if( ($('.qa-template-ask #title').length > 0) && (getCookie('gmf-show-editor-tutorial') == null) && !ismobile ) {
		$('<div id="editortutorial">  <p id="hideeditortut">Tutorial nicht mehr anzeigen?</p> <iframe width="570" height="428" src="//www.youtube.com/embed/_vDs5j2JoBM?rel=0&vq=large&iv_load_policy=3" frameborder="0" allowfullscreen></iframe> </div>').insertBefore( $('.qa-template-ask tr:nth-child(4) .qa-form-tall-data') );

		$('#hideeditortut').click( function() {
			$('#editortutorial').hide();
			setCookie('gmf-show-editor-tutorial', 'false', 30);
		});
	}*/
	
	/* display shortlink if symbol in sharebox is clicked */
	$('.shlink').click( function(e) {
		e.preventDefault();
		// if not yet created
		if( $('.shlinktxt').length==0) {
			var ahref = $('.shlink').attr('href');
			$('<div class="shlinktxt"><input type="text" value="'+ahref.substring(7, ahref.length)+'"></input></div>').insertAfter('.shtw');
			$('.shlinktxt input').select();
		}
		else {
			$('.shlinktxt').toggle();
			$('.shlinktxt input').select();
		}
	});

	// adsense banner very left, only on question page
	// var bodyclass = $('body').attr('class').split(' ')[0];
	// if(anonymousUser && $(window).width()>1310 && bodyclass=='qa-template-question'){ // $('.qa-main').length>0 && 
	// $('.adholder-mid').length>0 makes sure the the adsense-script has been loaded
	if(anonymousUser && $(window).width()>1510 && $('.adholder-mid').length>0){
		// <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script> <!-- Klaustukai 160x600 Left --> <ins class="adsbygoogle" style="display:inline-block;width:160px;height:600px" data-ad-client="ca-pub-6679343814337183" data-ad-slot="7052313154"></ins> <script> (adsbygoogle = window.adsbygoogle || []).push({}); </script>
			$('.content-wrapper').prepend('<div class="adholder-left"><ins class="adsbygoogle" style="display:inline-block;width:160px;height:600px" data-ad-client="ca-pub-6679343814337183" data-ad-slot="7052313154"></ins></div>');
			(adsbygoogle = window.adsbygoogle || []).push({});
	}

	// un-reverse email in expert's contact field and link it
	$('.remail').each( function() {
		var oneway = $(this).text();
		var correctmail = oneway.split('').reverse().join('')
		$(this).html('<a href="mailto:'+correctmail+'">'+correctmail+'</a>');
	});
	
	// rename
	// $(".qa-nav-main-list li.qa-nav-main-user a").html("Nariai ir Ekspertai");
	
	if(!ismobile) {
		// hide old menu items
		$(".qa-nav-main-unanswered, .qa-nav-main-points").hide();
	}
	
}); // end document ready


/* URL parameter reader */
function getURLparameter( name ) 
{
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );
	if( results == null )
		return null;
	else
		return results[1];
}

/* COOKIE for teaser-dialog-Box */
function setCookie(name,value,days) {
	
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function getCookie(name) 
{
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}


/* QUESTION PAGE */
var qa_element_revealed=null;

function qa_toggle_element(elem)
{
	var e=elem ? document.getElementById(elem) : null;
	
	if (e && e.qa_disabled)
		e=null;
	
	if (e && (qa_element_revealed==e)) {
		qa_conceal(qa_element_revealed, 'form');
		qa_element_revealed=null;

	} else {
		if (qa_element_revealed)
			qa_conceal(qa_element_revealed, 'form');
	
		if (e) {
			if (e.qa_load && !e.qa_loaded) {
				e.qa_load();
				e.qa_loaded=true;
			}
			
			if (e.qa_show)
				e.qa_show();
			
			qa_reveal(e, 'form', function() {
				var t=$(e).offset().top;
				var h=$(e).height()+16;
				var wt=$(window).scrollTop();
				var wh=$(window).height();
				
				if ( (t<wt) || (t>(wt+wh)) )
					qa_scroll_page_to(t);
				else if ((t+h)>(wt+wh))
					qa_scroll_page_to(t+h-wh);

				if (e.qa_focus)
					e.qa_focus();
			});
		}
				
		qa_element_revealed=e;
	}
	
	return !(e||!elem); // failed to find item
}

function qa_submit_answer(questionid, elem)
{
	var params=qa_form_params('a_form');
	
	params.a_questionid=questionid;
	
	qa_ajax_post('answer', params,
		function(lines) {
			
			if (lines[0]=='1') {
				if (lines[1]<1) {
					var b=document.getElementById('q_doanswer');
					if (b)
						b.style.display='none';
				}
			
				var t=document.getElementById('a_list_title');
				qa_set_inner_html(t, 'a_list_title', lines[2]);
				qa_reveal(t, 'a_list_title');
				
				var e=document.createElement('div');
				e.innerHTML=lines.slice(3).join("\n");
				
				var c=e.firstChild;
				c.style.display='none';

				var l=document.getElementById('a_list');
				l.insertBefore(c, l.firstChild);
				
				var a=document.getElementById('anew');
				a.qa_disabled=true;
				
				qa_reveal(c, 'answer');
				qa_conceal(a, 'form');

			} else if (lines[0]=='0') {
				document.forms['a_form'].submit();
			
			} else {
				qa_ajax_error();
			}

		}
	);
	
	qa_show_waiting_after(elem, false);
	
	return false;
}

function qa_submit_comment(questionid, parentid, elem)
{
	var params=qa_form_params('c_form_'+parentid);

	params.c_questionid=questionid;
	params.c_parentid=parentid;
	
	qa_ajax_post('comment', params,
		function (lines) {

			if (lines[0]=='1') {
				var l=document.getElementById('c'+parentid+'_list');
				l.innerHTML=lines.slice(2).join("\n");
				l.style.display='';
				
				var a=document.getElementById('c'+parentid);
				a.qa_disabled=true;
				
				var c=document.getElementById(lines[1]); // id of comment
				if (c) {
					c.style.display='none';
					qa_reveal(c, 'comment');
				}
				
				qa_conceal(a, 'form');

			} else if (lines[0]=='0') {
				document.forms['c_form_'+parentid].submit();
			
			} else {
				qa_ajax_error();
			}

		}
	);
	
	qa_show_waiting_after(elem, false);
	
	return false;
}

function qa_answer_click(answerid, questionid, target)
{
	var params={};
	
	params.answerid=answerid;
	params.questionid=questionid;
	params.code=target.form.elements.code.value;
	params[target.name]=target.value;
	
	qa_ajax_post('click_a', params,
		function (lines) {
			if (lines[0]=='1') {
				qa_set_inner_html(document.getElementById('a_list_title'), 'a_list_title', lines[1]);

				var l=document.getElementById('a'+answerid);
				var h=lines.slice(2).join("\n");
				
				if (h.length)
					qa_set_outer_html(l, 'answer', h);
				else
					qa_conceal(l, 'answer');
			
			} else {
				target.form.elements.qa_click.value=target.name;
				target.form.submit();
			}
		}
	);
	
	qa_show_waiting_after(target, false);
	
	return false;
}

function qa_comment_click(commentid, questionid, parentid, target)
{
	var params={};
	
	params.commentid=commentid;
	params.questionid=questionid;
	params.parentid=parentid;
	params.code=target.form.elements.code.value;
	params[target.name]=target.value;
	
	qa_ajax_post('click_c', params,
		function (lines) {
			if (lines[0]=='1') {
				var l=document.getElementById('c'+commentid);
				var h=lines.slice(1).join("\n");
				
				if (h.length)
					qa_set_outer_html(l, 'comment', h)
				else
					qa_conceal(l, 'comment');
			
			} else {
				target.form.elements.qa_click.value=target.name;
				target.form.submit();
			}
		}
	);
	
	qa_show_waiting_after(target, false);
	
	return false;
}

function qa_show_comments(questionid, parentid, elem)
{
	var params={};
	
	params.c_questionid=questionid;
	params.c_parentid=parentid;
	
	console.log(params);
	console.log('----');
	
	qa_ajax_post('show_cs', params,
		function (lines) {
			console.log(lines);
			if (lines[0]=='1') {
				var l=document.getElementById('c'+parentid+'_list');
				l.innerHTML=lines.slice(1).join("\n");
				l.style.display='none';
				qa_reveal(l, 'comments');
			
			} else {
				qa_ajax_error();
			}
		}
	);
	
	qa_show_waiting_after(elem, true);
	
	return false;
}

function qa_form_params(formname)
{
	var es=document.forms[formname].elements;
	var params={};
	
	for (var i=0; i<es.length; i++) {
		var e=es[i];
		var t=(e.type || '').toLowerCase();
		
		if ( ((t!='checkbox') && (t!='radio')) || e.checked)
			params[e.name]=e.value;
	}
	
	return params;
}

function qa_scroll_page_to(scroll)
{
	$('html,body').animate({scrollTop: scroll}, 400);
}
/* END QUESTION PAGE */

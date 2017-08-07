// $.noConflict();

$(document).ready(function()
{
	
	var doc = document;
	var body = $( doc.body );
	function translateForm(language){
		formlang = language;
		var lang;
		if(translation[language] === undefined){
			lang = translation['en'];
		}else{
			lang = translation[language];
		}

		$('.card-number-label').text(lang['form']['card-number']);
		$('.card-cvc-label').text(lang['form']['card-cvc']);
		$('.card-holdername-label').text(lang['form']['card-holdername']);
		$('.card-expiry-label').text(lang['form']['card-expiry']);
		
		$('.amount-label').text(lang['form']['amount']);
		$('.currency-label').text(lang['form']['currency']);
		$('.submit-button').text(lang['form']['submit-button']);
		$('#tooltip').attr('title', lang['form']['tooltip']);
	}

	$('.card-number').keyup(function() {
		var detector = new BrandDetection();
		var brand = detector.detect($('.card-number').val());
		$('.card-number')[0].className = $('.card-number')[0].className.replace(/paymill-card-number-.*/g, '');
		if (brand !== 'unknown') {
			$('#card-number').addClass('paymill-card-number-' + brand);

			if (!detector.validate($('.card-number').val())) {
				$('#card-number').addClass('paymill-card-number-grayscale');
			}

			if (brand !== 'maestro') {
				VALIDATE_CVC = true;
			} else {
				VALIDATE_CVC = false;
			}
		}
	});

	$('.card-expiry').keyup(function() {
		if ( /^\d\d$/.test( $('.card-expiry').val() ) ) {
			text = $('.card-expiry').val();
			$('.card-expiry').val(text += '/');
		}
	});


	function PaymillResponseHandler(error, result) {
		if (error) {
			$('.payment_errors').text(error.apierror);
			$('.payment_errors').css('display','inline-block');
		} else {
			$('.payment_errors').css('display','none');
			$('.payment_errors').text('');
			var form = $('#payment-form');
			// Token
			var token = result.token;
			form.append('<input type="hidden" name="paymillToken" value="'+token+'"/>');
			form.get(0).submit();
		}
		$('.submit-button').removeAttr('disabled');
	}

	function validate() {
		var iban = new Iban();
		if ('' === $('.elv-holdername').val()) {
			$('.payment_errors').text(translation[formlang]['error']['invalid-elv-holdername']);
			$('.payment_errors').css('display', 'inline-block');
			$('.submit-button').removeAttr('disabled');
			return false;
		}

		if (isSepa()) {
			if (!iban.validate($('.elv-account').val())) {
				$('.payment_errors').text(translation[formlang]['error']['invalid-elv-iban']);
				$('.payment_errors').css('display', 'inline-block');
				$('.submit-button').removeAttr('disabled');
				return false;
			}
			if ($('.elv-bankcode').val().length !== 9 && $('.elv-bankcode').val().length !== 11) {
				$('.payment_errors').text(translation[formlang]['error']['invalid-elv-bic']);
				$('.payment_errors').css('display', 'inline-block');
				$('.submit-button').removeAttr('disabled');
				return false;
			}
		} else {
			if (!paymill.validateAccountNumber($('.elv-account').val()) || $('.elv-bankcode').val().length > 10) {
				$('.payment_errors').text(translation[formlang]['error']['invalid-elv-accountnumber']);
				$('.payment_errors').css('display', 'inline-block');
				$('.submit-button').removeAttr('disabled');
				return false;
			}
			if (!paymill.validateBankCode($('.elv-bankcode').val())) {
				$('.payment_errors').text(translation[formlang]['error']['invalid-elv-bankcode']);
				$('.payment_errors').css('display', 'inline-block');
				$('.submit-button').removeAttr('disabled');
				return false;
			}
		}
		return true;
	}

	function isSepa() {
		var reg = new RegExp(/^\D{2}/);
		return reg.test($('.elv-account').val());
	}

	$('#payment-form').submit(function (event) 
	{
		$('.submit-button').attr('disabled', 'disabled');

		if (false === paymill.validateHolder($('.card-holdername').val())) {
			$('.payment_errors').text(translation[formlang]['error']['invalid-card-holdername']);
			$('.payment_errors').css('display','inline-block');
			$('.submit-button').removeAttr('disabled');
			return false;
		}
		if ((false === paymill.validateCvc($('.card-cvc').val()))) {
			if(VALIDATE_CVC){
				$('.payment_errors').text(translation[formlang]['error']['invalid-card-cvc']);
				$('.payment_errors').css('display','inline-block');
				$('.submit-button').removeAttr('disabled');
				return false;
			} else {
				$('.card-cvc').val('000');
			}
		}
		if (false === paymill.validateCardNumber($('.card-number').val())) {
			$('.payment_errors').text(translation[formlang]['error']['invalid-card-number']);
			$('.payment_errors').css('display','inline-block');
			$('.submit-button').removeAttr('disabled');
			return false;
		}
		var expiry = $('.card-expiry').val();
		expiry = expiry.split('/');
		if(expiry[1] && (expiry[1].length <= 2)){
			expiry[1] = '20'+expiry[1];
		}
		if (false === paymill.validateExpiry(expiry[0], expiry[1])) {
			$('.payment_errors').text(translation[formlang]['error']['invalid-card-expiry-date']);
			$('.payment_errors').css('display','inline-block');
			$('.submit-button').removeAttr('disabled');
			return false;
		}
		var params = {
			amount_int:     parseInt($('.amount').val().replace(/[\.,]/, '.') * 100),  // E.g. '15' for 0.15 Eur
			currency:       $('.currency').val(),    // ISO 4217 e.g. 'EUR'
			number:         $('.card-number').val(),
			exp_month:      expiry[0],
			exp_year:       expiry[1],
			cvc:            $('.card-cvc').val(),
			cardholder:     $('.card-holdername').val()
		};

		paymill.createToken(params, PaymillResponseHandler);
		return false;
	});

	$('#language_switch').click(function(){
		var language = formlang;
		var newimg;

		if(formlang === 'en'){
			newimg = paymillroot+'paymill/image/gb.png';
			language = 'de';
		} else {
			newimg = paymillroot+'paymill/image/de.png';
			language = 'en';
		}

		$(this).attr('src', newimg);
		translateForm(language);
	});

	translateForm(formlang);
});

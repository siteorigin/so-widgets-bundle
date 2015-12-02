jQuery(function ($) {

  var $contactForms = $('form.sow-contact-form');
  // Check if there are any recaptcha placeholders.
  var useRecaptcha = $contactForms.toArray().some(function(form){
    return $(form).find('div').hasClass('sow-recaptcha');
  });

  if(useRecaptcha) {
    if (window.recaptcha) {
      SiteOriginContactForm.init($, useRecaptcha);
    } else {
      // Load the recaptcha API
      var apiUrl = 'https://www.google.com/recaptcha/api.js?onload=soContactFormInitialize&render=explicit';
      var script = $('<script type="text/javascript" src="' + apiUrl + '" async defer>');
      $('body').append(script);
    }
  } else {
    SiteOriginContactForm.init($, useRecaptcha);
  }
});

function soContactFormInitialize() {
  SiteOriginContactForm.init(window.jQuery, true);
}

var SiteOriginContactForm = {
  init: function ($, useRecaptcha) {
    var $contactForms = $('form.sow-contact-form');
    $contactForms.each(function () {
      var $el = $(this);
      if(useRecaptcha) {
        // Render recaptcha
        var $recaptchaDiv = $el.find('.sow-recaptcha');
        if ($recaptchaDiv.length) {
          var sitekey = $recaptchaDiv.data('sitekey');
          grecaptcha.render($recaptchaDiv.get(0),
            {
              'sitekey': sitekey,
              'callback': function(response) {
                  // Enable the submit button once we have a response from recaptcha.
                  $(this).find('.sow-submit-wrapper > input.sow-submit').prop('disabled', false);
              }.bind(this),
            }
          );
        }
      }

      // Disable the submit button on click to avoid multiple submits.
      var $submitButton = $el.find('.sow-submit-wrapper > input.sow-submit');
      $submitButton.click(function () {
        $submitButton.prop('disabled', true);
        //Ensure the form still submits
        $el.submit();
      });
    });
  },
};
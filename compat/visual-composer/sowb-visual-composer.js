/* globals jQuery, sowbForms */
var sowbForms = window.sowbForms || {};

sowbForms.setupVcWidgetForm = function() {
	if ( typeof vc !== 'undefined') {
	    var sowbEscapeParam = function (value) {
            if (_.isUndefined(value) || _.isNull(value) || !value.toString) {
                return '';
            }
            return _.escape(value.toString()).replace(/\[/g, '&#91;').replace(/\]/g, '&#93;');
        };
	    var sowbUnescapeParam = function (value) {
            return _.unescape(value).replace(/&#91;/g, '[').replace(/&#93;/g, ']');
        };
	    if ( typeof vc.ShortcodesBuilder !== 'undefined') {
            vc.ShortcodesBuilder.prototype.escapeParam = sowbEscapeParam;

            vc.ShortcodesBuilder.prototype.unescapeParam = sowbUnescapeParam;
        }
        if ( typeof vc.Storage !== 'undefined') {

	        var parseContent = vc.Storage.prototype.parseContent;
	        var storageCreateShortcodeString = vc.Storage.prototype.storageCreateShortcodeString;
            var escapeParam = vc.Storage.prototype.escapeParam;
            var unescapeParam = vc.Storage.prototype.unescapeParam;

            vc.Storage.prototype.parseContent = function (data, content, parent) {
                var isSowbWidget = /^\[siteorigin_widget[^\]]*\]/.test(content);
                if ( isSowbWidget ) {
                    vc.Storage.prototype.escapeParam = sowbEscapeParam;
                    vc.Storage.prototype.unescapeParam = sowbUnescapeParam;
                }

                var parsed = parseContent.apply(this, [data, content, parent]);

                if ( isSowbWidget ) {
                    vc.Storage.prototype.escapeParam = escapeParam;
                    vc.Storage.prototype.unescapeParam = unescapeParam;
                }

                return parsed;
            };

            vc.Storage.prototype.storageCreateShortcodeString = function (model) {
                var isSowbWidget = model.get('shortcode') === 'siteorigin_widget';
                if ( isSowbWidget ) {
                    vc.Storage.prototype.escapeParam = sowbEscapeParam;
                    vc.Storage.prototype.unescapeParam = sowbUnescapeParam;
                }

                var scString = storageCreateShortcodeString.apply(this, [model]);

                if ( isSowbWidget ) {
                    vc.Storage.prototype.escapeParam = escapeParam;
                    vc.Storage.prototype.unescapeParam = unescapeParam;
                }

                return scString;
            };
        }

    }
	
};

jQuery(function ($) {
	sowbForms.setupVcWidgetForm();
});

window.sowbForms = sowbForms;

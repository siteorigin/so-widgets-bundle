/* globals jQuery, sowb */

var sowb = window.sowb || {};

sowb.setupVideoPlayer = function() {
	var $ = jQuery;
	$('video.sow-video-widget').mediaelementplayer();
};

jQuery(function ($) {
	sowb.setupVideoPlayer();
});

window.sowb = sowb;

/*
 * WPtouch 1.9.x -The WPtouch Core JS File
 */

var $wpt = jQuery.noConflict();

if ( ( navigator.platform == 'iPhone' || navigator.platform == 'iPod' ) && typeof orientation != 'undefined' ) { 
	var touchStartOrClick = 'touchstart'; 
} else {
	var touchStartOrClick = 'click'; 
};

/* Try to get out of frames! */
if ( window.top != window.self ) { 
	window.top.location = self.location.href
}

$wpt.fn.wptouchFadeToggle = function( speed, easing, callback ) { 
	return this.animate( {opacity: 'toggle'}, speed, easing, callback ); 
};

function wptouch_switch_confirmation( e )
{
	if( document.cookie && document.cookie.indexOf( 'wptouch_switch_toggle' ) > -1 )
	{ // just switch
		$wpt( '#switch span' ).removeClass( 'active' );
		$wpt( '.off' ).addClass( 'active' );
	}
	else
	{ // ask first
		if ( confirm( touch_skin_switch_confirm_text ) )
		{
			$wpt( '#switch span' ).removeClass( 'active' );
			$wpt( '.off' ).addClass( 'active' );
		}
		else
		{
			e.preventDefault();
			e.stopImmediatePropagation();
			return false;
		}
	}
	return true;
}

$wpt(function() {
		var tabContainers = $wpt( '#menu-head > ul' );
		$wpt( '#tabnav a' ).bind(touchStartOrClick, function () {
				tabContainers.hide().filter( this.hash ).show();
		$wpt( '#tabnav a' ).removeClass( 'selected' );
		$wpt( this ).addClass( 'selected' );
				return false;
		}).filter( ':first' ).trigger( touchStartOrClick );
});

function bnc_showhide_coms_toggle() {
	$wpt( '#commentlist' ).wptouchFadeToggle( 350 );
	$wpt( 'img#com-arrow' ).toggleClass( 'com-arrow-down' );
	$wpt( 'h3#com-head' ).toggleClass( 'comhead-open' );
}
	
function doWPtouchReady() {

	$wpt( '#headerbar-menu div' ).bind( touchStartOrClick, function( e ){
		var type = $wpt( this ).attr( 'rel' );
		var active_type = $wpt( '#wptouch-menu .wptouch-menu-inner:visible' ).length > 0 ? $wpt( '#wptouch-menu .wptouch-menu-inner:visible' ).attr( 'rel' ) : '';
		console.log( type, active_type );
		$wpt( '#wptouch-menu .wptouch-menu-inner' ).hide();
		$wpt( '#wptouch-menu .wptouch-menu-inner[rel=' + type +']' ).show();
		if( active_type == type || active_type == '' )
		{
			$wpt( '#wptouch-menu' ).wptouchFadeToggle( 350 );
		}
		$wpt( '#headerbar-menu div' ).removeClass( 'open' );
		if( active_type != type )
		{
			$wpt( this ).addClass( 'open' );
		}
	});

	$wpt( 'a#searchopen, #wptouch-search-inner a' ).click( function(){
		$wpt( '#wptouch-search' ).wptouchFadeToggle( 350 );
		$wpt( '#s' ).focus();		
	});

	/* add dynamic automatic video resizing via fitVids */

	var videoSelectors = [
		"iframe[src^='http://player.vimeo.com']",
		"iframe[src^='http://www.youtube.com']",
		"iframe[src^='http://www.kickstarter.com']",
		"object",
		"embed",
		"video"
	];
	
	var allVideos = $wpt( '.post' ).find( videoSelectors.join(',') );
	
	$wpt( allVideos ).each( function(){ 
		$wpt( this ).unwrap().addClass( 'wptouch-videos' ).parentsUntil( '.content', 'div:not(.fluid-width-video-wrapper), span' ).removeAttr( 'width' ).removeAttr( 'height' ).removeAttr( 'style' );
	});

	$wpt( '.post' ).fitVids();

	$wpt( document ).on( touchStartOrClick, '.post-arrow', function( e ){
		$wpt( this ).toggleClass( 'post-arrow-down' );
		$wpt( this ).parents( '.post' ).find( '.mainentry' ).wptouchFadeToggle(500);
	});

	$wpt( '#switch a.off' ).click( function( e )
	{
		return wptouch_switch_confirmation( e );
	});
}

$wpt( document ).ready( function() { doWPtouchReady(); } );


/*global jQuery */
/*! 
* FitVids 1.0
*
* Copyright 2011, Chris Coyier - http://css-tricks.com + Dave Rupert - http://daverupert.com
* Credit to Thierry Koblentz - http://www.alistapart.com/articles/creating-intrinsic-ratios-for-video/
* Released under the WTFPL license - http://sam.zoy.org/wtfpl/
*
* Date: Thu Sept 01 18:00:00 2011 -0500
*
* Modified by BraveNewCode for WPtouch Pro
*/

(function( $ ) {
$.fn.fitVids = function( options ) {
	var settings = { customSelector: null }

	var div = document.createElement('div'),
			ref = document.getElementsByTagName('base')[0] || document.getElementsByTagName('script')[0];

	div.className = 'fit-vids-style';
	div.innerHTML = '&shy;<style>\
		.fluid-width-video-wrapper {\
			 width: 100%;\
			 position: relative;\
			 padding: 0;\
		}\
		\
		.fluid-width-video-wrapper *{\
			 position: absolute;\
			 top: 0;\
			 left: 0;\
			 width: 100%;\
			 height: 100%;\
		}\
	</style>';

	ref.parentNode.insertBefore(div,ref);

	if ( options ) {
		$.extend( settings, options );
	}
	
	return this.each(function(){
		var selectors = [
			"iframe[src^='http://player.vimeo']", 
			"iframe[src^='http://www.youtube']", 
			"iframe[src^='http://www.kickstarter']",
			"object", 
			"embed",
			"video"
		];

		if (settings.customSelector) {
			selectors.push(settings.customSelector);
		}

		var $allVideos = $(this).find(selectors.join(','));

		$allVideos.each(function()
		{
			var $this = $(this);

			if (this.tagName.toLowerCase() == 'embed' && $this.parent('object').length || $this.parent('.fluid-width-video-wrapper').length) { return; }
			var height = $this.height(), aspectRatio = height / $this.width();

			$this.wrap('<div class="fluid-width-video-wrapper"></div>').parent('.fluid-width-video-wrapper').css('padding-top', (aspectRatio * 100)+"%");
			$this.removeAttr('height').removeAttr('width');
		});
	});
}
})( jQuery );
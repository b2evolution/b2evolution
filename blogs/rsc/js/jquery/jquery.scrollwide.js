/**
 * jQuery Wide Scroll
 * ---------------------------------------------------------------------------------
 *
 * jQuery Wide Scroll is a plugin that generates a customizable horizontal scrolling.
 *
 * @version 1.0
 * @author  Yuriy Bakhtin
 *
 * Usage with default values:
 * ---------------------------------------------------------------------------------
 * $('#div').scrollWide();
 *
 * <div id="div"></div>
 *
 * Usage with custom values:
 * ---------------------------------------------------------------------------------
 * 
 * $('#div').scrollWide( {
 *   scroll_step: 80, // Step of scrolling in percents of the display width 1%-100%
 *   scroll_time: 500, // Time to scroll one step (ms)
 *   width: 20, // Width of the scroll controls
 *   margin: 5, // Space between wide div and the scroll controls
 *   class_name: 'wscontrol',
 *   class_name_over: 'wsover',
 *   class_name_click: 'wsclick',
 *   class_name_left: 'wsleft',
 *   class_name_right: 'wsright',
* } );
 */

;(function($) {

	$.fn.scrollWide = function( settings )
	{
		if( this.length == 0 )
		{
			return;
		}
		else if( this.length > 1 )
		{
			return this.each( function()
			{
				$.fn.scrollWide.apply( $( this ), [settings] );
			} );
		}

		var opt = $.extend( {}, $.fn.scrollWide.defaults, settings ),
			$this = $( this ),
			id    = this.attr( 'id' );

		if( !this.HasScrollBarHorizontal() )
		{	// No horizontal scrollbar
			return $this;
		}

		if( id === undefined )
		{
			id = 'scrollWide-' + $this.index();
			$this.attr( 'id', id );
		}

		$this.data( 'options', opt );

		initialize( $this );

		return $this;
	};

	$.fn.HasScrollBarHorizontal = function()
	{
		var _elm = $(this)[0];
		var _hasScrollBar = false;
		if( $(this).width() < _elm.scrollWidth ) /* ( _elm.clientHeight < _elm.scrollHeight ) - Vertical Scroll Bar */
		{
			_hasScrollBar = true;
		}
		return _hasScrollBar;
	}

	function initialize( obj )
	{
		var options = obj.data('options'),
			obj_orig_width = obj.width();

		obj.css( {
			width: obj_orig_width - options.width - options.margin,
			marginRight: options.width + options.margin + 2
		} );

		var scroll_right = create_control( obj, 'right' );
		var scroll_left = create_control( obj, 'left' );

		obj.bind( 'scroll', function()
		{	// Do these actions when main div is scrolling
			var maxScrollLeft = this.scrollWidth - this.clientWidth;
			if( $( this ).scrollLeft() == maxScrollLeft )
			{	// Hide right scroll control if scrollbar is located in the right position
				scroll_right.hide();
			}
			else
			{	// Show right scroll control
				scroll_right.show();
			}

			if( $( this ).scrollLeft() == 0 )
			{	// Hide left scroll control if scrollbar is located in the left position
				scroll_left.hide();
				obj.css( 'marginLeft', '0' );
			}
			else
			{	// Show left scroll control
				scroll_left.show();
				obj.css( { // Move main div to clear a space for the left scroll control
					position: 'relative',
					marginLeft: options.width + options.margin + 2
				} );
			}

			if( scroll_right.is(':visible') && scroll_left.is(':visible') )
			{	// When left & right scroll controls are visible
				obj.css( 'width', obj_orig_width - 2 * ( options.width + options.margin ) - 2 );
			}
			else
			{	// Only one control is visible
				obj.css( 'width', obj_orig_width - options.width - options.margin - 1 );
			}
		} );

		$( [ scroll_right.get(0), scroll_left.get(0) ] ).bind( 'mouseover', function()
		{	// Change style of the controls elements on mouseover event
			$( '.' + options.class_name ).removeClass( options.class_name_over );
			$( this ).addClass( options.class_name_over );
			$( '.' + options.class_name ).removeClass( options.class_name_click );
		} );

		$( [ scroll_right.get(0), scroll_left.get(0) ] ).bind( 'mouseout', function()
		{	// Change style of the controls elements on mouseout event
			$( this ).removeClass( options.class_name_over );
			$( '.' + options.class_name ).removeClass( options.class_name_click );
		} );

		$( [ scroll_right.get(0), scroll_left.get(0) ] ).bind( 'mousedown mouseup', function()
		{	// Change style of the controls elements on mousedown & mouseup events
			$( this ).toggleClass( options.class_name_click );
		} );

		scroll_right.bind( 'click', function()
		{	// Bind action to scroll to the right
			var scroll_value = obj.scrollLeft() + Math.floor( obj.width() * options.scroll_step / 100 );
			obj.animate( {scrollLeft: scroll_value + 'px'}, options.scroll_time );
		} );

		scroll_left.bind( 'click', function()
		{	// Bind action to scroll to the left
			var scroll_value = obj.scrollLeft() - Math.floor( obj.width() * options.scroll_step / 100 );
			obj.animate( { scrollLeft: scroll_value + 'px' }, options.scroll_time );
		} );
	}

	function create_control( obj, type )
	{
		var options = obj.data('options'),
			control_id = obj.attr( 'id' ) + '-' + type;

		var control_class_name = options.class_name_left;
		if( type == 'right' )
		{
			control_class_name = options.class_name_right;
		}

		// Insert new control element
		obj.after( '<div id="' + control_id + '" class="' + options.class_name + ' ' + control_class_name + '"></div>' );

		var scroll_control_obj = $( '#' + control_id );

		var control_css_left = obj.offset().left;
		if( type == 'right' )
		{
			control_css_left += obj.outerWidth() + options.margin;
		}
		else
		{	// left control
			if( $(this).scrollLeft() == 0 )
			{	// Hide left control
				scroll_control_obj.hide();
			}
		}

		scroll_control_obj.css( { // Set position and sizes for new created scroll control element
			position: 'absolute',
			left: control_css_left,
			top: obj.offset().top,
			width: options.width,
			height: obj.outerHeight() - 2
		} );

		return scroll_control_obj;
	}

	function debug( message )
	{
		if( window.console && window.console.log )
		{
			window.console.log( message );
		}
	};

	// Default settings
	$.fn.scrollWide.defaults = {
		scroll_step: 80, // Step of scrolling in percents of the display width 1%-100%
		scroll_time: 500, // Time to scroll one step (ms)
		width: 20, // Width of the scroll controls
		margin: 5, // Space between wide div and the scroll controls
		class_name: 'wscontrol',
		class_name_over: 'wsover',
		class_name_click: 'wsclick',
		class_name_left: 'wsleft',
		class_name_right: 'wsright',
	};

})(jQuery);


// Initialize for div.wide_scroll:
jQuery( document ).ready( function()
{
	jQuery( 'div.wide_scroll' ).scrollWide( { scroll_time: 100 } );
} )
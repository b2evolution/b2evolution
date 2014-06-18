/**
 * Switch width of page wrapper
 *
 * @param object Object of current switcher
 * @param string Width value in percents or pixels
 * @param string Cookie name
 * @param string Cookie path
 */
function switch_width( obj_switcher, width_value, cookie_name, cookie_path )
{
	if( jQuery( obj_switcher ).hasClass( 'roundbutton_selected' ) )
	{	// Current switcher is already selected now, Exit here
		return false;
	}

	var current_width_value = jQuery( '#wrapper' )[0].style.width;
	var actual_width_value = width_value;

	if( width_value == '100%' )
	{	// We need in this modification because only FF can animates from % to px (All other browsers animate only from px to px sizes)
		width_value = ( jQuery( 'body' ).width() - 10 ) + 'px';
	}

	if( current_width_value == '100%' )
	{	// If cuurent width in percents we should convert it to pixels and then after 100ms we can change it to new required width size(px)
		jQuery( '#wrapper' ).css( 'width', jQuery( '#wrapper' ).css( 'width' ) );
		setTimeout( function()
			{
				jQuery( '#wrapper' ).css( 'width', width_value );
			}, 100 );
	}
	else
	{	// If current width in pixels is all good we can play CSS animation right now without any delay and convetation
		jQuery( '#wrapper' ).css( 'width', width_value );
	}

	if( actual_width_value == '100%' )
	{	// If actual width in percents we should revert the width of wrapper to percent format( because we use only pixel sizes during css animation )
		setTimeout( function()
			{
				jQuery( '#wrapper' ).css( 'width', actual_width_value );
			}, 1000 );
	}

	// Mark current switcher as selected
	jQuery( '#width_switcher a' ).removeClass( 'roundbutton_selected' );
	jQuery( obj_switcher ).addClass( 'roundbutton_selected' );

	// Set cookie for 10 years
	var date = new Date();
	date.setTime( date.getTime() + ( 10*365*24*60*60*1000 ) );
	document.cookie = cookie_name + '=' + actual_width_value +
	'; expires=' + date.toGMTString() +
	'; path=' + cookie_path;
}

jQuery( document ).ready( function()
{
	// Display width switcher only when JavaScript is enabled
	jQuery( '#width_switcher' ).show();

	if( jQuery( '#width_switcher' ).length > 0 )
	{	// If skin has a width switcher

		function change_wrapper_size()
		{	// If window width less than 1000px we should hide the width switcher
			if( jQuery( 'body' ).width() <= 1000 )
			{	// Fit to fixed width and hide the switcher
				jQuery( '#width_switcher a:first' ).click();
				jQuery( '#width_switcher' ).hide();
			}
			else
			{	// Show the switcher
				jQuery( '#width_switcher' ).show();
			}
		}

		change_wrapper_size();
		jQuery( window ).resize( function() { change_wrapper_size(); } );
	}
} );
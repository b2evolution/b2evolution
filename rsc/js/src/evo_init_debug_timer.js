/**
 * This file initialize Debug
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */

(function($){
	jQuery( "table.debug_timer th" ).on( "click", function(event) {
		var table = jQuery(this).closest( "table.debug_timer" );
		if( table.data( "clicked_once" ) ) return; else table.data( "clicked_once", true );
		jQuery( "tbody:eq(0) tr:last", table ).remove();
		jQuery( "tbody:eq(1) tr", table ).appendTo( jQuery( "tbody:eq(0)", table ) );
		// click for tablesorter:
		table.tablesorter();
		jQuery(event.currentTarget).click();
	});
})(jQuery);
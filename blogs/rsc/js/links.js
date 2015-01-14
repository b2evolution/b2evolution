/**
 * This file implements links specific Javascript functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author attila: Attila Simo
 *
 * @version $Id: $
 */

function evo_display_position_onchange( selectInput, url, crumb )
{
	var oThis = selectInput;
	var new_position = selectInput.value;
	jQuery.get( url + 'async.php?action=set_object_link_position&link_ID=' + selectInput.id.substr(17) + '&link_position=' + new_position + '&crumb_link=' + crumb, {
	}, function(r, status) {
		r = ajax_debug_clear( r );
		if( r == "OK" ) {
			evoFadeSuccess( jQuery(oThis.form).closest('tr') );
			jQuery(oThis.form).closest('td').removeClass('error');
			if( new_position == 'albumart')
			{ // Position "Album Art" can be used only by one link
				jQuery( 'select[name=link_position][id!=' + selectInput.id + '] option[value=albumart]:selected' ).each( function()
				{ // Replace previous position with "Inline"
					jQuery( this ).parent().val( 'aftermore' );
					evoFadeSuccess( jQuery( this ).closest('tr') );
				} );
			}
		} else {
			jQuery(oThis).val(r);
			evoFadeFailure( jQuery(oThis.form).closest('tr') );
			jQuery(oThis.form).closest('td').addClass('error');
		}
	} );
	return false;
}


/**
 * Insert an image tag into the post ( example: [image:123:caption text] )
 *
 * @param integer File ID
 * @param string Caption text
 */
function insert_image_link( link_ID, caption )
{
	var b2evoCanvas = window.parent.document.getElementById('itemform_post_content');
	if( b2evoCanvas != null )
	{	// Canvas exists
		var insert_tag = '[image:' + link_ID + ':' + caption + ']';
		// Insert an image tag
		textarea_wrap_selection( b2evoCanvas, insert_tag, '', 0, window.parent.document );

		var $position_selector = jQuery( '#display_position_' + link_ID );
		if( $position_selector.length != 0 )
		{	// Change the position to 'Inline'
			if( $position_selector.val() != 'inline' )
			{
				$position_selector.val( 'inline' ).change();
			}
		}
	}
}


/**
 * Replace the inline image placeholders when file is unlinked from item
 *
 * @param integer Link ID
 */
function item_unlink( link_ID )
{
	var b2evoCanvas = window.parent.document.getElementById( 'itemform_post_content' );
	if( b2evoCanvas != null )
	{ // Canvas exists
		var regexp = new RegExp( '\\\[image:' + link_ID + ':?[^\\\]]*\\\]', 'ig' );
		textarea_str_replace( b2evoCanvas, regexp, '', window.parent.document );
	}
}
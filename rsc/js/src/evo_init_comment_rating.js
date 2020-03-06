/**
 * This file initialize Comment rating
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */

jQuery( document ).ready( function()
{
	if( typeof( evo_comment_rating_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	// This will only be run once when the document is ready. This will not render the
	// star ratings for AJAX forms:
	evo_render_star_rating();
} );

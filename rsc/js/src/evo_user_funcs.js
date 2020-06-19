/**
 * This file initialize JS for User
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery
 */
jQuery( document ).ready( function()
{
	if( typeof( evo_user_funcs_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}
	
	var config = evo_user_funcs_config;

	/**
	 * Init autocomplete event for Specific criteria input
	 */
	window.userfield_criteria_autocomplete = function userfield_criteria_autocomplete( obj_this )
		{
			if( obj_this.find( 'option:selected[rel=suggest]' ).length > 0 )
			{	// Selected field type can be suggested with values
				var field_type_id = obj_this.val();
				obj_this.nextAll( 'input' ).first().autocomplete({
					source: function(request, response) {
						jQuery.getJSON( htsrv_url + 'anon_async.php?action=get_user_field_autocomplete', {
							term: request.term, attr_id: field_type_id
						}, response);
					},
				});
			}
			else
			{	// Destroy autocomplete event from previous binding
				obj_this.nextAll( 'input' ).first().autocomplete().autocomplete( 'destroy' );
			}
		};

	jQuery( document ).on( 'change', 'select[id^=criteria_type]', function()
		{
			window.userfield_criteria_autocomplete( jQuery( this ) );
		} );

	for(var c = 0; c < jQuery( 'select[id^=criteria_type]' ).length; c++ )
	{	// Bind autocomplete event for each Specific criteria
		window.userfield_criteria_autocomplete( jQuery( 'select[id^=criteria_type]:eq(' + c + ')' ) );
	}

	if( config.can_edit_user_level )
	{	// If user can edit the users - Init js to edit user level by AJAX
	
		jQuery('.user_level_edit').each( function()
		{
			if( jQuery( this ).find( 'a' ).length == 0 )
			{
				jQuery( this ).removeClass( 'user_level_edit' );
			}
		} );
	}

	/**
	 * Merge two users from duplicated users list
	 */
	window.merge_duplicated_users = function merge_duplicated_users( link_obj )
		{
			var selected_user_ID = jQuery( '[name=selected_user_ID]:checked' );
			if( selected_user_ID.length == 0 )
			{
				alert( config.msg_select_remaining_account );
				return false;
			}
			location.href = jQuery( link_obj ).attr( 'href' ) + '&selected_user_ID=' + selected_user_ID.val();
			return false;
		};
} );
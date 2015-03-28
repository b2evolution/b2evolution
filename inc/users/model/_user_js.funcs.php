<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>

<script type="text/javascript">
/**
 * Init autocomplete event for Specific criteria input
 */
function userfield_criteria_autocomplete( obj_this )
{
	if( obj_this.find( 'option:selected[rel=suggest]' ).length > 0 )
	{	// Selected field type can be suggested with values
		var field_type_id = obj_this.val();
		obj_this.next().find( 'input' ).autocomplete({
			source: function(request, response) {
				jQuery.getJSON( htsrv_url + 'anon_async.php?action=get_user_field_autocomplete', {
					term: request.term, attr_id: field_type_id
				}, response);
			},
		});
	}
	else
	{	// Destroy autocomplete event from previous binding
		obj_this.next().find( 'input' ).autocomplete().autocomplete( 'destroy' );
	}
}

jQuery( document ).on( 'change', 'select[id^=criteria_type]', function()
{
	userfield_criteria_autocomplete( jQuery( this ) );
} );

if( jQuery( 'select[id^=criteria_type]:first' ).val() == '' )
{	// Pre-select a random option
	var count_options = parseInt( jQuery( 'select[id^=criteria_type]:first option' ).length );
	var index = Math.ceil( Math.random() * count_options );
	if( index == count_options )
	{	// Exclude empty value
		index = 1;
	}
	jQuery( 'select[id^=criteria_type]:first option:eq(' + index + ')' ).attr( 'selected', 'selected' );
}

for(var c = 0; c < jQuery( 'select[id^=criteria_type]' ).length; c++ )
{	// Bind autocomplete event for each Specific criteria
	userfield_criteria_autocomplete( jQuery( 'select[id^=criteria_type]:eq(' + c + ')' ) );
}

jQuery( document ).on( 'click', 'span[rel=add_criteria]', function()
{ // Add new criteria to search
	var params = '<?php
			global $b2evo_icons_type, $blog;
			echo empty( $b2evo_icons_type ) ? '' : '&b2evo_icons_type='.$b2evo_icons_type;
			echo is_admin_page() ? '&is_backoffice=1' : '&blog='.$blog;
		?>';

	obj_this = jQuery( this ).parent().parent();
	jQuery.ajax({
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_userfields_criteria' + params,
	success: function( result )
		{	// Display fieldset of new Specific criteria
			obj_this.after( ajax_debug_clear( result ) );

			// Preselect a random option
			obj_new = obj_this.next().next();
			var count_options = parseInt( obj_new.find( 'option' ).length );
			var index = Math.ceil( Math.random() * count_options );
			if( index == count_options )
			{	// Exclude empty value
				index = 1;
			}
			obj_new.find( 'option:eq(' + index + ')' ).attr( 'selected', 'selected' );

			// Bind auto complete event to the new select
			userfield_criteria_autocomplete( obj_new.find( 'select' ) );
		}
	});
} );

<?php
global $current_User;
if( is_admin_page() && is_logged_in() && $current_User->check_perm( 'users', 'moderate', false ) )
{	// If user can edit the users - Init js to edit user level by AJAX
?>
jQuery(document).ready( function()
{
	jQuery('.user_level_edit').each( function()
	{
		if( jQuery( this ).find( 'a' ).length == 0 )
		{
			jQuery( this ).removeClass( 'user_level_edit' );
		}
	} );
<?php
	$user_levels = array();
	for( $l = 0; $l <= 10; $l++ )
	{
		$user_levels[ $l ] = $l;
	}
	// Print JS to edit an user level
	echo_editable_column_js( array(
		'column_selector' => '.user_level_edit',
		'ajax_url'        => get_secure_htsrv_url().'async.php?action=user_level_edit&'.url_crumb( 'userlevel' ),
		'options'         => $user_levels,
		'new_field_name'  => 'new_user_level',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'user_ID',
		'print_init_tags' => false ) );
?>
});
<?php } ?>
</script>
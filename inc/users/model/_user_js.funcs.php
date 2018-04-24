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

for(var c = 0; c < jQuery( 'select[id^=criteria_type]' ).length; c++ )
{	// Bind autocomplete event for each Specific criteria
	userfield_criteria_autocomplete( jQuery( 'select[id^=criteria_type]:eq(' + c + ')' ) );
}

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
		'ajax_url'        => get_htsrv_url().'async.php?action=user_level_edit&'.url_crumb( 'userlevel' ),
		'options'         => $user_levels,
		'new_field_name'  => 'new_user_level',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'user_ID',
		'print_init_tags' => false ) );
?>
});
<?php } ?>

/**
 * Merge two users from duplicated users list
 */
function merge_duplicated_users( link_obj )
{
	var selected_user_ID = jQuery( '[name=selected_user_ID]:checked' );
	if( selected_user_ID.length == 0 )
	{
		alert( '<?php echo TS_('Please select a remaining account!'); ?>' );
		return false;
	}
	location.href = jQuery( link_obj ).attr( 'href' ) + '&selected_user_ID=' + selected_user_ID.val();
	return false;
}
</script>
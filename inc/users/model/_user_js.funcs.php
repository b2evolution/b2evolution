<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$can_edit_user_level = is_admin_page() && check_user_perm( 'users', 'moderate' );
$user_funcs_config = array(
	'msg_select_remaining_account' => T_('Please select a remaining account!'),
	'can_edit_user_level' => $can_edit_user_level,
);
expose_var_to_js( 'evo_user_funcs_config', evo_json_encode( $user_funcs_config ) );

if( $can_edit_user_level )
{
	$user_levels = array();
	for( $l = 0; $l <= 10; $l++ )
	{
		$user_levels[ $l ] = $l;
	}
	echo_editable_column_js( array(
		'column_selector' => '.user_level_edit',
		'ajax_url'        => get_htsrv_url().'async.php?action=user_level_edit&'.url_crumb( 'userlevel' ),
		'options'         => $user_levels,
		'new_field_name'  => 'new_user_level',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'user_ID',
		'print_init_tags' => false ) );
}
?>

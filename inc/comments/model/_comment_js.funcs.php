<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Collection, $Blog, $Session, $admin_url, $status_list, $CommentList, $b2evo_icons_type;

// Require this file because function evoAlert() is used here
require_js_defer( 'functions.js', 'blog', true );

// Initialize JavaScript to build and open window:
echo_modalwindow_js();

$comment_funcs_config = array(
		'is_admin_page'    => is_admin_page(),
		'admin_url'        => $admin_url,
		'crumb_comment'    => get_crumb( 'comment' ),
		'crumb_antispam'   => get_crumb( 'antispam' ),
		'blog_ID'          => $Blog->ID,
		'b2evo_icons_type' => isset( $b2evo_icons_type ) ? $b2evo_icons_type : '',
		'request_from'     => request_from(),
		'displayed'        => ! empty( $CommentList ) ? intval( $CommentList->result_num_rows ) : 0,

		'button_class_button_red'   => button_class( 'button_red' ),
		'button_class_button_green' => button_class( 'button_green' ),
		'button_class_button'       => button_class( 'button', true ),
		'button_class_group'        => button_class( 'group', true ),
		'button_class_text'         => button_class( 'text', true ),

		'delete_confirmation_msg' => T_('You are about to delete this comment!\\nThis cannot be undone!'),
		'loading_msg' => T_('Loading...'),
		'confirm_ban_delete_title' => T_('Confirm ban & delete'),
		'perform_selected_operations_msg' => T_('Perform selected operations'),
	);

	expose_var_to_js( 'evo_comment_funcs_config', evo_json_encode( $comment_funcs_config ) );
?>

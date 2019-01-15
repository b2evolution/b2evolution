<?php
/**
 * This file implements the UI view to change user account status from users list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $admin_url;

$Form = new Form( NULL, 'users_status_checkchanges' );

$Form->switch_template_parts( array(
		'labelclass' => 'control-label col-sm-6',
		'inputstart' => '<div class="controls col-sm-6">',
		'inputstart_radio' => '<div class="controls col-sm-6">',
		'infostart'  => '<div class="controls col-sm-6"><div class="form-control-static">',
	) );

$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";

$Form->begin_form( 'fform' );

$Form->add_crumb( 'users' );
$Form->hidden_ctrl();

// A link to close popup window:
$close_icon = action_icon( T_('Close this window'), 'close', '', '', 0, 0, array( 'id' => 'close_button', 'class' => 'floatright' ) );

$Form->begin_fieldset( T_('Set account status...').get_manual_link( 'userlist-set-account-status' ).$close_icon );

	// Status:
	$user_status_icons = get_user_status_icons();
	reset( $user_status_icons );
	$first_key = key( $user_status_icons );
	$status_icon = '<div id="user_status_icon" class="status_icon">'.$user_status_icons[$first_key].'</div>';
	$Form->select_input_array( 'account_status', $first_key, get_user_statuses(), T_( 'New account status' ), '', array( 'input_prefix' => $status_icon ) );

$Form->end_fieldset();

$Form->button( array( '', 'actionArray[update_status]', T_('Make changes now!'), 'SaveButton' ) );

$Form->end_form();
?>
<script>
var user_status_icons = new Array;
<?php
foreach( $user_status_icons as $status => $icon )
{	// Init js array with user status icons
?>
user_status_icons['<?php echo $status; ?>'] = '<?php echo format_to_js( $icon ); ?>';
<?php } ?>

jQuery( '#account_status' ).change( function()
{	// Change icon of the user status
	if( typeof user_status_icons[ jQuery( this ).val() ] != 'undefined' )
	{
		jQuery( '#user_status_icon' ).html( user_status_icons[ jQuery( this ).val() ] );
	}
	else
	{
		jQuery( '#user_status_icon' ).html( '' );
	}
} );
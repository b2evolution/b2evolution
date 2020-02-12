<?php
/**
 * This file implements the UI view for the demo content panel on blog management screens.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url;

load_funcs( 'collections/_demo_content.funcs.php' );
load_funcs( 'dashboard/model/_dashboard.funcs.php' );

$welcome_content_Widget = new Widget( 'block_item' );
	echo '<form action="'.$admin_url.'?ctrl=collections" method="post" class="evo_form__install">';
	echo '<input type="hidden" name="action" value="create_demo_content" />';
	echo '<input type="hidden" name="install_test_features" value="0" />';
	echo '<input type="hidden" name="crumb_demo_content" value="'.get_crumb( 'demo_content' ).'" />';

	$welcome_content_Widget->title = T_('Quick start wizard');
	$welcome_content_Widget->disp_template_replaced( 'block_start' );

	echo '<p>'.T_('Your b2evolution installation is installed and working but there is no content yet.').'</p>';
	echo '<p>'.T_('Would you like to create some demo contents to get a better understanding of how things work? You can easily delete these demo contents when you no longer need them.').'</p>';
	$enable_create_demo_users = get_table_count( 'T_users', 'user_ID != 1' ) === 0;
	$show_create_email_lists = ( get_table_count( 'T_email__newsletter' ) === 0 );
	echo echo_installation_options( array(
			'enable_create_demo_users' => $enable_create_demo_users,
			'show_create_organization' => $enable_create_demo_users && ( get_table_count( 'T_users__organization' ) === 0 ),
			'show_create_messages'     => $enable_create_demo_users && ( get_table_count( 'T_messaging__message' ) === 0 ),
			'show_create_email_lists'     => $show_create_email_lists,
			'show_create_email_campaigns' => $show_create_email_lists && ( get_table_count( 'T_email__campaign' ) === 0 ),
			'show_create_automations'     => $show_create_email_lists && ( get_table_count( 'T_automation__automation' ) === 0 ),
		) );

	?>
	<p class="evo_form__install_buttons">
		<button id="cancel_button" type="submit" class="btn btn-primary"><?php echo T_('Create!')?></button>
	</p>
	</form>
	
	<p><?php echo sprintf( T_('Alternatively, you can also <a %s>manually create a new collection</a>.'), 'href="'.$admin_url.'?ctrl=collections&amp;action=new"' ); ?></p>
	<?php

	$welcome_content_Widget->disp_template_replaced( 'block_end' );
?>
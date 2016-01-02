<?php
/**
 * This file display the 3rd step of phpBB importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher, $flush_action;

phpbb_display_steps( 3 );

$Form = new Form();

$Form->begin_form( 'fform', T_('phpBB Importer').' - '.T_('Step 3: Import users') );
evo_flush();

$Form->add_crumb( 'phpbb' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'forums' );

if( $flush_action == 'users' )
{
	$Form->begin_fieldset( T_('Import log') );

	// Import the users
	phpbb_import_users();

	$Form->end_fieldset();
}


$Form->begin_fieldset( T_('Report of users import') );

	$Form->info( T_('Count of the imported users'), '<b>'.(int)phpbb_get_var( 'users_count_imported' ).'</b>' );

	$Form->info( T_('Count of the updated users'), '<b>'.(int)phpbb_get_var( 'users_count_updated' ).'</b>' );

	$GroupCache = & get_GroupCache();

	$group_default = phpbb_get_var( 'group_default' );
	if( $Group = & $GroupCache->get_by_ID( $group_default, false ) )
	{
		$Form->info( T_('Default group'), $Group->get_name() );
	}

	$group_invalid = phpbb_get_var( 'group_invalid' );
	if( !empty( $group_invalid ) && $Group = & $GroupCache->get_by_ID( $group_invalid, false ) )
	{
		$group_invalid_name = $Group->get_name();
	}
	else
	{
		$group_invalid_name = T_('No import');
	}
	$Form->info( T_('Group for invalid users'), $group_invalid_name );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Continue!'), 'SaveButton' )/*,
											 array( 'button', 'button', T_('Back'), 'SaveButton', 'location.href=\''.$dispatcher.'?ctrl=phpbbimport&step=groups\'' )*/ ) );

$Form->end_form();

?>
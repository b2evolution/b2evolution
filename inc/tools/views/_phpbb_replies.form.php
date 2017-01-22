<?php
/**
 * This file display the 6th step of phpBB importer
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

global $dispatcher, $flush_action, $phpbb_tool_title;

phpbb_display_steps( 6 );

$Form = new Form();

$Form->begin_form( 'fform', $phpbb_tool_title.' - '.T_('Step 6: Import replies') );
evo_flush();

$Form->add_crumb( 'phpbb' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'messages' );
$Form->hidden( 'ver', get_param( 'ver' ) );

if( $flush_action == 'replies' )
{
	$Form->begin_fieldset( T_('Import log') );

	// Import the replies into the comments
	phpbb_import_replies();

	$Form->end_fieldset();
}

$Form->begin_fieldset( T_('Report of the replies import') );

	$Form->info( T_('Count of the imported replies'), '<b>'.(int)phpbb_get_var( 'replies_count_imported' ).'</b>' );

	$Form->info( T_('Count of the imported topics'), (int)phpbb_get_var( 'topics_count_imported' ) );

	$Form->info( T_('Count of the imported forums'), (int)phpbb_get_var( 'forums_count_imported' ) );

	$Form->info( T_('Count of the imported users'), (int)phpbb_get_var( 'users_count_imported' ) );

	$Form->info( T_('Count of the updated users'), (int)phpbb_get_var( 'users_count_updated' ) );

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( phpbb_get_var( 'blog_ID' ) );
	$Form->info( T_('Collection'), $Blog->get( 'name' ), '' );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Continue!'), 'SaveButton' )/*,
											 array( 'button', 'button', T_('Back'), 'SaveButton', 'location.href=\''.$dispatcher.'?ctrl=phpbbimport&step=topics\'' )*/ ) );

$Form->end_form();

?>
<?php
/**
 * This file display the 7th step of phpBB importer
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

global $dispatcher, $flush_action, $phpbb_tool_title, $phpbb_version;

phpbb_display_steps( 7 );

$Form = new Form();

$Form->begin_form( 'fform', $phpbb_tool_title.' - '.T_('Step 7: Import messages') );
evo_flush();

$Form->add_crumb( 'phpbb' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'finish' );
$Form->hidden( 'ver', get_param( 'ver' ) );

if( $flush_action == 'messages' )
{
	$Form->begin_fieldset( T_('Import log') );

	// Import the messages
	phpbb_import_messages();

	$Form->end_fieldset();
}

$Form->begin_fieldset( T_('Report of the mesagges import') );

	$Form->info( T_('Count of the imported messages'), '<b>'.(int)phpbb_get_var( 'messages_count_imported' ).'</b>' );

	$Form->info( T_('Count of the messages that are NOT imported because of missing users'), '<b class="red">'.(int)phpbb_get_var( 'messages_count_missing_users' ).'</b>' );

	$Form->info( T_('Count of the imported replies'), (int)phpbb_get_var( 'replies_count_imported' ) );

	$Form->info( T_('Count of the imported topics'), (int)phpbb_get_var( 'topics_count_imported' ) );

	$Form->info( T_('Count of the imported forums'), (int)phpbb_get_var( 'forums_count_imported' ) );

	$Form->info( T_('Count of the imported users'), (int)phpbb_get_var( 'users_count_imported' ) );

	$Form->info( T_('Count of the updated users'), (int)phpbb_get_var( 'users_count_updated' ) );

	$Form->info( T_('Count of the imported / missing avatars'), intval( phpbb_get_var( 'avatars_count_imported' ) ).' / <b class="red">'.intval( phpbb_get_var( 'avatars_count_missing' ) ).'</b>' );

	if( $phpbb_version == 3 )
	{	// Only for phpBB3:
		$Form->info( T_('Count of the imported / missing attachments'), intval( phpbb_get_var( 'attachments_count_imported' ) ).' / <b class="red">'.intval( phpbb_get_var( 'attachments_count_missing' ) ).'</b>' );
	}

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( phpbb_get_var( 'blog_ID' ) );
	$Form->info( T_('Collection'), $Blog->get( 'name' ), '' );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Go to Forum'), 'SaveButton' ) ) );

$Form->end_form();

?>
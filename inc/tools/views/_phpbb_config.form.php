<?php
/**
 * This file display the 1st step of phpBB importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $phpbb_db_config, $phpbb_blog_ID, $phpbb_tool_title, $admin_url, $phpbb_version;

phpbb_display_steps( 1 );

$Form = new Form();

$Form->begin_form( 'fform', $phpbb_tool_title.' - '.T_('Step 1: Database connection') );
evo_flush();

$Form->add_crumb( 'phpbb' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'database' );
$Form->hidden( 'ver', get_param( 'ver' ) );

$Form->begin_fieldset( T_('Access information for database of phpBB forum') );

	$Form->text( 'db_host', param( 'db_host', 'string', $phpbb_db_config['host'] ), 20, T_('Database host') );

	$Form->text( 'db_name', param( 'db_name', 'string', $phpbb_db_config['name'] ), 20, T_('Database name') );

	$Form->text( 'db_user', param( 'db_user', 'string', $phpbb_db_config['user'] ), 20, T_('Username') );

	$Form->password( 'db_pass', param( 'db_pass', 'string', $phpbb_db_config['password'] ), 20, T_('Password') );

	$Form->text( 'db_prefix', param( 'db_prefix', 'string', $phpbb_db_config['prefix'] ), 20, T_('Table prefix') );

	$Form->text( 'path_avatars', param( 'path_avatars', 'string', phpbb_get_var( 'path_avatars' ) ), 80, T_('Source for avatars'), '', 1000 );

	if( $phpbb_version == 3 )
	{	// Only for phpBB3:
		$Form->text( 'path_attachments', param( 'path_attachments', 'string', phpbb_get_var( 'path_attachments' ) ), 80, T_('Source for attachments'), '', 1000 );
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('Destination collection') );

	$BlogCache = & get_BlogCache();

	$Form->select_input_object( 'forum_blog_ID', param( 'forum_blog_ID', 'integer', phpbb_get_var( 'blog_ID' ) ), $BlogCache, T_('Destination collection'), array(
			'note' => T_('Select the destination forum collection.').' <a href="'.$admin_url.'?ctrl=collections&action=new">'.T_('Create new collection').' &raquo;</a>',
			'allow_none' => true,
			'object_callback' => 'get_option_list_forums' ) );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Continue').'!', 'SaveButton' ) ) );

$Form->end_form();

?>
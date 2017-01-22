<?php
/**
 * This file display the 2nd step of phpBB importer
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

global $phpbb_db_config, $dispatcher, $phpbb_tool_title;

phpbb_display_steps( 2 );

$Form = new Form();

$Form->begin_form( 'fform', $phpbb_tool_title.' - '.T_('Step 2: User group mapping') );
evo_flush();

$Form->add_crumb( 'phpbb' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'users' );
$Form->hidden( 'ver', get_param( 'ver' ) );

$Form->begin_fieldset( T_('Access information for database of phpBB forum') );

	$Form->info( T_('Connection'), '<b class="green">OK</b>' );

	$Form->info( T_('Database host'), $phpbb_db_config['host'] );

	$Form->info( T_('Database name'), $phpbb_db_config['name'] );

	$Form->info( T_('Username'), $phpbb_db_config['user'] );

	$Form->info( T_('Password'), str_repeat( '*', strlen( $phpbb_db_config['password'] ) ) );

	$Form->info( T_('Table prefix'), $phpbb_db_config['prefix'] );

	$path_avatars = phpbb_get_var( 'path_avatars' );
	$path_avatars_note = '';
	if( !empty( $path_avatars ) && !file_exists( $path_avatars ) )
	{	// Path avatars is incorrect
		$path_avatars = '<b class="red">'.$path_avatars.'</b>';
		$path_avatars_note = T_('This folder does not exist');
	}
	$Form->info( T_('Source for avatars'), $path_avatars, $path_avatars_note );

	$BlogCache = & get_BlogCache();
	if( $phpbbBlog = & $BlogCache->get_by_ID( phpbb_get_var( 'blog_ID' ) ) )
	{
		$Form->info( T_('Blog for import'), $phpbbBlog->get( 'name' ) );
	}

$Form->end_fieldset();


$Form->begin_fieldset( T_('Users groups') );

	$b2evo_groups = b2evo_groups();

	$rank_values = phpbb_get_var( 'ranks' );
	$phpbb_ranks = phpbb_ranks();

	$b2evo_groups_default = $b2evo_groups;
	$b2evo_groups_default['0'] = T_('Select');
	$Form->select_input_array( 'phpbb_group_default', phpbb_get_var( 'group_default' ), $b2evo_groups_default, T_('Default group'), T_( 'Use this group as the default for users without a defined rank' ).' ('.phpbb_rank_info( '' ).')', array( 'force_keys_as_values' => true ) );

	$Form->select_input_array( 'phpbb_group_invalid', phpbb_get_var( 'group_invalid' ), $b2evo_groups, '<span class="red">'.T_('Invalid users').'</span>', T_( 'Use this group as the default for users which were deleted from the DB' ), array( 'force_keys_as_values' => true ) );

	echo T_('Please select the ranks which should be imported:');

	foreach( $phpbb_ranks as $rank_ID => $rank_name )
	{
		$rank_users_count = phpbb_rank_info( $rank_ID, true );
		if( $rank_users_count == 0 )
		{	// Don't display ranks without users
			continue;
		}
		$rank_value = isset( $rank_values[ $rank_ID ] ) ? $rank_values[ $rank_ID ] : phpbb_get_var( 'all_group_default' );
		$Form->select_input_array( 'phpbb_ranks['.$rank_ID.']', $rank_value, $b2evo_groups, $rank_name, phpbb_rank_info( $rank_ID ), array( 'force_keys_as_values' => true, ) );
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('Select the forums which will be imported') );

	phpbb_forums_list( $Form );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Continue!'), 'SaveButton' ),
											 array( 'button', 'button', T_('Back'), 'SaveButton', 'location.href=\''.$dispatcher.'?ctrl=phpbbimport\'' ) ) );

$Form->end_form();

?>
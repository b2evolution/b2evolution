<?php
/**
 * This file display the 1st step of phpBB importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _misc_tools.view.php 505 2011-12-09 20:54:21Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $phpbb_db_config, $phpbb_blog_ID, $dispatcher;

$Form = new Form();

$Form->begin_form( 'fform', T_('phpBB Importer').' - '.T_('Step 1: Database connection') );
evo_flush();

$Form->add_crumb( 'phpbb' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'database' );

$Form->begin_fieldset( T_('Access information for database of phpBB forum') );

	$Form->text( 'db_host', param( 'db_host', 'string', $phpbb_db_config['host'] ), 20, T_('Database host') );

	$Form->text( 'db_name', param( 'db_name', 'string', $phpbb_db_config['name'] ), 20, T_('Database name') );

	$Form->text( 'db_user', param( 'db_user', 'string', $phpbb_db_config['user'] ), 20, T_('Username') );

	$Form->password( 'db_pass', param( 'db_pass', 'string', $phpbb_db_config['password'] ), 20, T_('Password') );

	$Form->text( 'db_prefix', param( 'db_prefix', 'string', $phpbb_db_config['prefix'] ), 20, T_('Table prefix') );

	$Form->text( 'path_avatars', param( 'path_avatars', 'string', phpbb_get_var( 'path_avatars' ) ), 80, T_('Source for avatars'), '', 1000 );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Select a blog for import') );

	$BlogCache = & get_BlogCache();

	$Form->select_input_object( 'forum_blog_ID', param( 'forum_blog_ID', 'integer', phpbb_get_var( 'blog_ID' ) ), $BlogCache, T_('Blog for import'), array(
			'note' => T_('Select the destination forum collection.').' <a href="'.$dispatcher.'?ctrl=collections&action=new">'.T_('Create new collection').' &raquo;</a>',
			'allow_none' => true,
			'object_callback' => 'get_option_list_forums' ) );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Continue !'), 'SaveButton' ) ) );

$Form->end_form();

?>
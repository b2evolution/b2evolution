<?php
/**
 * This file display the 4th step of phpBB importer
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
 * @version $Id: _phpbb_users.form.php 505 2011-12-09 20:54:21Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher, $flush_action;

$Form = new Form();

$Form->begin_form( 'fform', T_('phpBB Importer').' - '.T_('Step 4: Import forums') );
evo_flush();

$Form->add_crumb( 'phpbb' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'topics' );

if( $flush_action == 'forums' )
{
	$Form->begin_fieldset( T_('Import log') );

	// Import the forums into the categories
	phpbb_import_forums();

	$Form->end_fieldset();
}

$Form->begin_fieldset( T_('Report of the forums import') );

	$Form->info( T_('Count of the imported forums'), '<b>'.(int)phpbb_get_var( 'forums_count_imported' ).'</b>' );

	$Form->info( T_('Count of the imported users'), (int)phpbb_get_var( 'users_count_imported' ) );

	$Form->info( T_('Count of the updated users'), (int)phpbb_get_var( 'users_count_updated' ) );

	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( phpbb_get_var( 'blog_ID' ) );
	$Form->info( T_('Blog'), $Blog->get( 'name' ), '' );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Continue !'), 'SaveButton' )/*,
											 array( 'button', 'button', T_('Back'), 'SaveButton', 'location.href=\''.$dispatcher.'?ctrl=phpbbimport&step=users\'' )*/ ) );

$Form->end_form();

?>
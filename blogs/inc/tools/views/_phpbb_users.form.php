<?php
/**
 * This file display the 3rd step of phpBB importer
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
		$Form->info( T_('Default group'), $Group->get( 'name' ) );
	}

	$group_invalid = phpbb_get_var( 'group_invalid' );
	if( !empty( $group_invalid ) && $Group = & $GroupCache->get_by_ID( $group_invalid, false ) )
	{
		$group_invalid_name = $Group->get( 'name' );
	}
	else
	{
		$group_invalid_name = T_('No import');
	}
	$Form->info( T_('Group for invalid users'), $group_invalid_name );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Continue !'), 'SaveButton' )/*,
											 array( 'button', 'button', T_('Back'), 'SaveButton', 'location.href=\''.$dispatcher.'?ctrl=phpbbimport&step=groups\'' )*/ ) );

$Form->end_form();

?>
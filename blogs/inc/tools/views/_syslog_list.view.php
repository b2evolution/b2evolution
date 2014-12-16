<?php
/**
 * This file implements the system log list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2013 by Francois PLANQUE - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _syslog_list.view.php 7044 2014-07-02 08:55:10Z yura $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Get params from request
$user_login = param( 'user_login', 'string', '', true );
$type = param( 'type', 'string', '', true );
$origin = param( 'origin', 'string', '', true );
$origin_ID = param( 'origin_ID', 'integer', '', true );
$object = param( 'object', 'string', '', true );
$object_ID = param( 'object_ID', 'integer', '', true );

// Create query
$SQL = new SQL();
$SQL->SELECT( 'slg_timestamp, slg_type, slg_user_ID, slg_origin, slg_origin_ID, slg_object, slg_object_ID, slg_message' );
$SQL->FROM( 'T_syslog' );
$SQL->FROM_add( 'LEFT JOIN T_users ON slg_user_ID = user_ID' );

if( !empty( $type ) )
{ // Filter by log type:
	$SQL->WHERE_and( 'slg_type = '.$DB->quote( $type ) );
}

if( !empty( $user_login ) )
{ // Filter by user login:
	$user_login = str_replace( '*', '%', $user_login );
	$SQL->WHERE_and( 'user_login LIKE '.$DB->quote( $user_login ) );
}

if( !empty( $origin ) )
{ // Filter by origin type:
	$SQL->WHERE_and( 'slg_origin = '.$DB->quote( $origin ) );

	if( $origin == 'plugin' && !empty( $origin_ID ) )
	{ // Filter by origin ID
		$SQL->WHERE_and( 'slg_origin_ID = '.$DB->quote( $origin_ID ) );
	}
}

if( !empty( $object ) )
{ // Filter by object type:
	$SQL->WHERE_and( 'slg_object = '.$DB->quote( $object ) );

	if( !empty( $object_ID ) )
	{ // Filter by object ID
		$SQL->WHERE_and( 'slg_object_ID = '.$DB->quote( $object_ID ) );
	}
}

// Create result set:
$Results = new Results( $SQL->get(), 'slg_', 'D' );

$Results->title = T_('System log');

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_syslog_list( & $Form )
{
	$Form->text_input( 'user_login', get_param( 'user_login' ), 10, T_('User login'), '', array( 'maxlength' => 20 ) );

	$field_options = array (
			'0'              => T_('All'),
			'info'           => T_('Info'),
			'warning'        => T_('Warning'),
			'error'          => T_('Error'),
			'critical_error' => T_('Critical Error'),
		);
	$Form->select_input_array( 'type', get_param( 'type' ), $field_options, T_('Log type'), '', array( 'force_keys_as_values' => true ) );

	$field_options = array (
			'0'      => T_('All'),
			'core'   => T_('Core'),
			'plugin' => T_('Plugin'),
		);
	$Form->select_input_array( 'origin', get_param( 'origin' ), $field_options, T_('Origin type'), '', array( 'force_keys_as_values' => true ) );

	$Form->text_input( 'origin_ID', get_param( 'origin_ID' ), 5, T_('Origin ID'), '', array( 'maxlength' => 11 ) );

	$field_options = array (
			'0'       => T_('All'),
			'comment' => T_('Comment'),
			'item'    => T_('Item'),
			'user'    => T_('User'),
			'file'    => T_('File')
		);
	$Form->select_input_array( 'object', get_param( 'object' ), $field_options, T_('Object type'), '', array( 'force_keys_as_values' => true ) );

	$Form->text_input( 'object_ID', get_param( 'object_ID' ), 5, T_('Object ID'), '', array( 'maxlength' => 11 ) );
}
$Results->filter_area = array(
	'callback' => 'filter_syslog_list',
	'url_ignore' => 'results_slg_per_page,results_slg_page',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=syslog' ),
		)
	);

$Results->cols[] = array(
		'th' => T_('Date Time'),
		'order' => 'slg_ID',
		'default_dir' => 'D',
		'td' => '%mysql2localedatetime_spans( #slg_timestamp#, "M-d" )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap timestamp'
	);

$Results->cols[] = array(
		'th' => T_('Type'),
		'order' => 'slg_type',
		'td' => '$slg_type$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => T_('User'),
		'order' => 'user_login',
		'td' => '%get_user_identity_link( 0, #slg_user_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => T_('Origin'),
		'order' => 'slg_origin',
		'td' => '$slg_origin$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => T_('Origin ID'),
		'order' => 'slg_origin_ID',
		'td' => '$slg_origin_ID$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => T_('Object'),
		'order' => 'slg_object',
		'td' => '$slg_object$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => T_('Object ID'),
		'order' => 'slg_object_ID',
		'td' => '$slg_object_ID$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => T_('Message'),
		'order' => 'slg_message',
		'td' => '%format_to_output( #slg_message#, \'htmlspecialchars\' )%' // Escape syslog messages because it may contain special characters
	);

/**
 * Get a link to object of system log
 *
 * @param string Object type
 * @param integer Object ID
 * @return string
 */
function syslog_object_link( $object_type, $object_ID )
{
	global $current_User, $admin_url;

	$link = '';

	if( empty( $object_ID ) )
	{ // Invalid object ID
		return T_('Empty object ID');
	}

	switch( $object_type )
	{
		case 'comment':
			// Link to comment
			$CommentCache = & get_CommentCache();
			if( ( $Comment = & $CommentCache->get_by_ID( $object_ID, false, false ) ) !== false )
			{
				if( $current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $Comment ) )
				{ // Current user has permission to edit this comment
					$Item = & $Comment->get_Item();
					$link = '<a href="'.$admin_url.'?ctrl=comments&action=edit&comment_ID='.$Comment->ID.'">'.$Item->title.' #'.$Comment->ID.'</a>';
				}
			}
			else
			{ // Comment was deleted or ID is incorrect
				$link = T_('No comment');
			}
			break;

		case 'item':
			// Link to item
			$ItemCache = & get_ItemCache();
			if( ( $Item = & $ItemCache->get_by_ID( $object_ID, false, false ) ) !== false )
			{
				if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
				{ // Current user has permission to edit this item
					$link = '<a href="'.$admin_url.'?ctrl=items&amp;action=edit&amp;p='.$Item->ID.'">'.$Item->title.'</a>';
				}
			}
			else
			{ // Item was deleted or ID is incorrect
				$link = T_('No item');
			}
			break;

		case 'user':
			// Link to user
			if( $current_User->check_perm( 'users', 'view' ) )
			{ // Current user has permission to view users
				$UserCache = get_UserCache();
				if( ( $User = & $UserCache->get_by_ID( $object_ID, false, false ) ) !== false )
				{ // User exists
					$link = $User->get_identity_link();
				}
				else
				{ // User was deleted or ID is incorrect
					$link = T_('No user');
				}
			}
			break;

		case 'file':
			// Link to file
			$FileCache = & get_FileCache();
			if( ( $File = & $FileCache->get_by_ID( $object_ID, false, false ) ) !== false )
			{ // File exists
				$link = $File->is_dir() ? '' : $File->get_view_link();
				$link .= ' '.$File->get_target_icon();
			}
			else
			{ // User was deleted or ID is incorrect
				$link = T_('No file');
			}
			break;
	}

	return $link;
}
$Results->cols[] = array(
		'th' => T_('Object Link'),
		'td' => '%syslog_object_link( #slg_object#, #slg_object_ID# )%',
	);

$Results->display();

?>
<script type="text/javascript">
function syslog_origin_ID()
{
	if( jQuery( '#origin' ).val() == 'plugin' )
	{
		jQuery( '#ffield_origin_ID' ).show();
	}
	else
	{
		jQuery( '#ffield_origin_ID' ).hide();
	}
}
function syslog_object_ID()
{
	if( jQuery( '#object' ).val() != '0' )
	{
		jQuery( '#ffield_object_ID' ).show();
	}
	else
	{
		jQuery( '#ffield_object_ID' ).hide();
	}
}

syslog_origin_ID();
syslog_object_ID();

jQuery( '#origin' ).change( function () { syslog_origin_ID(); } );
jQuery( '#object' ).change( function () { syslog_object_ID(); } );
</script>
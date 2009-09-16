<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * @author evofactory-test
 * @author fplanque: Francois Planque.
 *
 * @version _userfields.view.php,v 1.1 2009/09/11 18:34:05 fplanque Exp
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;
global $Session;

/**
 * View funcs
 */
// require_once dirname(__FILE__).'/_user_fields_view.funcs.php';

$final = param( 'final', 'integer', 0, true );

// Create result set:
$sql = 'SELECT *
					FROM T_users__fielddefs';

$Results = & new Results( $sql, 'ufdfs_', 'A' );

$Results->title = T_('User fields');

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ufdf_ID',
		'td_class' => 'center',
		'td' => '$ufdf_ID$',
	);

$Results->cols[] = array(
	'th' => T_('Type'),
	'order' => 'ufdf_type',
	'td' => '$ufdf_type$',
);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'ufdf_name',
		'td' => '$ufdf_name$',
	);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => action_icon( T_('Edit this user field...'), 'edit',
	                        '%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=edit\')%' )
	                    .action_icon( T_('Duplicate this user field...'), 'copy',
	                        '%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=copy\')%' )
	                    .action_icon( T_('Delete this file user field!'), 'delete',
	                        '%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=delete\')%' ),
						);

  $Results->global_icon( T_('Create a new user field...'), 'new', regenerate_url( 'action', 'action=new' ), T_('New user field').' &raquo;', 3, 4  );
}


// Display results:
$Results->display();

/*
 * _userfields.view.php,v
 * Revision 1.1  2009/09/11 18:34:05  fplanque
 * userfields editing module.
 * needs further cleanup but I think it works.
 *
 */
?>
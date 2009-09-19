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

load_class( 'users/model/_userfield.class.php', 'Userfield' );

global $dispatcher;

// Get params from request
$s = param( 's', 'string', '', true );
$s_type = param( 's_type', 'string', '', true );

// Create query
$SQL = & new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_users__fielddefs' );

if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$SQL->WHERE_and( 'CONCAT_WS( " ", ufdf_name ) LIKE "%'.$DB->escape($s).'%"' );
}

if( !empty( $s_type ) )
{	// We want to filter on user field type:
	$SQL->WHERE_and( 'ufdf_type LIKE "%'.$DB->escape($s_type).'%"' );
}

// Create result set:
$Results = & new Results( $SQL->get(), 'ufdf_', 'A' );

$Results->title = T_('User fields');

/**
 * Callback to enumerate possible user field types
 * 
 */
function enumerate_types( $selected = '' ) {
	$options = '<option value="">All</option>';
	foreach( Userfield::get_types() as $type_code => $type_name ) {
		$options .= '<option value="'.$type_code.'" ';
		if( $type_code == $selected ) $options .= '"selected" ';
		$options .= '>'.$type_name.'</option>';
	}
	return $options;
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_userfields( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
	$Form->select( 's_type', get_param( 's_type' ), 'enumerate_types', T_('Type'), '', ''  );
}

$Results->filter_area = array(
	'callback' => 'filter_userfields',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=userfields' ),
		)
	);


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
										'%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=new\')%' )
									.action_icon( T_('Delete this user field!'), 'delete',
										'%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=delete\')%' ),
						);

  $Results->global_icon( T_('Create a new user field...'), 'new',
				regenerate_url( 'action', 'action=new' ), T_('New user field').' &raquo;', 3, 4  );
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
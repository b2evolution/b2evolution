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
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;

// Create result set:
$Results = & new Results(
							'SELECT * FROM T_country', 'ctry_' );
$Results->Cache = & get_Cache( 'CountryCache' );
$Results->title = T_('Countries list');

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Code'),
							'order' => 'ctry_code',
							'td' => '<strong><a href="'.$dispatcher.'?ctrl=countries&amp;ctry_ID=$ctry_ID$&amp;action=edit" title="'.
											T_('Edit this country...').'">$ctry_code$</a></strong>',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Code'),
							'order' => 'ctry_code',
							'td' => '<strong>$ctry_code$</strong>',
						);

}

$Results->cols[] = array(
						'th' => T_('Name'),
						'order' => 'ctry_name',
						'td' => '$ctry_name$',
					);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => action_icon( T_('Edit this country...'), 'edit',
	                        '%regenerate_url( \'action\', \'ctry_ID=$ctry_ID$&amp;action=edit\')%' )
	                    .action_icon( T_('Duplicate this country...'), 'copy',
	                        '%regenerate_url( \'action\', \'ctry_ID=$ctry_ID$&amp;action=new\')%' )
	                    .action_icon( T_('Delete this country!'), 'delete',
	                        '%regenerate_url( \'action\', \'ctry_ID=$ctry_ID$&amp;action=delete\')%' ),
						);

  $Results->global_icon( T_('Create a new country ...'), 'new', regenerate_url( 'action', 'action=new'), T_('New country').' &raquo;', 3, 4  );
}

$Results->display();

/*
 * $Log$
 * Revision 1.3  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
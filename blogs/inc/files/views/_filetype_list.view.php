<?php
/**
 * This file implements the File types list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $rsc_url, $dispatcher;

global $Session;

// Create result set:
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_filetypes' );

$Results = new Results( $SQL->get(), 'ftyp_' );
$Results->Cache = & get_FiletypeCache();
$Results->title = T_('File types list');

$Results->cols[] = array(
						'th' => T_('Icon'),
						//'order' => 'ftyp_icon',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '% {Obj}->get_icon() %',
					);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Extensions'),
							'order' => 'ftyp_extensions',
							'td' => '<strong><a href="'.$dispatcher.'?ctrl=filetypes&amp;ftyp_ID=$ftyp_ID$&amp;action=edit" title="'.
											T_('Edit this file type...').'">$ftyp_extensions$</a></strong>',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Extensions'),
							'order' => 'ftyp_extensions',
							'td' => '<strong>$ftyp_extensions$</strong>',
						);

}

$Results->cols[] = array(
						'th' => T_('Name'),
						'order' => 'ftyp_name',
						'td' => '$ftyp_name$',
					);

$Results->cols[] = array(
						'th' => T_('Mime type'),
						'order' => 'ftyp_mimetype',
						'td' => '$ftyp_mimetype$',
					);

$Results->cols[] = array(
						'th' => T_('View type'),
						'order' => 'ftyp_viewtype',
						'td' => '$ftyp_viewtype$',
					);
/**
 * Display the permissions for the type file
 */
function display_perm( $perm )
{
	switch( $perm )
	{
		case 'any':
			$r = get_icon( 'file_allowed' );
			break;
		case 'registered':
			$r = get_icon( 'file_allowed_registered' );
			break;
		case 'admin':
			$r = get_icon( 'file_not_allowed' );
			break;
		default:
			debug_die( 'Wrong filetype allowed value!' );
	}
	return $r;
}

$Results->cols[] = array(
						'th' => T_('Upload'),
						'order' => 'ftyp_allowed',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%display_perm( #ftyp_allowed# )%',
					);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => action_icon( T_('Edit this file type...'), 'edit',
	                        '%regenerate_url( \'action\', \'ftyp_ID=$ftyp_ID$&amp;action=edit\')%' )
	                    .action_icon( T_('Duplicate this file type...'), 'copy',
	                        '%regenerate_url( \'action\', \'ftyp_ID=$ftyp_ID$&amp;action=copy\')%' )
	                    .action_icon( T_('Delete this file type!'), 'delete',
	                        '%regenerate_url( \'action\', \'ftyp_ID=$ftyp_ID$&amp;action=delete&amp;'.url_crumb('filetype').'\')%' ),
						);

  $Results->global_icon( T_('Create a new file type...'), 'new', regenerate_url( 'action', 'action=new'), T_('New file type').' &raquo;', 3, 4  );
}

$fadeout_id = $Session->get('fadeout_id');

// if there happened something with a item, apply fadeout to the row:
$highlight_fadeout = empty($fadeout_id) ? array() : array( 'ftyp_ID'=>array($fadeout_id) );

$Results->display( NULL, $highlight_fadeout );

//Flush fadeout
$Session->delete( 'fadeout_id');

/*
 * $Log$
 * Revision 1.13  2011/03/10 14:54:18  efy-asimo
 * Allow file types modification & add m4v file type
 *
 * Revision 1.12  2010/02/08 17:53:02  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.11  2010/01/30 18:55:27  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.10  2010/01/28 03:42:20  fplanque
 * minor
 *
 * Revision 1.9  2010/01/23 12:54:49  efy-yury
 * add: fadeouts
 *
 * Revision 1.8  2010/01/03 13:10:58  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.7  2009/09/25 13:09:36  efy-vyacheslav
 * Using the SQL class to prepare queries
 *
 * Revision 1.6  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.5  2009/07/06 23:52:24  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.4  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:30  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/08 20:23:03  fplanque
 * action icons / wording
 *
 * Revision 1.1  2007/06/25 11:00:12  fplanque
 * MODULES (refactored MVC)
 *
 */
?>
<?php
/**
 * This file implements the File types list.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $rsc_url;

// Create result set:
$Results = & new Results(
							'SELECT * FROM T_filetypes', 'ftyp_' );

$Results->title = T_('File types list');

$Results->cols[] = array(
						'th' => T_('Icon'),
						//'order' => 'ftyp_icon',
						'th_start' => '<th class="firstcol shrinkwrap">',
						'td_start' => '<td class="firstcol shrinkwrap">',
						'td' => '<img src="'.$rsc_url.'icons/fileicons/$ftyp_icon$" alt="$ftyp_icon$">',
					);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Extensions'),
							'order' => 'ftyp_extensions',
							'td' => '<strong><a href="admin.php?ctrl=filetypes&amp;ftyp_ID=$ftyp_ID$&amp;action=edit" title="'.
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
	if ( $perm )
	{
		$r = get_icon( 'file_allowed' );
	}
	else
	{
		$r = get_icon( 'file_not_allowed' );
	}
	return $r;
}

$Results->cols[] = array(
						'th' => T_('Upload'),
						'order' => 'ftyp_allowed',
						'th_start' => '<th class="shrinkwrap">',
						'td_start' => '<td class="shrinkwrap">',
						'td' => '%display_perm( #ftyp_allowed# )%',
					);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_start' => '<th class="lastcol shrinkwrap">',
							'td_start' => '<td class="lastcol shrinkwrap">',
							'td' => action_icon( T_('Edit this file type...'), 'edit',
	                        '%regenerate_url( \'action\', \'ftyp_ID=$ftyp_ID$&amp;action=edit\')%' )
	                    .action_icon( T_('Duplicate this file type...'), 'copy',
	                        '%regenerate_url( \'action\', \'ftyp_ID=$ftyp_ID$&amp;action=copy\')%' )
	                    .action_icon( T_('Delete this file type!'), 'delete',
	                        '%regenerate_url( \'action\', \'ftyp_ID=$ftyp_ID$&amp;action=delete\')%' ),
						);

  $Results->global_icon( T_('Add a file type...'), 'new', regenerate_url( 'action', 'action=new'), T_('Add file type'), 3, 4  );
}


$Results->display();

?>
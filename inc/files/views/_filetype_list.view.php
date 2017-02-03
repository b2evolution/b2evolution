<?php
/**
 * This file implements the File types list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package admin
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
						'th' => /* noun */ T_('Upload'),
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

  $Results->global_icon( T_('Create a new file type...'), 'new', regenerate_url( 'action', 'action=new'), T_('New file type').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$fadeout_id = $Session->get('fadeout_id');

// if there happened something with a item, apply fadeout to the row:
$highlight_fadeout = empty($fadeout_id) ? array() : array( 'ftyp_ID'=>array($fadeout_id) );

$Results->display( NULL, $highlight_fadeout );

//Flush fadeout
$Session->delete( 'fadeout_id');

?>
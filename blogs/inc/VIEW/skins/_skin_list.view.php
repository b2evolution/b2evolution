<?php
/**
 * This file implements the UI view for the installed skins.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Create result set:
$Results = & new Results( 'SELECT *
													 	 FROM T_skin' );
$Results->Cache = & get_Cache( 'SkinCache' );
$Results->title = T_('Installed skins');

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'skin_name',
							'td' => '<strong><a href="'.regenerate_url( '', 'skin_ID=$skin_ID$&amp;action=edit' ).'" title="'.T_('Edit skin properties...').'">$skin_name$</a></strong>',
						);
}
else
{ // We have NO permission to modify:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'skin_name',
							'td' => '<strong>$skin_name$</strong>',
						);
}

$Results->cols[] = array(
						'th' => T_('Skin type'),
						'order' => 'skin_type',
						'td_class' => 'center',
						'td' => '$skin_type$',
					);

$Results->cols[] = array(
						'th' => T_('# of blogs'),
						'order' => '',
						'td_class' => 'right',
						'td' => 'n.a.',
					);

$Results->cols[] = array(
						'th' => T_('Skin Folder'),
						'order' => 'skin_folder',
						'td' => '$skin_folder$',
					);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => action_icon( T_('Edit skin properties...'), 'properties',
	                        '%regenerate_url( \'action\', \'skin_ID=$skin_ID$&amp;action=edit\')%' )
	                    .action_icon( T_('Uninstall this skin!'), 'delete',
	                        '%regenerate_url( \'action\', \'skin_ID=$skin_ID$&amp;action=delete\')%' ),
						);

  $Results->global_icon( T_('Install new skin...'), 'new', regenerate_url( 'action', 'action=new'), T_('Install'), 3, 4  );
}


// $fadeout_array = array( 'skin_ID' => array(6) );
$fadeout_array = NULL;

$Results->display( NULL, 'session' );



/*
 * $Log:
 */
?>
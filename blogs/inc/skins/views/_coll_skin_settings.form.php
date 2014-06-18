<?php
/**
 * This file implements the Skin properties form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package admin
 *
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _coll_skin_settings.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Skin
 */

global $Blog, $current_User;

$Form = new Form( NULL, 'skin_settings_checkchanges' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'collection' );
	$Form->hidden_ctrl();
	$Form->hidden( 'tab', 'skin' );
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'blog', $Blog->ID );

	$skin_type_params = array(
		'normal' => array(
			'skin_ID' => $Blog->get_setting( 'normal_skin_ID' ),
			'fieldset_title' => T_('Default skin'),
		),
		'mobile' => array(
			'skin_ID' => $Blog->get_setting( 'mobile_skin_ID', true ),
			'fieldset_title' => T_('Default mobile phone skin'),
		),
		'tablet' => array(
			'skin_ID' => $Blog->get_setting( 'tablet_skin_ID', true ),
			'fieldset_title' => T_('Default tablet skin'),
		),
	);

	foreach( $skin_type_params as $type => $params )
	{
		$fieldset_title_links = '<span class="floatright">&nbsp;'.action_icon( T_('Select another skin...'), 'edit', regenerate_url( 'action', 'ctrl=coll_settings&amp;skinpage=selection&amp;skin_type='.$type ), T_('Use a different skin').' &raquo;', 3, 4 ).'</span>';
		if( $current_User->check_perm( 'options', 'view' ) && ( $params[ 'skin_ID' ] ) )
		{ // display Reset params only when skin ID has a real value ( when skin_ID = 0 means it must be the same as the normal skin value )
			$fieldset_title_links .= ' <span class="floatright">'.action_icon( T_('Reset params'), 'reload', regenerate_url( 'action', 'ctrl=skins&amp;skin_ID='.$params[ 'skin_ID' ].'&amp;blog='.$Blog->ID.'&amp;action=reset&amp;'.url_crumb('skin') ), ' '.T_('Reset params'), 3, 4 ).'&nbsp;</span>';
		}
		display_skin_fieldset( $Form, $params[ 'skin_ID' ], array( 'fieldset_title' => $params[ 'fieldset_title' ], 'fieldset_links' => $fieldset_title_links ) );
	}

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>
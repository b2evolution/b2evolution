<?php
/**
 * This file implements the UI view for the user report form.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _user_report.form.php 7878 2014-12-23 11:54:05Z yura $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $display_mode, $user_tab, $admin_url;

/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;
/**
 * @var instance of User class
 */
global $current_User;

if( $display_mode != 'js' )
{
	// ------------------- PREV/NEXT USER LINKS -------------------
	user_prevnext_links( array(
			'user_tab'     => 'report'
		) );
	// ------------- END OF PREV/NEXT USER LINKS -------------------
}

$Form = new Form( $form_action, 'user_checkchanges' );

$form_title = '';
$form_class = 'fform';
$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";

if( $display_mode != 'js' )
{
	if( !$user_profile_only )
	{
		echo_user_actions( $Form, $edited_User, $action );
	}

	$form_text_title = T_( 'Report User' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, '', $form_text_title );
}

$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

$Form->hidden_ctrl();
$Form->hidden( 'user_tab', $user_tab );

$Form->begin_fieldset( T_('Report User'), array( 'class'=>'fieldset clear' ) );

user_report_form( array(
		'Form'       => $Form,
		'user_ID'    => $edited_User->ID,
		'crumb_name' => 'user',
		'cancel_url' => $admin_url.'?ctrl=user&amp;user_tab='.$user_tab.'&amp;action=remove_report&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb( 'user' ),
	) );

if( $display_mode == 'js' )
{ // Display a close link for popup window
	echo '<div class="center" style="margin-top:32px">'.action_icon( T_('Close this window'), 'close', '', ' '.T_('Close this window'), 3, 4, array( 'id' => 'close_button', 'class' => 'small' ) ).'</div>';
}

$Form->end_fieldset();

$Form->end_form();

?>
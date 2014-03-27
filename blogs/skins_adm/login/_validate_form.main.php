<?php
/**
 * This is the account validation form. It gets included if the user needs to validate his account.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// init force request new email address param
$force_request = param( 'force_request', 'boolean', false );

// get last activation email timestamp from User Settings
$last_activation_email_date = $UserSettings->get( 'last_activation_email', $current_User->ID );

/**
 * Include page header:
 */
$page_title = T_( 'Account activation' );
$wrap_width = '500px';
if( $force_request || empty( $last_activation_email_date ) )
{
	$wrap_height = 235;
}
else
{
	$wrap_height = 410;
}
if( $current_User->grp_ID == 1 )
{
	$wrap_height += 100;
}
$wrap_height .= 'px';

require dirname(__FILE__).'/_html_header.inc.php';

display_activateinfo( array(
		'form_before' => str_replace( '$title$', $page_title, $form_before ),
		'form_after' => $form_after,
		'form_class'    => 'form-login',
		'form_template' => $login_form_params,
		'redirect_to'   => url_rel_to_same_host( $redirect_to, $secure_htsrv_url )
	) );

if( $current_User->grp_ID == 1 )
{ // allow admin users to validate themselves by a single click:

	echo str_replace( '$title$', $page_title, $form_before );

	$Form = new Form( $secure_htsrv_url.'login.php', 'form_validatemail', 'post', 'fieldset' );

	$Form->switch_template_parts( $login_form_params );

	$Form->begin_form( 'form-login' );

	$Form->add_crumb( 'validateform' );
	$Form->hidden( 'action', 'validatemail');
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $secure_htsrv_url) );
	$Form->hidden( 'reqID', 1 );
	$Form->hidden( 'sessID', $Session->ID );

	echo '<p>'.sprintf( T_('Since you are an admin user, you can activate your account (%s) by a single click.' ), $current_User->email ).'</p>';
	// TODO: the form submit value is too wide (in Konqueror and most probably in IE!)
	$Form->end_form( array(array( 'name'=>'form_validatemail_admin_submit', 'value'=>T_('Activate my account!'), 'class'=>'ActionButton' )) ); // display hidden fields etc

	echo $form_after;
}
?>

<div class="form-login-links floatright">
	<?php
	user_logout_link();
	?>
</div>

<?php
require dirname(__FILE__).'/_html_footer.inc.php';

?>
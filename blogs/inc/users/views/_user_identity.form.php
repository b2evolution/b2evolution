<?php
/**
 * This file implements the UI view for the user properties.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of UserSettings class
 */
global $UserSettings;
/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var instance of User class
 */
global $current_User;
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

$GroupCache = & get_GroupCache();
$group_msg_perm = $GroupCache->get_option_array( 'check_messaging_perm' );

// User Groups dropdown list handler
// Depending on the group it may be impossible for a user to access private messages
?>
<script type="text/javascript">
	function user_group_changed()
	{
		var val = jQuery( '#edited_user_grp_ID' ).val();
		if( val == null )
		{ // there is no groups drop down list
			return;
		}

		var perms = [];
		var msgform = 0;

		<?php
		foreach( $group_msg_perm as $key => $value )
		{ // set groups permissions
			echo 'perms['.$key.'] = '.($value ? 'true' : 'false').';';
		}
		echo 'msgform = '.$edited_User->get( 'allow_msgform' );
		?>

		if( perms[val] )
		{ // private messages are allowed in selected group - enable option
			if( $( '.checkbox[name=PM]' ).attr('disabled') )
			{
				$( '.checkbox[name=PM]' ).removeAttr('disabled');
				$( '#label_for_PM_1' ).removeAttr('disabled');
				if( msgform % 2 == 1 )
				{ // if user allows PM check PM box
					$( '.checkbox[name=PM]' ).attr('checked', true );
				}
			}
		}
		else
		{ // private messages are not allowed in selected group - disable option
			if( ! $( '.checkbox[name=PM]' ).attr('disabled') )
			{
				// uncheck PM box, because it is not a valid option
				$( '.checkbox[name=PM]' ).attr('checked', false );
				$( '.checkbox[name=PM]' ).attr('disabled','disabled');
				$( '#label_for_PM_1' ).attr('disabled','disabled');
			}
		}
	}

	// Identity shown dropdown list handler
	// init variables
	var idmodes = [];
	var laquo = String.fromCharCode(171);
	var raquo = String.fromCharCode(187);
	idmodes["nickname"] = " " + laquo + "<?php echo T_( 'Nickname' ); ?>" + raquo;
	idmodes["login"] = " " + laquo + "<?php echo T_( 'Login' ); ?>" + raquo;
	idmodes["firstname"] = " " + laquo + "<?php echo T_( 'First name' ); ?>" + raquo;
	idmodes["lastname"] = " " + laquo + "<?php echo T_( 'Last name' ); ?>" + raquo;
	idmodes["namefl"] = " " + laquo + "<?php echo T_( 'First name' ).' '.T_( 'Last name' ); ?>" + raquo;
	idmodes["namelf"] = " " + laquo + "<?php echo T_( 'Last name' ).' '.T_( 'First name' ); ?>" + raquo;

	// Identity fields on change fucntion
	function idmodes_onchange( fieldname )
	{
		var fieldText = jQuery( '#edited_user_' + fieldname ).val();
		if( fieldText == "" )
		{
			fieldText = "-";
		}
		jQuery( '#edited_user_idmode option[value="' + fieldname + '"]' ).text( fieldText + idmodes[fieldname] );
	}

	// Handle Identity shown composite fields (-First name Last name- and -Last name First name-)
	function name_onchange()
	{
		var firstName = jQuery( '#edited_user_firstname' ).val();
		var lastName = jQuery( '#edited_user_lastname' ).val();
		jQuery( '#edited_user_idmode option[value="namefl"]' ).text( firstName + " " + lastName + idmodes["namefl"] );
		jQuery( '#edited_user_idmode option[value="namelf"]' ).text( lastName + " " + firstName + idmodes["namelf"] );
	}

	// Add more fields when click on the link
	function add_more_fields()
	{
		var j = jQuery("#add_more_fields").parent().parent().siblings("fieldset[id^=ffield_new_uf_val]").length;
		for( i = j + 1; i < j + 4; i++ ){
			var strHtml = jQuery("#add_more_fields").parent().parent().prev().html();
			strHtml = strHtml.replace(/new_uf_type_\d+/, "new_uf_type_" + i).replace(/new_uf_val_\d+/g, "new_uf_val_" + i)
				.replace(/selected=\"selected\"/, "");
			strHtml = '<fieldset id="ffield_new_uf_val_' + i + '">' + strHtml + '</fieldset>';
			jQuery("#add_more_fields").parent().parent().before(strHtml);
			area2input(jQuery("#new_uf_val_" + i));
			jQuery("#new_uf_val_" + i).val("").next().text("");
		}
		jQuery("input[name=new_fields_num]").val(j+3);
		inputhint();
	}


	// Switch from input to textarea
	function input2area(el) 
	{
		if (el) {
			strHtml = '<textarea cols="25" rows="3" name="' + el.attr("name") + '" id="' + el.attr("id")
				+ '" >' + el.val().replace(/\|/g, "\n") + '</textarea>';
			el.replaceWith(strHtml);
		}
	}

	// Switch from textarea to input
	function area2input(el) 
	{
		if (el) {
			strHtml = '<input type="text" class="form_text_input" size="30" maxlength="255" name="'
				+ el.attr("name") + '" id="' + el.attr("id") + '" value="' + el.val().replace(/\n/g, "|") + '"></textarea>';
			el.replaceWith(strHtml);
		}
	}

	// Switch between input and textarea automatically
	function inputhint() 
	{
		$('select[name*="type"]').change(function(e){
			var group = $(this).children("optgroup").children('option[value='+$(this).val()+']').parent().attr("label");
			switch(group) {
				case '<?php echo T_("Instant Messaging") ?>' :
					{
						$(this).parent().next().children("span").text("Please enter an instant messaging account name.");
						area2input($(this).parent().next().children("textarea"));
						break;
					}
				case '<?php echo T_("Phone") ?>' :
					{
						$(this).parent().next().children("span").text("Please enter a phone number.");
						area2input($(this).parent().next().children("textarea"));
						break;
					}
				case '<?php echo T_("Web") ?>' :
					{
						$(this).parent().next().children("span").text("Please enter a web page address like http://www.abc.com/");
						area2input($(this).parent().next().children("textarea"));
						break;
					}
				case '<?php echo T_("Organization") ?>' :
					{
						$(this).parent().next().children("span").text(" ");
						area2input($(this).parent().next().children("textarea"));
						break;
					}
				case '<?php echo T_("Address") ?>' :
					{
						$(this).parent().next().children("span").text("Please enter a postal address.");
						input2area($(this).parent().next().children("input"));
						break;
					}
				default :
					$(this).parent().next().children("span").text(" ");
					area2input($(this).parent().next().children("textarea"));
			}
		});
	}

	jQuery(function()
	{
		// Initialize the new fields
		$('select[name*="type"]').each(function(){
			var group = $(this).children("optgroup").children('option[value='+$(this).val()+']').parent().attr("label");
			switch(group) {
				case '<?php echo T_("Instant Messaging") ?>' :
					{
						$(this).parent().next().children("span").text("Please enter an instant messaging account name.");
						break;
					}
				case '<?php echo T_("Phone") ?>' :
					{
						$(this).parent().next().children("span").text("Please enter a phone number.");
						break;
					}
				case '<?php echo T_("Web") ?>' :
					{
						$(this).parent().next().children("span").text("Please enter a web page address like http://www.abc.com/");
						break;
					}
				case '<?php echo T_("Organization") ?>' :
					{
						$(this).parent().next().children("span").text(" ");
						break;
					}
				case '<?php echo T_("Address") ?>' :
					{
						$(this).parent().next().children("span").text("Please enter a postal address.");
						input2area($(this).parent().next().children("input"));
						break;
					}
			}
		});

		inputhint();

		$('#user_checkchanges').submit(function(){
			$("textarea").each(function(){
				area2input($(this));
			});
		});
	});


</script>
<?php

// Begin payload block:
//$this->disp_payload_begin();

$Form = new Form( $form_action, 'user_checkchanges' );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	if( $edited_User->ID == 0 )
	{
		$form_title = T_('Edit user profile');
	}
	else
	{
		$form_title = sprintf( T_('Edit profile for user %s'), $edited_User->dget('fullname').' &laquo;'.$edited_User->dget('login').'&raquo;' );
	}
	$form_class = 'fform';
}
else
{
	$form_title = '';
	$form_class = 'bComment';
}

	$Form->begin_form( $form_class, $form_title );

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'profile' );
	$Form->hidden( 'identity_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );

	/***************  User permissions  **************/

$Form->begin_fieldset( T_('User permissions').get_manual_link('user_permissions'), array( 'class'=>'fieldset clear' ) );

$edited_User->get_Group();

$has_full_access = $current_User->check_perm( 'users', 'edit' );

if( $edited_User->ID != 1 && $has_full_access )
{	// This is not Admin and we're not restricted: we're allowed to change the user group:
	$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get('newusers_grp_ID') : $edited_User->Group->ID;
	$GroupCache = & get_GroupCache();
	$Form->select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_('User group'), '', false, '', 'get_option_list', 'user_group_changed()' );
}
else
{
	echo '<input type="hidden" name="edited_user_grp_ID" value="'.$edited_User->Group->ID.'" />';
	$Form->info( T_('User group'), $edited_User->Group->dget('name') );
}

$field_note = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://manual.b2evolution.net/User_levels"' );
if( $action != 'view' && $has_full_access )
{
	$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, T_('User level'), $field_note, array( 'required' => true ) );
}
else
{
	$Form->info_field( T_('User level'), $edited_User->get('level'), array( 'note' => $field_note ) );
}

$Form->end_fieldset();

	/***************  Email communications  **************/

$Form->begin_fieldset( T_('Email communications') );

$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'">'.get_icon( 'email', 'imgtag', array('title'=>T_('Send an email')) ).'</a>';

if( $action != 'view' )
{ // We can edit the values:

	$Form->text_input( 'edited_user_email', $edited_User->email, 30, T_('Email'), $email_fieldnote, array( 'maxlength' => 100, 'required' => true ) );
	if( $has_full_access )
	{ // user has "edit users" perms:
		$Form->checkbox( 'edited_user_validated', $edited_User->get('validated'), T_('Validated email'), T_('Has this email address been validated (through confirmation email)?') );
	}
	else
	{ // info only:
		$Form->info( T_('Validated email'), ( $edited_User->get('validated') ? T_('yes') : T_('no') ), T_('Has this email address been validated (through confirmation email)?') );
	}
	$messaging_options = array(
		array( 'PM', 1, T_( 'Allow others to send me private messages' ), ( $edited_User->get( 'allow_msgform' ) % 2 == 1 ) ),
		array( 'email', 2, T_( 'Allow others to send me emails through a message form (email address will never be displayed)' ),  $edited_User->get( 'allow_msgform' ) > 1 ) );
	$Form->checklist( $messaging_options, 'edited_user_msgform', T_('Message form') );
	$notify_options = array(
		array( 'edited_user_notify', 1, T_( 'Notify me by email whenever a comment is published on one of <strong>my</strong> posts.' ), $edited_User->get( 'notify' ) ),
		array( 'edited_user_notify_moderation', 2, T_( 'Notify me by email whenever a comment is awaiting moderation on one of <strong>my</strong> blogs.' ), $edited_User->get( 'notify_moderation' ) ) );
	$Form->checklist( $notify_options, 'edited_user_notification', T_( 'Notifications' ) );

}
else
{ // display only

	$Form->info( T_('Email'), $edited_User->get('email'), $email_fieldnote );
	$Form->info( T_('Validated email'), ( $edited_User->get('validated') ? T_('yes') : T_('no') ), T_('Has this email address been validated (through confirmation email)?') );
	$Form->info( T_('Message form'), ($edited_User->get('allow_msgform') ? T_('yes') : T_('no')) );
	$Form->info( T_('Notifications'), ($edited_User->get('notify') ? T_('yes') : T_('no')) );

  }

$Form->end_fieldset();

	/***************  Identity  **************/

$Form->begin_fieldset( T_('Identity') );

if( $action != 'view' )
{   // We can edit the values:

	$Form->text_input( 'edited_user_login', $edited_User->login, 20, T_('Login'), '', array( 'required' => true, 'onchange' => 'idmodes_onchange( "login" )' ) );
	$Form->text_input( 'edited_user_firstname', $edited_User->firstname, 20, T_('First name'), '', array( 'maxlength' => 50 ) );
	$Form->text_input( 'edited_user_lastname', $edited_User->lastname, 20, T_('Last name'), '', array( 'maxlength' => 50 ) );

	$nickname_editing = $Settings->get( 'nickname_editing' );
	if( ( $nickname_editing == 'edited-user' && $edited_User->ID == $current_User->ID ) || ( $nickname_editing != 'hidden' && $has_full_access ) )
	{
		$Form->text_input( 'edited_user_nickname', $edited_User->nickname, 20, T_('Nickname'), '', array( 'maxlength' => 50, 'required' => true, 'onchange' => 'idmodes_onchange( "nickname" )' ) );
	}
	else
	{
		$Form->hidden( 'edited_user_nickname', $edited_User->nickname );
	}

	$Form->select( 'edited_user_idmode', $edited_User->get( 'idmode' ), array( &$edited_User, 'callback_optionsForIdMode' ), T_('Identity shown') );

	$CountryCache = & get_CountryCache();
	$Form->select_input_object( 'edited_user_ctry_ID', $edited_User->ctry_ID, $CountryCache, 'Country', array( 'required' => !$has_full_access, 'allow_none' => $has_full_access ) );

	$Form->checkbox( 'edited_user_showonline', $edited_User->get('showonline'), T_('Show online'), T_('Check this to be displayed as online when visiting the site.') );

	if( $Settings->get( 'registration_require_gender' ) != 'hidden' )
	{
		$Form->radio( 'edited_user_gender', $edited_User->get('gender'), array(
					array( 'M', T_('Male') ),
					array( 'F', T_('Female') ),
				), T_('Gender') );
	}
}
else
{ // display only

	if( $Settings->get('allow_avatars') )
	{
		$Form->info( T_('Profile picture'), $edited_User->get_avatar_imgtag() );
	}

	$Form->info( T_('Login'), $edited_User->get('login') );
	$Form->info( T_('First name'), $edited_User->get('firstname') );
	$Form->info( T_('Last name'), $edited_User->get('lastname') );
	$Form->info( T_('Nickname'), $edited_User->get('nickname') );
	$Form->info( T_('Identity shown'), $edited_User->get('preferredname') );
	$Form->info( T_('Country'), $edited_User->get_country_name() );
	$Form->info( T_('Show online'), ($edited_User->get('showonline')) ? T_('yes') : T_('no') );

	if( $Settings->get( 'registration_require_gender' ) != 'hidden' )
	{
		$user_gender = $edited_User->get( 'gender' );
		if( ! empty( $user_gender ) )
		{
			$Form->info( T_('Gender'), ( $user_gender == 'M' ) ? T_( 'Male' ) : T_( 'Female' ) );
		}
	}
	$Form->info( T_('Multiple sessions'), ($UserSettings->get('login_multiple_sessions', $edited_User->ID) ? T_('Allowed') : T_('Forbidden')) );
}

$Form->end_fieldset();

	/***************  Password  **************/

if( empty( $edited_User->ID ) && $action != 'view' )
{ // We can edit the values:

	$Form->begin_fieldset( T_('Password') );
		$Form->password_input( 'edited_user_pass1', '', 20, T_('New password'), array( 'maxlength' => 50, 'required' => true, 'autocomplete'=>'off' ) );
		$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'note'=>sprintf( T_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'required' => true, 'autocomplete'=>'off' ) );
	$Form->end_fieldset();
}

	/***************  Multiple sessions  **************/

if( empty( $edited_User->ID ) && $action != 'view' )
{	// New user will be created with default multiple_session setting

	$multiple_sessions = $Settings->get( 'multiple_sessions' );
	if( $multiple_sessions == 'userset_default_yes' || ( $has_full_access && $multiple_sessions == 'adminset_default_yes' ) )
	{
		$Form->hidden( 'edited_user_set_login_multiple_sessions', 1 );
	}
	else
	{
		$Form->hidden( 'edited_user_set_login_multiple_sessions', 0 );
	}
}

	/***************  Additional info  **************/


$Form->begin_fieldset( T_('Additional info') );

if( $edited_User->ID != 0 )
{ // We're NOT creating a new user:
	$Form->info_field( T_('ID'), $edited_User->ID );

	$Form->info_field( T_('Posts'), $edited_User->get_num_posts() );

	$Form->info_field( T_('Created on'), $edited_User->dget('datecreated') );
	$Form->info_field( T_('From IP'), $edited_User->dget('ip') );
	$Form->info_field( T_('From Domain'), $edited_User->dget('domain') );
	$Form->info_field( T_('With Browser'), $edited_User->dget('browser') );
}

if( ($url = $edited_User->get('url')) != '' )
{
	if( !preg_match('#://#', $url) )
	{
		$url = 'http://'.$url;
	}
	$url_fieldnote = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
}
else
	$url_fieldnote = '';

if( $edited_User->get('icq') != 0 )
	$icq_fieldnote = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$edited_User->get('icq').'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Search on ICQ.com')) ).'</a>';
else
	$icq_fieldnote = '';

if( $edited_User->get('aim') != '' )
	$aim_fieldnote = '<a href="aim:goim?screenname='.$edited_User->get('aim').'&amp;message=Hello">'.get_icon( 'play', 'imgtag', array('title'=>T_('Instant Message to user')) ).'</a>';
else
	$aim_fieldnote = '';


if( $action != 'view' )
{ // We can edit the values:

	if( $has_full_access )
	{
		$Form->text_input( 'edited_user_source', $edited_User->source, 30, T_('Source'), '', array( 'maxlength' => 30 ) );
	}
	$Form->text_input( 'edited_user_url', $edited_User->url, 30, T_('URL'), $url_fieldnote, array( 'maxlength' => 100 ) );
	$Form->text_input( 'edited_user_icq', $edited_User->icq, 30, T_('ICQ'), $icq_fieldnote, array( 'maxlength' => 10 ) );
	$Form->text_input( 'edited_user_aim', $edited_User->aim, 30, T_('AIM'), $aim_fieldnote, array( 'maxlength' => 50 ) );
	$Form->text_input( 'edited_user_msn', $edited_User->msn, 30, T_('MSN IM'), '', array( 'maxlength' => 100 ) );
	$Form->text_input( 'edited_user_yim', $edited_User->yim, 30, T_('YahooIM'), '', array( 'maxlength' => 50 ) );

}
else
{ // display only

	$Form->info( T_('URL'), $edited_User->get('url'), $url_fieldnote );
	$Form->info( T_('ICQ'), $edited_User->get('icq', 'formvalue'), $icq_fieldnote );
	$Form->info( T_('AIM'), $edited_User->get('aim'), $aim_fieldnote );
	$Form->info( T_('MSN IM'), $edited_User->get('msn') );
	$Form->info( T_('YahooIM'), $edited_User->get('yim') );

  }

$Form->end_fieldset();

	/***************  Experimental  **************/

$Form->begin_fieldset( T_('Experimental') );

// This totally needs to move into User object
global $DB;

// Get original user id for duplicate
if ($edited_User->ID == 0) {
	$user_id = param( 'user_ID', 'string', "" );
	if ($user_id == "" || $user_id == 0 )
		$user_id = param( 'orig_user_ID', 'string', "" );
	if ($user_id == "" || $user_id == 0 )
		$user_id = $edited_User->ID;
} else
	$user_id = $edited_User->ID;

// Get existing userfields:
$userfields = $DB->get_results( '
	SELECT uf_ID, ufdf_ID, ufdf_type, ufdf_name, uf_varchar
		FROM T_users__fields LEFT JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID
	 WHERE uf_user_ID = '.$user_id.'
	 ORDER BY uf_ID' );

 foreach( $userfields as $userfield )
{
	switch( $userfield->ufdf_ID )
	{
		case 10200:
			$field_note = '<a href="aim:goim?screenname='.$userfield->uf_varchar.'&amp;message=Hello">'.get_icon( 'play', 'imgtag', array('title'=>T_('Instant Message to user')) ).'</a>';
			break;

		case 10300:
			$field_note = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$userfield->uf_varchar.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Search on ICQ.com')) ).'</a>';
			break;

		default:
			if( $userfield->ufdf_ID >= 100000 && $userfield->ufdf_ID < 200000 )
			{
				$url = $userfield->uf_varchar;
				if( !preg_match('#://#', $url) )
				{
					$url = 'http://'.$url;
				}
				$field_note = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
			}
			else
			{
				$field_note = '';
			}
	}

	$uf_val = param( 'uf_'.$userfield->uf_ID, 'string', NULL );
	if( is_null( $uf_val ) )
	{	// No value submitted yet, get DB val:
		$uf_val = $userfield->uf_varchar;
	}

	// Display existing field:
	$Form->text_input( 'uf_'.$userfield->uf_ID, $uf_val, 50, $userfield->ufdf_name, $field_note, array( 'maxlength' => 255 ) );
}

// Get recommended userfields:
$userfields = $DB->get_results( '
	SELECT '. $user_id .' as uf_ID, ufdf_ID, ufdf_type, ufdf_name, "" as uf_varchar
	from T_users__fielddefs
	where ufdf_required = "recommend" and ufdf_ID not in
		( select uf_ufdf_ID
			from T_users__fields
			where uf_user_ID = '. $user_id .'
		) order by ufdf_ID' );

$i = 0;
foreach( $userfields as $userfield )
{
	$i++;
	switch( $userfield->ufdf_ID )
	{
		case 10200:
			$field_note = '<a href="aim:goim?screenname='.$userfield->uf_varchar.'&amp;message=Hello">'.get_icon( 'play', 'imgtag', array('title'=>T_('Instant Message to user')) ).'</a>';
			break;

		case 10300:
			$field_note = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$userfield->uf_varchar.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Search on ICQ.com')) ).'</a>';
			break;

		default:
			if( $userfield->ufdf_ID >= 100000 && $userfield->ufdf_ID < 200000 )
			{
				$url = $userfield->uf_varchar;
				if( !preg_match('#://#', $url) )
				{
					$url = 'http://'.$url;
				}
				$field_note = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
			}
			else
			{
				$field_note = '';
			}
	}

	$uf_val = param( 'uf_rec_'.$i, 'string', NULL );
	if( is_null( $uf_val ) )
	{	// No value submitted yet, get DB val:
		$uf_val = $userfield->uf_varchar;
	}

	// Display existing field:
	$Form->text_input( 'uf_rec_'.$i, $uf_val, 50, $userfield->ufdf_name, $field_note, array( 'maxlength' => 255 ) );
}

// Get list of possible field types:
// TODO: use userfield manipulation functions
$userfielddefs = $DB->get_results( '
	SELECT ufdf_ID, ufdf_type, ufdf_name
		FROM T_users__fielddefs
	 ORDER BY ufdf_ID' );
// New fields:
for( $i=1; $i<=3; $i++ )
{
	$label = '<select name="new_uf_type_'.$i.'"><option value="">'.T_('Add field...').'</option><optgroup label="'.T_('Instant Messaging').'">';
	foreach( $userfielddefs as $fielddef )
	{
		// check for group header:
		switch( $fielddef->ufdf_ID )
		{
			case 50000:
				$label .= "\n".'</optgroup><optgroup label="'.T_('Phone').'">';
				break;
			case 100000:
				$label .= "\n".'</optgroup><optgroup label="'.T_('Web').'">';
				break;
			case 200000:
				$label .= "\n".'</optgroup><optgroup label="'.T_('Organization').'">';
				break;
			case 300000:
				$label .= "\n".'</optgroup><optgroup label="'.T_('Address').'">';
				break;
		}
		$label .= "\n".'<option value="'.$fielddef->ufdf_ID.'"';
		if( param( 'new_uf_type_'.$i, 'string', '' ) == $fielddef->ufdf_ID )
		{	// We had selected this type before getting an error:
			$label .= ' selected="selected"';
		}
		$label .= '>'.$fielddef->ufdf_name.'</option>';
	}
	$label .= '</optgroup></select>';

	$Form->text_input( 'new_uf_val_'.$i, param( 'new_uf_val_'.$i, 'string', '' ), 30, $label, ' ', array('maxlength' => 255, 'clickable_label'=>false) );
}

$Form->info( '', '<a id="add_more_fields" href="javascript:add_more_fields()">+ add more fields</a>' );
$Form->hidden( 'new_fields_num', '3' );
$Form->hidden( 'orig_user_ID', $user_id );

$Form->end_fieldset();

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$action_buttons = array(
		array( '', 'actionArray[update]', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) );
	if( $is_admin )
	{
		// dh> TODO: Non-Javascript-confirm before trashing all settings with a misplaced click.
		$action_buttons[] = array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'ResetButton',
			'onclick' => "return confirm('".TS_('This will reset all your user settings.').'\n'.TS_('This cannot be undone.').'\n'.TS_('Are you sure?')."');" );
	}
	$Form->buttons( $action_buttons );
}


$Form->end_form();

?>
<script type="text/javascript">
	// call the users group dropdown list handler
	user_group_changed();

	// handle firstname and lastname change in the Identity shown dropdown list
	jQuery( '#edited_user_firstname' ).change( function()
	{
		// change First name text
		idmodes_onchange( "firstname" );
		// change -First name Last name- and -Last name First name- texts
		name_onchange();
	} );
	jQuery( '#edited_user_lastname' ).change( function()
	{
		// change Last name text
		idmodes_onchange( "lastname" );
		// change -First name Last name- and -Last name First name- texts
		name_onchange();
	} );
</script>
<?php

/*
 * $Log$
 * Revision 1.33  2011/09/06 00:54:38  fplanque
 * i18n update
 *
 * Revision 1.32  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.31  2011/09/04 21:32:17  fplanque
 * minor MFB 4-1
 *
 * Revision 1.30  2011/08/30 06:45:34  efy-james
 * User field type intelligence
 *
 * Revision 1.29  2011/08/29 08:51:14  efy-james
 * Default / mandatory additional fields
 *
 * Revision 1.28  2011/08/26 08:34:37  efy-james
 * Duplicate additional fields when duplicating user
 *
 * Revision 1.27  2011/08/26 04:06:30  efy-james
 * Add extra addional fields on user
 *
 * Revision 1.26  2011/08/25 10:52:04  efy-james
 * Add extra addional fields on user
 *
 * Revision 1.25  2011/08/25 09:53:32  efy-james
 * Add extra addional fields on user
 *
 * Revision 1.24  2011/05/19 17:47:07  efy-asimo
 * register for updates on a specific blog post
 *
 * Revision 1.23  2011/05/13 07:24:26  efy-asimo
 * dinamically update "Identiy shown" select options
 *
 * Revision 1.22  2011/05/11 07:11:52  efy-asimo
 * User settings update
 *
 * Revision 1.21  2011/04/06 13:30:56  efy-asimo
 * Refactor profile display
 *
 * Revision 1.20  2011/02/17 14:56:38  efy-asimo
 * Add user source param
 *
 * Revision 1.19  2010/11/24 14:55:30  efy-asimo
 * Add user gender
 *
 * Revision 1.18  2010/11/03 19:44:15  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.17  2010/10/17 18:53:04  sam2kb
 * Added a link to delete edited user
 *
 * Revision 1.16  2010/07/26 06:52:27  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.15  2010/07/14 09:06:14  efy-asimo
 * todo fp>asimo modifications
 *
 * Revision 1.14  2010/05/06 09:24:14  efy-asimo
 * Messaging options - fix
 *
 * Revision 1.13  2010/05/05 09:37:08  efy-asimo
 * add _login.disp.php and change groups&users messaging perm
 *
 * Revision 1.12  2010/04/16 10:42:11  efy-asimo
 * users messages options- send private messages to users from front-office - task
 *
 * Revision 1.11  2010/03/01 07:52:51  efy-asimo
 * Set manual links to lowercase
 *
 * Revision 1.10  2010/02/23 05:01:46  sam2kb
 * minor
 *
 * Revision 1.9  2010/02/14 14:18:39  efy-asimo
 * insert manual links
 *
 * Revision 1.8  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.7  2010/01/30 18:55:35  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.6  2010/01/03 16:28:35  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.5  2009/11/21 13:39:05  efy-maxim
 * 'Cancel editing' fix
 *
 * Revision 1.4  2009/11/21 13:31:59  efy-maxim
 * 1. users controller has been refactored to users and user controllers
 * 2. avatar tab
 * 3. jQuery to show/hide custom duration
 *
 * Revision 1.3  2009/10/28 14:26:24  efy-maxim
 * allow selection of None/NULL for country
 *
 * Revision 1.2  2009/10/28 13:41:58  efy-maxim
 * default multiple sessions settings
 *
 * Revision 1.1  2009/10/28 10:02:42  efy-maxim
 * rename some php files
 *
 */
?>
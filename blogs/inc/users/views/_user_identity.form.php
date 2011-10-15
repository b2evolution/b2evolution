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

?>
<script type="text/javascript">
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
			strHtml = '<input type="text" class="form_text_input" size="40" maxlength="255" name="'
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
	});


</script>
<?php

$has_full_access = $current_User->check_perm( 'users', 'edit' );
$new_user_creating = ( $edited_User->ID == 0 );

$Form = new Form( $form_action, 'user_checkchanges' );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	if( $new_user_creating )
	{
		$form_title = T_('Edit user profile');
	}
	else
	{
		$form_title = get_usertab_header( $edited_User, 'profile', T_( 'Edit profile' ) );
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

if( $new_user_creating )
{
	$current_User->check_perm( 'users', 'edit', true );
	$edited_User->get_Group();

	$Form->begin_fieldset( T_( 'New user' ), array( 'class' => 'fieldset clear' ) );

	$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get( 'newusers_grp_ID' ) : $edited_User->Group->ID;
	$GroupCache = & get_GroupCache();
	$Form->select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_( 'User group' ) );

	$field_note = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://manual.b2evolution.net/User_levels"' );
	$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, T_('User level'), $field_note, array( 'required' => true ) );

	$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'">'.get_icon( 'email', 'imgtag', array('title'=>T_('Send an email')) ).'</a>';
	$Form->text_input( 'edited_user_email', $edited_User->email, 30, T_('Email'), $email_fieldnote, array( 'maxlength' => 100, 'required' => true ) );
	$Form->checkbox( 'edited_user_validated', $edited_User->get('validated'), T_('Validated email'), T_('Has this email address been validated (through confirmation email)?') );

	$Form->end_fieldset();
}

	/***************  Identity  **************/

$Form->begin_fieldset( T_('Identity') );

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

	if( $Settings->get( 'registration_require_gender' ) != 'hidden' )
	{
		$Form->radio( 'edited_user_gender', $edited_User->get('gender'), array(
					array( 'M', T_('A man') ),
					array( 'F', T_('A woman') ),
				), T_('I am') );
	}

	$CountryCache = & get_CountryCache();
	$Form->select_country( 'edited_user_ctry_ID', $edited_User->ctry_ID, $CountryCache, T_('Country'), array( 'required' => !$has_full_access, 'allow_none' => $has_full_access ) );

	$Form->text_input( 'edited_user_postcode', $edited_User->postcode, 12, T_('ZIP/Postcode'), '', array( 'maxlength' => 12 ) );

	$Form->text_input( 'edited_user_age_min', $edited_User->age_min, 3, T_('My age group: from'), '', array( 'number' => true ) );
	$Form->text_input( 'edited_user_age_max', $edited_User->age_max, 3, T_('to'), '', array( 'number' => true ) );

	if( $new_user_creating )
	{
		$Form->text_input( 'edited_user_source', $edited_User->source, 30, T_('Source'), '', array( 'maxlength' => 30 ) );
	}
	$Form->text_input( 'edited_user_url', $edited_User->url, 40, T_('URL'), $url_fieldnote, array( 'maxlength' => 100 ) );
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

	if( $Settings->get( 'registration_require_gender' ) != 'hidden' )
	{
		$user_gender = $edited_User->get( 'gender' );
		if( ! empty( $user_gender ) )
		{
			$Form->info( T_('Gender'), $edited_User->get_gender() );
		}
	}

	$Form->info( T_('Country'), $edited_User->get_country_name() );
	$Form->info( T_('My ZIP/Postcode'), $edited_User->get('postcode') );
	$Form->info( T_('My age group'), $edited_User->get('age_min') );
	$Form->info( T_('to'), $edited_User->get('age_max') );

	$Form->info( T_('URL'), $edited_User->get('url'), $url_fieldnote );
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

// This totally needs to move into User object
global $DB;

// Get original user id for duplicate
if ($edited_User->ID == 0)
{
	$user_id = param( 'user_ID', 'string', "" );
	if ($user_id == "" || $user_id == 0 )
		$user_id = param( 'orig_user_ID', 'string', "" );
	if ($user_id == "" || $user_id == 0 )
		$user_id = $edited_User->ID;
}
else
{
	$user_id = $edited_User->ID;
}

$userfields_new_sql = '';
$new_field_type = param( 'new_field_type', 'integer', 0 );
if( $new_field_type > 0 && $Messages->has_errors() )
{	// Means we want to add a new field (step 2)
	$userfields_new_sql = 'OR ufdf_ID = "'.$new_field_type.'"';
	// We save a new field type here to remmember it after submiting of the form
	$Form->hidden( 'new_field_type', $new_field_type );
}

// -------------------  Get existing userfields: -------------------------------
$userfields = $DB->get_results( '
	SELECT ufdf_ID, uf_ID, ufdf_type, ufdf_name, uf_varchar, ufdf_required
		FROM T_users__fields
			LEFT JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID
	WHERE uf_user_ID = '.$user_id.'

	UNION

	SELECT ufdf_ID, "0" AS uf_ID, ufdf_type, ufdf_name, "" AS uf_varchar, ufdf_required
		FROM T_users__fielddefs
	WHERE ( ufdf_required IN ( "recommended", "require" )
		AND ufdf_ID NOT IN ( SELECT uf_ufdf_ID FROM T_users__fields WHERE uf_user_ID = '.$user_id.' ) )
		'.$userfields_new_sql.'

	ORDER BY 1, 2 DESC' );

$uf_new_fields = param( 'uf_new', 'array' );
foreach( $userfields as $userfield )
{
	$uf_val = param( 'uf_'.$userfield->uf_ID, 'string', NULL );

	if( $userfield->uf_ID == '0' )
	{ // Set uf_ID for new (not saved) fields (recommended & require types)
		$userfield->uf_ID = 'new['.$userfield->ufdf_ID.']';
		if( isset( $uf_new_fields[$userfield->ufdf_ID] ) )
			$uf_val = $uf_new_fields[$userfield->ufdf_ID];
	}

	if( is_null( $uf_val ) )
	{ // No value submitted yet, get DB val:
		$uf_val = $userfield->uf_varchar;
	}

	switch( $userfield->ufdf_ID )
	{
		case 10200:
			$field_note = '<a href="aim:goim?screenname='.$userfield->uf_varchar.'&amp;message=Hello" class="action_icon">'.get_icon( 'play', 'imgtag', array('title'=>T_('Instant Message to user')) ).'</a>';
			break;

		case 10300:
			$field_note = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$userfield->uf_varchar.'" target="_blank" class="action_icon">'.get_icon( 'play', 'imgtag', array('title'=>T_('Search on ICQ.com')) ).'</a>';
			break;

		default:
			if( $userfield->ufdf_type == 'url' )
			{
				$url = $userfield->uf_varchar;
				if( !preg_match('#://#', $url) )
				{
					$url = 'http://'.$url;
				}
				$field_note = '<a href="'.$url.'" target="_blank" class="action_icon">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
			}
			else
			{
				$field_note = '';
			}
	}

	// Display existing field:
	if( $userfield->ufdf_type == 'text' )
	{
		$Form->textarea( 'uf_'.$userfield->uf_ID, $uf_val, 5, $userfield->ufdf_name, $field_note, 38, '', $userfield->ufdf_required == 'require' );
	}
	else
	{
		$field_params = array( 'maxlength' => 255 );
		if( $userfield->ufdf_required == 'require' )
		{
			$field_params['required'] = true;
		}
		$Form->text_input( 'uf_'.$userfield->uf_ID, $uf_val, 40, $userfield->ufdf_name, $field_note, $field_params );
	}
}

// ------------------- Add new field: -------------------------------
echo '<br />';

// Hidden field to detect that we press on the button 'Add'
$Form->hidden( 'action_new_field', '' );

$Form->output = false;
$button_add_field = $Form->button( array( 'type' => 'button', 'value' => 'Add', 'id' => 'button_add_field' ) );
$Form->output = true;

$Form->select( 'new_field_type', '', 'callback_options_user_new_fields', T_('Add a field of type'), $button_add_field );


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

	// Action for the button when we want to add a new field in the Additional info
	jQuery( '#button_add_field' ).click( function ()
	{
		if( $( 'input[name=new_field_type]' ).length > 0 )
		{	// Remove a hidden input which storing a current new field to add
			$( 'input[name=new_field_type]' ).remove();
		}
		// Set an actions fields to add a new field
		$( 'input[name=action_new_field]' ).val( 'add' )
																			 .after( '<input type="hidden" value="update" name="action" />' );
		$( '#user_checkchanges' ).submit();
	} );
</script>
<?php

/*
 * $Log$
 * Revision 1.53  2011/10/15 15:03:28  efy-yurybakh
 * Additional info fields - step 2
 *
 * Revision 1.52  2011/10/11 02:05:41  fplanque
 * i18n/wording cleanup
 *
 * Revision 1.51  2011/09/29 17:18:17  efy-yurybakh
 * remove a pipes in textarea
 *
 * Revision 1.50  2011/09/29 09:50:51  efy-yurybakh
 * User fields
 *
 * Revision 1.49  2011/09/28 10:50:00  efy-yurybakh
 * User additional info fields
 *
 * Revision 1.48  2011/09/27 17:31:19  efy-yurybakh
 * User additional info fields
 *
 * Revision 1.47  2011/09/26 08:51:47  efy-vitalij
 * add select_country item
 *
 * Revision 1.46  2011/09/23 11:57:28  efy-vitalij
 * add admin functionality to password change form and edit validate messages in password edit form
 *
 * Revision 1.45  2011/09/18 00:58:44  fplanque
 * forms cleanup
 *
 * Revision 1.44  2011/09/15 22:34:09  fplanque
 * cleanup
 *
 * Revision 1.43  2011/09/15 20:51:09  efy-abanipatra
 * user postcode,age_min,age_mac added.
 *
 * Revision 1.42  2011/09/15 08:58:46  efy-asimo
 * Change user tabs display
 *
 * Revision 1.41  2011/09/14 23:42:16  fplanque
 * moved icq aim yim msn to additional userfields
 *
 * Revision 1.40  2011/09/14 22:18:10  fplanque
 * Enhanced addition user info fields
 *
 * Revision 1.39  2011/09/14 07:54:20  efy-asimo
 * User profile refactoring - modifications
 *
 * Revision 1.38  2011/09/12 06:41:06  efy-asimo
 * Change user edit forms titles
 *
 * Revision 1.37  2011/09/12 05:28:47  efy-asimo
 * User profile form refactoring
 *
 * Revision 1.36  2011/09/10 22:48:41  fplanque
 * doc
 *
 * Revision 1.35  2011/09/09 06:34:16  sam2kb
 * minor
 *
 * Revision 1.34  2011/09/06 03:25:41  fplanque
 * i18n update
 *
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
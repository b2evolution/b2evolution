<?php
/**
 * This file implements the UI view for the user properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

$allowed_to_edit = ( $current_User->check_perm( 'users', 'edit' )
											|| ($user_profile_only && $edited_User->ID == $current_User->ID) );
?>
<div class="panelblock">

<?php
if( $current_User->check_perm( 'users', 'view' ) )
{
	?>
	<div style="float:right">
		<?php
		if( $user > 0 )
		{ // Links to next/previous user
			$prevuserid = $nextuserid = 0;

			$query = 'SELECT MAX(ID), MIN(ID) FROM T_users';
			$uminmax = $DB->get_row( $query, ARRAY_A );

			foreach( $userlist as $fuser )
			{ // find prev/next id
				if( $fuser->ID < $user )
				{
					if( $fuser->ID > $prevuserid )
					{
						$prevuserid = $fuser->ID;
						$prevuserlogin = $fuser->user_login;
					}
				}
				elseif( $fuser->ID > $user )
				{
					if( $fuser->ID < $nextuserid || $nextuserid == 0 )
					{
						$nextuserid = $fuser->ID;
						$nextuserlogin = $fuser->user_login;
					}
				}
			}

			echo ( $user != $uminmax['MIN(ID)'] ) ? '<a title="'.T_('first user').'" href="?user='.$uminmax['MIN(ID)'].'">[&lt;&lt;]</a>' : '[&lt;&lt;]';
			echo ( $prevuserid ) ? '<a title="'.T_('previous user').' ('.$prevuserlogin.')" href="?user='.$prevuserid.'">[&lt;]</a>' : '[&lt;]';
			echo ( $nextuserid ) ? '<a title="'.T_('next user').' ('.$nextuserlogin.')" href="?user='.$nextuserid.'">[&gt;]</a>' : '[&gt;]';
			echo ( $user != $uminmax['MAX(ID)'] ) ? '<a title="'.T_('last user').'" href="?user='.$uminmax['MAX(ID)'].'">[&gt;&gt;]</a>' : '[&gt;&gt;]';
		}
		?>
		<a title="<?php echo T_('Close user profile'); ?>" href="b2users.php"><?php
			echo getIcon( 'close', 'imgtag', array( 'title' => T_('Close user profile') ) );
			?></a>
	</div>
	<?php
}


$Form = & new Form( 'b2users.php', 'form' );

if( $edited_User->get('ID') == 0 )
{
	$Form->begin_form( 'fform', T_('Create new user profile') );
}
else
{
	$Form->begin_form( 'fform', T_('Profile for:').' '.$edited_User->dget('firstname').' '.$edited_User->dget('lastname')
				.' ['.$edited_User->dget('login').']' );
}

$Form->hidden( 'action', 'userupdate' );
$Form->hidden( 'edited_user_ID', $edited_User->dget('ID','formvalue') );
$Form->hidden( 'edited_user_oldlogin', $edited_User->dget('login', 'formvalue') );


$Form->fieldset( T_('User rights'), 'fieldset clear' );

$field_note = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://b2evolution.net/man/user_levels.html"' );
if( $user_profile_only )
{
	$Form->info( T_('Level'), $edited_User->dget('level'), $field_note );
}
else
{
	$Form->text( 'edited_user_level', $edited_User->level, 2, T_('Level'), $field_note, 2 );
}
if( $edited_User->get('ID') != 1 && !$user_profile_only )
{
	$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get('newusers_grp_ID') : $edited_User->Group->get('ID');
	form_select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_('User group') );
}
else
{
	echo '<input type="hidden" name="edited_user_grp_ID" value="'.$edited_User->Group->ID.'" />';
	$Form->info( T_('User group'), $edited_User->Group->dget('name') );
}

$Form->fieldset_end();


$Form->fieldset( T_('User') );

$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Send an email').'" class="middle" /></a>';

if( ($url = $edited_User->get('url')) != '' )
{
	if( !preg_match('#://#', $url) )
	{
		$url = 'http://'.$url;
	}
	$url_fieldnote = '<a href="'.$url.'" target="_blank"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Visit the site').'" class="middle" /></a>';
}
else
	$url_fieldnote = '';

if( $edited_User->get('icq') != 0 )
	$icq_fieldnote = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$edited_User->get('icq').'" target="_blank"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Search on ICQ.com').'" class="middle" /></a>';
else
	$icq_fieldnote = '';

if( $edited_User->get('aim') != '' )
	$aim_fieldnote = '<a href="aim:goim?screenname='.$edited_User->get('aim').'&amp;message=Hello"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Instant Message to user').'" class="middle" /></a>';
else
	$aim_fieldnote = '';


if( $allowed_to_edit )
{ // We can edit the values:
	$Form->text( 'edited_user_login', $edited_User->login, 20, T_('Login'), '', 20 );
	$Form->text( 'edited_user_firstname', $edited_User->firstname, 20, T_('First name'), '', 50 );
	$Form->text( 'edited_user_lastname', $edited_User->lastname, 20, T_('Last name'), '', 50 );
	$Form->text( 'edited_user_nickname', $edited_User->nickname, 20, T_('Nickname'), '', 50 );

	echo $Form->begin_field( 'edited_user_idmode', T_('Identity shown') );
	$idmode = $edited_User->get( 'idmode' ); ?>

				<select name="edited_user_idmode" id="edited_user_idmode">
					<option value="nickname"<?php if ( $idmode == 'nickname' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('nickname') != '' ) $edited_User->disp('nickname', 'htmlhead' ); else echo '['.T_('Nickname').']'; ?></option>
					<option value="login"<?php if ( $idmode == 'login' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('login') != '' ) $edited_User->disp('login', 'htmlhead' ); else echo '['.T_('Login').']'; ?></option>
					<option value="firstname"<?php if ( $idmode == 'firstname' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('firstname') != '' ) $edited_User->disp('firstname', 'htmlhead' ); else echo '['.T_('First name').']'; ?></option>
					<option value="lastname"<?php if ( $idmode == 'lastname' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('lastname') != '' ) $edited_User->disp('lastname', 'htmlhead' ); else echo '['.T_('Last name').']'; ?></option>
					<option value="namefl"<?php if ( $idmode == 'namefl' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('firstname') != '' ) $edited_User->disp('firstname', 'htmlhead' ); else echo '['.T_('First name').']'; echo ' '; if( $edited_User->get('lastname') != '' ) $edited_User->disp('lastname', 'htmlhead' ); else echo '['.T_('Last name').']'; ?></option>
					<option value="namelf"<?php if ( $idmode == 'namelf' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('lastname') != '' ) $edited_User->disp('lastname', 'htmlhead' ); else echo '['.T_('Last name').']'; echo ' '; if( $edited_User->get('firstname') != '' ) $edited_User->disp('firstname', 'htmlhead' ); else echo '['.T_('First name').']'; ?></option>
				</select>

	<?php
	echo $Form->end_field();

	$Form->checkbox( 'edited_user_showonline', $edited_User->get('showonline'), T_('Show Online'), T_('Check this to be displayed as online when visiting the site.') );
	$Form->select( 'edited_user_locale', $edited_User->get('locale'), 'locale_options_return', T_('Preferred locale'), T_('Preferred locale for admin interface, notifications, etc.'));
	$Form->text( 'edited_user_email', $edited_User->email, 30, T_('Email'), $email_fieldnote, 100 );
	$Form->checkbox( 'edited_user_notify', $edited_User->get('notify'), T_('Notifications'), T_('Check this to receive notification whenever one of your posts receives comments, trackbacks, etc.') );
	$Form->text( 'edited_user_url', $edited_User->url, 30, T_('URL'), $url_fieldnote, 100 );
	$Form->text( 'edited_user_icq', $edited_User->icq, 30, T_('ICQ'), $icq_fieldnote, 10 );
	$Form->text( 'edited_user_aim', $edited_User->aim, 30, T_('AIM'), $aim_fieldnote, 50 );
	$Form->text( 'edited_user_msn', $edited_User->msn, 30, T_('MSN IM'), '', 100 );
	$Form->text( 'edited_user_yim', $edited_User->yim, 30, T_('YahooIM'), '', 50 );
	$Form->text( 'edited_user_pass1', '', 20, T_('New password'), '', 50, T_('Leave empty if you don\'t want to change the password.'), 'password' );
	$Form->text( 'edited_user_pass2', '', 20, T_('Confirm new password'), '', 50, '', 'password' );

}
else
{ // display only
	$Form->_info( T_('Login'), $edited_User->dget('login') );
	$Form->_info( T_('First name'), $edited_User->dget('firstname') );
	$Form->_info( T_('Last name'), $edited_User->dget('lastname') );
	$Form->_info( T_('Nickname'), $edited_User->dget('nickname') );
	$Form->_info( T_('Identity shown'), $edited_User->dget('preferedname') );
	$Form->_info( T_('Show Online'), ($edited_User->dget('showonline')) ? T_('yes') : T_('no') );
	$Form->_info( T_('Locale'), $edited_User->dget('locale'), T_('Preferred locale for admin interface, notifications, etc.') );
	$Form->_info( T_('Email'), $edited_User->dget('email'), $email_fieldnote );
	$Form->_info( T_('Notifications'), ($edited_User->dget('notify')) ? T_('yes') : T_('no') );
	$Form->_info( T_('URL'), $edited_User->dget('url'), $url_fieldnote );
	$Form->_info( T_('ICQ'), $edited_User->dget('icq', '$Form->value'), $icq_fieldnote );
	$Form->_info( T_('AIM'), $edited_User->dget('aim'), $aim_fieldnote );
	$Form->_info( T_('MSN IM'), $edited_User->dget('msn') );
	$Form->_info( T_('YahooIM'), $edited_User->dget('yim') );
}

$Form->fieldset_end();


if( $allowed_to_edit )
{ // Edit buttons
	$Form->buttons( array( array( '', '', T_('Save !'), 'SaveButton' ),
												 array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}


$Form->fieldset( T_('User information') );

$Form->info( T_('ID'), $edited_User->dget('ID') );

if( $app_shortname == 'b2evo' )
{ // TODO: move this out of the core
	$Form->info( T_('Posts'), ( $action != 'newtemplate' ) ? $edited_User->getNumPosts() : '-' );
}
$Form->info( T_('Created on'), $edited_User->dget('datecreated') );
$Form->info( T_('From IP'), $edited_User->dget('ip') );
$Form->info( T_('From Domain'), $edited_User->dget('domain') );
$Form->info( T_('With Browser'), $edited_User->dget('browser') );

$Form->fieldset_end();
$Form->end_form();

?>

<div class="clear"></div>

</div>
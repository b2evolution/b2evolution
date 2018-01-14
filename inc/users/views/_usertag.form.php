<?php
/**
 * This file display the user tag form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var UserTag
 */
global $edited_UserTag;

global $action, $admin_url, $display_merge_tags_form, $return_to;

if( ! empty( $edited_UserTag->merge_tag_ID ) )
{ // Display a for to confirm merge the tag to other one
	$Form = new Form( NULL, 'usertagmerge_checkchanges', 'post', 'compact' );

	$Form->begin_form( 'fform', T_('Merge tags?'), array( 'formstart_class' => 'panel-danger' ) );
	$Form->hidden( 'utag_ID', $edited_UserTag->merge_tag_ID );
	$Form->hidden( 'old_tag_ID', $edited_UserTag->ID );
	$Form->add_crumb( 'usertag' );
	$Form->hiddens_by_key( get_memorized( 'action,utag_ID' ) );

	echo '<p>'.$edited_UserTag->merge_message.'</p>';

	$Form->button( array( 'submit', 'actionArray[merge_confirm]', T_('Confirm'), 'SaveButton btn-danger' ) );
	$Form->button( array( 'submit', 'actionArray[merge_cancel]', T_('Cancel'), 'SaveButton btn-default' ) );

	$Form->end_form();
}

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'usertag_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing').'!', 'close', ( $return_to ? $return_to : $admin_url.'?ctrl=usertags' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New User Tag') : /* TRANS: noun */ T_('Tag') ).get_manual_link( 'user-tag-form' ) );

	$Form->add_crumb( 'usertag' );
	$Form->hidden( 'action',  $creating ? 'create' : 'update' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',utag_ID' : '' ) ) );

	$Form->text_input( 'utag_name', $edited_UserTag->get( 'name' ), 50, /* TRANS: noun */ T_('Tag'), '', array( 'maxlength' => 255, 'required' => true ) );

$Form->end_form( array( array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ) ) );


// Item list with this tag:
if( $edited_UserTag->ID > 0 )
{
	$SQL = new SQL();
	$SQL->SELECT( 'T_users.*' );
	$SQL->FROM( 'T_users__usertag' );
	$SQL->FROM_add( 'INNER JOIN T_users ON uutg_user_ID = user_ID' );
	$SQL->WHERE( 'uutg_emtag_ID = '.$DB->quote( $edited_UserTag->ID ) );

	// Create result set:
	$Results = new Results( $SQL->get(), 'taguser_', 'A' );

	$Results->title = T_('Users that have this tag').' ('.$Results->get_total_rows().')';
	$Results->Cache = get_UserCache();

	$Results->cols[] = array(
			'th'       => T_('User ID'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'order'    => 'user_ID',
			'td'       => '$user_ID$',
		);

	$Results->cols[] = array(
			'th'    => T_('Login'),
			'order' => 'user_login',
			'td'    => '%get_user_identity_link( #user_login#, #user_ID#, "admin", "login" )%',
		);

	$Results->cols[] = array(
			'th'    => T_('Full name'),
			'order' => 'post_title',
			'td'    => '$user_firstname$ $user_lastname$',
		);

	function taguser_edit_actions( $User )
	{
		global $current_User, $edited_UserTag;

		// Display the edit icon if current user has the rights:
		$r = action_icon( T_('Edit this user...'), 'edit', regenerate_url( 'ctrl,action', 'ctrl=user&amp;user_ID='.$User->ID.'&amp;user_tab=admin' ) );

		if( $current_User->check_perm( 'users', 'moderate', false, $User ) )
		{ // Display the unlink icon if current user has the rights:
			$r .= action_icon( T_('Unlink this tag from user!'), 'unlink',
				regenerate_url( 'utag_ID,action,utag_filter', 'utag_ID='.$edited_UserTag->ID.'&amp;user_ID='.$User->ID.'&amp;action=unlink&amp;return_to='.urlencode( regenerate_url( 'action', '', '', '&' ) ).'&amp;'.url_crumb( 'usertag' ) ),
				NULL, NULL, NULL,
				array( 'onclick' => 'return confirm(\''.format_to_output( sprintf( TS_('Are you sure you want to remove the tag "%s" from "%s"?'),
						$edited_UserTag->dget( 'name' ),
						$User->dget( 'login' ) ).'\');', 'htmlattr' )
					) );
		}

		return $r;
	}
	$Results->cols[] = array(
			'th'       => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td'       => '%taguser_edit_actions( {Obj} )%',
		);

	$Results->display();
}
?>
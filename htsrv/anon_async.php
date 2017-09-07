<?php
/**
 * This is the handler for ANONYMOUS (non logged in) asynchronous 'AJAX' calls.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

// Disable log in with HTTP basic authentication because we need some action even for anonymous users,
// but it is impossible if wrong login was entered on "HTTP Basic Authentication" form.
// (Used to correct work of action "get_user_salt")
$disable_http_auth = true;

require_once $inc_path.'_main.inc.php';

load_funcs( '../inc/skins/_skin.funcs.php' );

global $skins_path, $ads_current_skin_path, $disp, $ctrl;
param( 'action', 'string', '' );
$item_ID = param( 'p', 'integer' );
$blog_ID = param( 'blog', 'integer' );
// Initialize this array in order to don't load JS files twice in they have been already loaded on parent page:
$required_js = param( 'required_js', 'array:string', array(), false, true );

// Send the predefined cookies:
evo_sendcookies();

// Make sure the async responses are never cached:
header_nocache();
header_content_type( 'text/html', $io_charset );

// Save current debug values
$current_debug = $debug;
$current_debug_jslog = $debug_jslog;

// Do not append Debuglog to response!
$debug = false;

// Do not append Debug JSlog to response!
$debug_jslog = false;

// Don't check new updates from b2evolution.net (@see b2evonet_get_updates()),
// in order to don't break the response data:
$allow_evo_stats = false;

// Init AJAX log
$Ajaxlog = new Log();

$Ajaxlog->add( sprintf( 'action: %s', $action ), 'note' );

$params = param( 'params', 'array', array() );
switch( $action )
{
	case 'get_comment_form':
		// display comment form
		$ItemCache = & get_ItemCache();
		$Item = $ItemCache->get_by_ID( $item_ID );
		$BlogCache = & get_BlogCache();
		$Collection = $Blog = $BlogCache->get_by_ID( $blog_ID );

		locale_activate( $Blog->get('locale') );

		$disp = param( 'disp', '/^[a-z0-9\-_]+$/', '' );
		$blog_skin_ID = $Blog->get_skin_ID();
		if( ! empty( $blog_skin_ID ) )
		{ // check if Blog skin has specific comment form
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $blog_skin_ID );
			$ads_current_skin_path = $skins_path.$Skin->folder.'/';
		}

		require skin_template_path( '_item_comment_form.inc.php' );
		break;


	case 'get_msg_form':
		// display send message form
		$recipient_id = param( 'recipient_id', 'integer', 0 );
		$recipient_name = param( 'recipient_name', 'string', '' );
		$subject = param( 'subject', 'string', '' );
		$email_author = param( 'email_author', 'string', '' );
		$email_author_address = param( 'email_author_address', 'string', '' );
		$redirect_to = param( 'redirect_to', 'url', '' );
		$post_id = NULL;
		$comment_id = param( 'comment_id', 'integer', 0 );
		$BlogCache = & get_BlogCache();
		$Collection = $Blog = $BlogCache->get_by_ID( $blog_ID );

		locale_activate( $Blog->get('locale') );

		if( $recipient_id > 0 )
		{ // Get identity link for existed users
			$RecipientCache = & get_UserCache();
			$Recipient = $RecipientCache->get_by_ID( $recipient_id );
			$recipient_link = $Recipient->get_identity_link( array( 'link_text' => 'nickname' ) );
		}
		else if( $comment_id > 0 )
		{ // Anonymous Users
			$gender_class = '';
			if( check_setting( 'gender_colored' ) )
			{ // Set a gender class if the setting is ON
				$gender_class = ' nogender';
			}
			$recipient_link = '<span class="user anonymous'.$gender_class.'" rel="bubbletip_comment_'.$comment_id.'">'.$recipient_name.'</span>';
		}

		$blog_skin_ID = $Blog->get_skin_ID();
		if( ! empty( $blog_skin_ID ) )
		{ // check if Blog skin has specific concact message form
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $blog_skin_ID );
			$ads_current_skin_path = $skins_path.$Skin->folder.'/';
		}

		require skin_template_path( '_contact_msg.form.php' );
		break;


	case 'get_user_bubbletip':
		// Get contents of a user bubbletip
		// Displays avatar & name
		$user_ID = param( 'userid', 'integer', 0 );
		$comment_ID = param( 'commentid', 'integer', 0 );

		if( strpos( $_SERVER["HTTP_REFERER"], $admin_url ) !== false )
		{	// If ajax is requested from admin page we should to set a variable $is_admin_page = true if user has permissions
			// Check global permission:
			if( empty($current_User) || ! $current_User->check_perm( 'admin', 'restricted' ) )
			{	// No permission to access admin...
				require $adminskins_path.'_access_denied.main.php';
			}
			else
			{	// Set this page as admin page
				$is_admin_page = true;
			}
		}

		if( $blog_ID > 0 )
		{	// Get Blog if ID is set
			$BlogCache = & get_BlogCache();
			$Collection = $Blog = $BlogCache->get_by_ID( $blog_ID );
		}

		if( $user_ID > 0 )
		{	// Print info of the registered users
			$UserCache = & get_UserCache();
			$User = & $UserCache->get_by_ID( $user_ID );

			$Ajaxlog->add( 'User: #'.$user_ID.' '.$User->login );

			if( is_logged_in() &&
			    ( $current_User->ID == $User->ID || $current_User->can_moderate_user( $User->ID ) ) &&
			    $current_User->check_status( 'can_access_admin' ) &&
			    $current_User->check_perm( 'admin', 'restricted' ) )
			{	// Display the moderation buttons only if current user has a permission:
				$moderation_buttons = '<p class="bubbletip_user__buttons">';
				if( ! is_admin_page() )
				{
					$moderation_buttons .= '<a href="'.url_add_param( $admin_url, 'ctrl=user&amp;user_ID='.$User->ID ).'" class="btn btn-sm btn-block btn-primary">'
							.T_('Edit in Back-Office').'</a>';
				}
				if( $current_User->ID != $User->ID && $current_User->check_perm( 'users', 'edit' ) )
				{	// Display a button to delete a spammer only for other users and if current user can edit them:
					$moderation_buttons .= '<a href="'.url_add_param( $admin_url, 'ctrl=users&amp;action=delete&amp;deltype=spammer&amp;user_ID='.$User->ID.'&amp;'.url_crumb( 'user' ) )
								.'" class="btn btn-sm btn-block btn-danger">'
							.T_('Delete Spammer')
						.'</a>';
				}
				$moderation_buttons .= '</p>';
			}
			else
			{	// No permission to moderate users:
				$moderation_buttons = '';
			}

			echo '<div class="bubbletip_user">';

			if( $User->check_status( 'is_closed' ) )
			{ // display only info about closed accounts
				echo T_( 'This account has been closed.' );
				echo $moderation_buttons;
				echo '</div>'; /* end of: <div class="bubbletip_user"> */
				break;
			}

			$avatar_overlay_text = '';
			$link_class = '';
			if( is_admin_page() )
			{	// Set avatar size for Back-office
				$avatar_size = $Settings->get('bubbletip_size_admin');
			}
			else if( is_logged_in() )
			{	// Set avatar size for logged in users in the Front-office
				$avatar_size = $Settings->get('bubbletip_size_front');
			}
			else
			{	// Set avatar size for Anonymous users
				$avatar_size = $Settings->get('bubbletip_size_anonymous');
				$avatar_overlay_text = $Settings->get('bubbletip_overlay');
				$link_class = 'overlay_link';
			}

			$width = $thumbnail_sizes[$avatar_size][1];
			$height = $thumbnail_sizes[$avatar_size][2];
			// Display user avatar with login
			// Attributes 'w' & 'h' we use for following js-scale div If image is downloading first time (Fix bubbletip)
			echo '<div class="center" w="'.$width.'" h="'.$height.'">';
			echo get_avatar_imgtag( $User->login, 'login', true, $avatar_size, 'avatar_above_login', '', $avatar_overlay_text, $link_class, true, '' );
			echo '</div>';

			if( ! ( $Settings->get( 'allow_anonymous_user_profiles' ) || ( is_logged_in() && $current_User->check_perm( 'user', 'view', false, $User ) ) ) )
			{ // User is not logged in and anonymous users may NOT view user profiles, or if current User has no permission to view additional information about the User
				echo $moderation_buttons;
				echo '</div>'; /* end of: <div class="bubbletip_user"> */
				break;
			}

			// Additional user info
			$user_info = array();

			// Preferred Name
			if( $User->get_preferred_name() != $User->login )
			{
				$user_info[] = $User->get_preferred_name();
			}

			// Location
			$location = array();
			if( !empty( $User->city_ID ) )
			{	// City
				$location[] = $User->get_city_name( false );
			}
			if( !empty( $User->subrg_ID ) )
			{	// Subregion
				if( !is_logged_in() )
				{	// Display subregion for not logged in users
					$location[] = $User->get_subregion_name();
				}
				else if( $current_User->subrg_ID != $User->subrg_ID )
				{	// If subregions are different
					$location[] = $User->get_subregion_name();
				}
			}
			if( !empty( $User->rgn_ID ) )
			{	// Region
				if( !is_logged_in() )
				{	// Display region for not logged in users
					$location[] = $User->get_region_name();
				}
				else if( $current_User->rgn_ID != $User->rgn_ID )
				{	// If regions are different
					$location[] = $User->get_region_name();
				}
			}
			if( !empty( $User->ctry_ID ) )
			{	// Country
				if( !is_logged_in() )
				{	// Display country for not logged in users
					$location[] = $User->get_country_name();
				}
				else if( $current_User->ctry_ID != $User->ctry_ID )
				{	// If countries are different
					$location[] = $User->get_country_name();
				}
			}
			if( !empty( $location ) )
			{	// Set location info
				$user_info[] = implode( '<br />', $location );
			}

			// Age group
			if( !empty( $User->age_min ) && !empty( $User->age_max ) && $User->age_min != $User->age_max )
			{
				$user_info[] = sprintf( T_('%d to %d years old '), $User->age_min, $User->age_max );
			}
			else if( !empty( $User->age_min ) || !empty( $User->age_max ) )
			{	// Min age equals max age
				$age = !empty( $User->age_min ) ? $User->age_min : $User->age_max;
				$user_info[] = sprintf( T_('%d years old '), $age );
			}

			if( !empty( $user_info ) )
			{	// Display additional user info
				echo '<ul>';
				foreach( $user_info as $info )
				{
					echo '<li>'.$info.'</li>';
				}
				echo '</ul>';
			}

			echo $moderation_buttons;
			echo '</div>'; /* end of: <div class="bubbletip_user"> */
		}
		else if( $comment_ID > 0 )
		{	// Print info for an anonymous user who posted a comment
			$CommentCache = & get_CommentCache();
			$Comment = $CommentCache->get_by_ID( $comment_ID );

			$Ajaxlog->add( 'Comment: #'.$comment_ID.' '.$Comment->get_author_name() );

			echo '<div class="bubbletip_anon">';

			echo $Comment->get_avatar( 'fit-160x160', 'bCommentAvatarCenter' );
			echo '<div>'.$Comment->get_author_name_anonymous( 'htmlbody', array( 'rel' => '' ) ).'</div>';
			echo '<div>'.T_('This user is not registered on this site.').'</div>';
			echo $Comment->get_author_url_link( '', '<div>', '</div>');

			if( isset( $Blog ) )
			{	// Link to send message
				echo '<div>';
				$Comment->msgform_link( $Blog->get('msgformurl'), '', '', get_icon( 'email', 'imgtag' ).' '.T_('Send a message') );
				echo '</div>';
			}
			echo '</div>';
		}
		else
		{ // user_ID and comment_ID are both null, this can happen when the user was deleted
			echo '<div class="bubbletip_user">';
			echo T_( 'This account has been deleted.' );
			echo '</div>';
			break;
		}

		break;


	case 'set_comment_vote':
		// Used for quick SPAM vote of comments
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		if( !is_logged_in( false ) )
		{ // Only active logged in users can vote
			break;
		}

		// Use the glyph or font-awesome icons if requested by skin
		param( 'b2evo_icons_type', 'string', '' );

		if( param( 'is_backoffice', 'integer', 0 ) )
		{ // Set admin skin, used for buttons, @see button_class()
			global $current_User, $UserSettings, $is_admin_page, $adminskins_path;
			$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
			$is_admin_page = true;
			require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
			$AdminUI = new AdminUI();
		}
		else
		{
			$BlogCache = &get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, true );
			$skin_ID = $Blog->get_skin_ID();
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $skin_ID );
		}

		// Check permission for spam voting
		$current_User->check_perm( 'blog_vote_spam_comments', 'edit', true, $blog_ID );

		$type = param( 'type', 'string' );
		$commentid = param( 'commentid', 'integer' );
		if( $type != 'spam' || empty( $commentid ) )
		{	// Incorrect params
			break;
		}

		$edited_Comment = & Comment_get_by_ID( $commentid, false );
		if( $edited_Comment !== false )
		{ // The comment still exists
			if( $current_User->ID == $edited_Comment->author_user_ID )
			{ // Do not allow users to vote on their own comments
				break;
			}

			// Update a vote of the comment for current User:
			$edited_Comment->set_vote( 'spam', param( 'vote', 'string' ) );
			$edited_Comment->dbupdate();

			// Display a panel for next spam voting:
			$edited_Comment->vote_spam( '', '', '&amp;', true, true, array(
					'display'            => true,
					'button_group_class' => button_class( 'group' ).( is_admin_page() ? ' btn-group-sm' : '' ),
				) );
		}

		break;

	case 'voting':
		// Actions for voting by AJAX

		if( !is_logged_in( false ) )
		{ // Only active logged in users can vote
			break;
		}

		param( 'vote_action', 'string', '' );

		if( !empty( $vote_action ) )
		{ // Use crumb checking only for actions
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'voting' );
		}

		param( 'vote_type', 'string', '' );
		param( 'vote_ID', 'string', 0 );
		param( 'checked', 'integer', 0 );
		param( 'redirect_to', 'url', '' );
		// Use the glyph or font-awesome icons if requested by skin
		param( 'b2evo_icons_type', 'string', '' );

		$Ajaxlog->add( sprintf( 'vote action: %s', $vote_action ), 'note' );
		$Ajaxlog->add( sprintf( 'vote type: %s', $vote_type ), 'note' );
		$Ajaxlog->add( sprintf( 'vote ID: %s', $vote_ID ), 'note' );

		$voting_form_params = array(
				'vote_type' => $vote_type,
			);

		switch( $vote_type )
		{
			case 'link':
				// Vote on pictures

				$link_ID = preg_replace( '/link_(\d+)/i', '$1', $vote_ID );
				if( empty( $link_ID )  || ( ! is_decimal( $link_ID ) ) )
				{ // There is no correct link ID
					break 2;
				}

				$LinkCache = & get_LinkCache();
				$Link = & $LinkCache->get_by_ID( $link_ID, false );
				if( !$Link )
				{ // Incorrect link ID
					break 2;
				}

				$File = & $Link->get_File();
				if( !$File )
				{ // The Link File is not available
					break 2;
				}

				if( empty( $File->hash ) )
				{ // File hash still is not defined, we should create and save it
					$File->set_param( 'hash', 'string', md5_file( $File->get_full_path(), true ) );
					$File->dbsave();
				}

				if( !empty( $vote_action ) )
				{ // Vote for this file link
					link_vote( $link_ID, $current_User->ID, $vote_action, $checked );
				}

				$voting_form_params['vote_ID'] = $link_ID;

				if( empty( $vote_action ) || in_array( $vote_action, array( 'like', 'noopinion', 'dontlike' ) ) )
				{ // Display a voting form if no action
					// or Refresh a voting form only for these actions (in order to disable icons)
					if( ! empty( $blog_ID ) )
					{ // If blog is defined we should check if we can display info about number of votes
						$BlogCache = & get_BlogCache();
						if( ( $Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) ) &&
						    $blog_skin_ID = $Blog->get_skin_ID() )
						{
							$LinkOwner = & $Link->get_LinkOwner();
							$SkinCache = & get_SkinCache();
							if( $Skin = & $SkinCache->get_by_ID( $blog_skin_ID, false, false ) &&
							    $Skin->get_setting( 'colorbox_vote_'.$LinkOwner->get( 'name' ).'_numbers' ) )
							{ // Display number of votes for current link type if it is enabled by blog skin
								$voting_form_params['display_numbers'] = true;
							}
						}
					}
					display_voting_form( $voting_form_params );
				}
				break;

			case 'comment':
				// Vote on comments

				$comment_ID = (int)$vote_ID;
				if( empty( $comment_ID ) )
				{ // No comment ID
					break 2;
				}

				$CommentCache = & get_CommentCache();
				$Comment = $CommentCache->get_by_ID( $comment_ID, false );
				if( !$Comment )
				{ // Incorrect comment ID
					break 2;
				}

				if( $current_User->ID == $Comment->author_user_ID )
				{ // Do not allow users to vote on their own comments
					break 2;
				}

				$comment_Item = & $Comment->get_Item();
				$comment_Item->load_Blog();

				if( ! $comment_Item->Blog->get_setting('allow_rating_comment_helpfulness') )
				{ // If Users cannot vote
					break 2;
				}

				if( !empty( $vote_action ) )
				{ // Vote for this comment
					switch( $vote_action )
					{ // Set field value
						case 'like':
							$field_value = 'yes';
							break;

						case 'noopinion':
							$field_value = 'noopinion';
							break;

						case 'dontlike':
							$field_value = 'no';
							break;
					}

					if( isset( $field_value ) )
					{ // Update a vote of current user
						$Comment->set_vote( 'helpful', $field_value );
						$Comment->dbupdate();
					}
				}

				if( !empty( $redirect_to ) )
				{ // Redirect to back page, It is used by browsers without JavaScript
					header_redirect( $redirect_to, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}

				if( param( 'skin_ID', 'integer', 0 ) > 0 )
				{	// If request is from skin:
					$SkinCache = & get_SkinCache();
					$request_Skin = & $SkinCache->get_by_ID( get_param( 'skin_ID' ), false, false );
					if( $request_Skin && method_exists( $request_Skin, 'display_comment_voting_panel' ) )
					{	// Request skin to display a voting panel for item:
						$request_Skin->display_comment_voting_panel( $Comment, array( 'display_wrapper' => false ) );
						break 2;
					}
				}

				$Comment->vote_helpful( '', '', '&amp;', true, true, array( 'display_wrapper' => false ) );
				break;

			case 'item':
				// Vote on items:

				$item_ID = intval( $vote_ID );
				if( empty( $item_ID ) )
				{	// No item ID
					break 2;
				}

				$ItemCache = & get_ItemCache();
				$Item = $ItemCache->get_by_ID( $item_ID, false );
				if( ! $Item )
				{	// Incorrect item ID:
					break 2;
				}

				if( $current_User->ID == $Item->creator_user_ID )
				{	// Do not allow users to vote on their own comments:
					break 2;
				}

				$item_Blog = & $Item->get_Blog();

				if( empty( $item_Blog ) || ! $item_Blog->get_setting( 'voting_positive' ) )
				{	// If Users cannot vote:
					break 2;
				}

				if( ! empty( $vote_action ) )
				{	// Vote for the item:
					switch( $vote_action )
					{ // Set field value
						case 'like':
							$field_value = 'positive';
							break;

						case 'noopinion':
							$field_value = 'neutral';
							break;

						case 'dontlike':
							$field_value = 'negative';
							break;
					}

					if( isset( $field_value ) )
					{ // Update a vote of current user
						$Item->set_vote( $field_value );
						$Item->dbupdate();
					}
				}

				if( ! empty( $redirect_to ) )
				{	// Redirect to back page, It is used by browsers without JavaScript:
					header_redirect( $redirect_to, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}

				if( param( 'widget_ID', 'integer', 0 ) > 0 )
				{	// If request is from widget:
					$WidgetCache = & get_WidgetCache();
					$item_Widget = & $WidgetCache->get_by_ID( get_param( 'widget_ID' ), false, false );
					if( $item_Widget && $item_Widget->code == 'item_vote' )
					{	// Request widget to display a voting panel for item:
						$item_Widget->display_voting_panel( $Item, array( 'display_wrapper' => false ) );
						break 2;
					}
				}
				elseif( param( 'skin_ID', 'integer', 0 ) > 0 )
				{	// If request is from skin:
					$SkinCache = & get_SkinCache();
					$request_Skin = & $SkinCache->get_by_ID( get_param( 'skin_ID' ), false, false );
					if( $request_Skin && method_exists( $request_Skin, 'display_item_voting_panel' ) )
					{	// Request skin to display a voting panel for item:
						$request_Skin->display_item_voting_panel( $Item, array( 'display_wrapper' => false ) );
						break 2;
					}
				}

				// Display a voting panel for item:
				$Item->display_voting_panel( array( 'display_wrapper' => false ) );
				break;
		}
		break;

	case 'get_user_new_field':
		// Used in the identity user form to add a new field
		$field_ID = param( 'field_id', 'integer', 0 );
		$user_ID = param( 'user_id', 'integer', 0 );

		if( $field_ID == 0 )
		{	// Bad request
			break;
		}

		$userfields = $DB->get_results( '
			SELECT ufdf_ID, "0" AS uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, "" AS uf_varchar, ufdf_required, ufdf_options, ufdf_suggest, ufdf_duplicated, ufgp_ID, ufgp_name
				FROM T_users__fielddefs
				LEFT JOIN T_users__fieldgroups ON ufgp_ID = ufdf_ufgp_ID
			WHERE ufdf_ID = "'.$field_ID.'"' );

		if( $userfields[0]->ufdf_duplicated == 'forbidden' )
		{	// This field can be only one instance for one user
			echo '[0]'; // not duplicated field

			$user_field_exist = $DB->get_var( '
				SELECT uf_ID
					FROM T_users__fields
				WHERE uf_user_ID = "'.$user_ID.'" AND uf_ufdf_ID = "'.$field_ID.'"' );
			if( $user_field_exist > 0 )
			{	// User already has a current field type
				break;
			}
		}
		else
		{	// It Means: this field can be duplicated
			echo '[1]';
		}

		// Use the glyph or font-awesome icons if requested by skin
		param( 'b2evo_icons_type', 'string', '' );

		$Form = new Form();
		$Form->fieldstart = '#fieldstart#';
		$Form->fieldend = '#fieldend#';
		$Form->labelclass = '#labelclass#';
		$Form->labelstart = '#labelstart#';
		$Form->labelend = '#labelend#';
		$Form->inputstart = '#inputstart#';
		$Form->inputend = '#inputend#';

		userfields_display( $userfields, $Form, 'add', false );

		break;

	case 'get_user_field_autocomplete':
		// Used for autocompletion of the user field

		/**
		 * Possible values of var $attr_id
		 * 1) 111 - this goes from filter search, it is ufdf_ID
		 * 2) uf_new_222_ - field from identity form ( doesn't still exist in DB (recommened & required fields) )
		 * 3) uf_add_222_ - field from identity form ( user want add this field )
		 *             where 222 == ufdf_ID
		 * 4) uf_333 - field exists in DB (where 333 == uf_ID from table T_users__fields)
		*/
		$attr_id = param( 'attr_id', 'string' );
		$term = param( 'term', 'string' );

		$field_type_id = 0;
		if( (int)$attr_id > 0 )
		{	// From filter 'Specific criteria'
			$field_type_id = (int)$attr_id;
		}
		else if( preg_match( '/^uf_(new|add)_(\d+)_/i', $attr_id, $match ) )
		{	// From new fields we can get the value for uf_ufdf_ID
			$field_type_id = (int)$match[2];
		}
		else if( preg_match( '/^uf_(\d+)$/i', $attr_id, $match ) )
		{	// From fields in DB we can get only uf_ID, then we should get a value uf_ufdf_ID from DB
			$field_id = (int)$match[1];
			$field_type_id = $DB->get_var( '
				SELECT uf_ufdf_ID
				  FROM T_users__fields
				 WHERE uf_ID = "'.$field_id.'"' );
		}

		if( $field_type_id == 0 )
		{	// Bad request
			break;
		}

		echo evo_json_encode( $DB->get_col( '
			SELECT DISTINCT ( uf_varchar )
			  FROM T_users__fields
			 WHERE uf_varchar LIKE '.$DB->quote('%'.$term.'%').'
			   AND uf_ufdf_ID = "'.$field_type_id.'"
			 ORDER BY uf_varchar' ) );

		exit(0); // Exit here in order to don't display the AJAX debug info after JSON formatted data

		break;

	case 'get_userfields_criteria':
		// Get fieldset for users filter by Specific criteria

		// Use the glyph or font-awesome icons if requested by skin
		param( 'b2evo_icons_type', 'string', '' );

		if( param( 'is_backoffice', 'integer', 0 ) )
		{
			global $current_User, $UserSettings, $is_admin_page;
			$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
			$is_admin_page = true;
			/**
			 * Load the AdminUI class for the skin.
			 */
			require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
			$AdminUI = new AdminUI();
		}
		else
		{
			$BlogCache = &get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, true );
			$skin_ID = $Blog->get_skin_ID();
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $skin_ID );
		}

		$Form = new Form();
		$Form->switch_layout( 'blockspan' );

		echo '<br />';
		$Form->output = false;
		$criteria_input = $Form->text( 'criteria_value[]', '', 17, '', '', 50 );
		$criteria_input .= get_icon( 'add', 'imgtag', array( 'rel' => 'add_criteria' ) );
		$Form->output = true;

		global $user_fields_empty_name;
		$user_fields_empty_name = /* TRANS: verb */ T_('Select').'...';

		$Form->select( 'criteria_type[]', '', 'callback_options_user_new_fields', T_('Specific criteria'), $criteria_input );

		break;

	case 'get_regions_option_list':
		// Get option list with regions by selected country
		$country_ID = param( 'ctry_id', 'integer', 0 );
		$region_ID = param( 'rgn_id', 'integer', 0 );
		$page = param( 'page', 'string', '' );
		$mode = param( 'mode', 'string', '' );

		$params = array();
		if( $page == 'edit' )
		{
			$params['none_option_text'] = T_( 'Unknown' );
		}

		load_funcs( 'regional/model/_regional.funcs.php' );
		echo get_regions_option_list( $country_ID, 0, $params );

		if( $mode == 'load_subregions' || $mode == 'load_all' )
		{	// Load also the subregions
			echo '-##-'.get_subregions_option_list( $region_ID, 0, $params );
		}
		if( $mode == 'load_all' )
		{	// Load also the cities
			echo '-##-'.get_cities_option_list( $country_ID, $region_ID, 0, 0, $params );
		}

		break;

	case 'get_subregions_option_list':
		// Get option list with sub-regions by selected region
		$country_ID = param( 'ctry_id', 'integer', 0 );
		$region_ID = param( 'rgn_id', 'integer', 0 );
		$page = param( 'page', 'string', '' );
		$mode = param( 'mode', 'string', '' );

		$params = array();
		if( $page == 'edit' )
		{
			$params['none_option_text'] = T_( 'Unknown' );
		}

		load_funcs( 'regional/model/_regional.funcs.php' );
		echo get_subregions_option_list( $region_ID, 0, $params );

		if( $mode == 'load_all' )
		{	// Load also the cities
			echo '-##-'.get_cities_option_list( $country_ID, $region_ID, 0, 0, $params );
		}

		break;

	case 'get_cities_option_list':
		// Get option list with cities by selected country, region or sub-region
		$country_ID = param( 'ctry_id', 'integer', 0 );
		$region_ID = param( 'rgn_id', 'integer', 0 );
		$subregion_ID = param( 'subrg_id', 'integer', 0 );
		$page = param( 'page', 'string', '' );

		$params = array();
		if( $page == 'edit' )
		{
			$params['none_option_text'] = T_( 'Unknown' );
		}

		load_funcs( 'regional/model/_regional.funcs.php' );
		echo get_cities_option_list( $country_ID, $region_ID, $subregion_ID, 0, $params );

		break;

	case 'get_field_bubbletip':
		// Get info for user field
		$field_ID = param( 'field_ID', 'integer', 0 );

		if( $field_ID > 0 )
		{	// Get field info from DB
			$field = $DB->get_row( '
				SELECT ufdf_bubbletip, ufdf_duplicated
				  FROM T_users__fielddefs
				 WHERE ufdf_ID = '.$DB->quote( $field_ID ) );

			if( is_null( $field ) )
			{	// No field in DB
				break;
			}

			if( !empty( $field->ufdf_bubbletip ) )
			{	// Field has a defined bubbletip text
				$field_info = nl2br( $field->ufdf_bubbletip );
			}
			else if( in_array( $field->ufdf_duplicated, array( 'allowed', 'list' ) ) )
			{	// Default info for fields with multiple values
				$field_info = T_('To enter multiple values,<br />please click on (+)');
			}
		}

		if( !empty( $field_info ) )
		{ // Replace mask text (+) with img tag

		// Use the glyph or font-awesome icons if requested by skin
		param( 'b2evo_icons_type', 'string', '' );

			echo str_replace( '(+)', get_icon( 'add' ), $field_info );
		}

		break;

	case 'collapse_filter':
	case 'expand_filter':
		// Save a value of state(collapse/expand) of the current filter
		param( 'target', 'string', '' );
		if( !empty( $target ) )
		{	// We want to record a 'collapse'/'expand' value:
			$target_status = $action == 'collapse_filter' ? 'collapsed' : 'expanded';
			if( preg_match( '/_(filters|colselect)$/', $target ) )
			{	// accept all _filters and _colselect open/close requests!
				// We have a valid value:
				$Session->set( $target, $target_status );
			}
			else
			{	// Warning: you may not see this on AJAX calls
				$Ajaxlog->add( 'Cannot ['.$target_status.'] unknown param ['.$target.']', 'error' );
			}
		}
		break;

	case 'validate_login':
		// Validate if username is available
		param( 'login', 'string', '' );

		if( param_check_valid_login( 'login' ) )
		{	// Login format is correct
			if( !empty( $login ) )
			{
				$SQL = new SQL( 'Validate if username is available' );
				$SQL->SELECT( 'user_ID' );
				$SQL->FROM( 'T_users' );
				$SQL->WHERE( 'user_login = "'.$DB->escape( $login ).'"' );
				if( $DB->get_var( $SQL->get() ) )
				{	// Login already exists
					echo 'exists';
				}
				else
				{	// Login is available
					echo 'available';
				}
			}
		}
		else
		{	// Incorrect format of login
			echo param_get_error_msg( 'login' );
		}
		break;

	case 'results':
		// Refresh a results table (To change page, page size, an order)

		/**
		 * Variable to define a current request as ajax content
		 * It is used to don't display a wrapper data such as header, footer and etc.
		 * @see is_ajax_content()
		 *
		 * @var boolean
		 */
		$ajax_content_mode = true;

		// get callback function param, this function will display the results content
		$callback_function = param( 'callback_function', 'string', '' );
		if( param( 'is_backoffice', 'integer', 0 ) )
		{
			global $current_User, $UserSettings, $is_admin_page;
			$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
			$params = array( 'skin_type' => 'admin', 'skin_name' => $admin_skin );
			$is_admin_page = true;
			/**
			 * Load the AdminUI class for the skin.
			 */
			require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
			$AdminUI = new AdminUI();

			// Get the requested params and memorize it to make correct links for paging, ordering and etc.
			param( 'ctrl', '/^[a-z0-9_]+$/', $default_ctrl, true );
			param( 'blog', 'integer', NULL, true );
			$ReqPath = $admin_url;
		}
		else
		{
			$BlogCache = &get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, true );
			$skin_ID = $Blog->get_skin_ID();
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $skin_ID );
			$params = array( 'skin_type' => 'front', 'skin_name' => $Skin->folder );
		}

		// load required resource for each callback function
		switch( $callback_function )
		{
			case 'hits_results_block':
				load_funcs('sessions/model/_hitlog.funcs.php');
				break;

			case 'items_created_results_block':
			case 'items_edited_results_block':
			case 'comments_results_block':
			case 'threads_results_block':
			case 'user_reports_results_block':
			case 'blogs_user_results_block':
			case 'blogs_all_results_block':
			case 'items_list_block_by_page':
			case 'items_manual_results_block':
				break;

			default:
				$Ajaxlog->add( 'Incorrect callback function name!', 'error' );
				debug_die( 'Incorrect callback function!' );
		}

		// Call the requested callback function to display the results
		call_user_func( $callback_function, $params );
		break;

	case 'set_comment_status':
		// Used for quick moderation of comments in dashboard, item list full view, comment list and front-office screens

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$result_success = false;

		if( is_logged_in() )
		{ // Only logged in users can moderate comments

			// Check comment moderate permission below after we have the $edited_Comment object

			$request_from = param( 'request_from', 'string', NULL );
			$is_admin_page = $request_from != 'front';
			$blog = param( 'blogid', 'integer' );
			$moderation = param( 'moderation', 'string', NULL );
			$status = param( 'status', 'string' );
			$expiry_status = param( 'expiry_status', 'string', 'active' );
			$limit = param( 'limit', 'integer', 0 );

			$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ), false );
			if( $edited_Comment !== false )
			{ // The comment still exists
				// Check permission:
				$current_User->check_perm( 'comment!'.$status, 'moderate', true, $edited_Comment );

				$redirect_to = param( 'redirect_to', 'url', NULL );

				$edited_Comment->set( 'status', $status );
				// Comment moderation is done, handle moderation "secret"
				$edited_Comment->handle_qm_secret();
				$result_success = $edited_Comment->dbupdate();
				if( $result_success !== false )
				{
					$edited_Comment->handle_notifications();
				}
			}
		}

		if( $result_success === false )
		{ // Some errors on deleting of the comment, Exit here
			header_http_response( '500 '.T_('Comment cannot be updated!'), 500 );
			exit(0);
		}

		if( $moderation != NULL && in_array( $request_from, array( 'items', 'comments' ) ) )
		{ // AJAX request goes from backoffice and ctrl = items or comments
			if( param( 'is_backoffice', 'integer', 0 ) )
			{ // Set admin skin, used for buttons, @see button_class()
				global $current_User, $UserSettings, $is_admin_page, $adminskins_path;
				$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
				$is_admin_page = true;
				require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
				$AdminUI = new AdminUI();
			}

			$statuses = param( 'statuses', 'string', NULL );
			$item_ID = param( 'itemid', 'integer' );
			$currentpage = param( 'currentpage', 'integer', 1 );

			// In case of comments_fullview we must set a filterset name to be abble to restore filterset.
			// If $moderation is not NULL, then this requests came from the comments_fullview
			// TODO: asimo> This should be handled with a better solution
			$filterset_name = ( $item_ID > 0 ) ? '' : 'fullview';

			echo_item_comments( $blog, $item_ID, $statuses, $currentpage, $limit, array(), $filterset_name, $expiry_status );
		}
		elseif( $request_from == 'front' )
		{ // AJAX request goes from frontoffice
			// Send new current status as ajax response
			echo $edited_Comment->status;
			// Also send the statuses which will be after raising/lowering of a status by current user
			$comment_raise_status = $edited_Comment->get_next_status( true, $edited_Comment->status );
			$comment_lower_status = $edited_Comment->get_next_status( false, $edited_Comment->status );
			echo ':'.( $comment_raise_status ? $comment_raise_status[0] : '' );
			echo ':'.( $comment_lower_status ? $comment_lower_status[0] : '' );
		}
		break;

	case 'get_user_new_org':
		// Used in the identity user form to add a new organization
		if( ! is_logged_in() )
		{ // User must be logged in
			break;
		}

		$first_org = param( 'first_org', 'integer', 0 );

		// Use the glyph or font-awesome icons if it is defined by skin
		param( 'b2evo_icons_type', 'string', '' );

		$Form = new Form();

		$OrganizationCache = & get_OrganizationCache();
		$OrganizationCache->clear();
		// Load only organizations that allow to join members or own organizations of the current user:
		$OrganizationCache->load_where( '( org_accept != "no" OR org_owner_user_ID = "'.$current_User->ID.'" )' );

		$Form->output = false;
		$Form->switch_layout( 'none' );
		$org_suffix = ' &nbsp; <strong>'.T_('Role').':</strong> '.$Form->text_input( 'org_roles[]', '', 20, '', '', array( 'maxlength' => 255 ) ).' &nbsp; ';
		$Form->switch_layout( NULL );
		$Form->output = true;

		// Special form template that will be replaced to current skin on ajax response
		$Form->fieldstart = '#fieldstart#';
		$Form->fieldend = '#fieldend#';
		$Form->labelclass = '#labelclass#';
		$Form->labelstart = '#labelstart#';
		$Form->labelend = '#labelend#';
		$Form->inputstart = '#inputstart#';
		$Form->inputend = '#inputend#';

		$org_suffix .= ' '.get_icon( 'add', 'imgtag', array( 'class' => 'add_org', 'style' => 'cursor:pointer' ) );
		$org_suffix .= ' '.get_icon( 'minus', 'imgtag', array( 'class' => 'remove_org', 'style' => 'cursor:pointer' ) );
		$Form->select_input_object( 'organizations[]', 0, $OrganizationCache, T_('Organization'), array( 'allow_none' => $first_org ? true : false, 'field_suffix' => $org_suffix ) );

		break;

	case 'change_user_org_status':
		// Used in the identity user form to change an accept status of organization by Admin users
		if( ! is_logged_in() )
		{ // User must be logged in
			break;
		}

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'userorg' );

		/**
		 * This string is value of "rel" attibute of span icon element
		 * The format of this string is: 'org_status_y_1_2' or 'org_status_n_1_2', where:
		 *     'y' - organization was accepted by admin,
		 *     'n' - not accepted
		 *     '1' - this number is ID of organization - uorg_org_ID from the DB table T_users__user_org
		 *     '2' - this number is ID of user - uorg_user_ID from the DB table T_users
		 */
		$status = explode( '_', param( 'status', 'string' ) );

		// ID of organization
		$org_ID = isset( $status[3] ) ? intval( $status[3] ) : 0;

		// ID of organization
		$user_ID = isset( $status[4] ) ? intval( $status[4] ) : 0;

		// Get organization by ID:
		$OrganizationCache = & get_OrganizationCache();
		$user_Organization = & $OrganizationCache->get_by_ID( $org_ID, false, false );

		if( count( $status ) != 5 || ( $status[2] != 'y' && $status[2] != 'n' ) || $org_ID == 0 || ! $user_Organization )
		{ // Incorrect format of status param
			$Ajaxlog->add( /* DEBUG: do not translate */ 'Incorrect request to accept organization!', 'error' );
			break;
		}

		// Check permission:
		$current_User->check_perm( 'orgs', 'edit', true, $user_Organization );

		// Use the glyph or font-awesome icons if it is defined by skin
		param( 'b2evo_icons_type', 'string', '' );

		if( $status[2] == 'y' )
		{ // Status will be change to "Not accepted"
			$org_is_accepted = false;
		}
		else
		{ // Status will be change to "Accepted"
			$org_is_accepted = true;
		}

		// Change an accept status of organization for edited user
		$DB->query( 'UPDATE T_users__user_org
			  SET uorg_accepted = '.$DB->quote( $org_is_accepted ? 1 : 0 ).'
			WHERE uorg_user_ID = '.$DB->quote( $user_ID ).'
			  AND uorg_org_ID = '.$DB->quote( $org_ID ) );

		$accept_icon_params = array( 'style' => 'cursor: pointer;', 'rel' => 'org_status_'.( $org_is_accepted ? 'y' : 'n' ).'_'.$org_ID.'_'.$user_ID );
		if( $org_is_accepted )
		{ // Organization is accepted by admin
			$accept_icon = get_icon( 'allowback', 'imgtag', array_merge( array( 'title' => T_('Accepted') ), $accept_icon_params ) );
		}
		else
		{ // Organization is not accepted by admin yet
			$accept_icon = get_icon( 'bullet_red', 'imgtag', array_merge( array( 'title' => T_('Not accepted') ), $accept_icon_params ) );
		}

		// Display icon with new status
		echo $accept_icon;

		break;

	case 'get_regform_crumb':
		// Get crumb value for register form:
		// (Used for widget "Email capture / Quick registration" when page caching is enabled)
		echo get_crumb( 'regform' );
		break;

	case 'get_user_salt':
		// Get the salt of the user from the given login info
		// Note: If there are more users with the received login then give at most 3 salt values for the 3 most recently active users
		// It always returns at least one salt value to show no difference between the existing and not existing user names

		$get_widget_login_hidden_fields = param( 'get_widget_login_hidden_fields', 'boolean', false );

		// Check that this action request is not a CSRF hacked request:
		if( ! $get_widget_login_hidden_fields )
		{ // If the request was received from the normal login form check the loginsalt crumb
			$Session->assert_received_crumb( 'loginsalt' );
		}

		$result = array();

		if( $get_widget_login_hidden_fields )
		{	// Get the loginform crumb, the password encryption salt, and the Session ID for the widget login form:
			$pepper = $Session->get( 'core.pepper' );
			if( empty( $pepper ) )
			{	// Session salt is not generated yet, needs to generate:
				$pepper = generate_random_key(64);
				$Session->set( 'core.pepper', $pepper, 86400 /* expire in 1 day */ );
				$Session->dbsave(); // save now, in case there's an error later, and not saving it would prevent the user from logging in.
			}
			$result['crumb'] = get_crumb( 'loginform' );
			$result['pepper'] = $pepper;
			$result['session_id'] = $Session->ID;
		}

		$login = param( $dummy_fields[ 'login' ], 'string', '' );
		$check_field = is_email( $login ) ? 'user_email' : 'user_login';

		// Get the most recently used 3 users with matching email address:
		$salts = $DB->get_results( 'SELECT user_salt, user_pass_driver FROM T_users
						WHERE '.$check_field.' = '.$DB->quote( utf8_strtolower( $login ) ).'
						ORDER BY user_lastseen_ts DESC, user_status ASC
						LIMIT 3', ARRAY_A );

		// Make sure to return at least one hashed password, to make it unable to guess if user exists with the given login
		if( empty( $salts ) )
		{ // User with the given login was not found add one random salt value
			$salts[] = array(
					'user_salt'        => generate_random_key( 8 ),
					'user_pass_driver' => 'evo$salted',
				);
		}

		$result['salts'] = array();
		$result['hash_algo'] = array();
		foreach( $salts as $salt )
		{
			// Get password driver by code:
			$user_PasswordDriver = get_PasswordDriver( $salt['user_pass_driver'] );

			if( ! $user_PasswordDriver )
			{	// Skip this user because he has an unknown password driver:
				continue;
			}

			$result['salts'][] = $salt['user_salt'];
			$result['hash_algo'][] = $user_PasswordDriver->get_javascript_hash_code( 'raw_password', 'salts[index]' );
		}

		echo evo_json_encode( $result );

		exit(0); // Exit here in order to don't display the AJAX debug info after JSON formatted data
		break;

	case 'crop':
		// Get form to crop profile picture

		if( ! is_logged_in() )
		{ // Only the logged in user can crop pictures
			break;
		}

		$file_ID = param( 'file_ID', 'integer' );
		$cropped_File = & $current_User->get_File_by_ID( $file_ID, $error_code );
		if( ! $cropped_File )
		{ // Wrong file for cropping
			break;
		}

		$BlogCache = &get_BlogCache();
		$Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, true );
		$skin_ID = $Blog->get_skin_ID();
		$SkinCache = & get_SkinCache();
		$Skin = & $SkinCache->get_by_ID( $skin_ID );

		$display_mode = 'js';
		$form_action = get_htsrv_url().'profile_update.php';

		$window_width = param( 'window_width', 'integer' );
		$window_height = param( 'window_height', 'integer' );

		require $inc_path.'users/views/_user_crop.form.php';
		break;

	case 'get_user_report_form':
		// Get form to report for user

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'user' );
		$user_ID = param( 'user_ID', 'integer', true );

		if( ! is_logged_in() || ( isset( $User ) && $current_User->ID == $User->ID ) || ! $current_User->check_status( 'can_report_user', $user_ID ) )
		{ // Only if current user can reports
			break;
		}

		$UserCache = & get_UserCache();
		$edited_User = & $UserCache->get_by_ID( $user_ID );

		if( param( 'is_backoffice', 'integer', 0 ) )
		{ // Load the AdminUI class for the skin.
			$user_tab = param( 'user_tab', 'string' );
			global $current_User, $UserSettings, $is_admin_page;
			$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
			$is_admin_page = true;
			require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
			$AdminUI = new AdminUI();
		}
		else
		{ // Load Blog skin
			$BlogCache = & get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, true );
			$skin_ID = $Blog->get_skin_ID();
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $skin_ID );
		}

		$display_mode = 'js';
		$form_action = get_htsrv_url().'profile_update.php';

		require $inc_path.'users/views/_user_report.form.php';
		break;

	case 'get_user_contact_form':
		// Get form to add/edit user to contact

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'user' );

		if( ! is_logged_in() || ( isset( $User ) && $current_User->ID == $User->ID ) ||
		    ! $current_User->check_perm( 'perm_messaging', 'reply' ) ||
				! $current_User->check_status( 'can_edit_contacts' ) )
		{ // Only if current user can reports
			break;
		}

		$user_ID = param( 'user_ID', 'integer', true );
		$UserCache = & get_UserCache();
		$edited_User = & $UserCache->get_by_ID( $user_ID );

		if( param( 'is_backoffice', 'integer', 0 ) )
		{ // Load the AdminUI class for the skin.
			$user_tab = param( 'user_tab', 'string' );
			global $current_User, $UserSettings, $is_admin_page;
			$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
			$is_admin_page = true;
			require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
			$AdminUI = new AdminUI();
		}
		else
		{ // Load Blog skin
			$BlogCache = & get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, true );
			$skin_ID = $Blog->get_skin_ID();
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $skin_ID );
		}

		$display_mode = 'js';
		$form_action = get_htsrv_url().'profile_update.php';

		require $inc_path.'users/views/_user_groups.form.php';
		break;

	case 'set_object_link_position':
		// Change a position of a link on the edit item screen (fieldset "Images & Attachments")

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'link' );

		// Check item/comment edit permission below after we have the $LinkOwner object ( we call LinkOwner->check_perm ... )

		param('link_ID', 'integer', true);
		param('link_position', 'string', true);

		// Don't display the inline position reminder again until the user logs out or loses the session cookie
		if( $link_position == 'inline' )
		{
			$Session->set( 'display_inline_reminder', 'false' );
		}

		$LinkCache = & get_LinkCache();
		if( ( $Link = & $LinkCache->get_by_ID( $link_ID ) ) === false )
		{	// Bad request with incorrect link ID
			echo '';
			exit(0);
		}
		$LinkOwner = & $Link->get_LinkOwner();

		// Check permission:
		$LinkOwner->check_perm( 'edit', true );

		if( $Link->set( 'position', $link_position ) && $Link->dbupdate() )
		{ // update was successful
			echo 'OK';

			// Update last touched date of Owners
			$LinkOwner->update_last_touched_date();

			if( $link_position == 'cover' && $LinkOwner->type == 'item' )
			{ // Position "Cover" can be used only by one link
			  // Replace previous position with "Inline"
				$DB->query( 'UPDATE T_links
						SET link_position = "aftermore"
					WHERE link_ID != '.$DB->quote( $link_ID ).'
						AND link_itm_ID = '.$DB->quote( $LinkOwner->Item->ID ).'
						AND link_position = "cover"' );
			}
		}
		else
		{ // return the current value on failure
			echo $Link->get( 'position' );
		}
		break;

	case 'update_links_order':
		// Update the order of all links at one time:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'link' );

		$link_IDs = param( 'links', 'string' );

		if( empty( $link_IDs ) )
		{ // No links to update, wrong request, exit here:
			break;
		}

		$link_IDs = explode( ',', $link_IDs );

		// Check permission by first link:
		$LinkCache = & get_LinkCache();
		if( ( $Link = & $LinkCache->get_by_ID( $link_IDs[0] ) ) === false )
		{ // Bad request with incorrect link ID
			exit(0);
		}
		$LinkOwner = & $Link->get_LinkOwner();
		// Check permission:
		$LinkOwner->check_perm( 'edit', true );

		$DB->begin( 'SERIALIZABLE' );

		// Get max order value of the links:
		$max_link_order = intval( $DB->get_var( 'SELECT MAX( link_order )
			 FROM T_links
			WHERE link_ID IN ( '.$DB->quote( $link_IDs ).' )' ) );

		// Initialize parts of sql queries to update the links order:
		$fake_sql_update_strings = '';
		$real_sql_update_strings = '';
		$real_link_order = 0;
		$link_order = array();
		foreach( $link_IDs as $link_ID )
		{
			$max_link_order++;
			$fake_sql_update_strings .= ' WHEN link_ID = '.$DB->quote( $link_ID ).' THEN '.$max_link_order;
			$real_link_order++;
			$real_sql_update_strings .= ' WHEN link_ID = '.$DB->quote( $link_ID ).' THEN '.$real_link_order;
			$link_order[$link_ID] = $real_link_order;
		}

		// Do firstly fake ordering start with max order, to avoid duplicate entry error:
		$DB->query( 'UPDATE T_links
			  SET link_order = CASE '.$fake_sql_update_strings.' ELSE link_order END
			WHERE link_ID IN ( '.$DB->quote( $link_IDs ).' )' );
		// Do real ordering start with number 1:
		$DB->query( 'UPDATE T_links
			  SET link_order = CASE '.$real_sql_update_strings.' ELSE link_order END
			WHERE link_ID IN ( '.$DB->quote( $link_IDs ).' )' );

		$DB->commit();
		echo json_encode( $link_order );
		break;

	case 'test_api':
		// Spec action to test API from ctrl=system:
		echo 'ok';
		break;

	case 'get_file_select_item':
		$field_params = param( 'params', 'array', true );
		$field_name = param( 'field_name', 'string', true );
		$root = param( 'root', 'string', true );
		$file_path = param( 'path', 'string', true );

		$FileCache = & get_FileCache();
		list( $root_type, $root_in_type_ID ) = explode( '_', $root, 2 );
		if( ! ( $current_File = $FileCache->get_by_root_and_path( $root_type, $root_in_type_ID, $file_path ) ) )
		{	// No file:
			debug_die( 'No such file' );
			// Exit here.
		}

		if( ! $current_File->is_image() )
		{
			debug_die( 'Incorrect file type for '.$field_name );
		}

		// decode params with HTML tags
		$field_params['field_item_start'] = base64_decode( $field_params['field_item_start'] );
		$field_params['field_item_end'] = base64_decode( $field_params['field_item_end'] );

		$current_File->load_meta( true ); // erhsatingin > can we force create file meta in DB here or should this whole thing require login?
		$r = file_select_item( $current_File->ID, $field_params );

		echo json_encode( array(
				'fieldName' => $field_name,
				'fieldValue' => $current_File->ID,
				'item' => base64_encode( $r )
			) );
		break;

	default:
		$Ajaxlog->add( T_('Incorrect action!'), 'error' );
		break;
}

$disp = NULL;
$ctrl = NULL;

if( $current_debug || $current_debug_jslog )
{	// debug is ON
	$Ajaxlog->display( NULL, NULL, true, 'all',
					array(
							'error' => array( 'class' => 'jslog_error', 'divClass' => false ),
							'note'  => array( 'class' => 'jslog_note',  'divClass' => false ),
						), 'ul', 'jslog' );
}

// Add ajax response end comment:
echo '<!-- Ajax response end -->';

exit(0);

?>

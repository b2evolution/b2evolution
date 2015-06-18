<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var Item
 */
global $edited_Item;
/**
 * @var Blog
 */
global $Blog;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var UserSettings
 */
global $UserSettings;

global $pagenow;

global $Session;

global $mode, $admin_url, $rsc_url;
global $post_comment_status, $trackback_url, $item_tags;
global $bozo_start_modified, $creating;
global $item_title, $item_content;
global $redirect_to;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

// Used to mark the required fields (in non-standard template)
$required_star = '<span class="label_field_required">*</span>';

$Form = new Form( NULL, 'item_checkchanges', 'post' );
$Form->labelstart = '<strong>';
$Form->labelend = "</strong>\n";


// ================================ START OF EDIT FORM ================================

$iframe_name = NULL;
$params = array();
if( !empty( $bozo_start_modified ) )
{
	$params['bozo_start_modified'] = true;
}

$Form->begin_form( '', '', $params );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $Blog->ID );
	if( isset( $mode ) )
	{ // used by bookmarklet
		$Form->hidden( 'mode', $mode );
	}
	if( isset( $edited_Item ) )
	{
		// Item ID
		$Form->hidden( 'post_ID', $edited_Item->ID );
	}
	$Form->hidden( 'redirect_to', $redirect_to );

	// In case we send this to the blog for a preview :
	$Form->hidden( 'preview', 1 );
	$Form->hidden( 'more', 1 );
	$Form->hidden( 'preview_userid', $current_User->ID );

	// Post type
	$Form->hidden( 'item_typ_ID', $edited_Item->ityp_ID );
?>
<div class="row">

<div class="left_col col-md-9">

	<?php
	// ############################ WORKFLOW #############################

	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$Form->begin_fieldset( T_('Workflow properties'), array( 'id' => 'itemform_workflow_props', 'fold' => true ) );

			echo '<div id="itemform_edit_workflow" class="edit_fieldgroup">';
			$Form->switch_layout( 'linespan' );

			$Form->select_input_array( 'item_priority', $edited_Item->priority, item_priority_titles(), T_('Priority'), '', array( 'force_keys_as_values' => true ) );

			echo ' '; // allow wrapping!

			// Load current blog members into cache:
			$UserCache = & get_UserCache();
			// Load only first 21 users to know when we should display an input box instead of full users list
			$UserCache->load_blogmembers( $Blog->ID, 21, false );

			if( count( $UserCache->cache ) > 20 )
			{
				$assigned_User = & $UserCache->get_by_ID( $edited_Item->get( 'assigned_user_ID' ), false, false );
				$Form->username( 'item_assigned_user_login', $assigned_User, T_('Assigned to'), '', 'only_assignees' );
			}
			else
			{
				$Form->select_object( 'item_assigned_user_ID', NULL, $edited_Item, T_('Assigned to'),
														'', true, '', 'get_assigned_user_options' );
			}

			echo ' '; // allow wrapping!

			$ItemStatusCache = & get_ItemStatusCache();
			$Form->select_options( 'item_st_ID', $ItemStatusCache->get_option_list( $edited_Item->pst_ID, true ), T_('Task status') );

			echo ' '; // allow wrapping!

			$Form->date( 'item_deadline', $edited_Item->get('datedeadline'), T_('Deadline') );

			$Form->switch_layout( NULL );
			echo '</div>';

		$Form->end_fieldset();
	}


	// ############################ POST CONTENTS #############################

	$item_type_link = $edited_Item->get_type_edit_link( 'link', $edited_Item->get( 't_type' ), T_('Change type') );
	$Form->begin_fieldset( sprintf( T_('%s contents'), $item_type_link ).get_manual_link('post_contents_fieldset'), array( 'id' => 'itemform_content' ) );

	$Form->switch_layout( 'none' );

	echo '<table cellspacing="0" class="compose_layout" align="center"><tr>';
	$display_title_field = $edited_Item->get_type_setting( 'use_title' ) != 'never';
	if( $display_title_field )
	{ // Display title
		$field_required = ( $edited_Item->get_type_setting( 'use_title' ) == 'required' ) ? $required_star : '';
		echo '<td width="1%" class="label">'.$field_required.'<strong>'.T_('Title').':</strong></td>';
		echo '<td width="97%" class="input">';
		$Form->text_input( 'post_title', $item_title, 20, '', '', array( 'maxlength' => 255, 'style' => 'width: 100%;' ) );
		echo '</td>';
	}
	else
	{ // Hide title
		$Form->hidden( 'post_title', $item_title );
	}

	// -- Language chooser BEGIN --
	$locale_options = locale_options( $edited_Item->get( 'locale' ), false, true );

	if( is_array( $locale_options ) )
	{ // We've only one enabled locale.
		// Tblue> The locale name is not really needed here, but maybe we
		//        want to display the name of the only locale?
		$Form->hidden( 'post_locale', $locale_options[0] );
		//pre_dump( $locale_options );
	}
	else
	{ // More than one locale => select field.
		echo '<td width="1%" class="label">';
		if( $display_title_field )
		{
			echo '&nbsp;&nbsp;';
		}
		echo '<strong>'.T_('Language').':</strong></td>';
		echo '<td width="1%" class="select">';
		$Form->select_options( 'post_locale', $locale_options, '' );
		echo '</td>';
	}
	// -- Language chooser END --
	echo '</tr></table>';

	if( $edited_Item->get_type_setting( 'use_url' ) != 'never' )
	{ // Display url
		$field_required = ( $edited_Item->get_type_setting( 'use_url' ) == 'required' ) ? $required_star : '';
		echo '<table cellspacing="0" class="compose_layout" align="center"><tr>';
		echo '<td width="1%" class="label">'.$field_required.'<strong>'.T_('Link to url').':</strong></td>';
		echo '<td class="input" style="padding-right:2px">';
		$Form->text_input( 'post_url', $edited_Item->get( 'url' ), 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
		echo '</td>';
		echo '</tr></table>';
	}
	else
	{ // Hide url
		$Form->hidden( 'post_url', $edited_Item->get( 'url' ) );
	}

	$Form->switch_layout( NULL );

	if( $edited_Item->get_type_setting( 'use_text' ) != 'never' )
	{ // Display text
		// --------------------------- TOOLBARS ------------------------------------
		echo '<div class="edit_toolbars">';
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'AdminDisplayToolbar', array(
				'target_type' => 'Item',
				'edit_layout' => 'expert',
				'Item' => $edited_Item,
			) );
		echo '</div>';

		// ---------------------------- TEXTAREA -------------------------------------
		$Form->fieldstart = '<div class="edit_area">';
		$Form->fieldend = "</div>\n";
		$Form->textarea_input( 'content', $item_content, 16, '', array( 'cols' => 40 , 'id' => 'itemform_post_content', 'class' => 'autocomplete_usernames' ) );
		?>
		<script type="text/javascript" language="JavaScript">
			<!--
			// This is for toolbar plugins
			var b2evoCanvas = document.getElementById('itemform_post_content');
			// -->
		</script>

	<?php
	}
	else
	{ // Hide text
		$Form->hidden( 'content', $item_content );
	}
	$Form->fieldstart = '<div class="tile">';
	$Form->fieldend = '</div>';

	// ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';

	echo '<div class="pull-left">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'expert' ) );
	echo '</div>';

	echo '<div class="pull-right">';
	echo_publish_buttons( $Form, $creating, $edited_Item );
	echo '</div>';

	echo '<div class="clear"></div>';

	echo '</div>';

	$Form->end_fieldset();


	// ####################### ATTACHMENTS/LINKS #########################
	if( isset($GLOBALS['files_Module']) && ( !$creating ||
		( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item )
		&& $current_User->check_perm( 'files', 'view', false ) ) ) )
	{ // Files module is enabled, but in case of creating new posts we should show file attachments block only if user has all required permissions to attach files
		load_class( 'links/model/_linkitem.class.php', 'LinkItem' );
		$LinkOwner = new LinkItem( $edited_Item );
		attachment_iframe( $Form, $LinkOwner, $iframe_name, $creating, true );
	}
	// ############################ ADVANCED #############################

	$Form->begin_fieldset( T_('Advanced properties').get_manual_link('post_advanced_properties_fieldset'), array( 'id' => 'itemform_adv_props', 'fold' => true ) );

	echo '<table cellspacing="0" class="compose_layout">';

	if( ! $edited_Item->get_type_setting( 'use_custom_fields' ) )
	{ // All CUSTOM FIELDS are hidden by post type
		display_hidden_custom_fields( $Form, $edited_Item );
	}
	else
	{ // CUSTOM FIELDS varchar
		$custom_fields = $edited_Item->get_type_custom_fields( 'varchar' );
		foreach( $custom_fields as $custom_field )
		{ // Loop through custom varchar fields
			echo '<tr><td class="label"><label for="item_varchar_'.$custom_field['ID'].'"><strong>'.$custom_field['label'].':</strong></label></td>';
			echo '<td class="input" width="97%">';
			$Form->text_input( 'item_varchar_'.$custom_field['ID'], $edited_Item->get_setting( 'custom_varchar_'.$custom_field['ID'] ), 20, '', '', array( 'maxlength' => 255, 'style' => 'width: 100%;' ) );
			echo '</td></tr>';
		}
	}

	//add slug_changed field - needed for slug trim, if this field = 0 slug will trimmed
	$Form->hidden( 'slug_changed', 0 );

	$edit_slug_link = '';
	if( $edited_Item->ID > 0 && $current_User->check_perm( 'slugs', 'view' ) )
	{ // user has permission to view slugs:
		$edit_slug_link = action_icon( T_('Edit slugs'), 'edit', $admin_url.'?ctrl=slugs&amp;slug_item_ID='.$edited_Item->ID, T_('Edit slugs'), 3, 4 )
			// TRANS: Full phrase is "<a href="">Edit slugs</a> for this post"
			.' '.T_('for this post').' - ';
	}

	if( empty( $edited_Item->tiny_slug_ID ) )
	{
		$tiny_slug_info = T_('No Tiny URL yet.');
	}
	else
	{
		$tiny_slug_info = $edited_Item->get_tinyurl_link( array(
				'before' => T_('Tiny URL').': ',
				'after'  => ''
			) );
	}

	echo '<tr><td class="label" valign="top"><label for="post_urltitle" title="'.T_('&quot;slug&quot; to be used in permalinks').'"><strong>'.T_('URL slugs').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'post_urltitle', $edited_Item->get_slugs(), 40, '', '<br />'.$edit_slug_link.$tiny_slug_info, array( 'maxlength' => 210, 'style' => 'width: 100%;' ) );
	echo '</td></tr>';

	if( $edited_Item->get_type_setting( 'use_tags' ) != 'never' )
	{ // Display tags
		$field_required = ( $edited_Item->get_type_setting( 'use_tags' ) == 'required' ) ? $required_star : '';
		echo '<tr><td class="label"><label for="item_tags">'.$field_required.'<strong>'.T_('Tags').':</strong></label></td>';
		echo '<td class="input" width="97%">';

		$link_to_tags_manager = '';
		if( $current_User->check_perm( 'options', 'view' ) )
		{ // Display a link to manage tags only when current use has the rights
			$link_to_tags_manager = ' &ndash; <a href="'.$admin_url.'?ctrl=itemtags&amp;tag_item_ID='.$edited_Item->ID.'">'.T_('Go to tags manager').'</a>';
		}
		// Checkbox to suggest tags
		$suggest_checkbox = '<label>'
				.'<input id="suggest_item_tags" name="suggest_item_tags" value="1" type="checkbox"'.( $UserSettings->get( 'suggest_item_tags' ) ? ' checked="checked"' : '' ).' /> '
				.T_('Auto-suggest tags as you type (based on existing tag)').$link_to_tags_manager
			.'</label>';
		$Form->text_input( 'item_tags', $item_tags, 40, '', $suggest_checkbox, array( 'maxlength' => 255, 'style' => 'width: 100%;' ) );
		echo '</td></tr>';
	}
	else
	{ // Hide tags
		$Form->hidden( 'item_tags', $item_tags );
	}

	$edited_Item_excerpt = $edited_Item->get('excerpt');
	if( $edited_Item->get_type_setting( 'use_excerpt' ) != 'never' )
	{ // Display excerpt
		$field_required = ( $edited_Item->get_type_setting( 'use_excerpt' ) == 'required' ) ? $required_star : '';
		$field_class = param_has_error( 'post_excerpt' ) ? ' field_error' : '';
		echo '<tr><td class="label"><label for="post_excerpt">'.$field_required.'<strong>'.T_('Excerpt').':</strong></label></td>';
		echo '<td class="input" width="97%">';
		$Form->textarea_input( 'post_excerpt', $edited_Item_excerpt, 3, '', array(
				'class'    => $field_class,
				'required' => $field_required,
				'style'    => 'width:100%'
			) );
		echo '</td></tr>';
	}
	else
	{ // Hide excerpt
		$Form->hidden( 'post_excerpt', htmlspecialchars( $edited_Item_excerpt ) );
	}

	if( $edited_Item->get_type_setting( 'use_title_tag' ) != 'never' )
	{ // Display <title> tag
		$field_required = ( $edited_Item->get_type_setting( 'use_title_tag' ) == 'required' ) ? $required_star : '';
		echo '<tr><td class="label"><label for="titletag">'.$field_required.'<strong>'.T_('&lt;title&gt; tag').':</strong></label></td>';
		echo '<td class="input" width="97%">';
		$Form->text_input( 'titletag', $edited_Item->get('titletag'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
		echo '</td></tr>';
	}
	else
	{ // Hide <title> tag
		$Form->hidden( 'titletag', $edited_Item->get('titletag') );
	}

	if( $edited_Item->get_type_setting( 'use_meta_desc' ) != 'never' )
	{ // Display <meta> description
		$field_required = ( $edited_Item->get_type_setting( 'use_meta_desc' ) == 'required' ) ? $required_star : '';
		echo '<tr><td class="label"><label for="metadesc" title="&lt;meta name=&quot;description&quot;&gt;">'.$field_required.'<strong>'.T_('&lt;meta&gt; desc').':</strong></label></td>';
		echo '<td class="input" width="97%">';
		$Form->text_input( 'metadesc', $edited_Item->get_setting('metadesc'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
		echo '</td></tr>';
	}
	else
	{ // Hide <meta> description
		$Form->hidden( 'metadesc', $edited_Item->get_setting('metadesc') );
	}

	if( $edited_Item->get_type_setting( 'use_meta_keywds' ) != 'never' )
	{ // Display <meta> keywords
		$field_required = ( $edited_Item->get_type_setting( 'use_meta_keywds' ) == 'required' ) ? $required_star : '';
		echo '<tr><td class="label"><label for="metakeywords" title="&lt;meta name=&quot;keywords&quot;&gt;">'.$field_required.'<strong>'.T_('&lt;meta&gt; keywds').':</strong></label></td>';
		echo '<td class="input" width="97%">';
		$Form->text_input( 'metakeywords', $edited_Item->get_setting('metakeywords'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
		echo '</td></tr>';
	}
	else
	{ // Hide <meta> keywords
		$Form->hidden( 'metakeywords', $edited_Item->get_setting('metakeywords') );
	}

	echo '</table>';

	if( $edited_Item->get('excerpt_autogenerated') )
	{ // store hash of current post_excerpt to detect if it was changed.
		$Form->hidden('post_excerpt_previous_md5', md5($edited_Item_excerpt));
	}
	$Form->end_fieldset();


	// ####################### ADDITIONAL ACTIONS #########################

	if( isset( $Blog ) && $Blog->get('allowtrackbacks') )
	{
		$Form->begin_fieldset( T_('Additional actions'), array( 'id' => 'itemform_additional_actions', 'fold' => true ) );

		// --------------------------- TRACKBACK --------------------------------------
		?>
		<div id="itemform_trackbacks">
			<label for="trackback_url"><strong><?php echo T_('Trackback URLs') ?>:</strong>
			<span class="notes"><?php echo T_('(Separate by space)') ?></span></label><br />
			<input type="text" name="trackback_url" class="large form_text_input" id="trackback_url" value="<?php echo format_to_output( $trackback_url, 'formvalue' ); ?>" />
		</div>
		<?php

		$Form->end_fieldset();
	}


	// ####################### PLUGIN FIELDSETS #########################

	$Plugins->trigger_event( 'AdminDisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item, 'edit_layout' => 'expert' ) );

	if( $current_User->check_perm( 'meta_comment', 'view', false, $edited_Item ) )
	{
		// ####################### META COMMENTS #########################
		$currentpage = param( 'currentpage', 'integer', 1 );
		$total_comments_number = generic_ctp_number( $edited_Item->ID, 'metas', 'total' );
		param( 'comments_number', 'integer', $total_comments_number );

		$Form->begin_fieldset( T_('Meta comments')
						.( $total_comments_number > 0 ? ' <span class="badge badge-important">'.$total_comments_number.'</span>' : '' ),
					array( 'id' => 'itemform_meta_cmnt', 'fold' => true, 'deny_fold' => ( $total_comments_number > 0 ) ) );

		global $CommentList;
		$CommentList = new CommentList2( $Blog );

		// Filter list:
		$CommentList->set_filters( array(
			'types' => array( 'meta' ),
			'statuses' => get_visibility_statuses( 'keys', array( 'redirected', 'trash' ) ),
			'order' => 'ASC',
			'post_ID' => $edited_Item->ID,
			'comments' => 20,
			'page' => $currentpage,
			'expiry_statuses' => array( 'active' ),
		) );
		$CommentList->query();

		// comments_container value shows, current Item ID
		echo '<div id="styled_content_block">';
		echo '<div id="comments_container" value="'.$edited_Item->ID.'">';
		// display comments
		$CommentList->display_if_empty( array(
				'before'    => '<div class="bComment"><p>',
				'after'     => '</p></div>',
				'msg_empty' => T_('No feedback for this post yet...'),
			) );
		require $inc_path.'comments/views/_comment_list.inc.php';
		echo '</div>'; // comments_container div
		echo '</div>';

		if( $current_User->check_perm( 'meta_comment', 'add', false, $edited_Item ) )
		{ // Display a link to add new meta comment if current user has a permission
			echo action_icon( T_('Add a meta comment'), 'new', $admin_url.'?ctrl=items&amp;p='.$edited_Item->ID.'&amp;comment_type=meta&amp;blog='.$Blog->ID.'#comments', T_('Add a meta comment').' &raquo;', 3, 4 );
		}

		$Form->end_fieldset();
	}
	?>

</div>

<div class="right_col col-md-3">

	<?php
	// ################### MODULES SPECIFIC ITEM SETTINGS ###################

	modules_call_method( 'display_item_settings', array( 'Form' => & $Form, 'Blog' => & $Blog, 'edited_Item' => & $edited_Item, 'edit_layout' => 'expert', 'fold' => true ) );

	// ################### CATEGORIES ###################

	cat_select( $Form, true, true, array( 'fold' => true ) );

	// ################### LOCATIONS ###################
	echo_item_location_form( $Form, $edited_Item, array( 'fold' => true ) );

	// ################### PROPERTIES ###################

	$Form->begin_fieldset( T_('Properties'), array( 'id' => 'itemform_extra', 'fold' => true ) );

	$Form->switch_layout( 'linespan' );

	if( $edited_Item->get_type_setting( 'allow_featured' ) )
	{ // Display featured
		$Form->checkbox_basic_input( 'item_featured', $edited_Item->featured, '<strong>'.T_('Featured post').'</strong>' );
	}
	else
	{ // Hide featured
		$Form->hidden( 'item_featured', $edited_Item->featured );
	}

	$Form->checkbox_basic_input( 'item_hideteaser', $edited_Item->get_setting( 'hide_teaser' ), '<strong>'.T_('Hide teaser when displaying -- more --').'</strong>' );

	if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';
		issue_date_control( $Form, true );
		echo '</div>';
	}

	echo '<table>';

	echo '<tr><td><strong>'.T_('Order').':</strong></td><td>';
	$Form->text( 'item_order', $edited_Item->order, 10, '', T_('can be decimal') );
	echo '</td></tr>';

	if( $current_User->check_perm( 'users', 'edit' ) )
	{
		echo '<tr><td><strong>'.T_('Owner').':</strong></td><td>';
		$Form->username( 'item_owner_login', $edited_Item->get_creator_User(), '', T_( 'login of this post\'s owner.').'<br/>' );
		$Form->hidden( 'item_owner_login_displayed', 1 );
		echo '</td></tr>';
	}

	if( $edited_Item->get_type_setting( 'use_coordinates' ) != 'never' )
	{ // Dispaly Latitude & Longitude settings
		$field_required = ( $edited_Item->get_type_setting( 'use_coordinates' ) == 'required' ) ? $required_star : '';
		echo '<tr><td>'.$field_required.'<strong>'.T_('Latitude').':</strong></td><td>';
		$Form->text( 'item_latitude', $edited_Item->get_setting( 'latitude' ), 10, '' );
		echo '</td></tr>';
		echo '<tr><td>'.$field_required.'<strong>'.T_('Longitude').':</strong></td><td>';
		$Form->text( 'item_longitude', $edited_Item->get_setting( 'longitude' ), 10, '' );
		echo '</td></tr>';
	}

	if( $edited_Item->get_type_setting( 'use_custom_fields' ) )
	{ // Display CUSTOM FIELDS double only when its are allowed by post type setting
		$custom_fields = $edited_Item->get_type_custom_fields( 'double' );
		foreach( $custom_fields as $custom_field )
		{ // Loop through custom double fields
			echo '<tr><td><strong>'.$custom_field['label'].':</strong></td><td>';
			$Form->text( 'item_double_'.$custom_field['ID'], $edited_Item->get_setting( 'custom_double_'.$custom_field['ID'] ), 10, '', T_('can be decimal') );
			echo '</td></tr>';
		}
	}

	echo '</table>';

	$Form->switch_layout( NULL );

	$Form->end_fieldset();


	// ################### TEXT RENDERERS ###################

	$Form->begin_fieldset( T_('Text Renderers')
					.action_icon( T_('Plugins'), 'edit', $admin_url.'?ctrl=coll_settings&amp;tab=plugin_settings&amp;blog='.$Blog->ID, T_('Plugins'), 3, 4, array( 'class' => 'action_icon pull-right' ) ),
				array( 'id' => 'itemform_renderers', 'fold' => true ) );

	// fp> TODO: there should be no param call here (shld be in controller)
	$edited_Item->renderer_checkboxes( param('renderers', 'array:string', NULL) );

	$Form->end_fieldset();


	// ################### COMMENT STATUS ###################

	if( $edited_Item->allow_comment_statuses() )
	{
		$Form->begin_fieldset( T_('Comments'), array( 'id' => 'itemform_comments', 'fold' => true ) );

		?>
			<label title="<?php echo T_('Visitors can leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="open" class="checkbox" <?php if( $post_comment_status == 'open' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Open') ?></label><br />
		<?php
		if( $edited_Item->get_type_setting( 'allow_closing_comments' ) )
		{ // Allow closing comments
		?>
			<label title="<?php echo T_('Visitors can NOT leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="closed" class="checkbox" <?php if( $post_comment_status == 'closed' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Closed') ?></label><br />
		<?php
		}

		if( $edited_Item->get_type_setting( 'allow_disabling_comments' ) )
		{ // Allow disabling comments
		?>
			<label title="<?php echo T_('Visitors cannot see nor leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="disabled" class="checkbox" <?php if( $post_comment_status == 'disabled' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Disabled') ?></label><br />
		<?php
		}

		if( $edited_Item->get_type_setting( 'use_comment_expiration' ) != 'never' )
		{ // Display comment expiration
			$Form->switch_layout( 'table' );
			$Form->duration_input( 'expiry_delay',  $edited_Item->get_setting( 'comment_expiry_delay' ), T_('Expiry delay'), 'months', 'hours',
							array( 'minutes_step' => 1,
								'required' => $edited_Item->get_type_setting( 'use_comment_expiration' ) == 'required',
								'note' => T_( 'Older comments and ratings will no longer be displayed.' ) ) );
			$Form->switch_layout( NULL );
		}
		else
		{ // Hide comment expiration
			$Form->hidden( 'expiry_delay',  $edited_Item->get_setting( 'comment_expiry_delay' ) );
		}

		$Form->end_fieldset();
	}


	// ################### GOAL TRACKING ###################

	$Form->begin_fieldset( T_('Goal tracking').get_manual_link( 'track-item-as-goal' )
					.action_icon( T_('Goals'), 'edit', $admin_url.'?ctrl=goals&amp;blog='.$Blog->ID, T_('Goals'), 3, 4, array( 'class' => 'action_icon pull-right' ) ),
				array( 'id' => 'itemform_goals', 'fold' => true ) );

	$Form->switch_layout( 'table' );
	$Form->formstart = '<table id="item_locations" cellspacing="0" class="fform">'."\n";
	$Form->labelstart = '<td class="right"><strong>';
	$Form->labelend = '</strong></td>';

	echo '<p class="note">'.T_( 'You can track a hit on a goal every time this page is displayed to a user.' ).'</p>';

	echo $Form->formstart;

	$goal_ID = $edited_Item->get_setting( 'goal_ID' );
	$item_goal_cat_ID = 0;
	$GoalCache = & get_GoalCache();
	if( ! empty( $goal_ID ) && $item_Goal = $GoalCache->get_by_ID( $goal_ID, false, false ) )
	{ // Get category ID of goal
		$item_goal_cat_ID = $item_Goal->gcat_ID;
	}

	$GoalCategoryCache = & get_GoalCategoryCache( NT_( 'No Category' ) );
	$GoalCategoryCache->load_all();
	$Form->select_input_object( 'goal_cat_ID', $item_goal_cat_ID, $GoalCategoryCache, T_('Category'), array( 'allow_none' => true ) );

	// Get only the goals without a defined redirect url
	$goals_where_sql = 'goal_redir_url IS NULL';
	if( empty( $item_goal_cat_ID ) )
	{ // Get the goals without category
		$goals_where_sql .= ' AND goal_gcat_ID IS NULL';
	}
	else
	{ // Get the goals by category ID
		$goals_where_sql .= ' AND goal_gcat_ID = '.$DB->quote( $item_goal_cat_ID );
	}
	$GoalCache->load_where( $goals_where_sql );
	$Form->select_input_object( 'goal_ID', $edited_Item->get_setting( 'goal_ID' ), $GoalCache,
		get_icon( 'multi_action', 'imgtag', array( 'style' => 'margin:0 5px 0 14px;position:relative;top:-1px;') ).T_('Goal'),
		array(
			'allow_none' => true,
			'note' => '<img src="'.$rsc_url.'img/ajax-loader.gif" alt="'.T_('Loading...').'" title="'.T_('Loading...').'" style="display:none;margin-left:5px" align="top" />'
		) );

	echo $Form->formend;

	$Form->switch_layout( NULL );

	$Form->end_fieldset();

	?>

</div>

<div class="clear"></div>

</div>

<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();

// ####################### JS BEHAVIORS #########################
// JS code for status dropdown select button
echo_status_dropdown_button_js( 'post' );
echo_link_files_js();
echo_autocomplete_tags();
if( empty( $edited_Item->ID ) )
{ // if we creating new post - we add slug autofiller JS
	echo_slug_filler();
}
else
{	// if we are editing the post
	echo_set_slug_changed();
}
// New category input box:
echo_onchange_newcat();
// Location
echo_regional_js( 'item', $edited_Item->region_visible() );
// Post type
echo_onchange_item_type_js();
// Goal
echo_onchange_goal_cat();
// Fieldset folding
echo_fieldset_folding_js();

// require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';

?>
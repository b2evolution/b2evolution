<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id: _item_expert.form.php 6479 2014-04-16 07:18:54Z yura $
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

global $pagenow;

global $Session, $evo_charset;

global $mode, $admin_url, $rsc_url;
global $post_comment_status, $trackback_url, $item_tags;
global $bozo_start_modified, $creating;
global $item_title, $item_content;
global $redirect_to;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

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
	$Form->hidden( 'mode_editing', 'expert' ); // used by in-skin mode when we want back in a last editing mode
	if( isset( $mode ) )   $Form->hidden( 'mode', $mode );	// used by bookmarklet
	if( isset( $edited_Item ) )
	{
		if( $action == 'copy' )
		{	// Copy post
			$Form->hidden( 'post_ID', 0 );
		}
		else
		{	// Edit post
			$Form->hidden( 'post_ID', $edited_Item->ID );
		}

		// Here we add js code for attaching file popup window: (Yury)
		if( !empty( $edited_Item->ID ) && ( $Session->get('create_edit_attachment') === true ) )
		{	// item also created => we have $edited_Item->ID for popup window
			echo_attaching_files_button_js( $iframe_name );
			// clear session variable
			$Session->delete('create_edit_attachment');
		}
	}
	$Form->hidden( 'redirect_to', $redirect_to );

	// In case we send this to the blog for a preview :
	$Form->hidden( 'preview', 1 );
	$Form->hidden( 'more', 1 );
	$Form->hidden( 'preview_userid', $current_User->ID );

?>
<div class="row">

<div class="left_col col-md-9">

	<?php
	// ############################ WORKFLOW #############################

	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$Form->begin_fieldset( T_('Workflow properties'), array( 'id' => 'itemform_workflow_props' ) );

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

	$Form->begin_fieldset( T_('Post contents').get_manual_link('post_contents_fieldset') );

	$Form->switch_layout( 'none' );

	echo '<table cellspacing="0" class="compose_layout"><tr>';
	$display_title_field = $Blog->get_setting('require_title') != 'none';
	if( $display_title_field )
	{
		echo '<td width="1%"><strong>'.T_('Title').':</strong></td>';
		echo '<td width="97%" class="input">';
		$Form->text_input( 'post_title', $item_title, 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;', 'required' => $Blog->get_setting('require_title') == 'required') );
		echo '</td>';
	}

	// -- Language chooser BEGIN --
	$locale_options = locale_options( $edited_Item->get( 'locale' ), false, true );

	if ( is_array( $locale_options ) )
	{	// We've only one enabled locale.
		// Tblue> The locale name is not really needed here, but maybe we
		//        want to display the name of the only locale?
		$Form->hidden( 'post_locale', $locale_options[0] );
		//pre_dump( $locale_options );
	}
	else
	{	// More than one locale => select field.
		echo '<td width="1%">';
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

	echo '<table cellspacing="0" class="compose_layout"><tr>';
	echo '<td width="1%"><strong>'.T_('Link to url').':</strong></td>';
	echo '<td class="input">';
	$Form->text_input( 'post_url', $edited_Item->get( 'url' ), 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td>';
	echo '<td width="1%">&nbsp;&nbsp;<strong>'.T_('Type').':</strong></td>';
	echo '<td width="1%" class="select">';
	$ItemTypeCache = & get_ItemTypeCache();
	$Form->select_object( 'item_typ_ID', $edited_Item->ptyp_ID, $ItemTypeCache,
								'', '', false, '', 'get_option_list_usable_only' );
	echo '</td>';

	echo '</tr></table>';

 	$Form->switch_layout( NULL );

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
	$Form->textarea_input( 'content', $item_content, 16, '', array( 'cols' => 40 , 'id' => 'itemform_post_content' ) );
	$Form->fieldstart = '<div class="tile">';
	$Form->fieldend = '</div>';
	?>
	<script type="text/javascript" language="JavaScript">
		<!--
		// This is for toolbar plugins
		var b2evoCanvas = document.getElementById('itemform_post_content');
		// -->
	</script>

	<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'expert' ) );

	echo_publish_buttons( $Form, $creating, $edited_Item );

	echo '</div>';

	$Form->end_fieldset();


	// ####################### ATTACHMENTS/LINKS #########################
	if( isset($GLOBALS['files_Module']) && ( !$creating ||
		( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item )
		&& $current_User->check_perm( 'files', 'view', false ) ) ) )
	{ // Files module is enabled, but in case of creating new posts we should show file attachments block only if user has all required permissions to attach files
		load_class( 'links/model/_linkitem.class.php', 'LinkItem' );
		$LinkOwner = new LinkItem( $edited_Item );
		attachment_iframe( $Form, $LinkOwner, $iframe_name, $creating );
	}
	// ############################ ADVANCED #############################

	$Form->begin_fieldset( T_('Advanced properties').get_manual_link('post_advanced_properties_fieldset'), array( 'id' => 'itemform_adv_props' ) );

	// CUSTOM FIELDS varchar
	echo '<table cellspacing="0" class="compose_layout">';
	$field_count = $Blog->get_setting( 'count_custom_varchar' );
	for( $i = 1 ; $i <= $field_count; $i++ )
	{ // Loop through custom varchar fields
		$field_guid = $Blog->get_setting( 'custom_varchar'.$i );
		$field_name = $Blog->get_setting( 'custom_varchar_'.$field_guid );
		// Display field
		echo '<tr><td class="label"><label for="item_varchar_'.$field_guid.'"><strong>'.$field_name.':</strong></label></td>';
		echo '<td class="input" width="97%">';
		$Form->text_input( 'item_varchar_'.$field_guid, $edited_Item->get_setting( 'custom_varchar_'.$field_guid ), 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
		echo '</td></tr>';
	}

	//add slug_changed field - needed for slug trim, if this field = 0 slug will trimmed
	$Form->hidden( 'slug_changed', 0 );

	$edit_slug_link = '';
	if( $edited_Item->ID > 0 && $current_User->check_perm( 'slugs', 'view' ) )
	{	// user has permission to view slugs:
		$edit_slug_link = '&nbsp;'.action_icon( T_('Edit slugs...'), 'edit', $admin_url.'?ctrl=slugs&amp;slug_item_ID='.$edited_Item->ID );
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

	echo '<tr><td class="label" valign="top"><label for="post_urltitle" title="'.T_('&quot;slug&quot; to be used in permalinks').'"><strong>'.T_('URL slugs').$edit_slug_link.':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'post_urltitle', $edited_Item->get_slugs(), 40, '', '<br />'.$tiny_slug_info, array('maxlength'=>210, 'style'=>'width: 100%;') );
	echo '</td></tr>';

	echo '<tr><td class="label"><label for="titletag"><strong>'.T_('&lt;title&gt; tag').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'titletag', $edited_Item->get('titletag'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td></tr>';

	echo '<tr><td class="label"><label for="metadesc" title="&lt;meta name=&quot;description&quot;&gt;"><strong>'.T_('&lt;meta&gt; desc').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'metadesc', $edited_Item->get_setting('post_metadesc'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td></tr>';

	echo '<tr><td class="label"><label for="custom_headers" title="&lt;meta name=&quot;keywords&quot;&gt;"><strong>'.T_('&lt;meta&gt; keywds').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'custom_headers', $edited_Item->get_setting('post_custom_headers'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td></tr>';

	echo '<tr><td class="label"><label for="item_tags"><strong>'.T_('Tags').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'item_tags', $item_tags, 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td></tr>';

	echo '</table>';

 	$Form->switch_layout( 'linespan' );
	echo '<div id="itemform_urltitle" class="edit_fieldgroup">';

	echo '</div>';
	$Form->switch_layout( NULL );

	$edited_Item_excerpt = $edited_Item->get('excerpt');
	?>

	<div id="itemform_post_excerpt" class="edit_fieldgroup">
		<label for="post_excerpt"><strong><?php echo T_('Excerpt') ?>:</strong>
		<span class="notes"><?php echo T_('(for XML feeds)') ?></span></label><br />
		<textarea name="post_excerpt" rows="2" cols="25" class="form-control form_textarea_input" id="post_excerpt"><?php echo evo_htmlspecialchars( $edited_Item_excerpt, NULL, $evo_charset ) ?></textarea>
	</div>

	<?php

	if( $edited_Item->get('excerpt_autogenerated') )
	{ // store hash of current post_excerpt to detect if it was changed.
		$Form->hidden('post_excerpt_previous_md5', md5($edited_Item_excerpt));
	}
	$Form->end_fieldset();


	// ####################### ADDITIONAL ACTIONS #########################

	if( isset( $Blog ) && $Blog->get('allowtrackbacks') )
	{
		$Form->begin_fieldset( T_('Additional actions'), array( 'id' => 'itemform_additional_actions' ) );

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

	?>

</div>

<div class="right_col col-md-3 form-inline">

	<?php
	// ################### MODULES SPECIFIC ITEM SETTINGS ###################

	modules_call_method( 'display_item_settings', array( 'Form' => & $Form, 'Blog' => & $Blog, 'edited_Item' => & $edited_Item ) );

	// ################### CATEGORIES ###################

	cat_select( $Form );

	// ################### LOCATIONS ###################
	echo_item_location_form( $Form, $edited_Item );

	// ################### PROPERTIES ###################

	$Form->begin_fieldset( T_('Properties'), array( 'id' => 'itemform_extra' ) );

	$Form->switch_layout( 'linespan' );

	$Form->checkbox_basic_input( 'item_featured', $edited_Item->featured, '<strong>'.T_('Featured post').'</strong>' );
	echo "<br/>";

	$Form->checkbox_basic_input( 'item_hideteaser', $edited_Item->get_setting( 'hide_teaser' ), '<strong>'.T_('Hide teaser when displaying -- more --').'</strong>' );
	echo "<br/>";

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

	if( $Blog->get_setting( 'show_location_coordinates' ) )
	{ // Dispaly Latitude & Longitude settings
		echo '<tr><td><strong>'.T_('Latitude').':</strong></td><td>';
		$Form->text( 'item_latitude', $edited_Item->get_setting( 'latitude' ), 10, '' );
		echo '</td></tr>';
		echo '<tr><td><strong>'.T_('Longitude').':</strong></td><td>';
		$Form->text( 'item_longitude', $edited_Item->get_setting( 'longitude' ), 10, '' );
		echo '</td></tr>';
	}

	// CUSTOM FIELDS double
	$field_count = $Blog->get_setting( 'count_custom_double' );
	for( $i = 1 ; $i <= $field_count; $i++ )
	{ // Loop through custom double fields
		$field_guid = $Blog->get_setting( 'custom_double'.$i );
		$field_name = $Blog->get_setting( 'custom_double_'.$field_guid );
		// Display field
		echo '<tr><td><strong>'.$field_name.':</strong></td><td>';
		$Form->text( 'item_double_'.$field_guid, $edited_Item->get_setting( 'custom_double_'.$field_guid ), 10, '', T_('can be decimal') );
		echo '</td></tr>';
	}

	echo '</table>';

	$Form->switch_layout( NULL );

	$Form->end_fieldset();


	// ################### VISIBILITY / SHARING ###################

	$Form->begin_fieldset( T_('Visibility / Sharing'), array( 'id' => 'itemform_visibility' ) );

	$Form->switch_layout( 'linespan' );
	visibility_select( $Form, $edited_Item->status );
	$Form->switch_layout( NULL );

	$Form->end_fieldset();


	// ################### TEXT RENDERERS ###################

	$Form->begin_fieldset( T_('Text Renderers'), array( 'id' => 'itemform_renderers' ) );

	// fp> TODO: there should be no param call here (shld be in controller)
	$edited_Item->renderer_checkboxes( param('renderers', 'array/string', NULL) );

	$Form->end_fieldset();


	// ################### COMMENT STATUS ###################

	if( ( $Blog->get_setting( 'allow_comments' ) != 'never' ) && ( $Blog->get_setting( 'disable_comments_bypost' ) ) )
	{
		$Form->begin_fieldset( T_('Comments'), array( 'id' => 'itemform_comments' ) );

		?>
			<label title="<?php echo T_('Visitors can leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="open" class="checkbox" <?php if( $post_comment_status == 'open' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Open') ?></label><br />

			<label title="<?php echo T_('Visitors can NOT leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="closed" class="checkbox" <?php if( $post_comment_status == 'closed' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Closed') ?></label><br />

			<label title="<?php echo T_('Visitors cannot see nor leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="disabled" class="checkbox" <?php if( $post_comment_status == 'disabled' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Disabled') ?></label><br />
		<?php

		$Form->switch_layout( 'table' );
		$Form->duration_input( 'expiry_delay',  $edited_Item->get_setting( 'post_expiry_delay' ), T_('Expiry delay'), 'months', 'hours',
							array( 'minutes_step' => 1, 'required' => false, 'note' => T_( 'Older comments and ratings will no longer be displayed.' ) ) );
		$Form->switch_layout( NULL );

		$Form->end_fieldset();
	}

	?>

</div>

<div class="clear"></div>

</div>

<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();

// ####################### JS BEHAVIORS #########################
echo_publishnowbutton_js();
echo_set_is_attachments();
echo_link_files_js();
echo_autocomplete_tags( $edited_Item->get_tags() );
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
$edited_Item->load_Blog();
// Location
echo_regional_js( 'item', $edited_Item->Blog->region_visible() );
// Item type
echo_onchange_item_type_js();

// require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';

?>
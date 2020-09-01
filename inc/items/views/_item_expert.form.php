<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Item
 */
global $edited_Item;
/**
 * @var Blog
 */
global $Collection, $Blog;
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

global $mode, $admin_url, $rsc_url, $locales;
global $post_comment_status, $trackback_url, $item_tags;
global $bozo_start_modified, $creating;
global $item_title, $item_content;
global $redirect_to, $orig_action;
global $attachment_tab;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

// Used to mark the required fields (in non-standard template)
$required_star = '<span class="label_field_required">*</span>';

$Form = new Form( NULL, 'item_checkchanges', 'post' );
$Form->labelstart = '<strong>';
$Form->labelend = "</strong>\n";


// ================================ START OF EDIT FORM ================================
echo_image_insert_modal();
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

	// Try to get the original item ID (For example, on copy action):
	$original_item_ID = get_param( 'p' );
	if( ! empty( $original_item_ID ) )
	{
		$Form->hidden( 'p', $original_item_ID );
		if( $action == 'new_version' )
		{	// Set a flag to know this is a new version of this Item:
			$Form->hidden( 'source_version_item_ID', $original_item_ID );
		}
	}

	$Form->hidden( 'redirect_to', $redirect_to );

	// In case we send this to the blog for a preview :
	$Form->hidden( 'preview', 1 );
	$Form->hidden( 'preview_block', 0 );
	$Form->hidden( 'more', 1 );

	// Post type
	$Form->hidden( 'item_typ_ID', $edited_Item->ityp_ID );

	// Check if current Item type usage is not content block in order to hide several fields below:
	$is_not_content_block = ( $edited_Item->get_type_setting( 'usage' ) != 'content-block' );
?>
<div class="row">

<div class="left_col col-lg-9 col-md-8 content-form-with-tab">

	<?php
	// ############################ INSTRUCTIONS ##############################
	$ItemType = & $edited_Item->get_ItemType();
	if( $ItemType && $ItemType->get( 'back_instruction' ) == 1 && $ItemType->get( 'instruction' ) )
	{
		?>
		<div class="alert alert-dismissable alert-info fade in">
			<button type="button" class="close" data-dismiss="alert">
				<span aria-hidden="true">x</span>
			</button>
			<?php echo $ItemType->get( 'instruction' );?>
		</div>
		<?php
	}

	// ############################ POST CONTENTS #############################

	$item_type_link = $edited_Item->get_type_edit_link( 'link', $edited_Item->get( 't_type' ), TB_('Change type') );
	if( $edited_Item->ID > 0 )
	{	// Set form title for editing the item:
		$form_title_item_ID = TB_('Item').' <a href="'.$admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$edited_Item->ID.'" class="post_type_link">#'.$edited_Item->ID.'</a>';
	}
	elseif( $creating )
	{
		if( ! empty( $original_item_ID ) )
		{	// Set form title for duplicating the item:
			$form_title_item_ID = sprintf( ( $action == 'new_version' ? TB_('Add version for Item %s') : TB_('Duplicating Item %s') ),
				'<a href="'.$admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$original_item_ID.'" class="post_type_link">#'.$original_item_ID.'</a>' );
		}
		else
		{	// Set form title for creating new item:
			$form_title_item_ID = TB_('New Item');
		}
	}
	if( check_user_perm( 'options', 'edit' ) )
	{	// Add an icon to edit item type if current user has a permission:
		$item_type_edit_link = ' '.action_icon( TB_('Edit this Post Type...'), 'edit', $admin_url.'?ctrl=itemtypes&amp;action=edit&amp;ityp_ID='.$edited_Item->get( 'ityp_ID' ) );
	}
	else
	{
		$item_type_edit_link = '';
	}
	if( $ItemType->is_enabled( $edited_Item->get_blog_ID() ) )
	{	// If Item Type is enabled for the Item's Collection:
		$item_type_before = '';
		$item_type_after = '';
	}
	else
	{	// Mark with orange label if Item Type is disabled for the Item's Collection:
		$item_type_before = '<span class="label label-warning" title="'.format_to_output( TB_('This type is disabled for this collection.'), 'htmlattr' ).'">';
		$item_type_after = '</span>';
	}
	$Form->begin_fieldset( $form_title_item_ID.get_manual_link( 'post-contents-panel' )
				.'<span class="pull-right">'.$item_type_before.TB_('Type').$item_type_after.': '.$item_type_link.$item_type_edit_link.'</span>',
			array( 'id' => 'itemform_content' ) );

	$Form->switch_layout( 'fields_table' );

	if( $edited_Item->get_type_setting( 'use_short_title' ) == 'optional' )
	{	// Display short title:
		$Form->begin_fieldset( '', array( 'class' => 'evo_fields_table__single_row' ) );
		if( $edited_Item->get_type_setting( 'use_short_title' ) == 'optional' )
		{	// Display a post short title field:
			$short_title_maxlen = intval( $edited_Item->get_type_setting( 'short_title_maxlen' ) );
			$Form->text_input( 'post_short_title', htmlspecialchars_decode( $edited_Item->get( 'short_title' ) ), 50, TB_('Short title'), '', array(
					'maxlength' => $short_title_maxlen,
					'data-recommended-length' => '20;30' ) );
		}
		else
		{	// Hide a post short title field:
			$Form->hidden( 'post_short_title', htmlspecialchars_decode( $edited_Item->get( 'short_title' ) ) );
		}
		$Form->end_fieldset();
	}

	$Form->begin_fieldset( '', array( 'class' => 'evo_fields_table__single_row' ) );
	if( $edited_Item->get_type_setting( 'use_title' ) != 'never' )
	{	// Display a post title field:
		$title_maxlen = intval( $edited_Item->get_type_setting( 'title_maxlen' ) );
		$Form->text_input( 'post_title', $item_title, 20, TB_('Title'), '', array(
				'maxlength' => $title_maxlen,
				'data-recommended-length' => '60;65',
				'required' => ( $edited_Item->get_type_setting( 'use_title' ) == 'required' ) ) );
	}
	else
	{	// Hide a post title field:
		$Form->hidden( 'post_title', $item_title );
	}
	$Form->end_fieldset();

	// URL slugs:
	//add slug_changed field - needed for slug trim, if this field = 0 slug will trimmed
	$Form->hidden( 'slug_changed', 0 );
	$edit_slug_link = '';
	if( $edited_Item->ID > 0 && check_user_perm( 'slugs', 'view' ) )
	{	// Current User has a permission to view slugs:
		// Get icon to copy canonical slug to clipboard:
		$edit_slug_link = action_icon( TB_('Copy slug to clipboard'), 'clipboard-copy', '#', TB_('Copy slug'), 3, 4, array(
				'id'      => 'item_canonical_slug_clipboard_icon', // ID is used to highlight on coping process
				'onclick' => 'return evo_copy_to_clipboard( \'item_canonical_slug_clipboard_icon\', \''.format_to_js( $edited_Item->get( 'urltitle' ) ).'\' )',
			) ).' ';
		// Get link to edit slugs page:
		$edit_slug_link .= action_icon( TB_('Edit slugs'), 'edit', $admin_url.'?ctrl=slugs&amp;slug_item_ID='.$edited_Item->ID, TB_('Edit slugs'), 3, 4 )
			// TRANS: Full phrase is "<a href="">Edit slugs</a> for this post"
			.' '.TB_('for this post').' - ';
	}

	if( empty( $edited_Item->tiny_slug_ID ) )
	{	// No tiny URL:
		$tiny_slug_info = TB_('No Tiny URL yet.');
	}
	else
	{	// Get a link to tiny URL:
		$tiny_slug_info = $edited_Item->get_tinyurl_link( array(
				'before' => TB_('Tiny URL').': ',
				'after'  => ''
			) ).' ';
		// Get icon to copy tiny URL to clipboard:
		$tiny_slug_info .= action_icon( TB_('Copy Tiny URL to clipboard'), 'clipboard-copy', '#', '', NULL, NULL, array(
				'id'      => 'item_tiny_url_clipboard_icon', // ID is used to highlight on coping process
				'onclick' => 'return evo_copy_to_clipboard( \'item_tiny_url_clipboard_icon\', \''.$edited_Item->get_tinyurl().'\' )',
			) );
	}
	$Form->text_input( 'post_urltitle', $edited_Item->get_slugs(), 40, TB_('URL slugs'), $edit_slug_link.$tiny_slug_info, array( 'maxlength' => 210 ) );

	$Form->switch_layout( NULL );

	if( $edited_Item->get_type_setting( 'allow_attachments' ) &&
	    check_user_perm( 'files', 'view', false ) )
	{	// If current user has a permission to view the files AND attachments are allowed for the item type:
		load_class( 'links/model/_linkitem.class.php', 'LinkItem' );
		// Initialize this object as global because this is used in many link functions:
		global $LinkOwner;
		$LinkOwner = new LinkItem( $edited_Item, param( 'temp_link_owner_ID', 'integer', 0 ) );
	}

	if( $edited_Item->get_type_setting( 'use_text' ) != 'never' )
	{ // Display text
		// --------------------------- TOOLBARS ------------------------------------
		echo '<div class="edit_toolbars">';
		// CALL PLUGINS NOW:
		$admin_toolbar_params = array(
				'edit_layout' => 'expert',
				'Item' => $edited_Item,
			);
		if( isset( $LinkOwner) && $LinkOwner->is_temp() )
		{
			$admin_editor_params['temp_ID'] = $LinkOwner->get_ID();
		}
		$Plugins->trigger_event( 'AdminDisplayToolbar', $admin_toolbar_params );
		echo '</div>';

		// ---------------------------- TEXTAREA -------------------------------------
		$Form->fieldstart = '<div class="edit_area">';
		$Form->fieldend = "</div>\n";
		$Form->textarea_input( 'content', $item_content, 16, '', array( 'cols' => 40 , 'id' => 'itemform_post_content', 'class' => 'autocomplete_usernames link_attachment_dropzone' ) );
		?>
		<script>
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
	ob_start();
	$admin_editor_params = array(
			'target_type'   => 'Item',
			'target_object' => $edited_Item,
			'content_id'    => 'itemform_post_content',
			'edit_layout'   => 'expert',
		);
	if( isset( $LinkOwner) && $LinkOwner->is_temp() )
	{
		$admin_editor_params['temp_ID'] = $LinkOwner->get_ID();
	}
	$Plugins->trigger_event( 'AdminDisplayEditorButton', $admin_editor_params );
	$plugin_button = ob_get_flush();
	if( empty( $plugin_button ) && $edited_Item->get_type_setting( 'use_text' ) != 'never')
	{	// If button is not displayed by any plugin and text is allowed for current item type:
		// Display a current status of HTML allowing for the edited item:
		echo '<span class="html_status">';
		if( $edited_Item->get_type_setting( 'allow_html' ) )
		{
			echo TB_('HTML is allowed');
		}
		else
		{
			echo TB_('HTML is not allowed');
		}
		// Display manual link for more info:
		echo get_manual_link( 'post-allow-html' );
		echo '</span>';
	}
	if( $edited_Item->get_type_setting( 'usage' ) == 'widget-page' &&
	    check_user_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
	{	// Display a button to edit widgets only if item type is used for page containers and current user has permission to edit widgets:
		echo '<a href="'.$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'" class="btn btn-primary">'.TB_('Edit widgets now').'</a>';
	}
	echo '</div>';

	echo '<div class="pull-right">';
	echo_publish_buttons( $Form, $creating, $edited_Item );
	echo '</div>';

	echo '<div class="clearfix"></div>';

	echo '</div>';

	$Form->end_fieldset();

	global $UserSettings;
	
	$active_tab_pane_value = $UserSettings->get_collection_setting( 'active_tab_pane_itemform', $Blog->ID );
	
	echo '<input type="hidden" name="tab_pane_active[tab_pane_itemform]" id="itemform_tab_pane" value="'.$active_tab_pane_value.'">';

	echo '<ul class="nav nav-tabs">';

	$tab_panes = array();

	// Attachments:
	if( $edited_Item->get_type_setting( 'allow_attachments' ) )
	{	// If attachments are allowed for the Item Type:
		$LinkOwner = new LinkItem( $edited_Item, param( 'temp_link_owner_ID', 'integer', 0 ) );
		if( $LinkOwner->check_perm( 'edit', false ) )
		{	// If current User has a permission to edit attachments of the edited Item:
			$tab_panes[] = '#attachment';
			echo '<li><a data-toggle="tab" href="#attachment">'.T_('Attachments').'</a></li>';
		}
	}

	// Custom fields:
	$custom_fields = $edited_Item->get_type_custom_fields();
	if( count( $custom_fields ) )
	{
		$tab_panes[] = '#custom_fields';
		echo '<li><a data-toggle="tab" href="#custom_fields">'.T_('Custom fields').'</a></li>';
	}

	// Advanced properties:
	$tab_panes[] = '#advance_properties';
	echo '<li><a data-toggle="tab" href="#advance_properties">'.T_('Advanced properties').'</a></li>';

	// Additional actions:
	if( isset( $Blog ) && $Blog->get( 'allowtrackbacks' ) )
	{
		$tab_panes[] = '#allowtrackbacks';
		echo '<li><a data-toggle="tab" href="#allowtrackbacks">'.T_('Additional actions').'</a></li>';
	}

	// Internal comments:
	if( $edited_Item->can_see_meta_comments() )
	{	// Meta/Internal comments are allowed only when current User has a permission to view them:
		$total_comments_number = generic_ctp_number( $edited_Item->ID, 'metas', 'total' );
		$tab_panes[] = '#internal_comments';
		echo '<li><a data-toggle="tab" href="#internal_comments">'.T_('Internal comments').( $total_comments_number > 0 ? ' <span class="badge badge-important">'.$total_comments_number.'</span>' : '' ).'</a></li>';
	}

	// Checklist:
	if( $edited_Item->can_see_meta_comments() && // Current User must has at least a permission to view meta comments
	    ( ! $creating || $edited_Item->can_meta_comment() ) ) // No need to display this tab for new Item if current User cannot add checklist item
	{	// Checklist is allowed only for users who can see meta/internal comments:
		$tab_panes[] = '#checklist';
		$unchecked_checklist_lines = $edited_Item->get_unchecked_checklist_lines();
		echo '<li><a data-toggle="tab" href="#checklist">'.T_('Checklist').( $unchecked_checklist_lines > 0 ? ' <span id="checklist_counter" class="badge badge-important">'.$unchecked_checklist_lines.'</span>' : '' ).'</a></li>';
	}

	echo '</ul>';

	echo '<div class="tab-content evo_tab_pane_itemform_content">';

	$attachment_tab = true;
	$fold_images_attachments_block = ( $orig_action != 'update_edit' && $orig_action != 'create_edit' ); // don't fold the links block on these two actions
	$Form->attachments_fieldset( $edited_Item, $fold_images_attachments_block );

	if( count( $custom_fields ) )
	{	// Display fieldset with custom fields only if at least one exists:
		$custom_fields_title = get_manual_link( 'post-custom-fields-panel' );
		if( $current_User->check_perm( 'options', 'edit' ) )
		{	// Display an icon to edit post type if current user has a permission:
			$custom_fields_title .= '<span class="floatright panel_heading_action_icons">'
					.action_icon( TB_('Edit fields...'), 'edit',
						$admin_url.'?ctrl=itemtypes&amp;action=edit&amp;ityp_ID='.$edited_Item->get( 'ityp_ID' ).'#fieldset_wrapper_custom_fields',
						TB_('Edit fields...'), 3, 4, array( 'class' => 'action_icon btn btn-default btn-sm' ) )
				.'</span>';
		}

		$Form->open_tab_pane( array( 'id' => 'custom_fields', 'class' => 'tab_pane_pads', 'right_items' => $custom_fields_title ) );

		$Form->switch_layout( 'fields_table' );

		// Display inputs to edit custom fields:
		display_editable_custom_fields( $Form, $edited_Item );

		$Form->switch_layout( NULL );

		$Form->close_tab_pane();
	}

	

	// ############################ ADVANCED PROPERTIES #############################

	$Form->open_tab_pane( array( 'id' => 'advance_properties', 'class' => 'tab_pane_pads', 'right_items' => get_manual_link( 'post-advanced-properties-panel' ) ) );

	$Form->switch_layout( 'fields_table' );

	if( $edited_Item->get_type_setting( 'use_tags' ) != 'never' )
	{	// Display tags:
		$link_to_tags_manager = '';
		if( check_user_perm( 'options', 'view' ) )
		{ // Display a link to manage tags only when current use has the rights
			$link_to_tags_manager = ' &ndash; <a href="'.$admin_url.'?ctrl=itemtags&amp;tag_item_ID='.$edited_Item->ID.'">'.TB_('Go to tags manager').'</a>';
		}
		// Checkbox to suggest tags
		$suggest_checkbox = '<label>'
				.'<input id="suggest_item_tags" name="suggest_item_tags" value="1" type="checkbox"'.( $UserSettings->get( 'suggest_item_tags' ) ? ' checked="checked"' : '' ).' /> '
				.TB_('Auto-suggest tags as you type (based on existing tags)').$link_to_tags_manager
			.'</label>';
		$Form->text_input( 'item_tags', $item_tags, 40, TB_('Tags'), $suggest_checkbox, array(
				'maxlength' => 255,
				'required'  => ( $edited_Item->get_type_setting( 'use_tags' ) == 'required' ),
				'style'     => 'width: 100%;',
				'input_prefix' => '<div class="input-group">',
				'input_suffix' => '<span class="input-group-btn">'
						.'<input class="btn btn-primary" type="button" name="actionArray[extract_tags]"'
							.' onclick="return b2edit_confirm( \''.TS_('This will save your changes, then analyze your post to find existing tags. Are you sure?').'\','
							.' \''.$admin_url.'?ctrl=items&amp;blog='.$edited_Item->get_blog_ID().'\','
							.' \'extract_tags\' );"'
							.' value="'.format_to_output( TB_('Extract'), 'htmlattr' ).'" />'
					.'</span></div>',
			) );
	}
	else
	{	// Hide tags:
		$Form->hidden( 'item_tags', $item_tags );
	}

	if( $is_not_content_block )
	{	// Display excerpt for item with type usage except of content block:
		$edited_item_excerpt = $edited_Item->get( 'excerpt' );
		if( $edited_Item->get_type_setting( 'use_excerpt' ) != 'never' )
		{	// Display excerpt:
			$excerpt_checkbox = '<label>'
					.'<input name="post_excerpt_autogenerated" value="1" type="checkbox"'.( $edited_Item->get( 'excerpt_autogenerated' ) ? ' checked="checked"' : '' ).' /> '
					.TB_('Auto-generate excerpt from content')
				.'</label>';
			$Form->textarea_input( 'post_excerpt', $edited_item_excerpt, 3, TB_('Excerpt'), array(
					'required' => ( $edited_Item->get_type_setting( 'use_excerpt' ) == 'required' ),
					'style'    => 'width:100%',
					'note'     => $excerpt_checkbox,
					'data-recommended-length' => '80;120',
				) );
		}
		else
		{	// Hide excerpt:
			$Form->hidden( 'post_excerpt', htmlspecialchars( $edited_item_excerpt ) );
		}
	}

	if( $edited_Item->get_type_setting( 'use_url' ) != 'never' )
	{	// Display url:
		if( is_pro() )
		{	// Only PRO feature for using of post link URL as an External Canonical URL:
			$external_canonical_url_checkbox = '<label>'
					.'<input name="post_external_canonical_url" value="1" type="checkbox"'.( $edited_Item->get_setting( 'external_canonical_url' ) ? ' checked="checked"' : '' ).' /> '
					.sprintf( TB_('Use as <a %s>External canonical URL</a>'), 'href="'.get_manual_url( 'external-canonical-url' ).'"' ).' '.get_pro_label()
				.'</label>';
		}
		else
		{
			$external_canonical_url_checkbox = '';
		}
		$Form->text_input( 'post_url', $edited_Item->get( 'url' ), 20, TB_('Link to url'), $external_canonical_url_checkbox, array(
				'maxlength' => 255,
				'data-maxlength' => 255,
				'required'  => ( $edited_Item->get_type_setting( 'use_url' ) == 'required' )
			) );
	}
	else
	{	// Hide url:
		$Form->hidden( 'post_url', $edited_Item->get( 'url' ) );
		if( is_pro() )
		{	// Only PRO feature for using of post link URL as an External Canonical URL:
			$Form->hidden( 'post_external_canonical_url', $edited_Item->get_setting( 'external_canonical_url' ) );
		}
	}

	if( $is_not_content_block )
	{	// Display title tag, meta description and meta keywords for item with type usage except of content block:
		if( $edited_Item->get_type_setting( 'use_title_tag' ) != 'never' )
		{	// Display <title> tag:
			$Form->text_input( 'titletag', $edited_Item->get( 'titletag' ), 40, TB_('&lt;title&gt; tag'), '', array(
					'maxlength' => 500,
					'data-recommended-length' => '60;65',
					'required'  => ( $edited_Item->get_type_setting( 'use_title_tag' ) == 'required' )
				) );
		}
		else
		{	// Hide <title> tag:
			$Form->hidden( 'titletag', $edited_Item->get( 'titletag' ) );
		}

		if( $edited_Item->get_type_setting( 'use_meta_desc' ) != 'never' )
		{	// Display <meta> description:
			$Form->text_input( 'metadesc', $edited_Item->get_setting( 'metadesc' ), 40, TB_('&lt;meta&gt; desc'), '', array(
					'maxlength' => 500,
					'data-recommended-length' => '80;120',
					'required'  => ( $edited_Item->get_type_setting( 'use_meta_desc' ) == 'required' )
				) );
		}
		else
		{	// Hide <meta> description:
			$Form->hidden( 'metadesc', $edited_Item->get_setting('metadesc') );
		}

		if( $edited_Item->get_type_setting( 'use_meta_keywds' ) != 'never' )
		{	// Display <meta> keywords:
			$Form->text_input( 'metakeywords', $edited_Item->get_setting( 'metakeywords' ), 40, TB_('&lt;meta&gt; keywds'), '', array(
					'maxlength' => 500,
					'data-recommended-length' => '200;250',
					'required'  => ( $edited_Item->get_type_setting( 'use_meta_keywds' ) == 'required' )
				) );
		}
		else
		{	// Hide <meta> keywords:
			$Form->hidden( 'metakeywords', $edited_Item->get_setting( 'metakeywords' ) );
		}

		if( $edited_Item->get_type_setting( 'allow_switchable' ) )
		{	// Display "Switchable content" options:
			$Form->text_input( 'item_switchable_params', $edited_Item->get_setting( 'switchable_params' ), 40, TB_('Switchable content'), '', array(
					'maxlength' => 500,
					'input_prefix' => '<div><input type="checkbox" id="item_switchable_params" name="item_switchable" value="1"'.( $edited_Item->get_setting( 'switchable' ) ? ' checked="checked"' : '' ).' /> '.TB_('Enabled with params').':</div>',
				) );
		}
	}

	$Form->switch_layout( NULL );

	$Form->close_tab_pane();

	// ####################### ADDITIONAL ACTIONS #########################

	if( isset( $Blog ) && $Blog->get('allowtrackbacks') )
	{
		$Form->open_tab_pane( array( 'id' => 'allowtrackbacks', 'class' => 'tab_pane_pads', 'right_items' => get_manual_link( 'post-edit-additional-actions-panel' ) ) );

		// --------------------------- TRACKBACK --------------------------------------
		?>
		<div id="itemform_trackbacks">
			<label for="trackback_url"><strong><?php echo TB_('Trackback URLs') ?>:</strong>
			<span class="notes"><?php echo TB_('(Separate by space)') ?></span></label><br />
			<input type="text" name="trackback_url" class="large form_text_input form-control" id="trackback_url" value="<?php echo format_to_output( $trackback_url, 'formvalue' ); ?>" />
		</div>
		<?php

		$Form->close_tab_pane();
	}

	if( $edited_Item->can_see_meta_comments() )
	{
		// ####################### INTERNAL COMMENTS #########################
		$currentpage = param( 'currentpage', 'integer', 1 );
		param( 'comments_number', 'integer', $total_comments_number );
		param( 'comment_type', 'string', 'meta' );

		$Form->open_tab_pane( array( 'id' => 'internal_comments', 'class' => 'tab_pane_pads', 'right_items' => get_manual_link( 'meta-comments-panel' ) ) );

		if( $creating )
		{	// Display button to save new creating item:
			$Form->submit( array( 'actionArray[create_edit]', /* TRANS: This is the value of an input submit button */ TB_('Save post to start adding Internal comments'), 'btn-primary' ) );
		}
		else
		{	// Display internal comments of the edited item:
			global $CommentList, $UserSettings;
			$CommentList = new CommentList2( $Blog );

			// Filter list:
			$CommentList->set_filters( array(
				'types' => array( 'meta' ),
				'statuses' => get_visibility_statuses( 'keys', array( 'redirected', 'trash' ) ),
				'order' => 'DESC',
				'post_ID' => $edited_Item->ID,
				'comments' => $UserSettings->get( 'results_per_page' ),
				'page' => $currentpage,
				'expiry_statuses' => array( 'active' ),
			) );
			$CommentList->query();

			// comments_container value shows, current Item ID
			echo '<div class="evo_content_block">';
			echo '<div id="comments_container" value="'.$edited_Item->ID.'" class="evo_comments_container">';
			// display comments
			$CommentList->display_if_empty( array(
					'before'    => '<div class="evo_comment"><p>',
					'after'     => '</p></div>',
					'msg_empty' => TB_('No internal comment for this post yet...'),
				) );
			require $inc_path.'comments/views/_comment_list.inc.php';
			echo '</div>'; // comments_container div
			echo '</div>';

			if( $edited_Item->can_meta_comment() )
			{ // Display a link to add new internal comment if current user has a permission
				echo action_icon( TB_('Add internal comment').'...', 'new', $admin_url.'?ctrl=items&amp;p='.$edited_Item->ID.'&amp;comment_type=meta&amp;blog='.$Blog->ID.'#comments', TB_('Add internal comment').' &raquo;', 3, 4 );
			}

			// Load JS functions to work with internal comments:
			load_funcs( 'comments/model/_comment_js.funcs.php' );
		}

		$Form->close_tab_pane();
	}

	// ####################### CHECKLIST #########################
	if( $edited_Item->can_see_meta_comments() && // Current User must has at least a permission to view meta comments
	    ( ! $creating || $edited_Item->can_meta_comment() ) ) // No need to display this tab for new Item if current User cannot add checklist item
	{	// Checklist is allowed only for users who can see meta/internal comments:
		$Form->open_tab_pane( array( 'id' => 'checklist', 'class' => 'tab_pane_pads', 'right_items' => get_manual_link( 'item-checklist-panel' ) ) );
		if( $creating )
		{	// Display button to save new creating item:
			$Form->submit( array( 'actionArray[create_edit]', /* TRANS: This is the value of an input submit button */ TB_('Save post to start adding Checklist lines'), 'btn-primary' ) );
		}
		else
		{
			// Make sure the widget does not insert a form here!
			skin_widget( array(
				// CODE for the widget:
				'widget' => 'item_checklist_lines',
				// Optional display params
				'Item'  => $edited_Item,
				'title' => NULL,
			) );
		}
		$Form->close_tab_pane();
	}

	echo '</div><br>';

	// ####################### PLUGIN FIELDSETS #########################

	$Plugins->trigger_event( 'AdminDisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item, 'edit_layout' => 'expert' ) );

	?>

</div>

<div class="right_col col-lg-3 col-md-4">

	<?php
	// ################### MODULES SPECIFIC ITEM SETTINGS ###################

	modules_call_method( 'display_item_settings', array( 'Form' => & $Form, 'Blog' => & $Blog, 'edited_Item' => & $edited_Item, 'edit_layout' => 'expert', 'fold' => true ) );

	// ############################ WORKFLOW #############################

	if( $is_not_content_block && $edited_Item->can_edit_workflow() )
	{	// Display workflow properties if current user can edit at least one workflow property:
		$Form->begin_fieldset( TB_('Workflow properties').get_manual_link( 'post-edit-workflow-panel' ), array( 'id' => 'itemform_workflow_props', 'fold' => true ) );

			echo '<div id="itemform_edit_workflow" class="edit_fieldgroup">';
			$Form->switch_layout( 'linespan' );

			$edited_Item->display_workflow_field( 'status', $Form );

			echo ' '; // allow wrapping!

			$edited_Item->display_workflow_field( 'user', $Form );

			echo ' '; // allow wrapping!

			$edited_Item->display_workflow_field( 'priority', $Form );

			echo ' '; // allow wrapping!

			$edited_Item->display_workflow_field( 'deadline', $Form );

			$Form->switch_layout( NULL );
			echo '</div>';

		$Form->end_fieldset();
	}

	if( $is_not_content_block )
	{	// Display category selector for item with type usage except of content block:
		// ################### CATEGORIES ###################
		cat_select( $Form, true, true, array( 'fold' => true ) );
	}
	else
	{	// Use a hidden input field for category in order to don't reset this to default on each updating:
		$Form->hidden( 'post_category', $edited_Item->get( 'main_cat_ID' ) );
	}

	// ################### LOCATIONS ###################
	echo_item_location_form( $Form, $edited_Item, array( 'fold' => true ) );

	// ################### PROPERTIES ###################

	$Form->begin_fieldset( TB_('Properties').get_manual_link( 'post-properties-panel' ), array( 'id' => 'itemform_extra', 'fold' => true ) );

	$Form->switch_layout( 'linespan' );

	echo '<table>';

	if( $edited_Item->get_type_setting( 'use_parent' ) != 'never' )
	{	// Display parent ID:
		echo '<tr><td><strong>'.TB_('Parent ID').':</strong></td><td>';
		$Form->item_selector( 'post_parent_ID', $edited_Item->get( 'parent_ID' ), '', array(
				'window_title_page1' => NT_('Select the parent'),
				'window_title_page2' => NT_('Select this Post as parent:'),
				'required'           => ( $edited_Item->get_type_setting( 'use_parent' ) == 'required' ),
			) );
		echo '</td></tr>';
	}
	else
	{	// Hide parent ID:
		$Form->hidden( 'post_parent_ID', $edited_Item->get( 'parent_ID' ) );
	}

	if( check_user_perm( 'users', 'edit' ) )
	{	// If current User has full access to edit other users,
		// Display item's owner:
		echo '<tr><td class="flabel_item_owner_login"><strong>'.TB_('Owner').':</strong></td><td>';
		$Form->username( 'item_owner_login', $edited_Item->get_creator_User(), '', TB_( 'login of this post\'s owner.') );
		// Display a checkbox to create new user:
		echo '<label class="ffield_item_create_user"><input type="checkbox" name="item_create_user" value="1"'.( get_param( 'item_create_user' ) ? ' checked="checked"' : '' ).' /> '.TB_('Create new user').'</label>';
		$Form->hidden( 'item_owner_login_displayed', 1 );
		echo '</td></tr>';
	}

	if( $edited_Item->get_type_setting( 'use_coordinates' ) != 'never' )
	{	// Display Latitude & Longitude settings:
		$field_required = ( $edited_Item->get_type_setting( 'use_coordinates' ) == 'required' ) ? $required_star : '';
		echo '<tr><td>'.$field_required.'<strong>'.TB_('Latitude').':</strong></td><td>';
		$Form->text( 'item_latitude', $edited_Item->get_setting( 'latitude' ), 10, '' );
		echo '</td></tr>';
		echo '<tr><td>'.$field_required.'<strong>'.TB_('Longitude').':</strong></td><td>';
		$Form->text( 'item_longitude', $edited_Item->get_setting( 'longitude' ), 10, '' );
		echo '</td></tr>';
	}

	echo '</table>';

	if( check_user_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{	// If user has a permission to edit advanced properties of items:
		if( $edited_Item->get_type_setting( 'allow_featured' ) )
		{ // Display featured
			$Form->checkbox_basic_input( 'item_featured', $edited_Item->featured, '<strong>'.TB_('Featured post').'</strong>' );
		}
		else
		{ // Hide featured
			$Form->hidden( 'item_featured', $edited_Item->featured );
		}
	}

	if( $Blog->get_setting( 'track_unread_content' ) )
	{	// Display setting to mark Item as "must read" when tracking of unread content is enabled for collection:
		$Form->checkbox_basic_input( 'item_mustread', $edited_Item->get_setting( 'mustread' ), '<strong>'.TB_('Must read').'</strong> '.get_pro_label(), array( 'disabled' => ! is_pro() ) );
	}

	if( $is_not_content_block && $edited_Item->get_type_setting( 'allow_breaks' ) )
	{	// Display "hide teaser" checkbox for item with type usage except of content block:
		$Form->checkbox_basic_input( 'item_hideteaser', $edited_Item->get_setting( 'hide_teaser' ), '<strong>'.sprintf( TB_('Hide teaser when displaying part after %s'), '<code>[teaserbreak]</code>' ).'</strong>' );
	}

	// Single/page view:
	if( check_user_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{	// If user has a permission to edit advanced properties of items:
		if( ! in_array( $edited_Item->get_type_setting( 'usage' ), array( 'intro-front', 'intro-main', 'intro-cat', 'intro-tag', 'intro-sub', 'intro-all', 'content-block', 'special' ) ) )
		{	// We don't need this setting for intro, content block and special items:
			echo '<div class="itemform_extra_radio">';
			$Form->radio( 'post_single_view', $edited_Item->get( 'single_view' ), array(
					array( 'normal', TB_('Normal') ),
					array( '404', '404' ),
					array( 'redirected', TB_('Redirected') ),
				), TB_('Single/page view'), true );
			echo '</div>';
		}
	}

	// Issue date:
	if( check_user_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{	// If user has a permission to edit advanced properties of items:
		echo '<div class="itemform_extra_radio">';
		$Form->output = false;
		$item_issue_date_time = $Form->date( 'item_issue_date', $edited_Item->get( 'issue_date' ), '' );
		$item_issue_date_time .= $Form->time( 'item_issue_time', $edited_Item->get( 'issue_date' ), '', 'hh:mm:ss', '' );
		$Form->output = true;
		$Form->radio( 'item_dateset', $edited_Item->get( 'dateset' ), array(
				array( 0, TB_('Update to NOW') ),
				array( 1, TB_('Set to').': ', '', $item_issue_date_time ),
			), TB_('Issue date'), array( 'lines' => true ) );
		echo '</div>';
	}

	$Form->switch_layout( NULL );

	$Form->end_fieldset();


	// ################### TEXT RENDERERS ###################
	if( $edited_Item->get_type_setting( 'use_text' ) != 'never' )
	{	// Display text renderers only when text content is allowed for the item type:

		$Form->begin_fieldset( TB_('Text Renderers').get_manual_link( 'post-renderers-panel' )
					.action_icon( TB_('Plugins'), 'edit', $admin_url.'?ctrl=coll_settings&amp;tab=plugins&plugin_group=rendering&amp;blog='.$Blog->ID, TB_('Plugins'), 3, 4, array( 'class' => 'action_icon pull-right' ) ),
				array( 'id' => 'itemform_renderers', 'fold' => true ) );

		// fp> TODO: there should be no param call here (shld be in controller)
		$edited_Item->renderer_checkboxes( param('renderers', 'array:string', NULL) );

		$Form->end_fieldset();
	}


	// ################### LANGUAGE / VERSIONS ###################
	$multiple_available_locales = count( $edited_Item->get_available_locales() ) > 1;
	$Form->begin_fieldset( TB_('Language / Versions').get_manual_link( 'post-language-versions' ), array(
			'id'           => 'itemform_language',
			'fold'         => true,
			'default_fold' => ! $multiple_available_locales
		) );
	$Form->switch_layout( 'fields_table' );

		$Form->select_input_options( 'post_locale', $edited_Item->get_locale_options(), TB_('Language'), '', array( 'style' => 'width:auto' ) );

		if( $multiple_available_locales )
		{	// Display this setting if we have more than 1 enabled locale:
			$Form->radio( 'post_locale_visibility', $edited_Item->get( 'locale_visibility' ), array(
					array( 'always', TB_('Show for any navigation locale') ),
					array( 'follow-nav-locale', TB_('Show only if matching navigation locale') )
				), '', true );
		}

		$other_version_items = $edited_Item->get_other_version_items( $original_item_ID );
		$item_add_version_link = $edited_Item->get_add_version_link();
		$item_link_version_link = $edited_Item->get_link_version_link();
		if( $item_add_version_link || $item_link_version_link || count( $other_version_items ) > 0 )
		{	// Display other versions and link to add version:
			echo '<b>'.TB_('Other versions').':</b>';
			echo '<ul style="list-style:disc;margin-left:20px">';
			$other_version_locales = array( $edited_Item->get( 'locale' ) => 1 );
			foreach( $other_version_items as $other_version_Item )
			{	// Find duplicated locales:
				$other_version_locales[ $other_version_Item->get( 'locale' ) ] = isset( $other_version_locales[ $other_version_Item->get( 'locale' ) ] ) ? 2 : 1;
			}
			foreach( $other_version_items as $other_version_Item )
			{	// Display a link to another version of the Item:
				echo '<li>'.$other_version_Item->get_title( array( 'link_type' => 'edit_view_url' ) ).' '
						.locale_flag( $other_version_Item->get( 'locale' ), 'w16px', 'flag', '', false )
						.'<span class="note'.( $other_version_locales[ $other_version_Item->get( 'locale' ) ] == 2 ? ' red' : '' ).'">('.$other_version_Item->get( 'locale' ).')</span>'
						.$edited_Item->get_unlink_version_link( array( 'unlink_item_ID' => $other_version_Item->ID ) )
					.'</li>';
			}
			if( $item_add_version_link )
			{	// Display link to add new version if it is allowed:
				echo '<li>'.$item_add_version_link.'</li>';
			}
			if( $item_link_version_link )
			{	// Display link to add new version if it is allowed:
				echo '<li>'.$item_link_version_link.'</li>';
			}
			echo '</ul>';
		}

	$Form->switch_layout( NULL );
	$Form->end_fieldset();


	// ################### COMMENT STATUS ###################

	if( $edited_Item->allow_comment_statuses() )
	{
		$Form->begin_fieldset( TB_('Comments').get_manual_link( 'post-comments-panel' ), array( 'id' => 'itemform_comments', 'fold' => true ) );

		?>
			<label title="<?php echo TB_('Visitors can leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="open" class="checkbox" <?php if( $post_comment_status == 'open' ) echo 'checked="checked"'; ?> />
			<?php echo TB_('Open') ?></label><br />
		<?php
		if( $edited_Item->get_type_setting( 'allow_closing_comments' ) )
		{ // Allow closing comments
		?>
			<label title="<?php echo TB_('Visitors can NOT leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="closed" class="checkbox" <?php if( $post_comment_status == 'closed' ) echo 'checked="checked"'; ?> />
			<?php echo TB_('Closed') ?></label><br />
		<?php
		}

		if( $edited_Item->get_type_setting( 'allow_disabling_comments' ) )
		{ // Allow disabling comments
		?>
			<label title="<?php echo TB_('Visitors cannot see nor leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="disabled" class="checkbox" <?php if( $post_comment_status == 'disabled' ) echo 'checked="checked"'; ?> />
			<?php echo TB_('Disabled') ?></label><br />
		<?php
		}

		if( $edited_Item->get_type_setting( 'allow_comment_form_msg' ) )
		{	// If custom message is allowed before comment form:
			$Form->switch_layout( 'none' );
			$Form->textarea_input( 'comment_form_msg', $edited_Item->get_setting( 'comment_form_msg' ), 3, TB_('Message before comment form') );
			echo '<br />';
			$Form->switch_layout( NULL );
		}

		if( check_user_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
		{	// If user has a permission to edit advanced properties of items:
			if( $edited_Item->get_type_setting( 'use_comment_expiration' ) != 'never' )
			{ // Display comment expiration
				$Form->switch_layout( 'table' );
				$Form->duration_input( 'expiry_delay',  $edited_Item->get_setting( 'comment_expiry_delay' ), TB_('Expiry delay'), 'months', 'hours',
								array( 'minutes_step' => 1,
									'required' => $edited_Item->get_type_setting( 'use_comment_expiration' ) == 'required',
									'note' => TB_( 'Older comments and ratings will no longer be displayed.' ) ) );
				$Form->switch_layout( NULL );
			}
			else
			{ // Hide comment expiration
				$Form->hidden( 'expiry_delay',  $edited_Item->get_setting( 'comment_expiry_delay' ) );
			}
		}

		$Form->end_fieldset();
	}


	if( in_array( $edited_Item->get_type_setting( 'usage' ), array( 'post', 'page', 'widget-page' ) ) )
	{	// Display user tagging for items which can be displayed only on disp=single, disp=page or disp=widget_page:

		// ################### USER TAGGING ###################
		$Form->begin_fieldset( TB_('User Tagging').get_manual_link( 'post-user-tagging-panel' )
						.( check_user_perm( 'options', 'view' ) ? action_icon( TB_('User Tags'), 'edit', $admin_url.'?ctrl=usertags', TB_('User Tags'), 3, 4, array( 'class' => 'action_icon pull-right' ) ) : '' ),
					array( 'id' => 'itemform_usertags', 'fold' => true ) );

		$Form->switch_layout( 'table' );
		$Form->formstart = '<table id="item_locations" cellspacing="0" class="fform">'."\n";
		$Form->labelstart = '<td class="right"><strong>';
		$Form->labelend = '</strong></td>';

		echo '<p class="note">'.TB_('You can tag the (registered) Users who view this page.').'</p>';

		echo $Form->formstart;

		$Form->usertag_input( 'user_tags', $edited_Item->get_setting( 'user_tags' ), 40, TB_('Tags'), '', array(
				'maxlength'    => 255,
				'style'        => 'width:100%',
				'input_prefix' => '<span class="evo_input__tags">',
				'input_suffix' => '</span>',
			) );

		echo $Form->formend;

		$Form->switch_layout( NULL );

		$Form->end_fieldset();
	}

	if( $is_not_content_block &&
	    check_user_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{	// Display goal tracking and notifications for item with type usage except of content block
		// and if user has a permission to edit advanced properties of items:

		// ################### GOAL TRACKING ###################

		$Form->begin_fieldset( TB_('Goal tracking').get_manual_link( 'post-goal-tracking-panel' )
						.action_icon( TB_('Goals'), 'edit', $admin_url.'?ctrl=goals&amp;blog='.$Blog->ID, TB_('Goals'), 3, 4, array( 'class' => 'action_icon pull-right' ) ),
					array( 'id' => 'itemform_goals', 'fold' => true ) );

		$Form->switch_layout( 'table' );
		$Form->formstart = '<table id="item_locations" cellspacing="0" class="fform">'."\n";
		$Form->labelstart = '<td class="right"><strong>';
		$Form->labelend = '</strong></td>';

		echo '<p class="note">'.TB_( 'You can track a hit on a goal every time this page is displayed to a user.' ).'</p>';

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
		$Form->select_input_object( 'goal_cat_ID', $item_goal_cat_ID, $GoalCategoryCache, TB_('Category'), array( 'allow_none' => true ) );

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
			get_icon( 'multi_action', 'imgtag', array( 'style' => 'margin:0 5px 0 14px;position:relative;top:-1px;') ).TB_('Goal'),
			array(
				'allow_none' => true,
				'note' => '<img src="'.$rsc_url.'img/ajax-loader.gif" alt="'.TB_('Loading...').'" title="'.TB_('Loading...').'" style="display:none;margin-left:5px" align="top" />'
			) );

		echo $Form->formend;

		$Form->switch_layout( NULL );

		$Form->end_fieldset();


		// ################### NOTIFICATIONS ###################

		$Form->begin_fieldset( TB_('Notifications').get_manual_link( 'post-notifications-panel' ), array( 'id' => 'itemform_notifications', 'fold' => true ) );

			$Form->info( TB_('Moderators'), $edited_Item->check_notifications_flags( 'moderators_notified' ) ? TB_('Notified at least once') : TB_('Not notified yet') );

			$notify_types = array(
					'members_notified'   => TB_('Members'),
					'community_notified' => TB_('Community'),
					'pings_sent'         => TB_('Public pings'),
			);

			foreach( $notify_types as $notify_type => $notify_title )
			{
				if( ! $edited_Item->notifications_allowed() )
				{	// Notifications are not allowed for the Item:
					$Form->info( $notify_title, TB_('Not Possible for this post type') );
				}
				else
				{	// Notifications are allowed for the Item:
					if( $edited_Item->check_notifications_flags( $notify_type ) )
					{	// Nofications/Pings were sent:
						$notify_status = ( $notify_type == 'pings_sent' ) ? TB_('Sent') : TB_('Notified');
						$notify_select_options = array(
								''      => TB_('Done'),
								'force' => ( $notify_type == 'pings_sent' ) ? TB_('Send again') : TB_('Notify again')
							);
					}
					elseif( $edited_Item->get_type_setting( 'usage' ) != 'post' )
					{	// Item type is not applicable and Nofications/Pings are not sent yet:
						$notify_status = TB_('Not Recommended');
						$notify_select_options = array(
								''      => TB_('Do nothing'),
								'force' => ( $notify_type == 'pings_sent' ) ? TB_('Send anyways') : TB_('Notify anyways'),
								'mark'  => ( $notify_type == 'pings_sent' ) ? TB_('Mark as Sent') : TB_('Mark as Notified')
							);
					}
					else
					{	// Nofications/Pings are not sent yet:
						$notify_status = ( $notify_type == 'pings_sent' ) ? TB_('To be sent') : TB_('To be notified');
						$notify_select_options = array(
								''     => ( $notify_type == 'pings_sent' ) ? TB_('Send on next save') : TB_('Notify on next save'),
								'skip' => TB_('Skip on next save'),
								'mark' => ( $notify_type == 'pings_sent' ) ? TB_('Mark as Sent') : TB_('Mark as Notified')
							);
					}
					$Form->select_input_array( 'item_'.$notify_type, get_param( 'item_'.$notify_type ), $notify_select_options, $notify_title, NULL, array( 'input_prefix' => $notify_status.' &nbsp; &nbsp; ' ) );
				}
			}

		$Form->end_fieldset();
	}


	// ################### QUICK SETTINGS ###################

	$item_ID = get_param( 'p' ) > 0 ? get_param( 'p' ) : $edited_Item->ID;
	if( $action == 'copy' )
	{
		$prev_action = $action;
	}
	else
	{
		$prev_action = $item_ID > 0 ? 'edit' : 'new';
	}
	$quick_setting_url = $admin_url.'?ctrl=items&amp;prev_action='.$prev_action.( $item_ID > 0 ? '&amp;p='.$item_ID : '' )
		.'&amp;blog='.$Blog->ID.'&amp;'.url_crumb( 'item' ).'&amp;action=';

	if( check_user_perm( 'blog_post!published', 'create', false, $Blog->ID ) )
	{ // Display a link to show/hide quick button to publish the post ONLY if current user has a permission:
		echo '<p>';
		if( $UserSettings->get_collection_setting( 'show_quick_publish', $Blog->ID ) )
		{ // The quick button is displayed
			echo action_icon( '', 'deactivate', $quick_setting_url.'hide_quick_button', TB_('Show the quick "Publish!" button when relevant.'), 3, 4 );
		}
		else
		{ // The quick button is hidden
			echo action_icon( '', 'activate', $quick_setting_url.'show_quick_button', TB_('Never show the quick "Publish!" button.'), 3, 4 );
		}
		echo '</p>';
	}

	// Display a link to reset default settings for current user on this screen:
	echo '<p>';
	echo action_icon( '', 'refresh', $quick_setting_url.'reset_quick_settings', TB_('Reset defaults for this screen.'), 3, 4 );
	echo '</p>';


	echo '<div id="publish_buttons">';
	echo_publish_buttons( $Form, $creating, $edited_Item );
	echo '</div>';
	?>
	<script>
	jQuery( document ).ready( function()
	{
		var affix_obj = jQuery( "#publish_buttons" );
		var affix_offset = 110;

		if( affix_obj.length == 0 )
		{ // No Messages, exit
			return;
		}

		affix_obj.wrap( "<div class=\"publish_buttons_wrapper\"></div>" );
		var wrapper = affix_obj.parent();

		affix_obj.affix( {
				offset: {
					top: function() {
						return wrapper.offset().top - affix_offset - parseInt( affix_obj.css( "margin-top" ) );
					}
				}
			} );

		affix_obj.on( "affix.bs.affix", function()
			{
				wrapper.css( { "min-height": affix_obj.outerHeight( true ) } );

				affix_obj.css( { "width": affix_obj.outerWidth(), "top": affix_offset, "z-index": 99999 } );

				jQuery( window ).on( "resize", function()
					{
						affix_obj.css( { "width": wrapper.css( "width" ) } );
					});
			} );

		affix_obj.on( "affixed-top.bs.affix", function()
			{
				wrapper.css( { "min-height": "" } );
				affix_obj.css( { "width": "", "top": "", "z-index": "" } );
			} );
	} );
	</script>


</div>

<div class="clearfix"></div>

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
// Goal
echo_onchange_goal_cat();
// Fieldset folding
echo_fieldset_folding_js();
// Save and restore item content field height and scroll position:
echo_item_content_position_js( get_param( 'content_height' ), get_param( 'content_scroll' ) );
// JS code for merge button:
echo_item_merge_js();
// JS code for link to add new version:
echo_item_add_version_js();
// JS code for link to link new version:
echo_item_link_version_js();
if( $edited_Item->can_meta_comment() )
{	// Init Item Checklist JS to update red badge in tab of not checked lines:
	expose_var_to_js( 'evo_item_checklist_config', true );
}

// JS to post excerpt mode switching:
?>
<script>

<?php

$js_tab_panes_array = json_encode($tab_panes);
echo "var js_tab_panes_array = ". $js_tab_panes_array . ";\n";

?>

tab_href_value = jQuery( '.content-form-with-tab #itemform_tab_pane' ).val();

// Check if database saved active tab pane value exits in the tab pane or not
if( js_tab_panes_array.indexOf( tab_href_value ) == -1 )
{
	tab_href_value = js_tab_panes_array[0];
}

if( jQuery( '.checklist_lines' ).length > 0 )
{	// Watch for new checklist items and update badge accordingly:
	var checklist = document.querySelector( '.checklist_lines' );
	var observer_config = { attributes: true, childList: true, characterData: true };
	var observer = new MutationObserver( function( mutations ) {
		mutations.forEach( function( mutation )
			{
				if( ( mutation.addedNodes && mutation.addedNodes.length > 0 ) || ( mutation.removedNodes && mutation.removedNodes.length > 0 ) )
				{
					var nodes_to_check = [];
					// element added to DOM
					if( mutation.addedNodes.length > 0 )
					{
						nodes_to_check = mutation.addedNodes;
					}
					else if( mutation.removedNodes.length > 0 )
					{
						nodes_to_check = mutation.removedNodes;
					}
					var hasClass = [].some.call( nodes_to_check, function( el )
						{	// Check if added/removed node has class '.checklist_line':
							if( el.classList )
							{
								return el.classList.contains( 'checklist_line' );
							}
							else
							{
								return false;
							}
						} );

					if( hasClass )
					{	// element has class `.checklist_line`, update counter:
						window.update_checklist_tab_badge();
					}
				}
			} );
	} );
	observer.observe( checklist, observer_config );
}

jQuery( '.content-form-with-tab .nav-tabs a[href="' + tab_href_value + '"]' ).tab( 'show' );
jQuery( '.content-form-with-tab #itemform_tab_pane' ).val( tab_href_value );

if( jQuery( '.content-form-with-tab #attachment' ).length > 0 )
{	// Show attachment tab result summary in single line:
	jQuery( ".content-form-with-tab .results_summary" ).detach().prependTo( '.content-form-with-tab #attachment .pull-left' );
}

jQuery( '.content-form-with-tab .nav-tabs a' ).on( 'shown.bs.tab', function( event )
{	// Do tab wise operations
	tab_href_value = jQuery( event.target ).attr( "href" );
	jQuery( '.content-form-with-tab #itemform_tab_pane' ).val( tab_href_value );
	if( tab_href_value === '#advance_properties' )
	{
		jQuery( window ).resize();
	}
});

jQuery( '#post_excerpt' ).on( 'keyup', function()
{
	// Disable excerpt auto-generation on any changing and enable if excerpt field is empty:
	jQuery( 'input[name=post_excerpt_autogenerated]' ).prop( 'checked', ( jQuery( this ).val() == '' ) );
} );
</script>
<?php

// require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';

?>

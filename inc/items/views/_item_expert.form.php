<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
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

	// Try to get the original item ID (For example, on copy action):
	$original_item_ID = get_param( 'p' );
	if( ! empty( $original_item_ID ) )
	{
		$Form->hidden( 'p', $original_item_ID );
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

<div class="left_col col-lg-9 col-md-8">

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

	$item_type_link = $edited_Item->get_type_edit_link( 'link', $edited_Item->get( 't_type' ), T_('Change type') );
	if( $edited_Item->ID > 0 )
	{	// Set form title for editing the item:
		$form_title_item_ID = T_('Item').' <a href="'.$admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$edited_Item->ID.'" class="post_type_link">#'.$edited_Item->ID.'</a>';
	}
	elseif( $creating )
	{
		if( ! empty( $original_item_ID ) )
		{	// Set form title for duplicating the item:
			$form_title_item_ID = sprintf( T_('Duplicating Item %s'), '<a href="'.$admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$original_item_ID.'" class="post_type_link">#'.$original_item_ID.'</a>' );
		}
		else
		{	// Set form title for creating new item:
			$form_title_item_ID = T_('New Item');
		}
	}
	$Form->begin_fieldset( $form_title_item_ID.get_manual_link( 'post-contents-panel' )
				.'<span class="pull-right">'.sprintf( T_('Type: %s'), $item_type_link ).'</span>',
			array( 'id' => 'itemform_content' ) );

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
	if( $Blog->get_setting( 'new_item_locale_source' ) == 'use_coll' &&
	    $edited_Item->get( 'locale' ) == $Blog->get( 'locale' ) &&
	    isset( $locales[ $edited_Item->get( 'locale' ) ] ) )
	{	// Force to use  collection locale because it is restricted by collection setting and the edited item has the same locale as collection:
		$locale_options = array( $edited_Item->get( 'locale' ), $locales[ $edited_Item->get( 'locale' ) ]['name'] );
	}
	else
	{	// Allow to select a locale:
		$locale_options = locale_options( $edited_Item->get( 'locale' ), false, true );
	}

	if( is_array( $locale_options ) )
	{ // We've only one enabled locale.
		// Tblue> The locale name is not really needed here, but maybe we
		//        want to display the name of the only locale?
		$Form->hidden( 'post_locale', $locale_options[0] );
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

	$Form->switch_layout( NULL );

	if( $edited_Item->get_type_setting( 'use_text' ) != 'never' )
	{ // Display text
		// --------------------------- TOOLBARS ------------------------------------
		echo '<div class="edit_toolbars">';
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'AdminDisplayToolbar', array(
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
	ob_start();
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array(
			'target_type'   => 'Item',
			'target_object' => $edited_Item,
			'content_id'    => 'itemform_post_content',
			'edit_layout'   => 'expert',
		) );
	$plugin_button = ob_get_flush();
	if( empty( $plugin_button ) )
	{	// If button is not displayed by any plugin
		// Display a current status of HTML allowing for the edited item:
		echo '<span class="html_status">';
		if( $edited_Item->get_type_setting( 'allow_html' ) )
		{
			echo T_('HTML is allowed');
		}
		else
		{
			echo T_('HTML is not allowed');
		}
		// Display manual link for more info:
		echo get_manual_link( 'post-allow-html' );
		echo '</span>';
	}
	echo '</div>';

	echo '<div class="pull-right">';
	echo_publish_buttons( $Form, $creating, $edited_Item );
	echo '</div>';

	echo '<div class="clearfix"></div>';

	echo '</div>';

	$Form->end_fieldset();


	// ####################### ATTACHMENTS/LINKS #########################
	if( isset( $GLOBALS['files_Module'] )
		&& $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item )
		&& $current_User->check_perm( 'files', 'view', false ) )
	{ // Files module is enabled, but in case of creating new posts we should show file attachments block only if user has all required permissions to attach files
		load_class( 'links/model/_linkitem.class.php', 'LinkItem' );
		global $LinkOwner; // Initialize this object as global because this is used in many link functions
		$LinkOwner = new LinkItem( $edited_Item );
		$fold_images_attachments_block = ( $orig_action != 'update_edit' && $orig_action != 'create_edit' ); // don't fold the links block on these two actions
		display_attachments_fieldset( $Form, $LinkOwner, $creating, $fold_images_attachments_block );
	}


	// ############################ CUSTOM FIELDS #############################

	if( ! $edited_Item->get_type_setting( 'use_custom_fields' ) )
	{	// All CUSTOM FIELDS are hidden by post type:
		display_hidden_custom_fields( $Form, $edited_Item );
	}
	else
	{	// CUSTOM FIELDS:
		$custom_fields = $edited_Item->get_type_custom_fields();

		if( count( $custom_fields ) )
		{	// Display fieldset with custom fields only if at least one exists:
			$Form->begin_fieldset( T_('Custom fields').get_manual_link( 'post-custom-fields-panel' ), array( 'id' => 'itemform_custom_fields', 'fold' => true ) );

			echo '<table cellspacing="0" class="compose_layout">';

			foreach( $custom_fields as $custom_field )
			{	// Loop through custom fields:
				echo '<tr><td class="label"><label for="item_'.$custom_field['type'].'_'.$custom_field['ID'].'"><strong>'.$custom_field['label'].':</strong></label></td>';
				echo '<td class="input" width="97%">';
				switch( $custom_field['type'] )
				{
					case 'double':
						$Form->text( 'item_double_'.$custom_field['ID'], $edited_Item->get_setting( 'custom_double_'.$custom_field['ID'] ), 10, '', T_('can be decimal') );
						break;
					case 'varchar':
						$Form->text_input( 'item_varchar_'.$custom_field['ID'], $edited_Item->get_setting( 'custom_varchar_'.$custom_field['ID'] ), 20, '', '', array( 'maxlength' => 255, 'style' => 'width: 100%;' ) );
						break;
					case 'text':
						$Form->textarea_input( 'item_text_'.$custom_field['ID'], $edited_Item->get_setting( 'custom_text_'.$custom_field['ID'] ), 5, '' );
						break;
					case 'html':
						$Form->textarea_input( 'item_html_'.$custom_field['ID'], $edited_Item->get_setting( 'custom_html_'.$custom_field['ID'] ), 5, '', array( 'note' => T_('This field allows HTML code') ) );
						break;
				}
				echo '</td></tr>';
			}

			echo '</table>';

			$Form->end_fieldset();
		}
	}

	// ############################ ADVANCED PROPERTIES #############################

	$Form->begin_fieldset( T_('Advanced properties').get_manual_link( 'post-advanced-properties-panel' ), array( 'id' => 'itemform_adv_props', 'fold' => true ) );

	echo '<table cellspacing="0" class="compose_layout">';

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
				.T_('Auto-suggest tags as you type (based on existing tags)').$link_to_tags_manager
			.'</label>';
		$Form->text_input( 'item_tags', $item_tags, 40, '', $suggest_checkbox, array(
				'maxlength' => 255,
				'style'     => 'width: 100%;',
				'input_prefix' => '<div class="input-group">',
				'input_suffix' => '<span class="input-group-btn">'
						.'<input class="btn btn-primary" type="button" name="actionArray[extract_tags]"'
							.' onclick="return b2edit_confirm( \''.TS_('This will save your changes, then analyze your post to find existing tags. Are you sure?').'\','
							.' \''.$admin_url.'?ctrl=items&amp;blog='.$edited_Item->get_blog_ID().'\','
							.' \'extract_tags\' );"'
							.' value="'.format_to_output( T_('Extract'), 'htmlattr' ).'" />'
					.'</span></div>',
			) );
		echo '</td></tr>';
	}
	else
	{ // Hide tags
		$Form->hidden( 'item_tags', $item_tags );
	}

	$edited_item_excerpt = $edited_Item->get( 'excerpt' );
	if( $edited_Item->get_type_setting( 'use_excerpt' ) != 'never' )
	{ // Display excerpt
		$field_required = ( $edited_Item->get_type_setting( 'use_excerpt' ) == 'required' ) ? $required_star : '';
		$field_class = param_has_error( 'post_excerpt' ) ? ' field_error' : '';
		echo '<tr><td class="label"><label for="post_excerpt">'.$field_required.'<strong>'.T_('Excerpt').':</strong></label></td>';
		echo '<td class="input" width="97%">';
		$excerpt_checkbox = '<label>'
				.'<input name="post_excerpt_autogenerated" value="1" type="checkbox"'.( $edited_Item->get( 'excerpt_autogenerated' ) ? ' checked="checked"' : '' ).' /> '
				.T_('Auto-generate excerpt from content')
			.'</label>';
		$Form->textarea_input( 'post_excerpt', $edited_item_excerpt, 3, '', array(
				'class'    => $field_class,
				'required' => $field_required,
				'style'    => 'width:100%',
				'note'     => $excerpt_checkbox,
			) );
		echo '</td></tr>';
	}
	else
	{ // Hide excerpt
		$Form->hidden( 'post_excerpt', htmlspecialchars( $edited_item_excerpt ) );
	}

	if( $edited_Item->get_type_setting( 'use_url' ) != 'never' )
	{ // Display url
		$field_required = ( $edited_Item->get_type_setting( 'use_url' ) == 'required' ) ? $required_star : '';
		echo '<tr><td class="label"><label for="post_url">'.$field_required.'<strong>'.T_('Link to url').':</strong></label></td>';
		echo '<td class="input" width="97%">';
		$Form->text_input( 'post_url', $edited_Item->get( 'url' ), 20, '', '', array( 'maxlength' => 255, 'style' => 'width:100%' ) );
		echo '</td></tr>';
	}
	else
	{ // Hide url
		$Form->hidden( 'post_url', $edited_Item->get( 'url' ) );
	}

	if( $edited_Item->get_type_setting( 'use_parent' ) != 'never' )
	{ // Display parent ID:
		if( $parent_Item = & $edited_Item->get_parent_Item() )
		{	// Get parent item info if it is defined:
			$parent_info = '';
			$status_icons = get_visibility_statuses( 'icons' );
			if( isset( $status_icons[ $parent_Item->get( 'status' ) ] ) )
			{	// Status colored icon:
				$parent_info .= $status_icons[ $parent_Item->get( 'status' ) ];
			}
			// Title with link to permament url:
			$parent_info .= ' '.$parent_Item->get_title( array( 'link_type' => 'permalink' ) );
			// Icon to edit:
			$parent_info .= ' '.$parent_Item->get_edit_link( array( 'text' => '#icon#' ) );
		}
		else
		{	// No parent item defined
			$parent_info = '';
		}
		$field_required = ( $edited_Item->get_type_setting( 'use_parent' ) == 'required' ) ? $required_star : '';
		echo '<tr><td class="label"><label for="post_parent_ID">'.$field_required.'<strong>'.T_('Parent ID').':</strong></label></td>';
		echo '<td class="input" width="97%">';
		$Form->text_input( 'post_parent_ID', $edited_Item->get( 'parent_ID' ), 11, '', $parent_info );
		echo '</td></tr>';
	}
	else
	{ // Hide parent ID:
		$Form->hidden( 'post_parent_ID', $edited_Item->get( 'parent_ID' ) );
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

	$Form->end_fieldset();


	// ####################### ADDITIONAL ACTIONS #########################

	if( isset( $Blog ) && $Blog->get('allowtrackbacks') )
	{
		$Form->begin_fieldset( T_('Additional actions').get_manual_link( 'post-edit-additional-actions-panel' ), array( 'id' => 'itemform_additional_actions', 'fold' => true ) );

		// --------------------------- TRACKBACK --------------------------------------
		?>
		<div id="itemform_trackbacks">
			<label for="trackback_url"><strong><?php echo T_('Trackback URLs') ?>:</strong>
			<span class="notes"><?php echo T_('(Separate by space)') ?></span></label><br />
			<input type="text" name="trackback_url" class="large form_text_input form-control" id="trackback_url" value="<?php echo format_to_output( $trackback_url, 'formvalue' ); ?>" />
		</div>
		<?php

		$Form->end_fieldset();
	}


	// ####################### PLUGIN FIELDSETS #########################

	$Plugins->trigger_event( 'AdminDisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item, 'edit_layout' => 'expert' ) );

	if( $current_User->check_perm( 'meta_comment', 'view', false, $Blog->ID ) )
	{
		// ####################### META COMMENTS #########################
		$currentpage = param( 'currentpage', 'integer', 1 );
		$total_comments_number = generic_ctp_number( $edited_Item->ID, 'metas', 'total' );
		param( 'comments_number', 'integer', $total_comments_number );
		param( 'comment_type', 'string', 'meta' );

		$Form->begin_fieldset( T_('Meta comments').get_manual_link( 'meta-comments-panel' )
						.( $total_comments_number > 0 ? ' <span class="badge badge-important">'.$total_comments_number.'</span>' : '' ),
					array( 'id' => 'itemform_meta_cmnt', 'fold' => true, 'deny_fold' => ( $total_comments_number > 0 ) ) );

		if( $creating )
		{	// Display button to save new creating item:
			$Form->submit( array( 'actionArray[create_edit]', /* TRANS: This is the value of an input submit button */ T_('Save post to start adding Meta comments'), 'btn-primary' ) );
		}
		else
		{	// Display meta comments of the edited item:
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
					'msg_empty' => T_('No meta comment for this post yet...'),
				) );
			require $inc_path.'comments/views/_comment_list.inc.php';
			echo '</div>'; // comments_container div
			echo '</div>';

			if( $edited_Item->can_meta_comment() )
			{ // Display a link to add new meta comment if current user has a permission
				echo action_icon( T_('Add meta comment').'...', 'new', $admin_url.'?ctrl=items&amp;p='.$edited_Item->ID.'&amp;comment_type=meta&amp;blog='.$Blog->ID.'#comments', T_('Add meta comment').' &raquo;', 3, 4 );
			}

			// Load JS functions to work with meta comments:
			load_funcs( 'comments/model/_comment_js.funcs.php' );
		}

		$Form->end_fieldset();
	}
	?>

</div>

<div class="right_col col-lg-3 col-md-4">

	<?php
	// ################### MODULES SPECIFIC ITEM SETTINGS ###################

	modules_call_method( 'display_item_settings', array( 'Form' => & $Form, 'Blog' => & $Blog, 'edited_Item' => & $edited_Item, 'edit_layout' => 'expert', 'fold' => true ) );

	// ############################ WORKFLOW #############################

	if( $Blog->get_setting( 'use_workflow' ) && $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) )
	{	// We want to use workflow properties for this blog:
		$Form->begin_fieldset( T_('Workflow properties').get_manual_link( 'post-edit-workflow-panel' ), array( 'id' => 'itemform_workflow_props', 'fold' => true ) );

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
				$Form->username( 'item_assigned_user_login', $assigned_User, T_('Assigned to'), '', 'only_assignees', array( 'size' => 10 ) );
			}
			else
			{
				$Form->select_object( 'item_assigned_user_ID', NULL, $edited_Item, T_('Assigned to'),
														'', true, '', 'get_assigned_user_options' );
			}

			echo ' '; // allow wrapping!

			$ItemStatusCache = & get_ItemStatusCache();
			$ItemStatusCache->load_all();

			$ItemTypeCache = & get_ItemTypeCache();
			$current_ItemType = $ItemTypeCache->get_by_ID( $edited_Item->ityp_ID );
			$Form->select_options( 'item_st_ID', $ItemStatusCache->get_option_list( $edited_Item->pst_ID, true, 'get_name', $current_ItemType->get_ignored_post_status() ), T_('Task status') );

			echo ' '; // allow wrapping!

			$Form->date( 'item_deadline', $edited_Item->get('datedeadline'), T_('Deadline') );

			$Form->switch_layout( NULL );
			echo '</div>';

		$Form->end_fieldset();
	}
	// ################### CATEGORIES ###################

	cat_select( $Form, true, true, array( 'fold' => true ) );

	// ################### LOCATIONS ###################
	echo_item_location_form( $Form, $edited_Item, array( 'fold' => true ) );

	// ################### PROPERTIES ###################

	$Form->begin_fieldset( T_('Properties').get_manual_link( 'post-properties-panel' ), array( 'id' => 'itemform_extra', 'fold' => true ) );

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

	echo '</table>';

	$Form->switch_layout( NULL );

	$Form->end_fieldset();


	// ################### TEXT RENDERERS ###################

	$Form->begin_fieldset( T_('Text Renderers').get_manual_link( 'post-renderers-panel' )
					.action_icon( T_('Plugins'), 'edit', $admin_url.'?ctrl=coll_settings&amp;tab=plugins&plugin_group=rendering&amp;blog='.$Blog->ID, T_('Plugins'), 3, 4, array( 'class' => 'action_icon pull-right' ) ),
				array( 'id' => 'itemform_renderers', 'fold' => true ) );

	// fp> TODO: there should be no param call here (shld be in controller)
	$edited_Item->renderer_checkboxes( param('renderers', 'array:string', NULL) );

	$Form->end_fieldset();


	// ################### COMMENT STATUS ###################

	if( $edited_Item->allow_comment_statuses() )
	{
		$Form->begin_fieldset( T_('Comments').get_manual_link( 'post-comments-panel' ), array( 'id' => 'itemform_comments', 'fold' => true ) );

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

		if( $edited_Item->get_type_setting( 'allow_comment_form_msg' ) )
		{	// If custom message is allowed before comment form:
			$Form->switch_layout( 'none' );
			$Form->textarea_input( 'comment_form_msg', $edited_Item->get_setting( 'comment_form_msg' ), 3, T_('Message before comment form') );
			echo '<br />';
			$Form->switch_layout( NULL );
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

	$Form->begin_fieldset( T_('Goal tracking').get_manual_link( 'post-goal-tracking-panel' )
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


	// ################### NOTIFICATIONS ###################

	$Form->begin_fieldset( T_('Notifications').get_manual_link( 'post-notifications-panel' ), array( 'id' => 'itemform_notifications', 'fold' => true ) );

		$Form->info( T_('Moderators'), $edited_Item->check_notifications_flags( 'moderators_notified' ) ? T_('Notified at least once') : T_('Not notified yet') );

		$notify_types = array(
				'members_notified'   => T_('Members'),
				'community_notified' => T_('Community'),
				'pings_sent'         => T_('Public pings'),
		);

		foreach( $notify_types as $notify_type => $notify_title )
		{
			if( ! $edited_Item->notifications_allowed() )
			{	// Notifications are not allowed for the Item:
				$Form->info( $notify_title, T_('Not Possible for this post type') );
			}
			else
			{	// Notifications are allowed for the Item:
				if( $edited_Item->check_notifications_flags( $notify_type ) )
				{	// Nofications/Pings were sent:
					$notify_status = ( $notify_type == 'pings_sent' ) ? T_('Sent') : T_('Notified');
					$notify_select_options = array(
							''      => T_('Done'),
							'force' => ( $notify_type == 'pings_sent' ) ? T_('Send again') : T_('Notify again')
						);
				}
				elseif( $edited_Item->get_type_setting( 'usage' ) != 'post' )
				{	// Item type is not applicable and Nofications/Pings are not sent yet:
					$notify_status = T_('Not Recommended');
					$notify_select_options = array(
							''      => T_('Do nothing'),
							'force' => ( $notify_type == 'pings_sent' ) ? T_('Send anyways') : T_('Notify anyways'),
							'mark'  => ( $notify_type == 'pings_sent' ) ? T_('Mark as Sent') : T_('Mark as Notified')
						);
				}
				else
				{	// Nofications/Pings are not sent yet:
					$notify_status = ( $notify_type == 'pings_sent' ) ? T_('To be sent') : T_('To be notified');
					$notify_select_options = array(
							''     => ( $notify_type == 'pings_sent' ) ? T_('Send on next save') : T_('Notify on next save'),
							'skip' => T_('Skip on next save'),
							'mark' => ( $notify_type == 'pings_sent' ) ? T_('Mark as Sent') : T_('Mark as Notified')
						);
				}
				$Form->select_input_array( 'item_'.$notify_type, get_param( 'item_'.$notify_type ), $notify_select_options, $notify_title, NULL, array( 'input_prefix' => $notify_status.' &nbsp; &nbsp; ' ) );
			}
		}

	$Form->end_fieldset();


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

	if( $current_User->check_perm( 'blog_post!published', 'create', false, $Blog->ID ) )
	{ // Display a link to show/hide quick button to publish the post ONLY if current user has a permission:
		echo '<p>';
		if( $UserSettings->get_collection_setting( 'show_quick_publish', $Blog->ID ) )
		{ // The quick button is displayed
			echo action_icon( '', 'activate', $quick_setting_url.'hide_quick_button', T_('Show the quick "Publish!" button when relevant.'), 3, 4 );
		}
		else
		{ // The quick button is hidden
			echo action_icon( '', 'deactivate', $quick_setting_url.'show_quick_button', T_('Never show the quick "Publish!" button.'), 3, 4 );
		}
		echo '</p>';

		// CALL PLUGINS NOW:
		ob_start();
		$Plugins->trigger_event( 'AdminDisplayEditorButton', array(
				'target_type'   => 'Item',
				'target_object' => $edited_Item,
				'content_id'    => 'itemform_post_content',
				'edit_layout'   => 'expert_quicksettings',
				'quicksetting_item_start' => '<p id="quicksetting_wysiwyg_switch">',
				'quicksetting_item_end' => '</p>'
			) );
		$quick_setting_switch = ob_get_flush();
	}

	// Display a link to reset default settings for current user on this screen:
	echo '<p>';
	echo action_icon( '', 'refresh', $quick_setting_url.'reset_quick_settings', T_('Reset defaults for this screen.'), 3, 4 );
	echo '</p>';

	?>

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
// Location
echo_regional_js( 'item', $edited_Item->region_visible() );
// Goal
echo_onchange_goal_cat();
// Fieldset folding
echo_fieldset_folding_js();
// Save and restore item content field height and scroll position:
echo_item_content_position_js( get_param( 'content_height' ), get_param( 'content_scroll' ) );

// JS to post excerpt mode switching:
?>
<script type="text/javascript">
jQuery( '#post_excerpt' ).on( 'keyup', function()
{
	// Disable excerpt auto-generation on any changing and enable if excerpt field is empty:
	jQuery( 'input[name=post_excerpt_autogenerated]' ).prop( 'checked', ( jQuery( this ).val() == '' ) );
} );
</script>
<?php

// require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';

?>
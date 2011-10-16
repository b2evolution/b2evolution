<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id$
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

global $Session;

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
		$Form->hidden( 'post_ID', $edited_Item->ID );

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
<div class="left_col">

	<?php
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
	$Plugins->trigger_event( 'AdminDisplayToolbar', array( 'target_type' => 'Item', 'edit_layout' => 'expert' ) );
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
	if( isset($GLOBALS['files_Module']) )
	{
		attachment_iframe( $Form, $creating, $edited_Item, $Blog, $iframe_name );
	}
	// ############################ ADVANCED #############################

	$Form->begin_fieldset( T_('Advanced properties').get_manual_link('post_advanced_properties_fieldset'), array( 'id' => 'itemform_adv_props' ) );

	// CUSTOM FIELDS varchar
	echo '<table cellspacing="0" class="compose_layout">';
	for( $i = 1 ; $i <= 3; $i++ )
	{	// For each custom double field:
		if( $field_name = $Blog->get_setting('custom_varchar'.$i) )
		{	// Field has a name: display it:
			echo '<tr><td class="label"><label for="item_varchar'.$i.'"><strong>'.$field_name.':</strong></label></td>';
			echo '<td class="input" width="97%">';
			$Form->text_input( 'item_varchar'.$i, $edited_Item->{'varchar'.$i}, 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
			echo '</td><td width="1"><!-- for IE7 --></td></tr>';
		}
	}

	//add slug_changed field - needed for slug trim, if this field = 0 slug will trimmed
	$Form->hidden( 'slug_changed', 0 );

	echo '<tr><td class="label"><label for="post_urltitle" title="'.T_('&quot;slug&quot; to be used in permalinks').'"><strong>'.T_('URL title "slug"').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'post_urltitle', $edited_Item->get('urltitle'), 40, '', '', array('maxlength'=>210, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '<tr><td class="label"><label for="titletag"><strong>'.T_('&lt;title&gt; tag').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'titletag', $edited_Item->get('titletag'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '<tr><td class="label"><label for="metadesc" title="&lt;meta name=&quot;description&quot;&gt;"><strong>'.T_('&lt;meta&gt; desc').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'metadesc', $edited_Item->get('metadesc'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '<tr><td class="label"><label for="metakeywords" title="&lt;meta name=&quot;keywords&quot;&gt;"><strong>'.T_('&lt;meta&gt; keywds').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'metakeywords', $edited_Item->get('metakeywords'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '<tr><td class="label"><label for="item_tags"><strong>'.T_('Tags').':</strong> <span class="notes">'.T_('sep by ,').'</span></label></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'item_tags', $item_tags, 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

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
		<textarea name="post_excerpt" rows="2" cols="25" class="large" id="post_excerpt"><?php echo htmlspecialchars($edited_Item_excerpt) ?></textarea>
	</div>

	<?php

	if( $edited_Item->get('excerpt_autogenerated') )
	{ // store hash of current post_excerpt to detect if it was changed.
		$Form->hidden('post_excerpt_previous_md5', md5($edited_Item_excerpt));
	}
	$Form->end_fieldset();


	// ############################ WORKFLOW #############################

	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$Form->begin_fieldset( T_('Workflow properties'), array( 'id' => 'itemform_workflow_props' ) );

			echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';
			$Form->switch_layout( 'linespan' );

			$Form->select_object( 'item_priority', NULL, $edited_Item, T_('Priority'), '', true, '', 'priority_options' );

			echo ' '; // allow wrapping!

			$Form->select_object( 'item_assigned_user_ID', NULL, $edited_Item, T_('Assigned to'),
														'', true, '', 'get_assigned_user_options' );

			echo ' '; // allow wrapping!

			$ItemStatusCache = & get_ItemStatusCache();
			$Form->select_options( 'item_st_ID', $ItemStatusCache->get_option_list( $edited_Item->pst_ID, true ), T_('Task status') );

			echo ' '; // allow wrapping!

			$Form->date( 'item_deadline', $edited_Item->get('datedeadline'), T_('Deadline') );

			$Form->switch_layout( NULL );
			echo '</div>';

		$Form->end_fieldset();
	}

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

<div class="right_col">

	<?php
  // ################### MODULES SPECIFIC ITEM SETTINGS ###################

	modules_call_method( 'display_item_settings', array( 'Form' => & $Form, 'Blog' => & $Blog, 'edited_Item' => & $edited_Item ) );

	// ################### CATEGORIES ###################

	cat_select( $Form );

	// ################### PROPERTIES ###################

	$Form->begin_fieldset( T_('Properties'), array( 'id' => 'itemform_extra' ) );

	$Form->checkbox_basic_input( 'item_featured', $edited_Item->featured, '<strong>'.T_('Featured post').'</strong>' );
	echo "<br/>";

	$Form->checkbox_basic_input( 'item_hideteaser', $edited_Item->hideteaser, '<strong>'.T_('Hide teaser when displaying -- more --').'</strong>' );
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

	// CUSTOM FIELDS double
	for( $i = 1 ; $i <= 5; $i++ )
	{	// For each custom double field:
		if( $field_name = $Blog->get_setting('custom_double'.$i) )
		{	// Field has a name: display it:
			echo '<tr><td><strong>'.$field_name.':</strong></td><td>';
			$Form->text( 'item_double'.$i, $edited_Item->{'double'.$i}, 10, '', T_('can be decimal') );
			echo '</td></tr>';
		}
	}

	echo '</table>';

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
	$edited_Item->renderer_checkboxes( param('renderers', 'array', NULL) );

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

		$Form->end_fieldset();
	}

	?>

</div>

<div class="clear"></div>

<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();

// ####################### JS BEHAVIORS #########################
echo_publishnowbutton_js();
echo_set_is_attachments();
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

// require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';

/*
 * $Log$
 * Revision 1.84  2011/10/16 19:39:06  fplanque
 * Moved inskin.form to _edit.disp because there is no use for inskin.from in the backoffice.
 *
 * Revision 1.83  2011/10/12 11:23:31  efy-yurybakh
 * In skin posting (beta)
 *
 * Revision 1.82  2011/10/05 14:16:55  efy-asimo
 * Display module specific item settings in both item edit form
 *
 * Revision 1.81  2011/10/04 21:56:00  fplanque
 * minor
 *
 * Revision 1.80  2011/09/08 05:22:40  efy-asimo
 * Remove item attending and add item settings
 *
 * Revision 1.79  2011/09/04 22:13:17  fplanque
 * copyright 2011
 *
 * Revision 1.78  2011/08/25 01:02:10  fplanque
 * doc/minor
 *
 * Revision 1.77  2011/08/24 12:16:43  efy-james
 * Add checkbox for hide teaser
 *
 * Revision 1.76  2011/08/16 07:02:25  efy-asimo
 * Fix attaching files issue on new post create
 *
 * Revision 1.75  2011/05/25 14:59:34  efy-asimo
 * Post attending
 *
 * Revision 1.74  2011/03/02 09:45:59  efy-asimo
 * Update collection features allow_comments, disable_comments_bypost, allow_attachments, allow_rating
 *
 * Revision 1.73  2011/01/06 14:31:47  efy-asimo
 * advanced blog permissions:
 *  - add blog_edit_ts permission
 *  - make the display more compact
 */
?>

<?php
/**
 * This is the template that displays the edit item form. It gets POSTed to /htsrv/item_edit.php.
 *
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Collection, $Blog, $Session, $inc_path;
global $action, $form_action;

/**
 * @var User
 */
global $current_User;
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

global $trackback_url;
global $bozo_start_modified, $creating;
global $edited_Item, $item_tags, $item_title, $item_content;
global $post_category, $post_extracats;
global $admin_url, $redirect_to, $form_action;


// Default params:
$params = array_merge( array(
		'edit_form_params' => array(),
	), $params );

// Determine if we are creating or updating...
$creating = is_create_action( $action );

// Used to mark the required fields (in non-standard template)
$required_star = '<span class="label_field_required">*</span>';

$Form = new Form( $form_action, 'item_checkchanges', 'post' );

$Form->switch_template_parts( $params['edit_form_params'] );

// ================================ START OF EDIT FORM ================================
$form_params = array();
$iframe_name = NULL;
if( !empty( $bozo_start_modified ) )
{
	$form_params['bozo_start_modified'] = true;
}

$Form->begin_form( 'inskin', '', $form_params );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $Blog->ID );
	if( isset( $edited_Item ) )
	{
		$copy_post_ID = param( 'cp', 'integer', 0 );
		if( $copy_post_ID > 0 )
		{	// Copy post
			$Form->hidden( 'post_ID', 0 );
		}
		else
		{	// Edit post
			$Form->hidden( 'post_ID', $edited_Item->ID );
		}
	}
	$Form->hidden( 'redirect_to', $redirect_to );

	// In case we send this to the blog for a preview :
	$Form->hidden( 'preview', 0 );
	$Form->hidden( 'more', 1 );

	// Add hidden required fields or fields that were set in the init_inskin_editing() function
	$Form->hidden( 'item_typ_ID', $edited_Item->ityp_ID );

	if( $edited_Item->get( 'urltitle' ) != '' )
	{	// post_urltitle can be defined from request param
		$Form->hidden( 'post_urltitle', $edited_Item->get( 'urltitle' ) );
	}

	if( $action != 'new' )
	{ // DO NOT ADD HIDDEN FIELDS IF THEY ARE NOT SET
		// These fields will be set only in case when switch tab from admin editing to in-skin editing
		// Fields used in "advanced" form, but not here:
		$Form->hidden( 'post_comment_status', $edited_Item->get( 'comment_status' ) );
		$Form->hidden( 'post_locale', $edited_Item->get( 'locale' ) );
		$Form->hidden( 'post_locale_visibility', $edited_Item->get( 'locale_visibility' ) );
		$Form->hidden( 'post_parent_ID', $edited_Item->get( 'parent_ID' ) );
		$Form->hidden( 'titletag', $edited_Item->get( 'titletag' ) );
		$Form->hidden( 'metadesc', $edited_Item->get_setting( 'metadesc' ) );
		$Form->hidden( 'metakeywords', $edited_Item->get_setting( 'metakeywords' ) );

		if( $edited_Item->can_edit_workflow( 'status' ) )
		{	// Allow workflow status if current user can edit this property:
			$Form->hidden( 'item_st_ID', $edited_Item->pst_ID );
		}
		if( $edited_Item->can_edit_workflow( 'status' ) )
		{	// Allow workflow user if current user can edit this property:
			$Form->hidden( 'item_assigned_user_ID', $edited_Item->assigned_user_ID );
		}
		if( $edited_Item->can_edit_workflow( 'priority' ) )
		{	// Allow workflow priority if current user can edit this property:
			$Form->hidden( 'item_priority', $edited_Item->priority );
		}
		if( $edited_Item->can_edit_workflow( 'deadline' ) )
		{	// Allow workflow deadline if current user can edit this property:
			$Form->hidden( 'item_deadline', mysql2date( locale_input_datefmt(), $edited_Item->datedeadline ) );
			$Form->hidden( 'item_deadline_time', mysql2date( 'H:i', $edited_Item->datedeadline ) );
		}
		$Form->hidden( 'trackback_url', $trackback_url );
		if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
		{	// If user has a permission to edit advanced properties of items:
			$Form->hidden( 'item_featured', $edited_Item->featured );
			$Form->hidden( 'expiry_delay', $edited_Item->get_setting( 'comment_expiry_delay' ) );
			$Form->hidden( 'goal_ID', $edited_Item->get_setting( 'goal_ID' ) );
		}
		if( is_pro() && $Blog->get_setting( 'track_unread_content' ) )
		{	// Update setting to mark Item as "must read" only for PRO version and when tracking of unread content is enabled for collection:
			$Form->hidden( 'item_mustread', $edited_Item->get_setting( 'mustread' ) );
		}
		$Form->hidden( 'item_hideteaser', $edited_Item->get_setting( 'hide_teaser' ) );
		$Form->hidden( 'item_switchable', $edited_Item->get_setting( 'switchable' ) );
		$Form->hidden( 'item_switchable_params', $edited_Item->get_setting( 'switchable_params' ) );

		$creator_User = $edited_Item->get_creator_User();
		$Form->hidden( 'item_owner_login', $creator_User->login );
		$Form->hidden( 'item_owner_login_displayed', 1 );
	}
	elseif( !isset( $edited_Item->status ) )
	{
		$highest_publish_status = get_highest_publish_status( 'post', $Blog->ID, false, '', $edited_Item );
		$edited_Item->set( 'status', $highest_publish_status );
	}

	if( $current_User->check_perm( 'admin', 'restricted' ) )
	{ // These fields can be edited only by users which have an access to back-office
		if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
		{ // Time stamp field values
			$Form->hidden( 'item_dateset', $edited_Item->get( 'dateset' ) );
			$Form->hidden( 'item_issue_date', mysql2localedate( $edited_Item->get( 'issue_date' ) ) );
			$Form->hidden( 'item_issue_time', substr( $edited_Item->get( 'issue_date' ), 11 ) );
		}
	}

	if( $edited_Item->get_type_setting( 'use_coordinates' ) != 'never' )
	{
		$Form->hidden( 'item_latitude', $edited_Item->get_setting( 'latitude' ) );
		$Form->hidden( 'item_longitude', $edited_Item->get_setting( 'longitude' ) );
		$Form->hidden( 'google_map_zoom', $edited_Item->get_setting( 'map_zoom' ) );
		$Form->hidden( 'google_map_type', $edited_Item->get_setting( 'map_type' ) );
	}

	if( $edited_Item->get_type_setting( 'allow_attachments' ) &&
			$current_User->check_perm( 'files', 'view', false ) )
	{	// If current user has a permission to view the files AND attachments are allowed for the item type:
		load_class( 'links/model/_linkitem.class.php', 'LinkItem' );
		// Initialize this object as global because this is used in many link functions:
		global $LinkOwner;
		$LinkOwner = new LinkItem( $edited_Item, param( 'temp_link_owner_ID', 'integer', 0 ) );
	}

	$front_edit_fields = $edited_Item->get_front_edit_fields();
	foreach( $front_edit_fields as $front_edit_field )
	{
		$front_edit_field_is_visible = ! empty( $front_edit_field['order'] );
		if( $front_edit_field['type'] == 'item' )
		{	// Item field:
			switch( $front_edit_field['name'] )
			{
				case 'title':
					// Title:
					if( $edited_Item->get_type_setting( 'use_title' ) == 'never' )
					{	// Skip, because it is not used for the Item Type:
						break;
					}
					if( $front_edit_field_is_visible )
					{	// Display only if it is visible on front-office:
						$Form->switch_layout( 'fields_table' );
						$Form->begin_fieldset();
						$Form->text_input( 'post_title', $item_title, 20, T_('Title'), '', array(
								'maxlength' => intval( $edited_Item->get_type_setting( 'title_maxlen' ) ),
								'required'  => ( $edited_Item->get_type_setting( 'use_title' ) == 'required' ),
								'style'     => 'width:100%',
							) );
						$Form->end_fieldset();
						$Form->switch_layout( NULL );
					}
					else
					{	// Put value in hidden field for proper switching between back-office edit form:
						$Form->hidden( 'post_title', $item_title );
					}
					break;

				case 'short_title':
					// Short title:
					if( $edited_Item->get_type_setting( 'use_short_title' ) == 'never' )
					{	// Skip, because it is not used for the Item Type:
						break;
					}
					if( $front_edit_field_is_visible )
					{	// Display only if it is visible on front-office:
						$Form->switch_layout( 'fields_table' );
						$Form->begin_fieldset();
						$Form->text_input( 'post_short_title', htmlspecialchars_decode( $edited_Item->get( 'short_title' ) ), 50, T_('Short title'), '', array(
								'maxlength' => intval( $edited_Item->get_type_setting( 'short_title_maxlen' ) ),
								'style'     => 'width:100%',
							) );
						$Form->end_fieldset();
						$Form->switch_layout( NULL );
					}
					else
					{	// Put value in hidden field for proper switching between back-office edit form:
						$Form->hidden( 'post_short_title', htmlspecialchars_decode( $edited_Item->get( 'short_title' ) ) );
					}
					break;

				case 'text':
					// Text:
					if( $edited_Item->get_type_setting( 'use_text' ) == 'never' )
					{	// Skip, because it is not used for the Item Type:
						break;
					}
					if( $front_edit_field_is_visible )
					{	// Display only if it is visible on front-office:
						// --------------------------- TOOLBARS ------------------------------------
						echo '<div class="edit_toolbars">';
						// CALL PLUGINS NOW:
						$admin_toolbar_params = array(
								'edit_layout' => 'expert',
								'Item' => $edited_Item,
							);
						if( isset( $LinkOwner) && $LinkOwner->is_temp() )
						{
							$admin_toolbar_params['temp_ID'] = $LinkOwner->get_ID();
						}
						$Plugins->trigger_event( 'AdminDisplayToolbar', $admin_toolbar_params );
						echo '</div>';

						// ---------------------------- TEXTAREA -------------------------------------
						$Form->switch_layout( 'none' );
						$Form->fieldstart = '<div class="edit_area" data-filedrop-callback="helloWorld">';
						$Form->fieldend = "</div>\n";
						$Form->textarea_input( 'content', $item_content, 16, NULL, array(
								'cols' => 50 ,
								'id' => 'itemform_post_content',
								'class' => 'autocomplete_usernames link_attachment_dropzone'
							) );
						$Form->switch_layout( NULL );
						?>
						<script>
							<!--
							// This is for toolbar plugins
							var b2evoCanvas = document.getElementById('itemform_post_content');
							//-->
						</script>

						<?php
						echo '<div class="edit_plugin_actions">';
						// Text Renderers:
						if( $Blog->get_setting( 'in_skin_editing_renderers' ) )
						{	// If text renderers are allowed to update from front-office:
							$item_renderer_checkboxes = $edited_Item->get_renderer_checkboxes();
						}
						if( ! empty( $item_renderer_checkboxes ) )
						{	// Display only if at least one text renderer is visible:
							echo '<div id="itemform_renderers" class="btn-group dropup pull-right">
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span> '.T_('Text Renderers').'</button>
								<div class="dropdown-menu dropdown-menu-right">'.$item_renderer_checkboxes.'</div>
							</div>';
							// JS code to don't hide popup on click to checkbox:
							echo '<script>jQuery( "#itemform_renderers .dropdown-menu" ).on( "click", function( e ) { e.stopPropagation() } )</script>';
						}
						// CALL PLUGINS NOW:
						$display_editor_params = array(
								'target_type'   => 'Item',
								'target_object' => $edited_Item,
								'content_id'    => 'itemform_post_content',
								'edit_layout'   => 'inskin',
							);
						if( isset( $LinkOwner) && $LinkOwner->is_temp() )
						{
							$display_editor_params['temp_ID'] = $LinkOwner->get_ID();
						}
						$Plugins->trigger_event( 'DisplayEditorButton', $display_editor_params );
						echo '</div>';
						echo '<div class="clear"></div>';
					}
					else
					{	// Put value in hidden field for proper switching between back-office edit form:
						$Form->hidden( 'content', $item_content );
					}
					break;

				case 'instruction':
					// Instructions:
					if( $front_edit_field_is_visible && $edited_Item->get_type_setting( 'instruction' ) )
					{	// Display only if it is visible on front-office:
						echo '<div class="alert alert-info fade in evo_instruction">'.$edited_Item->get_type_setting( 'instruction' ).'</div>';
					}
					break;

				case 'attachments':
					// Attachments:
					if( $front_edit_field_is_visible )
					{	// Display only if it is visible on front-office:
						$Form->attachments_fieldset( $edited_Item );
					}
					break;

				case 'tags':
					// Tags:
					if( $edited_Item->get_type_setting( 'use_tags' ) == 'never' )
					{	// Skip, because it is not used for the Item Type:
						break;
					}
					if( $front_edit_field_is_visible )
					{	// Display only if it is visible on front-office:
						// Checkbox to suggest tags:
						$suggest_checkbox = '<label class="text-normal">'
								.'<input id="suggest_item_tags" name="suggest_item_tags" value="1" type="checkbox"'.( $UserSettings->get( 'suggest_item_tags' ) ? ' checked="checked"' : '' ).' /> '
								.T_('Auto-suggest tags as you type (based on existing tags)')
							.'</label>';
						$Form->text_input( 'item_tags', $item_tags, 40, T_('Tags'), $suggest_checkbox, array(
								'maxlength' => 255,
								'required'  => ( $edited_Item->get_type_setting( 'use_tags' ) == 'required' ),
								'style'     => 'width:100%',
							) );
					}
					else
					{	// Put value in hidden field for proper switching between back-office edit form:
						$Form->hidden( 'item_tags', $item_tags );
						$Form->hidden( 'suggest_item_tags', $UserSettings->get( 'suggest_item_tags' ) );
					}
					break;

				case 'excerpt':
					// Excerpt:
					if( $edited_Item->get_type_setting( 'use_excerpt' ) == 'never' )
					{	// Skip, because it is not used for the Item Type:
						break;
					}
					if( $front_edit_field_is_visible )
					{	// Display only if it is visible on front-office:
						$excerpt_checkbox = '<label class="text-normal">'
								.'<input name="post_excerpt_autogenerated" value="1" type="checkbox"'.( $edited_Item->get( 'excerpt_autogenerated' ) ? ' checked="checked"' : '' ).' /> '
								.T_('Auto-generate excerpt from content')
							.'</label>';
						$Form->textarea_input( 'post_excerpt', $edited_Item->get( 'excerpt' ), 3, T_('Excerpt'), array(
								'required' => ( $edited_Item->get_type_setting( 'use_excerpt' ) == 'required' ),
								'note'     => $excerpt_checkbox,
								'style'    => 'width:100%',
							) );
					}
					else
					{	// Put value in hidden field for proper switching between back-office edit form:
						$Form->hidden( 'post_excerpt', $edited_Item->get( 'excerpt' ) );
						$Form->hidden( 'post_excerpt_autogenerated', $edited_Item->get( 'excerpt_autogenerated' ) );
					}
					break;

				case 'url':
					// Link to url:
					if( $edited_Item->get_type_setting( 'use_url' ) == 'never' )
					{	// Skip, because it is not used for the Item Type:
						break;
					}
					if( $front_edit_field_is_visible )
					{	// Display only if it is visible on front-office:
						if( is_pro() )
						{	// Only PRO feature for using of post link URL as an External Canonical URL:
							$external_canonical_url_checkbox = '<label>'
									.'<input name="post_external_canonical_url" value="1" type="checkbox"'.( $edited_Item->get_setting( 'external_canonical_url' ) ? ' checked="checked"' : '' ).' /> '
									.sprintf( T_('Use as <a %s>External canonical URL</a>'), 'href="'.get_manual_url( 'external-canonical-url' ).'"' ).' '.get_pro_label()
								.'</label>';
						}
						else
						{
							$external_canonical_url_checkbox = '';
						}
						$Form->text_input( 'post_url', $edited_Item->get( 'url' ), 20, T_('Link to url'), $external_canonical_url_checkbox, array(
								'maxlength' => 255,
								'required'  => ( $edited_Item->get_type_setting( 'use_url' ) == 'required' ),
								'style'    => 'width:100%',
							) );
					}
					else
					{	// Put value in hidden field for proper switching between back-office edit form:
						$Form->hidden( 'post_url', $edited_Item->get( 'url' ) );
						if( is_pro() )
						{	// Only PRO feature for using of post link URL as an External Canonical URL:
							$Form->hidden( 'post_external_canonical_url', $edited_Item->get_setting( 'external_canonical_url' ) );
						}
					}
					break;

				case 'location':
					// Location:
					if( ! $edited_Item->country_visible() )
					{	// Skip, because it is not used for the Item Type:
						break;
					}
					if( $front_edit_field_is_visible )
					{	// Display only if it is visible on front-office:
						echo_item_location_form( $Form, $edited_Item );
					}
					else
					{	// Put value in hidden field for proper switching between back-office edit form:
						$Form->hidden( 'item_ctry_ID', $edited_Item->ctry_ID );
						if( $edited_Item->region_visible() )
						{
							$Form->hidden( 'item_rgn_ID', $edited_Item->rgn_ID );
						}
						if( $edited_Item->subregion_visible() )
						{
							$Form->hidden( 'item_subrg_ID', $edited_Item->subrg_ID );
						}
						if( $edited_Item->city_visible() )
						{
							$Form->hidden( 'item_ctry_ID', $edited_Item->ctry_ID );
						}
					}
					break;
			}
		}
		else
		{	// Custom field:
			if( $front_edit_field_is_visible )
			{	// Display only if it is visible on front-office:
				display_editable_custom_field( $front_edit_field['name'], $Form, $edited_Item );
			}
			else
			{	// Put value in hidden field for proper switching between back-office edit form:
				$Form->hidden( 'item_cf_'. $front_edit_field['name'],  $front_edit_field['value'] );
			}
		}
	}

	// ################### CATEGORIES ###################
	if( $Blog->get_setting( 'in_skin_editing_category' ) &&
	    get_post_cat_setting( $Blog->ID ) > 0 )
	{	// Display categories if it is allowed to update from front-office:
		cat_select( $Form, true, false );
	}
	else
	{	// When categories are hidden, we store main and extra categories IDs in the hidden input:
		if( $edited_Item->ID > 0 )
		{	// Get cat_ID from existing Item:
			$main_Chapter = $edited_Item->get_main_Chapter();
			$cat = $main_Chapter->ID;
			$extra_cats = $edited_Item->get( 'extra_cat_IDs' );
		}
		else
		{	// Forums skin get cat_ID from $_GET:
			$cat = param( 'cat', 'integer', 0 );
			$extra_cats = array( $cat );
		}

		if( $cat > 0 )
		{	// Put main and extra categories IDs in the hidden input:
			$Form->hidden( 'post_category', $cat );
			$Form->hidden( 'cat', $cat );
			foreach( $extra_cats as $extra_cat_ID )
			{
				$Form->hidden( 'post_extracats[]', $extra_cat_ID );
			}
		}
	}

// ####################### PLUGIN FIELDSETS #########################
$Plugins->trigger_event( 'DisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item) );
?>

<div class="clear"></div>

<div class="center margin2ex">
<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';
	echo_item_status_buttons( $Form, $edited_Item );
	echo '</div>';
?>
</div>
<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();


// ####################### JS BEHAVIORS #########################
// JS code for status dropdown submit button
echo_status_dropdown_button_js( 'post' );
// New category input box:
echo_onchange_newcat();
echo_autocomplete_tags();
echo_fieldset_folding_js();

// Insert image modal window:
echo_image_insert_modal();

$edited_Item->load_Blog();
?>
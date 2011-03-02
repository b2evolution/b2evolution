<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package collections
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
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

global $mode;
global $post_comment_status, $trackback_url, $item_tags;
global $bozo_start_modified, $creating;
global $item_title, $item_content;
global $redirect_to;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'item_checkchanges', 'post' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ), 4, 2 );

$Form->labelstart = '<strong>';
$Form->labelend = "</strong>\n";

// ================================ START OF EDIT FORM ================================

$params = array();
if( !empty( $bozo_start_modified ) )
{
	$params['bozo_start_modified'] = true;
}
$Form->begin_form( '', '', $params );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $Blog->ID );
	if( isset( $mode ) )   $Form->hidden( 'mode', $mode ); // used by bookmarklet
	if( isset( $edited_Item ) )   $Form->hidden( 'post_ID', $edited_Item->ID );
	$Form->hidden( 'redirect_to', $redirect_to );

	// In case we send this to the blog for a preview :
	$Form->hidden( 'preview', 1 );
	$Form->hidden( 'more', 1 );
	$Form->hidden( 'preview_userid', $current_User->ID );

	// Fields used in "advanced" form, but not here:
	$Form->hidden( 'post_locale', $edited_Item->get( 'locale' ) );
	$Form->hidden( 'item_typ_ID', $edited_Item->ptyp_ID );
	$Form->hidden( 'post_url', $edited_Item->get( 'url' ) );
	$Form->hidden( 'post_excerpt', $edited_Item->get( 'excerpt' ) );
	$Form->hidden( 'post_urltitle', $edited_Item->get( 'urltitle' ) );
	$Form->hidden( 'titletag', $edited_Item->get( 'titletag' ) );
	$Form->hidden( 'metadesc', $edited_Item->get( 'metadesc' ) );
	$Form->hidden( 'metakeywords', $edited_Item->get( 'metakeywords' ) );

	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$Form->hidden( 'item_priority', $edited_Item->priority );
		$Form->hidden( 'item_assigned_user_ID', $edited_Item->assigned_user_ID );
		$Form->hidden( 'item_st_ID', $edited_Item->pst_ID );
		$Form->hidden( 'item_deadline', $edited_Item->datedeadline );
	}
	$Form->hidden( 'trackback_url', $trackback_url );
	$Form->hidden( 'item_featured', $edited_Item->featured );
	$Form->hidden( 'item_order', $edited_Item->order );
	// CUSTOM FIELDS double
	for( $i = 1 ; $i <= 5; $i++ )
	{	// For each custom double field:
		$Form->hidden( 'item_double'.$i, $edited_Item->{'double'.$i} );
	}
	// CUSTOM FIELDS varchar
	for( $i = 1 ; $i <= 3; $i++ )
	{	// For each custom varchar field:
		$Form->hidden( 'item_varchar'.$i, $edited_Item->{'varchar'.$i} );
	}

	// TODO: Form::hidden() do not add, if NULL?!

?>

<div class="left_col">

	<?php
	// ############################ POST CONTENTS #############################

	$Form->begin_fieldset( T_('Mass post contents') );
	//$Form->begin_fieldset( T_('Mass post contents').get_manual_link('post_contents_fieldset') );

	$Form->hidden( 'post_title', 'None' );
	$Form->hidden( 'mass_create', '1' );

	// ---------------------------- TEXTAREA -------------------------------------
	$Form->fieldstart = '<div>';
	$Form->fieldend = "</div>\n";
	$Form->textarea_input( 'content', $item_content, 16, '', array( 'style' => 'width:99%;', 'note' => T_('Separate posts with a blank line. The first line of each post becomes a title.'), 'cols' => 40 , 'rows' => 33, 'id' => 'itemform_post_content' ) );
	$Form->fieldstart = '<div class="tile">';
	$Form->fieldend = '</div>';
	echo '<div style="height:6px"></div>';

	// ------------------------------- SETTINGS ---------------------------------

	$Form->checkbox( 'paragraphs_linebreak', false, '', T_( 'Create paragraphs at each line break' ), 'compose_layout' );

	// ------------------------------- ACTIONS ----------------------------------

	echo '<div class="edit_actions">';

	$next_action = ($creating ? 'create' : 'update');
	$Form->submit( array( 'actionArray['.$next_action.']', /* TRANS: This is the value of an input submit button */ T_('Create posts'), 'SaveButton' ) );

	echo '</div>';

	$Form->end_fieldset();

	?>

</div>

<div class="right_col">

	<?php
	// ################### CATEGORIES ###################

	cat_select( $Form );


	// ################### VISIBILITY / SHARING ###################

	$Form->begin_fieldset( T_('Visibility / Sharing'), array( 'id' => 'itemform_visibility' ) );

	$Form->switch_layout( 'linespan' );
	visibility_select( $Form, $edited_Item->status, true );
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
// New category input box:
echo_onchange_newcat();

/*
 * $Log$
 * Revision 1.13  2011/03/02 09:45:59  efy-asimo
 * Update collection features allow_comments, disable_comments_bypost, allow_attachments, allow_rating
 *
 * Revision 1.12  2010/03/04 19:36:04  fplanque
 * minor/doc
 *
 * Revision 1.11  2010/02/05 09:51:40  efy-asimo
 * create categories on the fly
 *
 * Revision 1.10  2010/01/30 18:55:31  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.9  2010/01/03 13:45:36  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.8  2009/11/23 00:35:19  sam2kb
 * minor
 *
 * Revision 1.7  2009/10/28 10:30:32  efy-maxim
 * 6px
 *
 * Revision 1.6  2009/10/26 17:58:58  efy-maxim
 * mass create fix and design improvement
 *
 * Revision 1.5  2009/10/19 13:28:16  efy-maxim
 * paragraphs at each line break or separate posts with a blank line
 *
 * Revision 1.4  2009/10/18 20:46:27  fplanque
 * no message
 *
 * Revision 1.3  2009/10/18 11:29:43  efy-maxim
 * 1. mass create in 'All' tab; 2. "Text Renderers" and "Comments"
 *
 * Revision 1.2  2009/10/18 08:16:55  efy-maxim
 * log
 *
 */

?>
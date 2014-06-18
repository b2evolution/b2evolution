<?php
/**
 * This file implements the Comment form.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _comment.form.php 6608 2014-05-05 10:39:52Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;
/**
 * @var Comment
 */
global $edited_Comment;
/**
 *
 */
global $Plugins;

global $mode, $month, $tab, $redirect_to, $comment_content;

$Form = new Form( NULL, 'comment_checkchanges', 'post' );

$link_attribs = array( 'style' => 'margin-right: 3ex;' ); // Avoid misclicks by all means!
if( $current_User->check_perm( 'blog_post!draft', 'edit', false, $Blog->ID ) )
{
	$Form->global_icon( T_( 'Elevate this comment into a post' ), '', '?ctrl=comments&amp;action=elevate&amp;comment_ID='.$edited_Comment->ID.'&amp;'.url_crumb('comment'),
				T_( 'Elevate into a post' ), 4, 3, $link_attribs );
}

$delete_url = '?ctrl=comments&amp;action=delete&amp;comment_ID='.$edited_Comment->ID.'&amp;'.url_crumb('comment');
if( $edited_Comment->status == 'trash' )
{
	$delete_title = T_('Delete this comment');
	$delete_text = T_('delete');
	$link_attribs['onclick'] = 'return confirm(\''.TS_('You are about to delete this comment!\\nThis cannot be undone!').'\')';
}
else
{
	$delete_title = T_('Recycle this comment');
	$delete_text = T_('recycle');
}
$Form->global_icon( $delete_title, 'delete', $delete_url, $delete_text, 4, 3, $link_attribs );

$Form->global_icon( T_('Cancel editing!'), 'close', str_replace( '&', '&amp;', $redirect_to), T_('cancel'), 4, 1 );

$Form->begin_form( 'eform' );

$Form->add_crumb( 'comment' );
$Form->hidden( 'ctrl', 'comments' );
$Form->hidden( 'redirect_to', $redirect_to );
$Form->hidden( 'comment_ID', $edited_Comment->ID );
?>

<div class="clear"></div>

<div class="row">

<div class="left_col col-md-9">


	<?php
	$Form->begin_fieldset( T_('Comment contents').get_manual_link( 'comment-content-fieldset' ) );

	echo '<table cellspacing="0" class="compose_layout">';

	echo '<tr><td width="1%"><strong>'.T_('In response to').':</strong></td>';
	echo '<td>';
	$comment_Item = & $edited_Comment->get_Item();
	$comment_Item->title( array(
			'link_type' => 'admin_view',
			'max_length' => '30'
		) );
	echo '</td>';

	$Blog_owner_User = & $Blog->get_owner_User();
	if( ( $Blog_owner_User->ID == $current_User->ID ) || $current_User->check_perm( 'blog_admin', 'edit', false, $Blog->ID ) )
	{ // User has prmission to change comment's post, because user is the owner of the current blog, or user has admin full access permission for current blog
		$Form->switch_layout( 'none' );

		// Move to another post
		echo '<td width="1%">&nbsp;&nbsp;<strong>'.T_('Move to post ID').':</strong></td>';
		echo '<td class="input">';
		$Form->text_input( 'moveto_post', $comment_Item->ID, 20, '', '', array('maxlength'=>100, 'style'=>'width:25%;') );
		echo '</td>';

		$Form->switch_layout( NULL );
	}

	echo '</tr></table>';

	if( $Blog->get_setting( 'threaded_comments' ) )
	{ // Display a reply comment ID only when this feature is enabled in blog settings
		echo '<table cellspacing="0" class="compose_layout">';
		echo '<tr><td width="1%"><strong>'.T_('In reply to comment ID').':</strong></td>';
		echo '<td class="input">';
		$Form->switch_layout( 'none' );
		$Form->text_input( 'in_reply_to_cmt_ID', $edited_Comment->in_reply_to_cmt_ID, 10, '' );
		$Form->switch_layout( NULL );
		echo '&nbsp;<span class="note">'.T_('(leave blank for normal comments)').'</span>';
		echo '</td>';
		echo '</tr></table>';
	}

	echo '<table cellspacing="0" class="compose_layout">';

	if( ! $edited_Comment->get_author_User() )
	{ // This is not a member comment
		$Form->switch_layout( 'none' );

		echo '<tr><td width="1%"><strong>'.T_('Author').':</strong></td>';
		echo '<td class="input">';
		$Form->text_input( 'newcomment_author', $edited_Comment->author, 20, '', '', array('maxlength'=>100, 'style'=>'width: 100%;' ) );
		echo '</td></tr>';

		echo '<tr><td width="1%"><strong>'.T_('Email').':</strong></td>';
		echo '<td class="input">';
		$Form->text_input( 'newcomment_author_email', $edited_Comment->author_email, 20, '', '', array('maxlength'=>100, 'style'=>'width: 100%;') );
		echo '</td></tr>';

		echo '<tr><td width="1%"><strong>'.T_('Website URL').':</strong></td>';
		echo '<td class="input">';
		$Form->text_input( 'newcomment_author_url', $edited_Comment->author_url, 20, '', '', array('maxlength'=>100, 'style'=>'width: 100%;') );
		echo '</td></tr>';

		$Form->switch_layout( NULL );
	}
	else
	{
		echo '<tr><td width="1%"><strong>'.T_('Author').':</strong></td>';
		echo '<td class="input">';
		$edited_Comment->author();
		echo '</td></tr>';
	}

	echo '</table>';
	?>

	<div class="edit_toolbars">
	<?php // --------------------------- TOOLBARS ------------------------------------
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'AdminDisplayToolbar', array(
				'target_type' => 'Comment',
				'edit_layout' => NULL,
				'Comment' => $edited_Comment,
			) );
	?>
	</div>

	<?php // ---------------------------- TEXTAREA -------------------------------------
	$content = $comment_content;
	$Form->fieldstart = '<div class="edit_area">';
	$Form->fieldend = "</div>\n";
	$Form->textarea_input( 'content', $content, 16, '', array( 'cols' => 40 , 'id' => 'commentform_post_content' ) );
	$Form->fieldstart = '<div class="tile">';
	$Form->fieldend = '</div>';
	?>
	<script type="text/javascript">
		<!--
		// This is for toolbar plugins
		var b2evoCanvas = document.getElementById('commentform_post_content');
		//-->
	</script>

	<div class="edit_actions">

	<?php
	// ---------- DELETE ----------
	if( $action == 'editcomment' )
	{	// Editing comment
		// Display delete button if user has permission to:
		$edited_Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton', true );
	}

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Comment', 'edit_layout' => NULL ) );

	echo_comment_buttons( $Form, $edited_Comment );

	?>
	</div>

	<?php
	$Form->end_fieldset();

	// -------------------------- ATTACHMENTS/LINKS --------------------------
	if( isset($GLOBALS['files_Module']) )
	{
		load_class( 'links/model/_linkcomment.class.php', 'LinkComment' );
		$LinkOwner = new LinkComment( $edited_Comment );
		attachment_iframe( $Form, $LinkOwner );
	}

	$Form->begin_fieldset( T_('Advanced properties') );

 	$Form->switch_layout( 'linespan' );

	if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{	// ------------------------------------ TIME STAMP -------------------------------------
		echo '<div id="itemform_edit_timestamp">';
		$Form->date( 'comment_issue_date', $edited_Comment->date, T_('Comment date') );
		echo ' '; // allow wrapping!
		$Form->time( 'comment_issue_time', $edited_Comment->date, '' );
		echo '</div>';
	}

	// --------------------------- ALLOW MESSAGE FORM ---------------------------
	if( ! $edited_Comment->get_author_User() )
	{	// Not a member comment
		// TODO: move next to email address
		?>
		<input type="checkbox" class="checkbox" name="comment_allow_msgform" value="1"
		<?php
		if( $edited_Comment->allow_msgform )
		{
			echo ' checked="checked"';
		}
		?>
			id="comment_allow_msgform" tabindex="7" />
		<label for="comment_allow_msgform"><strong><?php echo T_('Allow message form'); ?></strong></label>
		<span class="note"><?php echo T_( 'Comment author can be contacted directly via email' ); ?></span>
		<?php
	}

	$Form->switch_layout( NULL );

	$Form->end_fieldset();

	// ####################### PLUGIN FIELDSETS #########################

	$Plugins->trigger_event( 'AdminDisplayCommentFormFieldset', array( 'Form' => & $Form, 'Comment' => & $edited_Comment, 'edit_layout' => NULL ) );
	?>

</div>

<div class="right_col col-md-3 form-inline">

<?php
	if( $comment_Item->can_rate()
		|| !empty( $edited_Comment->rating ) )
	{	// Rating is editable
		$Form->begin_fieldset( T_('Rating') );

		echo '<p>';
		$edited_Comment->rating_input( array( 'reset' => true ) );
		echo '</p>';

 		$Form->end_fieldset();
	}
	else
	{
		$Form->hidden( 'comment_rating', 0 );
	}

		/*
		$Form->begin_fieldset( T_('Properties') );
			echo '<p>';
			$Form->checkbox_basic_input( 'comment_featured', $edited_Comment->featured, T_('Featured') );
			echo '</p>';
		$Form->end_fieldset();
		*/

		$Form->begin_fieldset( T_('Visibility'), array( 'id' => 'commentform_visibility' ) );

		$Form->switch_layout( 'linespan' );

		// Get those statuses which are not allowed for the current User to create comments in this blog
		$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_comment!', 'edit' ), array( 'redirected', 'trash' ) );
		// Get allowed visibility statuses
		$sharing_options = get_visibility_statuses( 'radio-options', $exclude_statuses );
		$Form->radio( 'comment_status', $edited_Comment->status, $sharing_options, '', true );

		$Form->switch_layout( NULL );

		$Form->end_fieldset();

		$Form->begin_fieldset( T_('Links') );
			echo '<p>';
			$Form->checkbox_basic_input( 'comment_nofollow', $edited_Comment->nofollow, T_('Nofollow website URL') );
			// TODO: apply to all links  -- note: see basic antispam plugin that does this for x hours
			echo '</p>';
		$Form->end_fieldset();

		$Form->begin_fieldset( T_('Feedback info') );
	?>

	<p><strong><?php echo T_('Type') ?>:</strong> <?php echo $edited_Comment->type; ?></p>
	<p><strong><?php echo T_('IP address') ?>:</strong> <?php
		// Display IP address and allow plugins to filter it, e.g. the DNSBL plugin will add a link to check the IP:
		echo $Plugins->get_trigger_event( 'FilterIpAddress', array('format'=>'htmlbody', 'data'=>$edited_Comment->author_IP), 'data' ); ?>
		<?php $edited_Comment->ip_country(); ?>
	</p>
	<p><strong><?php echo T_('Spam Karma') ?>:</strong> <?php $edited_Comment->spam_karma(); ?></p>

	<?php
		$Form->end_fieldset();

		// ####################### TEXT RENDERERS #########################
		global $Plugins;
		$Form->begin_fieldset( T_('Text Renderers'), array( 'id' => 'itemform_renderers' ) );
		$edited_Comment->renderer_checkboxes();
		$Form->end_fieldset();
	?>
</div>

<div class="clear"></div>

</div>

<?php
$Form->end_form();

// ####################### JS BEHAVIORS #########################
echo_comment_publishbt_js();

?>
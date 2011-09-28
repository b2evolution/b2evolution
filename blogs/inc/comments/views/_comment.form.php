<?php
/**
 * This file implements the Comment form.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
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

global $comments_use_autobr, $mode, $month, $tab, $redirect_to;

$Form = new Form( NULL, 'comment_checkchanges', 'post' );

if( $current_User->check_perm( 'blog_post!draft', 'edit', false, $Blog->ID ) )
{
	$Form->global_icon( T_( 'Elevate this comment into a post' ), '', '?ctrl=comments&amp;action=elevate&amp;comment_ID='.$edited_Comment->ID.'&amp;'.url_crumb('comment'),
				T_( 'Elevate into a post' ), 4, 3, array( 'style' => 'margin-right: 3ex;' ) );
}

$Form->global_icon( T_('Delete this comment'), 'delete', '?ctrl=comments&amp;action=delete&amp;comment_ID='.$edited_Comment->ID.'&amp;'.url_crumb('comment'),
			T_('delete'), 4, 3, array(
				 'onclick' => 'return confirm(\''.TS_('You are about to delete this comment!\\nThis cannot be undone!').'\')',
				 'style' => 'margin-right: 3ex;',	// Avoid misclicks by all means!
		) );

$Form->global_icon( T_('Cancel editing!'), 'close', str_replace( '&', '&amp;', $redirect_to), T_('cancel'), 4, 1 );

$Form->begin_form( 'eform' );

$Form->add_crumb( 'comment' );
$Form->hidden( 'ctrl', 'comments' );
$Form->hidden( 'redirect_to', $redirect_to );
$Form->hidden( 'comment_ID', $edited_Comment->ID );
?>

<div class="clear"></div>

<div class="left_col">


	<?php
	$Form->begin_fieldset( T_('Comment contents') );

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
		$Plugins->trigger_event( 'AdminDisplayToolbar', array( 'target_type' => 'Comment', 'edit_layout' => NULL ) );
	?>
	</div>

	<?php // ---------------------------- TEXTAREA -------------------------------------
	$content = $edited_Comment->content;
	if( $comments_use_autobr == 'always' || $comments_use_autobr == 'opt-out' )
	{
		// echo 'unBR:',htmlspecialchars(str_replace( ' ', '*', $content) );
		$content = unautobrize($content);
	}

	$Form->fieldstart = '<div class="edit_area">';
	$Form->fieldend = "</div>\n";
	$Form->textarea( 'content', $content, 16, '', '', 40 , '' );
	$Form->fieldstart = '<div class="tile">';
	$Form->fieldend = '</div>';
	?>
	<script type="text/javascript">
		<!--
		// This is for toolbar plugins
		var b2evoCanvas = document.getElementById('content');
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

	// --------------------------- AUTOBR --------------------------------------
	// fp> TODO: this should be Auto-P and handled by the Auto-P plugin
	?>
	<input type="checkbox" class="checkbox" name="post_autobr" value="1"
	<?php
	if( $comments_use_autobr == 'always' || $comments_use_autobr == 'opt-out' )
	{
		echo ' checked="checked"';
	}
	?>
		id="autobr" tabindex="6" />
	<label for="autobr"><strong><?php echo T_('Auto-BR'); ?></strong></label>
	<br />

	<?php
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

<div class="right_col">

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

		$sharing_options[] = array( 'published', T_('Published (Public)') );
		$sharing_options[] = array( 'draft', T_('Draft (Not published!)') );
		$sharing_options[] = array( 'deprecated', T_('Deprecated (Not published!)') );
		$Form->radio( 'comment_status', $edited_Comment->status, $sharing_options, '', true );

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
		echo $Plugins->get_trigger_event( 'FilterIpAddress', array('format'=>'htmlbody', 'data'=>$edited_Comment->author_IP), 'data' ); ?></p>
	<p><strong><?php echo T_('Spam Karma') ?>:</strong> <?php $edited_Comment->spam_karma(); ?></p>

	<?php
		$Form->end_fieldset();
	?>
</div>

<div class="clear"></div>

<?php
$Form->end_form();

// ####################### JS BEHAVIORS #########################
echo_comment_publishbt_js();


/*
 * $Log$
 * Revision 1.26  2011/09/28 08:35:17  efy-yurybakh
 * backoffice (-) icon feature to remove rating
 *
 * Revision 1.25  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.24  2011/03/23 14:09:29  efy-asimo
 * Elevate comment into a post feature
 *
 * Revision 1.23  2011/03/02 09:45:59  efy-asimo
 * Update collection features allow_comments, disable_comments_bypost, allow_attachments, allow_rating
 *
 * Revision 1.22  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.21  2011/01/06 14:31:47  efy-asimo
 * advanced blog permissions:
 *  - add blog_edit_ts permission
 *  - make the display more compact
 *
 * Revision 1.20  2010/07/20 06:49:28  efy-asimo
 * admin user can move comments to different post
 * add comments to msgform
 *
 * Revision 1.19  2010/06/24 08:54:05  efy-asimo
 * PHP 4 compatibility
 *
 * Revision 1.18  2010/05/29 03:47:20  sam2kb
 * Added missing crumb
 *
 * Revision 1.17  2010/03/30 11:14:03  efy-asimo
 * move comments from one post to another
 *
 * Revision 1.16  2010/02/08 17:52:13  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.15  2010/01/30 18:55:22  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.14  2010/01/29 23:07:05  efy-asimo
 * Publish Comment button
 *
 * Revision 1.13  2010/01/13 22:09:44  fplanque
 * normalized
 *
 * Revision 1.12  2010/01/13 19:49:45  efy-yury
 * update comments: crumbs
 *
 * Revision 1.11  2009/12/28 07:57:18  sam2kb
 * Added a link to delete comments
 *
 * Revision 1.10  2009/09/26 15:10:26  tblue246
 * Translation fix
 *
 * Revision 1.9  2009/08/31 17:27:31  tblue246
 * Minor
 *
 * Revision 1.8  2009/08/31 17:21:31  fplanque
 * minor
 *
 * Revision 1.7  2009/08/26 23:37:00  tblue246
 * Backoffice comment editing: Allow changing of "Allow message form" setting for guest comments
 *
 * Revision 1.6  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.5  2009/01/19 21:40:58  fplanque
 * Featured post proof of concept
 *
 * Revision 1.4  2008/04/15 21:53:31  fplanque
 * minor
 *
 * Revision 1.3  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/12/18 23:51:33  fplanque
 * nofollow handling in comment urls
 *
 * Revision 1.1  2007/11/25 19:50:09  fplanque
 * missing files!!!
 *
 * Revision 1.3  2007/10/29 01:24:49  fplanque
 * no message
 *
 * Revision 1.2  2007/09/04 19:51:27  fplanque
 * in-context comment editing
 *
 * Revision 1.1  2007/06/25 10:59:38  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.25  2007/04/26 00:11:06  fplanque
 * (c) 2007
 *
 * Revision 1.24  2007/02/28 23:37:52  blueyed
 * doc
 *
 * Revision 1.23  2007/02/25 01:40:43  fplanque
 * doc
 *
 * Revision 1.22  2007/02/21 23:35:57  blueyed
 * Trigger FilterIpAddress event
 *
 * Revision 1.21  2006/12/12 02:53:56  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.20  2006/12/06 23:55:53  fplanque
 * hidden the dead body of the sidebar plugin + doc
 *
 * Revision 1.19  2006/12/03 00:22:17  fplanque
 * doc
 *
 * Revision 1.18  2006/12/01 16:26:34  blueyed
 * Added AdminDisplayCommentFormFieldset hook
 *
 * Revision 1.17  2006/11/19 03:50:29  fplanque
 * cleaned up CSS
 *
 * Revision 1.15  2006/11/16 23:48:55  blueyed
 * Use div.line instead of span.line as element wrapper for XHTML validity
 *
 * Revision 1.14  2006/10/01 22:21:54  blueyed
 * edit_layout param fixes/doc
 */
?>

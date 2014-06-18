<?php
/**
 * This is the template that displays the edit comment form. It gets POSTed to /htsrv/action.php.
 *
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evoskins
 *
 * @version $Id: _edit_comment.disp.php 6508 2014-04-18 04:47:50Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $edited_Comment, $comment_Item, $comment_content;
global $display_params, $admin_url, $samedomain_htsrv_url, $dummy_fields;

if( empty( $comment_Item ) )
{
	$comment_Item = & $edited_Comment->get_Item();
}

$form_action = url_add_param( $admin_url, 'ctrl=comments' );

$display_params = array_merge( $display_params, array(
	'textarea_lines' => 16,
	'form_comment_text' => T_('Comment content'),
) );

$Form = new Form( $form_action, 'comment_edit', 'post' );

$Form->begin_form( 'bComment' );

	$Form->add_crumb( 'comment' );
	$Form->hidden( 'blog', $Blog->ID );
	$Form->hidden( 'mname', 'collections' );
	$Form->hidden( 'action_type', 'comment' );
	$Form->hidden( 'comment_ID', $edited_Comment->ID );
	$Form->hidden( 'redirect_to', url_add_tail( $comment_Item->get_permanent_url(), '#c'.$edited_Comment->ID ) );

	$Form->info( T_('In response to'), $comment_Item->get_title() );

	if( $Blog->get_setting( 'threaded_comments' ) )
	{ // Display a reply comment ID only when this feature is enabled in blog settings
		$Form->text_input( 'in_reply_to_cmt_ID', $edited_Comment->in_reply_to_cmt_ID, 10, T_('In reply to comment ID'), T_('(leave blank for normal comments)') );
	}

	if( $edited_Comment->get_author_User() )
	{
		$Form->info( T_('Author'), $edited_Comment->get_author() );
	}
	else
	{
		$Form->text_input( 'newcomment_author', $edited_Comment->author, 20, T_('Author'), '', array('maxlength'=>100, 'style'=>'width: 100%;' ) );
		$Form->text_input( 'newcomment_author_email', $edited_Comment->author_email, 20, T_('Email'), '', array('maxlength'=>100, 'style'=>'width: 100%;') );
		$Form->text_input( 'newcomment_author_url', $edited_Comment->author_url, 20, T_('Website URL'), '', array('maxlength'=>100, 'style'=>'width: 100%;') );
	}

	echo '<div class="comment_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array(
			'target_type' => 'Comment',
			'edit_layout' => NULL,
			'Comment' => $edited_Comment,
		) );
	echo '</div>';

	$Form->textarea_input( 'content', $comment_content, $display_params['textarea_lines'], $display_params['form_comment_text'], array( 'cols' => 38, 'class' => 'bComment', 'id' => $dummy_fields[ 'content' ] ) );

	// set b2evoCanvas for plugins
	echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "'.$dummy_fields[ 'content' ].'" );</script>';

	if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		$Form->begin_fieldset( '', array( 'id' => 'comment_date_field' ) );
		echo $Form->begin_field( NULL, T_('Comment date') );
		$Form->switch_layout( 'blockspan' );
		$Form->date_input( 'comment_issue_date', $edited_Comment->date, '', array( 'size' => "10" ) );
		$Form->time_input( 'comment_issue_time', $edited_Comment->date, '', array( 'size' => "10" ) );
		$Form->switch_layout( NULL );
		$Form->end_fieldset();
	}

	if( $comment_Item->can_rate() || !empty( $edited_Comment->rating ) )
	{ // Rating is editable
		$edited_Comment->rating_input( array(
				'before' => $Form->begin_field( 'comment_rating_field', T_('Rating') ),
				'after' => $Form->inputend.$Form->fieldend
			) );
	}

	// Get those statuses which are not allowed for the current User to create comments in this blog
	$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_comment!', 'edit' ), array( 'redirected', 'trash' ) );
	// Get allowed visibility statuses
	$sharing_options = get_visibility_statuses( 'radio-options', $exclude_statuses );
	if( count( $sharing_options ) == 1 )
	{ // Only one visibility status is available, don't show radio but set hidden field
		$Form->hidden( 'comment_status', $sharing_options[0][0] );
	}
	else
	{ // Display visibiliy options
		$Form->radio( 'comment_status', $edited_Comment->status, $sharing_options, T_('Visibility'), true );
	}

	// Display renderers checkboxes ( Note: This contains inputs )
	$comment_renderer_checkboxes = $edited_Comment->renderer_checkboxes( NULL, false );
	if( !empty( $comment_renderer_checkboxes ) )
	{
		$Form->info( T_('Text Renderers'), $comment_renderer_checkboxes );
	}

	// Display comment attachments
	$LinkOwner = new LinkComment( $edited_Comment );
	if( $LinkOwner->count_links() )
	{ // there are attachments to display
		if( $current_User->check_perm( 'files', 'view' ) && $current_User->check_perm( 'admin', 'restricted' ) )
		{
			$Form->begin_fieldset( T_('Attachments') );
			display_attachments( $LinkOwner );
			$Form->end_fieldset();
		}
		else
		{
			$Form->info( T_('Attachments'), T_('You do not have permission to edit file attachments for this comment') );
		}
	}

	echo '<div class="center margin2ex">';
	$Form->submit( array( 'actionArray[update]', T_('Save Changes!'), 'SaveButton', '' ) );
	echo '</div>';
$Form->end_form();

?>
<script type="text/javascript">
	function switch_edit_view()
	{
		var form = document.getElementById('comment_edit');
		if( form )
		{
			jQuery(form).append( '<input type="hidden" name="action" value="switch_view" />');
			form.submit();
		}
		return false;
	}
</script>
<?php

?>
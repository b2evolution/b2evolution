<?php
/**
 * This is the template that displays the edit comment form. It gets POSTed to comments ctrl.
 *
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evoskins
 * @subpackage pureforums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $edited_Comment, $comment_Item, $comment_content, $cat;
global $display_params, $admin_url, $dummy_fields;

$form_action = url_add_param( $admin_url, 'ctrl=comments' );

// Default params:
$disp_params = array_merge( array(
		'disp_edit_categories' => true,
		'edit_form_params' => array(
			'formstart'      => '<table class="forums_table topics_table" cellspacing="0" cellpadding="0"><tr class="table_title"><th colspan="2"><div class="form_title">'.T_('Editing reply').'</div></th></tr>',
			'formend'        => '</table>',
			'fieldset_begin' => '<tr><td colspan="2">',
			'fieldset_end'   => '</td></tr>',
			'fieldstart'     => '<tr>',
			'fieldend'       => '</tr>',
			'labelstart'     => '<td><strong>',
			'labelend'       => '</strong></td>',
			'inputstart'     => '<td>',
			'inputend'       => '</td>',
			'infostart'      => '<td>',
			'infoend'        => '</td>',
			'buttonsstart'   => '<tr><td colspan="2">',
			'buttonsend'     => '</td></tr>',
			'output'         => true
		),
		'categories_name'      => T_('Appears in'),
		'category_name'        => T_('Forum'),
		'category_main_title'  => T_('Main forum'),
		'category_extra_title' => T_('Additional forum'),
		'textarea_lines'       => 16,
		'form_comment_text'    => T_('Comment content'),
	), $display_params );

// BREADCRUMBS
$comment_Item = & $edited_Comment->get_Item();
$main_Chapter = & $comment_Item->get_main_Chapter();
$cat = $main_Chapter->ID;
$Skin->display_breadcrumbs( $cat );

$Form = new Form( $form_action, 'comment_edit', 'post' );

$Form->switch_template_parts( $disp_params['edit_form_params'] );

$Form->begin_form( 'inskin', '', $display_params );

	$Form->add_crumb( 'comment' );
	$Form->hidden( 'blog', $Blog->ID );
	$Form->hidden( 'mname', 'collections' );
	$Form->hidden( 'action_type', 'comment' );
	$Form->hidden( 'comment_ID', $edited_Comment->ID );
	$Form->hidden( 'redirect_to', $edited_Comment->get_permanent_url() );

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
		$Form->text_input( 'newcomment_author', $edited_Comment->author, 20, T_('Author'), '', array( 'maxlength' => 100, 'style' => 'width: 99%;' ) );
		$Form->text_input( 'newcomment_author_email', $edited_Comment->author_email, 20, T_('Email'), '', array( 'maxlength' => 255, 'style' => 'width: 99%;' ) );
		$Form->text_input( 'newcomment_author_url', $edited_Comment->author_url, 20, T_('Website URL'), '', array( 'maxlength' => 255, 'style' => 'width: 99%;' ) );
	}

	ob_start();
	echo '<div class="comment_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayCommentToolbar', array( 'Comment' => & $edited_Comment, 'Item' => & $comment_Item ) );
	echo '</div>';
	$plugins_toolbar = ob_get_clean();

	$Form->switch_template_parts( array(
			'inputstart' => '<td class="form_input comment_content">'.$plugins_toolbar,
		) );
	$Form->textarea_input( 'content', $comment_content, $disp_params['textarea_lines'], $disp_params['form_comment_text'], array(
			'cols' => 60 ,
			'style' => 'width:100%',
			'class' => 'autocomplete_usernames',
			'id' => $dummy_fields[ 'content' ]
		) );

	// set b2evoCanvas for plugins
	echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "'.$dummy_fields[ 'content' ].'" );</script>';

	if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		$Form->switch_template_parts( array(
			'fieldstart' => '',
			'fieldend'   => '',
			'labelstart' => '',
			'labelend'   => '',
			'labelempty' => '',
			'inputstart' => '',
			'inputend'   => '',
			'output'     => false
		) );

		$comment_date_input = $Form->date_input( 'comment_issue_date', $edited_Comment->date, '' );
		$comment_date_input .= $Form->time_input( 'comment_issue_time', $edited_Comment->date, '' );

		$Form->switch_template_parts( $disp_params['edit_form_params'] );

		$Form->info( T_('Comment date'), $comment_date_input, '' );
	}

	if( $comment_Item->can_rate() || !empty( $edited_Comment->rating ) )
	{ // Rating is editable
		$edit_form_params = $disp_params['edit_form_params'];
		$before_rating = $edit_form_params['fieldstart'].$edit_form_params['labelstart'].T_('Rating').$edit_form_params['labelend'].$edit_form_params['inputstart'];
		$after_rating = $edit_form_params['inputend'].$edit_form_params['fieldend'];
		$edited_Comment->rating_input( array( 'before' => $before_rating, 'after' => $after_rating ) );
	}

	$comment_Item = & $edited_Comment->get_Item();
	// Comment status cannot be more than post status, restrict it:
	$restrict_max_allowed_status = ( $comment_Item ? $comment_Item->status : '' );

	// Get those statuses which are not allowed for the current User to create comments in this blog
	$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_comment!', 'edit', $edited_Comment->status, $restrict_max_allowed_status ), array( 'redirected', 'trash' ) );
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

	// Display renderers
	$comment_renderer_checkboxes = $edited_Comment->renderer_checkboxes( NULL, false );
	if( !empty( $comment_renderer_checkboxes ) )
	{
		$Form->info( T_('Text Renderers'), $comment_renderer_checkboxes );
	}

	// Display comment attachments
	$LinkOwner = new LinkComment( $edited_Comment );
	if( $LinkOwner->count_links() )
	{ // there are attachments to display
		$Form->switch_template_parts( array(
			'fieldset_begin' => '<tr><td class="form_label" valign="top"><strong>$fieldset_title$:</strong></td><td class="form_input">',
		) );
		$Form->begin_fieldset( T_('Attachments') );
		if( $current_User->check_perm( 'files', 'view' ) )
		{ // User has permission to view files
			display_attachments( $LinkOwner );
		}
		else
		{
			echo T_('You do not have permission to edit file attachments for this comment');
		}
		$Form->end_fieldset();
		$Form->switch_template_parts( $disp_params['edit_form_params'] );
	}

	$Form->begin_fieldset();
	echo '<div class="center">';
	$Form->submit( array( 'actionArray[update]', T_('Save Changes!'), 'SaveButton submit' ) );
	echo '</div>';
	$Form->end_fieldset();
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

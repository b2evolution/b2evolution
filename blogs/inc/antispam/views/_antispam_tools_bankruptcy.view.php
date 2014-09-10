<?php
/**
 * This file implements the UI controller for the antispam bankruptcy tool.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * @version $Id: _antispam_tools_bankruptcy.view.php 2803 2013-01-22 17:01:38Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $blogs_list, $delete_bankruptcy_blogs, $bankruptcy_blogs_IDs, $comment_status;

$Form = new Form();

$Form->add_crumb( 'antispam' );
$Form->hidden_ctrl();
$Form->hidden( 'tab3', 'tools' );
$Form->hidden( 'tool', 'bankruptcy' );

$Form->begin_form( 'fform', T_('Declare comment spam bankruptcy...') );

if( isset( $delete_bankruptcy_blogs ) && $delete_bankruptcy_blogs )
{
	$Form->begin_fieldset( T_('Deleting log') );

	antispam_bankruptcy_delete( $bankruptcy_blogs_IDs, $comment_status );

	$Form->end_fieldset();
}

$visibility_statuses = get_visibility_statuses( '', array() );
$Form->begin_fieldset( T_('Filter comments by status') );
	$Form->select_input_array( 'comment_status', $comment_status, $visibility_statuses, T_('Look at comments with status'), '' );
	$Form->buttons( array( array( 'submit', 'actionArray[bankruptcy_filter]', T_('Filter') ) ) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Select blogs') );

$blogs_list = antispam_bankruptcy_blogs( $comment_status );
if( empty( $blogs_list ) )
{ // No blogs
	echo '<p>'.sprintf( T_('No comments found with status %s...'), $visibility_statuses[ $comment_status ] ).'</p>';
}
else
{ // Print blogs list
	foreach( $blogs_list as $blog )
	{
		echo '<p><input type="checkbox" name="bankruptcy_blogs[]" value="'.$blog->blog_ID.'" id="bankruptcy_blog_'.$blog->blog_ID.'" /> ';
		echo '<label for="bankruptcy_blog_'.$blog->blog_ID.'">'.$blog->blog_name.' ('.sprintf( T_('<b>%s</b> comments with status %s'), $blog->comments_count, $visibility_statuses[ $comment_status ] ).')</label></p>';
	}
}

$Form->end_fieldset();

$buttons = array();
if( !empty( $blogs_list ) )
{
	$buttons[] = array( 'submit', 'actionArray[bankruptcy_delete]', sprintf( T_('Delete ALL comments with status %s from the selected blogs!'), $visibility_statuses[ $comment_status ] ), 'RedButton', 
		"return confirm('".sprintf( TS_('ALL comments with status %s\nincluding NON spam\nwill be deleted from the selected blogs.\nThis cannot be undone!\nAre you sure?') , $visibility_statuses[ $comment_status ] )."')" );
}

$Form->end_form( $buttons );

?>
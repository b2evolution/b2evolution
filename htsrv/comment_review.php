<?php
/**
 * This is file implements the comments quick edit operations after email notification.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'/_main.inc.php';

param( 'cmt_ID', 'integer', '', true );
param( 'secret', 'string', '', true );
param_action();

$to_comment_edit = $admin_url.'?ctrl=comments&action=edit&comment_ID='.$cmt_ID;

if( $action == 'exit' )
{	// Display messages and exit

	// Bootstrap
	require_js( '#bootstrap#', 'rsc_url' );
	require_css( '#bootstrap_css#', 'rsc_url' );

	require_css( 'bootstrap-backoffice-b2evo_base.bmin.css', 'rsc_url' );

	// Set bootstrap classes for messages
	$Messages->set_params( array(
		'class_success'  => 'alert alert-dismissible alert-success fade in',
		'class_warning'  => 'alert alert-dismissible alert-warning fade in',
		'class_error'    => 'alert alert-dismissible alert-danger fade in',
		'class_note'     => 'alert alert-dismissible alert-info fade in',
		'before_message' => '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>',
	) );

	// Send the predefined cookies:
	evo_sendcookies();

	headers_content_mightcache( 'text/html', 0 );  // Do NOT cache!

	?>
	<!DOCTYPE html>
	<html lang="<?php locale_lang() ?>">
	<head>
		<meta name="viewport" content="width = 600" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title><?php echo T_('Comment moderation') ?></title>
		<meta name="ROBOTS" content="NOINDEX" />
		<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
	</head>
	<body style="padding: 10px 20px">
		<div class="container">
			<div class="wrap"<?php echo empty( $wrap_styles ) ? '' : ' style="'.implode( ';', $wrap_styles ).'"';?>>
				<h1><?php echo T_('Comment moderation') ?></h1>
				<?php $Messages->disp(); ?>
				<div class="action_messages">
					<p><a href="<?php echo $admin_url.'?ctrl=dashboard'; ?>" class="btn btn-primary"><?php echo T_('Go to the back-office...'); ?></a></p>
				</div>
			</div>
		</div>
	</body>
	</html>
	<?php

	exit;
}
elseif( $cmt_ID != null )
{
	$posted_Comment = & Comment_get_by_ID( $cmt_ID );
}
else
{
	$Messages->add( 'Requested comment does not exist!' );
	header_redirect( regenerate_url('action', 'action=exit', '', '&') );
}

$comment_Item = & $posted_Comment->get_Item();
$comment_Blog = $comment_Item->get_Blog();
if( $comment_Blog->get_setting( 'comment_quick_moderation' ) == 'never' )
{	// comment quick moderation setting was set to 'never' after this comment quick moderation link was created
	// don't allow quick moderation
	$Messages->add( T_('Quick moderation not available.') );
}

// Check the secret parameter (This doubles as a CRUMB)
if( ( $secret != $posted_Comment->get('secret') ) || empty( $secret ) )
{	// Invalid secret, no moderation allowed here, go to regular form with regular login requirements:
	$Messages->add( T_('Invalid secret key. Quick moderation not available.') );
}

if( $posted_Comment->status == 'trash' )
{	// Comment is already in trash
	$Messages->add( T_('The comment was already deleted. Quick moderation not available.') );
}

if( $Messages->has_errors() )
{	// quick moderation is not available, redirect to normal edit form
	header_redirect( $to_comment_edit );
}

$antispam_url = $admin_url.'?ctrl=antispam&action=ban&keyword='.rawurlencode( get_ban_domain( $posted_Comment->author_url ) ).'&'.url_crumb( 'antispam' );

// perform action if action is not null
switch( $action )
{
	case 'publish':
		// Open comment to the highest status:

		if( ! is_logged_in() )
		{	// Don't allow this action for not logged in user:
			$Messages->add( T_('Log in for more quick moderation actions.'), 'error' );

			header_redirect( get_login_url( 'quick comment moderation', NULL, false, $comment_Blog->ID ) );
			/* exited */
		}

		// We try to set a comment status to max allowed as "Public",
		// but really it can be reduced to lower in Comment->dbupdate()
		// depending on current User's permissions and parent Item status:
		$posted_Comment->set( 'status', 'published' );

		// Comment moderation is done, handle moderation "secret"
		$posted_Comment->handle_qm_secret();

		$posted_Comment->dbupdate();	// Commit update to the DB

		$posted_Comment->handle_notifications();

		$Messages->add( T_('Comment status has been updated.'), 'success' );

		header_redirect( regenerate_url('action', 'action=exit', '', '&') );
		/* exited */
		break;

	case 'deprecate':
		// Deprecate comment:

		$posted_Comment->set( 'status', 'deprecated' );

		// Comment moderation is done, handle moderation "secret"
		$posted_Comment->handle_qm_secret();

		$posted_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment has been deprecated.'), 'success' );

		header_redirect( regenerate_url('action', 'action=exit', '', '&') );
		/* exited */
		break;

	case 'delete':
		// Delete from DB:
		$posted_Comment->dbdelete( true );

		$Messages->add( T_('Comment has been deleted.'), 'success' );

		header_redirect( regenerate_url('action', 'action=exit', '', '&') );
		break;

	case 'recycle':
		// Recycle comment:
		$posted_Comment->dbdelete();

		$Messages->add( T_('Comment has been recycled.'), 'success' );

		header_redirect( regenerate_url('action', 'action=exit', '', '&') );
		break;

	case 'deleteurl':
		// Delete author url:
		$posted_Comment->set( 'author_url', null );

		$posted_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment url has been deleted.'), 'success' );

		// redirect to this page, without action param!!!
		header_redirect( regenerate_url( 'action', array ( 'cmt_ID='.$cmt_ID, 'secret='.$secret ), '', '&' ) );
		break;

	case 'antispamtool':
		// Redirect to the Antispam ban screen

		header_redirect( $antispam_url );
		/* exited */
		break;
}

// No action => display the form
if( empty( $params ) )
{
	$params = array();
}

// Default params:
$params = array_merge( array(
	'wrap_width'         => '580px',
	'review_page_before' => '<div class="evo_panel__comment_review col-md-8 col-md-offset-2">',
	'review_page_after'  => '</div>',
	'review_form_title'  => T_('Comment review'),
	'review_form_before' => '',
	'review_form_after'  => '',
	'form_class_review'  => 'evo_form__comment_review',
), $params );

// Header
$page_title = $params['review_form_title'];

require $adminskins_path.'/login/_html_header.inc.php';

echo $params['review_page_before'];

echo $params['review_form_before'];

$Form = new Form( get_htsrv_url().'comment_review.php', 'review', 'post' );

// Display unsubscribe form
$Form->begin_form( $params['form_class_review'] );

$is_meta = $posted_Comment->is_meta();
echo '<div class="panel panel-default evo_content_block">';
	echo '<div class="panel-heading">';
		echo '<h3 class="panel-title">'.T_('Posted comment').'</h3>';
	echo '</div>';
	echo '<div class="panel-body">';

		echo $posted_Comment->get_author( array(
				'before'      => '<div class="comment_avatar">',
				'after'       => '</div>',
				'before_user' => '<div class="comment_avatar">',
				'after_user'  => '</div>',
				'link_text'   => 'only_avatar',
				'link_class'  => 'user',
				'thumb_size'  => 'crop-top-80x80',
				'thumb_class' => 'user',
		) );

		echo '<h3 class="comment_title">';
		if( ! $is_meta && ( $posted_Comment->status !== 'draft' || $posted_Comment->author_user_ID == $current_User->ID ) )
		{	// Display Comment permalink icon
			echo $posted_Comment->get_permanent_link( '#icon#' ).' ';
		}
		echo $posted_Comment->get_title( array(
			'author_format' => '<strong>%s</strong>',
			'link_text'     => 'auto',
			'linked_type'   => $is_meta,
		) );
		$comment_Item = & $posted_Comment->get_Item();
		echo ' '.T_('in response to')
			.' <a href="'.$admin_url.'?ctrl=items&amp;blog='.$comment_Item->get_blog_ID().'&amp;p='.$comment_Item->ID.'"><strong>'.$comment_Item->dget('title').'</strong></a>';

		echo '</h3>';

		echo '<div class="notes">';
			$posted_Comment->rating( array(
				'before'      => '<div class="comment_rating">',
				'after'       => '</div> &bull; ',
			) );
			$posted_Comment->date();
			if( is_logged_in() )
			{
				$posted_Comment->author_url_with_actions( '', true );
			}
			else
			{
				$posted_Comment->author_url( '', ' &bull; '.T_('Url').': ', '' );
			}
			$posted_Comment->author_email( '', ' &bull; '.T_('Email').': <span class="bEmail">', '</span> &bull; ' );
			$posted_Comment->author_ip( 'IP: <span class="bIP">', '</span> ', 'antispam' );
			$posted_Comment->ip_country();
			$posted_Comment->spam_karma( ' &bull; '.T_('Spam Karma').': %s%', ' &bull; '.T_('No Spam Karma') );
		echo '</div>';

		$user_permission = is_logged_in() && ( $current_User->check_perm( 'meta_comment', 'edit', false, $posted_Comment ) );
		if( $user_permission )
		{	// Put the internal comment content into this container to edit by ajax:
			echo '<div id="editable_comment_'.$posted_Comment->ID.'" class="editable_comment_content">';
		}
		$posted_Comment->content();
		if( $user_permission )
		{	// End of the container that is used to edit internal comment by ajax:
			echo '</div>';
		}
	echo '</div>';
echo '</div>';

if( ( $secret == $posted_Comment->get( 'secret' ) ) && ( $secret != NULL ) )
{
	if( is_logged_in() )
	{
		$status_order = get_visibility_statuses( 'ordered-array' );
		$status_index = get_visibility_statuses( 'ordered-index', array( 'redirected' ) );

		// delete button
		if( $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $posted_Comment ) )
		{
			echo '<button type="submit" name="actionArray[delete]" class="btn btn-danger" title="'.T_('Delete this comment').'"/>';
			echo get_icon( 'delete' ).' '.T_('Delete');
			echo '</button>';
			echo "\n";
		}

		// recycle button
		if( ( $posted_Comment->get( 'status' ) != 'trash' ) && $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $posted_Comment ) )
		{
			echo '<button type="submit" name="actionArray[recycle]" class="btn btn-warning" title="'.T_('Recycle this comment').'"/>';
			echo get_icon( 'recycle' ). ' '.T_('Recycle');
			echo '</button>';
			echo "\n";
		}

		// deprecate button
		if( $current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $posted_Comment ) )
		{
			if( $posted_Comment->status != 'deprecated' )
			{
				$status_icon_color = $status_order[ $status_index[ 'deprecated' ] ][3];
				echo '<button type="submit" name="actionArray[deprecate]" class="btn btn-default" title="'.T_('Deprecate this comment').'"/>';
				echo get_icon( 'move_down_'.$status_icon_color ).' '.T_('Deprecate');
				echo '</button>';
				echo "\n";
			}

			// Try to display a button to open comment to higher status:
			if( $posted_Comment->status != 'published' )
			{	// Check what max comment status is allowed for current User:
				$max_allowed_comment_status = $posted_Comment->get_allowed_status( 'published' );

				if( $max_allowed_comment_status != $posted_Comment->get( 'status' ) )
				{	// If current comment status is not max allowed status yet:
					$status_button_titles = get_visibility_statuses( 'ordered-array' );
					foreach( $status_button_titles as $status_button_title )
					{	// Find button status title by status key:
						if( $status_button_title[0] == $max_allowed_comment_status )
						{
							$max_allowed_comment_status_title = $status_button_title[1];
							$status_icon_color = $status_order[ $status_index[ $status_button_title[0] ] ][3];
							break;
						}
					}
					if( ! empty( $max_allowed_comment_status_title ) )
					{	// Display the button only if next/higher status is used as moderation status:
						echo '<button type="submit" name="actionArray[publish]" class="btn btn-default">';
						echo get_icon( 'move_up_'.$status_icon_color ).' '.$max_allowed_comment_status_title;
						echo '</button>';
						echo "\n";
					}
				}
			}
		}

		if( $posted_Comment->author_url != null )
		{
			if( $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $posted_Comment ) )
			{	// delete url button
				echo '<button type="submit" name="actionArray[deleteurl]" class="btn btn-danger" title="'.T_('Delete comment URL').'" />';
				echo get_icon( 'delete' ).' '.T_('Delete URL');
				echo '</button>';
				echo "\n";
			}

			if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
			{	// antispam tool button
				echo '<button type="submit" name="actionArray[antispamtool]" class="btn btn-default" title="'.T_('Antispam tool').'" />';
				echo get_icon( 'lightning' ).' '.T_('Antispam tool');
				echo '</button>';
				echo "\n";
			}
		}

		echo '<input type="hidden" name="secret" value="'.$secret.'" />';
		echo "\n";
		echo '<input type="hidden" name="cmt_ID" value="'.$cmt_ID.'" />';
		echo "\n";
	}
	else
	{	// Display this message because the button above to open a comment to higher status is available only for logged in users:
		echo '<p>'.sprintf( T_('<a %s>Log in</a> for more actions.'), 'href="'.get_login_url( 'quick comment moderation', NULL, false, $comment_Blog->ID ).'"' ).'</p>';
	}
}
else
{
	die( T_('Invalid link!') );
}

$Form->end_form();

echo $params['review_form_after'];

echo $params['review_page_after'];

// Footer
require $adminskins_path.'/login/_html_footer.inc.php';
?>
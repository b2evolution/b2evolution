<?php
/**
 * This is file implements the comments quick edit operations after email notification.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'/_main.inc.php';

param('cmt_ID', 'integer', '' );
param('secret', 'string', '' );
param_action();

$to_comment_edit = $admin_url.'?ctrl=comments&action=edit&comment_ID='.$cmt_ID;

if( $action == 'exit' )
{	// Display messages and exit

	// Send the predefined cookies:
	evo_sendcookies();

	headers_content_mightcache( 'text/html', 0 );  // Do NOT cache!

	require_css( 'basic.css', 'rsc_url' ); // Basic styles
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
	<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title><?php echo T_('Comment moderation') ?></title>
		<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
	</head>
	<body style="padding: 10px 20px">
		<h1><?php echo T_('Comment moderation') ?></h1>
		<?php $Messages->disp(); ?>
		<div class="action_messages">
			<p><a href="<?php echo $admin_url.'?ctrl=dashboard'; ?>"><?php echo T_('Go to the back-office...'); ?></a></p>
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
{ // comment quick moderation setting was set to 'never' after this comment quick moderation link was created
	// don't allow quick moderation
	$Messages->add( T_('Quick moderation not available.') );
}

// Check the secret parameter (This doubles as a CRUMB)
if( ( $secret != $posted_Comment->get('secret') ) || empty( $secret ) )
{ // Invalid secret, no moderation allowed here, go to regular form with regular login requirements:
	$Messages->add( T_('Invalid secret key. Quick moderation not available.') );
}

if( $posted_Comment->status == 'trash' )
{ // Comment is already in trash
	$Messages->add( T_('The comment was already deleted. Quick moderation not available.') );
}

if( $Messages->has_errors() )
{ // quick moderation is not available, redirect to normal edit form
	header_redirect( $to_comment_edit );
}

$antispam_url = $admin_url.'?ctrl=antispam&action=ban&keyword='.rawurlencode(get_ban_domain($posted_Comment->author_url)).'&'.url_crumb( 'antispam' );

// perform action if action is not null
switch( $action )
{
	case 'publish':
		$posted_Comment->set('status', 'published' );
		// Comment moderation is done, handle moderation "secret"
		$posted_Comment->handle_qm_secret();

		$posted_Comment->dbupdate();	// Commit update to the DB

		$posted_Comment->handle_notifications();

		$Messages->add( T_('Comment has been published.'), 'success' );

		header_redirect( regenerate_url('action', 'action=exit', '', '&') );
		/* exited */
		break;

	case 'deprecate':
		$posted_Comment->set('status', 'deprecated' );
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

// Send the predefined cookies:
evo_sendcookies();

headers_content_mightcache( 'text/html', 0 );  // Do NOT cache!

$b2evo_icons_type = 'fontawesome';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title><?php echo ' '.T_('Comment review').' '; ?></title>
	<link type="text/css" rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css?v=<?php echo $app_version_long; ?>">
</head>

<body>

<form method="post" name="review">

<?php

if ($secret == $posted_Comment->get('secret') && ($secret != NULL) )
{
	// delete button
	echo '<input type="submit" name="actionArray[delete]"';
	echo ' value="'.T_('Delete').'" title="'.T_('Delete this comment').'"/>';
	echo "\n";

	// recycle button
	echo '<input type="submit" name="actionArray[recycle]"';
	echo ' value="'.T_('Recycle').'" title="'.T_('Recycle this comment').'"/>';
	echo "\n";

	// deprecate button
	if( $posted_Comment->status != 'deprecated' )
	{
		echo '<input type="submit" name="actionArray[deprecate]"';
		echo ' value="'.T_('Deprecate').'" title="'.T_('Deprecate this comment').'"/>';
		echo "\n";
	}

	// publish button
	if( $posted_Comment->status != 'published' )
	{
		echo '<input type="submit" name="actionArray[publish]"';
		echo ' value="'.T_('Publish').'" title="'.T_('Publish this comment').'"/>';
		echo "\n";
	}

	if( $posted_Comment->author_url != null )
	{
		// delete url button
		echo '<input type="submit" name="actionArray[deleteurl]"';
		echo ' value="'.T_('Delete URL').'" title="'.T_('Delete comment URL').'" />';
		echo "\n";

		// antispam tool button
		echo '<input type="submit" name="actionArray[antispamtool]"';
		echo ' value="'.T_('Antispam tool').'" title="'.T_('Antispam tool').'" />';
		echo "\n";
	}

	echo '<input type="hidden" name="secret" value="'.$secret.'" />';
	echo "\n";
	echo '<input type="hidden" name="cmt_ID" value="'.$cmt_ID.'" />';
	echo "\n";
}
else
{
	die( T_('Invalid link!') );
}

?>
<fieldset>
<legend><?php echo T_('Posted comment')?></legend>
<div class="bComment">
	<div class="bSmallHead">
		<span class="bDate"><?php $posted_Comment->date(); ?></span>
		@
		<span class="bTime"><?php $posted_Comment->time( '#short_time' ); ?></span>
		<?php
				$posted_Comment->author_url( '', ' &middot; Url: <span class="bUrl">', '</span>' );
				if( $posted_Comment->author_url != null )
				{
					echo ' '.action_icon( T_('Delete comment URL'), 'remove', regenerate_url( '', array( 'action=deleteurl', 'cmt_ID='.$cmt_ID, 'secret='.$secret ) ) ).' ';
					echo ' '.action_icon( T_('Antispam tool'), 'lightning', $antispam_url );
				}
				$posted_Comment->author_email( '', ' &middot; Email: <span class="bEmail">', '</span>' );
				$posted_Comment->author_ip( ' &middot; IP: <span class="bIP">', '</span>', 'antispam' );
				echo ' &middot; <span class="bKarma">';
				$posted_Comment->spam_karma( T_('Spam Karma').': %s%', T_('No Spam Karma') );
				echo '</span>';
			 ?>
	</div>
	<div class="bTitle">
		<?php echo $posted_Comment->get_title(); ?>
	</div>
	<?php $posted_Comment->rating(); ?>
	<?php $posted_Comment->avatar(); ?>
	<fieldset class="bCommentText">
		<legend><?php echo T_('Content')?></legend>
		<?php $posted_Comment->content() ?>
	</fieldset>
</div>
</fieldset>

</form>

</body>
</html>
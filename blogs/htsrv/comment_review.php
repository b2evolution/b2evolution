<?php
/**
 * This is file implements the comments quick edit operations after mail notification.
 */

/**
 * Initialize everything:
 */
require_once dirname(dirname(__FILE__)).'/conf/_config.php';

require_once dirname(dirname(__FILE__)).'/inc/_main.inc.php';

$cmt_ID = param('cmt_ID', 'integer', '' );
$secret = param('secret', 'string', '' );
$action = param('action', 'string', '' );
$redirect_to = $admin_url.'?ctrl=dashboard';

if( $cmt_ID != null )
{
	$posted_Comment = Comment_get_by_ID( $cmt_ID );
}
else
{
	$Messages->add( 'Requested comment does not exist!' );
	header_redirect( $redirect_to );
}

// fp>asimo TODO: Have a check for the secret here. In all cases where the secret is invalid, redirect to the normal comment
// edit form (which requires to be logged in.)
// Also, please delete the secret in Comment:dbupdate if the status is no longer draft.


// perform action if action is not null
switch( $action )
{
	case 'publish':
		// Check the secret paramater (This doubles as a CRUMB)
		if( $secret == $posted_Comment->get('secret') )
		{
			$posted_Comment->set('status', 'published' );

			$posted_Comment->dbupdate();	// Commit update to the DB

			$Messages->add( T_('Comment has been published.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Comment can not be published, invalid call!'), 'error' );
		}

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'deprecate':
		// Check the secret paramater (This doubles as a CRUMB)
		if( $secret == $posted_Comment->get('secret') )
		{
			$posted_Comment->set('status', 'deprecated' );

			$posted_Comment->dbupdate();	// Commit update to the DB

			$Messages->add( T_('Comment has been deprecated.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Comment can not be deprecated, invalid call!'), 'error' );
		}

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'delete':
		// Check the secret paramater (This doubles as a CRUMB)
		if( $secret == $posted_Comment->get('secret') )
		{
			// Delete from DB:
			$posted_Comment->dbdelete();

			$Messages->add( T_('Comment has been deleted.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Can not delete the comment, invalid call!'), 'error' );
		}

		header_redirect( $redirect_to );
		break;
}

// No action => display the form
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php echo ' '.T_('Comment review').' '; ?></title>
</head>

<body>

<form method="post" name="review" onSubmit="return OnSubmitForm()">

<?php

if ($secret == $posted_Comment->get('secret') && ($secret != NULL) )
{
	// delete button
	echo '<input type="submit" name="delete"';
	echo ' value="'.T_('Delete').'" title="'.T_('Delete this comment').'"';
	echo ' onClick="document.pressed=this.name"/>';
// fp>asimo: TODO: this screen needs to work 100% without Javascript. Please use action[] names and param_action() for buttons.
// Use hidden form fields for $secret and $cmt_ID
	echo "\n";

	// deprecate button
	if( $posted_Comment->status != 'deprecated')
	{
		echo '<input type="submit" name="deprecate"';
		echo ' value="'.T_('Deprecate').'" title="'.T_('Deprecate this comment').'"';
		echo ' onClick="document.pressed=this.name"/>';
		echo "\n";
	}

	// publish button
	if( $posted_Comment->status != 'published' )
	{
		echo '<input type="submit" name="publish"';
		echo ' value="'.T_('Publish').'" title="'.T_('Publish this comment').'"';
		echo ' onClick="document.pressed=this.name"/>';
		echo "\n";
	}
}
else
{
	die( T_('Invalid link!') );
}

?>
<fieldset>
<legend><?php echo T_('Posted comment')?></legend>
<div class=bComment>
	<div class="bSmallHead">
		<span class="bDate"><?php $posted_Comment->date(); ?></span>
		@
		<span class="bTime"><?php $posted_Comment->time( 'H:i' ); ?></span>
		<?php
				$posted_Comment->author_url( '', ' &middot; Url: <span class="bUrl">', '</span>' );
				$posted_Comment->author_email( '', ' &middot; Email: <span class="bEmail">', '</span>' );
				$posted_Comment->author_ip( ' &middot; IP: <span class="bIP">', '</span>' );
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


<script language="JavaScript">
function OnSubmitForm()
{
	if(document.pressed == 'deprecate')
	{
		document.review.action ='comment_review.php?action=deprecate&secret=<?php echo $secret;?>&cmt_ID=<?php echo $cmt_ID;?>';
  	}
	else
	if(document.pressed == 'publish')
	{
		document.review.action ='comment_review.php?action=publish&secret=<?php echo $secret;?>&cmt_ID=<?php echo $cmt_ID;?>';
	}
	else
	if(document.pressed == 'delete')
	{
		document.review.action ='comment_review.php?action=delete&secret=<?php echo $secret;?>&cmt_ID=<?php echo $cmt_ID;?>';
	}
	return true;
}
</script>

</body>
</html>
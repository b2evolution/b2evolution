<?php
/**
 * This is the template that displays the comment form for a post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage basic
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $cookie_name, $cookie_email, $cookie_url;
global $comment_allowed_tags;
global $comment_cookies, $comment_allow_msgform, $dummy_fields;

$comment_reply_ID = param( 'reply_ID', 'integer', 0 );

?>
<h4><?php echo T_('Leave a comment') ?>:</h4>

<?php
	if( ( $Comment = get_comment_from_session() ) == NULL )
	{
		$comment_author = param_cookie( $cookie_name, 'string', '' );
		$comment_author_email = utf8_strtolower( param_cookie( $cookie_email, 'string', '' ) );
		$comment_author_url = param_cookie( $cookie_url, 'string', '' );
		$comment_text = '';
	}
	else
	{
		$comment_author = $Comment->author;
		$comment_author_email = $Comment->author_email;
		$comment_author_url = $Comment->author_url;
		$comment_text = $Comment->content;
	}
	$redirect = htmlspecialchars( url_rel_to_same_host( regenerate_url( '', '', '', '&' ), get_htsrv_url() ) );
?>

<!-- form to add a comment -->
<form action="<?php echo get_htsrv_url() ?>comment_post.php" method="post" id="bComment_form_id_<?php echo $Item->ID ?>">

	<input type="hidden" name="comment_item_ID" value="<?php echo $Item->ID() ?>" />
	<input type="hidden" name="redirect_to" value="<?php echo $Item->get_feedback_url( $disp == 'feedback-popup', '&' ) ?>" />
	<input type="hidden" name="crumb_comment" value="<?php echo get_crumb( 'comment' ) ?>" />
	<?php
		if( !empty( $comment_reply_ID ) )
		{
	?>
	<input type="hidden" name="reply_ID" value="<?php echo $comment_reply_ID ?>" />
	<a href="<?php echo url_add_param( $Item->get_permanent_url(), 'reply_ID='.$comment_reply_ID.'&amp;redir=no' ).'#c'.$comment_reply_ID ?>"><?php echo T_('You are currently replying to a specific comment') ?></a>
	<?php } ?>
<table>
	<?php
	if( is_logged_in() )
	{ // User is logged in:
		?>
		<tr valign="top" bgcolor="#eeeeee">
			<td align="right"><strong><?php echo T_('User') ?>:</strong></td>
			<td align="left">
				<strong><?php echo $current_User->get_identity_link( array( 'link_text' => 'auto' ) )?></strong>
				<?php user_profile_link( ' [', ']', T_('Edit profile') ) ?>
				</td>
		</tr>
		<?php
	}
	else
	{ // User is not logged in:
		?>
		<tr valign="top" bgcolor="#eeeeee">
			<td align="right"><label for="author"><strong><?php echo T_('Name') ?>:</strong></label></td>
			<td align="left"><input type="text" name="<?php echo $dummy_fields[ 'name' ] ?>" id="author" value="<?php echo htmlspecialchars( $comment_author ) ?>" size="40" maxlength="100" tabindex="1" /></td>
		</tr>

		<tr valign="top" bgcolor="#eeeeee">
			<td align="right"><label for="email"><strong><?php echo T_('Email') ?>:</strong></label></td>
			<td align="left"><input type="text" name="<?php echo $dummy_fields[ 'email' ] ?>" id="email" value="<?php echo htmlspecialchars( $comment_author_email ) ?>" size="40" maxlength="255" tabindex="2" /><br />
				<small><?php echo T_('Your email address will <strong>not</strong> be displayed on this site.') ?></small>
			</td>
		</tr>

		<?php
		$Item->load_Blog();
		if( $Item->Blog->get_setting( 'allow_anon_url' ) )
		{
		?>
			<tr valign="top" bgcolor="#eeeeee">
				<td align="right"><label for="url"><strong><?php echo T_('Site/Url') ?>:</strong></label></td>
				<td align="left"><input type="text" name="<?php echo $dummy_fields[ 'url' ] ?>" id="url" value="<?php echo htmlspecialchars( $comment_author_url ) ?>" size="40" maxlength="255" tabindex="3" /><br />
					<small><?php echo T_('Your URL will be displayed.') ?></small>
				</td>
			</tr>
		<?php
		}
	}
	?>

	<tr valign="top" bgcolor="#eeeeee">
		<td align="right"><label for="comment"><strong><?php echo T_('Comment text') ?>:</strong></label></td>
		<td align="left" width="450"><textarea cols="50" rows="12" name="<?php echo $dummy_fields[ 'content' ] ?>" id="comment" tabindex="4"><?php echo $comment_text ?></textarea><br />
			<small><?php echo T_('Allowed XHTML tags'), ': ', htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)) ?></small>
		</td>
	</tr>

	<?php if( ! is_logged_in() ) { ?>
	<tr valign="top" bgcolor="#eeeeee">
		<td align="right"><strong><?php echo T_('Options') ?>:</strong></td>
		<td align="left">
		<?php if( ! is_logged_in() )
		{ // User is not logged in:
			?>
			<input type="checkbox" name="comment_cookies" value="1" checked="checked" tabindex="7" id="comment_cookies" /> <label for="comment_cookies"><strong><?php echo T_('Remember me') ?></strong></label> <small><?php echo T_('(Set cookies for name, email &amp; url)') ?></small>
			<?php
		} ?>
		</td>
	</tr>
	<?php } ?>

	<tr valign="top" bgcolor="#eeeeee">
		<td colspan="2" align="center">
			<input type="submit" name="submit" value="<?php echo T_('Send comment') ?>" tabindex="8" />
		</td>
	</tr>
</table>

</form>
<?php
	echo_comment_reply_js( $Item );
?>
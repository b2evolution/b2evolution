<?php
/**
 * This is the template that displays the comment form for a post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage basic
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $cookie_name, $cookie_email, $cookie_url;
global $comment_allowed_tags, $comments_use_autobr;
global $comment_cookies, $comment_allow_msgform;

?>
<h4><?php echo T_('Leave a comment') ?>:</h4>

<?php
	$comment_author = isset($_COOKIE[$cookie_name]) ? trim($_COOKIE[$cookie_name]) : '';
	$comment_author_email = isset($_COOKIE[$cookie_email]) ? trim($_COOKIE[$cookie_email]) : '';
	$comment_author_url = isset($_COOKIE[$cookie_url]) ? trim($_COOKIE[$cookie_url]) : '';
	$redirect = htmlspecialchars(url_rel_to_same_host(regenerate_url('','','','&'), $htsrv_url));
?>

<!-- form to add a comment -->
<form action="<?php echo $htsrv_url ?>comment_post.php" method="post">

	<input type="hidden" name="comment_post_ID" value="<?php echo $Item->ID() ?>" />
	<input type="hidden" name="redirect_to" value="<?php echo $Item->get_feedback_url( $disp == 'feedback-popup', '&' ) ?>" />
	<input type="hidden" name="crumb_comment" value="<?php echo get_crumb( 'comment' ) ?>" />

<table>
	<?php
	if( is_logged_in() )
	{ // User is logged in:
		?>
		<tr valign="top" bgcolor="#eeeeee">
			<td align="right"><strong><?php echo T_('User') ?>:</strong></td>
			<td align="left">
				<strong><?php echo $current_User->colored_name()?></strong>
				<?php user_profile_link( ' [', ']', T_('Edit profile') ) ?>
				</td>
		</tr>
		<?php
	}
	else
	{ // User is not loggued in:
		?>
		<tr valign="top" bgcolor="#eeeeee">
			<td align="right"><label for="author"><strong><?php echo T_('Name') ?>:</strong></label></td>
			<td align="left"><input type="text" name="u" id="author" value="<?php echo $comment_author ?>" size="40" tabindex="1" /></td>
		</tr>

		<tr valign="top" bgcolor="#eeeeee">
			<td align="right"><label for="email"><strong><?php echo T_('Email') ?>:</strong></label></td>
			<td align="left"><input type="text" name="i" id="email" value="<?php echo $comment_author_email ?>" size="40" tabindex="2" /><br />
				<small><?php echo T_('Your email address will <strong>not</strong> be displayed on this site.') ?></small>
			</td>
		</tr>

		<tr valign="top" bgcolor="#eeeeee">
			<td align="right"><label for="url"><strong><?php echo T_('Site/Url') ?>:</strong></label></td>
			<td align="left"><input type="text" name="o" id="url" value="<?php echo $comment_author_url ?>" size="40" tabindex="3" /><br />
				<small><?php echo T_('Your URL will be displayed.') ?></small>
			</td>
		</tr>
		<?php
		}
	?>

	<tr valign="top" bgcolor="#eeeeee">
		<td align="right"><label for="comment"><strong><?php echo T_('Comment text') ?>:</strong></label></td>
		<td align="left" width="450"><textarea cols="50" rows="12" name="p" id="comment" tabindex="4"></textarea><br />
			<small><?php echo T_('Allowed XHTML tags'), ': ', htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)) ?></small>
		</td>
	</tr>

	<tr valign="top" bgcolor="#eeeeee">
		<td align="right"><strong><?php echo T_('Options') ?>:</strong></td>
		<td align="left">

		<?php if(substr($comments_use_autobr,0,4) == 'opt-') { ?>
			<input type="checkbox" name="comment_autobr" value="1" <?php if($comments_use_autobr == 'opt-out') echo ' checked="checked"' ?> tabindex="6" id="comment_autobr" /> <label for="comment_autobr"><strong><?php echo T_('Auto-BR') ?></strong></label> <small>(<?php echo T_('Line breaks become &lt;br /&gt;') ?>)</small><br />
		<?php }
		if( ! is_logged_in() )
		{ // User is not logged in:
			?>
			<input type="checkbox" name="comment_cookies" value="1" checked="checked" tabindex="7" id="comment_cookies" /> <label for="comment_cookies"><strong><?php echo T_('Remember me') ?></strong></label> <small><?php echo T_('(Set cookies for name, email &amp; url)') ?></small>
			<?php
		} ?>
		</td>
	</tr>

	<tr valign="top" bgcolor="#eeeeee">
		<td colspan="2" align="center">
			<input type="submit" name="submit" value="<?php echo T_('Send comment') ?>" tabindex="8" />
		</td>
	</tr>
</table>

</form>

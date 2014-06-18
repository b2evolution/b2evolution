<?php
/**
 * This is the BODY footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage glossyblue
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>

  <div id="footer">
	<div id="inner_footer">
	<?php
		// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
		// Display container contents:
		skin_container( NT_('Sidebar 2'), array(
				// The following (optional) params will be used as defaults for widgets included in this container:
				// This will enclose each widget in a block:
				'block_start' => '<div class="$wi_class$ footer_widget">',
				'block_end' => '</div>',
				// This will enclose the title of each widget:
				'block_title_start' => '<h4>',
				'block_title_end' => '</h4>',
				'block_display_title' => true,
				// If a widget displays a list, this will enclose that list:
				'list_start' => '<ul>',
				'list_end' => '</ul>',
				// This will enclose each item in a list:
				'item_start' => '<li>',
				'item_end' => '</li>',
				// This will enclose sub-lists in a list:
				'group_start' => '<ul>',
				'group_end' => '</ul>',
				// This will enclose (foot)notes:
				'notes_start' => '<div class="notes">',
				'notes_end' => '</div>',
				// Search block:
				'disp_search_options' => false,
			) );
		// ----------------------------- END OF "Sidebar" CONTAINER -----------------------------
	?>
	<div class="clear"><?php echo get_icon( 'pixel' ); ?></div>
	</div>
  </div><!--/footer -->
</div><!--/page -->

<!--credits start -->
<div id="credits">
	<div class="alignleft">
		<?php
		// Display a link to contact the owner of this blog (if owner accepts messages):
		$Blog->contact_link( array(
				'before'      => '',
				'after'       => ' ',
				'text'   => T_('Contact'),
				'title'  => T_('Send a message to the owner of this blog...'),
			) );
		// Display a link to help page:
		$Blog->help_link( array(
				'before'      => '/ ',
				'after'       => '.',
				'text'        => T_('Help'),
			) );
		?>

		<?php
		// Display footer text (text can be edited in Blog Settings):
		$Blog->footer_text( array(
				'before'      => '',
				'after'       => '. ',
			) );
		?>

 		<?php
		// Display additional credits:
		// If you can add your own credits without removing the defaults, you'll be very cool :))
		// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
		credits( array(
				'list_start'  => ' ',
				'list_end'    => '.<br />',
				'separator'   => ' / ',
				'item_start'  => '',
				'item_end'    => '',
			) );
		?>
		Design &amp; icons by <a href="http://www.ndesign-studio.com">N.Design Studio</a>. Skin by <a href="http://www.tenderfeelings.be">Tender Feelings</a> / <?php display_param_link( $skinfaktory_links ) ?>.
	</div>
	<div class="alignright">
		<?php
		if( $Blog->get_setting( 'feed_content' ) != 'none' )
		{ 
		?>
			<a href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" class="rss">Entries RSS</a>
			<a href="<?php $Blog->disp( 'comments_rss2_url', 'raw' ) ?>" class="rss">Comments RSS</a>
		<?php
		}
		?>
		<span class="loginout"><?php user_login_link(); user_logout_link();?></span>
	</div>
	<div class="clear"></div>
</div>
<!--credits end -->
<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------
?>
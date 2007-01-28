<?php
/**
 * This is the footer include template.
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage natural_pink
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>

<div id="pageFooter">
	<p class="baseline">
		Original <a href="http://b2evolution.net/">b2evolution</a> template design by <a href="http://severinelandrieu.com/">S&eacute;verine LANDRIEU</a> &amp; <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a>.
	</p>
  <p class="baseline">
		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => '',
					'after'       => ' &bull; ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
		?>

    Credits: <a href="http://skinfaktory.com/">skin makers</a>
	<?php
		// Display additional credits (see /conf/_advanced.php):
 		// If you can add your own credits without removing the defaults, you'll be very cool :))
		// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
		display_list( $credit_links, '|', '', '|', ' ', ' ' );
  ?>
  </p>
</div>

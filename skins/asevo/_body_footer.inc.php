<?php
/**
 * This is the footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage natural_pink
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>

<!-- =================================== START OF FOOTER =================================== -->
<div class="foot_zone">
<div class="inner_wrapper">
	<?php
		// Display footer text (text can be edited in Blog Settings):
		$Blog->footer_text( array(
				'before'      => '<p class="baseline">',
				'after'       => '</p>',
			) );
	?>

	<p class="baseline2">
		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => '',
					'after'       => ' | ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
			// Display a link to help page:
			$Blog->help_link( array(
					'before'      => ' ',
					'after'       => ' | ',
					'text'        => T_('Help'),
				) );
		?>

		<?php display_param_link( $skin_links ) ?> by <a href="http://asevo.com/">Asevo</a>

		<?php
			// Display additional credits:
			// If you can add your own credits without removing the defaults, you'll be very cool :))
			// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			credits( array(
					'list_start'  => '|',
					'list_end'    => ' ',
					'separator'   => '|',
					'item_start'  => ' ',
					'item_end'    => ' ',
				) );
		?>
  </p>
</div>
</div>

<?php
/**
 * This is the footer include template.
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage custom
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>

<div id="pageFooter">
	<?php
		// Display container and contents:
		$Skin->container( NT_("Footer"), array(
				// The following params will be used as defaults for widgets included in this container:
			) );
		// Note: Double quotes have been used around "Footer" only for test purposes.
	?>
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
		<?php display_param_link( $skin_links ) ?> design by <?php display_param_link( $francois_links ) ?> / <?php display_param_link( $skinfaktory_links ) ?>
		&bull;
		<?php
			// Display additional credits (see /conf/):
 			// If you can add your own credits without removing the defaults, you'll be very cool :))
		 	// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			display_list( $credit_links, T_('Credits').': ', ' ', '|', ' ', ' ' );
		?>
	</p>
</div>
</div>

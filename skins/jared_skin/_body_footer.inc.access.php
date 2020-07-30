<?php
/**
 * This is the BODY footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>

<?php
if( $Skin->show_container_when_access_denied( 'footer' ) )
{ // Display 'Footer' widget container
?>
<!-- =================================== START OF FOOTER =================================== -->
<footer class="container-fluid footer_wrapper">

		<?php
		// ------------------------- "Footer" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		widget_container( 'footer', array(
				// The following params will be used as defaults for widgets included in this container:
				'container_display_if_empty' => false, // If no widget, don't display container at all
				'container_start'     => '<div class="container evo_container $wico_class$">',
				'container_end'       => '</div>',
				'block_start'         => '<div class="evo_widget $wi_class$">',
				'block_end'           => '</div>',
				// Widget 'Search form':
				'search_input_before'  => '<div class="input-group">',
				'search_input_after'   => '',
				'search_submit_before' => '<span class="input-group-btn">',
				'search_submit_after'  => '</span></div>',
				// The following overrides are used to prevent nested "container" divs with subcontainers:
				'override_params_for_subcontainer_row' => array(
					'container_start' => '<div class="evo_container $wico_class$">',
					'container_end'   => '</div>',
				),
			) );
		// ----------------------------- END OF "Footer" CONTAINER -----------------------------
		?>

	<p class="baseline">
		<?php
		// Display footer text (text can be edited in Blog Settings):
		$Blog->footer_text( array(
				'before' => '',
				'after'  => ' &bull; ',
			) );
		// TODO: dh> provide a default class for pTyp, too. Should be a name and not the ityp_ID though..?!

		// Display a link to contact the owner of this blog (if owner accepts messages):
		$Blog->contact_link( array(
				'before' => '',
				'after'  => ' &bull; ',
				'text'   => T_('Contact'),
				'title'  => T_('Send a message to the owner of this blog...'),
			) );

		// Display a link to help page:
		$Blog->help_link( array(
				'before' => ' ',
				'after'  => ' ',
				'text'   => T_('Help'),
			) );

		// Display additional credits:
		// If you can add your own credits without removing the defaults, you'll be very cool :))
		// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
		credits( array(
				'list_start' => '&bull;',
				'list_end'   => ' ',
				'separator'  => '&bull;',
				'item_start' => ' ',
				'item_end'   => ' ',
			) );
		?>
	</p>

	<?php
		// Please help us promote b2evolution and leave this logo on your blog:
		powered_by( array(
				'block_start' => '<div class="powered_by">',
				'block_end'   => '</div>',
				// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
				'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
				'img_width'   => 120,
				'img_height'  => 32,
			) );
	?>

</footer><!-- .footer_wrapper -->
<?php } ?>

<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------
?>
<?php
/**
 * ==========================================================
 * IMPORTANT: do NOT duplicate this file into a custom skin.
 * If you do, your skin may break at any future core upgrade.
 * ==========================================================
 *
 * This is the HTML footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>
<!-- End of skin_wrapper -->
</div>

<?php
	modules_call_method( 'SkinEndHtmlBody' );

	// SkinEndHtmlBody hook -- could be used e.g. by a google_analytics plugin to add the javascript snippet here:
	$Plugins->trigger_event( 'SkinEndHtmlBody' );

	$Blog->disp_setting( 'footer_includes', 'raw' );

	if( $marketing_popup_container_code = $Blog->get_marketing_popup_container() )
	{	// Display marketing popup container:
		widget_container( $marketing_popup_container_code, array(
			// The following params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			'container_start' => '<div id="evo_container__'.$marketing_popup_container_code.'" class="evo_container $wico_class$ ddexitpop">',
			'container_end'   => '</div>',
			// Force loading of all ajax forms from widgets of this container right after page loading in order to don't wait scroll down event:
			'load_ajax_form_on_page_load' => true,
		) );
	}

	// Add structured data at the end
	skin_structured_data();
?>
</body>
</html>
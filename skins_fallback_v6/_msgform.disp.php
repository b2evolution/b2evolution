<?php
/**
 * This is the template that displays the message user form
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=msgform&recipient_id=n
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @todo dh> A user/blog might want to accept only mails from logged in users (fp>yes!)
 * @todo dh> For logged in users the From name and address should be not editable/displayed
 *           (the same as when commenting). (fp>yes!!!)
 * @todo dh> Display recipient's avatar?! fp> of course! :p
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// ------------------------- "Contact Page Main Area" CONTAINER EMBEDDED HERE --------------------------
// Display container and contents:
widget_container( 'contact_page_main_area', array(
	// The following params will be used as defaults for widgets included in this container:
	'container_display_if_empty' => false, // If no widget, don't display container at all
	'container_start'     => '<div class="evo_container $wico_class$">',
	'container_end'       => '</div>',
	'block_start'         => '<div class="evo_widget $wi_class$">',
	'block_end'           => '</div>',
) );
// ----------------------------- END OF "Contact Page Main Area" CONTAINER -----------------------------

?>
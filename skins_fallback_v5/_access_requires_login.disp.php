<?php
/**
 * This file is the template that displays an access denied for not logged in users
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// ------------------ "Login Required" CONTAINER EMBEDDED HERE -------------------
// Display container and contents:
skin_container( NT_('Login Required'), array(
		// The following params will be used as defaults for widgets included in this container:
		// This will enclose each widget in a block:
		'block_start'       => '<div class="panel panel-default evo_widget $wi_class$">',
		'block_end'         => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
		'block_title_end'   => '</h4></div>',
		// This will enclose the body of each widget:
		'block_body_start'  => '<div class="panel-body">',
		'block_body_end'    => '</div>',
	) );
// --------------------- END OF "Login Required" CONTAINER -----------------------
?>
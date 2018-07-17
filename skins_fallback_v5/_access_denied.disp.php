<?php
/**
 * This file is the template that displays an access denied for non-members
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// ------------------ "Access Denied" CONTAINER EMBEDDED HERE -------------------
// Display container and contents:
skin_container( NT_('Access Denied'), array(
		// The following params will be used as defaults for widgets included in this container:
		'block_start' => '<div class="evo_widget $wi_class$">',
		'block_end'   => '</div>',
	) );
// --------------------- END OF "Access Denied" CONTAINER -----------------------
?>
<?php
/**
 * This is the sidebar include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage pluralism
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>
<div id="sidebar">
	<ul>
	<?php
		// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
		// Display container contents:
		skin_container( NT_('Sidebar'), array(
				// The following (optional) params will be used as defaults for widgets included in this container:
				// This will enclose each widget in a block:
				'block_start' => '<li class="$wi_class$">',
				'block_end'   => '</li>',
				// This will enclose the title of each widget:
				'block_title_start' => '<h2>',
				'block_title_end'   => '</h2>',
				// If a widget displays a list, this will enclose that list:
				'list_start' => '<ul>',
				'list_end'   => '</ul>',
				// This will enclose each item in a list:
				'item_start' => '<li>',
				'item_end'   => '</li>',
				// This will enclose sub-lists in a list:
				'group_start' => '<ul>',
				'group_end'   => '</ul>',
				// This will enclose (foot)notes:
				'notes_start' => '<div class="notes">',
				'notes_end'   => '</div>',
			) );
		// ----------------------------- END OF "Sidebar" CONTAINER -----------------------------
	?>
	</ul>
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
</div>
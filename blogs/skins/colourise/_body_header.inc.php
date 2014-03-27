<?php
/**
 * This is the BODY header include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package colourise
 * @subpackage colourise
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>
<div id="wrap">
	<div id="header" class="pageHeader">
		<div id="top_menu">
		<?php
			// Display container and contents:
			skin_container( NT_('Page Top'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start' => '<div class="$wi_class$">',
					'block_end' => '</div>',
					'block_display_title' => false,
					'list_start' => '<ul>',
					'list_end' => '</ul>',
					'item_start' => '<li>',
					'item_end' => '</li>',
				) );
		?>
		</div>

		<?php
			// ------------------------- "Header" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			skin_container( NT_('Header'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start'       => '<div class="$wi_class$">',
					'block_end'         => '</div>',
					'block_title_start' => '<h1>',
					'block_title_end'   => '</h1>',
				) );
			// ----------------------------- END OF "Header" CONTAINER -----------------------------
		?>

		<div id="nav">
			<ul>
			<?php
				// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
				// Display container and contents:
				skin_container( NT_('Menu'), array(
						// The following params will be used as defaults for widgets included in this container:
						'block_start' => '',
						'block_end' => '',
						'block_display_title' => false,
						'list_start' => '',
						'list_end' => '',
						'item_start' => '<li>',
						'item_end' => '</li>',
					) );
				// ----------------------------- END OF "Menu" CONTAINER -----------------------------
			?>
			</ul>
		</div>

		<form action="<?php $Blog->gen_blogurl() ?>" method="get" class="search" id="quick-search">
			<p>
			<label for="qsearch">Search:</label>
			<input type="text" onblur="if (this.value == '') {this.value = 'Search...';}" onfocus="if (this.value == 'Search...') {this.value = '';}" value="Search..." name="s" id="qsearch" class="tbox"/>
			<input type="submit" value="Submit" class="btn"/>
			</p>
		</form>

	</div>

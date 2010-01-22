<?php

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------


// ------------------------- BODY HEADER INCLUDED HERE --------------------------
skin_include( '_body_header.inc.php' );
// Note: You can customize the default BODY header by copying the generic
// /skins/_body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>
<div id="header">
	
	<h1 id="logo-text"><a href="http://demo.themelab.com/">Theme Lab Demo Server</a></h1>
	<p id="intro">Just another WordPress theme site</p>
	
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

<div id="content-wrap">

	<div id="main">
		<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$', array(
			) );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
		?>
	</div>

	<div id="sidebar">
		<?php
		// ------------------------- SIDEBAR INCLUDED HERE --------------------------
		skin_include( '_sidebar.inc.php' );
		// Note: You can customize the default BODY footer by copying the
		// _body_footer.inc.php file into the current skin folder.
		// ----------------------------- END OF SIDEBAR -----------------------------
		?>
	</div>
</div>

<?php 
// ------------------------- BODY FOOTER INCLUDED HERE --------------------------
skin_include( '_body_footer.inc.php' );
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------

// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>
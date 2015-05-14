<?php
/**
 * This file is the template that displays an access denied for not logged in users
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 * @subpackage pixelgreen
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Blog, $Plugins, $skinfaktory_links;

if( version_compare( $app_version, '3.0' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 3.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}


// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array() );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>

<div id="header">
	<div id="header-content">

		<div class="PageTop">
			<?php
				// Display container and contents:
				skin_container( NT_('Page Top'), array(
						// The following params will be used as defaults for widgets included in this container:
						'block_start' => '<div class="widget $wi_class$">',
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
					'block_start'       => '<div class="widget $wi_class$">',
					'block_end'         => '</div>',
					'block_title_start' => '<h1>',
					'block_title_end'   => '</h1>',
				) );
			// ----------------------------- END OF "Header" CONTAINER -----------------------------
		?>
	</div>
</div>

<!-- wrap starts here -->
<div id="wrap">

	<div class="headerphoto"></div>

	<!-- content-wrap starts here -->
	<div id="content-wrap"><div id="content">
		<?php
			// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
			messages( array(
				'block_start' => '<div class="action_messages">',
				'block_end'   => '</div>',
			) );
			// --------------------------------- END OF MESSAGES ---------------------------------
		?>
		<?php
			// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
			request_title( array(
					'title_before'=> '<h2>',
					'title_after' => '</h2>',
					'title_none'  => '',
					'glue'        => ' - ',
					'title_single_disp' => false,
					'format'      => 'htmlbody',
				) );
			// ------------------------------ END OF REQUEST TITLE -----------------------------
		?>

		<div id="main">
			<?php
				// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
				//skin_include( '$disp$', array() );
				skin_include( '$disp$' );
				// Note: you can customize any of the sub templates included here by
				// copying the matching php file into your skin directory.
				// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
			?>


		</div>

	<!-- content-wrap ends here -->
	</div></div>

<!-- footer starts here -->
<div id="footer"><div id="footer-content">
	<?php
		// ------------------------- "Footer" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		skin_container( NT_('Footer'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start'       => '<div class="col float-left widget $wi_class$">',
				'block_end'         => '</div>',
				'block_title_start' => '<h1>',
				'block_title_end'   => '</h1>',
			) );
		// ----------------------------- END OF "Footer" CONTAINER -----------------------------
	?>

		<div class="col2 float-right">
		<p>
		Design by: <a href="http://www.styleshout.com/free-templates.php">styleshout</a><br />
		Skin by: <a href="http://www.brendoman.com/dbc">Danny Ferguson</a> / <?php display_param_link( $skinfaktory_links ) ?><br />
		</p>

		</div>

		<p class="baseline">
			<?php
				// Display a link to contact the owner of this blog (if owner accepts messages):
				$Blog->contact_link( array(
						'before'      => '',
						'after'       => '',
						'text'   => T_('Contact'),
						'title'  => T_('Send a message to the owner of this blog...'),
					) );
				// Display a link to help page:
				$Blog->help_link( array(
						'before'      => ' &bull; ',
						'after'       => ' ',
						'text'        => T_('Help'),
					) );
			?>
			<?php
				// Display footer text (text can be edited in Blog Settings):
				$Blog->footer_text( array(
						'before'      => ' &bull; ',
						'after'       => '',
					) );
			?>
			<br />
			<?php
				// Display additional credits:
				// If you can add your own credits without removing the defaults, you'll be very cool :))
				// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
				credits( array(
						'list_start'  => ' ',
						'list_end'    => ' ',
						'separator'   => '&bull;',
						'item_start'  => ' ',
						'item_end'    => ' ',
					) );
			?>
		</p>


</div></div>
<!-- footer ends here -->
<?php
	// Trigger plugin event, which could be used e.g. by a google_analytics plugin to add the javascript snippet here:
	$Plugins->trigger_event('SkinEndHtmlBody');
?>
<!-- wrap ends here -->
</div>
<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------
?>

</body>
</html>
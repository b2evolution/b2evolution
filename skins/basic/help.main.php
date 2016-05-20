<?php
/**
 * This is the template that displays the help screen for a collection
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=help
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage basic
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Blog, $Plugins, $Hit;

if( evo_version_compare( $app_version, '2.4.1' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 2.4.1 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

// The following is temporary and should be moved to some SiteSkin class
siteskin_init();

// ----------------------------- HEADER BEGINS HERE ------------------------------
?>
<html>
<head>
	<?php skin_content_meta(); /* Charset for static pages */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
			'auto_pilot'      => 'seo_title',
		) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<?php include_headlines() ?>
</head>
<body>
<?php
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>

	<?php
		// Display container and contents:
		skin_container( NT_('Page Top'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start' => '<div>',
				'block_end' => '</div>',
				'block_display_title' => false,
				'list_start' =>  T_('Select blog:').' ',
				'list_end' => '',
				'item_start' => ' [',
				'item_end' => '] ',
				'item_selected_start' => ' [<strong>',
				'item_selected_end' => '</strong>] ',
			) );
	?>


	<hr>
	<div align="center">
		<h1><?php $Blog->name() ?></h1>
		<?php
			$Blog->tagline( array(
					'before'    => '<p>',
					'after'     => '</p>',
				) );
		?>
	</div>
	<?php
		$Blog->longdesc( array(
				'before'    => '<hr><small>',
				'after'     => '</small>',
			) );
	?>

	<hr>

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
				'title_single_disp' => true,
				'format'      => 'htmlbody',
			) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?>

	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$' );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>

	<hr>

	<div align="center">
		<p>
		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => ' [',
					'after'       => '] ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
		?>

		<?php
			user_login_link( ' [', '] ', '', '#', 'sidebar login link' );
			user_register_link( ' [', '] ', '', '#', false, 'sidebar register link' );
			user_admin_link( ' [', '] ' );
			user_logout_link( ' [', '] ' );
		?>
		</p>
	</div>

	<hr>

	<?php
		// Please help us promote b2evolution and leave this logo on your blog:
		powered_by( array(
				'block_start' => '<div align="center">',
				'block_end'   => '</div>',
				// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
				'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
				'img_width'   => 120,
				'img_height'  => 32,
			) );
	?>

	<?php
		// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
		// If site footers are enabled, they will be included here:
		siteskin_include( '_site_body_footer.inc.php' );
		// ------------------------------- END OF SITE FOOTER --------------------------------

		$Hit->log();  // log the hit on this page
		debug_info();	// output debug info if requested
	?>
</body>
</html>
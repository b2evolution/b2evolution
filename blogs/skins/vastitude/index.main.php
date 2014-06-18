<?php
/**
 * This is the main/default template for the "Smoothe" series of skins.
 * b2evo 2.4.1 Skin: Vastitude / design by Andrew Hreschak, www.thedarksighed.com
 *
 * This skin only uses one single template which includes most of its features.
 * It will also rely on default includes for specific dispays (like the comment form).
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage smoothe
 *
 * @version $Id: index.main.php 6462 2014-04-14 13:28:56Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '3.0' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 3.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>


<div id="wrap">
<div id="bannertop">
<div class="topicons"></div>
</div> <!-- END BANNERTOP DIV -->
<div id="bannermid">
	<div class="subtitle">
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
	</div> <!-- END SUBTITLE DIV -->
</div> <!-- END BANNERMID DIV -->
<div class="submenu">
	<ul id="mainnav">
	<?php
		// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		// Note: this container is designed to be a single <ul> list
		skin_container( NT_('Menu'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start' => '',
				'block_end' => '',
				'block_display_title' => false,
				'list_start' => '',
				'list_end' => '',
				'item_start'		=>	'<li>',
				'item_end'		=>	' &nbsp; / </li>',
				'item_selected_start'	=>	'<li class="selected">',
				'item_selected_end'	=>	' &nbsp; / </li>',
				'link_selected_class'	=>	'selected',
			) );
		// ----------------------------- END OF "Menu" CONTAINER -----------------------------
	?>
	</ul>
</div>


<!-- =================================== START OF MAIN AREA =================================== -->
<div id="content">
	<div class="leftside">
	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		messages( array(
				'block_start' => '<div class="action_messages">',
				'block_end'   => '</div>',
			) );
		// --------------------------------- END OF MESSAGES ---------------------------------
	?>
<div class="text">
	<?php
	if( $disp != 'single' )
	{
		// ------------------------ TITLE FOR THE CURRENT REQUEST ------------------------
		request_title( array(
				'title_before'=> '<h2>',
				'title_after' => '</h2>',
				'title_none'  => '',
				'glue'        => ' - ',
				'title_single_disp' => true,
				'format'      => 'htmlbody',
			) );
		// ----------------------------- END OF REQUEST TITLE ----------------------------
	}
	?>

	<?php
	// Go Grab the featured post:
	if( $Item = & get_featured_Item() )
	{	// We have a featured/intro post to display:
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', array(
				'feature_block' => true,
				'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
				'intro_mode'   => 'normal',	// Intro posts will be displayed in normal mode
				'item_class'   => 'featured_post',
				'image_size'	 =>	'fit-400x320',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
	?>

	<?php
	if( $disp != 'front' && $disp != 'download' )
	{
		// --------------------------------- START OF POSTS -------------------------------------
		// Display message if no post:
		display_if_empty();

		echo '<div id="styled_content_block">'; // Beginning of posts display
		while( $Item = & mainlist_get_item() )
		{	// For each blog post:
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( '_item_block.inc.php', array(
					'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
					'image_size'	 =>	'fit-400x320',
				) );
			// ----------------------------END ITEM BLOCK  ----------------------------
		} // ---------------------------------- END OF POSTS ------------------------------------
		echo '</div>'; // End of posts display
	?>

	<?php
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start'		=>	'<p class="center">',
				'block_end'		=>	'</p>',
				'prev_text'		=>	'&lt;&lt;',
				'next_text'		=>	'&gt;&gt;',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	}
	?>


	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$', array(
				'disp_posts'  => '',		// We already handled this case above
				'disp_single' => '',		// We already handled this case above
				'disp_page'   => '',		// We already handled this case above
				'author_link_text' => 'preferredname',
			) );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>
</div><!-- END text -->
</div><!-- END leftside -->
<div class="rightside">
	<div id="sidebar">
		<ul>
		<?php
			// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
			item_prevnext_links( array(
					'block_start' => '<li class="prevnext">',
					'prev_start'  => 'Previous Post:<br />',
					'prev_end'    => '<br /><br />',
					'next_start'  => 'Next Post:<br />',
					'next_end'    => '<br /><br />',
					'block_end'   => '</li>',
				) );
		?>
	<?php
		// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
		// Display container contents:
		skin_container( NT_('Sidebar'), array(
				// The following (optional) params will be used as defaults for widgets included in this container:
				// This will enclose each widget in a block:
				'block_start' => '<li class="$wi_class$">',
				'block_end' => '</li>',
				// This will enclose the title of each widget:
				'block_title_start' => '<h2>',
				'block_title_end' => '</h2>',
				// If a widget displays a list, this will enclose that list:
				'list_start' => '<ul>',
				'list_end' => '</ul>',
				// This will enclose each item in a list:
				'item_start' => '<li>',
				'item_end' => '</li>',
				// This will enclose sub-lists in a list:
				'group_start' => '<ul>',
				'group_end' => '</ul>',
				// This will enclose (foot)notes:
				'notes_start' => '<div class="notes">',
				'notes_end' => '</div>',
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
					'img_url'     => 'img/powered-by-b2evolution-138x39-895C46.gif',
					'img_width'   => 138,
					'img_height'  => 39,
				) );
		?>
	</div>
</div> <!-- END RS DIV -->
</div> <!-- END CONTENT DIV -->

<!-- =================================== START OF FOOTER =================================== -->
<div class="pagefoot">
	<?php
		// Display container and contents:
		skin_container( NT_("Footer"), array(
				// The following params will be used as defaults for widgets included in this container:
			) );
		// Note: Double quotes have been used around "Footer" only for test purposes.
	?>
	<p class="center">
		<?php
			// Display footer text (text can be edited in Blog Settings):
			$Blog->footer_text( array(
					'before'      => '',
					'after'       => ' &bull; ',
				) );
		?>

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
			// Display additional credits:
 			// If you can add your own credits without removing the defaults, you'll be very cool :))
		 	// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			credits( array(
					'list_start'  => '&bull;',
					'list_end'    => ' ',
					'separator'   => '&bull;',
					'item_start'  => ' ',
					'item_end'    => ' ',
				) );
		?>

		<br/><?php display_param_link( $skin_links ) ?> <a href="http://blog.thedarksighed.com/projectblog/" title="Custom b2evolution template designs">design by Andrew Hreschak</a>

	</p>

</div><!-- END pagefoot -->

</div><!-- END wrap -->



<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>
<?php
/**
 * This is the template that displays the front page of a collection (when front page enabled)
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 * @subpackage tabs_bootstrap_home_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( evo_version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>


<div class="container container-xxl">

	<nav class="row">
		<div class="col-sm-12 tbhs_items_menu">
			<?php /* Top menu for large screen: */ ?>
			<ul class="nav nav-tabs hidden-xs" id="tbhs_items_menu_large">
			<?php
				// Item Short Title:
				foreach( $Skin->get_front_items() as $i => $Item )
				{
					echo '<li'.( $i == 0 ? ' class="active"' : '' ).'>'
							.'<a href="'.$ReqURL.'#'.$Item->get( 'urltitle' ).'"'
								.'data-slug="'.$Item->dget( 'urltitle', 'htmlattr' ).'">'
								.$Item->get_title( array(
									'title_field' => 'short_title,title',
									'link_type'   => 'none',
								) )
							.'</a>'
						.'</li>';
				}
			?>
			</ul>
			<?php
			/* Top menu for small screen: */
			if( $active_front_Item = & $Skin->get_active_front_Item() )
			{
			?>
			<div id="tbhs_items_menu_small"><div class="btn-group visible-xs-inline-block">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span id="tbhs_items_menu_small_active"><?php echo $active_front_Item->get_title( array(
						'title_field' => 'short_title,title',
						'link_type'   => 'none',
					) ); ?></span> <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
				<?php
					// Item Short Title:
					foreach( $Skin->get_front_items() as $i => $Item )
					{
						echo '<li>'
								.'<a href="'.$ReqURL.'#'.$Item->get( 'urltitle' ).'"'
									.'data-slug="'.$Item->dget( 'urltitle', 'htmlattr' ).'">'
									.$Item->get_title( array(
										'title_field' => 'short_title,title',
										'link_type'   => 'none',
									) )
								.'</a>'
							.'</li>';
					}
				?>
				</ul>
			</div></div>
			<?php } ?>
		</div>
	</nav>

	<div class="row">

		<div class="col-sm-12">
			<?php
			// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
			messages( array(
					'block_start' => '<div class="action_messages">',
					'block_end'   => '</div>',
				) );
			// --------------------------------- END OF MESSAGES ---------------------------------
			?>
		</div><!-- .col -->

		<div class="col-sm-5 col-xs-12">
		<?php
			// Item Long Title:
			foreach( $Skin->get_front_items() as $i => $Item )
			{
				// Item long title:
				echo '<h2 class="tbhs_item_title" '
					.'data-slug="'.$Item->dget( 'urltitle', 'htmlattr' ).'"'
					.( $i > 0 ? ' style="display:none"' : '' ).'>'
						.$Item->dget( 'title' )
					.'</h2>';
			}
		?>
		</div>

		<div class="col-sm-7 pull-right-sm col-xs-12" class="tbhs_">
		<?php
			// Item Teaser:
			foreach( $Skin->get_front_items() as $i => $Item )
			{	// Display images that are linked to this post:
				$Item->images( array(
					'before'                     => '<div class="tbhs_item_teaser_image" '
						.'data-slug="'.$Item->dget( 'urltitle', 'htmlattr' ).'"'
						.( $i > 0 ? ' style="display:none"' : '' ).'>',
					'before_images'              => '<div class="evo_post_images">',
					'before_image'               => '<figure class="evo_image_block">',
					'before_image_legend'        => '<figcaption class="evo_image_legend">',
					'after_image_legend'         => '</figcaption>',
					'after_image'                => '</figure>',
					'after_images'               => '</div>',
					'after'                      => '</div>',
					'image_class'                => 'img-responsive',
					'image_size'                 => 'fit-1920x1080',
					'limit'                      => 1,
					'restrict_to_image_position' => 'cover,teaser,teaserperm,teaserlink',
				) );
			}
		?>
		</div>

		<div class="col-sm-5 col-xs-12">
		<?php
			// Item content:
			foreach( $Skin->get_front_items() as $i => $Item )
			{
				echo '<div class="tbhs_item_content" '
					.'data-slug="'.$Item->dget( 'urltitle', 'htmlattr' ).'"'
					.( $i > 0 ? ' style="display:none"' : '' ).'>';
				// ---------------------- POST CONTENT INCLUDED HERE ----------------------
				skin_include( '_item_content.inc.php', array(
						'display_teaser_images' => false,
						'image_size'            => 'fit-1280x720',
						'content_mode'          => 'normal',
					) );
				// Note: You can customize the default item feedback by copying the generic
				// /skins/_item_feedback.inc.php file into the current skin folder.
				// -------------------------- END OF POST CONTENT -------------------------
				echo '</div>';
			}
		?>

		<?php
		// ------------------ "Front Page Main Area" CONTAINER EMBEDDED HERE -------------------
		// Display container and contents:
		widget_container( 'front_page_main_area', array(
				// The following params will be used as defaults for widgets included in this container:
				'container_display_if_empty' => false, // If no widget, don't display container at all
				'container_start'         => '<div class="evo_container $wico_class$">',
				'container_end'           => '</div>',
			) );
		// --------------------- END OF "Front Page Main Area" CONTAINER -----------------------
		?>
		</div>

	</div><!-- .row -->

</div><!-- .container -->


<!-- =================================== START OF SECONDARY AREA =================================== -->
<div class="container">

	<div class="row">

			<?php
				// ------------------------- "Front Page Secondary Area" CONTAINER EMBEDDED HERE --------------------------
				// Display container and contents:
				widget_container( 'front_page_secondary_area', array(
						// The following params will be used as defaults for widgets included in this container:
						'container_display_if_empty' => false, // If no widget, don't display container at all
						'container_start'   => '<div class="col-lg-12"><div class="evo_container $wico_class$ alternate-left-right">',
						'container_end'     => '</div></div>',
						'block_start'       => '<div class="evo_widget $wi_class$">',
						'block_end'         => '</div>',
						'block_title_start' => '<h2 class="page-header">',
						'block_title_end'   => '</h2>',
					) );
				// ----------------------------- END OF "Front Page Secondary Area" CONTAINER -----------------------------
			?>

		<footer class="col-lg-12">

			<?php
				// ------------------------- "Footer" CONTAINER EMBEDDED HERE --------------------------
				// Display container and contents:
				widget_container( 'footer', array(
						// The following params will be used as defaults for widgets included in this container:
						'container_display_if_empty' => false, // If no widget, don't display container at all
						'container_start' => '<div class="evo_container $wico_class$ clearfix">', // Note: clearfix is because of Bootstraps' .cols
						'container_end'   => '</div>',
						'block_start'     => '<div class="evo_widget $wi_class$">',
						'block_end'       => '</div>',
					) );
				// ----------------------------- END OF "Footer" CONTAINER -----------------------------
			?>

			<p class="center">
			<?php
				// Display footer text (text can be edited in Blog Settings):
				$Blog->footer_text( array(
						'before' => '',
						'after'  => ' &bull; ',
					) );

			// TODO: dh> provide a default class for pTyp, too. Should be a name and not the ityp_ID though..?!
			?>

			<?php
				// Display a link to contact the owner of this blog (if owner accepts messages):
				$Blog->contact_link( array(
						'before' => '',
						'after'  => ' &bull; ',
						'text'   => T_('Contact'),
						'title'  => T_('Send a message to the owner of this blog...'),
					) );
				// Display a link to help page:
				$Blog->help_link( array(
						'before' => ' ',
						'after'  => ' ',
						'text'   => T_('Help'),
					) );
			?>

			<?php
				// Display additional credits:
				// If you can add your own credits without removing the defaults, you'll be very cool :))
				// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
				credits( array(
						'list_start' => '&bull;',
						'list_end'   => ' ',
						'separator'  => '&bull;',
						'item_start' => ' ',
						'item_end'   => ' ',
					) );
			?>
			</p>

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

		</footer><!-- .col -->

	</div><!-- .row -->

</div><!-- .container -->

<script>
function tbhs_activate_front_tab( slug )
{
	if( slug.indexOf( '#' ) > -1 )
	{
		slug = slug.replace( /^.*#+(.+)$/, '$1' );
	}

	if( slug == "" )
	{
		return;
	}

	var item_slug_selector = '[data-slug="' + slug + '"]';

	jQuery( '#tbhs_items_menu_large li' ).removeClass( 'active' );
	jQuery( '#tbhs_items_menu_large a' + item_slug_selector ).parent().addClass( 'active' );
	jQuery( '#tbhs_items_menu_small_active' ).html( jQuery( '#tbhs_items_menu_small a' + item_slug_selector ).html() );

	jQuery( '.tbhs_item_title, .tbhs_item_content, .tbhs_item_teaser_image' ).hide();
	jQuery( '.tbhs_item_title' + item_slug_selector + ', .tbhs_item_content' + item_slug_selector + ', .tbhs_item_teaser_image' + item_slug_selector ).show();
}

jQuery( '#tbhs_items_menu_large a, #tbhs_items_menu_small a' ).click( function()
{	// Activate Item's data on select it from top menu:
	tbhs_activate_front_tab( jQuery( this ).data( 'slug' ) );
} );

jQuery( document ).ready( function()
{	// Activate Item's data on page loading with anchor as Item's slug:
	tbhs_activate_front_tab( window.top.location.hash );
} );

jQuery( window ).bind( 'hashchange', function()
{	// Activate Item's data on change browser history(back/forward):
	tbhs_activate_front_tab( location.href );
} );
</script>

<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>
<?php
/**
 * This is the BODY header include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage intense
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>

<div id="rap">
		<div id="top">
			<?php
			// Random or custom header image.
			$header_index = 0;
			if( ($head_image = $Skin->get_setting( 'head_image' )) !== false)
			{
				$head_image = (int) $head_image;
				$header_index = $head_image == 0 ? mt_rand(1, 4) : $head_image;
			}
			?>
			<div id="header" class="header<?php echo $header_index;?>">
				<div id="menu">
				<?php
					// Display container and contents:
					skin_container( NT_('Page Top'), array(
							// The following params will be used as defaults for widgets included in this container:
							'block_start'         => '<div>',
							'block_end'           => '</div>',
							'block_display_title' => false,
							'list_start'          => '<ul>',
							'list_end'            => '</ul>',
							'item_start'          => '<li class="first page_item">',
							'item_end'            => '</li>',
						) );
				?>
				</div>
				<h1 id="title"><a href="<?php $Blog->disp('url'); ?>" title="<?php $Blog->disp('name'); ?>"><?php $Blog->disp('name'); ?></a></h1>
			</div>

			<div class="nav rounded_bottom">
				<div id="slogan"><?php $Blog->tagline(); ?></div>
				<div id="navdiv">
					<ul id="navlist">
					<?php
						// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
						// Display container and contents:
						skin_container( NT_('Menu'), array(
								// The following params will be used as defaults for widgets included in this container:
								'block_start'         => '',
								'block_end'           => '',
								'block_display_title' => false,
								'list_start'          => '',
								'list_end'            => '',
								'item_start'          => '<li>',
								'item_end'            => '</li>',
							) );
						// ----------------------------- END OF "Menu" CONTAINER -----------------------------
					?>
					</ul>
				</div>
			</div>
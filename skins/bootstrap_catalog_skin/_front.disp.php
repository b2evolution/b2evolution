<?php
/**
 * This is the template that displays the front page of a collection (when front page enabled)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in a *.main.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$params = array_merge( array(
		'item_class'                 => 'evo_post evo_content_block',
		'item_type_class'            => 'evo_post__ptyp_',
		'item_status_class'          => 'evo_post__',
		'item_style'                 => '',

		'author_link_text'              => 'auto',
		'featured_intro_before'         => '',
		'featured_intro_after'          => '',
		'front_block_start'             => '<div class="evo_widget $wi_class$">',
		'front_block_end'               => '</div>',
		'front_block_first_title_start' => '<h3>',
		'front_block_first_title_end'   => '</h3>',
		'front_block_title_start'       => '<h3>',
		'front_block_title_end'         => '</h3>',
	), $params );


// ------------------------------- START OF INTRO-FRONT POST -------------------------------
// Go Grab the featured post:
if( $Item = & get_featured_Item( 'front' ) )
{	// We have a featured/intro post to display:
	$intro_item_style = '';
	$LinkOwner = new LinkItem( $Item );
	$LinkList = $LinkOwner->get_attachment_LinkList( 1, 'cover' );
	if( ! empty( $LinkList ) &&
   				 $Link = & $LinkList->get_next() &&
				 $File = & $Link->get_File() &&
				 $File->exists() &&
				 $File->is_image() )
	{	// Use cover image of intro-post as background:
		$intro_item_style = 'background-image: url('.$File->get_url().')';
	}
	?>
<div id="<?php $Item->anchor_id() ?>" class="jumbotron <?php $Item->div_classes( $params ); echo $Item->is_intro() ? ' evo_intro_post' : ' evo_featured_post'; echo empty( $intro_item_style ) ? '' : ' evo_hasbgimg'; ?>" lang="<?php $Item->lang() ?>" style="<?php echo $intro_item_style; ?>">
	<div class="evo_content_block">
	<?php
	$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)

	$action_links = $Item->get_edit_link( array( // Link to backoffice for editing
			'before' => '',
			'after'  => '',
			'text'   => $Item->is_intro() ? get_icon( 'edit' ).' '.T_('Edit Intro') : '#',
			'class'  => button_class( 'text' ),
		) );
	if( $Item->status != 'published' )
	{
		$Item->format_status( array(
				'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
			) );
	}
	$Item->title( array(
			'link_type'  => 'none',
			'before'     => '<div class="evo_post_title"><h2>',
			'after'      => '</h2><div class="'.button_class( 'group' ).'">'.$action_links.'</div></div>',
			'nav_target' => false,
		) );

	// ---------------------- POST CONTENT INCLUDED HERE ----------------------
	skin_include( '_item_content.inc.php', array_merge( $params, array( 'Item' => $Item ) ) );
	// Note: You can customize the default item content by copying the generic
	// /skins/_item_content.inc.php file into the current skin folder.
	// -------------------------- END OF POST CONTENT -------------------------

	locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
	</div><!-- /.evo_content_block -->
</div><!-- /.jumbotron -->
<?php
}
// ------------------------------- END OF INTRO-FRONT POST -------------------------------


// --------------------------------- START OF CATEGORY LIST --------------------------------
$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID );

if( count( $chapters ) > 0 )
{ // If category is found

echo '<section class="maincategories_section">';
	echo '<h3 class="maincategories_section__title">' . T_( 'Categories' ) . '</h3>';
	$section_is_started = false;
	
	echo '<div class="row">';
	foreach( $chapters as $root_Chapter )
	{ // Loop through categories:
		echo '<div class="category-item-wrapper col-md-3 col-xs-6">';
		echo '<a href="' . $root_Chapter->get_permanent_url() . '"><div class="category-item">';
		echo '<div class="rootcat rootcat_' . $root_Chapter->dget( 'ID' ) . '">' . $root_Chapter->dget( 'name' ) . '</div>';
		echo '</div></a>';
		echo '</div>';
	} // End of categories loop.
	echo '</div>';
echo '</section>';
}
// ---------------------------------- END OF CATEGORY LIST ---------------------------------


// ------------------------------ START OF FEATURED PRODUCTS -------------------------------
echo '<h3>' . T_( 'Featured products' ) . '</h3>';
echo '<section class="row">';
while( mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"
	skin_include( '_item_block.inc.php', array_merge( array(
			'content_mode' => 'excerpt', // 'auto' will auto select depending on $disp-detail
		), $params ) );
}
echo '</section>';
// ------------------------------- END OF FEATURED PRODUCTS --------------------------------


?>
<div class="evo_container evo_container__front_page_secondary">
<?php // ------------------ "Front Page Secondary Area" CONTAINER EMBEDDED HERE -------------------
skin_container( NT_('Front Page Secondary Area'), array(
		// The following params will be used as defaults for widgets included in this container:
		'block_start'             => $params['front_block_start'],
		'block_end'               => $params['front_block_end'],
		'block_first_title_start' => $params['front_block_first_title_start'],
		'block_first_title_end'   => $params['front_block_first_title_end'],
		'block_title_start'       => $params['front_block_title_start'],
		'block_title_end'         => $params['front_block_title_end'],
	) );
// --------------------- END OF "Front Page Secondary Area" CONTAINER -----------------------------
?>
</div>
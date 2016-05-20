<?php
/**
 * This is the template that displays a download page for post file
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Blog, $download_Item, $download_Link;

// Default params:
$params = array_merge( array(
		'download_item_links_before'    => '<p>',
		'download_item_links_after'     => '</p>',
		'download_item_link_before'     => '',
		'download_item_link_after'      => '',
		'download_item_links_separator' => ' | ',
		'download_file_title'           => T_( 'Downloading: %s' ),
		'download_item_title_before'    => ' <small>',
		'download_item_title_after'     => '</small>',
		'download_file_name_before'     => '<p>',
		'download_file_name_after'      => '</p>',
		'download_file_desc_text'       => '<p>%s</p>',
		'download_timer_text'           => '<div class="alert alert-info"><p id="download_timer_js" style="display:none">'.T_( 'Your download will start in: %s seconds.' ).
		                                     '<span id="download_help_url" style="display:none"><br />'.
		                                       T_( 'If your download hasn\'t started automatically, please <a %s>click here</a>.' ).
		                                     '</span>'.
		                                   '</p></div>',
		'download_nojs_text'            => '<div class="alert alert-info"><p class="alert alert-info" id="download_info_nojs">'.T_( 'Your download will start shortly...' ).'<br />'.
		                                       T_( 'If nothing happens, please <a %s>click here</a>.' ).
		                                   '</p></div>',

		'before_content_teaser'    => '',
		'after_content_teaser'     => '',
		'before_content_extension' => '',
		'after_content_extension'  => '',
		'before_image'             => '<div class="image_block">',
		'before_image_legend'      => '<div class="image_legend">',
		'after_image_legend'       => '</div>',
		'after_image'              => '</div>',
		'image_size'               => 'fit-400x320',
		'image_limit'              =>  1000,
		'image_link_to'            => 'original', // Can be 'original', 'single' or empty
	), $params );

/**
 * @var The downloading File
 */
$download_File = & $download_Link->get_File();
?>
<header>
	<div class="evo_post_title"</div>
		<h1>Download: <?php echo $download_File->get_name(); ?></h1>
	</div>
</header>
<div id="<?php $download_Item->anchor_id() ?>" class="<?php $download_Item->div_classes( $params ) ?>" lang="<?php $download_Item->lang() ?>">
<?php

// Temporarily switch to post locale (useful for multilingual blogs)
$download_Item->locale_temp_switch();

// File name
echo $params['download_file_name_before'];
printf( $params['download_file_title'], '<b>'.$download_File->get_name().'</b>' );
echo $download_Item->title( array(
		'before' => $params['download_item_title_before'],
		'after'  => $params['download_item_title_after'],
	) );
echo $params['download_file_name_after'];

// File description
$file_desc = $download_File->dget( 'desc' );
if( ! empty( $file_desc ) )
{
	printf( $params['download_file_desc_text'], $file_desc );
}

// The download progress:
$file_download_link_attrs = 'href="'.$download_Link->get_download_url( array( 'type' => 'action' ) ).'"'
	.' download="'.$download_File->get_name().'"';
// 1) JavaScript is enabled
printf( $params['download_timer_text'],
		'<span id="download_timer">'.$Blog->get_setting( 'download_delay' ).'</span>',
		$file_download_link_attrs
	);
// 2) JavaScript is NOT enabled
echo '<noscript>';
printf( $params['download_nojs_text'],
		$file_download_link_attrs
	);
echo '</noscript>';

// Post content
if( $download_Item->has_content_parts( $params ) )
{ // Display only text after <more>
	$download_Item->content_extension( array(
			'before'              => $params['before_content_extension'],
			'after'               => $params['after_content_extension'],
			'before_image'        => $params['before_image'],
			'before_image_legend' => $params['before_image_legend'],
			'after_image_legend'  => $params['after_image_legend'],
			'after_image'         => $params['after_image'],
			'image_size'          => $params['image_size'],
			'limit'               => $params['image_limit'],
			'image_link_to'       => $params['image_link_to'],
			'force_more'          => true,
		) );
}
else
{ // Display full content:
	$download_Item->content_teaser( array(
			'before'              => $params['before_content_teaser'],
			'after'               => $params['after_content_teaser'],
			'before_image'        => $params['before_image'],
			'before_image_legend' => $params['before_image_legend'],
			'after_image_legend'  => $params['after_image_legend'],
			'after_image'         => $params['after_image'],
			'image_size'          => $params['image_size'],
			'limit'               => $params['image_limit'],
			'image_link_to'       => $params['image_link_to'],
		) );
}

// Restore previous locale (Blog locale)
locale_restore_previous();

?>
</div>
<?php
/**
 * This is the template that displays the media index for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=arcdir
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Display photos:
// TODO: permissions, complete statuses, aggregations...
// TODO: A FileList object based on ItemListLight but adding File data into the query?
//          overriding ItemListLigth::query() for starters ;)


$FileCache = & get_Cache( 'FileCache' );

$FileList = & new DataObjectList2( $FileCache );

// Query list of files:
$SQL = & new SQL();
$SQL->SELECT( 'post_ID, post_datestart, post_datemodified, post_main_cat_ID, post_urltitle, post_ptyp_ID, post_title, post_excerpt, post_url,
							file_ID, file_title, file_root_type, file_root_ID, file_path, file_alt, file_desc' );
$SQL->FROM( 'T_categories INNER JOIN T_postcats ON cat_ID = postcat_cat_ID
							INNER JOIN T_items__item ON postcat_post_ID = post_ID
							INNER JOIN T_links ON post_ID = link_itm_ID
							INNER JOIN T_files ON link_file_ID = file_ID' );
$SQL->WHERE( 'cat_blog_ID = '.$Blog->ID ); // fp> TODO: want to restrict on images :]
$SQL->WHERE_and( 'post_status = "published"' );	// TODO: this is a dirty temporary hack. More should be shown.
$SQL->GROUP_BY( 'link_ID' );
$SQL->ORDER_BY( 'post_'.$Blog->get_setting('orderby').' '.$Blog->get_setting('orderdir')
								.', post_ID '.$Blog->get_setting('orderdir').', link_ID' );

$FileList->sql = $SQL->get();

$FileList->query( false, false, false );

echo '<table class="image_index" cellspacing="3">';

$nb_cols = 8;
$count = 0;
$prev_post_ID = 0;
while( $File = & $FileList->get_next() )
{
	if( ! $File->is_image() )
	{	// Skip anything that is not an image
		// fp> TODO: maybe this property should be stored in link_ltype_ID
		continue;
	}

	if( $count % $nb_cols == 0 )
	{
		echo '<tr>';
	}
	echo '<td>';

	$post_ID = $FileList->rows[$FileList->current_idx-1]->post_ID;
	if( $post_ID != $prev_post_ID )
	{
		$prev_post_ID = $post_ID;
		$count++;
	}

	// 1/ Hack a dirty permalink( will redirect to canonical):
	// $link = url_add_param( $Blog->get('url'), 'p='.$post_ID );

	// 2/ Hack a link to the right "page". Very daring!!
	// $link = url_add_param( $Blog->get('url'), 'paged='.$count );

	// 3/ Instantiate a light object in order to get permamnent url:
	$ItemLight = & new ItemLight( $FileList->get_row_by_idx( $FileList->current_idx - 1 ) );	// index had already been incremented

	echo '<a href="'.$ItemLight->get_permanent_url().'">';
	// Generate the IMG THUMBNAIL tag with all the alt, title and desc if available
	echo '<img src="'.$File->get_thumb_url().'" '
				.'alt="'.$File->dget('alt', 'htmlattr').'" '
				.'title="'.$File->dget('title', 'htmlattr').'" />';
	echo '</a>';

	echo '</td>';
	if( $count % $nb_cols == 0 )
	{
		echo '</tr>';
	}

}

if( $count && ( $count % $nb_cols != 0 ) )
{
	echo '</tr>';
}


echo '</table>';

/*
 * $Log$
 * Revision 1.3  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/12/23 20:10:49  fplanque
 * removed suspects
 *
 * Revision 1.1  2007/11/25 19:45:26  fplanque
 * cleaned up photo/media index a little bit
 *
 * Revision 1.10  2007/05/14 02:43:07  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.9  2007/04/26 00:11:03  fplanque
 * (c) 2007
 *
 * Revision 1.8  2007/03/18 01:39:57  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.7  2007/03/11 20:39:44  fplanque
 * little fix
 *
 * Revision 1.6  2007/01/23 09:25:39  fplanque
 * Configurable sort order.
 *
 * Revision 1.5  2007/01/23 03:46:24  fplanque
 * cleaned up presentation
 *
 * Revision 1.4  2007/01/15 20:48:19  fplanque
 * constrained photoblog image size
 * TODO: sharpness issue
 *
 * Revision 1.3  2006/12/14 23:02:28  fplanque
 * the unbelievable hack :P
 *
 * Revision 1.1  2006/12/14 22:29:37  fplanque
 * thumbnail archives proof of concept
 *
 */
?>
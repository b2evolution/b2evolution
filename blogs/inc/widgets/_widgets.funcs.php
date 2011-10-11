<?php
/**
 * This file implements additional functional for widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 */


/**
 * @todo factorize!
 *
 * @param integer should never be 0
 * @param boolean should be true only when it's called after initial install
 */
function insert_basic_widgets( $blog_id, $initial_install = false )
{
	global $DB, $test_install_all_features;

	$default_blog_param = 's:7:"blog_ID";s:0:"";';
	if( $initial_install )
	{	// In the case of initial install, we grab photos out of the photoblog (Blog #4)
		$default_blog_param = 's:7:"blog_ID";s:1:"4";';
	}

	if( false )
	{// old code: NOT USED ANYMORE - TO BE REMOVED SOON.
		$DB->query('INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Page Top", 1, "core", "colls_list_public"
							   FROM T_blogs');
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Header", 1, "core", "coll_title"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Header", 2, "core", "coll_tagline"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Menu", 1, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'home'))).'"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Menu", 3, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'ownercontact'))).'"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Menu", 2, "core", "coll_page_list"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Menu", 4, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'login'))).'"
							   FROM T_blogs'  );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Menu Top", 1, "core", "coll_search_form"
							   FROM T_blogs'  );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 10, "core", "coll_avatar"
							   FROM T_blogs'  );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 20, "plugin", "evo_Calr"
							   FROM T_blogs
							  WHERE blog_ID > 1' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 40, "core", "coll_longdesc"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 30, "core", "coll_title"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 50, "core", "coll_common_links"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 60, "core", "coll_search_form"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 70, "core", "coll_category_list"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Sidebar", 80, "core", "coll_media_index", \'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\'
							   FROM T_blogs
							  WHERE blog_ID <> 2' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Sidebar", 90, "core", "linkblog", "'.$DB->escape(serialize(array('blog_ID'=>3))).'"
							   FROM T_blogs
							  WHERE blog_ID <= 2' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 100, "core", "coll_xml_feeds"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
								 SELECT blog_ID, "Sidebar 2", 1, "core", "coll_post_list"
								   FROM T_blogs' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
								 SELECT blog_ID, "Sidebar 2", 2, "core", "coll_comment_list"
								   FROM T_blogs' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
								 SELECT blog_ID, "Sidebar 2", 3, "core", "coll_media_index", \'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\'
								   FROM T_blogs' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
								 SELECT blog_ID, "Sidebar 2", 4, "core", "free_html", \'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\'
								   FROM T_blogs' );
	}
	else
	{ // New code used in all situations:
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Page Top", 1, "core", "colls_list_public" )' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Header", 1, "core", "coll_title" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Header", 2, "core", "coll_tagline" )' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Menu", 1, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'home'))).'" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Menu", 2, "core", "coll_page_list" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Menu", 3, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'ownercontact'))).'" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Menu", 4, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'login'))).'" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Menu", 5, "core", "msg_menu_link", "'.$DB->escape(serialize(array('link_type'=>'messages'))).'" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Menu", 6, "core", "msg_menu_link", "'.$DB->escape(serialize(array('link_type'=>'contacts', 'show_badge'=>0))).'" )' );
		if( $test_install_all_features )
		{	// Add menu with User Directory
			$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
								VALUES( '.$blog_id.', "Menu", 7, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'users'))).'" )' );
		}

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Menu Top", 1, "core", "coll_search_form" )' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar", 10, "core", "coll_avatar" )' );
		if( $blog_id > 1 )
		{
			$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
								VALUES( '.$blog_id.', "Sidebar", 20, "plugin", "evo_Calr" )' );
		}
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar", 30, "core", "coll_title" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar", 40, "core", "coll_longdesc" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar", 50, "core", "coll_common_links" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar", 60, "core", "coll_search_form" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar", 70, "core", "coll_category_list" )' );
		if( $blog_id != 2 )
		{
			$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
								VALUES( '.$blog_id.', "Sidebar", 80, "core", "coll_media_index", \'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";'.$default_blog_param.'s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\' )' );
		}
		if( $blog_id <= 2 )
		{
			$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
								VALUES( '.$blog_id.', "Sidebar", 90, "core", "linkblog", "'.$DB->escape(serialize(array('blog_ID'=>3))).'" )' );
		}
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar", 100, "core", "coll_xml_feeds" )' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar 2", 1, "core", "coll_post_list" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar 2", 2, "core", "coll_comment_list" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Sidebar 2", 3, "core", "coll_media_index", \'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";'.$default_blog_param.'s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\' )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Sidebar 2", 4, "core", "free_html", \'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\' )' );
	}

}

/*
 * $Log$
 * Revision 1.11  2011/10/11 05:52:14  efy-asimo
 * Messages menu link widget
 *
 * Revision 1.10  2011/10/07 11:24:52  efy-yurybakh
 * Extend  _menu_link.widget.php
 *
 * Revision 1.9  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.8  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.7  2011/01/06 14:48:22  efy-asimo
 * Widget initialization - fix
 *
 * Revision 1.6  2010/12/08 12:57:16  efy-asimo
 * widgets default blog param - fix
 *
 * Revision 1.5  2010/12/07 16:53:34  efy-asimo
 * widgets - use current blog by default to pick photos - fix
 *
 * Revision 1.4  2010/03/22 23:50:56  fplanque
 * Fixed widget install factorization
 *
 */
?>
<?php
/**
 * This file implements the Blog class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 * @author gorgeb: EPISTEMA (Bertrand Gorge).
 * @author jeffbearer: Jeff BEARER
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobject.class.php';

/**
 * Blog
 *
 * Blog object with params
 *
 * @package evocore
 */
class Blog extends DataObject
{
	/**
	 * Short name for use in navigation menus
	 */
	var $shortname;
	/**
	 * Complete name
	 */
	var $name;
	/**
	 * Tagline to be displayed on template
	 */
	var $tagline;
	var $shortdesc; // description
	var $longdesc;
	var $locale;
	var $access_type;
	var $siteurl;
	var $staticfilename;
	var $stub;     // stub file (can be empty)
	var $urlname;  // used to identify blog in URLs
	var $links_blog_ID = 0;
	var $notes;
	var $keywords;
	var $allowcomments = 'post_by_post';
	var $allowtrackbacks = 0;
	var $allowpingbacks = 0;
	var $pingb2evonet = 0;
	var $pingtechnorati = 0;
	var $pingweblogs = 1;
	var $pingblodotgs = 0;
	var $default_skin;
	var $force_skin = 0;
	var $disp_bloglist = 1;
	var $in_bloglist = 1;
	var $UID;
	var $media_location = 'default';
	var $media_subdir = '';
	var $media_fullpath = '';
	var $media_url = '';

	/**
	 * Constructor
	 *
	 * {@internal Blog::Blog(-) }}
	 *
	 * @param object DB row
	 */
	function Blog( $db_row = NULL )
	{
		global $tableblogs, $basepath, $media_subdir;

		// Call parent constructor:
		parent::DataObject( $tableblogs, 'blog_', 'blog_ID' );

		if( $db_row == NULL )
		{
			global $default_locale;
			// echo 'Creating blank blog';
			$this->shortname = T_('New blog');
			$this->name = T_('New weblog');
			$this->locale = $default_locale;
			$this->access_type = 'index.php';
			$this->urlname = 'new';
			$this->default_skin = 'basic';
		}
		else
		{
			$this->ID = $db_row->blog_ID;
			$this->shortname = $db_row->blog_shortname;
			$this->name = $db_row->blog_name;
			$this->tagline = $db_row->blog_tagline;
			$this->shortdesc = $db_row->blog_description;	// description
			$this->longdesc = $db_row->blog_longdesc;
			$this->locale = $db_row->blog_locale;
			$this->access_type = $db_row->blog_access_type;
			$this->siteurl = $db_row->blog_siteurl;
			$this->staticfilename = $db_row->blog_staticfilename;
			$this->stub = $db_row->blog_stub;
			$this->urlname = $db_row->blog_urlname;
			$this->links_blog_ID = $db_row->blog_links_blog_ID;
			$this->notes = $db_row->blog_notes;
			$this->keywords = $db_row->blog_keywords;
			$this->allowcomments = $db_row->blog_allowcomments;
			$this->allowtrackbacks = $db_row->blog_allowtrackbacks;
			$this->allowpingbacks = $db_row->blog_allowpingbacks;
			$this->pingb2evonet = $db_row->blog_pingb2evonet;
			$this->pingtechnorati = $db_row->blog_pingtechnorati;
			$this->pingweblogs = $db_row->blog_pingweblogs;
			$this->pingblodotgs = $db_row->blog_pingblodotgs;
			$this->default_skin = $db_row->blog_default_skin;
			$this->force_skin = $db_row->blog_force_skin;
			$this->disp_bloglist = $db_row->blog_disp_bloglist;
			$this->in_bloglist = $db_row->blog_in_bloglist;
			$this->media_location = $db_row->blog_media_location;
			$this->media_subdir = $db_row->blog_media_subdir;
			$this->media_fullpath = $db_row->blog_media_fullpath;
			$this->media_url = $db_row->blog_media_url;
			$this->UID = $db_row->blog_UID;
		}
	}


	/**
	 * Set the media folder's subdir
	 *
	 * @param string the subdirectory
	 */
	function setMediaSubDir( $path )
	{
		parent::set_param( 'media_subdir', 'string', trailing_slash( $path ) );
	}


	/**
	 * Set the full path of the media folder
	 *
	 * @param string the full path
	 */
	function setMediaFullPath( $path )
	{
		parent::set_param( 'media_fullpath', 'string', trailing_slash( $path ) );
	}


	/**
	 * Set the full URL of the media folder
	 *
	 * @param string the full URL
	 */
	function setMediaUrl( $url )
	{
		parent::set_param( 'media_url', 'string', trailing_slash( $url ) );
	}


	/**
	 * Set param value
	 *
	 * {@internal Blog::set(-) }}
	 *
	 * @param string Parameter name
	 * @return mixed Parameter value
	 */
	function set( $parname, $parvalue )
	{
		global $Settings;

		switch( $parname )
		{
			case 'ID':
			case 'allowtrackbacks':
			case 'allowpingbacks':
			case 'pingb2evonet':
			case 'pingtechnorati':
			case 'pingweblogs':
			case 'pingblodotgs':
			case 'disp_bloglist':
			case 'force_skin':
				parent::set_param( $parname, 'number', $parvalue );
				break;

			/* fplanque: I'm removing this because it's no good when using absolute URL
			case 'access_type':
				if( $parvalue == 'default' )
				{
					$Settings->set('default_blog_ID', $this->ID);
					$Settings->updateDB();
				}
			*/

			default:
				parent::set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 * Generate blog URL
	 *
	 * {@internal Blog::gen_blogurl(-)}}
	 *
	 * @param string default|dynamic|static
	 * @param boolean should this be an absolute URL? (otherwise: relative to $baseurl)
	 */
	function gen_blogurl( $type = 'default', $absolute = true )
	{
		global $baseurl, $basepath, $Settings;

		if( preg_match( '#^https?://#', $this->siteurl ) )
		{
			$base = $this->siteurl;
		}
		else
		{
			$base = $absolute ? $baseurl.$this->siteurl : $this->siteurl;
		}

		if( $type == 'static' )
		{ // We want the static page, there is no access type option here:
			if( is_file( $basepath.$this->siteurl.$this->staticfilename ) )
			{ // If static page exists:
				return $base.$this->staticfilename;
			}
		}

		switch( $this->access_type )
		{
			case 'default':
				// Access through index.php: match absolute URL or call default blog
				if( ( $Settings->get('default_blog_ID') == $this->ID )
					|| preg_match( '#^https?://#', $this->siteurl ) )
				{ // Safety check! We only do that kind of linking if this is really the default blog...
					// or if we call by absolute URL
					return $base.'index.php';
				}
				// ... otherwise, we add the blog ID:

			case 'index.php':
				// Access through index.php + blog qualifier
				if( $Settings->get('links_extrapath') )
				{
					return $base.'index.php/'.$this->stub;
				}
				return $base.'index.php?blog='.$this->ID;

			case 'stub':
				// Access through stub file
				$blogurl = $base;
				if( !empty($this->stub) )
				{
					$blogurl .= $this->stub;
				}
				if( ($type == 'dynamic') && !( preg_match( '#.php$#', $blogurl ) ) )
				{ // We want to force the dynamic page but the URL is not explicitely dynamic
					$blogurl .= '.php';
				}
				return $blogurl;

			default:
				die( 'Unhandled Blog access type ['.$this->access_type.']' );
		}
	}


	/**
	 * generate the blog's media directory
	 *
	 * @todo create if necessary
	 * @param boolean absolute path or relative to $basepath.$media_subdir ?
	 */
	function gen_mediadir( $absolute = true )
	{
		global $basepath, $media_subdir, $Messages;

		switch( $this->media_location )
		{
			case 'default':
				$mediadir = $basepath.$media_subdir.'blogs/'.$this->urlname;
				break;
			case 'subdir':
				$mediadir = $basepath.$media_subdir.'blogs/'.$this->media_subdir;
				break;
			case 'custom':
				$mediadir = $this->media_fullpath;
				break;
		}

		if( !is_dir( $mediadir ) )
		{
			if( !mkdir( $mediadir ) ) // defaults to 0777
			{ // add error
				$Messages->add( sprintf( T_("The blog's media directory [%s] could not be created."), $mediadir ), 'error' );
				return false;
			}
			else
			{ // add note
				$Messages->add( sprintf( T_("The blog's media directory [%s] has been created with permissions %s."), $mediadir, '777' ), 'note' );
			}
		}

		return $absolute ?
						$mediadir :
						preg_replace( '#^'.$basepath.$media_subdir.'blogs/#', '', $this->media_fullpath );

	}


	/**
	 * Get a param
	 *
	 * {@internal Blog::get(-)}}
	 */
	function get( $parname )
	{
		global $xmlsrv_url, $admin_email, $baseurl, $basepath, $media_url;

		switch( $parname )
		{
			case 'mediadir':
				return $this->gen_mediadir();

			case 'mediaurl':
				return ($this->media_location == 'custom')
								? $this->media_url
								: $media_url.'blogs/'.$this->gen_mediadir( false ).'/';

			case 'subdir':
				return $this->siteurl;

			case 'suburl':
				return $this->gen_blogurl( 'default', false );

			case 'blogurl':
			case 'link':    // RSS wording
			case 'url':
				return $this->gen_blogurl( 'default' );

			case 'dynurl':
				return $this->gen_blogurl( 'dynamic' );

			case 'staticurl':
				return $this->gen_blogurl( 'static' );

			case 'dynfilepath':
				return $basepath.$this->siteurl.$this->stub.( preg_match( '#.php$#', $this->stub ) ? '' : '.php' );

			case 'staticfilepath':
				return $basepath.$this->siteurl.$this->staticfilename;

			case 'baseurl':
				if( preg_match( '#^https?://#', $this->siteurl ) )
				{	// We have a specific URL for this blog:
					return $this->siteurl;
				}
				else
				{	// This blog is located under b2evo's baseurl
					$r = $baseurl;
					if( !empty($this->siteurl) )
					{	// We have a subfolder:
						$r .= $this->siteurl;
					}
					return $r;
				}

			case 'cookie_domain':
				preg_match( '#(https?://(.+?)(:.+?)?)/#', $this->get('baseurl'), $match );
				return '.'.$match[2];

			case 'cookie_path':
				return preg_replace( '#https?://[^/]+#', '', $this->get('baseurl') );

			case 'blogstatsurl':
				return url_add_param( $this->gen_blogurl( 'default' ), 'disp=stats' );

			case 'lastcommentsurl':
				return url_add_param( $this->gen_blogurl( 'default' ), 'disp=comments' );

			case 'arcdirurl':
				return url_add_param( $this->gen_blogurl( 'default' ), 'disp=arcdir' );

			case 'msgformurl':
				return url_add_param( $this->gen_blogurl( 'default' ), 'disp=msgform' );

			case 'description':			// RSS wording
			case 'shortdesc':
					return $this->shortdesc;
				break;

			case 'rdf_url':
				return $xmlsrv_url.'rdf.php?blog='.$this->ID;

			case 'rss_url':
				return $xmlsrv_url.'rss.php?blog='.$this->ID;

			case 'rss2_url':
				return $xmlsrv_url.'rss2.php?blog='.$this->ID;

			case 'atom_url':
				return $xmlsrv_url.'atom.php?blog='.$this->ID;

			case 'comments_rdf_url':
				return $xmlsrv_url.'rdf.comments.php?blog='.$this->ID;

			case 'comments_rss_url':
				return $xmlsrv_url.'rss.comments.php?blog='.$this->ID;

			case 'comments_rss2_url':
				return $xmlsrv_url.'rss2.comments.php?blog='.$this->ID;

			case 'comments_atom_url':
				return $xmlsrv_url.'atom.comments.php?blog='.$this->ID;

			case 'pingback_url':
				return $xmlsrv_url.'xmlrpc.php';

			case 'admin_email':
				return $admin_email;

			default:
				// All other params:
				return parent::get( $parname );
		}
	}


	/**
	 * Delete a blog and dependencies from database
	 *
	 * Includes WAY TOO MANY requests because we try to be compatible with mySQL 3.23, bleh!
	 *
	 * {@internal Blog::dbdelete(-) }}
	 *
	 * @param boolean true if you want to try to delete the stub file
	 * @param boolean true if you want to try to delete the static file
	 * @param boolean true if you want to echo progress
	 */
	function dbdelete( $delete_stub_file = false, $delete_static_file = false, $echo = false )
	{
		global $DB, $cache_blogs;

		// Note: No need to localize the status messages...
		if( $echo ) echo '<p>mySQL 3.23 compatibility mode!';

		// Get list of cats that are going to be deleted (3.23)
		if( $echo ) echo '<br />Getting category list to delete... ';
		$cat_list = $DB->get_list( "SELECT cat_ID
																FROM T_categories
																WHERE cat_blog_ID = $this->ID" );

		if( empty( $cat_list ) )
		{	// There are no cats to delete
			echo 'None!';
		}
		else
		{	// Delete the cats & dependencies

			// Get list of posts that are going to be deleted (3.23)
			if( $echo ) echo '<br />Getting post list to delete... ';
			$post_list = $DB->get_list( "SELECT postcat_post_ID
																		FROM T_postcats
																		WHERE postcat_cat_ID IN ($cat_list)" );

			if( empty( $post_list ) )
			{	// There are no posts to delete
				echo 'None!';
			}
			else
			{	// Delete the posts & dependencies

				// Delete postcats
				if( $echo ) echo '<br />Deleting post-categories... ';
				$ret = $DB->query(	"DELETE FROM T_postcats
															WHERE postcat_cat_ID IN ($cat_list)" );
				if( $echo ) printf( '(%d rows)', $ret );


				// Delete comments
				if( $echo ) echo '<br />Deleting comments on blog\'s posts... ';
				$ret = $DB->query( "DELETE FROM T_comments
														WHERE comment_post_ID IN ($post_list)" );
				if( $echo ) printf( '(%d rows)', $ret );


				// Delete posts
				if( $echo ) echo '<br />Deleting blog\'s posts... ';
				$ret = $DB->query(	"DELETE FROM T_posts
															WHERE ID  IN ($post_list)" );
				if( $echo ) printf( '(%d rows)', $ret );

			} // / are there posts?

			// Delete categories
			if( $echo ) echo '<br />Deleting blog\'s categories... ';
			$ret = $DB->query( "DELETE FROM T_categories
													WHERE cat_blog_ID = $this->ID" );
			if( $echo ) printf( '(%d rows)', $ret );

		} // / are there cats?

		// Delete blogusers
		if( $echo ) echo '<br />Deleting user-blog permissions... ';
		$ret = $DB->query( "DELETE FROM T_blogusers
												WHERE bloguser_blog_ID = $this->ID" );
		if( $echo ) printf( '(%d rows)', $ret );

		// Delete hitlogs
		if( $echo ) echo '<br />Deleting blog hitlogs... ';
		$ret = $DB->query( "DELETE FROM T_hitlog
												WHERE hit_blog_ID = $this->ID" );
		if( $echo ) printf( '(%d rows)', $ret );

		if( $delete_stub_file )
		{ // Delete stub file
			if( $echo ) echo '<br />Trying to delete stub file... ';
			if( ! @unlink( $this->get('dynfilepath') ) )
				if( $echo )
				{
					echo '<span class="error">';
					printf(	T_('ERROR! Could not delete! You will have to delete the file [%s] by hand.'),
									$this->get('dynfilepath') );
					echo '</span>';
				}
			else
				if( $echo ) echo 'OK.';
		}
		if( $delete_static_file )
		{ // Delete static file
			if( $echo ) echo '<br />Trying to delete static file... ';
			if( ! @unlink( $this->get('staticfilepath') ) )
				if( $echo )
				{
					echo '<span class="error">';
					printf(	T_('ERROR! Could not delete! You will have to delete the file [%s] by hand.'),
									$this->get('staticfilepath') );
					echo '</span>';
				}
			else
				if( $echo ) echo 'OK.';
		}

		// Unset cache entry:
		unset( $cache_blogs[$this->ID] );

		// Delete main (blog) object:
		parent::dbdelete();

		echo '<br />Done.</p>';
	}

}

/*
 * $Log$
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.44  2004/10/11 19:22:16  blueyed
 * no message
 *
 * Revision 1.43  2004/10/11 18:44:09  fplanque
 * Edited code documentation.
 *
 * Revision 1.42  2004/10/11 18:40:05  fplanque
 * no message
 *
 * Revision 1.40  2004/10/6 9:37:31  gorgeb
 * Added allowcomments, a per blog setting taking three values : always, post_by_post, never.
 *
 * Revision 1.23  2004/6/8 15:5:45  jeffbearer
 * added msgfromurl display option, to display the messaging from
 */
?>
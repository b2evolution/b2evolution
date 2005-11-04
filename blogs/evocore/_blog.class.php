<?php
/**
 * This file implements the Blog class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by Jason Edgecombe.
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
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Jason EDGECOMBE grants François PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 * @author gorgeb: EPISTEMA (Bertrand Gorge).
 * @author jeffbearer: Jeff BEARER
 * @author edgester: Jason EDGECOMBE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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
	 * @var string Short name for use in navigation menus
	 */
	var $shortname;
	/**
	 * Complete name
	 * @var string Complete name
	 */
	var $name;
	/**
	 * Tagline to be displayed on template
	 * @var string Tagline to be displayed on template
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
	var $allowblogcss = 0;
	var $allowusercss = 0;
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
		global $basepath, $media_subdir;

		// Call parent constructor:
		parent::DataObject( 'T_blogs', 'blog_', 'blog_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_categories', 'fk'=>'cat_blog_ID', 'msg'=>T_('%d related categories') ),
			);

		$this->delete_cascades = array(
				array( 'table'=>'T_coll_user_perms', 'fk'=>'bloguser_blog_ID', 'msg'=>T_('%d user rights defintions') ),
				// b2evo only:
				array( 'table'=>'T_subscriptions', 'fk'=>'sub_coll_ID', 'msg'=>T_('%d subscriptions') ),
			);

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
			$this->allowblogcss = $db_row->blog_allowblogcss;
			$this->allowusercss = $db_row->blog_allowusercss;
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
	 * @return boolean true, if a value has been set; false if it has not changed
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
				return parent::set_param( $parname, 'number', $parvalue );
				break;

			/* fplanque: I'm removing this because it's no good when using absolute URL
			case 'access_type':
				if( $parvalue == 'default' )
				{
					$Settings->set('default_blog_ID', $this->ID);
					$Settings->dbupdate();
				}
			*/

			default:
				return parent::set_param( $parname, 'string', $parvalue );
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
	 * Get the blog's media directory (and create it if necessary).
	 *
	 * @param boolean absolute path or relative to $basepath.$media_subdir.'blogs/' ?
	 */
	function getMediaDir( $absolute = true )
	{
		global $basepath, $media_subdir, $Messages, $Settings, $Debuglog;

		if( ! $Settings->get( 'fm_enable_roots_blog' ) )
		{ // User directories are disabled:
			$Debuglog->add( 'Attempt to access blog media dir, but this feature is globally disabled' );
			return false;
		}

		switch( $this->media_location )
		{
			case 'default':
				$mediadir = $basepath.$media_subdir.'blogs/'.$this->urlname.'/';
				break;

			case 'subdir':
				$mediadir = $basepath.$media_subdir.$this->media_subdir;
				break;

			case 'custom':
				$mediadir = $this->media_fullpath;
				break;

			case 'none':
			default:
				$Debuglog->add( 'Attempt to access blog media dir, but this feature is disabled for this blog', 'files' );
				return false;
		}

		// TODO: use a File object here (to access perms, ..) when FileCache::get_by_path() is provided.
		if( !is_dir( $mediadir ) )
		{
			// TODO: Link to some help page(s) with errors!
			if( !is_writable( dirname($mediadir) ) )
			{ // add error
				$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; cannot be created, because the parent directory &laquo;%s&raquo; is not writable."), $mediadir, dirname($mediadir) ), 'error' );
				return false;
			}
			elseif( !@mkdir( $mediadir ) ) // default chmod?!
			{ // add error
				$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; could not be created."), $mediadir ), 'error' );
				return false;
			}
			else
			{ // add note
				$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; has been created with permissions %s."), $mediadir,
					'777' // FIXME: get perms of the File object - mkdir() does not default to 0777 really.
					), 'success' );
			}
		}

		// fplanque>> TODO: non absolute return IS FLAWED if case != default. DO we need it?
		return $absolute ?
						$mediadir :
						preg_replace( '#^'.$basepath.$media_subdir.'blogs/#', '', $mediadir );
	}


	/**
	 * Get a param
	 *
	 * {@internal Blog::get(-)}}
	 */
	function get( $parname )
	{
		global $xmlsrv_url, $admin_email, $baseurl, $basepath, $media_url, $current_User, $Settings, $Debuglog;

		if ($this->siteurl > '')
		{
			$rss_url =  $this->siteurl;
  		}

		switch( $parname )
		{
			case 'mediadir':
				return $this->getMediaDir();

			case 'mediaurl':
		 		if( ! $Settings->get( 'fm_enable_roots_blog' ) )
				{ // User directories are disabled:
					$Debuglog->add( 'Attempt to access blog media URL, but this feature is disabled', 'files' );
					return false;
				}

				switch( $this->media_location )
				{
					case 'default':
						return $media_url.'blogs/'.$this->urlname.'/';

					case 'subdir':
						return $media_url.$this->media_subdir;
						break;

					case 'custom':
						return $this->media_url;

					case 'none':
					default:
						$Debuglog->add( 'Attempt to access blog media url, but this feature is disabled for this blog', 'files' );
						return false;
				}

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

			case 'siteurl':
				if( preg_match( '#^https?://#', $this->siteurl ) )
				{ // We have a specific URL for this blog:
					return $this->siteurl;
				}
				else
				{ // This blog is located under b2evo's baseurl
					$r = $baseurl;
					if( !empty($this->siteurl) )
					{ // We have a subfolder:
						$r .= $this->siteurl;
					}
					return $r;
				}

			case 'baseurl':
        // Marian moved previous code to case 'siteurl'
				return $baseurl;

			case 'basehost':
				$baseurl = $this->get('baseurl');
				if( preg_match( '#(https?://(.+?)(:.+?)?)/#', $baseurl, $matches ) )
				{
					$baseurlroot = $matches[1];
					// echo "baseurlroot=$baseurlroot <br />";
					$basehost = $matches[2];
					// echo "basehost=$basehost <br />";
				}
				else
				{
					die( 'Your baseurl ('.$baseurl.') set in _config.php seems invalid. You probably missed the "http://" prefix or the trailing slash. Please correct that.' );
				}
				return $basehost;

			case 'cookie_domain':
				$basehost = $this->get('basehost');
				// Note: we need special treatment for localhost!
				return ($basehost == 'localhost') ? '' : '.'. $basehost;

			case 'cookie_path':
				return preg_replace( '#https?://[^/]+#', '', $this->get('baseurl') );

			case 'blogstatsurl':
				return ''; 						// Deprecated!

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
                if ($this->siteurl > '')
                {
    				return $rss_url.'?tempskin=rdf&amp;blog='.$this->ID;
                } else {
    				return $xmlsrv_url.'rdf.php?blog='.$this->ID;
                }

			case 'rss_url':
                if ($this->siteurl > '')
                {
    				return $rss_url.'?tempskin=rss&amp;blog='.$this->ID;
                } else {
    				return $xmlsrv_url.'rss.php?blog='.$this->ID;
			    }

			case 'rss2_url':
                if ($this->siteurl > '')
                {
    				return $rss_url.'?tempskin=rss2&amp;blog='.$this->ID;
                } else {
				    return $xmlsrv_url.'rss2.php?blog='.$this->ID;
				}

			case 'atom_url':
                if ($this->siteurl > '')
                {
    				return $rss_url.'?tempskin=atom&amp;blog='.$this->ID;
                } else {
				    return $xmlsrv_url.'atom.php?blog='.$this->ID;
				}

			case 'comments_rdf_url':
                if ($this->siteurl > '')
                {
    				return $rss_url.'?tempskin=rdf.comments&amp;blog='.$this->ID;
                } else {
				    return $xmlsrv_url.'rdf.comments.php?blog='.$this->ID;
				}

			case 'comments_rss_url':
                if ($this->siteurl > '')
                {
    				return $rss_url.'?tempskin=rss.comments&amp;blog='.$this->ID;
                } else {
				    return $xmlsrv_url.'rss.comments.php?blog='.$this->ID;
				}

			case 'comments_rss2_url':
                if ($this->siteurl > '')
                {
    				return $rss_url.'?tempskin=rss2.comments&amp;blog='.$this->ID;
                } else {
				    return $xmlsrv_url.'rss2.comments.php?blog='.$this->ID;
				}

			case 'comments_atom_url':
                if ($this->siteurl > '')
                {
    				return $rss_url.'?tempskin=atom.comments&amp;blog='.$this->ID;
                } else {
				    return $xmlsrv_url.'atom.comments.php?blog='.$this->ID;
				}

			case 'pingback_url':
                if ($this->siteurl > '')
                {
    				return $rss_url.'?tempskin=xmlrpc';
                } else {
				    return $xmlsrv_url.'xmlrpc.php';
				}

			case 'admin_email':
				return $admin_email;


			/* Add the html for a blog-specified stylesheet
			 * All stylesheets will be included if the blog settings allow it
			 * and the file "style.css" exists. CSS rules say that the latter style sheets can
			 * override earlier stylesheets.
			 */
			case 'blog_css':
				if( $this->allowblogcss
					&& file_exists( $this->get('mediadir').'style.css' ) )
				{
					return '<link rel="stylesheet" href="'.$this->get( 'mediaurl' ).'style.css" type="text/css" />';
				}
				else
				{
					return '';
				}

			/* Add the html for a user-specified stylesheet
			 * All stylesheets will be included if the blog settings allow it
			 * and the file "style.css" exists. CSS rules say that the latter style sheets can
			 * override earlier stylesheets. A user-specified stylesheet will
			 * override a blog-specified stylesheet which will override a skin stylesheet.
			 */
			case 'user_css':
				if( $this->allowusercss
					&& isset( $current_User )
					&& file_exists( $current_User->getMediaDir().'style.css' ) )
				{
					return '<link rel="stylesheet" href="'.$current_User->getMediaUrl().'style.css" type="text/css" />';
				}
				else
				{
					return '';
				}


			default:
				// All other params:
				return parent::get( $parname );
		}
	}


	/**
	 * Delete a blog and dependencies from database
	 *
	 * Includes WAY TOO MANY requests because we try to be compatible with MySQL 3.23, bleh!
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
		if( $echo ) echo '<p>MySQL 3.23 compatibility mode!';

		// Get list of cats that are going to be deleted (3.23)
		if( $echo ) echo '<br />Getting category list to delete... ';
		$cat_list = $DB->get_list( "SELECT cat_ID
																FROM T_categories
																WHERE cat_blog_ID = $this->ID" );

		if( empty( $cat_list ) )
		{ // There are no cats to delete
			echo 'None!';
		}
		else
		{ // Delete the cats & dependencies

			// Get list of posts that are going to be deleted (3.23)
			if( $echo ) echo '<br />Getting post list to delete... ';
			$post_list = $DB->get_list( "SELECT postcat_post_ID
																		FROM T_postcats
																		WHERE postcat_cat_ID IN ($cat_list)" );

			if( empty( $post_list ) )
			{ // There are no posts to delete
				echo 'None!';
			}
			else
			{ // Delete the posts & dependencies

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
															WHERE post_ID IN ($post_list)" );
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
		$ret = $DB->query( "DELETE FROM T_coll_user_perms
												WHERE bloguser_blog_ID = $this->ID" );
		if( $echo ) printf( '(%d rows)', $ret );

		// Delete subscriptions
		if( $echo ) echo '<br />Deleting subscriptions... ';
		$ret = $DB->query( "DELETE FROM T_subscriptions
												WHERE sub_coll_ID = $this->ID" );
		if( $echo ) printf( '(%d rows)', $ret );

		// Delete hitlogs
		if( $echo ) echo '<br />Deleting blog hitlogs... ';
		$ret = $DB->query( "DELETE FROM T_hitlog
												WHERE hit_blog_ID = $this->ID",
												'Deleting blog hitlogs' );
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


	/**
	 * Template function: display name of blog
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function name( $format = 'htmlbody', $disp = true )
	{
		if( $disp )
		{ //the result must be displayed
			$this->disp( 'name', $format );
		}
		else
		{ //the result must be returned
			return $this->dget( 'name', $format );
		}
	}


	/**
	 * Template function: return name of item
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function name_return( $format = 'htmlbody' )
	{
		$r = $this->name( $format, false );
		return $r;
	}

}

/*
 * $Log$
 * Revision 1.37  2005/11/04 13:50:57  blueyed
 * Dataobject::set_param() / set(): return true if a value has been set and false if it did not change. It will not get considered for dbchange() then, too.
 *
 * Revision 1.36  2005/10/31 23:20:45  fplanque
 * keeping things straight...
 *
 * Revision 1.35  2005/10/28 02:37:37  blueyed
 * Normalized AbstractSettings API
 *
 * Revision 1.34  2005/10/27 15:47:25  marian
 * Removed $_SERVER Variables for the multi-domain feature.
 *
 * Revision 1.33  2005/10/19 09:07:15  marian
 * Changes regarding multi-domain feature
 *
 * Revision 1.32  2005/10/18 18:45:58  fplanque
 * some rollbacks...
 *
 * Revision 1.31  2005/10/18 11:04:16  marian
 * Added extra functionality to support multi-domain feature.
 *
 * Revision 1.30  2005/10/14 21:00:08  fplanque
 * Stats & antispam have obviously been modified with ZERO testing.
 * Fixed a sh**load of bugs...
 *
 * Revision 1.29  2005/10/03 18:10:07  fplanque
 * renamed post_ID field
 *
 * Revision 1.28  2005/09/09 19:24:54  fplanque
 * documentation
 *
 * Revision 1.27  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.26  2005/08/26 14:29:29  fplanque
 * fixed cookie domains for localhost (needed to remember the current skin)
 *
 * Revision 1.25  2005/08/24 18:43:09  fplanque
 * Removed public stats to prevent spamfests.
 * Added context browsing to Archives plugin.
 *
 * Revision 1.24  2005/06/17 16:19:24  fplanque
 * doc
 *
 * Revision 1.23  2005/06/16 21:52:10  blueyed
 * mkdir fixed, todos, doc
 *
 * Revision 1.22  2005/06/03 15:12:32  fplanque
 * error/info message cleanup
 *
 * Revision 1.21  2005/06/02 18:50:52  fplanque
 * no message
 *
 * Revision 1.20  2005/05/25 17:13:33  fplanque
 * implemented email notifications on new comments/trackbacks
 *
 * Revision 1.19  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.18  2005/05/11 13:21:38  fplanque
 * allow disabling of mediua dir for specific blogs
 *
 * Revision 1.17  2005/05/06 20:04:48  fplanque
 * added contribs
 * fixed filemanager settings
 *
 * Revision 1.16  2005/03/16 19:58:23  fplanque
 * small AdminUI cleanup tasks
 *
 * Revision 1.15  2005/03/08 13:24:41  fplanque
 * minor
 *
 * Revision 1.14  2005/03/08 02:11:30  edgester
 * Refactored php code in custom skin into a skiin tag as per Francois Planque.
 * Added skin tag to all of the skins.
 *
 * Revision 1.13  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.12  2005/02/24 23:26:15  blueyed
 * accidently removed class member definition
 *
 * Revision 1.11  2005/02/24 22:17:46  edgester
 * Added a blog option to allow for a CSS file in the blog media dir to override the skin stylesheet.
 * Added a second blog option to allow for a user CSS file to  override the skin and blog stylesheets.
 *
 * Revision 1.10  2005/02/18 19:16:14  fplanque
 * started relation restriction/cascading handling
 *
 * Revision 1.9  2005/01/06 15:45:35  blueyed
 * Fixes..
 *
 * Revision 1.8  2005/01/06 10:15:45  blueyed
 * FM upload and refactoring
 *
 * Revision 1.7  2005/01/05 03:23:02  blueyed
 * fixed gen_mediadir()
 *
 * Revision 1.6  2005/01/05 02:50:45  blueyed
 * Message changed
 *
 * Revision 1.5  2004/12/21 21:22:46  fplanque
 * factoring/cleanup
 *
 * Revision 1.4  2004/12/15 20:50:33  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.3  2004/11/09 00:25:11  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.2  2004/10/14 18:31:24  blueyed
 * granting copyright
 *
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
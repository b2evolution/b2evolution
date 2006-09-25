<?php
/**
 * This file implements the Blog class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by Jason Edgecombe.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Jason EDGECOMBE grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 * @author jeffbearer: Jeff BEARER
 * @author edgester: Jason EDGECOMBE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobject.class.php';

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

	/*
   * ?> TODO: we should have an extra DB column that either defines type of blog_siteurl
   * OR split blog_siteurl into blog_siteurl_abs and blog_siteurl_rel (where blog_siteurl_rel could be "blog_sitepath")
   */
	var $siteurl;
	var $staticfilename;
	var $stub;     // stub file (can be empty/virtual)
	var $urlname;  // used to identify blog in URLs
	var $links_blog_ID = 0;
	var $notes;
	var $keywords;
	var $allowcomments = 'post_by_post';
	var $allowtrackbacks = 0;
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
	 * Additional settings for the collection
	 *
	 * Any non vital params should go into there (this includes many of the above).
	 *
	 * @var CollectionSettings lazy filled
	 */
	var $CollectionSettings;


	/**
	 * Constructor
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
			$this->set( 'shortname', T_('New blog') );
			$this->set( 'name', T_('New weblog') );
			$this->set( 'locale', $default_locale );
			$this->set( 'access_type', 'index.php' );
			$this->set( 'urlname', 'new' );
			$this->set( 'default_skin', 'basic' );
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
	 * Load data from Request form fields.
	 *
	 * @param array groups of params to load
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request( $groups = array() )
	{
		global $Messages, $default_locale, $DB;

		if( param( 'blog_name', 'string', NULL ) !== NULL )
		{ // General params:
			$this->set_from_Request( 'name' );
			$this->set( 'shortname',     param( 'blog_shortname',     'string', true ) );
			$this->set( 'locale',        param( 'blog_locale',        'string', $default_locale ) );
		}


		if( param( 'blog_urlname',   'string', NULL ) !== NULL )
		{	// check urlname
			if( param_check_not_empty( 'blog_urlname', T_('You must provide an URL blog name!') ) )
			{
				$this->set_from_Request( 'urlname' );

				if( ! preg_match( '|^[A-Za-z0-9\-]+$|', $this->urlname ) )
				{
					param_error( 'blog_urlname', T_('The url name is invalid.') );
				}

				if( $DB->get_var( 'SELECT COUNT(*)
														 FROM T_blogs
														WHERE blog_urlname = '.$DB->quote($this->get( 'urlname' )).'
														  AND blog_ID <> '.$this->ID
														) )
				{ // urlname is already in use
					param_error( 'blog_urlname', T_('This URL name is already in use by another blog. Please choose another name.') );
				}
			}
		}


		if( ($siteurl_type = param( 'blog_siteurl_type',   'string', NULL )) !== NULL )
		{ // Blog URL parameters:
			// TODO: we should have an extra DB column that either defines type of blog_siteurl OR split blog_siteurl into blog_siteurl_abs and blog_siteurl_rel (where blog_siteurl_rel could be "blog_sitepath")
			$blog_siteurl_relative = param( 'blog_siteurl_relative', 'string', true );
			$blog_siteurl_absolute = param( 'blog_siteurl_absolute', 'string', true );

			if( $siteurl_type == 'absolute' )
			{
				$blog_siteurl = $blog_siteurl_absolute;
				if( !preg_match( '#^https?://.+#', $blog_siteurl ) )
				{
					$Messages->add( T_('Blog Folder URL').': '
													.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>)!'), 'error' );
				}
			}
			else
			{ // relative siteurl
				$blog_siteurl = $blog_siteurl_relative;
				if( preg_match( '#^https?://#', $blog_siteurl ) )
				{
					$Messages->add( T_('Blog Folder URL').': '
													.T_('You must provide a relative URL (without <code>http://</code> or <code>https://</code>)!'), 'error' );
				}
			}
			$this->set( 'siteurl', $blog_siteurl );


			// Preferred access type:
			$this->set( 'access_type',   param( 'blog_access_type',   'string', true ) );
			$this->set( 'stub',          param( 'blog_stub',          'string', true ) );

			// TODO: change * to +
			// dh> Why? Will there be another way to have no/an empty stub? fp> yes, it's in a TODO somewhere (on the form?)
			if( ! preg_match( '|^[A-Za-z0-9\-]*$|', $this->urlname ) )
			{
				param_error( 'blog_stub', T_('The stub name is invalid.') );
			}

			if( $this->access_type == 'stub' )
			{	// fp> If there is a case to leave this blank, comment this out and explain the case. Thanks.
				// dh> I'm using it with "absolute URL" to have no "stub file" at all..
				// param_check_not_empty( 'blog_stub', T_('You must provide a stub file name, e-g: a_stub.php') );
			}
		}


		if( param( 'chapter_links',   'string', NULL ) !== NULL )
		{ // Feedback options:
			$this->set_setting( 'chapter_links', get_param( 'chapter_links' ) );
		}

		if( param( 'blog_default_skin',  'string', NULL ) !== NULL )
		{	// Default blog:
			$this->set_from_Request( 'default_skin' );
		}


		if( param( 'blog_links_blog_ID',  'integer', -1 ) != -1 )
		{	// Default display options:
			$this->set_from_Request( 'links_blog_ID' );

			// checkboxes (will not get send, if unchecked)
			$this->set( 'force_skin',  1-param( 'blog_force_skin',    'integer', 0 ) );
			$this->set( 'allowblogcss', param( 'blog_allowblogcss', 'integer', 0 ) );
			$this->set( 'allowusercss', param( 'blog_allowusercss', 'integer', 0 ) );
			$this->set( 'disp_bloglist', param( 'blog_disp_bloglist', 'integer', 0 ) );
			$this->set( 'in_bloglist',   param( 'blog_in_bloglist',   'integer', 0 ) );
		}


		if( param( 'blog_description', 'string', NULL ) !== NULL )
		{	// Description:
			$this->set_from_Request( 'shortdesc', 'blog_description' );
		}

		if( param( 'blog_keywords', 'string', NULL ) !== NULL )
		{	// Keywords:
			$this->set_from_Request( 'keywords' );
		}

		if( param( 'blog_tagline',   'html', NULL ) !== NULL )
		{	// HTML tagline:
			$this->set( 'tagline', format_to_post( get_param( 'blog_tagline' ), 0, 0 ) );
		}

		if( param( 'blog_longdesc',   'html', NULL ) !== NULL )
		{	// HTML long description:
			$this->set( 'longdesc', format_to_post( get_param( 'blog_longdesc' ), 0, 0 ) );
		}

		if( param( 'blog_notes',   'html', NULL ) !== NULL )
		{	// HTML notes:
			$this->set( 'notes', format_to_post( get_param( 'blog_notes' ), 0, 0 ) );
		}


		if( param( 'blog_staticfilename', 'string', NULL ) !== NULL )
		{	// Static file:
			$this->set_from_Request( 'staticfilename' );
		}

		if( param( 'blog_media_location',  'string', NULL ) !== NULL )
		{	// Media files location:
			$this->set_from_Request( 'media_location' );
			$this->setMediaSubDir(    param( 'blog_media_subdir',    'string', '' ) );
			$this->setMediaFullPath(  param( 'blog_media_fullpath',  'string', '' ) );
			$this->setMediaUrl(       param( 'blog_media_url',       'string', '' ) );

			// check params
			switch( $this->get( 'media_location' ) )
			{
				case 'custom': // custom path and URL
					if( $this->get( 'media_fullpath' ) == '' )
					{
						param_error( 'blog_media_fullpath', T_('Media dir location').': '.T_('You must provide the full path of the media directory.') );
					}
					if( !preg_match( '#https?://#', $this->get( 'media_url' ) ) )
					{
						param_error( 'blog_media_url', T_('Media dir location').': '
														.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>)!') );
					}
					break;

				case 'subdir':
					if( $this->get( 'media_subdir' ) == '' )
					{
						param_error( 'blog_media_subdir', T_('Media dir location').': '.T_('You must provide the media subdirectory.') );
					}
					break;
			}
		}

		if( in_array( 'pings', $groups ) )
		{ // we want to load the ping checkboxes:
			$this->set( 'pingb2evonet',    param( 'blog_pingb2evonet',    'integer', 0 ) );
			$this->set( 'pingtechnorati',  param( 'blog_pingtechnorati',  'integer', 0 ) );
			$this->set( 'pingweblogs',     param( 'blog_pingweblogs',     'integer', 0 ) );
			$this->set( 'pingblodotgs',    param( 'blog_pingblodotgs',    'integer', 0 ) );
		}

		if( param( 'blog_allowcomments',   'string', NULL ) !== NULL )
		{ // Feedback options:
			$this->set_from_Request( 'allowcomments' );
			$this->set_setting( 'new_feedback_status',  param( 'new_feedback_status', 'string', 'draft' ) );
			$this->set( 'allowtrackbacks', param( 'blog_allowtrackbacks', 'integer', 0 ) );
		}

		return ! param_errors_detected();

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

			case 'shortdesc':
				$this->shortdesc = $parvalue;
				return parent::set_param( 'description', 'string', $parvalue );
				break;

			default:
				return parent::set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 * Generate blog URL
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
				if( $Settings->get('links_extrapath') != 'disabled' )
				{	// We want to use extra path info, use the blog stub:
					// fp> this seems odd actually. Shouldn't it be the urlname here???
					return $base.'index.php/'.$this->stub;
				}
				else
				{	// Extra path is disabled, use the blog param:
					return $base.'index.php?blog='.$this->ID;
				}

			case 'stub':
				// Access through stub file
				$blogurl = $base;
				if( !empty($this->stub) )
				{
					$blogurl .= $this->stub;
				}
				if( ($type == 'dynamic') && !( preg_match( '#.php$#', $blogurl ) ) )
				{ // We want to force the dynamic page but the URL is not explicitly dynamic
					// This is needed when a static page is taking control of domain.com/stub and we want an explicit link to the LATEST content, which can only be gotten at domain.com/stub.php
					// fp> This creates a small problem with empty stubs (domain.com/.php). This should be fixed by using a fourth blog_access_type: default, index.php, stub, *default_stub* . Consequence: require the stub fied on blog properties form when stub mode is selected
					$blogurl .= '.php';
				}
				return $blogurl;

				// fp> TODO: default_stub: return $base  (to be checked)

			default:
				debug_die( 'Unhandled Blog access type ['.$this->access_type.']' );
		}
	}


	/**
	 * Get the blog's media directory (and create it if necessary).
	 *
	 * If we're {@link is_admin_page() on an admin page}, it adds status messages.
	 * @todo These status messages should rather go to a "syslog" and not be displayed to a normal user
	 *
	 * @return mixed the path as string on success, false if the dir could not be created
	 */
	function get_media_dir()
	{
		global $basepath, $media_subdir, $Messages, $Settings, $Debuglog;

		if( ! $Settings->get( 'fm_enable_roots_blog' ) )
		{ // User directories are disabled:
			$Debuglog->add( 'Attempt to access blog media dir, but this feature is globally disabled', 'files' );
			return false;
		}

		switch( $this->media_location )
		{
			case 'default':
				$mediadir = get_ads_canonical_path( $basepath.$media_subdir.'blogs/'.$this->urlname.'/' );
				break;

			case 'subdir':
				$mediadir = get_ads_canonical_path( $basepath.$media_subdir.$this->media_subdir );
				break;

			case 'custom':
				$mediadir = get_ads_canonical_path( $this->media_fullpath );
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
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; could not be created, because the parent directory is not writable or does not exist."), rel_path_to_base($mediadir) ), 'error' );
				}
				return false;
			}
			elseif( !@mkdir( $mediadir ) )
			{ // add error
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; could not be created."), rel_path_to_base($mediadir) ), 'error' );
				}
				return false;
			}
			else
			{ // chmod and add note:
				$chmod = $Settings->get('fm_default_chmod_dir');
				if( !empty($chmod) )
				{
					@chmod( $mediadir, octdec($chmod) );
				}
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; has been created with permissions %s."), rel_path_to_base($mediadir), substr( sprintf('%o', fileperms($mediadir)), -3 ) ), 'success' );
				}
			}
		}

		return $mediadir;
	}


	/**
	 * Get the URL to the media folder
	 *
	 * @return string the URL
	 */
	function get_media_url()
	{
		global $media_url, $Settings, $Debuglog;

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
	}


	/**
	 * Get a param.
	 *
	 * @return false|string The value as string or false in case of error (e.g. media dir is disabled).
	 */
	function get( $parname )
	{
		global $xmlsrv_url, $admin_email, $baseurl, $basepath, $media_url, $current_User, $Settings, $Debuglog;

		switch( $parname )
		{
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
				$r = $basepath.$this->siteurl;
				if( ! empty($this->stub) )
				{ // $stub can actually be empty/virtual - not a real php file!
					$r .= $this->stub.( preg_match( '#.php$#', $this->stub ) ? '' : '.php' );
				}
				// TODO: check if the path exists and return false otherwise?!
				return $r;

			case 'staticfilepath':
				return $basepath.$this->siteurl.$this->staticfilename;

			case 'baseurl':
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

			case 'baseurlroot':
				if( preg_match( '#(https?://(.+?)(:.+?)?)/#', $this->get('baseurl'), $matches ) )
				{
					// TODO: shouldn't that include a trailing slash?:
					return $matches[1];
				}

				debug_die( 'Blog::get(baseurl)/baseurlroot - assertion failed.' );

			case 'basehost':
				if( preg_match( '#(https?://(.+?)(:.+?)?)/#', $this->get('baseurl'), $matches ) )
				{
					return $matches[2];
				}

				debug_die( 'Blog::get(baseurl)/basehost - assertion failed.' );

			case 'cookie_domain':
				$basehost = $this->get('basehost');
				// Note: we need special treatment for hosts without dots (otherwise cookies won't get set!)
				return ( strpos( $basehost, '.' ) === false ) ? '' : '.'. $basehost;

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

			case 'rdf_url':
				return url_add_param( $this->gen_blogurl( 'default' ), 'tempskin=_rdf' );

			case 'rss_url':
				return url_add_param( $this->gen_blogurl( 'default' ), 'tempskin=_rss' );

			case 'rss2_url':
				return url_add_param( $this->gen_blogurl( 'default' ), 'tempskin=_rss2' );

			case 'atom_url':
				return url_add_param( $this->gen_blogurl( 'default' ), 'tempskin=_atom' );

			case 'comments_rdf_url':
				return url_add_param( $this->gen_blogurl( 'default' ), 'tempskin=_rdf&amp;disp=comments' );

			case 'comments_rss_url':
				return url_add_param( $this->gen_blogurl( 'default' ), 'tempskin=_rss&amp;disp=comments' );

			case 'comments_rss2_url':
				return url_add_param( $this->gen_blogurl( 'default' ), 'tempskin=_rss2&amp;disp=comments' );

			case 'comments_atom_url':
				return url_add_param( $this->gen_blogurl( 'default' ), 'tempskin=_atom&amp;disp=comments' );

			case 'admin_email':
				return $admin_email;


			/* Add the html for a blog-specified stylesheet
			 * All stylesheets will be included if the blog settings allow it
			 * and the file "style.css" exists. CSS rules say that the latter style sheets can
			 * override earlier stylesheets.
			 */
			case 'blog_css':
				if( $this->allowblogcss
					&& file_exists( $this->get_media_dir().'style.css' ) )
				{
					return '<link rel="stylesheet" href="'.$this->get_media_url().'style.css" type="text/css" />';
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
					&& file_exists( $current_User->get_media_dir().'style.css' ) )
				{
					return '<link rel="stylesheet" href="'.$current_User->get_media_url().'style.css" type="text/css" />';
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
	 * Get a setting.
	 *
	 * @return string|false|NULL value as string on success; NULL if not found; false in case of error
	 */
	function get_setting( $parname )
	{
		$this->load_CollectionSettings();

		return $this->CollectionSettings->get( $this->ID, $parname );
	}


 	/**
	 * Set a setting.
	 *
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function set_setting( $parname, $value )
	{
		$this->load_CollectionSettings();

		return $this->CollectionSettings->set( $this->ID, $parname, $value );
	}


	function load_CollectionSettings()
	{
		if( ! isset( $this->CollectionSettings ) )
		{
			require_once dirname(__FILE__).'/_collsettings.class.php';
			$this->CollectionSettings = new CollectionSettings(); // COPY (function)
		}
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate()
	{
		global $DB;

		$DB->begin();

		parent::dbupdate();

		if( isset( $this->CollectionSettings ) )
		{
			$this->CollectionSettings->dbupdate();
		}

		$DB->commit();
	}


	/**
	 * Delete a blog and dependencies from database
	 *
	 * Includes WAY TOO MANY requests because we try to be compatible with MySQL 3.23, bleh!
	 *
	 * @param boolean true if you want to try to delete the stub file
	 * @param boolean true if you want to try to delete the static file
	 * @param boolean true if you want to echo progress
	 */
	function dbdelete( $delete_stub_file = false, $delete_static_file = false, $echo = false )
	{
		global $DB, $cache_blogs, $Messages;

		// Note: No need to localize the status messages...
		if( $echo ) echo '<p>MySQL 3.23 compatibility mode!';

		// Get list of cats that are going to be deleted (3.23)
		if( $echo ) echo '<br />Getting category list to delete... ';
		$cat_list = $DB->get_list( "SELECT cat_ID
																FROM T_categories
																WHERE cat_blog_ID = $this->ID" );

		if( empty( $cat_list ) )
		{ // There are no cats to delete
			if( $echo ) echo 'None!';
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
				if( $echo ) echo 'None!';
			}
			else
			{ // Delete the posts & dependencies

				// TODO: There's also a constraint FK_post_parent_ID..

				// Delete postcats
				if( $echo ) echo '<br />Deleting post-categories... ';
				$ret = $DB->query(	"DELETE FROM T_postcats
															WHERE postcat_cat_ID IN ($cat_list)" );
				if( $echo ) printf( '(%d rows)', $ret );
				$Messages->add( T_('Deleted post-categories'), 'success' );

				// Delete comments
				if( $echo ) echo '<br />Deleting comments on blog\'s posts... ';
				$ret = $DB->query( "DELETE FROM T_comments
														WHERE comment_post_ID IN ($post_list)" );
				if( $echo ) printf( '(%d rows)', $ret );
				$Messages->add( T_('Deleted comments on blog\'s posts'), 'success' );


				// Delete posts
				if( $echo ) echo '<br />Deleting blog\'s posts... ';
				$ret = $DB->query(	"DELETE FROM T_posts
															WHERE post_ID IN ($post_list)" );
				if( $echo ) printf( '(%d rows)', $ret );
				$Messages->add( T_('Deleted blog\'s posts'), 'success' );

			} // / are there posts?

			// Delete categories
			if( $echo ) echo '<br />Deleting blog\'s categories... ';
			$ret = $DB->query( "DELETE FROM T_categories
													WHERE cat_blog_ID = $this->ID" );
			if( $echo ) printf( '(%d rows)', $ret );
			$Messages->add( T_('Deleted blog\'s categories'), 'success' );

		} // / are there cats?

		// Delete blogusers
		if( $echo ) echo '<br />Deleting user-blog permissions... ';
		$ret = $DB->query( "DELETE FROM T_coll_user_perms
												WHERE bloguser_blog_ID = $this->ID" );
		if( $echo ) printf( '(%d rows)', $ret );
		$Messages->add( T_('Deleted blog\'s user permissions'), 'success' );

		// Delete bloggroups
		if( $echo ) echo '<br />Deleting group-blog permissions... ';
		$ret = $DB->query( "DELETE FROM T_coll_group_perms
												WHERE bloggroup_blog_ID = $this->ID" );
		if( $echo ) printf( '(%d rows)', $ret );
		$Messages->add( T_('Deleted blog\'s group permissions'), 'success' );

		// Delete subscriptions
		if( $echo ) echo '<br />Deleting subscriptions... ';
		$ret = $DB->query( "DELETE FROM T_subscriptions
												WHERE sub_coll_ID = $this->ID" );
		if( $echo ) printf( '(%d rows)', $ret );
		$Messages->add( T_('Deleted blog\'s subscriptions'), 'success' );

		// Delete hitlogs
		if( $echo ) echo '<br />Deleting blog hitlogs... ';
		$ret = $DB->query( "DELETE FROM T_hitlog
												WHERE hit_blog_ID = $this->ID",
												'Deleting blog hitlogs' );
		if( $echo ) printf( '(%d rows)', $ret );
		$Messages->add( T_('Deleted blog\'s hitlogs'), 'success' );

		if( $delete_stub_file )
		{ // Delete stub file
			if( $echo ) echo '<br />Trying to delete stub file... ';
			if( ! @unlink( $this->get('dynfilepath') ) )
			{
				if( $echo )
				{
					echo '<span class="error">';
					printf(	T_('ERROR! Could not delete! You will have to delete the file [%s] by hand.'),
									$this->get('dynfilepath') );
					echo '</span>';
				}
				$Messages->add( sprintf( T_('Could not delete stub file [%s]'), $this->get('dynfilepath') ), 'error' );
			}
			else
			{
				if( $echo ) echo 'OK.';
				$Messages->add( T_('Deleted blog\'s stub file'), 'success' );
			}
		}
		if( $delete_static_file )
		{ // Delete static file
			if( $echo ) echo '<br />Trying to delete static file... ';
			if( ! @unlink( $this->get('staticfilepath') ) )
			{
				if( $echo )
				{
					echo '<span class="error">';
					printf(	T_('ERROR! Could not delete! You will have to delete the file [%s] by hand.'),
									$this->get('staticfilepath') );
					echo '</span>';
				}
				$Messages->add( sprintf( T_('Could not delete static file [%s]'), $this->get('staticfilepath') ), 'error' );
			}
			else
			{
				if( $echo ) echo 'OK.';
				$Messages->add( T_('Deleted blog\'s static file'), 'success' );
			}
		}

		// Unset cache entry:
		unset( $cache_blogs[$this->ID] );

		// Delete main (blog) object:
		parent::dbdelete();

		if( $echo ) echo '<br />Done.</p>';
	}


	/**
	 * Template function: display name of blog
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function name( $format = 'htmlbody' )
	{
		$this->disp( 'name', $format );
	}


	/**
	 * Get the name of the blog
	 *
	 * @return string
	 */
	function get_name()
	{
		return $this->name;
	}

}

/*
 * $Log$
 * Revision 1.27  2006/09/25 17:53:07  blueyed
 * Add attempt to access globally disabled media_dir to "files" Debuglog category (instead of "notes")
 *
 * Revision 1.26  2006/09/11 22:29:19  fplanque
 * chapter cleanup
 *
 * Revision 1.25  2006/09/11 22:06:08  blueyed
 * Cleaned up option_list callback handling
 *
 * Revision 1.24  2006/09/11 20:53:33  fplanque
 * clean chapter paths with decoding, finally :)
 *
 * Revision 1.23  2006/09/11 19:36:58  fplanque
 * blog url ui refactoring
 *
 * Revision 1.22  2006/09/10 20:59:18  fplanque
 * extended extra path info setting
 *
 * Revision 1.21  2006/09/10 19:32:32  fplanque
 * completed chapter URL name editing
 *
 * Revision 1.20  2006/09/10 14:50:48  fplanque
 * minor / doc
 *
 * Revision 1.19  2006/09/10 13:46:43  blueyed
 * Removed explicit ".php" extension for "dynamic" type urls.
 *
 * Revision 1.18  2006/09/05 19:08:43  fplanque
 * minor
 *
 * Revision 1.17  2006/08/21 16:07:43  fplanque
 * refactoring
 *
 * Revision 1.16  2006/08/20 22:25:21  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.15  2006/08/20 20:12:32  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.14  2006/08/19 02:15:06  fplanque
 * Half kille dthe pingbacks
 * Still supported in DB in case someone wants to write a plugin.
 *
 * Revision 1.13  2006/08/18 23:23:03  blueyed
 * Allow empty stub files.. +whitespace
 *
 * Revision 1.12  2006/08/18 18:29:37  fplanque
 * Blog parameters reorganization + refactoring
 *
 * Revision 1.11  2006/08/18 17:23:58  fplanque
 * Visual skin selector
 *
 * Revision 1.10  2006/08/18 00:40:35  fplanque
 * Half way through a clean blog management - too tired to continue
 * Should be working.
 *
 * Revision 1.9  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.8  2006/06/05 13:43:53  blueyed
 * todo questions
 *
 * Revision 1.7  2006/04/20 16:31:30  fplanque
 * comment moderation (finished for 1.8)
 *
 * Revision 1.6  2006/04/19 22:39:08  blueyed
 * Only add status messages about media_dir creation if on an admin page.
 *
 * Revision 1.5  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.4  2006/04/13 00:29:32  blueyed
 * cleanup
 *
 * Revision 1.3  2006/03/16 19:26:04  fplanque
 * Fixed & simplified media dirs out of web root.
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.51  2006/01/25 19:19:17  blueyed
 * Fixes for blogurl handling. Thanks to BenFranske for pointing out the biggest issue (http://forums.b2evolution.net/viewtopic.php?t=6844)
 *
 * Revision 1.50  2006/01/09 19:11:14  blueyed
 * User/Blog media dir creation messages more verbose/secure.
 */
?>
<?php
/**
 * This file implements the Admin UI class.
 * Admin skins should derive from this class and override {@link get_template()}
 * for example.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 *
 * @todo dh> Refactor to allow easier contributions!
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( '_core/ui/_menu.class.php', 'Menu' );


/**
 * The general Admin UI class. It provides functions to handle the UI part of the
 * Backoffice.
 *
 * Admin skins should derive from this class and override {@link get_template()}
 * for example.
 *
 * @package admin
 * @todo CODE DOCUMENTATION!!!
 */
class AdminUI_general extends Menu
{
	/**
	 * Visual path seperator (used in html title, ..)
	 *
	 * @var string
	 */
	var $pathSeparator = ' &rsaquo; ';

	/*-------------------------------------------------------------------*/
	/*- The members below should not get overridden in a derived class. -*/

	/**
	 * The path of the current selected menu entry.
	 * Array of strings.
	 * The top level entry is at position 0. Selected submenu entries follow.
	 *
	 * Use {@link get_path()} or {@link get_path_range()} to access it.
	 * Use {@link set_path()}, {@link append_path_level()} or {@link set_path_level()} to set it.
	 *
	 * @access protected
	 * @var array
	 */
	var $path = array();

	/**
	 * The properties of the path entries.
	 * Numbered Array of arrays.
	 * The top level entry is at position 0. Selected submenu entries follow.
	 *
	 * Use {@link get_prop_for_path()} or {@link get_properties_for_path()} to access it
	 * Use {@link set_path()}, {@link append_path_level()} or {@link set_path_level()} to set it.
	 *
	 * @access protected
	 * @var array
	 */
	var $pathProps = array();

	/**
	 * The explicit title for the page.
	 * @var string
	 */
	var $title;

	/**
	 * The explicit title for the titlearea (<h1>).
	 * @var string
	 */
	var $title_titlearea;
	var $title_titlearea_appendix = '';

	/**
	 * Collection List buttons: title for 'all' button
	 * @var string
	 */
	var $coll_list_all_title = NULL;
	/**
	 * Collection List buttons: url for 'all' button
	 * @var string
	 */
	var $coll_list_all_url = '';
	/**
	 * Collection List buttons: permission name to test on to decide wether or not to display buttons
	 * @var string
	 */
	var $coll_list_permname = NULL;
	/**
	 * Collection List buttons: minimum level required to display button
	 * @var mixed
	 */
	var $coll_list_permlevel = 1;
	/**
	 * Collection List buttons: params of the url used for the buttons
	 * @var array
	 */
	var $coll_list_url_params = array();
	/**
	 * Collection List buttons: javascript to execute on click of a button
	 * @var string
	 */
	var $coll_list_onclick = NULL;


	/**
	 * Bread crumb path
	 *
	 * Note: These are not real breadcrumbs. It's just "so to speak" for a hierarchical path.
	 */
	var $breadcrumbpath = array();


	/**
	 * Titles of bread crumb paths
	 *
	 * Used to build a html <title> tag
	 */
	var $breadcrumb_titles = array();

	/**
	 * Manual link for entire pages, used to get a big scope describing functionalities.
	 *
	 */
	var $page_manual_link = '';

	/**
	 * Constructor.
	 */
	function __construct()
	{
		global $mode; // TODO: make it a real property
		global $baseurl;

		$this->mode = $mode;

		$this->init_templates();
	}


	/**
	 * This function should init the templates - like adding Javascript through the {@link add_headline()} method.
	 */
	function init_templates()
	{
		global $Hit, $check_browser_version;

		require_js( '#jquery#', 'rsc_url' );
		require_js( 'jquery/jquery.raty.min.js', 'rsc_url' );

		// Load general JS file:
		require_js( 'build/evo_backoffice.bmin.js', 'rsc_url' );

		if( $check_browser_version && $Hit->get_browser_version() > 0 && $Hit->is_IE( 9, '<' ) )
		{	// Display info message if browser IE < 9 version and it is allowed by config var:
			global $Messages, $debug;
			$Messages->add( T_('Your web browser is too old. For this site to work correctly, we recommend you use a more recent browser.'), 'note' );
			if( $debug )
			{
				$Messages->add( 'User Agent: '.$Hit->get_user_agent(), 'note' );
			}
		}
	}

	/**
	* Note: These are not real breadcrumbs. It's just "so to speak" for a hierarchical path.
	*
	* @param boolean Add blog path
	* @param array Additional path: @see breadcrumbpath_add()
	* 		'text'
	* 		'url'
	* 		'help'  = NULL
	* 		'attrs' = ''
	*/
	function breadcrumbpath_init( $add_blog = true, $additional_path = array() )
	{
		global $Collection, $Blog, $Settings, $admin_url;

		// Path to site root
		$site_style = $Settings->get( 'site_color' ) != '' ? 'style="color:'.$Settings->get( 'site_color' ).'"' : '';
		$this->breadcrumbpath_add( $Settings->get( 'site_code' ), $admin_url.'?ctrl=dashboard', NULL, $site_style );

		if( !empty( $additional_path ) )
		{ // Additional path
			$path = array_merge( array(
					'text'  => '',
					'url'   => '',
					'help'  => NULL,
					'attrs' => '',
				), $additional_path );
			$this->breadcrumbpath_add( $path['text'], $path['url'], $path['help'], $path['attrs'] );
			$blog_url = $path['url'];
		}

		if( $add_blog && isset( $Blog ) )
		{ // Add path to Blog
			$this->breadcrumbpath_add( $Blog->dget('shortname'), !empty( $blog_url ) ? $blog_url : $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog=$blog$' );
		}

		// Initialize the default manual link, this is always visible when explicit manual link is not set for a page
		$this->page_manual_link = get_manual_link( '', NULL, T_('View manual'), 5 );
	}

	/**
	* Note: These are not real breadcrumbs. It's just "so to speak" for a hierarchical path.
	*
	* @param string Text
	* @param string Url
	* @param string Title for help
	* @param string Additional attributes for link tag
	*/
	function breadcrumbpath_add( $text, $url, $help = NULL, $attrs = '' )
	{
		global $Collection, $Blog, $current_User;

		$blog_ID = isset($Blog) ? $Blog->ID : 0;
		$url = str_replace( '$blog$', $blog_ID, $url );

		$html = $text;
		if( $current_User->check_perm( 'admin', 'normal' ) )
		{
			$html = '<a href="'.$url.'"'.( !empty( $attrs ) ? ' '.$attrs : '' ).'>'.$text.'</a>';
		}

		if( !empty($help) )
		{
			$html .= ' <abbr title="'.$help.'">?</abbr>';
		}

		$this->breadcrumbpath[] = $html;
		$this->breadcrumb_titles[] = strip_tags( $text );
	}

	/**
	 * Adds a manual link to the entire page right after the breadcrumb
	 *
	 * @param string Topic to the manual page
	 */
	function set_page_manual_link( $topic )
	{
		$this->page_manual_link = get_manual_link( $topic, NULL, T_('Manual page'), 5 );
	}

	/**
	 * Get breadcrumb path in html format
	 *
	 * @param array Params
	 * @return string Breadcrumb path links
	 */
	function breadcrumbpath_get_html( $params = array() )
	{
		$params = array_merge( array(
				'before'     => '<div class="breadcrumbpath floatleft">',
				'after'      => '</div><div class="breadcrumbpath floatright">'.$this->page_manual_link.'</div><div class="clear"></div>'."\n",
				'beforeText' => '&bull; <strong>'.T_('You are here').':</strong> ',
				'beforeEach' => '',
				'afterEach'  => '',
				'beforeSel'  => '<strong>',
				'afterSel'   => '</strong>',
				'separator'  => ' &gt; ',
			), $params );

		$r = '';

		if( $count = count( $this->breadcrumbpath ) )
		{
			$r = $params['before'].$params['beforeText'];

			for( $i=0; $i<$count-1; $i++ )
			{
				$r .= $params['beforeEach']
						.$this->breadcrumbpath[$i]
						.$params['separator']
					.$params['afterEach'];
			}

			$r .= $params['beforeSel'].$this->breadcrumbpath[$i].$params['afterSel'];

			$r .= $params['after'];
		}

		return $r;
	}

	/**
	 * Add menu entries to the beginning of the list for given path.
	 *
	 * @param NULL|string|array The path to add the entries to.
	 * @param array Menu entries to add (key (string) => entry (array)).
	 * @uses add_menu_entries()
	 */
	function unshift_menu_entries( $path, $entries )
	{
		// Get a reference to the node in the menu list.
		$node = & $this->get_node_by_path( $path, true );

		$node['entries'] = array_reverse( $node['entries'] );

		$this->add_menu_entries( $path, $entries );

		$node['entries'] = array_reverse( $node['entries'] );
	}


	/**
	 * Get the <title> of the page.
	 *
	 * This is either {@link $title} or will be constructed from title/text properties
	 * of the path entries.
	 *
	 * @param boolean If true, the fallback will be in reversed order
	 * @return string
	 */
	function get_title( $reversedDefault = false )
	{
		if( isset( $this->title ) )
		{	// Explicit title has been set:
			return $this->title;
		}
		else
		{	// Fallback: implode title/text properties of the path
			/*$titles = $this->get_properties_for_path( $this->path, array( 'title', 'text' ) );
			if( $reversedDefault )
			{ // We have asked for reverse order of the path elements:
				$titles = array_reverse($titles);
			}*/
			$titles = $this->breadcrumb_titles;
			if( count( $titles ) > 1 )
			{	// Remove 'Dashboard' text from the title
				array_shift( $titles );
			}
			return implode( ' &gt; ', $titles );
		}
	}


	/**
	 * Get the title for the titlearea (<h1>).
	 *
	 * This is the current path in the site structure
	 *
	 * @return string
	 */
	function get_title_for_titlearea()
	{
		if( ! isset( $this->title_titlearea ) )
		{ // Construct path:
			$titles = array();
			foreach( $this->path as $i => $lPath )
			{
				if( false !== ($title_text = $this->get_prop_for_path( $i, array( 'title', 'text' ) )) )
				{
					$titles[] = '<a href="'.$this->get_prop_for_path( $i, array( 'href' ) ).'">'.$title_text.'</a>';
				}
			}

			$this->title_titlearea = implode( $this->pathSeparator, $titles );
		}

		return $this->title_titlearea.$this->title_titlearea_appendix;
	}


	/**
	 * Append a string at the end of the existing titlearea.
	 *
	 * We actually keep the appended stuff separate from the main title, because the main title
	 * might in some occasions not be known immediately.
	 *
	 * @param string What to append to the titlearea
	 */
	function append_to_titlearea( $string )
	{
		$this->title_titlearea_appendix .= $this->pathSeparator.$string;
	}


	/**
	 * Get the title for HTML <title> tag.
	 *
	 * If no explicit title has been specified, auto construct one from path.
	 *
	 * @return string
	 */
	function get_html_title()
	{
		global $app_shortname;

		if( $htmltitle = $this->get_prop_for_node( $this->path, array( 'htmltitle' ) ) )
		{	// Explicit htmltitle set:
			$r = $htmltitle;
		}
		else
		{	// No explicit title set, construct Title from path
			$r = $this->get_title();
		}
		$r .= ' &ndash; '.$app_shortname;

		return $r;
	}


	/**
	 * Get a list of properties for a given path for a set of property names to check.
	 * The result is a list of properties for each node down the path.
	 *
	 * The property names must be given in $prop_by_ref, ordered by preference.
	 *
	 * @param string|array The path. See {@link get_node_by_path()}.
	 * @param array Alternative names of the property to receive (ordered by priority).
	 * @return array List of the properties.
	 */
	function get_properties_for_path( $path, $prop_by_pref )
	{
		if( !is_array($path) )
		{
			$path = array( $path );
		}
		$r = array();

		$prevPath = array();
		foreach( $path as $i => $lPath )
		{
			if( false !== ($prop = $this->get_prop_for_path( $i, $prop_by_pref )) )
			{
				$r[] = $prop;
			}

			$prevPath[] = $lPath;
		}

		return $r;
	}


	/**
	 * Get a property of a node, given by path.
	 *
	 * @param string|array The path. See {@link get_node_by_path()}.
	 * @param array Alternative names of the property to receive (ordered by priority).
	 * @return mixed|false False if property is not set for the node, otherwise its value.
	 */
	function get_prop_for_node( $path, $prop_by_pref )
	{
		$node = & $this->get_node_by_path( $path );

		foreach( $prop_by_pref as $lProp )
		{
			if( isset($node[$lProp]) )
			{
				return $node[$lProp];
			}
		}

		return false;
	}


	/**
	 * Get a property for a specific path entry.
	 *
	 * @param int The numeric index of the path entry to query (0 is first).
	 * @param array A list of properties to check, ordered by priority.
	 * @return mixed|false The first found property or false if it does not exist
	 */
	function get_prop_for_path( $depth, $prop_by_pref )
	{
		if( $pathWithProps = $this->get_path( $depth, true ) )
		{
			foreach( $prop_by_pref as $lProp )
			{
				if( isset($pathWithProps['props'][$lProp]) )
				{
					// echo "<br>path depth $depth property $lProp = ".$pathWithProps['props'][$lProp];
					return $pathWithProps['props'][$lProp];
				}
			}
		}

		return false;
	}


	/**
	 * Display doctype + <head>...</head> section
	 */
	function disp_html_head()
	{
		if( is_ajax_content() )
		{ // Don't display this content on AJAX request
			return;
		}

		global $adminskins_path;

		if( isset( $this->skin_name ) && file_exists( $adminskins_path.$this->skin_name.'/_html_header.inc.php' ) )
		{ // Get header of the skin
			require $adminskins_path.$this->skin_name.'/_html_header.inc.php';
		}
		else
		{ // Get general header
			require $adminskins_path.'_html_header.inc.php';
		}
	}


	/**
	 * Dsiplay the top of the HTML <body>...
	 *
	 * Typically includes title, menu, messages, etc.
	 *
	 * @param boolean Whether or not to display messages.
	 */
	function disp_body_top( $display_messages = true )
	{
		if( is_ajax_content() )
		{	// Don't display this content on AJAX request
			return;
		}

		load_funcs('skins/_skin.funcs.php');

		global $mode;

		/**
		 * @var Hit
		 */
		global $Hit;

		// #body_win and .body_firefox (for example) can be used to customize CSS per plaform/browser
		echo '<body id="body_'.$Hit->get_agent_platform().'" class="body_'.$Hit->get_agent_name().'">'."\n";

		if( ! empty( $mode ) )
		{
			global $Messages;

			$mode = preg_replace( '~[^a-z]~', '', $mode );	// sanitize
			echo '<div id="'.$mode.'_wrapper">';

			if( $display_messages )
			{ // Display info & error messages
				$Messages->display();
				// Clear the messages to avoid double displaying
				$Messages->clear();
			}
			return;
		}

		$skin_wrapper_class = 'skin_wrapper';
		if( is_logged_in() )
		{ // user is logged in
			if( $this->get_show_evobar() )
			{ // show evobar options is enabled for this admin skin
				require skin_fallback_path( '_toolbar.inc.php' );
				$skin_wrapper_class = $skin_wrapper_class.'_loggedin';
			}
		}
		else
		{ // user is not logged in
			require skin_fallback_path( '_toolbar.inc.php' );
			$skin_wrapper_class = $skin_wrapper_class.'_anonymous';
		}

		echo "\n";
		echo '<div id="skin_wrapper" class="'.$skin_wrapper_class.'">';

		echo "\n<!-- Start of skin_wrapper -->\n";

		echo $this->get_body_top();
	}


	/**
	 * Display body bottom, debug info and close </html>
	 */
	function disp_global_footer()
	{
		if( is_ajax_content() )
		{	// Don't display this content on AJAX request
			return;
		}

		global $adminskins_path, $mode;

		if( isset( $this->skin_name ) && file_exists( $adminskins_path.$this->skin_name.'/_html_footer.inc.php' ) )
		{ // Get footer of the skin
			require $adminskins_path.$this->skin_name.'/_html_footer.inc.php';
		}
		else
		{ // Get general footer
			require $adminskins_path.'_html_footer.inc.php';
		}
	}


	/**
	 * Display the start of a payload block
	 *
	 * Note: it is possible to display several payload blocks on a single page.
	 *       The first block uses the "sub" template, the others "block".
	 *
	 * @see disp_payload_end()
	 */
	function disp_payload_begin( $params = array() )
	{
		if( is_ajax_content() )
		{	// Don't display this content on AJAX request
			return;
		}

		$params = array_merge( array(
				'display_menu2' => true,
				'display_menu3' => true,
			), $params );

		global $Plugins;

		if( empty($this->displayed_sub_begin) )
		{	// We haven't displayed sub menus yet (tabs):
			$Plugins->trigger_event( 'AdminBeginPayload' );

			// Display submenu (this also opens a div class="panelblock" or class="panelblocktabs")

			//echo ' disp_submenu-BEGIN ';
			$path0 = $this->get_path(0);
			$r = $this->get_html_menu( $path0, 'sub', 0, ! $params['display_menu2'] );

			echo $this->replace_vars( $r );
			//echo ' disp_submenu-END ';

			// Show 3rd level menu for settings tab
			$path1 = $this->get_path(1);
			echo $this->get_html_menu( array($path0, $path1), 'menu3', 0, ! $params['display_menu3'] );


			$this->displayed_sub_begin = 1;
		}
		else
		{
			$template = $this->get_template( 'block' );

			echo $template['begin'];
		}
	}


	/**
	 * Display the end of a payload block
	 *
	 * Note: it is possible to display several payload blocks on a single page.
	 *       The first block uses the "sub" template, the others "block".
	 * @see disp_payload_begin()
	 */
	function disp_payload_end()
	{
		if( is_ajax_content() )
		{	// Don't display this content on AJAX request
			return;
		}

		if( empty($this->displayed_sub_end) )
		{
			$name = 'sub';
			$this->displayed_sub_end = 1;
		}
		else
		{
			$name = 'block';
		}

		$template = $this->get_template( $name );

		echo $template['end'];
	}


	/**
	 * Display a view
	 *
	 * Note: doing the require inside of a function has the side effect of forcing the view
	 * to declare any global object it wants to use. This can be a little tedious but on the
	 * other hand it has the advantage of clearly showing what objects are used and makes it
	 * easier to audit the views in order to determine if they include more business logic
	 * than they ought to.
	 *
	 * @param string
	 * @param array params to be used in the view (optional)
	 */
	function disp_view( $view_name, $params = array() )
	{
		global $inc_path;

		// THESE ARE THE GLOBALS WE WANT TO MAKE AVAILABLE TO ALL VIEWS:
		global $action;
		global $ctrl;

		global $DB;	// Note: not sure it's agood idea to let the views hit on the db...

		global $current_User;


		require $inc_path.$view_name;
	}


	/**
	 * Set params for blog list.
	 *
	 * @param string name of required permission needed to display the blog in the list
	 * @param string level of required permission needed to display the blog in the list
	 * @param string Url format string for elements, with %d for blog number.
	 * @param string Title for "all" button
	 * @param string URL for "all" button
	 * @param string onclick attribute format string, with %s for blog number. (NOTE: %s so that we can use this.value when selected through list)
	 */
	function set_coll_list_params( $permname = 'blog_ismember', $permlevel = 1, $url_params = array(),
							$all_title = NULL, $all_url = '', $onclick = NULL )
	{
		$this->coll_list_all_title = $all_title;
		$this->coll_list_all_url = $all_url;
		$this->coll_list_permname = $permname;
		$this->coll_list_permlevel = $permlevel;
		$this->coll_list_url_params = $url_params;
		$this->coll_list_onclick = $onclick;
	}


	/**
	 * Returns list of buttons for available Collections (aka Blogs) to work on.
	 *
	 * @return string HTML
	 */
	function get_bloglist_buttons( $title = '' )
	{
		global $current_User, $blog, $pagenow;

		$max_buttons = 7;

		if( empty( $this->coll_list_permname ) )
		{	// We have not requested a list of blogs to be displayed
			return;
		}

		// Prepare url params:
		$url_params = '?';
		$form_params = '';
		foreach( $this->coll_list_url_params as $name => $value )
		{
			$url_params .= $name.'='.$value.'&amp;';
			$form_params .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
		}

		$template = $this->get_template( 'CollectionList' );

		$BlogCache = & get_BlogCache();

		$blog_array = $BlogCache->load_user_blogs( $this->coll_list_permname, $this->coll_list_permlevel );


		$buttons = '';
		$select_options = '';
		$not_favorite_blogs = false;
		foreach( $blog_array as $l_blog_ID )
		{ // Loop through all blogs that match the requested permission:

			$l_Blog = & $BlogCache->get_by_ID( $l_blog_ID );

			if( $l_Blog->favorite() || $l_blog_ID == $blog )
			{ // If blog is favorute OR current blog, Add blog as a button:
				$buttons .= $template[ $l_blog_ID == $blog ? 'beforeEachSel' : 'beforeEach' ];

				$buttons .= '<a href="'.$url_params.'blog='.$l_blog_ID
							.'" class="'.( $l_blog_ID == $blog ? 'CurrentBlog' : 'OtherBlog' ).'"';

				if( !is_null($this->coll_list_onclick) )
				{	// We want to include an onclick attribute:
					$buttons .= ' onclick="'.sprintf( $this->coll_list_onclick, $l_blog_ID ).'"';
				}

				$buttons .= '>'.$l_Blog->dget( 'shortname', 'htmlbody' ).'</a> ';

				if( $l_blog_ID == $blog )
				{
					$buttons .= $template['afterEachSel'];
				}
				else
				{
					$buttons .= $template['afterEach'];
				}
			}

			if( !$l_Blog->favorite() )
			{ // If blog is not favorute, Add it into the select list:
				$not_favorite_blogs = true;
				$select_options .= '<option value="'.$l_blog_ID.'"';
				if( $l_blog_ID == $blog )
				{
					$select_options .= ' selected="selected"';
				}
				$select_options .= '>'.$l_Blog->dget( 'shortname', 'formvalue' ).'</option>';
			}
		}

		$r = $template['before'];

		$r .= $title;

		if( !empty( $this->coll_list_all_title ) )
		{ // We want to add an "all" button
			$r .= $template[ $blog == 0 ? 'beforeEachSel' : 'beforeEach' ];
			$r .= '<a href="'.$this->coll_list_all_url
						.'" class="'.( $blog == 0 ? 'CurrentBlog' : 'OtherBlog' ).'">'
						.$this->coll_list_all_title.'</a> ';
			$r .= $template[ $blog == 0 ? 'afterEachSel' : 'afterEach' ];
		}

		$r .= $template['buttons_start'];
		$r .= $buttons;
		$r .= $template['buttons_end'];


		$r .= $template['select_start'];
		if( $not_favorite_blogs )
		{ // Display select list with not favorite blogs
			$r .= '<form action="'.$pagenow.'" method="get">';
			$r .= $form_params;
			$r .= '<select name="blog" onchange="';
			if( empty( $this->coll_list_onclick ) )
			{ // Just submit...
				$r .= 'if(this.value>0) this.form.submit();';
			}
			else
			{
				$r .= sprintf( $this->coll_list_onclick, 'this.value' );
			}
			$r .= '">'
				.'<option value="0">'.T_('Select blog').'</option>'
				.$select_options.'</select>';
			$r .= '<noscript><input type="submit" value="Go" /></noscript></form>';
		}
		$r .= $template['select_end'];


		$r .= $template['after'];

		return $r;
	}


	/**
	 * Get a template by name.
	 *
	 * This is a method (and not a member array) to allow dynamic generation and T_()
	 *
	 * @param string Name of the template ('main', 'sub')
	 * @param integer Nesting level (start at 0)
	 * @param boolean TRUE to die on unknown template name
	 * @return array Associative array which defines layout and optionally properties.
	 */
	function get_template( $name, $level = 0, $die_on_unknown = false )
	{
		switch( $name )
		{
			case 'main':
				return array(
					'before' => '<div id="mainmenu"><ul>',
					'after' => '</ul></div>',
					'beforeEach' => '<li>',
					'afterEach' => '</li>',
					'beforeEachSel' => '<li class="current">',
					'afterEachSel' => '</li>',
					'beforeEachSelWithSub' => '<li class="parent">',
					'afterEachSelWithSub' => '</li>',
					'_props' => array(
						/**
						 * 'recurse'       => 'yes', // To display the submenus recursively
						 * 'recurse_level' => 2,     // Limit recursion
						 * @todo Move to new skin (recurse for subentries if an entry is selected)
						'recurseSelected' => true,
						*/
					),
				);
				break;


			case 'sub':
				// a payload block with embedded submenu
				return array(
						'before' => '<div class="pt">'
							."\n".'<ul class="hack">'
							."\n<li><!-- Yes, this empty UL is needed! It's a DOUBLE hack for correct CSS display --></li>"
							// TODO: this hack MAY NOT be needed when not using pixels instead of decimal ems or exs in the CSS
							."\n</ul>"
							."\n".'<div class="panelblocktabs">$top_block$'
							."\n".'<ul class="tabs">',
						'after' => "</ul>\n"
							.'<span style="float:right">$global_icons$</span>'
							."</div>\n</div>"
							."\n".'<div class="tabbedpanelblock">',
						'empty' => '<div class="panelblock"><span style="float:right">$global_icons$</span>',
						'beforeEach' => '<li>',
						'afterEach'  => '</li>',
						'beforeEachSel' => '<li class="current">',
						'afterEachSel' => '</li>',
						'end' => '</div>', // used to end payload block that opened submenu
					);


			case 'menu3':
				// level 3 submenu:
				return array(
							'before' => '<div class="menu3">',
							'after' => '</div>',
							'empty' => '',
							'beforeEach' => '<span class="option3">',
							'afterEach'  => '</span>',
							'beforeEachSel' => '<span class="current">',
							'afterEachSel' => '</span>',
						);


			case 'block':
				// an additional payload block, anywhere after the one with the submenu. Used by disp_payload_begin()/disp_payload_end()
				return array(
						'begin' => '<div class="panelblock">',
						'end' => "\n</div>",
					);


			case 'CollectionList':
				// Template for a list of Collections (Blogs)
				return array(
						'before' => '<div id="coll_list"><ul>',
						'after' => '</ul></div>',
						'buttons_start' => '',
						'buttons_end' => '',
						'beforeEach' => '<li>',
						'afterEach' => '</li>',
						'beforeEachSel' => '<li class="current">',
						'afterEachSel' => '</li>',
						'select_start' => '<li class="collection_select">',
						'select_end' => '</li>',
					);


			case 'Results':
				// Results list:
				return array(
					'page_url' => '', // All generated links will refer to the current page
					'before' => '<div class="results">',
					'content_start' => '<div id="$prefix$ajax_content">',
					'header_start' => '<div class="results_nav">',
						'header_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => '',
					'header_end' => '</div>',
					'head_title' => '<div class="table_title"><span style="float:right">$global_icons$</span>$title$</div>'."\n",
					'filters_start' => '<div class="filters">',
					'filters_end' => '</div>',
					'messages_start' => '<div class="messages">',
					'messages_end' => '</div>',
					'messages_separator' => '<br />',
					'list_start' => '<div class="table_scroll">'."\n"
					               .'<table class="grouped $list_class$" cellspacing="0" $list_attrib$>'."\n",
						'head_start' => "<thead>\n",
							'line_start_head' => '<tr>',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
							'colhead_start' => '<th $class_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$">',
							'colhead_start_last' => '<th class="lastcol $class$">',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => get_icon( 'sort_asc_off' ),
							'sort_asc_on' => get_icon( 'sort_asc_on' ),
							'sort_desc_off' => get_icon( 'sort_desc_off' ),
							'sort_desc_on' => get_icon( 'sort_desc_on' ),
							'basic_sort_off' => '',
							'basic_sort_asc' => get_icon( 'ascending' ),
							'basic_sort_desc' => get_icon( 'descending' ),
						'head_end' => "</thead>\n\n",
						'tfoot_start' => "<tfoot>\n",
						'tfoot_end' => "</tfoot>\n\n",
						'body_start' => "<tbody>\n",
							'line_start' => '<tr class="even">'."\n",
							'line_start_odd' => '<tr class="odd">'."\n",
							'line_start_last' => '<tr class="even lastline">'."\n",
							'line_start_odd_last' => '<tr class="odd lastline">'."\n",
								'col_start' => '<td $class_attrib$ $colspan_attrib$>',
								'col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
								'col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
								'col_end' => "</td>\n",
							'line_end' => "</tr>\n\n",
							'grp_line_start' => '<tr class="group">'."\n",
							'grp_line_start_odd' => '<tr class="odd">'."\n",
							'grp_line_start_last' => '<tr class="lastline">'."\n",
							'grp_line_start_odd_last' => '<tr class="odd lastline">'."\n",
										'grp_col_start' => '<td $class_attrib$ $colspan_attrib$>',
										'grp_col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
										'grp_col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
								'grp_col_end' => "</td>\n",
							'grp_line_end' => "</tr>\n\n",
						'body_end' => "</tbody>\n\n",
						'total_line_start' => '<tr class="total">'."\n",
							'total_col_start' => '<td $class_attrib$>',
							'total_col_start_first' => '<td class="firstcol $class$">',
							'total_col_start_last' => '<td class="lastcol $class$">',
							'total_col_end' => "</td>\n",
						'total_line_end' => "</tr>\n\n",
					'list_end' => "</table></div>\n\n",
					'footer_start' => '<div class="results_nav nav_footer">',
					'footer_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$<br />$page_size$'
					                  /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
					                  /* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
					                  /* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => '$page_size$',
					'footer_text_no_limit' => '', // Text if theres no LIMIT and therefor only one page anyway
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'no_prev_text' => '',
						'no_next_text' => '',
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "</div>\n\n",
					'no_results_start' => '<table class="grouped" cellspacing="0">'."\n",
					'no_results_end'   => '<tr class="lastline"><td class="firstcol lastcol">$no_results$</td></tr>'
								                .'</table>'."\n\n",
				'content_end' => '</div>',
				'after' => '</div>',
				'sort_type' => 'basic'
				);

			case 'compact_results':
				// Results list:
				return array(
					'page_url' => '', // All generated links will refer to the current page
					'before' => '<div class="compact_results">',
					'header_start' => '',
						'header_text' => '',
						'header_text_single' => '',
					'header_end' => '',
					'list_start' => '<table class="grouped" cellspacing="0">'."\n\n",
						'head_start' => "<thead>\n",
							'head_title' => '',
							'filters_start' => '<tr class="filters"><td colspan="$nb_cols$">',
							'filters_end' => '</td></tr>',
							'line_start_head' => '<tr>',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
							'colhead_start' => '<th $class_attrib$ $title_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$" $title_attrib$>',
							'colhead_start_last' => '<th class="lastcol $class$" $title_attrib$>',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => get_icon( 'sort_asc_off' ),
							'sort_asc_on' => get_icon( 'sort_asc_on' ),
							'sort_desc_off' => get_icon( 'sort_desc_off' ),
							'sort_desc_on' => get_icon( 'sort_desc_on' ),
							'basic_sort_off' => '',
							'basic_sort_asc' => get_icon( 'ascending' ),
							'basic_sort_desc' => get_icon( 'descending' ),
						'head_end' => "</thead>\n\n",
						'tfoot_start' => "<tfoot>\n",
						'tfoot_end' => "</tfoot>\n\n",
						'body_start' => "<tbody>\n",
							'line_start' => '<tr class="even">'."\n",
							'line_start_odd' => '<tr class="odd">'."\n",
							'line_start_last' => '<tr class="even lastline">'."\n",
							'line_start_odd_last' => '<tr class="odd lastline">'."\n",
								'col_start' => '<td $class_attrib$ $colspan_attrib$>',
								'col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
								'col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
								'col_end' => "</td>\n",
							'line_end' => "</tr>\n\n",
							'grp_line_start' => '<tr class="group">'."\n",
							'grp_line_start_odd' => '<tr class="odd">'."\n",
							'grp_line_start_last' => '<tr class="lastline">'."\n",
							'grp_line_start_odd_last' => '<tr class="odd lastline">'."\n",
										'grp_col_start' => '<td $class_attrib$ $colspan_attrib$>',
										'grp_col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
										'grp_col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
								'grp_col_end' => "</td>\n",
							'grp_line_end' => "</tr>\n\n",
						'body_end' => "</tbody>\n\n",
						'total_line_start' => '<tr class="total">'."\n",
							'total_col_start' => '<td $class_attrib$>',
							'total_col_start_first' => '<td class="firstcol $class$">',
							'total_col_start_last' => '<td class="lastcol $class$">',
							'total_col_end' => "</td>\n",
						'total_line_end' => "</tr>\n\n",
					'list_end' => "</table>\n\n",
					'footer_start' => '<div class="results_nav">',
					'footer_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$'
					                  /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
					                  /* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
					                  /* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => '',
					'footer_text_no_limit' => '', // Text if theres no LIMIT and therefor only one page anyway
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'no_prev_text' => '',
						'no_next_text' => '',
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "</div>\n\n",
					'no_results_start' => '<table class="grouped" cellspacing="0"><tbody>'."\n\n",
					'no_results_end'   => '<tr class="lastline noresults"><td class="firstcol lastcol">$no_results$</td></tr>'
								                .'</tbody></table>'."\n\n",
				'after' => '</div>',
				'sort_type' => 'basic'
				);


			case 'blockspan_form':
				// blockspan Form settings:
				return array(
					'layout' => 'blockspan',		// Temporary dirty hack
					'formstart' => '',
					'title_fmt' => '$title$'."\n", // TODO: icons
					'no_title_fmt' => '',          //           "
					'fieldset_begin' => '<fieldset $fieldset_attribs$>'."\n"
															.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
					'fieldset_end' => '</fieldset>'."\n",
					'fieldstart' => '<span class="block" $ID$>',
					'labelclass' => '',
					'labelstart' => '',
					'labelend' => "\n",
					'labelempty' => '',
					'inputstart' => '',
					'infostart' => '',
					'infoend' => '',
					'inputend' => "\n",
					'fieldend' => '</span>'.get_icon( 'pixel' )."\n",
					'buttonsstart' => '',
					'buttonsend' => "\n",
					'customstart' => '',
					'customend' => "\n",
					'note_format' => ' <span class="notes">%s</span>',
					'formend' => '',
				);

			case 'compact_form':
			case 'Form':
				// Default Form settings:
				return array(
					'layout' => 'fieldset',
					'formstart' => '',
					'title_fmt' => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
					'no_title_fmt' => '<span style="float:right">$global_icons$</span>'."\n",
					'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$>'."\n"
															.'<legend $title_attribs$>$fieldset_title$</legend><div class="fieldset">'."\n",
					'fieldset_end' => '</div></fieldset></div>'."\n",
					'fieldstart' => '<fieldset $ID$>'."\n",
					'labelclass' => '',
					'labelstart' => '<div class="label">',
					'labelend' => "</div>\n",
					'labelempty' => '<div class="label"></div>', // so that IE6 aligns DIV.input correcctly
					'inputstart' => '<div class="input">',
					'infostart' => '<div class="info">',
					'infoend' => "</div>\n",
					'inputend' => "</div>\n",
					'fieldend' => "</fieldset>\n\n",
					'buttonsstart' => '<fieldset><div class="input">',
					'buttonsend' => "</div></fieldset>\n\n",
					'customstart' => '<div class="custom_content">',
					'customend' => "</div>\n",
					'note_format' => ' <span class="notes">%s</span>',
					'formend' => '',
				);

			case 'file_browser':
				return array(
					'block_start' => '<div class="block_item"><h3><span style="float:right">$global_icons$</span>$title$</h3>',
					'block_end' => '</div>',
				);

			case 'block_item':
			case 'dash_item':
				return array(
					'block_start' => '<div class="block_item evo_content_block"><h3><span style="float:right">$global_icons$</span>$title$</h3>',
					'block_end' => '</div>',
				);

			case 'side_item':
				return array(
					'block_start' => '<div class="browse_side_item"><h3><span style="float:right">$global_icons$</span>$title$</h3>',
					'block_end' => '</div>',
				);

			case 'user_navigation':
				// The Prev/Next links of users, @see user_prevnext_links()
				return array();

			case 'button_classes':
				// Button classes, @see button_class()
				return array();

			case 'table_browse':
				// A browse table for items and comments
				return array(
					'table_start'     => '<table class="browse" cellspacing="0" cellpadding="0" border="0"><tr>',
					'full_col_start'  => '<td class="browse_left_col">',
					'left_col_start'  => '<td class="browse_left_col">',
					'left_col_end'    => '</td>',
					'right_col_start' => '<td class="browse_right_col">',
					'right_col_end'   => '</td>',
					'table_end'       => '</tr></table>',
				);

			case 'tooltip_plugin':
				// Plugin name for tooltips: 'bubbletip' or 'popover'
				return 'bubbletip';
				break;

			case 'autocomplete_plugin':
				// Plugin name to autocomplete the fields: 'hintbox', 'typeahead'
				return 'hintbox';
				break;

			case 'modal_window_js_func':
				// JavaScript function to initialize Modal windows, @see echo_user_ajaxwindow_js()
				return false; // Use standard functions
				break;

			case 'pagination':
				// Pagination, @see echo_comment_pages()
				return array();
				break;

			case 'blog_base.css':
				// File name of blog_base.css that are used on several back-office pages
				return 'blog_base.css';
				break;

			default:
				if( $die_on_unknown )
				{ // Die because template name is unknown
					debug_die( 'Unknown $name for AdminUI::get_template(): '.var_export( $name, true ) );
				}
				else
				{ // Return NULL, if we want to know when template is not defined by current skin
					return NULL;
				}
		}
	}


	/**
	 * Get a path key by numeric key. Starts with 0.
	 *
	 * @param integer The numeric index of the path (0 is first).
	 * @param boolean Also return properties?
	 * @return string|array|false (depends on $withProps)
	 */
	function get_path( $which, $withProps = false )
	{
		if( $which === 'last' )
		{
			$which = count($this->path)-1;
		}
		if( !isset($this->path[$which]) )
		{
			return false;
		}

		if( $withProps )
		{
			return array(
				'path' => $this->path[$which],
				'props' => isset( $this->pathProps[$which] ) ? $this->pathProps[$which] : array(),
			);
		}

		return $this->path[$which];
	}


	/**
	 * Get the list of path keys in a given range.
	 *
	 * @param integer start index
	 * @param integer|NULL end index (NULL means same as start index)
	 * @return array List of path keys.
	 */
	function get_path_range( $start, $end = NULL )
	{
		if( is_null($end) )
		{
			$end = $start;
		}

		$r = array();
		for( $i = $start; $i <= $end; $i++ )
		{
			$r[] = isset($this->path[$i]) ? $this->path[$i] : NULL;
		}

		return $r;
	}


	/**
	 * Set a specific path level (specific depth).
	 *
	 * First level is 0, then the first subpath/submenu is level 1, etc.
	 *
	 * E.g., if plugins.php gets called, there could be a call to
	 * $AdminUI->set_path_level( 0, 'plugins' ), which selects this entry from the menu.
	 * If a specific tab is called inside of plugins.php, there could be a call to
	 * $AdminUI->set_path_level( 1, $tab )
	 *
	 * Though, it is recommended to call the wrapper functions:
	 *  - {@link append_path_level()}
	 *  - {@link set_path()}
	 *
	 * This also marks the parent node as selected and checks for permissions.
	 *
	 * @param integer Path level to set (starts at 0)
	 * @param array Either the key of the path or an array(keyname, propsArray).
	 * @param array Properties for this path entry.
	 * @return boolean DEPRECATED True if perm granted, false if not (and we're not exiting).
	 */
	function set_path_level( $level, $pathKey, $pathProps = array() )
	{
		// Get the parent node (the level above this one):
		if( $level == 0 )
		{ // first level in menu-path: parent node is NULL
			$parentNode = & $this->get_node_by_path( NULL );
		}
		else
		{ // parent node is the trunk from root to previous level
			$parentNode = & $this->get_node_by_path( $this->get_path_range( 0, $level-1 ) );
		}
		if( ! $parentNode )
		{ // parent node does not exist:
			return false;
		}

		// Store the selected entry name in the parent node:
		$parentNode['selected'] = $pathKey;

		$this->path[$level] = $pathKey;
		$this->pathProps[$level] = $pathProps;

		// pre_dump( 'set_path_level: ', $level, $pathKey, $this->pathProps[$level] );

		return true;
	}


	/**
	 * Append a selected menu entry to the current path of selected entries.
	 *
	 * @param string|array Either the key of the path or an array(keyname, propsArray).
	 */
	function append_path_level( $path, $pathProps = array() )
	{
		$search_path = $this->path;
		$search_path[] = $path;
		// auto-detect path props from menu entries
		if( $node = & $this->get_node_by_path( $search_path ) )
		{
			$pathProps = array_merge( $node, $pathProps );
		}

		// Set the path level right after the last existing one:
		return $this->set_path_level( count($this->path), $path, $pathProps );
	}


	/**
	 * Set the full selected path.
	 *
	 * For example, this selects the tab/submenu 'plugins' in the main menu 'options':
	 * <code>
	 * set_path( 'options', 'plugins' );
	 * </code>
	 *
	 * Use {@link append_path_level()} to append a single path element.
	 *
	 * This is an easy stub for {@link set_path_level()}.
	 *
	 * @param string|array,... VARIABLE NUMBER OF ARGUMENTS. Each is either the key of a path entry or an array(keyname, propsArray).
	 */
	function set_path(        )
	{
		$args = func_get_args();

		$i = 0;
		$prevPath = array();  // Remember the path we have walked through

		// Loop though all path levels to set:
		foreach( $args as $arg )
		{
			if( is_array($arg) )
			{ // Path name and properties given
				list( $pathName, $pathProps ) = $arg;
			}
			else
			{ // Just the path name
				$pathName = $arg;
				$pathProps = array();
			}

			$this->init_menus();

			if( $node = & $this->get_node_by_path( array_merge( $prevPath, array($pathName) ) ) )
			{ // the node exists in the menu entries: merge the properties
				$pathProps = array_merge( $node, $pathProps );
			}

			if( ! $this->set_path_level( $i++, $pathName, $pathProps ) )
			{
				return false;
			}

			$prevPath[] = $pathName;
		}

		return true;
	}


	/**
	 * Init the menus.
	 *
	 * Do this as late as possible. Especially after determining the blog ID we work on.
	 * This allows to check for proper permissions and build correct cross navigation links.
	 *
	 * Note: The menu structure is determined by the modules and the plugins.
	 * Individual Admin skins can still override the whole menu. In a cumbersome way though.
	 */
	function init_menus()
	{
		global $Plugins;

		if( !empty($this->_menus) )
		{	// Already initialized!
			return;
		}

		// Let the modules construct the menu:
		// Part 1:
		modules_call_method( 'build_menu_1' );

		// Part 2:
		modules_call_method( 'build_menu_2' );

		// Part 3:
		modules_call_method( 'build_menu_3' );

		// Call AdminAfterMenuInit to notify Plugins that the menu is initialized
		// E.g. the livehits_plugin and weather_plugin use it for adding a menu entry.
		$Plugins->trigger_event( 'AdminAfterMenuInit' );
	}


	/**
	 * Get the top of the HTML <body>.
	 *
	 * This is not called if {@link $mode} is set.
	 *
	 * @return string
	 */
	function get_body_top()
	{
		return '';
	}


	/**
	 * Get the end of the HTML <body>. Close open divs, etc...
	 *
	 * This is not called if {@link $mode} is set.
	 *
	 * @return string
	 */
	function get_body_bottom()
	{
		return '';
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
	{
		global $app_shortname, $app_version, $current_User, $admin_url, $baseurl, $rsc_url;

		$r = '
		<div id="header">
			<div id="headinfo">
				<span id="headfunctions">'
					// Note: if we log in with another user, we may not have the perms to come back to the same place any more, thus: redirect to admin home.
					.'<a href="'.get_htsrv_url( true ).'login.php?action=logout&amp;redirect_to='.rawurlencode( url_rel_to_same_host( $admin_url, get_htsrv_url( true ) ) ).'">'.T_('Log out').'</a>
					<img src="'.$rsc_url.'icons/close.gif" width="14" height="14" border="0" class="top" alt="" title="'
					.T_('Log out').'" /></a>
				</span>

				'.$app_shortname.' v <strong>'.$app_version.'</strong>
			</div>

			<h1>'.$this->get_title_for_titlearea().'</h1>
		</div>
		';

		return $r;
	}


	/**
	 * Get the footer text
	 *
	 * @return string
	 */
	function get_footer_contents()
	{
		global $app_footer_text, $copyright_text;

		return '<div class="footer">'.$app_footer_text.' &ndash; '.$copyright_text."</div>\n\n";
	}


	/**
	 * Get colors for page elements that can't be controlled by CSS (charts)
	 *
	 * @return string
	 */
	function get_color( $what )
	{
		switch( $what )
		{
			case 'payload_background':
				return 'f1f6f8';
				break;
		}
		debug_die( 'unknown color' );
	}


	/**
	 * Display skin options.
	 * There is no common skin option.
	 */
	function display_skin_settings( $Form, $user_ID )
	{
	}


	/**
	 * Set skin specific options.
	 * There is no common skin option.
	 */
	function set_skin_settings( $user_ID )
	{
	}


	/**
	 * Get show evobar setting. Default true for every admin skin.
	 * @return boolean
	 */
	function get_show_evobar()
	{
		return true;
	}
}

?>

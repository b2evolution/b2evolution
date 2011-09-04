<?php
/**
 * This file implements the Admin UI class.
 * Admin skins should derive from this class and override {@link get_template()}
 * for example.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @todo dh> Refactor to allow easier contributions!
 *
 * @version $Id$
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
	 * Constructor.
	 */
	function AdminUI_general()
	{
		global $mode; // TODO: make it a real property
		global $htsrv_url, $baseurl;

		$this->mode = $mode;

		$this->init_templates();
	}


	/**
	 * This function should init the templates - like adding Javascript through the {@link add_headline()} method.
	 */
	function init_templates()
	{
	}

	/**
	* Note: These are not real breadcrumbs. It's just "so to speak" for a hierarchical path.
	*
	*/
	function breadcrumbpath_init( $add_blog = true )
	{
		global $Blog;
		$this->breadcrumbpath_add( T_('Dashboard'), '?ctrl=dashboard&amp;blog=0' );
		if( $add_blog && isset($Blog) )
		{
			$this->breadcrumbpath_add( $Blog->dget('shortname'), '?ctrl=dashboard&amp;blog=$blog$' );
		}
	}

	/**
	* Note: These are not real breadcrumbs. It's just "so to speak" for a hierarchical path.
	*
	* @param mixed $text
	* @param mixed $url
	*/
	function breadcrumbpath_add( $text, $url, $help = NULL )
	{
		global $Blog, $current_User;

		$blog_ID = isset($Blog) ? $Blog->ID : 0;
		$url = str_replace( '$blog$', $blog_ID, $url );

		$html = $text;
		if( $current_User->check_perm( 'admin', 'normal' ) )
		{
			$html = '<a href="'.$url.'">'.$text.'</a>';
		}

		if( !empty($help) )
		{
			$html .= ' <abbr title="'.$help.'">?</abbr>';
		}

		$this->breadcrumbpath[] = $html;
	}


	function breadcrumbpath_get_html()
	{
		$r = '';

		if( $count = count($this->breadcrumbpath) )
		{
			$r = '<div class="breadcrumbpath">&bull; <strong>You are here:</strong> ';

			for( $i=0; $i<$count-1; $i++ )
			{
				$r .= $this->breadcrumbpath[$i].' &gt; ';
			}

			$r .= '<strong>'.$this->breadcrumbpath[$i].'</strong>';

			$r .= "</div>\n";
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
		if( isset($this->title) )
		{ // Explicit title has been set:
			return $this->title;
		}
		else
		{ // Fallback: implode title/text properties of the path
			$titles = $this->get_properties_for_path( $this->path, array( 'title', 'text' ) );
			if( $reversedDefault )
			{ // We have asked for reverse order of the path elements:
				$titles = array_reverse($titles);
			}
			return implode( $this->pathSeparator, $titles );
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
		{ // Explicit htmltitle set:
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
		global $adminskins_path;
		require $adminskins_path.'_html_header.inc.php';
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
		global $skins_path, $mode;

		/**
		 * @var Hit
		 */
		global $Hit;

		// #body_win and .body_firefox (for example) can be used to customize CSS per plaform/browser
		echo '<body id="body_'.$Hit->get_agent_platform().'" class="body_'.$Hit->get_agent_name().'">'."\n";

		if( ! empty( $mode ) )
		{
			global $Messages;

			$mode = preg_replace( '¤[^a-z]¤', '', $mode );	// sanitize
			echo '<div id="'.$mode.'_wrapper">';

			if( $display_messages )
			{ // Display info & error messages
				$Messages->display( NULL, NULL, true, 'action_messages' );
			}
			return;
		}

		$skin_wrapper_class = 'skin_wrapper';
		if( is_logged_in() )
		{ // user is logged in
			if( $this->get_show_evobar() )
			{ // show evobar options is enabled for this admin skin
				require $skins_path.'_toolbar.inc.php';
				$skin_wrapper_class = $skin_wrapper_class.'_loggedin';
			}
		}
		else
		{ // user is not logged in
			require $skins_path.'_toolbar.inc.php';
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
		global $adminskins_path, $mode;

		require $adminskins_path.'_html_footer.inc.php';
	}


	/**
	 * Display the start of a payload block
	 *
	 * Note: it is possible to display several payload blocks on a single page.
	 *       The first block uses the "sub" template, the others "block".
	 *
	 * @see disp_payload_end()
	 */
	function disp_payload_begin()
	{
		global $Plugins;

		if( empty($this->displayed_sub_begin) )
		{	// We haven't displayed sub menus yet (tabs):
			$Plugins->trigger_event( 'AdminBeginPayload' );

			// Display submenu (this also opens a div class="panelblock" or class="panelblocktabs")

			//echo ' disp_submenu-BEGIN ';
			$path0 = $this->get_path(0);
			$r = $this->get_html_menu( $path0, 'sub' );

			echo $this->replace_vars( $r );
			//echo ' disp_submenu-END ';

			// Show 3rd level menu for settings tab
			$path1 = $this->get_path(1);
			echo $this->get_html_menu( array($path0, $path1), 'menu3' );


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
	function disp_view( $view_name, $view_params = array() )
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
		$count = 0;
		$current_is_displayed = false;

		foreach( $blog_array as $l_blog_ID )
		{	// Loop through all blogs that match the requested permission:

			$l_Blog = & $BlogCache->get_by_ID( $l_blog_ID );

			$count++;

			if( $count < $max_buttons
					|| ($current_is_displayed && $count == $max_buttons )
					|| $l_blog_ID == $blog )
			{	// Not too many yet OR current blog, add blog as a button:
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
					$current_is_displayed = true;
					$buttons .= $template['afterEachSel'];
				}
				else
				{
					$buttons .= $template['afterEach'];
				}
			}

			// Add item select list:
			$select_options .= '<option value="'.$l_blog_ID.'"';
			if( $l_blog_ID == $blog )
			{
				$select_options .= ' selected="selected"';
			}
			$select_options .= '>'.$l_Blog->dget( 'shortname', 'formvalue' ).'</option>';
		}

		$r = $template['before'];

		$r .= $title;

		if( !empty($this->coll_list_all_title) )
		{	// We want to add an "all" button
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
		if( $count > $max_buttons )
		{	// We could not display all blogs as buttons
			$r .= '<form action="'.$pagenow.'" method="get">';
			$r .= $form_params;
			$r .= '<select name="blog" onchange="';
			if( empty( $this->coll_list_onclick ) )
			{	// Just submit...
				$r .= 'this.form.submit();';
			}
			else
			{
				$r .= sprintf( $this->coll_list_onclick, 'this.value' );
			}
			$r .= '">'.$select_options.'</select>';
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
	 * @return array Associative array which defines layout and optionally properties.
	 */
	function get_template( $name, $level = 0 )
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
							."\n".'<div class="panelblocktabs">'
							."\n".'<ul class="tabs">',
						'after' => "</ul>\n"
							.'<span style="float:right">$global_icons$</span>'
							."</div>\n</div>"
							."\n".'<div class="tabbedpanelblock">',
						'empty' => '<div class="panelblock">',
						'beforeEach' => '<li>',
						'afterEach'  => '</li>',
						'beforeEachSel' => '<li class="current">',
						'afterEachSel' => '</li>',
						'end' => '</div>', // used to end payload block that opened submenu
					);


			case 'menu3':
				// level 3 submenu:
				return array(
							'before' => '<div class="menu3">&raquo;',
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
					'header_start' => '<div class="results_nav">',
						'header_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => '',
					'header_end' => '</div>',
					'list_start' => '<table class="grouped" cellspacing="0">'."\n\n",
						'head_start' => "<thead>\n",
							'head_title' => '<tr><th colspan="$nb_cols$" class="title"><span style="float:right">$global_icons$</span>$title$</th>'
							                ."\n</tr>\n",
							'filters_start' => '<tr class="filters"><td colspan="$nb_cols$">',
							'filters_end' => '</td></tr>',
							'line_start_head' => '<tr>',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
							'colhead_start' => '<th $class_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$">',
							'colhead_start_last' => '<th class="lastcol $class$">',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => '<img src="../admin/img/grey_arrow_up.gif" alt="A" title="'.T_('Ascending order')
							                    .'" height="12" width="11" />',
							'sort_asc_on' => '<img src="../admin/img/black_arrow_up.gif" alt="A" title="'.T_('Ascending order')
							                    .'" height="12" width="11" />',
							'sort_desc_off' => '<img src="../admin/img/grey_arrow_down.gif" alt="D" title="'.T_('Descending order')
							                    .'" height="12" width="11" />',
							'sort_desc_on' => '<img src="../admin/img/black_arrow_down.gif" alt="D" title="'.T_('Descending order')
							                    .'" height="12" width="11" />',
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
								'col_start' => '<td $class_attrib$>',
								'col_start_first' => '<td class="firstcol $class$">',
								'col_start_last' => '<td class="lastcol $class$">',
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
					'no_results_start' => '<table class="grouped" cellspacing="0">'."\n\n"
								                .'<tr><th class="title"><span style="float:right">$global_icons$</span>'
								                .'$title$</th></tr>'."\n",
					'no_results_end'   => '<tr class="lastline"><td class="firstcol lastcol">$no_results$</td></tr>'
								                .'</table>'."\n\n",
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
							'colhead_start' => '<th $class_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$">',
							'colhead_start_last' => '<th class="lastcol $class$">',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => '<img src="../admin/img/grey_arrow_up.gif" alt="A" title="'.T_('Ascending order')
							                    .'" height="12" width="11" />',
							'sort_asc_on' => '<img src="../admin/img/black_arrow_up.gif" alt="A" title="'.T_('Ascending order')
							                    .'" height="12" width="11" />',
							'sort_desc_off' => '<img src="../admin/img/grey_arrow_down.gif" alt="D" title="'.T_('Descending order')
							                    .'" height="12" width="11" />',
							'sort_desc_on' => '<img src="../admin/img/black_arrow_down.gif" alt="D" title="'.T_('Descending order')
							                    .'" height="12" width="11" />',
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
								'col_start' => '<td $class_attrib$>',
								'col_start_first' => '<td class="firstcol $class$">',
								'col_start_last' => '<td class="lastcol $class$">',
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
					'no_results_start' => '<table class="grouped" cellspacing="0">'."\n\n",
					'no_results_end'   => '<tr class="lastline"><td class="firstcol lastcol">$no_results$</td></tr>'
								                .'</table>'."\n\n",
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
					'fieldstart' => '<span class="block" $ID$>',
					'labelstart' => '',
					'labelend' => "\n",
					'labelempty' => '',
					'inputstart' => '',
					'infostart' => '',
					'inputend' => "\n",
					'fieldend' => '</span>'.get_icon( 'pixel' )."\n",
					'buttonsstart' => '',
					'buttonsend' => "\n",
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
					'fieldset_begin' => '<div class="fieldset_wrapper$class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$>'."\n"
															.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
					'fieldset_end' => '</fieldset></div>'."\n",
					'fieldstart' => '<fieldset $ID$>'."\n",
					'labelstart' => '<div class="label">',
					'labelend' => "</div>\n",
					'labelempty' => '<div class="label"></div>', // so that IE6 aligns DIV.input correcctly
					'inputstart' => '<div class="input">',
					'infostart' => '<div class="info">',
					'inputend' => "</div>\n",
					'fieldend' => "</fieldset>\n\n",
					'buttonsstart' => '<fieldset><div class="input">',
					'buttonsend' => "</div></fieldset>\n\n",
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
					'block_start' => '<div class="block_item"><h3><span style="float:right">$global_icons$</span>$title$</h3>',
					'block_end' => '</div>',
				);

			case 'side_item':
				return array(
					'block_start' => '<div class="browse_side_item"><h3><span style="float:right">$global_icons$</span>$title$</h3>',
					'block_end' => '</div>',
				);

			default:
				debug_die( 'Unknown $name for AdminUI::get_template(): '.var_export($name, true) );
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
		global $app_shortname, $app_version, $current_User, $htsrv_url_sensitive, $admin_url, $baseurl, $rsc_url;

		$r = '
		<div id="header">
			<div id="headinfo">
				<span id="headfunctions">'
					// Note: if we log in with another user, we may not have the perms to come back to the same place any more, thus: redirect to admin home.
					.'<a href="'.$htsrv_url_sensitive.'login.php?action=logout&amp;redirect_to='.rawurlencode(url_rel_to_same_host($admin_url, $htsrv_url_sensitive)).'">'.T_('Logout').'</a>
					<img src="'.$rsc_url.'icons/close.gif" width="14" height="14" border="0" class="top" alt="" title="'
					.T_('Logout').'" /></a>
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

		global $Hit;

		$r = '';

		if( $Hit->is_winIE() )
		{
		 $r .= '<!--[if lt IE 7]>
<div style="text-align:center; color:#f00; font-weight:bold; margin:1ex;">'.
			T_('WARNING: Internet Explorer 6 may not able to display this admin skin properly. We strongly recommend you upgrade to IE 7 or Firefox.').'</div>
<![endif]-->';
		}

		$r .= '<div class="footer">'.$app_footer_text.' &ndash; '.$copyright_text."</div>\n\n";

		return $r;
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


/*
 * $Log$
 * Revision 1.114  2011/09/04 22:13:25  fplanque
 * copyright 2011
 *
 * Revision 1.113  2011/02/15 15:37:00  efy-asimo
 * Change access to admin permission
 *
 * Revision 1.112  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.111  2010/11/22 13:44:33  efy-asimo
 * Admin skin preferences update
 *
 * Revision 1.110  2010/11/18 13:56:06  efy-asimo
 * admin skin preferences
 *
 * Revision 1.109  2010/05/06 18:59:31  blueyed
 * Admin: skin: base: add div.fieldset_wrapper_ID to 'Form' fieldset_begin (consistent with chicago skin).
 *
 * Revision 1.108  2010/02/08 17:56:45  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.107  2009/12/12 01:13:07  fplanque
 * A little progress on breadcrumbs on menu structures alltogether...
 *
 * Revision 1.106  2009/12/11 03:01:13  fplanque
 * breadcrumbs improved
 *
 * Revision 1.105  2009/12/06 22:55:18  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.104  2009/11/22 18:20:11  fplanque
 * Dashboard CSS enhancements
 *
 * Revision 1.103  2009/11/22 16:05:39  tblue246
 * minor/doc
 *
 * Revision 1.102  2009/10/27 22:40:21  fplanque
 * removed UGLY UGLY UGLY messages from iframe
 *
 * Revision 1.101  2009/10/12 23:03:32  blueyed
 * Fix displaying of Messages in $mode windows (e.g. file uploads) and enable
 * them in the attachment iframe.
 *
 * Revision 1.100  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.99  2009/09/25 07:33:31  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.98  2009/09/19 23:09:02  blueyed
 * Improve usability by adding the short app_name at the end of html title.
 *
 * Revision 1.97  2009/09/14 14:12:21  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.96  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 * Revision 1.95  2009/04/21 19:19:49  blueyed
 * doc/normalization
 *
 * Revision 1.94  2009/04/13 20:51:03  fplanque
 * long overdue cleanup of "no results" display: putting filter sback in right position
 *
 * Revision 1.93  2009/03/23 04:09:43  fplanque
 * Best. Evobar. Menu. Ever.
 * menu is now extensible by plugins
 *
 * Revision 1.92  2009/03/08 23:57:56  fplanque
 * 2009
 *
 * Revision 1.91  2009/03/08 22:52:37  fplanque
 * Partial rollback because I'm not sure mod is PHP4 compatible
 *
 * Revision 1.90  2009/03/07 21:32:52  blueyed
 * Fix doc and indent.
 *
 * Revision 1.89  2009/03/04 00:10:43  blueyed
 * Make Hit constructor more lazy.
 *  - Move referer_dom_ID generation/fetching to own method
 *  - wrap Debuglog additons with "debug"
 *  - Conditionally call detect_useragent, if required. Move
 *    vars to methods for this
 *  - get_user_agent alone does not require detect_useragent
 * Feel free to revert it (since it changed all the is_foo vars
 * to methods - PHP5 would allow to use __get to handle legacy
 * access to those vars however), but please consider also
 * removing this stuff from HTML classnames, since that is kind
 * of disturbing/unreliable by itself).
 *
 * Revision 1.88  2009/02/26 22:16:54  blueyed
 * Use load_class for classes (.class.php), and load_funcs for funcs (.funcs.php)
 *
 * Revision 1.87  2009/02/26 00:35:26  blueyed
 * Cleanup: moving modules_call_method where it gets used (only)
 *
 * Revision 1.86  2008/04/14 19:50:51  fplanque
 * enhanced attachments handling in post edit mode
 *
 * Revision 1.85  2008/04/13 20:40:05  fplanque
 * enhanced handlign of files attached to items
 *
 * Revision 1.84  2008/04/06 19:19:30  fplanque
 * Started moving some intelligence to the Modules.
 * 1) Moved menu structure out of the AdminUI class.
 * It is part of the app structure, not the UI. Up to this point at least.
 * Note: individual Admin skins can still override the whole menu.
 * 2) Moved DB schema to the modules. This will be reused outside
 * of install for integrity checks and backup.
 * 3) cleaned up config files
 *
 * Revision 1.83  2008/03/20 14:20:51  fplanque
 * no message
 *
 * Revision 1.82  2008/02/19 11:11:23  fplanque
 * no message
 *
 * Revision 1.81  2008/02/13 06:56:48  fplanque
 * moved blog selector to the left
 *
 * Revision 1.80  2008/01/23 18:28:05  fplanque
 * fixes
 *
 * Revision 1.79  2008/01/22 14:31:06  fplanque
 * minor
 *
 * Revision 1.78  2008/01/21 18:16:54  personman2
 * Different chart bg colors for each admin skin
 *
 * Revision 1.77  2008/01/21 16:46:16  fplanque
 * WARN that IE6 is crap!
 *
 * Revision 1.76  2008/01/21 15:02:01  fplanque
 * fixed evobar
 *
 * Revision 1.75  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.74  2008/01/05 02:28:17  fplanque
 * enhanced blog selector (bloglist_buttons)
 *
 * Revision 1.73  2007/11/22 14:16:43  fplanque
 * antispam / banning cleanup
 *
 * Revision 1.72  2007/11/02 02:37:37  fplanque
 * refactored blog settings / UI
 *
 * Revision 1.71  2007/09/29 03:08:24  fplanque
 * a little cleanup of the form class, hopefully fixing the plugin screen
 *
 * Revision 1.70  2007/09/28 09:28:36  fplanque
 * per blog advanced SEO settings
 *
 * Revision 1.69  2007/09/26 21:53:23  fplanque
 * file manager / file linking enhancements
 *
 * Revision 1.68  2007/09/18 00:00:59  fplanque
 * firefox mac specific forms
 *
 * Revision 1.67  2007/09/17 01:36:39  fplanque
 * look 'ma: just spent 5 hours on a smooth sized footer logo :P
 *
 * Revision 1.66  2007/09/11 08:21:29  yabs
 * minor bug fix
 *
 * Revision 1.65  2007/09/05 00:06:03  fplanque
 * no message
 *
 * Revision 1.64  2007/09/03 16:44:28  fplanque
 * chicago admin skin
 *
 * Revision 1.63  2007/07/16 02:53:04  fplanque
 * checking in mods needed by the chicago adminskin,
 * so that incompatibilities with legacy & evo can be detected early.
 *
 * Revision 1.62  2007/07/09 23:03:04  fplanque
 * cleanup of admin skins; especially evo
 *
 * Revision 1.61  2007/07/09 21:24:11  fplanque
 * cleanup of admin page top
 */
?>

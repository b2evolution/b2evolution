<?php
/**
 * This file implements the poplog (Link popularity) plugin for b2evolution
 * and shows how easy and powerful plugins already are.. :o)
 *
 * @author blueyed - http://thequod.de
 *
 * @package plugins
 *
 * TODO: add params to interface. Each Blog should have an own poplog category.
 * TODO: if $this->destformat changes all links would show up again.. :/ Prefix it?
 * NOTE: we could link to the original post in poplogs post's content.. :) 
 */
require_once dirname(__FILE__).'/../renderer.class.php';

class poplog_Rendererplugin extends RendererPlugin
{
	var $code = 'b2evPopL';
	var $name = 'Poplog';
	var $priority = 65;

	var $apply_when = 'opt-in';
	var $apply_to_html = true;
	var $apply_to_xml = true;
	var $short_desc;
	var $long_desc;


	/**
	 * Constructor
	 *
	 * {@internal poplog_Rendererplugin::poplog_Rendererplugin(-)}}
	 */
	function poplog_Rendererplugin()
	{
		global $Settings;

		$this->short_desc = T_('Poplog');
		$this->long_desc = T_('Rewrites URLs so that we can count them');

		/**
		 * extra categories, see above.
		 */
		$this->poplog_extra_cat_IDs = array();
		/**
		 * the status of the posts
		 */
		$this->poplog_post_status = 'published';
		/**
		 * the comment status of the posts
		 */
		$this->poplog_comments = 'open';

	}


	/**
	 * Perform rendering
	 *
	 * {@internal poplog_Rendererplugin::render(-)}}
	 *
	 * @param string content to render (by reference) / rendered content
	 * @param string Output format, see {@link format_to_output()}
	 * @return boolean true if we can render something for the required output format
	 */
	function render( & $content, $format )
	{
		global $blog;
		if( ! parent::render( $content, $format ) )
		{	// We cannot render the required format
			return false;
		}

		/**
		 * this is the format of the links to be generated. %d gets replaced by the ID for that link
		 */
		$CurBlog = Blog_get_by_ID( $blog );  // NOTE: we don't have global $Blog in edit_actions.php
		$this->destformat = $CurBlog->get( 'baseurl' ).'htsrv/urlredir.php?dest=%s';

		/**
		 * the category to store the links into. Each link will become a post there.
		 */
		$this->poplog_cat_ID = $blog + 1; // poplog category is the linkblog of current blog

		// this will replace all a-tags with a callback function (below)
		$content = preg_replace('#(<a .*?>)(.*?)</a>#ei', '$this->replace_callback(stripslashes("$1"), stripslashes("$2"))', $content);

		return true;
	}


	function replace_callback( $aopentag, $atext )
	{
		global $current_User, $localtimenow, $locales;

		$r = $aopentag.$atext.'</a>'; // standard return

		// check if main category exists
		if( !get_the_category_by_ID( $this->poplog_cat_ID, false ) )
		{ // poplog category does not exist
			debug_log('Poplog category #%d does not exist!'); // TODO: 0.9.1: add to warning messages (new error/notes class)
			return $r;
		}

		// check if extra categories exist and build clean array
		$extra_cats = array();
		foreach( $this->poplog_extra_cat_IDs as $extracat_ID )
		{
			if( get_the_category_by_ID( $extracat_ID, false ) )
			{
				$extra_cats[] = $extracat_ID;
			}
		}


		// get URL
		if( preg_match( '# href\s*=\s*"(.*?)"#i', $aopentag, $matches ) )
		{
			$url = $matches[1];
		}
		else
		{ // no href found! - should not happen
			debug_log('No href for poplog found!'); // TODO: 0.9.1: add to warning messages (new error/notes class)
			return $r;
		}

		// get title for poplog
		$atext = strip_tags( $atext );
		if( empty($atext) )
		{
			// get title out of a-tag
			if( preg_match( '# title\s*=\s*"(.*?)"#i', $aopentag, $matches ) )
			{
				$poplog_title = $matches[1];
			}
			else
			{ // use URL for title
				$poplog_title = $url;
			}
		}
		else
		{
			$poplog_title = $atext;
		}

		// get lang/locale     QUESTION: are there other ways?
		if( preg_match( '# lang\s*=\s*"(.*?)"#i', $aopentag, $matches ) )
		{
			$urllocale = locale_by_lang($matches[1]);  // might be sth we don't know though
		}
		else
		{ // locale of posting user by default
			$urllocale = $current_User->locale;
		}


		// At this point we either have the URL in poplog or have to create it
		$poplog_redirurl = sprintf($this->destformat, $url);
		$r = preg_replace( '#href\s*=\s*".*?"#', 'href="'.$poplog_redirurl.'"', $aopentag ).$atext.'</a>';

		#pre_dump( $poplog_redirurl, 'redir to' );
		$poplog_urltitle = urltitle_validate( '', $poplog_title, 0, true );
		#pre_dump( $poplog_urltitle, 'url title' );

		// look up if URL exists
		if( ($Item = Item_get_by_title( $poplog_urltitle )) )
		{ // already there
			if( $Item->url == $poplog_redirurl )
			{ // with exact our URL
				return $r;
			}
		}

		// the poplog entry does not exist yet, so create it
		bpost_create(
			$current_User->ID,
			$poplog_title,
			'',    // content
			date( 'Y-m-d H:i:s', $localtimenow ),
			$this->poplog_cat_ID,
			$this->poplog_extra_cat_IDs,
			$this->poplog_post_status,
			$urllocale,
			'',    // trackbacks
			0,     // autoBR
			true,  // pingsdone
			'',    // post_urltitle
			$poplog_redirurl,  // post_url
			$this->poplog_comments,
			array('') // renderers
		);

		return $r;
	}

}

// Register the plugin:
$this->register( new poplog_Rendererplugin() );

?>

<?php
/**
 * This file implements the linklogger (autolinkblog) plugin for b2evolution
 * and shows how easy and powerful plugins already are.. :o)
 *
 * @author blueyed - http://thequod.de
 *
 * @package plugins
 *
 * TODO: add params to interface. Each Blog should have an own linklogger category.
 * TODO: if $this->destformat changes all links would show up again.. :/ Prefix it?
 * NOTE: we could link to the original post in linkloggers post's content.. :)
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

class linklogger_plugin extends Plugin
{
	var $code = 'b2evLLog';
	var $name = 'Linklogger';
	var $priority = 65;

	var $apply_when = 'opt-in';
	var $apply_to_html = true;
	var $apply_to_xml = true;
	var $short_desc;
	var $long_desc;


	/**
	 * Constructor
	 *
	 * {@internal linklogger_plugin::linklogger_plugin(-)}}
	 */
	function linklogger_plugin()
	{
		global $Settings;

		$this->short_desc = T_("Adds all external links to the blog's Autolinkblog");
		$this->long_desc = T_('Rewrites external URLs so that we can count clicks on them ....... ');

		/**
		 * extra categories, see above.
		 */
		$this->linklogger_extra_cat_IDs = array();
		/**
		 * the status of the posts
		 */
		$this->linklogger_post_status = 'published';
		/**
		 * the comment status of the posts
		 */
		$this->linklogger_comments = 'open';

	}


	/**
	 * Perform rendering
	 *
	 * {@internal linklogger_plugin::Render(-)}}
	 *
	 * @todo get rid of global
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function Render( & $params )
	{
		if( ! parent::Render( $params ) )
		{	// We cannot render the required format
			return false;
		}

		$content = & $params['data'];

		global $blog;

		/**
		 * this is the format of the links to be generated. %d gets replaced by the ID for that link
		 */
		$CurBlog = Blog_get_by_ID( $blog );  // NOTE: we don't have global $Blog in edit_actions.php
		$this->destformat = $CurBlog->get( 'baseurl' ).'htsrv/urlredir.php?dest=%s';

		/**
		 * the category to store the links into. Each link will become a post there.
		 */
		$this->linklogger_cat_ID = $blog + 1; // linklogger category is the linkblog of current blog


		// check if main category exists
		if( !get_the_category_by_ID( $this->linklogger_cat_ID, false ) )
		{ // linklogger category does not exist
			$Debuglog->add('Autolinkblog category #%d does not exist!', 'error');
			return $r;
		}

		// check if extra categories exist
		$extra_cats = array();
		foreach( $this->linklogger_extra_cat_IDs as $extracat_ID )
		{
			if( get_the_category_by_ID( $extracat_ID, false ) )
			{
				$extra_cats[] = $extracat_ID;
			}
		}


		// this will replace all a-tags by using a callback function (below)
		$content = preg_replace('#(<a .*?>)(.*?)</a>#ei', '$this->replace_callback(stripslashes("$1"), stripslashes("$2"))', $content);

		return true;
	}


	/**
	 * Replaces parts of the post that are links by links through the redirect script and
	 * inserts non-existing posts into the Linkblog.
	 */
	function replace_callback( $aopentag, $atext )
	{
		global $current_User, $localtimenow, $locales, $Debuglog, $ItemCache;

		$r = $aopentag.$atext.'</a>'; // standard return

		// get URL
		if( preg_match( '# href\s*=\s*"(.*?)"#i', $aopentag, $matches ) )
		{
			$url = $matches[1];
		}
		else
		{ // no href found! - should not happen
			$Debuglog->add('No href for Linklogger found!', 'error');
			return $r;
		}

		// build the redirect-URL
		$linklogger_redirurl = sprintf($this->destformat, $url);
		$r = preg_replace( '#href\s*=\s*".*?"#', 'href="'.$linklogger_redirurl.'"', $aopentag ).$atext.'</a>';


		// get title for autolinkblog entry
		$atext = strip_tags( $atext );
		if( empty($atext) )
		{ // can happen if we just link an img or similar
			if( preg_match( '# title\s*=\s*"(.*?)"#i', $aopentag, $matches ) )
			{ // get title out of a-tag
				$linklogger_title = $matches[1];
			}
			else
			{ // use URL for title
				$linklogger_title = $url;
			}
		}
		else
		{
			$linklogger_title = $atext;
		}


		#pre_dump( $linklogger_redirurl, 'redir to' );
		$linklogger_urltitle = urltitle_validate( '', $linklogger_title, 0, true );
		#pre_dump( $linklogger_urltitle, 'url title' );


		// look up if URL exists  TODO: check if it's in the Autolinkblog!
		if( ($Item = $ItemCache->get_by_urltitle( $linklogger_urltitle, false )) )
		{ // already there
			if( $Item->url == $linklogger_redirurl )
			{ // with exact our URL
				return $r;
			}
		}


		// not stored yet, insert:

		// get lang/locale     QUESTION: are there other ways?
		if( preg_match( '# lang\s*=\s*"(.*?)"#i', $aopentag, $matches ) )
		{
			$urllocale = locale_by_lang( $matches[1] );  // might be sth we don't know though (locale_by_lang() should return false and we had to check for it)
		}
		else
		{
			// TODO: get the locale of the post where the link is in!
			global $default_locale;
			$urllocale = $default_locale;
		}

		// the linklogger entry does not exist yet, so create it
		$edited_Item = & new Item();
		$edited_Item->insert(
			$current_User->ID,
			$linklogger_title,
			'',    // content
			date( 'Y-m-d H:i:s', $localtimenow ),
			$this->linklogger_cat_ID,
			$this->linklogger_extra_cat_IDs,
			$this->linklogger_post_status,
			$urllocale,
			'',    // trackbacks
			0,     // autoBR
			true,  // pingsdone
			'',    // post_urltitle
			$linklogger_redirurl,  // post_url
			$this->linklogger_comments,
			array('') // renderers
		);


		return $r;
	}

}
?>
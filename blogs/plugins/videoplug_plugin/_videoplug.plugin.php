<?php
/**
 * This file implements the GMcode plugin for b2evolution
 *
 * GreyMatter style formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Replaces GreyMatter markup in HTML (not XML).
 *
 * @package plugins
 */
class videoplug_plugin extends Plugin
{
	var $code = 'evo_videoplug';
	var $name = 'Video Plug';
	var $priority = 65;
	var $apply_rendering = 'opt-out';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '1.10';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Video plug for a few popular video sites.');
		$this->long_desc = T_('This is a basic video plug pluigin. Use it by entering [video:youtube:123xyz] or [video:dailymotion:123xyz] into your post, where 123xyz is the ID of the video.');
	}


	/**
	 * Perform rendering
	 *
	 * @todo add more video sites, anyone...
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		// Youtube:
		$content = preg_replace( '¤\[video:youtube:(.+?)]¤', '<div class="videoblock"><object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/\\1"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/\\1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"></embed></object></div>', $content );

		// Dailymotion:
		$content = preg_replace( '¤\[video:dailymotion:(.+?)]¤', '<div class="videoblock"><object width="425" height="356"><param name="movie" value="http://www.dailymotion.com/swf/\\1"></param><param name="allowfullscreen" value="true"></param><embed src="http://www.dailymotion.com/swf/\\1" type="application/x-shockwave-flash" width="425" height="356" allowfullscreen="true"></embed></object></div>', $content );

		// Google video:
		$content = preg_replace( '¤\[video:google:(.+?)]¤', '<div class="videoblock"><embed style="width:400px; height:326px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=\\1&hl=en" flashvars=""></embed></div>', $content );

		return true;
	}


	/**
	 * Perform rendering for XML feeds
	 *
	 * @see Plugin::RenderItemAsXml()
	 */
	function RenderItemAsXml( & $params )
	{
		$this->RenderItemAsHtml( $params );

		/*
		$content = & $params['data'];
		$Item = & $params['Item'];

		$content = preg_replace( '¤\[video:.+?]¤', '<p>'.$Item->get_permanent_link( T_('See video').' &raquo;' ).'</p>', $content );
		*/

		return true;
	}

	/**
	 * Display a toolbar in admin
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( $params['edit_layout'] == 'simple' )
		{	// This is too complex for simple mode, don't display it:
			return false;
		}

		echo '<div class="edit_toolbar">';
		echo T_('Video').': ';
		echo '<input type="button" id="video_youtube" title="'.T_('Insert Youtube video').'" class="quicktags" onclick="videotag(\'youtube\');" value="'.T_('YouTube').'" />';
		echo '<input type="button" id="video_google" title="'.T_('Insert Google video').'" class="quicktags" onclick="videotag(\'google\');" value="'.T_('Google video').'" />';
		echo '<input type="button" id="video_dailymotion" title="'.T_('Insert DailyMotion video').'" class="quicktags" onclick="videotag(\'dailymotion\');" value="'.T_('DailyMotion').'" />';

		echo '</div>';

		?>
		<script type="text/javascript">
			//<![CDATA[
			function videotag( tag )
			{
				var video_ID = prompt('<?php echo T_('Enter video ID from') ?> '+tag+':', '' );
				if( ! video_ID )
				{
					return;
				}

				tag = '[video:'+tag+':'+video_ID+']';

				textarea_wrap_selection( b2evoCanvas, tag, '', 1 );
			}
			//]]>
		</script>
		<?php

		return true;
	}

}


/*
 * $Log$
 * Revision 1.2  2007/04/20 02:54:17  fplanque
 * videoplug plugin
 *
 * Revision 1.1.2.2  2007/04/19 01:14:43  fplanque
 * minor
 *
 * Revision 1.1.2.1  2007/04/19 01:03:54  fplanque
 * basic videoplug plugin
 *
 */
?>
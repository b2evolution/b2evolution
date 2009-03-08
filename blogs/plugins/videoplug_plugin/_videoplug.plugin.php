<?php
/**
 * This file implements the Video Plug plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Replaces Video Plug markup in HTML (not XML).
 *
 * @todo dh> Hook into AdminBeforeItemEditUpdate and validate provided video IDs
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
	var $version = '2.2';
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

		// fp> removed some embeds to make it xhtml compliant, using only object. (Hari style ;)
		// anyone, feel free to clean up the ones that have no object tag at all.

		// Youtube:
		$content = preg_replace( '#\[video:youtube:(.+?)]#', '<div class="videoblock"><object data="http://www.youtube.com/v/\\1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"><param name="movie" value="http://www.youtube.com/v/\\1"></param><param name="wmode" value="transparent"></param></object></div>', $content );

		// Dailymotion:
		$content = preg_replace( '#\[video:dailymotion:(.+?)]#', '<div class="videoblock"><object data="http://www.dailymotion.com/swf/\\1" type="application/x-shockwave-flash" width="425" height="335" allowfullscreen="true"><param name="movie" value="http://www.dailymotion.com/swf/\\1"></param><param name="allowfullscreen" value="true"></param></object></div>', $content );

		// Google video:
		$content = preg_replace( '#\[video:google:(.+?)]#', '<div class="videoblock"><embed style="width:400px; height:326px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=\\1&hl=en" flashvars=""></embed></div>', $content );

		// LiveVideo
		$content = preg_replace( '#\[video:livevideo:(.+?)]#', '<div class="videoblock"><object src="http://www.livevideo.com/flvplayer/embed/\\1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"><param name="movie" value="http://www.livevideo.com/flvplayer/embed/\\1"></param><param name="wmode" value="transparent"></param></object></div>', $content );

		// iFilm
		$content = preg_replace( '#\[video:ifilm:(.+?)]#', '<div class="videoblock"><embed width="425" height="350" src="http://www.ifilm.com/efp" quality="high" bgcolor="000000" name="efp" align="middle" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" flashvars="flvbaseclip=\\1"> </embed></div>', $content );

		// vimeo
		// blueyed> TODO: might want to use oEmbed (to get title etc separately and display it below video): http://vimeo.com/api/docs/oembed
		$content = preg_replace( '#\[video:vimeo:(.+?)]#', '<div class="videoblock"><object data="http://vimeo.com/moogaloop.swf?clip_id=$1&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" width="400" height="225" type="application/x-shockwave-flash">	<param name="allowfullscreen" value="true" />	<param name="allowscriptaccess" value="always" />	<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=$1&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" /></object></div>', $content );

		return true;
	}



	/**
	 * Perform rendering for XML feeds
	 *
	 * @see Plugin::RenderItemAsXml()
	 */
	function RenderItemAsXml( & $params )
	{
		return $this->RenderItemAsHtml( $params );
	}

	/**
	 * Display a toolbar in admin.
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

		echo '<div class="edit_toolbar" id="video_toolbar">';
		echo T_('Video').': ';
		echo '<input type="button" id="video_youtube" title="'.T_('Insert Youtube video').'" class="quicktags" onclick="videotag(\'youtube\');" value="YouTube" />';
		echo '<input type="button" id="video_google" title="'.T_('Insert Google video').'" class="quicktags" onclick="videotag(\'google\');" value="Google video" />';
		echo '<input type="button" id="video_dailymotion" title="'.T_('Insert DailyMotion video').'" class="quicktags" onclick="videotag(\'dailymotion\');" value="DailyMotion" />';
		echo '<input type="button" id="video_livevideo" title="'.T_('Insert LiveVideo video').'" class="quicktags" onclick="videotag(\'livevideo\');" value="LiveVideo" />';
		echo '<input type="button" id="video_ifilm" title="'.T_('Insert iFilm video').'" class="quicktags" onclick="videotag(\'ifilm\');" value="iFilm" />';
		echo '<input type="button" id="video_vimeo" title="'.T_('Insert vimeo video').'" class="quicktags" onclick="videotag(\'vimeo\');" value="vimeo" />';

		echo '</div>';

		?>
		<script type="text/javascript">
			//<![CDATA[
			function videotag( tag )
			{
				while( 1 )
				{
					var valid_video_ID = false;
					var p = '<?php echo TS_('Enter video ID from %s:') ?>';
					var video_ID = prompt( p.replace( /%s/, tag ), '' );
					if( ! video_ID )
					{
						return;
					}

					// Validate Video ID:
					// TODO: verify validation / add for others..
					switch( tag )
					{
						case 'youtube':
							if( video_ID.match( /^\w+$/ ) )
							{ // valid
								valid_video_ID = true;
							}
							break;

						case 'vimeo':
							if( video_ID.match( /^\d+$/ ) )
							{ // valid
								valid_video_ID = true;
							}
							break;

						default:
							valid_video_ID = true;
							break;
					}

					if( valid_video_ID )
					{
						break;
					}
					alert( '<?php echo TS_('The video ID is invalid.'); ?>' );
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
 * Revision 1.20  2009/03/08 23:57:52  fplanque
 * 2009
 *
 * Revision 1.19  2009/03/04 02:07:40  fplanque
 * keep it real
 *
 * Revision 1.18  2009/03/03 14:58:15  afwas
 * Added class 'edit_toolbar'. These toolbars now have two classes (thanks blueyed.)
 *
 * Revision 1.17  2009/03/03 14:28:49  tblue246
 * Fixing messed up regexp delimiters (wrong charset?!)
 *
 * Revision 1.16  2009/03/03 13:04:49  afwas
 * All toolbars have the class 'edit_toolbar'. Changed to 'video_toolbar'
 *
 * Revision 1.15  2009/02/24 22:48:54  blueyed
 * videoplug Plugin: Fix static ID for vimeo tag
 *
 * Revision 1.14  2008/09/24 09:29:57  fplanque
 * minor
 *
 * Revision 1.13  2008/09/22 19:29:17  blueyed
 * videoplug_plugin: add vimeo service
 *
 * Revision 1.12  2008/06/30 20:49:50  blueyed
 * videoplug_plugin: validate video ID (only for YouTube currently); do not translate video service names
 *
 * Revision 1.11  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.10  2007/11/29 21:52:06  fplanque
 * Hari style embed removal
 *
 * Revision 1.9  2007/09/16 22:16:46  fplanque
 * minor
 *
 * Revision 1.8  2007/09/16 03:04:21  blueyed
 * fixed doc. added question about using i18n for service names
 *
 * Revision 1.7  2007/09/11 20:57:51  fplanque
 * minor fixes
 *
 * Revision 1.6  2007/07/07 22:21:34  fplanque
 * doc
 *
 * Revision 1.5  2007/05/15 21:22:16  blueyed
 * Use+replace %s for dynamic value instead of appending some string!
 *
 * Revision 1.4  2007/05/04 20:43:09  fplanque
 * MFB
 *
 * Revision 1.1.2.5  2007/04/25 22:18:09  fplanque
 * (c) 2007
 *
 * Revision 1.1.2.4  2007/04/24 11:45:13  yabs
 * added a couple more video sources
 *
 * Revision 1.1.2.3  2007/04/20 01:44:24  fplanque
 * added toolbar
 *
 * Revision 1.1.2.2  2007/04/19 01:14:43  fplanque
 * minor
 *
 * Revision 1.1.2.1  2007/04/19 01:03:54  fplanque
 * basic videoplug plugin
 *
 */
?>

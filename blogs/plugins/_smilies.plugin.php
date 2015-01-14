<?php
/**
 * This file implements the Image Smilies Renderer plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class smilies_plugin extends Plugin
{
	var $code = 'b2evSmil';
	var $name = 'Smilies';
	/**
	 * @todo dh> Should get a low priority (e.g. 80) so it does not create icon image
	 *           tags which then get processed by another plugin.
	 *           Is there any benefit from a high prio like now? So that we do not
	 *           match "generated" simlies later?
	 * fp> There is... I can't remember the exact problem thouh. Probably some interaction with the code highlight or the video plugins.
	 */
	var $priority = 25;
	var $version = '5.0.0';
	var $group = 'rendering';
	var $number_of_installs = 3; // QUESTION: dh> why 3?

	/**
	 * Text similes search array
	 *
	 * @access private
	 */
	var $search;

	/**
	 * IMG replace array
	 *
	 * @access private
	 */
	var $replace;

	/**
	 * Smiley definitions
	 *
	 * @access private
	 */
	var $smilies;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Graphical smileys');
		$this->long_desc = T_('This renderer will convert text smilies like :) to graphical icons.<br />
Optionally, it will also display a toolbar for quick insertion of smilies into a post.');
	}


	/**
	* Defaults for user specific settings: "Display toolbar"
	 *
	 * @return array
	 */
	function GetDefaultSettings()
	{
		global $rsc_subdir;
		return array(
				'use_toolbar_default' => array(
					'label' => T_('Use smilies toolbar'),
					'defaultvalue' => 0,
					'type' => 'checkbox',
					'note' => T_('This is the default setting. Users can override it in their profile.'),
				),
				// TODO (yabs) : Display these as images and individual inputs
				'smiley_list' => array(
					'label' => $this->T_('Smiley list'),
					'note' => sprintf( $this->T_('This is the list of smileys [one per line], in the format : char_sequence image_file // optional comment<br />
							To disable a smiley, just add one or more spaces to the start of its setting<br />
							You can add new smiley images by uploading the images to the %s folder.' ), '<span style="font-weight:bold">'.$rsc_subdir.'smilies/</span>' ),
					'type' => 'html_textarea', // allows smilies with "<" in them
					'rows' => 10,
					'cols' => 60,
					'defaultvalue' => '
 =>       icon_arrow.gif
:!:      icon_exclaim.gif
:?:      icon_question.gif
:idea:   icon_idea.gif
:)       icon_smile.gif
:D       icon_biggrin.gif
:p       icon_razz.gif
B)       icon_cool.gif
;)       icon_wink.gif
:>       icon_twisted.gif
:roll:   icon_rolleyes.gif
:oops:   icon_redface.gif
:|       icon_neutral.gif
:-/      icon_confused.gif
:(       icon_sad.gif
 >:(      icon_mad.gif
:\'(      icon_cry.gif
|-|      icon_wth.gif
:>>      icon_mrgreen.gif
:yes:    grayyes.gif
;D       graysmilewinkgrin.gif
:P       graybigrazz.gif
:))      graylaugh.gif
88|      graybigeek.gif
:.       grayshy.gif
:no:     grayno.gif
XX(      graydead.gif
:lalala: icon_lalala.gif
:crazy:  icon_crazy.gif
>:XX     icon_censored.gif
 :DD     icon_lol.gif
 :o      icon_surprised.gif
 8|      icon_eek.gif
 >:-[    icon_evil.gif
 :)      graysmile.gif
 :b      grayrazz.gif
 )-o     grayembarrassed.gif
 U-(     grayuhoh.gif
 :(      graysad.gif
 :**:    graysigh.gif     // alternative: graysighw.gif
 :??:    grayconfused.gif // alternative: grayconfusedw.gif
 :`(     graycry.gif
 >:-(    graymad.gif
 :##      grayupset.gif   // alternative: grayupsetw.gif
 :zz:    graysleep.gif    // alternative: graysleepw.gif
 :wave:  icon_wave.gif',
				),
			);
}


	/**
	 * Allowing the user to override the display of the toolbar.
	 *
	 * @return array
	 */
	function GetDefaultUserSettings()
	{
		return array(
				'use_toolbar' => array(
					'label' => T_('Use smilies toolbar'),
					'defaultvalue' => $this->Settings->get('use_toolbar_default'),
					'type' => 'checkbox',
				),
			);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_post_rendering' => 'opt-in' ) );
		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Display a toolbar in admin
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( $this->UserSettings->get('use_toolbar') )
		{
			return $this->display_smiley_bar();
		}
		return false;
	}


	/**
	 * Event handler: Called when displaying editor toolbars.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		if( !empty( $params['Comment'] ) )
		{ // Comment is set, get Blog from comment
			$Comment = & $params['Comment'];
			if( !empty( $Comment->item_ID ) )
			{
				$comment_Item = & $Comment->get_Item();
				$Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{ // Comment is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_comment_rendering" plugin collection setting is not available
				return false;
			}
		}

		if( $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog )
		&& ( ( is_logged_in() && $this->UserSettings->get( 'use_toolbar' ) )
			|| ( !is_logged_in() && $this->Settings->get( 'use_toolbar_default' ) ) ) )
		{
			return $this->display_smiley_bar();
		}
		return false;
	}


	/**
	 * Display the smiley toolbar
	 *
	 * @return boolean did we display a toolbar?
	 */
	function display_smiley_bar()
	{
		$this->InitSmilies();	// check smilies cached

		$grins = '';
		$smiled = array();
		foreach( $this->smilies as $smiley )
		{
			if( ! in_array($smiley['image'], $smiled) )
			{ // include any smiley only once
				$smiled[] = $smiley['image'];

				$grins .= $this->get_smiley_img_tag( $smiley, array(
					'class' => 'top',
					'data-func' => 'textarea_wrap_selection|b2evoCanvas|'.str_replace( array( "'", '|' ), array( "\'", '\|' ), $smiley['code'] ).'| |1' ) )
					.' ';
			}
		}

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		echo '<div class="edit_toolbar" id="smiley_toolbar">'.$grins.'</div>' ;

		return true;
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$this->InitSmilies();	// check smilies are already cached


		if( ! isset( $this->search ) )
		{	// We haven't prepared the smilies yet
			$this->search = array();

			$tmpsmilies = $this->smilies;
			usort($tmpsmilies, array(&$this, 'smiliescmp'));

			foreach( $tmpsmilies as $smiley )
			{
				// Detect html entities if smile code, to find encoded code version when HTML is disabled in content
				$html_entities_exist = ( strpos( $smiley['code'], '>' ) !== false || strpos( $smiley['code'], '>' ) !== false );

				$this->search[] = $smiley['code'];
				$smiley_masked = '';
				for( $i = 0; $i < strlen( $smiley['code'] ); $i++ )
				{
					$smiley_masked .= '&#'.ord( substr( $smiley['code'], $i, 1 ) ).';';
				}
				if( $html_entities_exist )
				{ // Add code version with encoded html entities
					$this->search[] = str_replace( array( '<', '>' ), array( '&lt;', '&gt;' ), $smiley['code'] );
				}
				$smiley['code'] = $smiley_masked;
				$this->replace[] = $this->get_smiley_img_tag( $smiley );
				if( $html_entities_exist )
				{ // Add a duplicate of replacement when smile code has html entities
					$this->replace[] = $this->get_smiley_img_tag( $smiley );
				}
			}
		}


		// REPLACE:  But only in non-HTML blocks, totally excluding <CODE>..</CODE> and <PRE>..</PRE>

		$content = & $params['data'];

		// Lazy-check first, using stristr() (stripos() is only available since PHP5):
		if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false )
		{ // Call ReplaceTagSafe() on everything outside code/pre:
			$content = callback_on_non_matching_blocks( $content,
					'~<(code|pre)[^>]*>.*?</\1>~is',
					array( & $this, 'ReplaceTagSafe' ) );
		}
		else
		{ // No code/pre blocks, replace on the whole thing
			$content = $this->ReplaceTagSafe( $content );
		}

		return true;
	}


	/**
	 * @param array Smiley
	 * @param array Override params, e.g. "class"
	 */
	function get_smiley_img_tag( $smiley, $override_fields = array() )
	{
		$attribs = array(
			'src' => $smiley['image'],
			'title' => format_to_output( $smiley['code'], 'htmlattr' ),
			'alt' => format_to_output( $smiley['code'], 'htmlattr' ),
			'class' => 'middle',
			);

		if( $smiley_wh = imgsize($smiley['path'], 'widthheight_assoc') )
			$attribs += $smiley_wh;

		if( $override_fields )
			$attribs = $override_fields + $attribs;

		return '<img'.get_field_attribs_as_string($attribs).' />';
	}


	/**
	 * This callback gets called once after every tags+text chunk
	 * @return string Text with replaced smilies
	 */
	function preg_insert_smilies_callback( $text )
	{
		return str_replace( $this->search, $this->replace, $text );
	}


	/**
	 * Replace smilies in non-HTML-tag portions of the text.
	 * @uses callback_on_non_matching_blocks()
	 */
	function ReplaceTagSafe( $text )
	{
		return callback_on_non_matching_blocks( $text, '~<[^>]*>~', array( & $this, 'ReplaceInlinePlaceholderSafe' ) );
	}


	/**
	 * Replace smilies in non inline placeholders portions of the text.
	 * @uses callback_on_non_matching_blocks()
	 */
	function ReplaceInlinePlaceholderSafe( $text )
	{
		return callback_on_non_matching_blocks( $text, '~\[(image|file|inline):\d+:?[^\]]*\]~', array( & $this, 'preg_insert_smilies_callback' ) );
	}


	/**
	 * sorts the smilies' array by length
	 * this is important if you want :)) to superseede :) for example
	 */
	function smiliescmp($a, $b)
	{
		if( ($diff = strlen( $b[ 'code' ] ) - strlen( $a[ 'code' ] ) ) == 0)
		{
			return strcmp( $a[ 'code' ], $b[ 'code' ] );
		}
		return $diff;
	}


	/**
	 * Initiates the smiley array if not already initiated
	 *
	 * Attempts to use skin specific smileys where available
	 *	- skins_adm/skin/rsc/smilies/
	 *	- skins/skin/smilies/
	 *
	 * Attempts to fallback to default smilies
	 *	- rsc/smilies/
	 *
	 * If no image file found the smiley is not added
	 *
	 * @return array of available smilies( code, image url )
	 */
	function InitSmilies()
	{
		if( isset( $this->smilies ) )
		{ // smilies are already cached
			return;
		}

		global $admin_skin, $adminskins_path, $adminskins_url, $rsc_path, $rsc_url, $skin, $skins_path, $skins_url;

		// set the skin path/url and the default (rsc) path/url
		$currentskin_path = ( is_admin_page() ? $adminskins_path.$admin_skin.'/rsc' : $skins_path.$skin ).'/smilies/';
		$currentskin_url = ( is_admin_page() ? $adminskins_url.$admin_skin.'/rsc' : $skins_url.$skin ).'/smilies/';
		$default_path = $rsc_path.'smilies/';
		$default_url = $rsc_url.'smilies/';

		$skin_has_smilies = is_dir( $currentskin_path );	// check if skin has a /smilies/ folder

		$this->smilies = array();
		$temp_list = explode( "\n", str_replace( array( "\r", "\t" ), '', $this->Settings->get( 'smiley_list' ) ) );

		foreach( $temp_list as $temp_smiley )
		{
			$a_smiley = explode( '<->',	preg_replace_callback( '#^(\S.+?\s)(.+?)(\/\/.*?)*$#', array( $this, 'get_smiley' ),$temp_smiley ) );
			if( isset( $a_smiley[0] ) and isset( $a_smiley[1] ) )
			{
				// lets see if the file exists
				$temp_img = trim( $a_smiley[1] );
				if( $skin_has_smilies && is_file( $currentskin_path.$temp_img ) )
				{
					$temp_url = $currentskin_url.$temp_img;	// skin has it's own smiley, use it
					$temp_path = $currentskin_path.$temp_img;
				}
				elseif ( is_file( $default_path.$temp_img ) )
				{
					$temp_url = $default_url.$temp_img; // no skin image, but default smiley found so use it
					$temp_path = $default_path.$temp_img;
				}
				else
				{
					$temp_url = ''; // no smiley image found, so don't add the smiley
				}

				if( $temp_url )
					$this->smilies[] = array( 'code' => trim( $a_smiley[0] ), 'image' => $temp_url, 'path' => $temp_path );
			}
		}
	}

	// returns the relevant smiley parts (char_code, image_file)
	function get_smiley( $smiley_parts )
	{
		return ( ( isset( $smiley_parts[1] ) && isset( $smiley_parts[2] ) ) ? $smiley_parts[1].'<->'.$smiley_parts[2] : '' );
	}
}

?>
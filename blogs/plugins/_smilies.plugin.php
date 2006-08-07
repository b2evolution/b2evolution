<?php
/**
 * This file implements the Image Smilies Renderer plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
	var $priority = 80;
	var $version = '1.9-dev';
	var $apply_rendering = 'opt-out';

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
		return array(
				'use_toolbar_default' => array(
					'label' => T_( 'Use smilies toolbar' ),
					'defaultvalue' => '1',
					'type' => 'checkbox',
					'note' => T_( 'This is the default setting. Users can override it in their profile.' ),
				),
				'render_comments_default' => array(
					'label' => $this->T_('Render comments' ),
					'note' => $this->T_('If enabled the smileys in comments will also be rendered'),
					'defaultvalue' => '1',
					'type' => 'checkbox',
				),
				'smiley_list' => array(
					'label' => $this->T_( 'Smiley list list'),
					'note' => $this->T_( 'This is the list of smileys [one per line], in the format : char sequence => image_file' ),
					'type' => 'html_textarea', // allows smilies with "<" in them
					'rows' => 10,
					'cols' => 40,
					'defaultvalue' => '=> => icon_arrow.gif
:!:	=> icon_exclaim.gif,
:?: => icon_question.gif,
:idea: => icon_idea.gif
:) => icon_smile.gif
:D => icon_biggrin.gif
:p => icon_razz.gif
B) => icon_cool.gif
;) => icon_wink.gif
:> => icon_twisted.gif
:roll: => icon_rolleyes.gif
:oops: => icon_redface.gif
:| => icon_neutral.gif
:-/ => icon_confused.gif
:( => icon_sad.gif
>:( => icon_mad.gif
:\'( => icon_cry.gif
|-| => icon_wth.gif
:>> => icon_mrgreen.gif
:yes:	=> grayyes.gif,
;D => graysmilewinkgrin.gif
:P => graybigrazz.gif
:)) => graylaugh.gif
88| => graybigeek.gif
:. => grayshy.gif
:no: => grayno.gif,
XX( => graydead.gif
:lalala: => icon_lalala.gif
:crazy: => icon_crazy.gif
>:XX => icon_censored.gif',
				),
			);
								//	':DD'				=> 'icon_lol.gif',
								//	':o'				=> 'icon_surprised.gif',
								//	'8|'				=> 'icon_eek.gif',
								//	'>:-['			=> 'icon_evil.gif',
								//	':)'				=> 'graysmile.gif',
								//	':b'				=> 'grayrazz.gif',
								//	')-o'				=> 'grayembarrassed.gif',
								//	'U-('				=> 'grayuhoh.gif',
								//	':('				=> 'graysad.gif',
								//	':**:'			=> 'graysigh.gif', 			// alternative: graysighw.gif
								//	':??:'			=> 'grayconfused.gif',  // alternative: grayconfusedw.gif
								//	':`('				=> 'graycry.gif',
								//	'>:-('			=> 'graymad.gif',
								//	':##'				=> 'grayupset.gif',			// alternative: grayupsetw.gif
								//	':zz:'			=> 'graysleep.gif', 		// alternative: graysleepw.gif
								//	':wave:'		=> 'icon_wave.gif',
}


	/**
	 * Allowing the user to deactivate the toolbar..
	 * Allowing user to deactivate smilies in comments
	 *
	 * @return array
	 */
	function GetDefaultUserSettings()
	{
		return array(
				'use_toolbar' => array(
					'label' => T_( 'Use smilies toolbar' ),
					'defaultvalue' => $this->Settings->get('use_toolbar_default'),
					'type' => 'checkbox',
				),
				'render_comments' => array(
					'label' => $this->T_('Render comments' ),
					'note' => $this->T_('If enabled the smileys in comments will also be rendered'),
					'defaultvalue' => $this->Settings->get( 'render_comments_default' ),
					'type' => 'checkbox',
				),
			);
	}

	/**
	 * Display a toolbar
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( ! $this->UserSettings->get('use_toolbar') )
		{
			return false;
		}

		$this->InitSmilies();	// check smilies cached

		$grins = '';
		$smiled = array();
		foreach( $this->smilies as $smiley )
		{
			if (!in_array($smiley[ 'image' ], $smiled))
			{
				$smiled[] = $smiley[ 'image'];
				$smiley[ 'code' ] = str_replace(' ', '', $smiley[ 'code' ]);
				$grins .= '<img src="'.$smiley[ 'image' ].'" title="'.$smiley[ 'code' ].'" alt="'.$smiley[ 'code' ]
									.'" class="top" onclick="grin(\''. str_replace("'","\'",$smiley[ 'code' ]). '\');" /> ';
			}
		}

		print('<div class="edit_toolbar">'. $grins. '</div>');
		ob_start();
		?>
		<script type="text/javascript">
		function grin(tag)
		{
			if( typeof b2evo_Callbacks == 'object' )
			{ // see if there's a callback registered that should handle this:
				if( b2evo_Callbacks.trigger_callback("insert_raw_into_"+b2evoCanvas.id, tag) )
				{
					return;
				}
			}

			var myField = b2evoCanvas;

			if (document.selection) {
				myField.focus();
				sel = document.selection.createRange();
				sel.text = tag;
				myField.focus();
			}
			else if (myField.selectionStart || myField.selectionStart == '0') {
				var startPos = myField.selectionStart;
				var endPos = myField.selectionEnd;
				var cursorPos = endPos;
				myField.value = myField.value.substring(0, startPos)
								+ tag
								+ myField.value.substring(endPos, myField.value.length);
				cursorPos += tag.length;
				myField.focus();
				myField.selectionStart = cursorPos;
				myField.selectionEnd = cursorPos;
			}
			else {
				myField.value += tag;
				myField.focus();
			}
		}

		</script>
		<?php
		$grins = ob_get_contents();
		ob_end_clean();
		print($grins);

		return true;
	}

	/**
	 * Perform rendering
	 *
	 * @see Plugin::FilterCommentContent()
	 */
	function FilterCommentContent( & $params )
	{
		if( $this->UserSettings->get( 'render_comments' ) )
		{
			$this->RenderItemAsHtml( $params );
		}
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
				$this->search[] = $smiley[ 'code' ];
				$smiley_masked = '';
				for ($i = 0; $i < strlen($smiley[ 'code' ] ); $i++ )
				{
					$smiley_masked .=  '&#'.ord(substr($smiley[ 'code' ], $i, 1)).';';
				}

				// We don't use getimagesize() here until we have a mean
				// to preprocess smilies. It takes up to much time when
				// processing them at display time.
				$this->replace[] = '<img src="'.$smiley[ 'image' ].'" alt="'.$smiley_masked.'" class="middle" />';
			}
		}


		// REPLACE:  But only in non-HTML blocks, totally excluding <CODE>..</CODE> and <PRE>..</PRE>

		$content = & $params['data'];

		// Lazy-check first, using stristr() (stripos() is only available since PHP5):
		if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false )
		{ // Call ReplaceTagSafe() on everything outside <pre></pre> and <code></code>:
			$content = callback_on_non_matching_blocks( $content,
					'~<(code|pre)[^>]*>.*?</\1>~is',
					array( & $this, 'ReplaceTagSafe' ) );
		}
		else
		{ // No CODE or PRE blocks, replace on the whole thing
			$content = $this->ReplaceTagSafe($content);
		}

		return true;
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
	function ReplaceTagSafe($text)
	{
		return callback_on_non_matching_blocks( $text, '~<[^>]*>~', array(&$this, 'preg_insert_smilies_callback') );
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
			$a_smiley = explode( '<->', preg_replace( '#(.+)( )=>(.+?)#', '$1<->$2',$temp_smiley ) );

			if( isset( $a_smiley[0] ) and isset( $a_smiley[1] ) )
			{
				// lets see if the file exists
				$temp_img = trim( $a_smiley[1] );
				if( $skin_has_smilies && is_file( $currentskin_path.$temp_img ) )
				{
					$temp_url = $currentskin_url.$temp_img;	// skin has it's own smiley, use it
				}
				elseif ( is_file( $default_path.$temp_img ) )
				{
					$temp_url = $default_url.$temp_img; // no skin image, but default smiley found so use it
				}
				else
				{
					$temp_url = ''; // no smiley image found, so don't add the smiley
				}

				if( $temp_url )
					$this->smilies[] = array( 'code' => trim( $a_smiley[0] ),'image' => $temp_url );
			}
		}
	}

}


/*
 * $Log$
 * Revision 1.31  2006/08/07 18:26:21  fplanque
 * nuked obsolete smilies conf file
 *
 * Revision 1.30  2006/08/01 23:55:14  blueyed
 * minor: doc; removed level of indentation
 *
 * Revision 1.29  2006/08/01 08:44:31  yabs
 * Minor change - checks if skin has /smilies/ folder before start of checking for images
 *
 * Revision 1.28  2006/08/01 08:20:47  yabs
 * Added ability for admin skins to override default smiley images
 * Tidied up my previous code
 *
 * Revision 1.27  2006/07/31 16:19:04  yabs
 * Moved settings to admin
 * Added smilies in comments ( as user setting )
 * Added ability for blog skins to override default smiley images
 *
 * *note*
 * These changes are a tad quick 'n' dirty, I'm working on a cleaner version and will commit soon
 *
 * Revision 1.26  2006/07/12 21:13:17  blueyed
 * Javascript callback handler (e.g., for interaction of WYSIWYG editors with toolbar plugins)
 *
 * Revision 1.25  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.24  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.23  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.22  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.21  2006/05/30 20:26:59  blueyed
 * typo
 *
 * Revision 1.20  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.19  2006/04/24 20:16:08  blueyed
 * Use callback_on_non_matching_blocks(); excluding PRE and CODE blocks
 *
 * Revision 1.18  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
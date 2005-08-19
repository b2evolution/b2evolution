<?php

/**
 * This file implements the code formatting plugin.
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author Yabba: Yabba - {@link http://www.innervisions.org.uk/}
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Code Plugin
 *
 * This plugin responds to rendering event
 *
 * @package plugins
 */
class code_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name = 'Code';
	var $code = 'evo_Code';
	var $priority = 100;
	var $version = 'CVS $Revision$';
	var $author = 'Yabba';
	var $help_url = 'http://b2evolution.net/';

	/*
	 * These variables MAY be overriden.
	 */
	var $is_tool = false;
	var $apply_when = 'opt-out';
	var $apply_to_html = true;
	var $apply_to_xml = true;


	/**
	 * Constructor.
	 *
	 * Should set name and description in a localizable fashion.
	 * NOTE FOR PLUGIN DEVELOPERS UNFAMILIAR WITH OBJECT ORIENTED DEV:
	 * This function has the same name as the class, this makes it a "constructor".
	 * This means that this function will be called automagically by php when this
	 * plugin class is instantiated ("loaded").
	 *
	 * {@internal code_plugin::code_plugin(-)}}
	 */
	function code_plugin()
	{
		$this->short_desc = T_( 'Code formatter plugin' );
		$this->long_desc = T_( 'This plugin renders <pre> blocks' );
	}


	/**
	/**
	 * Event handlers:
	 */

	/**
	 * Event handler: Called when rendering text.
	 *
	 * Perform rendering
	 *
	 * {@internal code_plugin::Render(-)}}
	 *
	 * @param array Associative array of parameters
	 *              (Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function Render( & $params )
	{
		if( ! parent::Render( $params ) )
		{ // We cannot render the required format
			return false;
		}
		$jsCode = '<script type="text/javascript">
		//<![CDATA[
		function codenumbers( theBlock ){
		allKids = theBlock.getElementsByTagName( "span" );
		for ( i=0 ; i < allKids.length ; i++ )
		{
			if( allKids[i].className == "code_number" )
			{
				if ( allKids[i].style.display == "none" )
					allKids[i].style.display = "block";
				else
					allKids[i].style.display = "none";
				}
			}
		}
		//]]>
		</script>';
		$params['data'] = str_replace( "]\n" , "]" , $params['data'] );
		$match = '~(\<pre\>)(?:<br />)?|(\</pre\>)~';
		$codeSlice = preg_split( $match , $params['data'] );
		// every other $codeSlice[] will be <pre></pre> segment
		$output = ''; // reset content
		for ( $i = 0 ; $i < count( $codeSlice ) ; $i++ )
		{
			if ( !is_int( $i/2 ) )
			{ // this is a code slice
				$output .= '<pre>'.doCode( $codeSlice[$i] ).'</pre>';
			}
			else
			{
				$output .= $codeSlice[$i];
			}
		}
		$params['data'] = $output.$jsCode;
		return true;
	}

}

function doCode( $code )
{
	$rows = explode( "\n" , $code );
	$row_num = array();
	$i = 1;

	foreach( $rows as $row )
	{
		if( $i < 10 )
		{
			$i = "0".$i;
		}

		$row_num[] = "<div class='code_line".( is_int($i/2) )."'><span class='code_number'>$i</span>$row<br class='hack' /></div>";

		$i++;
	}

	return '<div class=\'code_block\' onclick=\'codenumbers(this)\'>'.implode( $row_num ).'</div>';
}

?>
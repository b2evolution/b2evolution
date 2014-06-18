<?php
/**
 * This is the header file for login/registering services
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Add CSS:
require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
require_css( 'basic.css', 'rsc_url' ); // Basic styles

require_js( '#jquery#', 'rsc_url' );

// Bootstrap
require_js( '#bootstrap#', 'rsc_url' );
require_css( '#bootstrap_css#', 'rsc_url' );
require_css( '#bootstrap_theme_css#', 'rsc_url' );
require_css( 'bootstrap/b2evo.css', 'rsc_url' );

require_css( 'login.css', 'rsc_url' );

// Set bootstrap classes for messages
$Messages->set_params( array(
		'class_success'  => 'alert alert-success fade in',
		'class_warning'  => 'alert fade in',
		'class_error'    => 'alert alert-danger fade in',
		'class_note'     => 'alert alert-info fade in',
		'before_message' => '<button class="close" data-dismiss="alert">&times;</button>',
	) );

// Form template
$login_form_params = array(
	'layout' => 'fieldset',
	'formstart' => '',
	'title_fmt' => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
	'no_title_fmt' => '<span style="float:right">$global_icons$</span>'."\n",
	'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$>'."\n"
											.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
	'fieldset_end' => '</fieldset></div>'."\n",
	'fieldstart' => '<div class="form-group" $ID$>'."\n",
	'labelclass' => 'control-label col-xs-3',
	'labelstart' => '',
	'labelend' => "\n",
	'labelempty' => '<label class="control-label col-xs-3"></label>',
	'inputstart' => '<div class="controls col-xs-9">',
	'infostart' => '<div class="controls-info col-xs-9">',
	'inputend' => "</div>\n",
	'fieldend' => "</div>\n\n",
	'buttonsstart' => '<div class="form-group"><div class="control-buttons col-sm-offset-3 col-xs-10">',
	'buttonsend' => "</div></div>\n\n",
	'customstart' => '<div class="custom_content">',
	'customend' => "</div>\n",
	'note_format' => ' <span class="help-inline">%s</span>',
	'formend' => '',
);

headers_content_mightcache( 'text/html', 0 );		// NEVER cache the login pages!

$wrap_styles = array();
$container_styles = array();
if( isset( $wrap_width ) )
{ // Set width for wrap
	$wrap_styles[] = 'width:'.$wrap_width;
}
if( isset( $wrap_height ) )
{ // Set height for wrap
	$wrap_styles[] = 'height:'.$wrap_height;
	$container_styles[] = 'min-height:'.$wrap_height;
}

// Display these parts before and after form
$form_before = '<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">$title$</h3>
	</div>
	<div class="panel-body">';
$form_after = '</div></div>';

?>
<!DOCTYPE html>
<html lang="<?php locale_lang() ?>">
<head>
	<title><?php echo $page_title ?></title>
	<meta name="ROBOTS" content="NOINDEX" />
	<meta name="viewport" content="width = 600" />
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
</head>
<body>
<div class="container"<?php echo empty( $container_styles ) ? '' : ' style="'.implode( ';', $container_styles ).'"';?>>
	<div class="wrap"<?php echo empty( $wrap_styles ) ? '' : ' style="'.implode( ';', $wrap_styles ).'"';?>>
<?php
if( ! empty( $login_error ) )
{
	$Messages->add( $login_error, 'error' );
}
$Messages->display();
?>
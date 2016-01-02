<?php
/**
 * This is the header file for login/registering services
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_funcs('skins/_skin.funcs.php');

// Initialize font-awesome icons and use them as a priority over the glyphicons, @see get_icon()
init_fontawesome_icons( 'fontawesome-glyphicons' );

require_js( '#jquery#', 'rsc_url' );

// Bootstrap
require_js( '#bootstrap#', 'rsc_url' );
require_css( '#bootstrap_css#', 'rsc_url' );
// require_css( '#bootstrap_theme_css#', 'rsc_url' );

// rsc/less/bootstrap-basic_styles.less
// rsc/less/bootstrap-basic.less
// rsc/less/bootstrap-evoskins.less
// rsc/build/bootstrap-backoffice-b2evo_base.bundle.css // CSS concatenation of the above
require_css( 'bootstrap-backoffice-b2evo_base.bmin.css', 'rsc_url' ); // Concatenation + Minifaction of the above

require_css( 'login.css', 'rsc_url' );

// Set bootstrap classes for messages
$Messages->set_params( array(
		'class_success'  => 'alert alert-dismissible alert-success fade in',
		'class_warning'  => 'alert alert-dismissible alert-warning fade in',
		'class_error'    => 'alert alert-dismissible alert-danger fade in',
		'class_note'     => 'alert alert-dismissible alert-info fade in',
		'before_message' => '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>',
	) );

// Form template
$login_form_params = array(
	'layout'         => 'fieldset',
	'formclass'     => 'form-horizontal',
	'formstart'      => '<div class="panel panel-default">'
												.'<div class="panel-heading">'
													.'<h3 class="panel-title">$form_title$$form_links$</h3>'
												.'</div>'
												.'<div class="panel-body">',
	'formend'        => '</div></div>',
	'title_fmt'      => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
	'no_title_fmt'   => '<span style="float:right">$global_icons$</span>'."\n",
	'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$>'."\n"
											.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
	'fieldset_end'   => '</fieldset></div>'."\n",
	'fieldstart'     => '<div class="form-group" $ID$>'."\n",
	'fieldend'       => "</div>\n\n",
	'labelclass'     => 'control-label col-xs-3',
	'labelstart'     => '',
	'labelend'       => "\n",
	'labelempty'     => '<label class="control-label col-xs-3"></label>',
	'inputstart'     => '<div class="controls col-xs-9">',
	'inputend'       => "</div>\n",
	'infostart'      => '<div class="controls col-xs-9"><p class="form-control-static">',
	'infoend'        => "</p></div>\n",
	'buttonsstart'   => '<div class="form-group"><div class="control-buttons col-sm-offset-3 col-xs-9">',
	'buttonsend'     => "</div></div>\n\n",
	'customstart'    => '<div class="custom_content">',
	'customend'      => "</div>\n",
	'note_format'    => ' <span class="help-inline">%s</span>',
	// Additional params depending on field type:
	// - checkbox
	'inputclass_checkbox'    => '',
	'inputstart_checkbox'    => '<div class="controls col-sm-9"><div class="checkbox"><label>',
	'inputend_checkbox'      => "</label></div></div>\n",
	'checkbox_newline_start' => '<div class="checkbox">',
	'checkbox_newline_end'   => "</div>\n",
	// - radio
	'fieldstart_radio'       => '<div class="form-group radio-group" $ID$>'."\n",
	'fieldend_radio'         => "</div>\n\n",
	'inputclass_radio'       => '',
	'radio_label_format'     => '$radio_option_label$',
	'radio_newline_start'    => '<div class="radio"><label>',
	'radio_newline_end'      => "</label></div>\n",
	'radio_oneline_start'    => '<label class="radio-inline">',
	'radio_oneline_end'      => "</label>\n",
);
$login_form_params['formstart'] = str_replace( '$form_title$', $page_title, $login_form_params['formstart'] );
if( empty( $use_form_links ) )
{ // Remove the mask for form links because it is not used by current template
	$login_form_params['formstart'] = str_replace( '$form_links$', '', $login_form_params['formstart'] );
}

headers_content_mightcache( 'text/html', 0 );		// NEVER cache the login pages!

$wrap_styles = array();
if( isset( $wrap_width ) )
{ // Set width for wrap
	$wrap_styles[] = 'width:'.$wrap_width;
}

?>
<!DOCTYPE html>
<html lang="<?php locale_lang() ?>">
<head>
	<meta name="viewport" content="width = 600" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title><?php echo $page_title ?></title>
	<meta name="ROBOTS" content="NOINDEX" />
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
</head>
<body>
<div class="container">
	<div class="wrap"<?php echo empty( $wrap_styles ) ? '' : ' style="'.implode( ';', $wrap_styles ).'"';?>>
<?php
if( ! empty( $login_error ) )
{
	$Messages->add( $login_error, 'error' );
}
$Messages->display();
?>
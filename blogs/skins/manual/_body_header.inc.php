<?php
/**
 * This is the BODY header include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage manual
 *
 * @version $Id: _body_header.inc.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $cookie_skin_width_name, $cookie_skin_width_value, $Settings;

/**
 * @var string Name of cookie for skin width
 */
$cookie_skin_width_name = 'skin_width';

if( isset( $_COOKIE[ $cookie_skin_width_name ] ) )
{ // Get skin width from $_COOKIE through param function
	$cookie_skin_width_value = param_cookie( $cookie_skin_width_name, '/^\d+(px|%)$/i', NULL );
	if( empty( $cookie_skin_width_value ) )
	{ // Force illegal value of width to default
		$cookie_skin_width_value = '960px';
	}
}

?>

<div class="PageTop">
	<?php
		// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		skin_container( NT_('Page Top'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start'         => '<div class="$wi_class$">',
				'block_end'           => '</div>',
				'block_display_title' => false,
				'list_start'          => '<ul>',
				'list_end'            => '</ul>',
				'item_start'          => '<li>',
				'item_end'            => '</li>',
			) );
		// ----------------------------- END OF "Page Top" CONTAINER -----------------------------
	?>
</div>

<div class="pageHeader<?php echo $Settings->get( 'site_skins_enabled' ) ? ' site_skins' : ''; ?>">
	<?php
		// ------------------------- "Header" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		skin_container( NT_('Header'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start'       => '<div class="$wi_class$">',
				'block_end'         => '</div>',
				'block_title_start' => '<h1>',
				'block_title_end'   => '</h1>',
			) );
		// ----------------------------- END OF "Header" CONTAINER -----------------------------
	?>
</div>

<div class="top_menu_bg"></div>

<div id="layout">
	<div id="wrapper"<?php echo ( !empty( $cookie_skin_width_value ) ) ? ' style="width:'.$cookie_skin_width_value.'"' : ''; ?>>

<div class="top_menu">
	<ul>
	<?php
		// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		// Note: this container is designed to be a single <ul> list
		skin_container( NT_('Menu'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start'         => '',
				'block_end'           => '',
				'block_display_title' => false,
				'list_start'          => '',
				'list_end'            => '',
				'item_start'          => '<li>',
				'item_end'            => '</li>',
			) );
		// ----------------------------- END OF "Menu" CONTAINER -----------------------------
	?>
	</ul>
	&nbsp;
</div>
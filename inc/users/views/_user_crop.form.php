<?php
/**
 * This file implements the UI view for the user picture crop form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $display_mode, $Settings;

/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;
/**
 * @var instance of User class
 */
global $current_User;
/**
 * @var File that should be cropped
 */
global $cropped_File;

global $image_width, $image_height;

if( $display_mode != 'js' )
{
	// ------------------- PREV/NEXT USER LINKS -------------------
	user_prevnext_links( array(
			'user_tab' => 'avatar'
		) );
	// ------------- END OF PREV/NEXT USER LINKS -------------------
}

$Form = new Form( $form_action, 'user_checkchanges' );

if( is_admin_page() )
{
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
	$ctrl_param = '?ctrl=user&amp;user_tab=avatar&amp;user_ID='.$edited_User->ID;
	
	$form_title = '';
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
}
else
{
	global $Blog;
	$form_title = '';
	$form_class = 'bComment';
	$ctrl_param = url_add_param( $Blog->gen_blogurl(), 'disp='.$disp );
}



if( $display_mode != 'js' && is_admin_page() )
{
	if( !$user_profile_only )
	{
		echo_user_actions( $Form, $edited_User, $action );
	}

	$form_text_title = T_( 'Crop profile picture' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, '', $form_text_title );
}

// Display this error when JS is not enabled
echo '<noscript>'
		.'<p class="error text-danger">'.T_('Please activate Javascript in your browser in order to use this feature.').'</p>'
		.'<style type="text/css">form#user_checkchanges { display:none }</style>'
	.'</noscript>';

$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

if( is_admin_page() )
{
	$Form->hidden_ctrl();
}
else
{
	$Form->hidden( 'disp', $disp );
	$Form->hidden( 'action', $action );
}
$Form->add_crumb( 'user' );
$Form->hidden( 'user_tab', param( 'user_tab_from', 'string', 'avatar' ) );
$Form->hidden( 'user_ID', isset( $edited_User ) ? $edited_User->ID : $current_User->ID );
$Form->hidden( 'file_ID', $cropped_File->ID );
$Form->hidden( 'image_crop_data', '' );
if( isset( $Blog ) )
{
	$Form->hidden( 'blog', $Blog->ID );
}

$close_icon = '';
if( $display_mode == 'js' )
{ // Display a close link for popup window
	$close_icon = action_icon( T_('Close this window'), 'close', '', '', 0, 0, array( 'id' => 'close_button', 'class' => 'floatright' ) );
}
$Form->begin_fieldset( T_('Crop profile picture').$close_icon, array( 'class' => 'fieldset clear', 'id' => 'image_crop' ) );

$cropped_image_tag = $cropped_File->get_tag( '', '', '', '', 'original', '' );

echo '<p class="crop_button top">';
$Form->button( array( 'submit', 'actionArray[crop]', T_('Crop'), 'SaveButton btn-primary' ) );
echo '</p>';

echo '<div id="image_crop_block"'.( ( ! is_admin_page() && $display_mode != 'js' ) ? ' class="short_width"' : '' ).'><div>';

// Main image
echo '<div id="target_cropped_image">'.$cropped_image_tag.'</div>';

echo '</div><div>';

// Check if we should display big preview images, Hide them on small screens:
$display_big_preview = ( empty( $image_width ) || $image_width > 400 ) && ( empty( $image_height ) || $image_height > 400 );

// Preview thumbnails
echo '<div class="preview_cropped_images'.( ! $display_big_preview ? ' only_small_preview' : '' ).'" style="display:none">';
	if( $display_big_preview )
	{
		echo '<div class="preview_cropped_image" style="width:128px;height:128px">'.$cropped_image_tag.'</div>';
	}
	echo '<div class="preview_cropped_image" style="width:64px;height:64px">'.$cropped_image_tag.'</div>';
	if( $display_big_preview )
	{
		echo '<div class="preview_cropped_image circle" style="width:128px;height:128px">'.$cropped_image_tag.'</div>';
	}
	echo '<div class="preview_cropped_image circle" style="width:64px;height:64px">'.$cropped_image_tag.'</div>';
echo '</div>';

echo '</div></div>';

echo '<p class="crop_button bottom" style="display:none">';
$Form->button( array( 'submit', 'actionArray[crop]', T_('Crop'), 'SaveButton btn-primary' ) );
echo '</p>';

$Form->end_fieldset();

$Form->end_form();

// Get min size(width or height) of the image
$file_size = $cropped_File->get_image_size( 'widthheight' );
$file_size = min( $file_size[0], $file_size[1] );

if( ! empty( $image_width ) && ! empty( $image_height ) )
{ // Limit the cropping image with window size
?>
<style>
#target_cropped_image img {
	max-width: <?php echo $image_width; ?>px !important;
	max-height: <?php echo $image_height; ?>px !important;
}
</style>
<?php
}
?>
<script type="text/javascript">
if( typeof( target_cropped_image_is_loaded ) != 'undefined' && target_cropped_image_is_loaded == true )
{ // Inititalize the crop tool after popup window opening when image was already loaded before
	init_jcrop_tool( jQuery( '#target_cropped_image img:first' ) );
}
jQuery( '#target_cropped_image img:first' ).load( function()
{ // Inititalize the crop tool after image has been loaded
	init_jcrop_tool( jQuery( this ) );
	target_cropped_image_is_loaded = true;
} );

// Initialize the crop tool
function init_jcrop_tool( image_obj )
{
	target_cropped_image_width = image_obj.width();
	target_cropped_image_height = image_obj.height();

	var min_width = Math.floor( <?php echo intval( $Settings->get( 'min_picture_size' ) ); ?> * Math.min( target_cropped_image_width, target_cropped_image_height ) / <?php echo $file_size; ?> );
	var min_height = min_width;

	// Set default selected crop area
	if( target_cropped_image_width == target_cropped_image_height )
	{ // Square image
		if( target_cropped_image_width > min_width )
		{
			var x1 = target_cropped_image_width * 0.05;
			var x2 = target_cropped_image_width * 0.95;
		}
		else
		{
			var x1 = 0;
			var x2 = target_cropped_image_width;
			min_width = x2;
		}
		if( target_cropped_image_height > min_height )
		{
			var y1 = target_cropped_image_height * 0.05;
			var y2 = target_cropped_image_height * 0.95;
		}
		else
		{
			var y1 = 0;
			var y2 = target_cropped_image_height;
			min_height = y2;
		}
	}
	else if( target_cropped_image_width > target_cropped_image_height )
	{ // Horizontal image
		var x1 = target_cropped_image_width / 2 - target_cropped_image_height / 2
		var y1 = 0;
		var x2 = target_cropped_image_width / 2 + target_cropped_image_height / 2;
		var y2 = target_cropped_image_height;
	}
	else if( target_cropped_image_width < target_cropped_image_height )
	{ // Vertical image
		var x1 = 0;
		var y1 = target_cropped_image_height / 2 - target_cropped_image_width / 2;
		var x2 = target_cropped_image_width;
		var y2 = target_cropped_image_height / 2 + target_cropped_image_width / 2;
	}

	// Initialize the crop tool
	image_obj.Jcrop(
	{
		aspectRatio: 1,
		minSize: [ min_width, min_height ],
		setSelect: [ x1, y1, x2, y2 ],
		onChange: show_preview_cropped_image,
		onSelect: show_preview_cropped_image,
	} );

	// Display the crop elements only after initialization
	jQuery( '.preview_cropped_images' ).show();
	if( jQuery( '#modal_window' ).length > 0 )
	{ // Crop button on bootstrap skins
		jQuery( '#modal_window .modal-footer button[type=submit]' ).attr( 'style', 'display:inline-block !important' );
	}
	else
	{ // Crop button on other skins
		jQuery( '.crop_button' ).show();
	}
}

// Update thumbnails on change a crop area
function show_preview_cropped_image( coords )
{
	var percent_width = Math.ceil( coords.w / target_cropped_image_width * 10000 ) / 100;
	var percent_height = Math.ceil( coords.h / target_cropped_image_height * 10000 ) / 100;
	var percent_top = Math.ceil( coords.x / target_cropped_image_width * 10000 ) / 100;
	var percent_left = Math.ceil( coords.y / target_cropped_image_height * 10000 ) / 100;
	jQuery( 'input[name=image_crop_data]' ).val( percent_top + ':' + percent_left + ':' + percent_width + ':' + percent_height );

	var top = coords.y;
	var left = coords.x;
	if( coords.w > coords.h )
	{ // Center a cropping area of horizontal image
		left += ( coords.w / 2 ) - ( coords.h / 2 );
	}
	else
	{
		var top_shift = ( coords.h - coords.w ) * 0.15;
		if( top + top_shift + coords.w < top + coords.h )
		{ // top - 15%
			top += top_shift;
		}
	}

	jQuery( '.preview_cropped_image img' ).each( function()
	{
		var ratio = jQuery( this ).parent().width() / ( coords.w < coords.h ? coords.w : coords.h );

		jQuery( this ).css(
		{
			width: Math.round( ratio * target_cropped_image_width ) + 'px',
			height: Math.round( ratio * target_cropped_image_height ) + 'px',
			marginLeft: '-' + Math.round( ratio * left ) + 'px',
			marginTop: '-' + Math.round( ratio * top ) + 'px'
		} );
	} );
}
</script>

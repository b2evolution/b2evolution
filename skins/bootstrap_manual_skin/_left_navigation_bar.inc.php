<?php
/**
 * This is the LEFT navigation bar include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Settings, $Session;
?>
<!-- =================================== START OF SIDEBAR =================================== -->
<aside class="col-md-3 pull-left">

	<div id="evo_container__sidebar">

		<div class="evo_container evo_container__sidebar">
		<?php
			// <div data-spy="affix" data-offset-top="165" class="affix_block">
			// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			// Note: this container is designed to be a single <ul> list
			skin_container( NT_('Sidebar'), array(
					// The following (optional) params will be used as defaults for widgets included in this container:
					// This will enclose each widget in a block:
					'block_start' => '<div class="panel panel-default widget $wi_class$">',
					'block_end'   => '</div>',
					// This will enclose the title of each widget:
					'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
					'block_title_end'   => '</h4></div>',
					// This will enclose the body of each widget:
					'block_body_start' => '<div class="panel-body">',
					'block_body_end'   => '</div>',
					// This will enclose (foot)notes:
					'notes_start' => '<div class="small text-muted">',
					'notes_end'   => '</div>',
					// Widget 'Search form':
					'search_class'         => 'compact_search_form',
					'search_input_before'  => '<div class="input-group">',
					'search_input_after'   => '',
					'search_submit_before' => '<span class="input-group-btn">',
					'search_submit_after'  => '</span></div>',
					// Widget 'Content Hierarchy':
					'item_before_opened'   => get_icon( 'collapse' ),
					'item_before_closed'   => get_icon( 'expand' ),
					'item_before_post'     => get_icon( 'post' ),
					'expand_all'           => false,
					'sorted'               => true
				) );
			// ----------------------------- END OF "Sidebar" CONTAINER -----------------------------
		?>
		</div>

		<div class="evo_container evo_container__sidebar2">
		<?php
			// <div data-spy="affix" data-offset-top="165" class="affix_block">
			// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			// Note: this container is designed to be a single <ul> list
			skin_container( NT_('Sidebar 2'), array(
					// The following (optional) params will be used as defaults for widgets included in this container:
					// This will enclose each widget in a block:
					'block_start' => '<div class="panel panel-default widget $wi_class$">',
					'block_end'   => '</div>',
					// This will enclose the title of each widget:
					'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
					'block_title_end'   => '</h4></div>',
					// This will enclose the body of each widget:
					'block_body_start' => '<div class="panel-body">',
					'block_body_end'   => '</div>',
					// This will enclose (foot)notes:
					'notes_start' => '<div class="small text-muted">',
					'notes_end'   => '</div>',
					// Widget 'Search form':
					'search_class'         => 'compact_search_form',
					'search_input_before'  => '<div class="input-group">',
					'search_input_after'   => '',
					'search_submit_before' => '<span class="input-group-btn">',
					'search_submit_after'  => '</span></div>',
					// Widget 'Content Hierarchy':
					'item_before_opened'   => get_icon( 'collapse' ),
					'item_before_closed'   => get_icon( 'expand' ),
					'item_before_post'     => get_icon( 'post' ),
					'expand_all'           => false,
					'sorted'               => true
				) );
			// ----------------------------- END OF "Sidebar" CONTAINER -----------------------------
		?>
		</div>

		<?php
			// Please help us promote b2evolution and leave this logo on your blog:
			powered_by( array(
					'block_start' => '<div class="powered_by">',
					'block_end'   => '</div>',
					// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
					'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
					'img_width'   => 120,
					'img_height'  => 32,
				) );
		?>

	</div><!-- DO WE NEED THIS DIV? -->

</aside><!-- .col -->

<script type="text/javascript">
var has_touch_event;
window.addEventListener( 'touchstart', function set_has_touch_event ()
{
	has_touch_event = true;
	// Remove event listener once fired, otherwise it'll kill scrolling
	window.removeEventListener( 'touchstart', set_has_touch_event );
}, false );

/**
 * Change header position to fixed or revert to static
 */
function change_position_leftnav()
{
	if( has_touch_event )
	{ // Don't fix the objects on touch devices
		return;
	}

	if( sidebar_size )
	{ // Sidebar exists
		if( !$sidebar.hasClass( 'fixed' ) && jQuery( window ).scrollTop() > $sidebar.offset().top - sidebar_top )
		{ // Make sidebar as fixed if we scroll down
			$sidebar.before( $sidebarSpacer );
			$sidebar.addClass( 'fixed' ).css( 'top', sidebar_top + 'px' );
		}
		else if( $sidebar.hasClass( 'fixed' )  && jQuery( window ).scrollTop() < $sidebarSpacer.offset().top - sidebar_top )
		{ // Remove 'fixed' class from sidebar if we scroll to the top of page
			$sidebar.removeClass( 'fixed' ).css( 'top', '' );
			$sidebarSpacer.remove();
		}

		if( $sidebar.hasClass( 'fixed' ) )
		{ // Check and fix an overlapping of footer with sidebar
			$sidebar.css( 'top', sidebar_top + 'px' );
			var diff = parseInt( $sidebar.offset().top + $sidebar.outerHeight() - jQuery( '#evo_container__footer' ).offset().top );
			if( diff >= 0 )
			{
				$sidebar.css( 'top', parseInt( sidebar_top - diff - 5 ) + 'px' );
			}
		}
	}
}

var sidebar_shift = <?php echo $Settings->get( 'site_skins_enabled' ) ? 54 : 0; ?>;
var $sidebar = jQuery( '#evo_container__sidebar' );
if( $sidebar.outerHeight( true ) + 70/* footer height */ < jQuery( window ).height() )
{
	var sidebar_size = $sidebar.size();
	var sidebar_top = <?php echo ( is_logged_in() ? 28 : 0 ) ; ?> + sidebar_shift;
	var $sidebarSpacer = $( '<div />', {
			"class" : "evo_container__sidebar fixed_spacer",
			"height": $sidebar.outerHeight()
		} );
	jQuery( window ).scroll( function ()
	{
		change_position_leftnav();
	} );
	jQuery( window ).resize( function()
	{
		change_position_leftnav();
	} );
}
</script>
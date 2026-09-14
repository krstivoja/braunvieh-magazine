<?php
/**
 * braunvieh-magazine/site-header — the magazine header block.
 *
 * Replaces cows/site-header (cows-modular-blocks). The links, fullscreen menu and
 * social icons come from the main site's English header (inc/header-footer-api.php);
 * the logo and search stay the magazine's own. The markup is the same as the plugin
 * block (and wp_nav_menu() + Custom_Walker_Nav_Menu for the menu), so
 * css/nav-sidebar.css and js/nav-sidebar.js work unchanged.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A list from the header/footer data, keeping only well-formed items.
 *
 * @param array  $data Header/footer data.
 * @param string $key  List key.
 * @return array[]
 */
function braunvieh_magazine_site_header_list( $data, $key ) {
	if ( empty( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
		return array();
	}
	return array_values( array_filter( $data[ $key ], 'is_array' ) );
}

/**
 * A string field of a list item ('' when missing or not a string).
 *
 * @param array  $item List item.
 * @param string $key  Field key.
 * @return string
 */
function braunvieh_magazine_site_header_field( $item, $key ) {
	return ( isset( $item[ $key ] ) && is_string( $item[ $key ] ) ) ? $item[ $key ] : '';
}

/**
 * Menu items as wp_nav_menu() + Custom_Walker_Nav_Menu + the my_menu_class level
 * filter print them (same ids, classes, sub-level wrapper and whitespace).
 *
 * @param array[] $items Menu items (id, title, url, target, classes, children).
 * @param int     $depth Nesting depth.
 * @return string
 */
function braunvieh_magazine_site_header_menu_items( $items, $depth = 0 ) {
	$output = '';
	$indent = str_repeat( "\t", $depth );

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$id       = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
		$title    = braunvieh_magazine_site_header_field( $item, 'title' );
		$url      = braunvieh_magazine_site_header_field( $item, 'url' );
		$target   = braunvieh_magazine_site_header_field( $item, 'target' );
		$children = braunvieh_magazine_site_header_list( $item, 'children' );

		// Class order as WordPress builds it: custom classes, menu-item,
		// has-children, level-N (my_menu_class), menu-item-{ID} (the walker).
		$classes = isset( $item['classes'] ) && is_array( $item['classes'] ) ? array_map( 'sanitize_html_class', array_filter( $item['classes'], 'is_string' ) ) : array();
		$classes[] = 'menu-item';
		if ( $children ) {
			$classes[] = 'menu-item-has-children';
		}
		$classes[] = 'level-' . $depth;
		if ( $id ) {
			$classes[] = 'menu-item-' . $id;
		}

		$output .= $indent . '<li' . ( $id ? ' id="menu-item-' . $id . '"' : '' ) . ' class="' . esc_attr( implode( ' ', array_filter( $classes ) ) ) . '">';
		$output .= '<a' . ( '' !== $target ? ' target="' . esc_attr( $target ) . '"' : '' ) . ( '' !== $url ? ' href="' . esc_url( $url ) . '"' : '' ) . '>';
		$output .= esc_html( $title );
		if ( $children ) {
			$output .= '<span class="submenu-icon"> ›</span>';
		}
		$output .= '</a>';

		if ( $children ) {
			$output .= "\n$indent<div class=\"mp-level\">\n";
			$output .= "$indent\t<a class=\"mp-back\" href=\"#\">← Back</a>\n";
			$output .= "$indent\t<h2 class=\"mp-heading\">" . esc_html( '' !== $title ? $title : 'Sub Menu' ) . "</h2>\n";
			$output .= "$indent\t<ul class=\"sub-menu\">\n";
			$output .= braunvieh_magazine_site_header_menu_items( $children, $depth + 1 );
			$output .= "$indent\t</ul>\n";
			$output .= "$indent</div>\n";
		}

		$output .= "</li>\n";
	}

	return $output;
}

/**
 * Render the header (block render callback).
 *
 * @return string
 */
function braunvieh_magazine_render_site_header() {
	$data           = braunvieh_magazine_header_footer();
	$assets         = get_template_directory_uri();
	$logo           = function_exists( 'get_field' ) ? get_field( 'logo', 'options' ) : '';
	$top_links      = braunvieh_magazine_site_header_list( $data, 'top_links' );
	$external_links = braunvieh_magazine_site_header_list( $data, 'external_links' );
	$social_links   = braunvieh_magazine_site_header_list( $data, 'social_links' );
	$menu           = braunvieh_magazine_site_header_list( $data, 'menu' );
	$arrow_svg      = '<span class="submenu-icon"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.52501 11.182L9.82776 6.87924L9.82775 10.832L10.8283 10.8284L10.8283 5.17157L5.17146 5.17157L5.17146 6.16859L9.12065 6.17213L4.8179 10.4749L5.52501 11.182Z" fill="white"/></svg></span>';

	ob_start();
	?>
<div id="side-nav">
	<div class="logo">
		<a href="<?php echo esc_url( site_url() ); ?>"><img src="<?php echo esc_url( is_string( $logo ) ? $logo : '' ); ?>" alt="logo"></a>
	</div>
	<nav class="side-nav-menu">
		<?php foreach ( $top_links as $link ) : ?>
			<a target="_blank" href="<?php echo esc_url( braunvieh_magazine_site_header_field( $link, 'url' ) ); ?>">
				<?php echo esc_html( braunvieh_magazine_site_header_field( $link, 'name' ) ); ?>
				<?php echo $arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<div class="menu-right">
		<div id="search" class="search-not-active">
			<img id="search-icon" src="<?php echo esc_url( $assets . '/css/img-nav/search.svg' ); ?>" alt="search icon">
			<?php echo do_shortcode( '[ivory-search id="17" title="Default Search Form"]' ); ?>
			<div class="search-toggle"></div>
		</div>
		<a href="#" id="trigger">
			<img src="<?php echo esc_url( $assets . '/css/img-nav/menu-trigger.svg' ); ?>" alt="menu icon">
		</a>
	</div>
</div>

<div class="mp-pusher" id="mp-pusher">
	<div class="mp-menu" id="mp-menu">
		<div class="mp-level">
			<h2 class="icon icon-world">Menu</h2>
			<div class="menu-scroll-container">
				<ul id="menu-fullscreen-menu-<?php echo esc_attr( sanitize_html_class( (string) BRAUNVIEH_HEADER_FOOTER_LANG ) ); ?>" class="menu"><?php echo braunvieh_magazine_site_header_menu_items( $menu ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per value above. ?></ul>
			</div>
			<div id="external-links">
				<?php foreach ( $external_links as $link ) : ?>
					<a target="_blank" href="<?php echo esc_url( braunvieh_magazine_site_header_field( $link, 'url' ) ); ?>">
						<?php echo esc_html( braunvieh_magazine_site_header_field( $link, 'name' ) ); ?>
						<?php echo $arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<div class="menu-overlay"></div>

<div id="side-social-links" class="fixed-social-links">
	<?php foreach ( $social_links as $link ) : ?>
		<div class="social-link">
			<a target="_blank" href="<?php echo esc_url( braunvieh_magazine_site_header_field( $link, 'url' ) ); ?>"><img src="<?php echo esc_url( braunvieh_magazine_site_header_field( $link, 'icon' ) ); ?>"></a>
		</div>
	<?php endforeach; ?>
</div>
	<?php
	return ob_get_clean();
}

// Register the dynamic block used by parts/header.html.
add_action( 'init', function () {
	register_block_type( 'braunvieh-magazine/site-header', array(
		'render_callback' => 'braunvieh_magazine_render_site_header',
		'supports'        => array( 'html' => false, 'inserter' => false ),
	) );
} );

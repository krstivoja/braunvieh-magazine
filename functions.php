<?php
/**
 * Braunvieh Magazine theme.
 *
 * Block-theme conversion of the classic `braunvieh` MAGAZINE theme. Front-end chrome
 * (header/footer) is provided by the cows/site-header & cows/site-footer
 * dynamic blocks (see the cows-modular-blocks plugin), which port the original
 * ACF-options-driven header.php / footer.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THEME_DIR', get_template_directory() );
define( 'THEME_URI', get_template_directory_uri() );

// Single-language site (English only) — hide the header language switcher
// (read by the shared cows/site-header block).
define( 'COWS_HIDE_LANGUAGE_SWITCHER', true );

add_theme_support( 'post-thumbnails' );
add_theme_support( 'title-tag' );
add_theme_support( 'wp-block-styles' );
add_theme_support( 'responsive-embeds' );

// Nav menu used by the header block (fullscreen menu). Idempotent if also registered elsewhere.
add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'fullscreen-menu' => __( 'Fullscreen Menu', 'braunvieh-magazine' ),
	) );
} );

// "Rot" style variation for core/button — the target of the migrator's standalone
// CTA links (a link that is a whole line/list item in a section_text WYSIWYG). The
// style class (.is-style-red) is what carries the red look; registering it here
// also surfaces it in the editor's Styles panel. See css/tokens.css for the look.
add_action( 'init', function () {
	if ( function_exists( 'register_block_style' ) ) {
		register_block_style( 'core/button', array(
			'name'  => 'red',
			'label' => __( 'Rot', 'braunvieh-magazine' ),
		) );
	}
} );

// Front-end styles/scripts (ported from the classic theme, minus WooCommerce).
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'braunvieh-icons', THEME_URI . '/css/osi2tvy.css', array(), '3.1.0' );
	wp_enqueue_style( 'braunvieh-legacy', THEME_URI . '/css/styles.css', array(), '3.1.0' );
	wp_enqueue_style( 'nav-sidebar', THEME_URI . '/css/nav-sidebar.css', array(), '3.1.0' );
	wp_enqueue_style( 'slick', THEME_URI . '/css/slick.css', array(), '3.1.0' );
	// WooCommerce spacing/layout fixes (ported from the shop theme) — the magazine
	// site now sells the subscription itself, so it needs the Woo styling too.
	wp_enqueue_style( 'woo-fixes', THEME_URI . '/css/woo-fixes.css', array( 'braunvieh-legacy' ), '3.1.0' );

	wp_enqueue_script( 'slick', THEME_URI . '/js/slick.min.js', array( 'jquery' ), '3.1.0', true );
	wp_enqueue_script( 'nav-sidebar', THEME_URI . '/js/nav-sidebar.js', array(), '3.1.0', true );
	wp_enqueue_script( 'custom', THEME_URI . '/js/custom.js', array( 'jquery' ), '3.1.0', true );

	// Token bridge — must load LAST so it overrides legacy hardcoded values in styles.css.
	wp_enqueue_style( 'braunvieh-tokens', THEME_URI . '/css/tokens.css', array( 'braunvieh-legacy', 'nav-sidebar', 'slick' ), '3.1.0' );

	// Customizer "Additional CSS" from the classic magazine theme, baked in — block
	// themes drop it (per-theme, Customizer off under FSE). Load LAST so it wins.
	wp_enqueue_style( 'braunvieh-customizer', THEME_URI . '/css/customizer.css', array( 'braunvieh-tokens' ), '3.1.0' );

	// TablePress default CSS: the migrated pages hold pre-rendered TablePress
	// table HTML (the ACF WYSIWYG had already run do_shortcode), so TablePress
	// never detects a [table] shortcode on the page and never outputs its own
	// styling. Force-load its default.css whenever the current page contains a
	// TablePress table, so tables get the same structure/borders as the original.
	if ( defined( 'TABLEPRESS_ABSPATH' ) && is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post && false !== strpos( (string) $post->post_content, 'tablepress' ) ) {
			$css_file = TABLEPRESS_ABSPATH . 'css/build/default.css';
			if ( file_exists( $css_file ) ) {
				$css_url = content_url( str_replace( wp_normalize_path( WP_CONTENT_DIR ), '', wp_normalize_path( $css_file ) ) );
				wp_enqueue_style( 'tablepress-default-forced', $css_url, array( 'braunvieh-tokens' ), null );
			}
		}
	}
}, 20 );

// ACF options pages used by the header/footer/banner fields.
add_action( 'acf/init', function () {
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page( 'Header' );
		acf_add_options_page( 'Footer' );
		acf_add_options_page( 'Banner' );
	}
} );

// Allow SVG uploads (logos/icons).
add_filter( 'upload_mimes', function ( $mimes ) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
} );

/* =========================================================================
 * WooCommerce — ported verbatim from the classic shop theme's functions.php,
 * so the shop behaves identically under this block theme. The classic Woo
 * template overrides live in ./woocommerce and render via the block templates'
 * `woocommerce/legacy-template` block.
 * ====================================================================== */

// WooCommerce theme support.
add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce' );
} );

// Under a block theme WooCommerce flips into "block theme mode": it adds the body
// classes `woocommerce-uses-block-theme` / `woocommerce-block-theme-has-button-styles`
// and an `.alignwide` wrapper around the shop content, which switch its grid to the
// block-theme (flex/grid) layout instead of the classic float grid the theme CSS
// targets — breaking the product grid (2-col, staggered) vs the classic 4-col.
// The shop renders classic Woo output, so strip those markers to keep the classic grid.
add_filter( 'body_class', function ( $classes ) {
	return array_values( array_diff( $classes, array( 'woocommerce-uses-block-theme', 'woocommerce-block-theme-has-button-styles' ) ) );
}, 20 );

// Under a block theme WooCommerce adds `woocommerce-blocktheme.css`, which restyles
// .woocommerce/.button/.product/.cart/.form-row etc. to inherit block-theme styling.
// The classic shop never loaded it — the shop is styled by the classic Woo stylesheets
// (woocommerce.css/-layout/-smallscreen, all still loaded) + the theme's styles.css +
// woo-fixes.css. Dequeue it so the shop looks byte-for-byte like the classic theme.
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'woocommerce-blocktheme' );
}, 100 );

// Rename the street address label/placeholder in checkout.
add_filter( 'woocommerce_default_address_fields', function ( $fields ) {
	$fields['address_1']['label']       = 'Strasse';
	$fields['address_1']['placeholder'] = 'Strassenname und Hausnummer';
	return $fields;
}, 9999 );

// Shop-archive category filter form. The classic theme added this in its
// woocommerce/archive-product.php, but the block templates render the shop loop
// via woocommerce/legacy-template → woocommerce_content(), which uses WooCommerce's
// own archive markup and does NOT include that custom form. Re-inject it at the
// same position (in the products header, right after the title) via the hook
// woocommerce_content() fires there.
add_action( 'woocommerce_archive_description', function () {
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_taxonomy() ) ) {
		return;
	}
	$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $cats ) || empty( $cats ) ) {
		return;
	}
	echo '<form action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" method="POST" id="filter">';
	echo '<select name="categoryfilter"><option value="">Select category...</option><option value="Alle Produkte">Alle Produkte</option>';
	foreach ( $cats as $cat ) {
		echo '<option value="' . esc_attr( $cat->name ) . '">' . esc_html( $cat->name ) . '</option>';
	}
	echo '</select><button>Apply filter</button><input type="hidden" name="action" value="myfilter"></form>';
}, 5 );

// [get_product_categories] — category link list used on the shop.
function get_product_categories_function() {
	$product_categories = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	) );
	$categories = "<div class=category-links><a href=/shop>Alle Produkte</a>";
	foreach ( (array) $product_categories as $cat ) {
		$name        = $cat->name;
		$link        = get_term_link( (int) $cat->term_id );
		$categories .= "<a href=$link>$name</a>";
	}
	$categories .= '</div>';
	return $categories;
}
add_shortcode( 'get_product_categories', 'get_product_categories_function' );

// Strip item meta from WooCommerce emails (classic behaviour).
add_filter( 'woocommerce_order_item_get_formatted_meta_data', function ( $formatted_meta, $item ) {
	return '';
}, 10, 2 );

// Required Privacy Policy checkbox on checkout (German source; TranslatePress → FR/IT).
add_action( 'woocommerce_review_order_before_submit', function () {
	woocommerce_form_field( 'privacy_policy_confirm', array(
		'type'     => 'checkbox',
		'class'    => array( 'form-row privacy-policy-confirm' ),
		'label'    => 'Ich bestätige, dass ich die oben genannte Seite gelesen habe und akzeptiere sie.',
		'required' => true,
	) );
}, 9 );

add_action( 'woocommerce_checkout_process', function () {
	if ( empty( $_POST['privacy_policy_confirm'] ) ) {
		wc_add_notice( 'Sie müssen die Datenschutzrichtlinie akzeptieren, um eine Bestellung aufzugeben.', 'error' );
	}
} );

add_action( 'woocommerce_checkout_update_order_meta', function ( $order_id ) {
	if ( ! empty( $_POST['privacy_policy_confirm'] ) ) {
		update_post_meta( $order_id, '_privacy_policy_confirm', 'yes' );
	}
} );

// Disable the place-order button until the privacy checkbox is ticked.
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	?>
	<script type="text/javascript">
	jQuery(function($){
		function toggle(){ var ok=$('#privacy_policy_confirm').is(':checked'); $('#place_order').prop('disabled',!ok).css('opacity',ok?'1':'0.2'); }
		toggle();
		$(document).on('change','#privacy_policy_confirm',toggle);
		$(document.body).on('updated_checkout',toggle);
	});
	</script>
	<?php
} );

// Shared helpers carried over from the classic theme.
require_once THEME_DIR . '/inc/custom_nav.php';            // fullscreen menu walker
require_once THEME_DIR . '/inc/footer_newsletter.php';     // [footer_newsletter] shortcode
require_once THEME_DIR . '/inc/body_class.php';
require_once THEME_DIR . '/inc/block_by_country.php';
require_once THEME_DIR . '/inc/cookie_banner_translations.php';
require_once THEME_DIR . '/inc/magazine.php';         // magazine CPT archive block + magazine.css + EXTERNAL_SHOP_URL

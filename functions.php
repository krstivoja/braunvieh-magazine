<?php
/**
 * Braunvieh Magazine — child theme of Braunvieh Block.
 *
 * The parent (braunvieh-block) provides the design: theme.json, CSS, blocks,
 * templates, and the header and footer, which on this site show the main site's
 * English header and footer (from its header/footer API). This child only holds
 * what is special about the magazine: WooCommerce (./woocommerce overrides, the
 * shop/cart/checkout block templates and the hooks below) and the magazine
 * archive (inc/magazine.php).
 *
 * This file loads before the parent's functions.php, so the constants below win.
 * The parent defines THEME_DIR / THEME_URI as its own paths: child files always
 * go through get_stylesheet_directory() / get_stylesheet_directory_uri().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The magazine site: the parent skips the main site's events, /en/ routing and
// shop redirect, and takes the header and footer from the main site's API…
if ( ! defined( 'BRAUNVIEH_SITE' ) ) {
	define( 'BRAUNVIEH_SITE', 'magazine' );
}

// …in English.
if ( ! defined( 'BRAUNVIEH_HEADER_FOOTER_LANG' ) ) {
	define( 'BRAUNVIEH_HEADER_FOOTER_LANG', 'en' );
}

// Single-language site (English only) — no header language switcher.
if ( ! defined( 'COWS_HIDE_LANGUAGE_SWITCHER' ) ) {
	define( 'COWS_HIDE_LANGUAGE_SWITCHER', true );
}

// Child stylesheets, after the parent's (priority 20) so they win.
add_action( 'wp_enqueue_scripts', function () {
	$dir    = get_stylesheet_directory();
	$uri    = get_stylesheet_directory_uri();
	$parent = array( 'braunvieh-legacy', 'nav-sidebar', 'slick', 'braunvieh-tokens' );

	// The WooCommerce styling the parent no longer carries — only where WooCommerce renders.
	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
		wp_enqueue_style( 'braunvieh-woocommerce', $uri . '/css/woocommerce.css', $parent, filemtime( $dir . '/css/woocommerce.css' ) );
	}

	// WooCommerce spacing/layout fixes (ported from the shop theme). Everywhere: it
	// also carries the logged-in customer/subscriber header offsets.
	wp_enqueue_style( 'woo-fixes', $uri . '/css/woo-fixes.css', $parent, filemtime( $dir . '/css/woo-fixes.css' ) );
}, 21 );

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

// Magazine archive block, [productbox], subscription check, magazine.css.
require_once get_stylesheet_directory() . '/inc/magazine.php';

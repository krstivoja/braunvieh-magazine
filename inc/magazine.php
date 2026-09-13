<?php
/**
 * Magazine-specific bits for the Braunvieh Magazine block theme.
 *
 * The classic theme rendered the `magazine` CPT archive with archive-magazine.php.
 * Block themes use block templates, so that PHP is ported here into a dynamic block
 * (braunvieh/magazine-archive, render callback — no wpautop) that templates/archive-magazine.html
 * embeds. Output is byte-for-byte the same as the classic template (subscription
 * check, OPEN/PURCHASE buttons, dFlip popups, pagination, popup JS).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// External shop URL — magazines are purchased on the separate shop site.
if ( ! defined( 'EXTERNAL_SHOP_URL' ) ) {
	define( 'EXTERNAL_SHOP_URL', 'https://pixels.productions/braunvieh-shop' );
}

// magazine.css on magazine pages / archive / singles (same condition as classic).
add_action( 'wp_enqueue_scripts', function () {
	$is_ssp_page = ( is_page() && function_exists( 'get_field' ) && get_field( 'ssp' ) );
	if ( $is_ssp_page || is_post_type_archive( 'magazine' ) || is_singular( 'magazine' ) ) {
		wp_enqueue_style( 'magazine', get_template_directory_uri() . '/css/magazine.css', array( 'braunvieh-legacy' ), '2.3.5' );
	}
}, 21 );

// These pages render differently for logged-in subscribers (OPEN) vs everyone else
// (PURCHASE), so they must NOT be stored in LiteSpeed's PUBLIC cache — otherwise a
// cached logged-out copy is served to subscribers and they never see OPEN. Mark them
// no-cache (belt-and-braces: the LiteSpeed action hook + the response header).
add_action( 'template_redirect', function () {
	$is_ssp_page = ( is_page() && function_exists( 'get_field' ) && get_field( 'ssp' ) );
	if ( $is_ssp_page || is_post_type_archive( 'magazine' ) || is_singular( 'magazine' ) ) {
		do_action( 'litespeed_control_set_nocache', 'braunvieh: subscription-varying page' );
		if ( ! headers_sent() ) {
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
		}
	}
}, 1 );

/**
 * Render the magazine archive (ported from the classic archive-magazine.php, minus
 * get_header/get_footer — those are the block template's header/footer parts).
 */
function braunvieh_render_magazine_archive() {
	ob_start();
	?>
	<div id="content" class="container">
		<div class="magazine-archive-container">
			<div class="row">
				<div class="col-12">
					<h1>MAGAZINE BROWN SWISS</h1>
					<?php
					// Find the subscription page (ssp field) to get the linked product ID.
					$subscription_page = get_posts( array(
						'post_type'      => 'page',
						'posts_per_page' => 1,
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array( 'key' => 'ssp', 'value' => '1', 'compare' => '=' ),
						),
					) );

					$product_id = null;
					if ( ! empty( $subscription_page ) ) {
						$product_id = get_field( 'woo_product', $subscription_page[0]->ID );
					}

					// User subscription status (Paid Member Subscriptions).
					$user_id                 = get_current_user_id();
					$has_active_subscription = false;
					$subscription_expired    = false;
					$subscription_id         = 30696; // The subscription plan ID.

					if ( $user_id > 0 ) {
						if ( function_exists( 'pms_get_member_subscriptions' ) ) {
							$member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );
							if ( $member_subscriptions ) {
								foreach ( $member_subscriptions as $subscription ) {
									if ( $subscription->subscription_plan_id == $subscription_id ) {
										if ( 'active' === $subscription->status ) {
											$has_active_subscription = true;
										} elseif ( 'expired' === $subscription->status ) {
											$subscription_expired = true;
										}
										break;
									}
								}
							}
						} elseif ( function_exists( 'pms_is_member_active' ) ) {
							if ( pms_is_member_active( $user_id, $subscription_id ) ) {
								$has_active_subscription = true;
							}
						} else {
							global $wpdb;
							$table_name   = $wpdb->prefix . 'pms_member_subscriptions';
							$subscription = $wpdb->get_row( $wpdb->prepare(
								"SELECT * FROM {$table_name} WHERE user_id = %d AND subscription_plan_id = %d ORDER BY id DESC LIMIT 1",
								$user_id,
								$subscription_id
							) );
							if ( $subscription ) {
								if ( 'active' === $subscription->status ) {
									$has_active_subscription = true;
								} elseif ( 'expired' === $subscription->status ) {
									$subscription_expired = true;
								}
							}
						}
					}

					// Local add-to-cart URL for the subscription product (buy on THIS
					// site, not the external shop). Prefer the constant, else the shop
					// URL as a last-resort fallback.
					$cows_local_buy = '#';
					if ( defined( 'BRAUNVIEH_SUB_PRODUCT_ID' ) && get_post_type( (int) BRAUNVIEH_SUB_PRODUCT_ID ) === 'product' ) {
						$cows_local_buy = home_url( '/cart/?add-to-cart=' . (int) BRAUNVIEH_SUB_PRODUCT_ID . '&quantity=1' );
					} elseif ( $product_id ) {
						$cows_local_buy = EXTERNAL_SHOP_URL;
					}

					// Expired-subscription message.
					if ( $user_id > 0 && $subscription_expired ) {
						$purchase_url = $cows_local_buy;
						?>
						<div class="magazine-expired-message">
							<h3 class="expired-title">Your subscription has expired</h3>
							<p class="expired-text">Please update payment method or purchase it again to be able to view the content</p>
							<a href="<?php echo esc_url( $purchase_url ); ?>" class="expired-purchase-button">PURCHASE</a>
						</div>
						<?php
					}
					?>

					<div class="magazine-archive-container">
						<?php
						if ( have_posts() ) {
							while ( have_posts() ) {
								the_post();
								$full_issue_id = get_field( 'magazine_full_issue' );
								$button_class  = 'magazine-archive-button';
								$button_text   = 'PURCHASE';
								$button_action = 'purchase';
								$button_url    = '#';

								if ( $has_active_subscription ) {
									$button_class .= ' magazine-button-active';
									$button_text   = 'OPEN';
									$button_action = 'read';
								} else {
									$button_class .= ' magazine-button-purchase';
									if ( '#' !== $cows_local_buy ) {
										$button_url = $cows_local_buy;
									}
								}
								?>
								<div class="magazine-archive-item magazine-item-clickable"
									<?php if ( 'read' === $button_action && $full_issue_id && ! empty( $full_issue_id ) ) : ?>
										data-popup-id="magazine-popup-<?php echo esc_attr( get_the_ID() ); ?>"
									<?php elseif ( 'purchase' === $button_action && '#' !== $button_url ) : ?>
										data-purchase-url="<?php echo esc_url( $button_url ); ?>"
									<?php endif; ?>>
									<h3 class="magazine-archive-title"><?php the_title(); ?></h3>
									<?php if ( has_post_thumbnail() ) : ?>
										<div class="magazine-archive-thumbnail">
											<?php the_post_thumbnail( 'medium_large', array( 'class' => 'magazine-thumbnail-img' ) ); ?>
										</div>
									<?php endif; ?>

									<?php if ( 'read' === $button_action && $full_issue_id && ! empty( $full_issue_id ) ) : ?>
										<a href="#" class="<?php echo esc_attr( $button_class ); ?>" data-popup-id="magazine-popup-<?php echo esc_attr( get_the_ID() ); ?>"><?php echo esc_html( $button_text ); ?></a>
									<?php else : ?>
										<a href="<?php echo esc_url( $button_url ); ?>" class="<?php echo esc_attr( $button_class ); ?>"><?php echo esc_html( $button_text ); ?></a>
									<?php endif; ?>
								</div>

								<?php if ( $full_issue_id && ! empty( $full_issue_id ) && ( $has_active_subscription || 'read' === $button_action ) ) : ?>
									<div class="magazine-demo-popup" id="magazine-popup-<?php echo esc_attr( get_the_ID() ); ?>" style="display: none;">
										<div class="magazine-popup-overlay"></div>
										<div class="magazine-popup-content">
											<button class="magazine-popup-close" data-popup-id="magazine-popup-<?php echo esc_attr( get_the_ID() ); ?>">&times;</button>
											<div class="magazine-demo-flip">
												<?php echo do_shortcode( '[dflip id="' . esc_attr( $full_issue_id ) . '"][/dflip]' ); ?>
											</div>
										</div>
									</div>
								<?php endif; ?>
								<?php
							}
							wp_reset_postdata();
						} else {
							echo '<p>No magazines found.</p>';
						}
						?>
					</div>

					<?php
					the_posts_pagination( array(
						'mid_size'  => 2,
						'prev_text' => __( '&laquo; Previous', 'braunvieh-magazine' ),
						'next_text' => __( 'Next &raquo;', 'braunvieh-magazine' ),
					) );
					?>
					<a href="#" id="go-top"></a>
				</div>
			</div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		function openMagazinePopup(popupId) {
			if (popupId && $('#' + popupId).length) {
				$('.magazine-demo-popup:visible').fadeOut(200);
				setTimeout(function () { $('#' + popupId).fadeIn(300); $('body').css('overflow', 'hidden'); }, 200);
			}
		}
		$(document).on('click', '.magazine-item-clickable', function (e) {
			var $target = $(e.target);
			if ($target.is('a') || $target.closest('a.magazine-button-active').length || $target.closest('a.magazine-button-purchase').length) { return; }
			e.preventDefault(); e.stopPropagation();
			var $item = $(this), popupId = $item.data('popup-id'), purchaseUrl = $item.data('purchase-url');
			if (popupId) { openMagazinePopup(popupId); }
			else if (purchaseUrl) { window.location.href = purchaseUrl; }
		});
		$(document).on('click', '.magazine-button-active', function (e) {
			e.preventDefault(); e.stopPropagation();
			var popupId = $(this).data('popup-id');
			if (popupId) { openMagazinePopup(popupId); }
		});
		$(document).on('click', '.magazine-button-purchase', function (e) { e.stopPropagation(); });
		$(document).on('click', '.magazine-popup-close', function (e) {
			e.preventDefault();
			var popupId = $(this).data('popup-id');
			if (popupId) { $('#' + popupId).fadeOut(300); $('body').css('overflow', ''); }
		});
		$(document).on('click', '.magazine-popup-overlay', function () {
			$(this).closest('.magazine-demo-popup').fadeOut(300); $('body').css('overflow', '');
		});
		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' || e.keyCode === 27) { $('.magazine-demo-popup:visible').fadeOut(300); $('body').css('overflow', ''); }
		});
	});
	</script>
	<?php
	return ob_get_clean();
}

// Register the dynamic block used by templates/archive-magazine.html.
add_action( 'init', function () {
	register_block_type( 'braunvieh/magazine-archive', array(
		'render_callback' => 'braunvieh_render_magazine_archive',
		'supports'        => array( 'html' => false, 'inserter' => false ),
	) );
} );

/**
 * [productbox] — the magazine landing "product box" (demo flipbook(s) + subscribe box).
 *
 * Ported from the classic theme's inc/magazine_functions.php, but ADAPTED for this
 * site which has NO WooCommerce: the classic version priced/checked out the
 * subscription through local Woo (wc_get_product / wc_get_cart_url). Here the
 * subscription is bought on the separate SHOP site, so the price is a theme
 * constant and "Subscribe now" links to EXTERNAL_SHOP_URL's add-to-cart. The demo
 * flipbooks and the PMS "already subscribed" path are unchanged.
 */

// Subscription details (the product lives on the shop site; edit here if it changes).
if ( ! defined( 'BRAUNVIEH_SUB_PRICE' ) )      { define( 'BRAUNVIEH_SUB_PRICE', 'US $ 35' ); }
if ( ! defined( 'BRAUNVIEH_SUB_PRODUCT_ID' ) ) { define( 'BRAUNVIEH_SUB_PRODUCT_ID', 35092 ); }

function braunvieh_productbox_func() {
	if ( ! get_field( 'ssp' ) ) {
		return '';
	}

	ob_start();

	// Active-subscription check (Paid Member Subscriptions), same as the archive.
	$user_id                 = get_current_user_id();
	$has_active_subscription = false;
	$subscription_id         = 30696;
	if ( $user_id > 0 ) {
		if ( function_exists( 'pms_get_member_subscriptions' ) ) {
			foreach ( (array) pms_get_member_subscriptions( array( 'user_id' => $user_id ) ) as $subscription ) {
				if ( $subscription->subscription_plan_id == $subscription_id ) {
					$has_active_subscription = ( 'active' === $subscription->status );
					break;
				}
			}
		} elseif ( function_exists( 'pms_is_member_active' ) ) {
			$has_active_subscription = pms_is_member_active( $user_id, $subscription_id );
		}
	}

	if ( $has_active_subscription ) {
		$magazine_archive_url = get_post_type_archive_link( 'magazine' );
		?>
		<div class="subscription-login-section">
			<div class="round magasines-redirect-image">
				<svg xmlns="http://www.w3.org/2000/svg" width="81" height="81" viewBox="0 0 81 81" fill="none"><path d="M40.5 16.16C38.33 13.29 34.63 10 29.09 10H10.66C9.2 10 8.02 11.17 8.02 12.62v4.36H3.63C2.18 16.97 1 18.14 1 19.59v48.8C1 69.83 2.18 71 3.63 71h73.74C78.82 71 80 69.83 80 68.39v-48.8c0-1.44-1.18-2.61-2.63-2.61h-4.39v-4.36C72.98 11.17 71.8 10 70.34 10H51.91c-5.54 0-9.24 3.29-11.41 6.16z" fill="#B3AAA0"/></svg>
			</div>
			<h3 style="margin-top: 2rem; margin-bottom: 0rem;">You have already purchased Magazines</h3>
			<a class="red-button magaziner-redirect" href="<?php echo esc_url( $magazine_archive_url ); ?>"><span>VIEW MAGAZINES PAGE</span></a>
		</div>
		<?php
	} else {
		// Demo flipbooks (magazines flagged display_demo). No Woo needed.
		$magazine_query = new WP_Query( array(
			'post_type'      => 'magazine',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );
		if ( $magazine_query->have_posts() ) :
			?>
			<div class="magazine-demo-container">
				<?php
				while ( $magazine_query->have_posts() ) :
					$magazine_query->the_post();
					if ( ! get_field( 'display_demo' ) ) {
						continue;
					}
					$demo_issue_id = get_field( 'magazine_demo_issue' );
					?>
					<div class="magazine-demo-item" data-popup-id="magazine-popup-<?php echo esc_attr( get_the_ID() ); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="magazine-demo-thumbnail"><?php the_post_thumbnail( 'medium_large', array( 'class' => 'magazine-thumbnail-img' ) ); ?></div>
						<?php endif; ?>
						<a href="#" class="magazine-demo-button" data-popup-id="magazine-popup-<?php echo esc_attr( get_the_ID() ); ?>">VIEW DEMO</a>
					</div>
					<?php if ( $demo_issue_id && ! empty( $demo_issue_id ) ) : ?>
						<div class="magazine-demo-popup" id="magazine-popup-<?php echo esc_attr( get_the_ID() ); ?>" style="display: none;">
							<div class="magazine-popup-overlay"></div>
							<div class="magazine-popup-content">
								<button class="magazine-popup-close" data-popup-id="magazine-popup-<?php echo esc_attr( get_the_ID() ); ?>">&times;</button>
								<div class="magazine-demo-flip"><?php echo do_shortcode( '[dflip id="' . esc_attr( $demo_issue_id ) . '"][/dflip]' ); ?></div>
							</div>
						</div>
					<?php endif; ?>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
			<?php
		endif;

		// Subscribe box — buys on THIS site (magazine now has its own WooCommerce).
		// Add the subscription product to the local cart and jump to checkout, like
		// the original single-site setup. Resolve the product id: prefer a local Woo
		// product (by the constant, else by title), so it works after CSV import.
		$cows_sub_id = 0;
		if ( defined( 'BRAUNVIEH_SUB_PRODUCT_ID' ) && get_post_type( (int) BRAUNVIEH_SUB_PRODUCT_ID ) === 'product' ) {
			$cows_sub_id = (int) BRAUNVIEH_SUB_PRODUCT_ID;
		} elseif ( function_exists( 'wc_get_product' ) ) {
			$found = get_posts( array( 'post_type' => 'product', 'title' => '10 Magazines per year', 'posts_per_page' => 1, 'fields' => 'ids', 'post_status' => 'publish' ) );
			if ( $found ) { $cows_sub_id = (int) $found[0]; }
		}
		$subscribe_url = $cows_sub_id
			? home_url( '/cart/?add-to-cart=' . $cows_sub_id . '&quantity=1' )
			: trailingslashit( EXTERNAL_SHOP_URL ) . '?add-to-cart=' . (int) BRAUNVIEH_SUB_PRODUCT_ID . '&quantity=1';
		?>
		<div class="section-text large subscription-product">
			<div class="subscription-box">
				<div class="subscription-price">
					<span class="price-amount"><?php echo esc_html( BRAUNVIEH_SUB_PRICE ); ?></span>
					<span class="price-period">PER YEAR</span>
					<div class="price-underline"></div>
				</div>
				<div class="subscription-features">
					<div class="feature-item">10 ISSUES PER YEAR</div>
					<div class="feature-item">READ UNLIMITED TIMES</div>
					<div class="feature-item">READ ON EVERY DEVICE</div>
				</div>
				<a href="<?php echo esc_url( $subscribe_url ); ?>" class="subscription-button">Subscribe now</a>
			</div>
		</div>
		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="subscription-login-section">
				<p class="subscription-login-text">PURCHASED ALREADY?</p>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="subscription-login-button">LOGIN</a>
			</div>
		<?php endif; ?>
		<?php
	}
	?>
	<script>
	jQuery(document).ready(function($){
		function openMagazinePopup(popupId){
			if(popupId && $('#'+popupId).length){
				$('.magazine-demo-popup:visible').fadeOut(200);
				setTimeout(function(){ $('#'+popupId).fadeIn(300); $('body').css('overflow','hidden'); },200);
			}
		}
		$(document).on('click','.magazine-demo-item',function(e){
			var $t=$(e.target);
			if($t.is('a')||$t.closest('a.magazine-demo-button').length){return;}
			e.preventDefault(); e.stopPropagation();
			var popupId=$(this).data('popup-id'); if(popupId){openMagazinePopup(popupId);}
		});
		$(document).on('click','.magazine-demo-button',function(e){
			e.preventDefault(); e.stopPropagation();
			var popupId=$(this).data('popup-id'); if(popupId){openMagazinePopup(popupId);}
		});
		$(document).on('click','.magazine-popup-close',function(e){
			e.preventDefault();
			var popupId=$(this).data('popup-id'); if(popupId){$('#'+popupId).fadeOut(300); $('body').css('overflow','');}
		});
		$(document).on('click','.magazine-popup-overlay',function(){ $(this).closest('.magazine-demo-popup').fadeOut(300); $('body').css('overflow',''); });
		$(document).on('keydown',function(e){ if(e.key==='Escape'||e.keyCode===27){ $('.magazine-demo-popup:visible').fadeOut(300); $('body').css('overflow',''); } });
	});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'productbox', 'braunvieh_productbox_func' );

/**
 * Trim checkout fields for the digital magazine (virtual-only) cart.
 *
 * The magazine subscription is a virtual product — no shipping, no delivery — so the
 * address / phone / order-notes fields aren't needed. Only drop them when the cart
 * needs no shipping, so a mixed/physical cart (if the site ever sells one) keeps the
 * full address. Priority 10000 so it runs AFTER Checkout Field Editor / WooCustomizer.
 * NOTE: do this in code, not the Checkout Field Editor UI (that's global).
 */
add_filter( 'woocommerce_checkout_fields', 'bvs_trim_checkout_fields_for_digital', 10000 );
function bvs_trim_checkout_fields_for_digital( $fields ) {
	if ( ! WC()->cart || WC()->cart->needs_shipping() ) {
		return $fields;
	}

	unset( $fields['billing']['billing_address_1'] );
	unset( $fields['billing']['billing_address_2'] );
	unset( $fields['billing']['billing_city'] );
	unset( $fields['billing']['billing_postcode'] );
	unset( $fields['billing']['billing_telefonnummer'] );
	unset( $fields['order']['order_comments'] );

	return $fields;
}

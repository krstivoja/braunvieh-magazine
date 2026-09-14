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
				<svg xmlns="http://www.w3.org/2000/svg" width="81" height="81" viewBox="0 0 81 81" fill="none"><path d="M10.3813 10.0026C9.03372 10.1421 8.01541 11.2722 8.02225 12.6169V16.9741H3.63337C3.54079 16.9707 3.45164 16.9707 3.35906 16.9741C2.0115 17.1137 0.993188 18.2438 1.00003 19.5884V68.3857C1.00003 69.829 2.17959 71 3.63337 71H77.3667C78.8204 71 80 69.829 80 68.3857V19.5884C80 18.1451 78.8204 16.9741 77.3667 16.9741H72.9778V12.6169C72.9778 11.1736 71.7982 10.0026 70.3444 10.0026H51.9111C46.3736 10.0026 42.6743 13.2909 40.5 16.1571C38.3261 13.2909 34.6265 10.0026 29.0889 10.0026H10.6556C10.563 9.99915 10.4739 9.99915 10.3813 10.0026ZM13.2889 15.2312H29.0889C34.4687 15.2312 37.3866 20.9228 37.8667 21.9304V59.5349C35.71 57.6797 32.8195 56.1852 29.0889 56.1852H13.2889V15.2312ZM51.9111 15.2312H67.7111V56.1855H51.9111C48.1806 56.1855 45.29 57.6798 43.1333 59.5351V21.9307C43.6134 20.9231 46.5313 15.2312 51.9111 15.2312ZM6.2667 22.2027H8.02225V58.7998C8.02225 60.2431 9.20181 61.4141 10.6556 61.4141H29.0889C32.48 61.4141 34.9351 63.7765 36.4129 65.7713H6.26652L6.2667 22.2027ZM72.9778 22.2027H74.7333V65.7713H44.5869C46.0648 63.7799 48.5198 61.4141 51.9109 61.4141H70.3443C71.798 61.4141 72.9776 60.2431 72.9776 58.7998L72.9778 22.2027ZM19.1595 23.0742C18.46 23.1082 17.8051 23.418 17.3354 23.9354C16.8691 24.4494 16.6256 25.1302 16.6633 25.8247C16.6976 26.5191 17.0096 27.1693 17.5308 27.6322C18.052 28.0986 18.7343 28.3369 19.4338 28.3028H31.7227C32.4291 28.3131 33.108 28.0407 33.6085 27.5505C34.1126 27.057 34.3937 26.3864 34.3937 25.6885C34.3937 24.9873 34.1126 24.3167 33.6085 23.8265C33.1079 23.3363 32.429 23.064 31.7227 23.0742H19.4338C19.3413 23.0674 19.2521 23.0674 19.1595 23.0742ZM49.0039 23.0742C48.3044 23.1082 47.6495 23.418 47.1798 23.9354C46.7135 24.4494 46.47 25.1302 46.5078 25.8247C46.5421 26.5191 46.8541 27.1693 47.3753 27.6322C47.8964 28.0986 48.5788 28.3369 49.2783 28.3028H61.5672C62.2735 28.3131 62.9524 28.0407 63.453 27.5505C63.957 27.057 64.2382 26.3864 64.2382 25.6885C64.2382 24.9873 63.957 24.3167 63.453 23.8265C62.9524 23.3363 62.2734 23.064 61.5672 23.0742H49.2783C49.1857 23.0674 49.0965 23.0674 49.0039 23.0742ZM19.1595 34.4029C17.7057 34.4778 16.5879 35.7067 16.6633 37.1534C16.7387 38.5967 17.9799 39.7064 19.4337 39.6316H31.7226C32.4289 39.6384 33.1078 39.3695 33.6084 38.8759C34.109 38.3857 34.3936 37.7151 34.3936 37.0173C34.3936 36.316 34.109 35.6454 33.6084 35.1552C33.1078 34.6617 32.4289 34.3927 31.7226 34.4029H19.4337C19.3411 34.3961 19.2521 34.3961 19.1595 34.4029ZM49.0039 34.4029C47.5501 34.4778 46.4323 35.7067 46.5077 37.1534C46.5831 38.5967 47.8244 39.7064 49.2781 39.6316H61.567C62.2734 39.6384 62.9522 39.3695 63.4528 38.8759C63.9534 38.3857 64.238 37.7151 64.238 37.0173C64.238 36.316 63.9534 35.6454 63.4528 35.1552C62.9522 34.6617 62.2733 34.3927 61.567 34.4029H49.2781C49.1856 34.3961 49.0965 34.3961 49.0039 34.4029ZM19.1595 45.7283C18.46 45.7623 17.8017 46.0721 17.332 46.5895C16.8588 47.1035 16.6153 47.7843 16.6531 48.4822C16.6873 49.1766 17.0028 49.8268 17.524 50.2931C18.0452 50.7561 18.7343 50.9978 19.4338 50.9569H31.7227C32.4291 50.9671 33.1079 50.6982 33.6085 50.2046C34.1126 49.7144 34.3937 49.0439 34.3937 48.3426C34.3937 47.6448 34.1126 46.9742 33.6085 46.4806C33.1079 45.9904 32.429 45.7181 31.7227 45.7283H19.4338C19.3413 45.7249 19.2521 45.7249 19.1595 45.7283ZM49.0039 45.7283C48.3044 45.7623 47.6461 46.0721 47.1764 46.5895C46.7032 47.1035 46.4598 47.7843 46.4975 48.4822C46.5318 49.1766 46.8472 49.8268 47.3684 50.2931C47.8896 50.7561 48.5788 50.9978 49.2783 50.9569H61.5672C62.2735 50.9671 62.9524 50.6982 63.453 50.2046C63.957 49.7144 64.2382 49.0439 64.2382 48.3426C64.2382 47.6448 63.957 46.9742 63.453 46.4806C62.9524 45.9904 62.2734 45.7181 61.5672 45.7283H49.2783C49.1857 45.7249 49.0965 45.7249 49.0039 45.7283Z" fill="#B3AAA0"/></svg>
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

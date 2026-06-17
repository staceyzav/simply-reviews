<?php
/**
 * Plugin Name: Simply Reviews
 * Plugin URI:  https://simplydesign.com/simply-reviews
 * Description: Curated testimonial slider. Add highlight reviews manually, display with [simply_reviews]. Zero dependencies — works on any theme.
 * Author:      Simply Design
 * Author URI:  https://simplydesign.com
 * Version:     1.0.0
 * License:     GPL-2.0-or-later
 * Text Domain: simply-reviews
 * Requires at least: 5.4
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SR_VERSION', '1.0.0' );
define( 'SR_PATH',    plugin_dir_path( __FILE__ ) );
define( 'SR_URL',     plugin_dir_url( __FILE__ ) );

require_once SR_PATH . 'includes/class-github-updater.php';
new Simply_GitHub_Updater( 'plugin', plugin_basename( __FILE__ ), 'staceyzav/simply-reviews', SR_VERSION );

// ── CPT ──────────────────────────────────────────────────────────────────────

add_action( 'init', 'sr_register_cpt' );
function sr_register_cpt() {
	register_post_type( 'simply_review', [
		'labels' => [
			'name'               => __( 'Reviews',        'simply-reviews' ),
			'singular_name'      => __( 'Review',         'simply-reviews' ),
			'add_new_item'       => __( 'Add New Review', 'simply-reviews' ),
			'edit_item'          => __( 'Edit Review',    'simply-reviews' ),
			'all_items'          => __( 'All Reviews',    'simply-reviews' ),
			'menu_name'          => __( 'Reviews',        'simply-reviews' ),
		],
		'public'        => false,
		'show_ui'       => true,
		'show_in_menu'  => true,
		'menu_icon'     => 'dashicons-star-filled',
		'menu_position' => 25,
		'supports'      => [ 'title', 'editor', 'page-attributes' ],
		'rewrite'       => false,
	] );
}

// ── META BOX ─────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', 'sr_add_meta_box' );
function sr_add_meta_box() {
	add_meta_box( 'sr_details', __( 'Review Details', 'simply-reviews' ), 'sr_meta_box_cb', 'simply_review', 'side', 'high' );
}

function sr_meta_box_cb( $post ) {
	wp_nonce_field( 'sr_save_meta', 'sr_nonce' );

	$rating = get_post_meta( $post->ID, '_review_rating', true ) ?: '5';
	$source = get_post_meta( $post->ID, '_review_source', true );
	$date   = get_post_meta( $post->ID, '_review_date',   true );
	?>
	<p>
		<label for="sr_rating"><strong><?php esc_html_e( 'Star Rating', 'simply-reviews' ); ?></strong></label><br>
		<select name="sr_rating" id="sr_rating" style="width:100%;margin-top:4px;">
			<?php foreach ( [ 5, 4, 3, 2, 1 ] as $n ) : ?>
				<option value="<?php echo $n; ?>" <?php selected( $rating, $n ); ?>>
					<?php echo esc_html( str_repeat( '★', $n ) . str_repeat( '☆', 5 - $n ) . " ($n)" ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="sr_source"><strong><?php esc_html_e( 'Source', 'simply-reviews' ); ?></strong></label><br>
		<select name="sr_source" id="sr_source" style="width:100%;margin-top:4px;">
			<option value="" <?php selected( $source, '' ); ?>><?php esc_html_e( '— select —', 'simply-reviews' ); ?></option>
			<?php foreach ( [ 'Google', 'Yelp', 'TripAdvisor', 'Facebook', 'Direct' ] as $s ) : ?>
				<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $source, $s ); ?>><?php echo esc_html( $s ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="sr_date"><strong><?php esc_html_e( 'Date', 'simply-reviews' ); ?></strong></label><br>
		<input type="text" name="sr_date" id="sr_date" value="<?php echo esc_attr( $date ); ?>"
			   placeholder="e.g. January 2025" style="width:100%;margin-top:4px;">
	</p>
	<p style="color:#666;font-size:11px;margin-top:8px;">
		<?php esc_html_e( 'Reviewer name = post title. Review text = post body. Drag to reorder in All Reviews.', 'simply-reviews' ); ?>
	</p>
	<?php
}

add_action( 'save_post_simply_review', 'sr_save_meta' );
function sr_save_meta( $post_id ) {
	if (
		! isset( $_POST['sr_nonce'] ) ||
		! wp_verify_nonce( $_POST['sr_nonce'], 'sr_save_meta' ) ||
		defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE
	) return;

	if ( isset( $_POST['sr_rating'] ) ) {
		$rating = absint( $_POST['sr_rating'] );
		update_post_meta( $post_id, '_review_rating', max( 1, min( 5, $rating ) ) );
	}
	if ( isset( $_POST['sr_source'] ) ) {
		$allowed = [ '', 'Google', 'Yelp', 'TripAdvisor', 'Facebook', 'Direct' ];
		$source  = in_array( $_POST['sr_source'], $allowed, true ) ? $_POST['sr_source'] : '';
		update_post_meta( $post_id, '_review_source', $source );
	}
	if ( isset( $_POST['sr_date'] ) ) {
		update_post_meta( $post_id, '_review_date', sanitize_text_field( $_POST['sr_date'] ) );
	}
}

// ── ENQUEUE ───────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'sr_enqueue' );
function sr_enqueue() {
	wp_enqueue_style(  'simply-reviews', SR_URL . 'assets/css/simply-reviews.css', [], SR_VERSION );
	wp_enqueue_script( 'simply-reviews', SR_URL . 'assets/js/simply-reviews.js',   [], SR_VERSION, true );
}

// ── SHORTCODE ─────────────────────────────────────────────────────────────────

add_shortcode( 'simply_reviews', 'sr_shortcode' );
function sr_shortcode( $atts ) {
	$a = shortcode_atts( [
		'limit'       => -1,
		'show_name'   => 1,
		'show_source' => 0,
		'show_date'   => 0,
		'min_stars'   => 1,
		'source'      => '',
		'autoplay'    => 0,
	], $atts );

	$query_args = [
		'post_type'      => 'simply_review',
		'posts_per_page' => intval( $a['limit'] ),
		'post_status'    => 'publish',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	];

	if ( ! empty( $a['source'] ) ) {
		$query_args['meta_query'] = [ [
			'key'   => '_review_source',
			'value' => sanitize_text_field( $a['source'] ),
		] ];
	}

	$reviews = get_posts( $query_args );

	// Filter by minimum star rating
	$min = max( 1, min( 5, intval( $a['min_stars'] ) ) );
	$reviews = array_values( array_filter( $reviews, function( $r ) use ( $min ) {
		return (int) ( get_post_meta( $r->ID, '_review_rating', true ) ?: 5 ) >= $min;
	} ) );

	if ( empty( $reviews ) ) return '';

	$show_name   = (bool) $a['show_name'];
	$show_source = (bool) $a['show_source'];
	$show_date   = (bool) $a['show_date'];
	$autoplay    = absint( $a['autoplay'] );
	$count       = count( $reviews );

	ob_start();
	?>
	<div class="sr-slider" data-autoplay="<?php echo esc_attr( $autoplay ); ?>">
		<div class="sr-slides">
			<?php foreach ( $reviews as $i => $review ) :
				$rating = (int) ( get_post_meta( $review->ID, '_review_rating', true ) ?: 5 );
				$source = get_post_meta( $review->ID, '_review_source', true );
				$date   = get_post_meta( $review->ID, '_review_date',   true );
			?>
			<div class="sr-slide<?php echo $i === 0 ? ' is-active' : ''; ?>">

				<div class="sr-stars" aria-label="<?php printf( esc_attr__( '%d out of 5 stars', 'simply-reviews' ), $rating ); ?>">
					<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
						<span class="sr-star<?php echo $s <= $rating ? ' filled' : ''; ?>" aria-hidden="true">★</span>
					<?php endfor; ?>
				</div>

				<div class="sr-quote">
					<p class="sr-text"><?php echo wp_kses_post( wpautop( $review->post_content ) ); ?></p>
				</div>

				<?php if ( $show_name || $show_source || $show_date ) : ?>
				<div class="sr-meta">
					<?php if ( $show_name && $review->post_title ) : ?>
						<span class="sr-name"><?php echo esc_html( $review->post_title ); ?></span>
					<?php endif; ?>
					<?php if ( $show_source && $source ) : ?>
						<span class="sr-source"><?php echo esc_html( $source ); ?></span>
					<?php endif; ?>
					<?php if ( $show_date && $date ) : ?>
						<span class="sr-date"><?php echo esc_html( $date ); ?></span>
					<?php endif; ?>
				</div>
				<?php endif; ?>

			</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $count > 1 ) : ?>
		<div class="sr-dots" role="tablist" aria-label="<?php esc_attr_e( 'Reviews navigation', 'simply-reviews' ); ?>">
			<?php for ( $i = 0; $i < $count; $i++ ) : ?>
				<button class="sr-dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
						role="tab"
						aria-label="<?php printf( esc_attr__( 'Review %d', 'simply-reviews' ), $i + 1 ); ?>"
						data-slide="<?php echo $i; ?>"></button>
			<?php endfor; ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

<?php
/**
 * Plugin Name: Simply Reviews
 * Plugin URI:  https://simplydesign.com/simply-reviews
 * Description: Curated testimonial slider. Add highlight reviews manually, display with [simply_reviews]. Zero dependencies — works on any theme.
 * Author:      Simply Design
 * Author URI:  https://simplydesign.com
 * Version:     1.0.5
 * License:     GPL-2.0-or-later
 * Text Domain: simply-reviews
 * Requires at least: 5.4
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SR_VERSION', '1.0.5' );
define( 'SR_PATH',    plugin_dir_path( __FILE__ ) );
define( 'SR_URL',     plugin_dir_url( __FILE__ ) );


// ── CPT + TAXONOMY ───────────────────────────────────────────────────────────

add_action( 'init', 'sr_register_taxonomy' );
function sr_register_taxonomy() {
	register_taxonomy( 'sr_category', [ 'simply_review', 'page' ], [
		'labels' => [
			'name'              => __( 'Review Categories', 'simply-reviews' ),
			'singular_name'     => __( 'Review Category',  'simply-reviews' ),
			'add_new_item'      => __( 'Add New Category',  'simply-reviews' ),
			'edit_item'         => __( 'Edit Category',     'simply-reviews' ),
			'search_items'      => __( 'Search Categories', 'simply-reviews' ),
			'all_items'         => __( 'All Categories',    'simply-reviews' ),
			'menu_name'         => __( 'Review Categories', 'simply-reviews' ),
		],
		'hierarchical'      => true,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => false,
	] );
}

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
	<p>
		<label for="sr_youtube"><strong><?php esc_html_e( 'YouTube URL', 'simply-reviews' ); ?></strong></label><br>
		<input type="url" name="sr_youtube" id="sr_youtube"
			   value="<?php echo esc_attr( get_post_meta( $post->ID, '_review_youtube', true ) ); ?>"
			   placeholder="https://youtu.be/..." style="width:100%;margin-top:4px;">
		<span style="color:#666;font-size:11px;display:block;margin-top:4px;"><?php esc_html_e( 'Optional. When set, slide shows video thumbnail + snippet overlay.', 'simply-reviews' ); ?></span>
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
	if ( isset( $_POST['sr_youtube'] ) ) {
		update_post_meta( $post_id, '_review_youtube', esc_url_raw( $_POST['sr_youtube'] ) );
	}
}

function sr_extract_youtube_id( $url ) {
	if ( ! $url ) return '';
	if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
		return $m[1];
	}
	return '';
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
		'limit'          => -1,
		'show_name'      => 1,
		'show_source'    => 0,
		'show_date'      => 0,
		'min_stars'      => 1,
		'source'         => '',
		'category'       => '',
		'autoplay'       => 0,
		'match_page_cat' => '',
	], $atts );

	if ( filter_var( $a['match_page_cat'], FILTER_VALIDATE_BOOLEAN ) && empty( $a['category'] ) ) {
		$page_terms = get_the_terms( get_the_ID(), 'sr_category' );
		if ( $page_terms && ! is_wp_error( $page_terms ) ) {
			$a['category'] = implode( ',', wp_list_pluck( $page_terms, 'slug' ) );
		}
	}

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

	if ( ! empty( $a['category'] ) ) {
		$query_args['tax_query'] = [ [
			'taxonomy' => 'sr_category',
			'field'    => 'slug',
			'terms'    => array_map( 'sanitize_title', explode( ',', $a['category'] ) ),
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

	$has_video = ! empty( array_filter( $reviews, function( $r ) {
		return sr_extract_youtube_id( get_post_meta( $r->ID, '_review_youtube', true ) );
	} ) );

	ob_start();
	?>
	<div class="sr-slider<?php echo $has_video ? ' sr-slider--has-video' : ''; ?>" data-autoplay="<?php echo esc_attr( $autoplay ); ?>">
		<div class="sr-viewport">
		<div class="sr-slides">
			<?php foreach ( $reviews as $i => $review ) :
				$rating   = (int) ( get_post_meta( $review->ID, '_review_rating', true ) ?: 5 );
				$source   = get_post_meta( $review->ID, '_review_source',  true );
				$date     = get_post_meta( $review->ID, '_review_date',    true );
				$yt_url   = get_post_meta( $review->ID, '_review_youtube', true );
				$yt_id    = sr_extract_youtube_id( $yt_url );
				$is_video = ! empty( $yt_id );
			?>
			<div class="sr-slide<?php echo $i === 0 ? ' is-active' : ''; ?><?php echo $is_video ? ' sr-slide--video' : ''; ?>">

			<?php if ( $is_video ) :
				$thumb_url = 'https://img.youtube.com/vi/' . $yt_id . '/maxresdefault.jpg';
				$snippet   = wp_strip_all_tags( $review->post_content );
			?>
				<div class="sr-video-thumb"
				     style="background-image:url('<?php echo esc_url( $thumb_url ); ?>')"
				     data-yt-id="<?php echo esc_attr( $yt_id ); ?>"
				     role="button"
				     tabindex="0"
				     aria-label="<?php echo esc_attr( sprintf( 'Play video review by %s', $review->post_title ) ); ?>">

					<div class="sr-video-caption">
					<div class="sr-video-play-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="11" fill="none" stroke="white" stroke-width="1.5"/><polygon points="9.5,6.5 19,12 9.5,17.5" fill="white"/></svg>
					</div>
						<?php if ( $snippet ) : ?>
						<div class="sr-quote">
							<div class="sr-text"><?php echo esc_html( $snippet ); ?></div>
						</div>
						<?php endif; ?>
						<div class="sr-meta">
							<?php if ( $review->post_title ) : ?>
							<span class="sr-name"><?php echo esc_html( $review->post_title ); ?></span>
							<?php endif; ?>
							<?php if ( $show_date && $date ) : ?>
							<span class="sr-date"><?php echo esc_html( $date ); ?></span>
							<?php endif; ?>
						</div>
					</div>

				</div>

			<?php else : ?>

				<div class="sr-stars" aria-label="<?php printf( esc_attr__( '%d out of 5 stars', 'simply-reviews' ), $rating ); ?>">
					<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
						<span class="sr-star<?php echo $s <= $rating ? ' filled' : ''; ?>" aria-hidden="true">★</span>
					<?php endfor; ?>
				</div>

				<div class="sr-quote">
					<div class="sr-text"><?php echo wp_kses_post( wpautop( $review->post_content ) ); ?></div>
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

			<?php endif; ?>

			</div>
			<?php endforeach; ?>
		</div>
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

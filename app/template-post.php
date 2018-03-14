<?php
/**
 * Template functions related to posts.  The functions in this file are for handling template tags or features
 * of template tags that WordPress core does not currently handle.
 *
 * @package    HybridCore
 * @subpackage Includes
 * @author     Justin Tadlock <justintadlock@gmail.com>
 * @copyright  Copyright (c) 2008 - 2017, Justin Tadlock
 * @link       https://themehybrid.com/hybrid-core
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace Hybrid;

/**
 * Checks if a post has any content. Useful if you need to check if the user has written any content
 * before performing any actions.
 *
 * @since  1.6.0
 * @access public
 * @param  int    $post_id
 * @return bool
 */
function post_has_content( $post_id = 0 ) {
	$post = get_post( $post_id );

	return ! empty( $post->post_content );
}

/**
 * Outputs a link to the post format archive.
 *
 * @since  2.0.0
 * @access public
 * @return void
 */
function post_format_link() {

	echo get_post_format_link();
}

/**
 * Generates a link to the current post format's archive.  If the post doesn't have a post format, the link
 * will go to the post permalink.
 *
 * @since  2.0.0
 * @access public
 * @return string
 */
function get_post_format_link() {

	$format = get_post_format();
	$url    = $format ? get_post_format_link( $format ) : get_permalink();

	return sprintf( '<a href="%s" class="post-format-link">%s</a>', esc_url( $url ), get_post_format_string( $format ) );
}

/**
 * Outputs a post's author.
 *
 * @since  2.0.0
 * @access public
 * @param  array   $args
 * @return void
 */
function post_author( $args = array() ) {

	echo get_post_author( $args );
}

/**
 * Function for getting the current post's author in The Loop and linking to the author archive page.
 * This function was created because core WordPress does not have template tags with proper translation
 * and RTL support for this.  An equivalent getter function for `the_author_posts_link()` would
 * instantly solve this issue.
 *
 * @since  2.0.0
 * @access public
 * @param  array   $args
 * @return string
 */
function get_post_author( $args = array() ) {

	$html = '';

	$defaults = array(
		'text'   => '%s',
		'before' => '',
		'after'  => '',
		'wrap'   => '<span %s>%s</span>',
	);

	$args = wp_parse_args( $args, $defaults );

	// Output buffering to get the author posts link.
	ob_start();
	\the_author_posts_link();
	$link = ob_get_clean();
	// A small piece of my soul just died.  Kittens no longer purr.  Dolphins lost the ability to swim with grace.

	if ( $link ) {
		$html .= $args['before'];
		$html .= sprintf( $args['wrap'], get_attr( 'entry-author' ), sprintf( $args['text'], $link ) );
		$html .= $args['after'];
	}

	return $html;
}

/**
 * Outputs a post's taxonomy terms.
 *
 * @since  2.0.0
 * @access public
 * @param  array   $args
 * @return void
 */
function post_terms( $args = array() ) {

	echo get_post_terms( $args );
}

/**
 * This template tag is meant to replace template tags like `the_category()`, `the_terms()`, etc.  These core
 * WordPress template tags don't offer proper translation and RTL support without having to write a lot of
 * messy code within the theme's templates.  This is why theme developers often have to resort to custom
 * functions to handle this (even the default WordPress themes do this).  Particularly, the core functions
 * don't allow for theme developers to add the terms as placeholders in the accompanying text (ex: "Posted in %s").
 * This funcion is a wrapper for the WordPress `get_the_terms_list()` function.  It uses that to build a
 * better post terms list.
 *
 * @since  2.0.0
 * @access public
 * @param  array   $args
 * @return string
 */
function get_post_terms( $args = array() ) {

	$html = '';

	$defaults = array(
		'post_id'    => get_the_ID(),
		'taxonomy'   => 'category',
		'text'       => '%s',
		'before'     => '',
		'after'      => '',
		'wrap'       => '<span %s>%s</span>',
		// Translators: Separates tags, categories, etc. when displaying a post.
		'sep'        => _x( ', ', 'taxonomy terms separator', 'hybrid-core' )
	);

	$args = wp_parse_args( $args, $defaults );

	$terms = get_the_term_list( $args['post_id'], $args['taxonomy'], '', $args['sep'], '' );

	if ( $terms ) {
		$html .= $args['before'];
		$html .= sprintf( $args['wrap'], get_attr( 'entry-terms', $args['taxonomy'] ), sprintf( $args['text'], $terms ) );
		$html .= $args['after'];
	}

	return $html;
}

/* === Galleries === */

/**
 * Gets the gallery *item* count.  This is different from getting the gallery *image* count.  By default,
 * WordPress only allows attachments with the 'image' mime type in galleries.  However, some scripts such
 * as Cleaner Gallery allow for other mime types.  This is a more accurate count than the
 * hybrid_get_gallery_image_count() function since it will count all gallery items regardless of mime type.
 *
 * @todo Check for the [gallery] shortcode with the 'mime_type' parameter and use that in get_posts().
 *
 * @since  1.6.0
 * @access public
 * @return int
 */
function get_gallery_item_count() {

	// Check the post content for galleries.
	$galleries = get_post_galleries( get_the_ID(), true );

	// If galleries were found in the content, get the gallery item count.
	if ( ! empty( $galleries ) ) {
		$items = '';

		foreach ( $galleries as $gallery => $gallery_items )
			$items .= $gallery_items;

		preg_match_all( '#src=([\'"])(.+?)\1#is', $items, $sources, PREG_SET_ORDER );

		if ( ! empty( $sources ) )
			return count( $sources );
	}

	// If an item count wasn't returned, get the post attachments.
	$attachments = get_posts(
		array(
			'fields'         => 'ids',
			'post_parent'    => get_the_ID(),
			'post_type'      => 'attachment',
			'numberposts'    => -1
		)
	);

	// Return the attachment count if items were found.
	return ! empty( $attachments ) ? count( $attachments ) : 0;
}

/**
 * Returns the number of images displayed by the gallery or galleries in a post.
 *
 * @since  1.6.0
 * @access public
 * @return int
 */
function get_gallery_image_count() {

	// Set up an empty array for images.
	$images = array();

	// Get the images from all post galleries.
	$galleries = get_post_galleries_images();

	// Merge each gallery image into a single array.
	foreach ( $galleries as $gallery_images )
		$images = array_merge( $images, $gallery_images );

	// If there are no images in the array, just grab the attached images.
	if ( empty( $images ) ) {
		$images = get_posts(
			array(
				'fields'         => 'ids',
				'post_parent'    => get_the_ID(),
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'numberposts'    => -1
			)
		);
	}

	// Return the count of the images.
	return count( $images );
}

/* === Links === */

/**
 * Gets a URL from the content, even if it's not wrapped in an <a> tag.
 *
 * @since  1.6.0
 * @access public
 * @param  string  $content
 * @return string
 */
function get_content_url( $content ) {

	// Catch links that are not wrapped in an '<a>' tag.
	preg_match( '/<a\s[^>]*?href=[\'"](.+?)[\'"]/is', make_clickable( $content ), $matches );

	return ! empty( $matches[1] ) ? esc_url_raw( $matches[1] ) : '';
}

/**
 * Filters 'get_the_post_format_url' to make for a more robust and back-compatible function.  If WP did
 * not find a URL, check the post content for one.  If nothing is found, return the post permalink.
 *
 * @since  1.6.0
 * @access public
 * @param  string  $url
 * @param  object  $post
 * @return string
 */
function get_the_post_format_url( $url = '', $post = null ) {

	if ( ! $url ) {

		$post = is_null( $post ) ? get_post() : $post;

		$content_url = get_content_url( $post->post_content );

		$url = $content_url ? esc_url( $content_url ) : esc_url( get_permalink( $post->ID ) );
	}

	return $url;
}

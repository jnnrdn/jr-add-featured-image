<?php
/**
 * Plugin Name: JR Add Featured Image
 * Plugin URI: http://wordpress.org/extend/plugins/easy-add-thumbnail/
 * Description: Checks if posts has a featured image. If not it sets the featured image to the first image block in that post (if any). 
 * Author: Jenny Ryden
 * Version: 1.0
 * Author URI: http://jennyryden.com
 * Requires at least: 5.3
 *
 * @package JR Add Thumbnail
 */

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License version 3 as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( function_exists( 'add_theme_support' ) ) {

	add_theme_support( 'post-thumbnails' ); // This should be in your theme. But we add this here because this way we can have featured images before switching to a theme that supports them.

	/**
	 * Automatically add first image in content as featured image if none is set.
	 *
	 * @param object $post Post Object.
	 * @link https://wordpress.org/plugins/easy-add-thumbnail/
	 */
	function jr_add_featured_image( $post ) {

		$already_has_thumb = has_post_thumbnail();
		$post_type         = get_post_type( $post->ID );
		$exclude_types     = array( '' );
		$exclude_types     = apply_filters( 'eat_exclude_types', $exclude_types );

		// Do nothing if the post has already a featured image set.
		if ( $already_has_thumb ) {
			return;
		}

		// Do the job if the post is not from an excluded type.
		if ( ! in_array( $post_type, $exclude_types, true ) ) {
			// Get first attached image.
			$args = array(
				'order'          => 'ASC',
				'post_mime_type' => 'image',
				'post_parent'    => $post->ID,
				'post_status'    => null,
				'post_type'      => 'attachment',
			);
			$attached_image = get_children( $args );

			if ( $attached_image ) {

				$attachment_values = array_values( $attached_image );
				// Add attachment ID.
				add_post_meta( $post->ID, '_thumbnail_id', $attachment_values[0]->ID, true );

			} else {
				// Use regex to find image blocks in the post content.
				$output = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches );
				$output = preg_match_all( '/wp:image {"id":[0-9]+/i', $post->post_content, $matches );

				// If there are any image block, set thumbnail to first image.
				if( $output ) {
					$images_found = $matches;
					// Get the ID of first image.
					$first_img_id = preg_match_all( '/[0-9]+/i', $matches[0][0], $matches );
					$first_img_id = $matches[0][0];

					add_post_meta( $post->ID, '_thumbnail_id', (int) $first_img_id, true );
				} else {
					return;
				}
			}
		}

	}

	// Set featured image before post is displayed on the site front-end (for old posts published before enabling this plugin).
	add_action( 'the_post', 'jr_add_featured_image' );

	// // Hooks added to set the thumbnail when publishing too.
	add_action( 'new_to_publish', 'jr_add_featured_image' );
	add_action( 'draft_to_publish', 'jr_add_featured_image' );
	add_action( 'pending_to_publish', 'jr_add_featured_image' );
	add_action( 'future_to_publish', 'jr_add_featured_image' );

}

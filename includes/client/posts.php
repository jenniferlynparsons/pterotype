<?php
namespace posts;

require_once plugin_dir_path( __FILE__ ) . '../server/activities/create.php';

function handle_post_status_change( $new_status, $old_status, $post ) {
    // TODO find a way to get the id of an existing object for the post, if it exists
    $actor_slug = PTEROTYPE_BLOG_ACTOR_SLUG;
    $actor_outbox = get_rest_url(
        null, sprintf( 'pterotype/v1/actor/%s/outbox', $actor_slug )
    );
    $post_object = post_to_object( $post );
    $activity = null;
    if ( $new_status == 'publish' && $old_status != 'publish' ) {
        // Create
        $activity = \activities\create\make_create( $actor_slug, $post_object );
    } else if ( $new_status == 'publish' && $old_status == 'publish' ) {
        // Update
        $activity = \activities\update\make_update( $actor_slug, $post_object );
    } else if ( $new_status != 'publish' && $old_status == 'publish' ) {
        // Delete
        // TODO delete isn't working
        $activity = \activities\delete\make_delete( $actor_slug, $post_object );
    }
    if ( $activity && ! is_wp_error( $activity ) ) {
        $server = rest_get_server();
        $request = \WP_REST_Request::from_url( $actor_outbox );
        $request->set_method('POST');
        $request->set_body( wp_json_encode( $activity ) );
        $request->add_header( 'Content-Type', 'application/ld+json' );
        $server->dispatch( $request );
    }
}

/**
Return an object of type Article
*/
function post_to_object( $post ) {
    $object = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Article',
        'name' => $post->post_title,
        'content' => $post->post_content,
        'attributedTo' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'url' => get_permalink( $post ),
        'summary' => $post->post_excerpt,
    );
    $existing = get_existing_object( $post );
    if ( $existing ) {
        $object['id'] = $existing->activitypub_id;
    }
    return $object;
}

function get_existing_object( $post ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM pterotype_objects WHERE object->"$.url" = %s', get_permalink( $post )
    ) );
}
?>
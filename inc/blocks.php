<?php
namespace blocks;

/*
If an actor is in another actor's block list, any activities
from the blocked actor that go into the blocking actors' inbox 
get ignored
*/

function create_block( $actor_id, $blocked_actor_url ) {
    global $wpdb;
    $res = $wpdb->insert(
        'pterotype_blocks',
        array( 'actor_id' => $actor_id, 'blocked_actor_url' => $blocked_actor_url )
    );
    if ( !$res ) {
        return new \WP_Error( 'db_error', __( 'Error inserting block row', 'pterotype' ) );
    }
}
?>

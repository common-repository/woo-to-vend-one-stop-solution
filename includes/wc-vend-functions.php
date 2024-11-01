<?php


function wvos_pri( $data ) {
    echo '<pre>';
    if( is_object( $data ) || is_array( $data ) ) {
        print_r( $data );
    }
    else {
        var_dump( $data );
    }
    echo '</pre>';
}




function wvos_get_product_vend_id($post_id, $key = 'variable_vend_id' ) {
	$_meta = get_post_meta( $post_id, $key , true );
	return isset( $_meta) && $_meta != '' ? $_meta : false;
}

function wvos_get_product_by_meta($meta_key, $meta_value = '' ) {
    $posts = get_posts(
        array(
            'meta_key'          => $meta_key,
            'meta_value'        => $meta_value,
            'post_type'         => array( 'product', 'product_variation' ),
            'posts_per_page'    => 1,
            'post_status'       => 'publish',
        )
    );

    return isset( $posts[0]->ID ) ? array($posts[0]->ID => $posts[0]->post_type) : null;
}

function wvos_array_keys_exists(array $keys, array $arr) {
    return !array_diff_key(array_flip($keys), $arr);
}

function wvos_is_woocommerce_active(){

    $result = false;
    $compare_plugins = array('woocommerce/woocommerce.php');

    $target = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

    if(count(array_intersect($compare_plugins, $target))>0){

        $result = true;

    }
    return $result;

}
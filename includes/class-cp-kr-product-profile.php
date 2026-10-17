<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CP_KR_Product_Profile {

    public static function build( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return new WP_Error( 'cp_kr_invalid_product', 'Product not found.' );
        }

        $profile = array(
            'id'              => $product->get_id(),
            'title'           => $product->get_name(),
            'approved_synonyms' => get_option('cp_kr_product_synonyms', [])[$product->get_id()] ?? [],
            'permalink'       => get_permalink( $product->get_id() ),
            'canonical_url'   => wp_get_canonical_url( $product->get_id() ) ?: get_permalink( $product->get_id() ),
            'short_desc'      => wp_strip_all_tags( $product->get_short_description() ),
            'full_desc'       => wp_strip_all_tags( $product->get_description() ),
            'sku'             => $product->get_sku(),
            'categories'      => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
            'tags'            => wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) ),
            'attributes'      => self::get_attributes( $product ),
            'variations'      => array(),
            'is_variable'     => $product->is_type( 'variable' ),
            'price'           => $product->get_price(),
            'currency'        => get_woocommerce_currency(),
        );

        if ( $product->is_type( 'variable' ) ) {
            foreach ( $product->get_children() as $variation_id ) {
                $variation = wc_get_product( $variation_id );
                if ( ! $variation || ! $variation->is_purchasable() ) {
                    continue;
                }
                $attrs = array();
                foreach ( $variation->get_attributes() as $key => $value ) {
                    if ( '' !== $value ) {
                        $attrs[ $key ] = $value;
                    }
                }
                $profile['variations'][] = array(
                    'id'         => $variation_id,
                    'name'       => $variation->get_name(),
                    'attributes' => $attrs,
                    'sku'        => $variation->get_sku(),
                    'price'      => $variation->get_price(),
                );
            }
        }

        return $profile;
    }

    private static function get_attributes( $product ) {
        $attributes = array();
        foreach ( $product->get_attributes() as $attribute ) {
            if ( $attribute->is_taxonomy() ) {
                $terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
                $attributes[ $attribute->get_name() ] = $terms;
            } else {
                $attributes[ $attribute->get_name() ] = $attribute->get_options();
            }
        }
        return $attributes;
    }
}
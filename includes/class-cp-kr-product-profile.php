<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CP_KR_Product_Profile {

    private const DEFAULT_SYNONYMS = [
        'bubble-envelopes' => ['bubble mailers', 'padded envelopes', 'padded mailing envelopes'],
        'thermal-label-rolls' => ['thermal labels', 'thermal printer labels', 'label rolls'],
        'arofol-bubble-envelopes' => ['bubble mailers', 'padded envelopes', 'padded mailing envelopes'],
        'small-bubble-wrap' => ['small bubble packaging', 'small bubble wrapping'],
        'mailing-bags' => ['postal bags', 'mail bags', 'mailing sacks'],
        'clear-bubble-bags' => ['bubble bags', 'clear bubble bags', 'bubble packaging bags'],
        'large-bubble-wrap' => ['large bubble packaging', 'large bubble wrapping'],
        '48mm-2-inch-packaging-tape' => ['48mm packing tape', '2 inch packing tape', 'carton sealing tape'],
        'hand-pallet-wrap' => ['hand stretch wrap', 'pallet stretch film', 'hand stretch film'],
        'cardboard-boxes' => ['cardboard cartons', 'shipping boxes'],
        'number-plate-envelopes' => ['number plate mailers', 'registration plate envelopes'],
        'anti-static-bubble-bags' => ['anti-static bags', 'ESD bubble bags', 'static-safe bubble bags'],
        'plain-grip-seal-bags' => ['grip seal bags', 'resealable bags', 'reclosable bags', 'press seal bags'],
        'heavy-duty-125-micron-500-gauge' => ['heavy duty 500 gauge', 'heavy duty 125 micron', '500 gauge 125 micron'],
        'medium-duty-62-5-micron-250-gauge' => ['medium duty 250 gauge', 'medium duty 62.5 micron', '250 gauge 62.5 micron'],
        'loose-fill-packing-peanuts' => ['packing peanuts', 'loose fill', 'loose fill packaging', 'packaging peanuts'],
        'blue-mailing-bags' => ['blue postal bags', 'blue mail bags'],
        'pink-mailing-bags' => ['pink postal bags', 'pink mail bags'],
    ];

    public static function build( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return new WP_Error( 'cp_kr_invalid_product', 'Product not found.' );
        }

        $slug = get_post_field( 'post_name', $product->get_id() );
        $configured_synonyms = (array)get_option('cp_kr_product_synonyms', []);
        $approved_synonyms = array_merge(
            self::DEFAULT_SYNONYMS[$slug] ?? [],
            (array)($configured_synonyms[$product->get_id()] ?? []),
            (array)($configured_synonyms[$slug] ?? [])
        );

        $profile = array(
            'id'              => $product->get_id(),
            'title'           => $product->get_name(),
            'slug'            => $slug,
            'approved_synonyms' => array_values(array_unique(array_filter($approved_synonyms))),
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
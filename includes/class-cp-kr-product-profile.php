<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CP_KR_Product_Profile {

    private const DEFAULT_SYNONYMS = [
        'bubble-envelopes' => [
            'bubble mailers', 'padded envelopes', 'padded mailing envelopes', 'padded mailers',
            'bubble bags', 'padded bags', 'bubble packaging', 'packaging bubble', 'packaging envelopes',
            'shipping envelopes', 'packing envelopes', 'packaging mailers', 'bubble envelopes for shipping',
            'padded envelopes for shipping', 'mailing bags',
        ],
        'thermal-label-rolls' => ['thermal labels', 'thermal printer labels', 'label rolls'],
        'arofol-bubble-envelopes' => [
            'arofol bubble envelopes', 'arofol padded envelopes', 'arofol mailers', 'bubble mailers',
            'padded envelopes', 'padded mailing envelopes', 'padded mailers', 'bubble bags', 'padded bags',
            'bubble packaging', 'packaging bubble', 'packaging envelopes', 'shipping envelopes',
            'packing envelopes', 'packaging mailers', 'bubble envelopes for shipping',
            'padded envelopes for shipping', 'mailing bags',
        ],
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

    private const DEFAULT_FIXED_SEEDS = [
        'bubble-envelopes' => [
            'bubble envelopes', 'padded envelopes', 'bubble mailers', 'padded mailers', 'bubble lined envelopes',
            'jiffy bags', 'jiffy envelopes', 'postal envelopes', 'padded postal bags', 'bubble bags',
            'arofol envelopes', 'arofol bubble envelopes', 'arofol padded envelopes',
            '100 x 165 bubble envelopes', '120 x 215 padded envelopes', '180 x 265 bubble mailers',
            '220 x 340 padded envelopes', '350 x 470 bubble envelopes',
            'white padded envelopes', 'gold bubble envelopes', 'gold padded mailers',
            'buy bubble envelopes uk', 'bulk bubble envelopes wholesale uk', 'bulk padded envelopes', 'wholesale bubble mailers', 'cheap padded envelopes',
            'buy padded mailers online uk', 'bubble envelope supplier uk', 'trade padded envelopes',
            'jiffy bags bulk buy', 'jiffy bags wholesale uk', 'padded envelopes next day delivery',
        ],
        'thermal-label-rolls' => [
            'thermal label rolls', 'direct thermal labels', '4x6 thermal labels', 'shipping label rolls',
            'royal mail thermal labels', 'parcelforce label rolls', 'dpd shipping labels', 'evri label rolls',
            '4x6 labels 500 per roll', '100 x 150mm thermal labels', '4 x 6 shipping labels uk',
            'zebra thermal labels', 'munbyn labels', 'rollo labels', 'dymo 4xl labels',
            'buy thermal label rolls uk', 'bulk thermal labels', 'cheapest 4x6 labels', 'thermal labels next day delivery',
            'waterproof shipping labels', 'smudge free thermal labels', 'bpa free thermal labels',
            '4 x 6 thermal labels bulk buy', 'thermal label rolls wholesale uk', 'direct thermal labels trade supplier',
            'shipping labels 100 x 150 mm bulk', 'buy 500 labels per roll',
        ],
        'arofol-bubble-envelopes' => [
            'arofol envelopes', 'arofol bubble envelopes', 'arofol padded envelopes', 'arofol mailers',
            'arofol 1/a envelopes', 'arofol 10/k bubble envelopes', 'arofol 350 x 470 envelopes',
            'jiffy bag jl2 equivalent', 'mail lite e2 equivalent', 'featherpost e/2',
            'padded envelopes', 'bubble mailers', 'gold padded envelopes', 'white bubble envelopes',
            'buy arofol envelopes uk', 'arofol envelopes bulk', 'arofol wholesale',
            '100 x 165 arofol envelopes', '120 x 215 arofol envelopes', '150 x 215 arofol envelopes',
            '180 x 265 arofol envelopes', '220 x 265 arofol envelopes', '220 x 340 arofol envelopes',
            '230 x 340 arofol envelopes', '270 x 360 arofol envelopes', '300 x 445 arofol envelopes', '350 x 470 arofol envelopes',
            'buy arofol bubble mailers online', 'arofol mailers bulk buy uk', 'arofol envelope trade supplier',
            'arofol 1/a padded envelopes buy', 'arofol 10/k envelopes wholesale',
        ],
        'small-bubble-wrap' => [
            'small bubble wrap', 'small bubble wrap rolls', 'bubble wrap roll', 'small bubble packaging',
            '9.5mm bubble wrap', '10mm bubble wrap', 'small bubble vs large bubble',
            '300mm bubble wrap', '500mm bubble wrap roll', '750mm bubble wrap', '1000mm bubble wrap roll', '500mm x 100m bubble wrap',
            'bubble wrap for glassware', 'bubble wrap for electronics', 'bubble wrap for moving house', 'bubble wrap for shipping',
            'buy bubble wrap uk', 'bulk bubble wrap rolls', 'cheap bubble wrap', 'bubble wrap next day delivery',
            'recycled bubble wrap', 'eco friendly bubble wrap', 'biodegradable bubble wrap',
            'small bubble wrap bulk buy uk', 'small bubble wrap roll supplier', '500 mm x 100 m bubble wrap roll buy',
            'bubble wrap rolls wholesale uk', 'bubble wrap trade supplier',
        ],
        'mailing-bags' => [
            'grey mailing bags', 'mailing bags', 'postage bags', 'postal bags', 'poly mailers',
            'poly postal bags', 'courier bags', 'ecommerce mailers', 'parcel bags', 'shipping bags',
            '10 x 14 mailing bags', '12 x 16 mailing bags', '17 x 24 mailing bags', '24 x 36 mailing bags', 'large parcel mailing bags',
            'buy mailing bags uk', 'bulk mailing bags', 'cheap postage bags', 'wholesale poly mailers',
            'recycled mailing bags', '100% recyclable postage bags', 'eco friendly mailing bags',
            'blue mailing bags', 'pink mailing bags', 'white mailing bags',
            'grey poly mailers bulk buy uk', 'grey courier bags wholesale', 'buy grey mailing bags online',
            'mailing bags trade supplier uk', 'large letter mailing bags bulk',
        ],
        'clear-bubble-bags' => [
            'clear bubble bags', 'bubble pouches', 'clear bubble pouches', 'bubble wrap bags',
            'bubble bags self seal', 'peel and seal bubble bags', 'protective pouches', 'clear bubble mailers',
            '100 x 135 bubble bags', '180 x 235 bubble pouches', '230 x 285 bubble bags', '380 x 435 bubble pouches',
            'bubble bags for electronics', 'bubble pouches for books', 'bubble bags for jewellery', 'bubble bags for mugs',
            'buy bubble pouches uk', 'bulk clear bubble bags', 'cheap bubble bags', 'wholesale bubble pouches',
            'self seal bubble bags', '30mm lip bubble pouches', 'air retention bubble bags',
            'clear bubble bags bulk buy uk', 'bubble pouches trade supplier', 'self seal bubble pouches wholesale',
            'BP1 bubble bags buy', 'BP7 bubble bags wholesale',
        ],
        'large-bubble-wrap' => [
            'large bubble wrap', 'heavy duty bubble wrap', 'large bubble wrap rolls', '22mm bubble wrap',
            'industrial bubble wrap', 'heavy duty bubble wrap uk', 'large cell bubble wrap', 'extra large bubble wrap',
            '500mm large bubble wrap', '750mm heavy duty bubble wrap', '1000mm bubble wrap roll',
            'bubble wrap for furniture', 'bubble wrap for appliances', 'bubble wrap for moving house', 'bubble wrap for mirrors',
            'buy large bubble wrap uk', 'bulk heavy duty bubble wrap', 'wholesale bubble wrap rolls',
            'large vs small bubble wrap', '22mm vs 9.5mm bubble wrap',
            'large cell bubble wrap bulk buy uk', '22 mm bubble wrap roll supplier', 'heavy duty bubble wrap trade supplier',
            'large bubble wrap rolls wholesale', 'buy 1000 mm bubble wrap roll',
        ],
        '48mm-2-inch-packaging-tape' => [
            'packaging tape', 'parcel tape', 'packing tape 48mm', 'clear packing tape', 'brown parcel tape',
            '48mm x 66m tape', '2 inch packing tape', '48mm tape uk',
            'fragile tape', 'fragile packaging tape', 'printed fragile tape', 'red fragile tape',
            'buy packing tape uk', 'bulk parcel tape', 'cheap packing tape', 'wholesale packaging tape',
            'box sealing tape', 'carton sealing tape', 'packing tape for boxes', 'tape for moving house',
            'strong packing tape', 'heavy duty parcel tape', 'polypropylene packing tape',
            '48 mm x 66 m packing tape bulk buy', 'packing tape trade supplier uk', 'carton sealing tape wholesale',
            'brown parcel tape case buy', 'clear packaging tape bulk order',
        ],
        'hand-pallet-wrap' => [
            'hand pallet wrap', 'stretch film', 'pallet wrap', 'hand stretch wrap',
            'stretch wrap', 'pallet wrapping film', 'hand pallet wrap rolls', 'extended core pallet wrap',
            '17mu pallet wrap', '20mu stretch film', '400mm pallet wrap', '300m pallet wrap', 'extended core stretch film',
            'black pallet wrap', 'clear stretch film', 'black stretch wrap',
            'pallet wrap for warehouses', 'stretch film for pallets', 'load stability film', 'pallet wrap for removals',
            'buy pallet wrap uk', 'bulk stretch film', 'wholesale pallet wrap', 'cheap hand pallet wrap',
            'pallet wrap that does not tear', 'heavy duty stretch film', 'pallet wrap for sharp loads',
            'hand pallet wrap bulk buy uk', 'extended core pallet wrap supplier', '17 micron hand stretch film buy',
            '20 micron pallet wrap wholesale', '400 mm x 300 m pallet wrap buy',
        ],
        'cardboard-boxes' => [
            'single wall cardboard boxes', 'cardboard boxes', 'shipping boxes', 'postal boxes',
            'single wall boxes', 'corrugated boxes', 'mailing boxes', 'packing boxes',
            '12 x 9 x 4 box', '12 x 9 x 6 box', '12 x 9 x 9 box', '12 x 9 cardboard box',
            'cardboard boxes for shipping', 'boxes for small business', 'ecommerce packaging boxes', 'storage boxes',
            'buy cardboard boxes uk', 'bulk shipping boxes', 'cheap cardboard boxes', 'wholesale boxes',
            'flat packed boxes', 'recyclable cardboard boxes', 'b flute boxes', 'single wall vs double wall',
            'single wall boxes bulk buy uk', 'cardboard box supplier uk', '12 x 9 x 4 cardboard box buy',
            'shipping boxes trade supplier', 'single wall cardboard boxes wholesale',
        ],
        'number-plate-envelopes' => [
            'number plate envelopes', 'number plate mailers', 'licence plate envelopes', 'number plate bubble envelopes',
            '180 x 535 number plate envelope', '535 x 180 padded envelope', 'long number plate envelope',
            'bubble lined number plate envelope', 'peel and seal number plate envelope', 'FSC number plate envelope', 'plastic free number plate mailer',
            'number plate posting envelope', 'number plate shipping envelope', 'mailing number plates uk',
            'buy number plate envelopes uk', 'bulk number plate mailers', 'number plate envelopes 100 pack',
            'number plate envelope supplier uk', '180 x 535 number plate mailers bulk', 'number plate postage envelopes wholesale',
            'buy padded number plate envelopes online',
        ],
        'anti-static-bubble-bags' => [
            'anti static bubble bags', 'anti static bags', 'ESD bubble bags', 'pink anti static bags',
            'ESD packaging', 'anti static bubble pouches', 'static shielding bags', 'dissipative bubble bags',
            '100 x 135 anti static bags', '180 x 235 ESD bags', '230 x 285 anti static bubble bags',
            'anti static bags for electronics', 'ESD bags for circuit boards', 'anti static packaging for hard drives', 'pink bubble bags for computer parts',
            'buy anti static bags uk', 'bulk ESD bubble bags', 'anti static bubble bags wholesale',
            'self seal anti static bags', '30mm lip ESD bags', 'anti static bubble wrap bags',
            'ESD bubble bags bulk buy uk', 'anti static packaging supplier uk', 'buy pink anti static bags wholesale',
            'anti static bags trade supplier', 'static shielding bubble bags bulk',
        ],
        'plain-grip-seal-bags' => [
            'grip seal bags', 'resealable bags', 'zip lock bags', 'grip seal bags uk',
            'ziplock bags', 'minigrip bags', 'resealable poly bags', 'baggies', 'press seal bags',
            '4 x 5.5 grip seal bags', '6 x 9 grip seal bags', '10 x 14 grip seal bags', '12.8 x 12.8 grip seal bags',
            'grip seal bags for food', 'grip seal bags for jewellery', 'grip seal bags for screws', 'grip seal bags for crafts',
            'buy grip seal bags uk', 'bulk grip seal bags', 'cheap resealable bags', 'wholesale ziplock bags',
            '45 micron grip seal bags', '180 gauge grip seal bags', '30% recycled grip seal bags', 'clear grip seal bags',
            'grip seal bags bulk buy uk', 'resealable bags trade supplier', 'clear zip lock bags wholesale',
            'buy grip seal bags online bulk', 'minigrip bags supplier uk',
        ],
        'heavy-duty-125-micron-500-gauge' => [
            'layflat tubing', 'polythene tubing', 'layflat polythene tubing', 'heavy duty layflat tubing',
            '500 gauge layflat tubing', '125 micron polythene tubing', '500 gauge tubing 165m', 'heavy duty poly tubing',
            '2 inch layflat tubing', '4 inch layflat tubing', '6 inch polythene tubing', '12 inch layflat tubing',
            'layflat tubing for carpets', 'tubing for rugs', 'heavy duty tubing for machine parts', 'polythene tubing for long items',
            'buy layflat tubing uk', 'bulk polythene tubing', 'heavy duty layflat tubing wholesale',
            '500 gauge vs 250 gauge layflat tubing', 'heavy duty vs medium duty polythene tubing',
            '500 gauge layflat tubing bulk buy uk', '125 micron layflat tubing supplier', '165 m layflat tubing roll buy',
            'heavy duty polythene tubing trade supplier', 'layflat tubing wholesale uk',
        ],
        'medium-duty-62-5-micron-250-gauge' => [
            'layflat tubing 250 gauge', 'medium duty layflat tubing', 'polythene tubing 250 gauge', '62.5 micron tubing',
            '2 inch 250 gauge tubing', '4 inch layflat tubing 330m', '6 inch polythene tubing 250 gauge', '12 inch medium duty tubing',
            'layflat tubing for clothing', 'tubing for posters', 'tubing for stationery', 'medium duty poly tubing for linens',
            'buy 250 gauge layflat tubing', 'bulk medium duty polythene tubing', 'cheap layflat tubing uk',
            '250 gauge vs 500 gauge', 'medium duty vs heavy duty layflat tubing',
            '250 gauge layflat tubing bulk buy uk', '62.5 micron layflat tubing supplier', '330 m layflat tubing roll buy',
            'medium duty polythene tubing trade supplier', '250 gauge tubing wholesale uk',
        ],
        'loose-fill-packing-peanuts' => [
            'packing peanuts', 'biodegradable packing peanuts', 'loose fill packing peanuts', 'void fill peanuts',
            'compostable packing peanuts', 'starch packing peanuts', 'eco friendly loose fill', 'polystyrene free packing peanuts', 'EN13432 packing peanuts',
            'ecoflo packing peanuts', 'ecoflo loose fill', 'ecoflo void fill',
            '5 cubic feet packing peanuts', '7.5 cubic feet loose fill', '15 cubic feet packing peanuts',
            'void fill for boxes', 'packing peanuts for fragile items', 'loose fill for shipping', 'eco void fill for ecommerce',
            'buy biodegradable packing peanuts uk', 'bulk loose fill peanuts', 'cheap eco packing peanuts',
            'EcoFlo packing peanuts bulk buy', 'biodegradable loose fill supplier uk', 'compostable packing peanuts wholesale',
            'EcoFlo loose fill trade supplier', 'packing peanuts 15 cubic feet buy',
        ],
        'blue-mailing-bags' => [
            'blue mailing bags', 'coloured mailing bags', 'blue postage bags', 'blue poly mailers',
            'metallic blue mailing bags', 'baby blue mailing bags', 'dark blue mailing bags',
            '6 x 9 blue mailing bags', '10 x 14 blue mailing bags', '12 x 16 blue mailing bags',
            'blue mailing bags for clothes', 'coloured postage bags for ecommerce', 'branded mailing bags blue',
            'buy blue mailing bags uk', 'bulk blue postage bags', 'wholesale coloured mailers',
            'recycled blue mailing bags', 'recyclable blue postage bags',
            'blue poly mailers bulk buy uk', 'blue mailing bags trade supplier', 'blue postage bags wholesale',
            'buy blue courier bags online', 'blue mailing bags large letter bulk',
        ],
        'pink-mailing-bags' => [
            'pink mailing bags', 'pink postage bags', 'pink poly mailers', 'pink postal bags',
            'hot pink mailing bags', 'baby pink mailing bags', 'vibrant pink postage bags',
            '6 x 9 pink mailing bags', '10 x 14 pink mailing bags', '12 x 16 pink mailing bags',
            'pink mailing bags for clothes', 'pink packaging bags for boutiques', 'pink mailers for gifts', 'pink postage bags for beauty products',
            'buy pink mailing bags uk', 'bulk pink postage bags', 'wholesale pink mailers',
            'recycled pink mailing bags', 'recyclable pink postage bags',
            'pink poly mailers bulk buy uk', 'pink mailing bags trade supplier', 'pink postage bags wholesale',
            'buy pink courier bags online', 'pink mailing bags large letter bulk',
        ],
    ];

    private const DEFAULT_VARIATION_SEEDS = [
        'bubble-envelopes' => [
            'Arofol 1/A White 100 x 165 mm', 'Arofol 1/A Gold 100 x 165 mm',
            'Arofol 2/B White 120 x 215 mm', 'Arofol 2/B Gold 120 x 215 mm',
            'Plain 3/C White 150 x 215 mm', 'Plain 3/C Gold 150 x 215 mm',
            'Plain 4/D White 180 x 265 mm', 'Plain 4/D Gold 180 x 265 mm',
            'Plain 5/E White 220 x 265 mm', 'Plain 5/E Gold 220 x 265 mm',
            'Plain 6/F White 220 x 340 mm', 'Plain 6/F Gold 220 x 340 mm',
            'Plain 7/G White 230 x 340 mm', 'Plain 7/G Gold 230 x 340 mm',
            'Plain 8/H White 270 x 360 mm', 'Plain 8/H Gold 270 x 360 mm',
            'Plain 9/I White 300 x 445 mm', 'Plain 9/I Gold 300 x 445 mm',
            'Plain 10/K White 350 x 470 mm', 'Plain 10/K Gold 350 x 470 mm',
        ],
        'thermal-label-rolls' => ['500 labels per roll 4 x 6 inch 100 x 150 mm 25 mm core 12 rolls per box'],
        'arofol-bubble-envelopes' => [
            '1/A White 100 x 165 mm', '1/A Gold 100 x 165 mm',
            '2/B White 120 x 215 mm', '2/B Gold 120 x 215 mm',
            '3/C White 150 x 215 mm', '3/C Gold 150 x 215 mm',
            '4/D White 180 x 265 mm', '4/D Gold 180 x 265 mm',
            '5/E White 220 x 265 mm', '5/E Gold 220 x 265 mm',
            '6/F White 220 x 340 mm', '6/F Gold 220 x 340 mm',
            '7/G White 230 x 340 mm', '7/G Gold 230 x 340 mm',
            '8/H White 270 x 360 mm', '8/H Gold 270 x 360 mm',
            '9/I White 300 x 445 mm', '9/I Gold 300 x 445 mm',
            '10/K White 350 x 470 mm', '10/K Gold 350 x 470 mm',
        ],
        'small-bubble-wrap' => [
            '300 mm x 10 m', '500 mm x 10 m', '750 mm x 10 m',
            '300 mm x 50 m', '500 mm x 50 m', '750 mm x 50 m',
            '300 mm x 75 m', '500 mm x 75 m', '750 mm x 75 m',
            '300 mm x 100 m', '500 mm x 100 m', '750 mm x 100 m', '1000 mm x 100 m',
        ],
        'mailing-bags' => [
            'Letter 5 x 7 inch', 'Large letter 6 x 9 inch', 'Large letter 9 x 12 inch',
            'Large letter 10 x 12 inch', 'Large letter 10 x 14 inch',
            'Small parcel 12 x 16 inch', 'Small parcel 12.5 x 17 inch', 'Small parcel 13 x 19 inch',
            'Small parcel 14 x 19 inch', 'Small parcel 14 x 16 inch', 'Small parcel 14 x 21 inch', 'Small parcel 16 x 21 inch',
            'Medium parcel 17 x 24 inch', 'Medium parcel 21 x 24 inch',
            'Large parcel 22 x 30 inch', 'Large parcel 24 x 36 inch', 'Large parcel 28 x 34 inch', 'Large parcel 34 x 42 inch',
        ],
        'clear-bubble-bags' => [
            'BP1 100 x 135 mm', 'BP2 130 x 185 mm', 'BP3 180 x 235 mm', 'BP4 230 x 285 mm',
            'BP5 280 x 360 mm', 'BP6 305 x 425 mm', 'BP7 380 x 435 mm',
        ],
        'large-bubble-wrap' => ['300 mm x 50 m', '500 mm x 50 m', '750 mm x 50 m', '1000 mm x 50 m'],
        '48mm-2-inch-packaging-tape' => [
            '48 mm x 66 m Brown', '48 mm x 66 m Clear', '48 mm x 66 m Fragile red on white',
        ],
        'hand-pallet-wrap' => [
            '17mu cast Clear Medium duty extended core 400 mm x 300 m',
            '20mu cast Black Heavy duty extended core 400 mm x 300 m',
            '20mu cast Clear Heavy duty extended core 400 mm x 300 m',
        ],
        'cardboard-boxes' => ['12 x 9 x 4 inch internal size', '12 x 9 x 6 inch internal size', '12 x 9 x 9 inch internal size'],
        'number-plate-envelopes' => ['Number plate envelope 180 x 535 mm Brown 100 per pack'],
        'anti-static-bubble-bags' => [
            '100 x 135 mm', '130 x 185 mm', '180 x 235 mm', '230 x 285 mm', '280 x 360 mm', '305 x 425 mm', '380 x 435 mm',
        ],
        'plain-grip-seal-bags' => [
            '1.5 x 2.5 inch', '2.3 x 2.3 inch', '2.3 x 3 inch', '3 x 3.3 inch', '3 x 7.5 inch',
            '3.5 x 4.5 inch', '4 x 5.5 inch', '4.5 x 4.5 inch', '5 x 7.5 inch', '5.5 x 5.5 inch',
            '6 x 9 inch', '7.5 x 7.5 inch', '8 x 11.3 inch', '9 x 12.8 inch', '10 x 14 inch',
            '11 x 16 inch', '12.8 x 12.8 inch', '13 x 18 inch', '15 x 20 inch',
        ],
        'heavy-duty-125-micron-500-gauge' => [
            '2 inch 51 mm x 165 m', '3 inch 75 mm x 165 m', '4 inch 102 mm x 165 m',
            '5 inch 127 mm x 165 m', '6 inch 152 mm x 165 m', '8 inch 203 mm x 165 m',
            '10 inch 254 mm x 165 m', '12 inch 305 mm x 165 m',
        ],
        'medium-duty-62-5-micron-250-gauge' => [
            '2 inch 51 mm x 330 m', '3 inch 75 mm x 330 m', '4 inch 102 mm x 330 m',
            '6 inch 152 mm x 330 m', '8 inch 203 mm x 330 m', '10 inch 254 mm x 330 m', '12 inch 305 mm x 330 m',
        ],
        'loose-fill-packing-peanuts' => ['EcoFlo 5 cubic feet bag', 'EcoFlo 7.5 cubic feet bag', 'EcoFlo 15 cubic feet bag'],
        'blue-mailing-bags' => [
            'Large letter 6 x 9 inch', 'Large letter 9 x 12 inch', 'Large letter 10 x 14 inch', 'Small parcel 12 x 16 inch',
        ],
        'pink-mailing-bags' => [
            'Large letter 6 x 9 inch', 'Large letter 9 x 12 inch', 'Large letter 10 x 14 inch', 'Small parcel 12 x 16 inch',
        ],
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
        $configured_seeds = (array)get_option('cp_kr_product_fixed_seeds', []);
        $fixed_seed_phrases = array_merge(
            self::DEFAULT_FIXED_SEEDS[$slug] ?? [],
            array_map(
                static fn($variation) => trim($product->get_name() . ' ' . $variation),
                self::DEFAULT_VARIATION_SEEDS[$slug] ?? []
            ),
            (array)($configured_seeds[$product->get_id()] ?? []),
            (array)($configured_seeds[$slug] ?? [])
        );

        $profile = array(
            'id'              => $product->get_id(),
            'title'           => $product->get_name(),
            'slug'            => $slug,
            'approved_synonyms' => array_values(array_unique(array_filter($approved_synonyms))),
            'fixed_seed_phrases' => array_values(array_unique(array_filter($fixed_seed_phrases))),
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
                if ( ! $variation ) continue;
                $attrs = array();
                foreach ( $variation->get_attributes() as $key => $value ) {
                    if ( '' !== $value ) {
                        $attrs[ $key ] = $value;
                    }
                }
                $variation_values = [];
                foreach ($attrs as $key => $value) {
                    $variation_values[] = self::variation_attribute_value($key, $value);
                }
                $profile['variations'][] = array(
                    'id'         => $variation_id,
                    'name'       => $variation->get_name(),
                    'attributes' => $attrs,
                    'seed_phrase' => trim($product->get_name() . ' ' . implode(' ', array_filter($variation_values))),
                    'sku'        => $variation->get_sku(),
                    'price'      => $variation->get_price(),
                );
            }
        }

        $profile['size_terms'] = self::get_size_terms($profile);

        return $profile;
    }

    private static function get_size_terms($profile) {
        $terms = [];

        foreach (($profile['attributes'] ?? []) as $name => $values) {
            if (!self::is_size_attribute($name)) continue;
            foreach ((array)$values as $value) {
                $terms[] = trim((string)$value);
            }
        }

        foreach (($profile['variations'] ?? []) as $variation) {
            foreach ((array)($variation['attributes'] ?? []) as $name => $value) {
                if (!self::is_size_attribute($name)) continue;
                $terms[] = self::variation_attribute_value($name, $value);
            }
        }

        $description = implode(' ', [
            (string)($profile['title'] ?? ''),
            (string)($profile['short_desc'] ?? ''),
            (string)($profile['full_desc'] ?? ''),
        ]);
        preg_match_all('/\b\d+(?:[.,]\d+)?\s*(?:x|×)\s*\d+(?:[.,]\d+)?(?:\s*(?:x|×)\s*\d+(?:[.,]\d+)?)?\s*(?:mm|cm|m|inches?|inch|in)\b/i', $description, $dimensions);
        preg_match_all('/\b\d+(?:[.,]\d+)?\s*(?:mm|cm|metres?|meters?|inches?|inch|in|microns?|micron|gauge|gsm)\b/i', $description, $measurements);
        preg_match_all('/\b(?:pack\s+of\s+\d+|\d+\s*(?:pack|pk|rolls?|labels?|bags?|boxes|envelopes|sheets|pieces|pcs))\b/i', $description, $quantities);

        $terms = array_merge($terms, $dimensions[0] ?? [], $measurements[0] ?? [], $quantities[0] ?? []);
        $terms = array_map(static function ($term) {
            return trim(preg_replace('/\s+/', ' ', str_replace(['×'], 'x', (string)$term)));
        }, $terms);

        return array_values(array_unique(array_filter($terms)));
    }

    private static function is_size_attribute($name) {
        return (bool)preg_match('/size|dimension|width|length|height|depth|diameter|gauge|micron|thickness|quantity|pack/i', (string)$name);
    }

    private static function variation_attribute_value($name, $value) {
        $taxonomy = preg_replace('/^attribute_/', '', (string)$name);
        if (str_starts_with($taxonomy, 'pa_')) {
            $term = get_term_by('slug', (string)$value, $taxonomy);
            if ($term && !is_wp_error($term)) return $term->name;
        }

        return trim(str_replace(['-', '_'], ' ', (string)$value));
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
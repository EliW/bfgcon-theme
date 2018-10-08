<?php
/**
 * ofa_secure_mail
 *
 * A function which uses built-in wordpress features, to generate a non-spam-able email.
 *
 * @param string $email Email that you wish to link to
 * @param string $text Name you want for the link
 * @return string
 * @author Eli White <eli@eliw.com>
 */
function ofa_secure_mail($email, $text) {
    $mailto = antispambot('mailto:' . $email);
    $txt = antispambot($text);
    return '<a href="' . $mailto . '">' . $txt . '</a>';
}

/**
 * ofa_secure_mail_shortcode
 *
 * Provide a short-code wrapper for above function
 *
 * @param string $atts
 * @return string
 * @author Eli White <eli@eliw.com>
 */
function ofa_secure_mail_shortcode($atts) {
    extract(shortcode_atts(array("email" => '', "text" => ''), $atts));
    return ofa_secure_mail($email, $text);
}
if (function_exists('add_shortcode')) { add_shortcode('antispam', 'ofa_secure_mail_shortcode'); }

/** Ticket Sale Shit **/

function clean_promotional_affiliate_ref($code) {
    return substr(preg_replace("/[^[:alnum:]_]/", '', trim(strip_tags($code))), 0, 14);
}

// On every request, see if a ref was set, if so, save it as a cookie for 1 week
if (!empty($_GET['paref'])) {
    $code = clean_promotional_affiliate_ref($_GET['paref']);
    setcookie('paref', $code, time()+14*60*60*24, COOKIEPATH, COOKIE_DOMAIN, true, true);
}


/* Some sizes we want */
add_image_size('publisherlogo', 300, 75, false);
add_image_size('gamelogo', 200, 200, false);
add_filter( 'image_size_names_choose', function($sizes) {
        return array_merge( $sizes, array(
            'publisherlogo' => __( 'Publisher Logo' ),
            'gamelogo' => __( 'Game Logo' ),
        ) );
    });
add_filter( 'jpeg_quality', function () { return 70; } );

// force load our CSS so that it can be timestamp based:
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'mondree-style', get_theme_file_uri() . '/style.css', [], filemtime(get_theme_file_path() . '/style.css'));
});

/**
 * OK:  This whole section is going to be semi-hacks to make Camptix do 'what I want' in the new
 * system.  I may turn all this into separate plugins in the future.  But it really seems to be
 * simpler to just 'do this' for now.
 **/

// Return the original post content, we don't want to modify it:
add_filter( 'camptix_post_content_override', function($shortcode, $content, $action) {
    return $content;
}, 10, 3);

// Add in that we want shipping info on paypal:
add_filter( 'camptix_paypal_payload', function($payload) {
    $payload['NOSHIPPING'] = 2;
    return $payload;
});

// Let's hook up a camptixJS that will modify what it does (for Stripe & more!)
add_action( 'wp_enqueue_scripts', function() {
    wp_register_script( 'ofa-camptix-tweaks', get_theme_file_uri() . '/camptix-tweaks.js',
        ['camptix'/*, 'stripe-checkout'*/], false, true );
    wp_enqueue_script( 'ofa-camptix-tweaks' );
});

// Make Buttons:
//  NOTE:  The default one of these is: add_filter( 'tix_render_payment_options', 'generate_payment_options', 10, 4 );
// I should really probably push this one OUT so it doesn't have to run.  But I'd have to do some funny stuff
// to make it happen at the right time after it's been added.  So meh.
function ofa_camptix_payment_buttons ( $payment_output, $total, $payment_methods, $selected_payment_method ) {
    // Since we aren't blowing away the original system anyway, let's use it.  Only modify the output here if
    // we have a total > 0 ... otherwise the default solution works fine for us:
    if ( $total > 0 ) {
        $output = '';
        foreach ( $payment_methods as $payment_method_key => $payment_method ) {
            $key = esc_attr( $payment_method_key );
            $checkout = esc_attr__( 'Checkout with', 'camptix' );
            $method = esc_html( $payment_method['name'] );
            $output .= <<<EOHTML
            <button id="tix-pm-{$key}" type="submit" name="tix_payment_method" value="{$key}">
                {$checkout} {$method}
            </button>
EOHTML;
        }
        return '<p class="tix-submit">'.$output.'<br class="tix-clear" /></p>';
    }

    // Else just return the original:
    return $payment_output;
}
// Add it at '11' so it runs AFTER the initial one, and just blows away everything #sad
add_filter( 'tix_render_payment_options', 'ofa_camptix_payment_buttons', 11, 4 );

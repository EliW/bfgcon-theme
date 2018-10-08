// We need to overwrite some parts of CampTixStripe to work the way we want:
// A bit 'overkill', but we are completely changing the checkout function in order to
//  change a few of the payload bits.
CampTixStripe.stripe_checkout = function() {
	// Close the popup if they hit back.
	window.addEventListener('popstate', function() {
		window.StripeHandler.close();
	});

	window.StripeHandler.open();
};

// Fixing a 'bug'.  The stripe plugin attaches itself to EVERY tix form, not just the checkout one.
CampTixStripe.init = function() {
    // Could look for this or CampTixStripeData...
    if (typeof StripeCheckout !== 'undefined') {
    	CampTixStripe.form = jQuery( '#tix form' );
    	CampTixStripe.form.on( 'submit', CampTixStripe.form_handler );

    	// On a failed attendee data request, we'll have the previous stripe token
    	if ( CampTixStripe.data.token ) {
    		CampTixStripe.add_stripe_token_hidden_fields( CampTixStripe.data.token, CampTixStripe.data.receipt_email || '' );
    	}
    }
};

// Now let's setup the JS needed to hook up our 'buttons' for checkout, instead of the select we had before:
window.CampTixUtilities.getSelectedPaymentOption = function() {
    // Hardcoded for our needs, default to stripe (if someone doesn't click a button but hits enter, the 'activeElement')
    //  won't be one of our buttons, so we need a default:
    return (document.activeElement.id == 'tix-pm-paypal') ? 'paypal' : 'stripe';
};

jQuery(document).ready( function($) {
    // only do this if we find the checkout form .... probably better to try to only inject this code
    //  when it even is needed, but this is easier at the moment ...
    if (typeof StripeCheckout !== 'undefined') {
        // Hopefully this runs AFTER the CampTix.init():
        var StripeHandler = StripeCheckout.configure({
            key: CampTixStripeData.public_key,
            image: CampTixStripeData.image,
            locale: 'auto',
            amount: parseInt( CampTixStripeData.amount ),
            currency: CampTixStripeData.currency,
            description: CampTixStripeData.description,
            name: CampTixStripeData.name,
            zipCode: true,
            billingAddress: true,
            email: '',
            token: CampTixStripe.stripe_token_callback
        });
        window.StripeHandler = StripeHandler;
    }
});

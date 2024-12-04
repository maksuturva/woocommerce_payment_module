(function ($) {
    function init() {
        $(document).on('found_variation', variationChanged);
        $(document).on('reset_data', variationsReset);
    }

    function variationChanged(event, variation) {
        updatePartPaymentWidget(variation.display_price);
    }

    function variationsReset() {
        updatePartPaymentWidget(0)
        $('#svea-pp-widget-mini').hide();
    }

    function updatePartPaymentWidget(price) {
        const formattedPrice = parseFloat(price).toFixed(2);

        document.dispatchEvent(
            new CustomEvent(
                "svea-partpayment-calculator-update-price",
                {"detail": {"price": formattedPrice}}
            )
        );
    }

    init();
})(jQuery);
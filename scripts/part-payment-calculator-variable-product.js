(function ($, settings) {
    function init() {
        $(document).on('found_variation', variationChanged);
        $(document).on('reset_data', variationsReset);
    }

    function variationChanged(event, variation) {
        const price = variation.display_price;

        updatePartPaymentWidget(price);
        toggleWidgets(price);
    }

    function variationsReset() {
        updatePartPaymentWidget(0);
        hideWidgets();
    }

    function updatePartPaymentWidget(price) {
        const formattedPrice = parseFloat(price).toFixed(2);

        console.log('updatePartPaymentWidget', formattedPrice);

        document.dispatchEvent(
            new CustomEvent(
                "svea-partpayment-calculator-update-price",
                {"detail": {"price": formattedPrice}}
            )
        );
    }

    function toggleWidgets(price) {
        if (settings.minThreshold !== null) {
            if (price < parseFloat(settings.minThreshold)) {
                hideWidgets();
            } else {
                showWidgets();
            }
            return
        }

        if (settings.plansMin === null || settings.plansMax === null) {
            return;
        }

        if (price >= settings.plansMin && price <= settings.plansMax) {
            showWidgets();
        } else {
            hideWidgets();
        }
    }

    function hideWidgets() {
        $('#svea-pp-widget-button').parent().hide();
        $('#svea-pp-widget-mini').parent().hide();
        $('#svea-pp-widget').parent().hide();
    }

    function showWidgets() {
        $('#svea-pp-widget-button').parent().show();
        $('#svea-pp-widget-mini').parent().show();
        $('#svea-pp-widget').parent().show();
    }

    init();
})(jQuery, window.svea_ppc_vp_params || {});
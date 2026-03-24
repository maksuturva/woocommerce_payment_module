(function () {
    const { subscribe, select } = wp.data;

    console.log('Svea: Checkout fee trigger loaded');

    // We need to keep track of the previous method to avoid infinite loops
    let previousPaymentMethod = '';
    const paymentStore = select('wc/store/payment');
    if (paymentStore) {
        previousPaymentMethod = paymentStore.getActivePaymentMethod();
    }

    // Subscribe to changes in the store
    const unsubscribe = subscribe(() => {
        const store = select('wc/store/payment');
        if (!store) return;

        const currentPaymentMethod = store.getActivePaymentMethod();

        // If the payment method has changed
        if (currentPaymentMethod !== previousPaymentMethod && currentPaymentMethod !== '') {
            previousPaymentMethod = currentPaymentMethod;

            console.log('Svea: Payment method changed to', currentPaymentMethod);

            // Trigger the server-side update
            if (window.wc && window.wc.blocksCheckout && window.wc.blocksCheckout.extensionCartUpdate) {
                window.wc.blocksCheckout.extensionCartUpdate({
                    namespace: 'svea_payments_update',
                    data: {
                        payment_method: currentPaymentMethod
                    }
                });
            }
        }
    });
})();

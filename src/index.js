import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { useState, useEffect } from '@wordpress/element';

const settings = getSetting('paymentMethodData', {}).WC_Gateway_Maksuturva || {};

const defaultLabel = decodeEntities(settings.title) || 'Svea';

const Content = (props) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;

    const [selectedSubMethod, setSelectedSubMethod] = useState(null);

    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            if (!selectedSubMethod) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: 'Please select a payment method.',
                };
            }

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        svea_payment_method: selectedSubMethod,
                    },
                },
            };
        });

        return () => {
            unsubscribe();
        };
    }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup, selectedSubMethod]);

    if (!settings.groups || settings.groups.length === 0) {
        return <div>{decodeEntities(settings.description || '')}</div>;
    }

    const getHandlingCost = (methodCode) => {
        if (!settings.handling_costs) return null;
        const cost = settings.handling_costs.find(c => c.payment_method_type === methodCode);
        return cost ? cost.handling_cost_amount : null;
    };

    const formatPrice = (price) => {
        return parseFloat(price).toFixed(2).replace('.', ',');
    };

    return (
        <div>
            <div className="svea-payment-methods-container">
                {settings.groups.map((group, groupIndex) => (
                    <fieldset key={groupIndex} style={{ border: 'none', margin: 0, padding: 0 }}>
                        <legend className="svea-payment-collated-title">{decodeEntities(group.title)}</legend>
                        <div className="svea-payment-methods-list" style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '10px' }}>
                            {group.methods.map((method) => {
                                const handlingCost = getHandlingCost(method.code);
                                const isSelected = selectedSubMethod === method.code;
                                return (
                                    <div
                                        key={method.code}
                                        className={`svea-payment-method-select ${isSelected ? 'selected' : ''}`}
                                        style={{
                                            cursor: 'pointer',
                                            border: isSelected ? '2px solid #007cba' : '1px solid #ddd',
                                            padding: '5px',
                                            borderRadius: '4px',
                                            backgroundColor: isSelected ? '#f0f0f1' : '#fff',
                                            transition: 'all 0.2s ease',
                                            opacity: isSelected ? 1 : 0.8,
                                            display: 'flex',
                                            flexDirection: 'column',
                                            justifyContent: 'center',
                                            alignItems: 'center',
                                            minHeight: '60px'
                                        }}
                                        onClick={() => setSelectedSubMethod(method.code)}
                                        onMouseEnter={(e) => {
                                            if (!isSelected) {
                                                e.currentTarget.style.borderColor = '#999';
                                                e.currentTarget.style.opacity = 1;
                                            }
                                        }}
                                        onMouseLeave={(e) => {
                                            if (!isSelected) {
                                                e.currentTarget.style.borderColor = '#ddd';
                                                e.currentTarget.style.opacity = 0.8;
                                            }
                                        }}
                                    >
                                        <label htmlFor={`svea_payment_method_${method.code}`} style={{ cursor: 'pointer', display: 'flex', justifyContent: 'center', alignItems: 'center', width: '100%', height: '100%', margin: 0 }}>
                                            {method.imageurl ? (
                                                <img
                                                    src={method.imageurl}
                                                    alt={method.displayname}
                                                    style={{ display: 'block', maxWidth: '100%', maxHeight: '90%' }}
                                                />
                                            ) : (
                                                <span style={{ display: 'block', textAlign: 'center', padding: '10px' }}>{decodeEntities(method.displayname)}</span>
                                            )}
                                        </label>
                                        {handlingCost && (
                                            <div className="handling-cost-amount" style={{ textAlign: 'center', fontSize: '0.9em', color: '#666', marginTop: '5px' }}>
                                                +{formatPrice(handlingCost)} {settings.currency_symbol}
                                            </div>
                                        )}
                                        <input
                                            className="input-radio svea-payment-method-select-radio"
                                            type="radio"
                                            id={`svea_payment_method_${method.code}`}
                                            name="svea_payment_method"
                                            value={method.code}
                                            checked={isSelected}
                                            onChange={() => setSelectedSubMethod(method.code)}
                                            style={{ display: 'none' }}
                                        />
                                    </div>
                                );
                            })}
                        </div>
                    </fieldset>
                ))}
            </div>
            {settings.terms && settings.terms.text && (
                <p>
                    {decodeEntities(settings.terms.text)}
                    {settings.terms.url && (
                        <> (<a href={settings.terms.url} target="_blank">PDF</a>)</>
                    )}
                </p>
            )}
            <div style={{ clear: 'both' }}></div>
        </div>
    );
};

const Label = (props) => {
    const { PaymentMethodLabel } = props.components;
    return <PaymentMethodLabel text={defaultLabel} />;
};

registerPaymentMethod({
    name: 'WC_Gateway_Maksuturva',
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: defaultLabel,
    supports: {
        features: settings.supports,
    },
});

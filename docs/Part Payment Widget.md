# Svea Part Payment Widget

This is a short guide how to enable Part Payment Widget on the product page.

To enable this feature, you need to edit `wc-maksuturva.php` file and add your information to the widget code.

## General information

The JavaScript based widget and it's parameters are documented here: https://cdn2.hubspot.net/hubfs/2478856/Payments/Materiaalipankki/Toimitusehdot%20ja%20maksutapakuvaukset/Svea_Part_Payment_Widget.pdf

## How to enable the widget

The widget is visible on the product page and below the current product price.  

* edit wc-maksuturva.php and find text PARTPAYMENTWIDGET. Uncomment this line. As follows:
````
    add_filter( 'woocommerce_get_price_html', [$this, 'svea_add_part_payment_widget'], 99, 2 );
````
* find the function named svea_add_part_payment_widget in wc-maksuturva.php
* edit widgetSellerId to match your production seller id value
* update other data-xxx fields if needed, description for fields: https://cdn2.hubspot.net/hubfs/2478856/Payments/Materiaalipankki/Toimitusehdot%20ja%20maksutapakuvaukset/Svea_Part_Payment_Widget.pdf
* pay attention to string escaping, don't forget to use `\"`

## How to disable the widget

* edit wc-maksuturva.php and find text PARTPAYMENTWIDGET. Comment or delete this line. As follows:
````
    // add_filter( 'woocommerce_get_price_html', [$this, 'svea_add_part_payment_widget'], 99, 2 );
````



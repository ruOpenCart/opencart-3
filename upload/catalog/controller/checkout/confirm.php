<?php
class ControllerCheckoutConfirm extends Controller {
    public function index(): void {
        $redirect = '';

        if ($this->cart->hasShipping()) {
            // Validate if shipping address has been set.
            if (!isset($this->session->data['shipping_address'])) {
                $redirect = $this->url->link('checkout/checkout', '', true);
            }

            // Validate if shipping method has been set.
            if (!isset($this->session->data['shipping_method'])) {
                $redirect = $this->url->link('checkout/checkout', '', true);
            }
        } else {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
        }

        // Validate if payment address has been set.
        if (!isset($this->session->data['payment_address'])) {
            $redirect = $this->url->link('checkout/checkout', '', true);
        }

        // Validate if payment method has been set.
        if (!isset($this->session->data['payment_method'])) {
            $redirect = $this->url->link('checkout/checkout', '', true);
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $redirect = $this->url->link('checkout/cart');
        }

        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $redirect = $this->url->link('checkout/cart');
                break;
            }
        }

        if (!$redirect) {
            $this->load->language('checkout/checkout');

            $order_data = [];

            $total = 0;
            $totals = [];
            $taxes = $this->cart->getTaxes();

            // Because __call cannot keep var references, so we put them into an array.
            $total_data = [
                'totals' => &$totals,
                'taxes'  => &$taxes,
                'total'  => &$total
            ];

            // Extensions
            $this->load->model('setting/extension');

            $sort_order = [];
            $results = $this->model_setting_extension->getExtensionsByType('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $sort_order = [];

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);

            $order_data['totals'] = $totals;
            $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
            $order_data['store_id'] = $this->config->get('config_store_id');
            $order_data['store_name'] = $this->config->get('config_name');

            if ($order_data['store_id']) {
                $order_data['store_url'] = $this->config->get('config_url');
            } else {
                if ($this->request->server['HTTPS']) {
                    $order_data['store_url'] = HTTPS_SERVER;
                } else {
                    $order_data['store_url'] = HTTP_SERVER;
                }
            }

            // Customers
            $this->load->model('account/customer');

            if ($this->customer->isLogged()) {
                $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

                $order_data['customer_id'] = $this->customer->getId();
                $order_data['customer_group_id'] = $customer_info['customer_group_id'];
                $order_data['firstname'] = $customer_info['firstname'];
                $order_data['lastname'] = $customer_info['lastname'];
                $order_data['email'] = $customer_info['email'];
                $order_data['telephone'] = $customer_info['telephone'];
                $order_data['custom_field'] = json_decode($customer_info['custom_field'], true);
            } elseif (isset($this->session->data['guest'])) {
                $order_data['customer_id'] = 0;
                $order_data['customer_group_id'] = (int)$this->session->data['guest']['customer_group_id'];
                $order_data['firstname'] = $this->session->data['guest']['firstname'];
                $order_data['lastname'] = $this->session->data['guest']['lastname'];
                $order_data['email'] = $this->session->data['guest']['email'];
                $order_data['telephone'] = $this->session->data['guest']['telephone'];
                $order_data['custom_field'] = $this->session->data['guest']['custom_field'];
            }

            $order_data['payment_zone_id'] = (int)$this->session->data['payment_address']['zone_id'];
            $order_data['payment_country_id'] = (int)$this->session->data['payment_address']['country_id'];
            $order_data['payment_firstname'] = $this->session->data['payment_address']['firstname'];
            $order_data['payment_lastname'] = $this->session->data['payment_address']['lastname'];
            $order_data['payment_company'] = $this->session->data['payment_address']['company'];
            $order_data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
            $order_data['payment_address_2'] = $this->session->data['payment_address']['address_2'];
            $order_data['payment_city'] = $this->session->data['payment_address']['city'];
            $order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
            $order_data['payment_zone'] = $this->session->data['payment_address']['zone'];
            $order_data['payment_country'] = $this->session->data['payment_address']['country'];
            $order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
            $order_data['payment_custom_field'] = isset($this->session->data['payment_address']['custom_field']) ? $this->session->data['payment_address']['custom_field'] : [];

            if (isset($this->session->data['payment_method']['title'])) {
                $order_data['payment_method'] = $this->session->data['payment_method']['title'];
            } else {
                $order_data['payment_method'] = '';
            }

            if (isset($this->session->data['payment_method']['code'])) {
                $order_data['payment_code'] = $this->session->data['payment_method']['code'];
            } else {
                $order_data['payment_code'] = '';
            }

            if ($this->cart->hasShipping()) {
                $order_data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
                $order_data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
                $order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
                $order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
                $order_data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
                $order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
                $order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
                $order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
                $order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
                $order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
                $order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
                $order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
                $order_data['shipping_custom_field'] = isset($this->session->data['shipping_address']['custom_field']) ? $this->session->data['shipping_address']['custom_field'] : [];

                if (isset($this->session->data['shipping_method']['title'])) {
                    $order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
                } else {
                    $order_data['shipping_method'] = '';
                }

                if (isset($this->session->data['shipping_method']['code'])) {
                    $order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
                } else {
                    $order_data['shipping_code'] = '';
                }
            } else {
                $order_data['shipping_zone_id'] = 0;
                $order_data['shipping_country_id'] = 0;
                $order_data['shipping_firstname'] = '';
                $order_data['shipping_lastname'] = '';
                $order_data['shipping_company'] = '';
                $order_data['shipping_address_1'] = '';
                $order_data['shipping_address_2'] = '';
                $order_data['shipping_city'] = '';
                $order_data['shipping_postcode'] = '';
                $order_data['shipping_zone'] = '';
                $order_data['shipping_country'] = '';
                $order_data['shipping_address_format'] = '';
                $order_data['shipping_method'] = '';
                $order_data['shipping_code'] = '';
                $order_data['shipping_custom_field'] = [];
            }

            $order_data['products'] = [];

            foreach ($this->cart->getProducts() as $product) {
                $option_data = [];

                foreach ($product['option'] as $option) {
                    $option_data[] = [
                        'product_option_id'       => $option['product_option_id'],
                        'product_option_value_id' => $option['product_option_value_id'],
                        'option_id'               => $option['option_id'],
                        'option_value_id'         => $option['option_value_id'],
                        'name'                    => $option['name'],
                        'value'                   => $option['value'],
                        'type'                    => $option['type']
                    ];
                }

                $order_data['products'][] = [
                    'product_id'   => $product['product_id'],
                    'name'         => $product['name'],
                    'model'        => $product['model'],
                    'option'       => $option_data,
                    'subscription' => $product['subscription'],
                    'download'     => $product['download'],
                    'quantity'     => $product['quantity'],
                    'subtract'     => $product['subtract'],
                    'price'        => $product['price'],
                    'total'        => $product['total'],
                    'tax'          => $this->tax->getTax($product['price'], $product['tax_class_id']),
                    'reward'       => $product['reward']
                ];
            }

            // Gift Voucher
            $order_data['vouchers'] = [];

            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $voucher) {
                    $order_data['vouchers'][] = [
                        'description'      => $voucher['description'],
                        'code'             => oc_token(10),
                        'to_name'          => $voucher['to_name'],
                        'to_email'         => $voucher['to_email'],
                        'from_name'        => $voucher['from_name'],
                        'from_email'       => $voucher['from_email'],
                        'voucher_theme_id' => $voucher['voucher_theme_id'],
                        'message'          => $voucher['message'],
                        'amount'           => $voucher['amount']
                    ];
                }
            }

            $order_data['comment'] = $this->session->data['comment'];

            $order_data['total'] = $total_data['total'];

            // Affiliate
            $order_data['affiliate_id'] = 0;
            $order_data['commission'] = 0;
            $order_data['marketing_id'] = 0;
            $order_data['tracking'] = '';

            if ($this->config->get('config_affiliate_status') && isset($this->session->data['tracking'])) {
                $subtotal = $this->cart->getSubTotal();

                // Customer Affiliate
                $this->load->model('account/affiliate');

                $affiliate_info = $this->model_account_customer->getAffiliateByTracking($this->session->data['tracking']);

                if ($affiliate_info) {
                    $order_data['affiliate_id'] = $affiliate_info['customer_id'];
                    $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
                    $order_data['tracking'] = $this->session->data['tracking'];
                }
            }

            $order_data['language_id'] = $this->config->get('config_language_id');
            $order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
            $order_data['currency_code'] = $this->session->data['currency'];
            $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
            $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

            if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
                $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
                $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
            } else {
                $order_data['forwarded_ip'] = '';
            }

            if (isset($this->request->server['HTTP_USER_AGENT'])) {
                $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
            } else {
                $order_data['user_agent'] = '';
            }

            if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
                $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
            } else {
                $order_data['accept_language'] = '';
            }

            // Orders
            $this->load->model('checkout/order');

            if (!isset($this->session->data['order_id'])) {
                $this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);
            } else {
                $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

                if ($order_info && !$order_info['order_status_id']) {
                    $this->model_checkout_order->editOrder($this->session->data['order_id'], $order_data);
                }
            }

            // Uploaded files
            $this->load->model('tool/upload');

            $data['products'] = [];

            foreach ($this->cart->getProducts() as $product) {
                $option_data = [];

                foreach ($product['option'] as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                        if ($upload_info) {
                            $value = $upload_info['name'];
                        } else {
                            $value = '';
                        }
                    }

                    $option_data[] = [
                        'name'  => $option['name'],
                        'value' => (oc_strlen($value) > 20 ? oc_substr($value, 0, 20) . '..' : $value)
                    ];
                }

                // Subscription
                $description = '';

                if ($product['subscription']) {
                    $trial_price = $this->currency->format($this->tax->calculate($product['subscription']['trial_price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $trial_cycle = $product['subscription']['trial_cycle'];
                    $trial_frequency = $this->language->get('text_' . $product['subscription']['trial_frequency']);
                    $trial_duration = $product['subscription']['trial_duration'];

                    if ($product['subscription']['trial_status']) {
                        $description .= sprintf($this->language->get('text_subscription_trial'), $trial_price, $trial_cycle, $trial_frequency, $trial_duration);
                    }

                    $price = $this->currency->format($this->tax->calculate($product['subscription']['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $cycle = $product['subscription']['cycle'];
                    $frequency = $this->language->get('text_' . $product['subscription']['frequency']);
                    $duration = $product['subscription']['duration'];

                    if ($duration) {
                        $description .= sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
                    } else {
                        $description .= sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
                    }
                }

                $data['products'][] = [
                    'cart_id'      => $product['cart_id'],
                    'product_id'   => $product['product_id'],
                    'name'         => $product['name'],
                    'model'        => $product['model'],
                    'option'       => $option_data,
                    'subscription' => $description,
                    'quantity'     => $product['quantity'],
                    'subtract'     => $product['subtract'],
                    'price'        => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                    'total'        => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']),
                    'href'         => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                ];
            }

            // Gift Voucher
            $data['vouchers'] = [];

            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $voucher) {
                    $data['vouchers'][] = [
                        'description' => $voucher['description'],
                        'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency'])
                    ];
                }
            }

            $data['totals'] = [];

            foreach ($order_data['totals'] as $total) {
                $data['totals'][] = [
                    'title' => $total['title'],
                    'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
                ];
            }

            $data['payment'] = $this->load->controller('extension/payment/' . $this->session->data['payment_method']['code']);
        } else {
            $data['redirect'] = $redirect;
        }

        $this->response->setOutput($this->load->view('checkout/confirm', $data));
    }
}
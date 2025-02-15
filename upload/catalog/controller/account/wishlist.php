<?php
class ControllerAccountWishList extends Controller {
    public function index(): void {
        if (!$this->customer->isLogged() || (!isset($this->request->get['customer_token']) || !isset($this->session->data['customer_token']) || ($this->request->get['customer_token'] != $this->session->data['customer_token']))) {
            $this->session->data['redirect'] = $this->url->link('account/wishlist', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('account/wishlist');

        // Image files
        $this->load->model('tool/image');

        // Products
        $this->load->model('catalog/product');

        // Wishlists
        $this->load->model('account/wishlist');

        if (isset($this->request->get['remove'])) {
            // Remove Wishlist
            $this->model_account_wishlist->deleteWishlist($this->request->get['remove']);

            $this->session->data['success'] = $this->language->get('text_remove');

            $this->response->redirect($this->url->link('account/wishlist'));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''), true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/wishlist', (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''), true)
        ];

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['products'] = [];

        $results = $this->model_account_wishlist->getWishlist();

        foreach ($results as $result) {
            $product_info = $this->model_catalog_product->getProduct($result['product_id']);

            if ($product_info) {
                if ($product_info['image']) {
                    $image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_height'));
                } else {
                    $image = false;
                }

                if ($product_info['quantity'] <= 0) {
                    $stock = $product_info['stock_status'];
                } elseif ($this->config->get('config_stock_display')) {
                    $stock = $product_info['quantity'];
                } else {
                    $stock = $this->language->get('text_instock');
                }

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = false;
                }

                if ((float)$product_info['special']) {
                    $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $special = false;
                }

                $data['products'][] = [
                    'product_id' => $product_info['product_id'],
                    'thumb'      => $image,
                    'name'       => $product_info['name'],
                    'model'      => $product_info['model'],
                    'stock'      => $stock,
                    'price'      => $price,
                    'special'    => $special,
                    'href'       => $this->url->link('product/product', 'product_id=' . $product_info['product_id']),
                    'remove'     => $this->url->link('account/wishlist', (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : '') . '&remove=' . $product_info['product_id'])
                ];
            } else {
                $this->model_account_wishlist->deleteWishlist($result['product_id']);
            }
        }

        $data['continue'] = $this->url->link('account/account', 'customer_token=' . $this->session->data['customer_token'], true);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/wishlist', $data));
    }

    public function add(): void {
        $this->load->language('account/wishlist');

        $json = [];

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];
        } else {
            $product_id = 0;
        }

        // Products
        $this->load->model('catalog/product');

        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
            if ($this->customer->isLogged()) {
                // Edit customers cart
                $this->load->model('account/wishlist');

                $this->model_account_wishlist->addWishlist($this->request->post['product_id']);

                $json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));
                $json['total'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
            } else {
                if (!isset($this->session->data['wishlist'])) {
                    $this->session->data['wishlist'] = [];
                }

                $this->session->data['wishlist'][] = (int)$this->request->post['product_id'];
                $this->session->data['wishlist'] = array_unique($this->session->data['wishlist']);

                $json['total'] = sprintf($this->language->get('text_wishlist'), isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0);
                $json['success'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist', (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : '')));
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
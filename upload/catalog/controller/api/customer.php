<?php
class ControllerApiCustomer extends Controller {
    public function index(): void {
        $this->load->language('api/customer');

        // Delete past customer in case there is an error
        unset($this->session->data['customer']);

        $json = [];

        if (!isset($this->session->data['api_id'])) {
            $json['error']['warning'] = $this->language->get('error_permission');
        } else {
            // Add keys for missing post vars
            $keys = [
                'customer_id',
                'customer_group_id',
                'firstname',
                'lastname',
                'email',
                'telephone',
            ];

            foreach ($keys as $key) {
                if (!isset($this->request->post[$key])) {
                    $this->request->post[$key] = '';
                }
            }

            // Customer
            if ($this->request->post['customer_id']) {
                // Customers
                $this->load->model('account/customer');

                $customer_info = $this->model_account_customer->getCustomer($this->request->post['customer_id']);

                if (!$customer_info || !$this->customer->login($customer_info['email'], '', true)) {
                    $json['error']['warning'] = $this->language->get('error_customer');
                }
            }

            if ((oc_strlen($this->request->post['firstname']) < 1) || (oc_strlen($this->request->post['firstname']) > 32)) {
                $json['error']['firstname'] = $this->language->get('error_firstname');
            }

            if ((oc_strlen($this->request->post['lastname']) < 1) || (oc_strlen($this->request->post['lastname']) > 32)) {
                $json['error']['lastname'] = $this->language->get('error_lastname');
            }

            if ((oc_strlen($this->request->post['email']) > 96) || (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL))) {
                $json['error']['email'] = $this->language->get('error_email');
            }

            if ($this->config->get('config_telephone_required') && (oc_strlen($this->request->post['telephone']) < 3) || (oc_strlen($this->request->post['telephone']) > 32)) {
                $json['error']['telephone'] = $this->language->get('error_telephone');
            }

            // Customer Group
            if (in_array($this->request->post['customer_group_id'], (array)$this->config->get('config_customer_group_display'))) {
                $customer_group_id = (int)$this->request->post['customer_group_id'];
            } else {
                $customer_group_id = (int)$this->config->get('config_customer_group_id');
            }

            // Custom field validation
            $this->load->model('account/custom_field');

            $custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

            foreach ($custom_fields as $custom_field) {
                if ($custom_field['location'] == 'account') {
                    if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                        $json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                    } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !preg_match(html_entity_decode($custom_field['validation'], ENT_QUOTES, 'UTF-8'), $this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                        $json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_regex'), $custom_field['name']);
                    }
                }
            }

            if (!$json) {
                $this->session->data['customer'] = [
                    'customer_id'       => (int)$this->request->post['customer_id'],
                    'customer_group_id' => $customer_group_id,
                    'firstname'         => $this->request->post['firstname'],
                    'lastname'          => $this->request->post['lastname'],
                    'email'             => $this->request->post['email'],
                    'telephone'         => $this->request->post['telephone'],
                    'custom_field'      => isset($this->request->post['custom_field']) ? $this->request->post['custom_field'] : []
                ];

                $json['success'] = $this->language->get('text_success');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
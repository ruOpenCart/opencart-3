<?php
class ModelExtensionPaymentGlobalpay extends Model {
    public function getMethod(array $address): array {
        $this->load->language('extension/payment/globalpay');

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_globalpay_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

        if (!$this->config->get('payment_globalpay_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = [];

        if ($status) {
            $method_data = [
                'code'       => 'globalpay',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => $this->config->get('payment_globalpay_sort_order')
            ];
        }

        return $method_data;
    }

    public function addOrder($order_info, $pas_ref, $auth_code, $account, $order_ref) {
        if ($this->config->get('payment_globalpay_auto_settle') == 1) {
            $settle_status = 1;
        } else {
            $settle_status = 0;
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "globalpay_order` SET `order_id` = '" . (int)$order_info['order_id'] . "', `settle_type` = '" . (int)$this->config->get('payment_globalpay_auto_settle') . "', `order_ref` = '" . $this->db->escape($order_ref) . "', `order_ref_previous` = '" . $this->db->escape($order_ref) . "', `date_added` = NOW(), `date_modified` = NOW(), `capture_status` = '" . (int)$settle_status . "', `currency_code` = '" . $this->db->escape($order_info['currency_code']) . "', `pasref` = '" . $this->db->escape($pas_ref) . "', `pasref_previous` = '" . $this->db->escape($pas_ref) . "', `authcode` = '" . $this->db->escape($auth_code) . "', `account` = '" . $this->db->escape($account) . "', `total` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . "'");

        return $this->db->getLastId();
    }

    public function addTransaction($globalpay_order_id, $type, $order_info) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "globalpay_order_transaction` SET `globalpay_order_id` = '" . (int)$globalpay_order_id . "', `date_added` = NOW(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . "'");
    }

    public function logger($message) {
        if ($this->config->get('payment_globalpay_debug') == 1) {
            // Log
            $log = new \Log('globalpay.log');
            $log->write($message);
        }
    }
}
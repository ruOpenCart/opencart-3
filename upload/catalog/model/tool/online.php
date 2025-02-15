<?php
class ModelToolOnline extends Model {
    public function addOnline(string $ip, int $customer_id, string $url, string $referer): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_online` WHERE `date_added` < '" . date('Y-m-d H:i:s', strtotime('-1 hour')) . "'");

        $this->db->query("REPLACE INTO `" . DB_PREFIX . "customer_online` SET `ip` = '" . $this->db->escape($ip) . "', `customer_id` = '" . (int)$customer_id . "', `url` = '" . $this->db->escape($url) . "', `referer` = '" . $this->db->escape($referer) . "', `date_added` = '" . $this->db->escape(date('Y-m-d H:i:s')) . "'");
    }
}
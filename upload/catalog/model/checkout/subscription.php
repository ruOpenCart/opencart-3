<?php
class ModelCheckoutSubscription extends Model {
    public function addSubscription(int $order_id, array $data): int {
        if (!empty($data['customer_payment_id'])) {
            $customer_payment_id = $data['customer_payment_id'];
        } else {
            $customer_payment_id = 0;
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "subscription` SET `customer_id` = '" . (int)$data['customer_id'] . "', `order_id` = '" . (int)$order_id . "', `order_product_id` = '" . (int)$data['order_product_id'] . "', `subscription_plan_id` = '" . (int)$data['subscription_plan_id'] . "', `customer_payment_id` = '" . (int)$customer_payment_id . "', `name` = '" . $this->db->escape($data['name']) . "', `description` = '" . $this->db->escape($data['description']) . "', `reference` = '', `trial_price` = '" . (float)$data['trial_price'] . "', `trial_frequency` = '" . $this->db->escape($data['trial_frequency']) . "', `trial_cycle` = '" . (int)$data['trial_cycle'] . "', `trial_duration` = '" . (int)$data['trial_duration'] . "', `trial_status` = '" . (int)$data['trial_status'] . "', `price` = '" . (float)$data['price'] . "', `frequency` = '" . $this->db->escape($data['frequency']) . "', `cycle` = '" . (int)$data['cycle'] . "', `duration` = '" . (int)$data['duration'] . "', `date_next` = '" . $this->db->escape($data['date_next']) . "', `subscription_status_id` = '" . (int)$this->config->get('config_subscription_status_id') . "', `status` = '" . (int)$data['status'] . "', `date_added` = NOW(), `date_modified` = NOW()");

        return $this->db->getLastId();
    }

    public function editReference(int $subscription_id, string $reference): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "subscription` SET `reference` = '" . $this->db->escape($reference) . "' WHERE `subscription_id` = '" . (int)$subscription_id . "'");
    }

    public function deleteSubscriptionByOrderId(int $order_id): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "subscription` WHERE `order_id` = '" . (int)$order_id . "'");
    }

    public function getSubscriptionByOrderProductId(int $order_product_id): array {
        $this->db->query("SELECT * FROM  `" . DB_PREFIX . "subscription` WHERE `order_product_id` = '" . (int)$order_product_id . "'");

        return $this->db->row;
    }

    public function addHistory(int $subscription_id, int $subscription_status_id, string $comment = '', bool $notify = false): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "subscription_history` SET `subscription_id` = '" . (int)$subscription_id . "', `subscription_status_id` = '" . (int)$subscription_status_id . "', `comment` = '" . $this->db->escape($comment) . "', `notify` = '" . (int)$notify . "', `date_added` = NOW()");

        $this->db->query("UPDATE `" . DB_PREFIX . "subscription` SET `subscription_status_id` = '" . (int)$subscription_status_id . "' WHERE `subscription_id` = '" . (int)$subscription_id . "'");
    }
}
<?php
class ModelAccountCustomer extends Model {
    public function addCustomer(array $data): int {
        if (isset($data['customer_group_id']) && in_array($data['customer_group_id'], (array)$this->config->get('config_customer_group_display'))) {
            $customer_group_id = $data['customer_group_id'];
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        // Customer Groups
        $this->load->model('account/customer_group');

        $customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

        $this->db->query("INSERT INTO `" . DB_PREFIX . "customer` SET `customer_group_id` = '" . (int)$customer_group_id . "', `store_id` = '" . (int)$this->config->get('config_store_id') . "', `language_id` = '" . (int)$this->config->get('config_language_id') . "', `firstname` = '" . $this->db->escape($data['firstname']) . "', `lastname` = '" . $this->db->escape($data['lastname']) . "', `email` = '" . $this->db->escape($data['email']) . "', `telephone` = '" . $this->db->escape($data['telephone']) . "', `custom_field` = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', `password` = '" . $this->db->escape(password_hash(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT)) . "', `newsletter` = '" . (isset($data['newsletter']) ? (int)$data['newsletter'] : 0) . "', `ip` = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', `status` = '" . (int)!$customer_group_info['approval'] . "', `date_added` = NOW()");

        $customer_id = $this->db->getLastId();

        if ($customer_group_info['approval']) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_approval` SET `customer_id` = '" . (int)$customer_id . "', `type` = 'customer', `date_added` = NOW()");
        }

        return $customer_id;
    }

    public function editCustomer(int $customer_id, array $data): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `firstname` = '" . $this->db->escape($data['firstname']) . "', `lastname` = '" . $this->db->escape($data['lastname']) . "', `email` = '" . $this->db->escape($data['email']) . "', `telephone` = '" . $this->db->escape($data['telephone']) . "', `custom_field` = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "' WHERE `customer_id` = '" . (int)$customer_id . "'");
    }

    public function editPassword(string $email, string $password): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `password` = '" . $this->db->escape(password_hash(html_entity_decode($password, ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT)) . "', `code` = '' WHERE LCASE(`email`) = '" . $this->db->escape(oc_strtolower($email)) . "'");
    }

    public function editAddressId(int $customer_id, int $address_id): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `address_id` = '" . (int)$address_id . "' WHERE `customer_id` = '" . (int)$customer_id . "'");
        $this->db->query("UPDATE `" . DB_PREFIX . "address` SET `default` = '1' WHERE `address_id` != '" . (int)$address_id . "' AND `customer_id` = '" . (int)$customer_id . "'");
    }

    public function editCode(string $email, string $code): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `code` = '" . $this->db->escape($code) . "' WHERE LCASE(`email`) = '" . $this->db->escape(oc_strtolower($email)) . "'");
    }

    public function editNewsletter(string $newsletter): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `newsletter` = '" . (int)$newsletter . "' WHERE `customer_id` = '" . (int)$this->customer->getId() . "'");
    }

    public function getCustomer(int $customer_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer` WHERE `customer_id` = '" . (int)$customer_id . "'");

        return $query->row;
    }

    public function getCustomerByEmail(string $email): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer` WHERE LCASE(`email`) = '" . $this->db->escape(oc_strtolower($email)) . "'");

        return $query->row;
    }

    public function getCustomerByCode(string $code): array {
        $query = $this->db->query("SELECT `customer_id`, `firstname`, `lastname`, `email` FROM `" . DB_PREFIX . "customer` WHERE `code` = '" . $this->db->escape($code) . "' AND `code` != ''");

        return $query->row;
    }

    public function getCustomerByToken(string $token): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer` WHERE `token` = '" . $this->db->escape($token) . "' AND `token` != ''");

        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `token` = ''");

        return $query->row;
    }

    public function getTotalCustomersByEmail(string $email): int {
        $query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "customer` WHERE LCASE(`email`) = '" . $this->db->escape(oc_strtolower($email)) . "'");

        return (int)$query->row['total'];
    }

    public function addTransaction(int $customer_id, string $description, float $amount = 0, int $order_id = 0): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_transaction` SET `customer_id` = '" . (int)$customer_id . "', `order_id` = '" . (float)$order_id . "', `description` = '" . $this->db->escape($description) . "', `amount` = '" . (float)$amount . "', `date_added` = NOW()");
    }

    public function deleteTransactionByOrderId(int $order_id): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_transaction` WHERE `order_id` = '" . (int)$order_id . "'");
    }

    public function getTransactionTotal(int $customer_id): float {
        $query = $this->db->query("SELECT SUM(`amount`) AS `total` FROM `" . DB_PREFIX . "customer_transaction` WHERE `customer_id` = '" . (int)$customer_id . "'");

        return (float)$query->row['total'];
    }

    public function getTotalTransactionsByOrderId(int $order_id): int {
        $query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "customer_transaction` WHERE `order_id` = '" . (int)$order_id . "'");

        return (int)$query->row['total'];
    }

    public function getRewardTotal(int $customer_id): int {
        $query = $this->db->query("SELECT SUM(`points`) AS `total` FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "'");

        return (int)$query->row['total'];
    }

    public function getIps(int $customer_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_ip` WHERE `customer_id` = '" . (int)$customer_id . "'");

        return $query->rows;
    }

    public function addLogin(int $customer_id, string $ip, string $country = ''): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_ip` SET `customer_id` = '" . (int)$customer_id . "', `store_id` = '" . (int)$this->config->get('config_store_id') . "', `ip` = '" . $this->db->escape($ip) . "', `country` = '" . $this->db->escape($country) . "', `date_added` = NOW()");
    }

    public function addLoginAttempt(string $email): void {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_login` WHERE LCASE(`email`) = '" . $this->db->escape(oc_strtolower((string)$email)) . "' AND `ip` = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "'");

        if (!$query->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_login` SET `email` = '" . $this->db->escape(oc_strtolower((string)$email)) . "', `ip` = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', `total` = '1', `date_added` = '" . $this->db->escape(date('Y-m-d H:i:s')) . "', `date_modified` = '" . $this->db->escape(date('Y-m-d H:i:s')) . "'");
        } else {
            $this->db->query("UPDATE `" . DB_PREFIX . "customer_login` SET `total` = (`total` + 1), `date_modified` = '" . $this->db->escape(date('Y-m-d H:i:s')) . "' WHERE `customer_login_id` = '" . (int)$query->row['customer_login_id'] . "'");
        }
    }

    public function getLoginAttempts(string $email): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_login` WHERE LCASE(`email`) = '" . $this->db->escape(oc_strtolower($email)) . "'");

        return $query->row;
    }

    public function deleteLoginAttempts(string $email): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_login` WHERE LCASE(`email`) = '" . $this->db->escape(oc_strtolower($email)) . "'");
    }

    public function addAffiliate(int $customer_id, array $data): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_affiliate` SET `customer_id` = '" . (int)$customer_id . "', `company` = '" . $this->db->escape($data['company']) . "', `website` = '" . $this->db->escape($data['website']) . "', `tracking` = '" . $this->db->escape(oc_token(10)) . "', `commission` = '" . (float)$this->config->get('config_affiliate_commission') . "', `tax` = '" . $this->db->escape($data['tax']) . "', `payment` = '" . $this->db->escape($data['payment']) . "', `cheque` = '" . $this->db->escape($data['cheque']) . "', `paypal` = '" . $this->db->escape($data['paypal']) . "', `bank_name` = '" . $this->db->escape($data['bank_name']) . "', `bank_branch_number` = '" . $this->db->escape($data['bank_branch_number']) . "', `bank_swift_code` = '" . $this->db->escape($data['bank_swift_code']) . "', `bank_account_name` = '" . $this->db->escape($data['bank_account_name']) . "', `bank_account_number` = '" . $this->db->escape($data['bank_account_number']) . "', `custom_field` = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', `status` = '" . (int)!$this->config->get('config_affiliate_approval') . "'");

        if ($this->config->get('config_affiliate_approval')) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_approval` SET `customer_id` = '" . (int)$customer_id . "', `type` = 'affiliate', `date_added` = NOW()");
        }
    }

    public function editAffiliate(int $customer_id, array $data): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "customer_affiliate` SET `company` = '" . $this->db->escape($data['company']) . "', `website` = '" . $this->db->escape($data['website']) . "', `commission` = '" . (float)$this->config->get('config_affiliate_commission') . "', `tax` = '" . $this->db->escape($data['tax']) . "', `payment` = '" . $this->db->escape($data['payment']) . "', `cheque` = '" . $this->db->escape($data['cheque']) . "', `paypal` = '" . $this->db->escape($data['paypal']) . "', `bank_name` = '" . $this->db->escape($data['bank_name']) . "', `bank_branch_number` = '" . $this->db->escape($data['bank_branch_number']) . "', `bank_swift_code` = '" . $this->db->escape($data['bank_swift_code']) . "', `bank_account_name` = '" . $this->db->escape($data['bank_account_name']) . "', `bank_account_number` = '" . $this->db->escape($data['bank_account_number']) . "',  `custom_field` = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "' WHERE `customer_id` = '" . (int)$customer_id . "'");
    }

    public function getAffiliate(int $customer_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_affiliate` WHERE `customer_id` = '" . (int)$customer_id . "'");

        return $query->row;
    }

    public function getAffiliateByTracking(string $tracking): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_affiliate` WHERE `tracking` = '" . $this->db->escape($tracking) . "'");

        return $query->row;
    }

    public function addReport(int $customer_id, string $ip, string $country = ''): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_affiliate_report` SET `customer_id` = '" . (int)$customer_id . "', `store_id` = '" . (int)$this->config->get('config_store_id') . "', `ip` = '" . $this->db->escape($ip) . "', `country` = '" . $this->db->escape($country) . "', `date_added` = NOW()");
    }
}
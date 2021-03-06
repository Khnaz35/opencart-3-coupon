<?php
class ModelExtensionModuleCouponNik extends Model {
    public function install() {
        $this->log('Installing module');
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "customer_coupon` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`coupon_id` INT(11) NOT NULL,
			`coupon_code` varchar(50) NOT NULL,
			`customer_id` INT(11) NOT NULL,
			`coupon_link` varchar(255) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "customer_coupon`");

        $this->log('Module uninstalled');
    }

    public function add($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "customer_coupon SET `coupon_id` = '" . (int)$data['coupon_id'] . "', coupon_code = '" . $this->db->escape($data['coupon_code']) . "', customer_id = '" . (int)$data['customer_id'] . "', coupon_link = '" . $this->db->escape($data['coupon_link']) . "'");

        return $this->db->getLastId();
    }

    public function delete($coupon_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "customer_coupon WHERE `coupon_id` = '" . (int)$coupon_id . "'");

        $this->cache->delete('customer_coupon');
    }

    public function getCoupons($data = array()) {
        $sql = "SELECT `coupon_id`, `name`, `code`, `discount`, `date_start`, `date_end`, `status` FROM " . DB_PREFIX . "coupon";

        if (!empty($data['filter_code'])) {
            $sql .= " WHERE code LIKE '" . $this->db->escape($data['filter_code']) . "%'";
        }

        $sort_data = array(
            'name',
            'code',
            'discount',
            'date_start',
            'date_end',
            'status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function repairRelations() {
        $query = $this->db->query("SELECT `coupon_id` FROM " . DB_PREFIX . "customer_coupon");

        foreach ($query->rows as $row) {
            $exist = $this->db->query("SELECT `name` FROM " . DB_PREFIX . "coupon WHERE coupon_id = '" . (int)$row['coupon_id'] . "'");
            if(!$exist->rows) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "customer_coupon WHERE coupon_id = '" . (int)$row['coupon_id'] . "'");
            }
        }
    }

    public function log($data) {
        // if ($this->config->has('payment_stripe_logging') && $this->config->get('payment_stripe_logging')) {
        $log = new Log('mailing.log');

        $log->write($data);
        // }
    }
}
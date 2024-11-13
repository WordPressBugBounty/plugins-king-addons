<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class FreemiusInit
{
    public $freemius;

    public function __construct()
    {
        $this->freemius = $this->init_freemius();
        add_action('admin_head', [$this, 'add_freemius_data_to_js']);
    }

    public function init_freemius()
    {
        if (!isset($this->freemius)) {
            require_once KING_ADDONS_PATH . 'freemius/start.php';
            $this->freemius = fs_dynamic_init([
                'id' => '16154', // Replace with your Freemius ID
                'slug' => 'king-addons',
                'type' => 'plugin',
                'public_key' => 'pk_eac3624cbc14c1846cf1ab9abbd68', // Replace with your public key
                'is_org_compliant' => true,
                'has_affiliation' => true,
                'is_premium' => false,
                'has_premium_version' => false,
                'has_addons' => false,
                'has_paid_plans' => true,
                'menu' => array(
                    'slug' => 'king-addons',
                    // 'first-path' => 'admin.php?page=king-addons',
                    'pricing' => false,
                    'contact' => false,
                    'support' => false,
                ),
            ]);

            // Signal that SDK was initiated.
            do_action('init_freemius_loaded');
        }
        return $this->freemius;
    }

    public function is_premium_active(): bool
    {
        return $this->freemius->is_paying();
    }

    public static function instance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    public function add_freemius_data_to_js()
    {
        if ($this->is_templates_page()) {
            if ($this->is_premium_active()) {
                ?>
                <script type="text/javascript">
                    (function () {
                        window.kingAddons = window.kingAddons || {};
                        window.kingAddons.installId = <?php
                        echo json_encode($this->freemius->get_site()->id);
                        ?>;
                    })();
                </script>
                <?php
            }
        }
    }

    private function is_templates_page()
    {
        return isset($_GET['page']) && $_GET['page'] === 'king-addons-templates';
    }

}

// Initialize the FreemiusInit class
new FreemiusInit();
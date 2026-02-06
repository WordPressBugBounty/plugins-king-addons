<?php

namespace King_Addons\Wishlist;

use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Boots wishlist functionality and exposes shared service instance.
 */
class Wishlist_Module
{
    private ?Wishlist_Service $service = null;
    private ?Wishlist_Renderer $renderer = null;
    private ?Wishlist_WooCommerce $woocommerce = null;
    private ?Wishlist_Frontend $frontend = null;

    /**
     * Hook module initialization.
     */
    public function __construct()
    {
        add_action('init', [$this, 'boot']);
        add_action('wp_login', [$this, 'handle_login'], 10, 2);
    }

    /**
     * Initialize wishlist module on init.
     *
     * @return void
     */
    public function boot(): void
    {
        Wishlist_DB::maybe_create_tables();
        $this->service = new Wishlist_Service();
        $this->renderer = new Wishlist_Renderer($this->service);
        $this->woocommerce = new Wishlist_WooCommerce($this->service, $this->renderer);
        $this->woocommerce->hooks();
        $this->frontend = new Wishlist_Frontend($this->service, $this->renderer);
        $this->frontend->hooks();

        if (isset($_GET['ka_wishlist'])) {
            $wishlist_id = sanitize_title(wp_unslash($_GET['ka_wishlist']));
            if (!empty($wishlist_id)) {
                $this->service->set_active_wishlist_id($wishlist_id);
            }
        }
    }

    /**
     * Merge guest wishlist into user account after login.
     *
     * @param string $user_login Username.
     * @param WP_User $user User object.
     * @return void
     */
    public function handle_login(string $user_login, WP_User $user): void
    {
        $session = new Wishlist_Session();
        $session_key = $session->get_session_key();

        $service = new Wishlist_Service($user->ID, $session_key);
        $service->merge_guest_items($user->ID, $session_key);
    }

    /**
     * Get wishlist service instance.
     *
     * @return Wishlist_Service Wishlist service.
     */
    public function service(): Wishlist_Service
    {
        if (!$this->service instanceof Wishlist_Service) {
            $this->service = new Wishlist_Service();
        }

        return $this->service;
    }

    /**
     * Get wishlist renderer instance.
     *
     * @return Wishlist_Renderer Renderer instance.
     */
    public function renderer(): Wishlist_Renderer
    {
        if (!$this->renderer instanceof Wishlist_Renderer) {
            $this->renderer = new Wishlist_Renderer($this->service());
        }

        return $this->renderer;
    }

    /**
     * Get frontend handler instance.
     *
     * @return Wishlist_Frontend Frontend instance.
     */
    public function frontend(): Wishlist_Frontend
    {
        if (!$this->frontend instanceof Wishlist_Frontend) {
            $this->frontend = new Wishlist_Frontend($this->service(), $this->renderer());
        }

        return $this->frontend;
    }
}




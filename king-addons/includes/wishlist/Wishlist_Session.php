<?php

namespace King_Addons\Wishlist;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages wishlist session keys for guests and logged-in users.
 */
class Wishlist_Session
{
    private const COOKIE_NAME = 'ka_wishlist_session';
    private const COOKIE_TTL_SECONDS = 2592000; // 30 days

    /**
     * Get or create a session key tied to a browser.
     *
     * @return string Session key.
     */
    public function get_session_key(): string
    {
        $existing = isset($_COOKIE[self::COOKIE_NAME]) ? sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_NAME])) : '';
        if (!empty($existing)) {
            return $existing;
        }

        $session_key = wp_generate_uuid4();
        $this->persist_cookie($session_key);

        return $session_key;
    }

    /**
     * Persist the wishlist session cookie.
     *
     * @param string $session_key Session key to store.
     * @return void
     */
    public function persist_cookie(string $session_key): void
    {
        setcookie(
            self::COOKIE_NAME,
            $session_key,
            [
                'expires' => time() + self::COOKIE_TTL_SECONDS,
                'path' => COOKIEPATH ? COOKIEPATH : '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
        $_COOKIE[self::COOKIE_NAME] = $session_key;
    }

    /**
     * Clear the wishlist session cookie.
     *
     * @return void
     */
    public function clear(): void
    {
        setcookie(
            self::COOKIE_NAME,
            '',
            [
                'expires' => time() - YEAR_IN_SECONDS,
                'path' => COOKIEPATH ? COOKIEPATH : '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
        unset($_COOKIE[self::COOKIE_NAME]);
    }
}




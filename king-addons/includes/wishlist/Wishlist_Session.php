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
     * Cookie name used for guest wishlist identity.
     *
     * @return string Cookie name.
     */
    public static function cookie_name(): string
    {
        return self::COOKIE_NAME;
    }

    /**
     * Read the existing session cookie without creating one.
     *
     * @return string Session key or empty string.
     */
    public function get_existing_key(): string
    {
        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return '';
        }

        return sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_NAME]));
    }

    /**
     * Get a session key tied to a browser.
     *
     * Pass $create = true only when a guest actually needs a persistent
     * wishlist (add/toggle). Reading a page must not Set-Cookie, or public
     * caches such as LiteSpeed cannot store the homepage for first visitors.
     *
     * @param bool $create Whether to mint and persist a cookie if none exists.
     * @return string Session key, or empty string when none exists and $create is false.
     */
    public function get_session_key(bool $create = false): string
    {
        $existing = $this->get_existing_key();
        if ($existing !== '') {
            return $existing;
        }

        if (!$create) {
            return '';
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
        if ($session_key === '' || headers_sent()) {
            return;
        }

        setcookie(
            self::COOKIE_NAME,
            $session_key,
            $this->cookie_options(time() + self::COOKIE_TTL_SECONDS)
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
        self::expire_if_present();
    }

    /**
     * Drop a leftover guest cookie without minting a new one.
     *
     * @return void
     */
    public static function expire_if_present(): void
    {
        if (empty($_COOKIE[self::COOKIE_NAME]) || headers_sent()) {
            return;
        }

        setcookie(
            self::COOKIE_NAME,
            '',
            (new self())->cookie_options(time() - YEAR_IN_SECONDS)
        );
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Shared cookie flags for set and expire.
     *
     * @param int $expires Unix expiry timestamp.
     * @return array<string, mixed> setcookie options.
     */
    private function cookie_options(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => COOKIEPATH ? COOKIEPATH : '/',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

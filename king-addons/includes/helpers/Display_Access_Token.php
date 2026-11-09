<?php
namespace King_Addons;

if (!defined('ABSPATH')) { exit; }

/** Expiring proof of access bound to the configured protection and site secrets. */
class Display_Access_Token
{
    public static function issue(string $context, int $expiry): string
    {
        return $expiry . '.' . hash_hmac('sha256', $context . '|' . $expiry, wp_salt('auth'));
    }

    public static function valid($token, string $context): bool
    {
        if (!is_string($token) || !preg_match('/^([0-9]{1,12})\.([a-f0-9]{64})$/D', $token, $match)) { return false; }
        $expiry = (int) $match[1];
        return $expiry > time() && hash_equals(self::issue($context, $expiry), $token);
    }
}

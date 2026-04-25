<?php
defined('ABSPATH') || exit;

/**
 * Handles encrypted storage of the agent token in wp_options.
 * Uses AES-256-CBC with AUTH_SALT as the key — never stores plaintext.
 */
final class ReviveGuard_TokenStore
{
    private const OPTION_KEY = 'reviveguard_agent_token';

    public static function get(): string
    {
        $encrypted = (string) get_option(self::OPTION_KEY, '');
        if (empty($encrypted)) {
            return '';
        }
        return self::decrypt($encrypted);
    }

    public static function set(string $rawToken): void
    {
        update_option(self::OPTION_KEY, self::encrypt($rawToken));
    }

    public static function clear(): void
    {
        delete_option(self::OPTION_KEY);
    }

    private static function getSalt(): string
    {
        return defined('AUTH_SALT') ? AUTH_SALT : wp_generate_password(64, true, true);
    }

    private static function encrypt(string $value): string
    {
        $salt = self::getSalt();
        $iv   = substr($salt, 0, 16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $salt, 0, $iv);
        return base64_encode((string) $encrypted);
    }

    private static function decrypt(string $encrypted): string
    {
        $salt    = self::getSalt();
        $iv      = substr($salt, 0, 16);
        $decoded = base64_decode($encrypted);
        if ($decoded === false) {
            return '';
        }
        $result = openssl_decrypt($decoded, 'AES-256-CBC', $salt, 0, $iv);
        return $result !== false ? $result : '';
    }
}

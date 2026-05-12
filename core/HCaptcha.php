<?php

declare(strict_types=1);

final class HCaptcha
{
    private const VERIFY_URL = 'https://api.hcaptcha.com/siteverify';

    public static function isEnabled(): bool
    {
        return Settings::get('hcaptcha_enabled', '0') === '1'
            && self::siteKey() !== ''
            && self::secretKey() !== '';
    }

    public static function siteKey(): string
    {
        return trim(Settings::get('hcaptcha_site_key'));
    }

    public static function scriptTag(): string
    {
        if (!self::isEnabled()) {
            return '';
        }

        return '<script src="https://js.hcaptcha.com/1/api.js" async defer></script>';
    }

    public static function widget(): string
    {
        if (!self::isEnabled()) {
            return '';
        }

        return '<div class="captcha-field"><div class="h-captcha" data-sitekey="' . cms_e(self::siteKey()) . '"></div></div>';
    }

    public static function verify(string $response, string $remoteIp = ''): array
    {
        if (!self::isEnabled()) {
            return [true, ''];
        }

        if ($response === '') {
            return [false, 'Please complete the hCaptcha check.'];
        }

        $payload = [
            'secret' => self::secretKey(),
            'response' => $response,
            'sitekey' => self::siteKey(),
        ];

        if ($remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            $body = http_build_query($payload, '', '&', PHP_QUERY_RFC1738);
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $body,
                    'ignore_errors' => true,
                    'timeout' => 12,
                ],
            ]);
            $result = file_get_contents(self::VERIFY_URL, false, $context);

            if ($result === false) {
                return [false, 'hCaptcha verification could not be reached. Please try again.'];
            }

            $decoded = json_decode($result, true);
            if (!is_array($decoded)) {
                return [false, 'hCaptcha returned an unreadable response. Please try again.'];
            }

            if (($decoded['success'] ?? false) === true) {
                return [true, ''];
            }

            return [false, 'hCaptcha verification failed. Please try again.'];
        } catch (Throwable $e) {
            return [false, 'hCaptcha verification failed: ' . $e->getMessage()];
        }
    }

    private static function secretKey(): string
    {
        return trim(Settings::get('hcaptcha_secret_key'));
    }
}

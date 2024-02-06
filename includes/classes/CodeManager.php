<?php

namespace OTP;

class CodeManager
{

    private $secret;
    private $digits;
    private $algorithm;

    private static $instance;

    public function __construct($secret, $digits = 6, $algorithm = 'sha256')
    {
        $this->secret = $secret;
        $this->digits = $digits;
        $this->algorithm = $algorithm;
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self(get_option('otp_encryption_server_secret', strval(time())));
        }

        return self::$instance;
    }

    public function generate($email)
    {
        if ($email === null) {
            return '';
        }

        $binary_salted_email = pack('N*', $email) . pack('N*', time());
        $hash = hash_hmac($this->algorithm, $binary_salted_email, $this->secret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated_hash = $this->hashToInt($hash, $offset) & 0x7FFFFFFF;
        $pin = str_pad($truncated_hash % pow(10, $this->digits), $this->digits, '0', STR_PAD_LEFT);

        return $pin;
    }

    private function hashToInt($hash, $offset)
    {
        return (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );
    }

    public function verify($pin, $email)
    {
        if ($email === null) {
            return false;
        }

        if ($this->generate($email) == $pin) {
            return true;
        }

        return false;
    }
}

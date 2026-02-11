<?php

namespace App\Libraries;

class MessageEncryption
{
    private $key;
    private $cipher = 'AES-256-CBC';
    private $encryptionPrefix = 'ENCRYPTED:';

    public function __construct()
    {
        // Get encryption key from environment or generate a temporary one
        $this->key = getenv('MESSAGE_ENCRYPTION_KEY') ?: $this->generateKey();
        
        // Ensure key is valid for the cipher
        if (strlen(base64_decode($this->key)) !== 32) {
            log_message('error', 'Invalid encryption key length. Using fallback key.');
            $this->key = $this->generateKey();
        }
    }

    /**
     * Encrypt a message
     */
    public function encrypt($plaintext)
    {
        if (empty($plaintext) || $this->isEncrypted($plaintext)) {
            return $plaintext;
        }

        try {
            $ivlen = openssl_cipher_iv_length($this->cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $ciphertext = openssl_encrypt(
                $plaintext,
                $this->cipher,
                base64_decode($this->key),
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($ciphertext === false) {
                log_message('error', 'Encryption failed: ' . openssl_error_string());
                return $plaintext;
            }

            // Combine IV and ciphertext and encode
            $encrypted = base64_encode($iv . $ciphertext);
            return $this->encryptionPrefix . $encrypted;
        } catch (\Exception $e) {
            log_message('error', 'Encryption exception: ' . $e->getMessage());
            return $plaintext;
        }
    }

    /**
     * Decrypt a message
     */
    public function decrypt($ciphertext)
    {
        if (empty($ciphertext) || !$this->isEncrypted($ciphertext)) {
            return $ciphertext;
        }

        try {
            // Remove prefix
            $ciphertext = substr($ciphertext, strlen($this->encryptionPrefix));
            $ciphertext = base64_decode($ciphertext);
            
            $ivlen = openssl_cipher_iv_length($this->cipher);
            $iv = substr($ciphertext, 0, $ivlen);
            $ciphertext = substr($ciphertext, $ivlen);
            
            $plaintext = openssl_decrypt(
                $ciphertext,
                $this->cipher,
                base64_decode($this->key),
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($plaintext === false) {
                log_message('error', 'Decryption failed: ' . openssl_error_string());
                return "Error: Could not decrypt message";
            }

            return $plaintext;
        } catch (\Exception $e) {
            log_message('error', 'Decryption exception: ' . $e->getMessage());
            return "Error: Could not decrypt message";
        }
    }

    /**
     * Check if a string is already encrypted
     */
    public function isEncrypted($text)
    {
        return strpos($text, $this->encryptionPrefix) === 0;
    }

    /**
     * Generate a temporary encryption key
     */
    private function generateKey()
    {
        $key = base64_encode(openssl_random_pseudo_bytes(32));
        log_message('warning', 'Using temporary encryption key. Set MESSAGE_ENCRYPTION_KEY in .env for persistent encryption.');
        return $key;
    }
}

<?php
/**
 * Class for using Cookie to managing session data with encryption.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class SessionHelper
{
    /** The cipher method */
    public $cipher_algo = 'AES-256-CBC';

    /** Usually 256-bit passphrase */
    public $passphrase = null;

    /** Bitwise disjunction of the flags OPENSSL_RAW_DATA, and OPENSSL_ZERO_PADDING or OPENSSL_DONT_ZERO_PAD_KEY */
    public $options = OPENSSL_RAW_DATA;

    /** Usually 128-bit iv */
    public $iv = null;

    /** Session cookie name */
    public $sessionName = null;

    /** Session data cookie name */
    public $sessionDataName = null;

    /** Session Path */
    public $sessionSavePath = null;

    /** Session max lifetime */
    public $sessionMaxlifetime = null;

    /** Current timestamp */
    public $currentTimestamp = null;

    /** Session data found */
    public $dataFound = false;
    
    /** Session Data */
    public $sessionData = '';

    /** Spam flag */
    public $isSpam = false;

    /**
     * Encryption
     *
     * @param string $plaintext
     * @return string ciphertext
     */
    protected function encryptData($plaintext)
    {
        if (!empty($this->passphrase) && !empty($this->iv)) {
            return base64_encode(openssl_encrypt(
                $plaintext,
                $this->cipher_algo,
                $this->passphrase,
                $this->options,
                $this->iv
            ));
        }
        return $plaintext;
    }

    /**
     * Decryption
     *
     * @param string $ciphertext
     * @return string plaintext
     */
    protected function decryptData($ciphertext)
    {
        if (!empty($this->passphrase) && !empty($this->iv)) {
            return openssl_decrypt(
                base64_decode($ciphertext),
                $this->cipher_algo,
                $this->passphrase,
                $this->options,
                $this->iv
            );
        }
        return $ciphertext;
    }

    /**
     * Returns random 64 char string
     *
     * @return string
     */
    protected function getRandomString()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Unset session cookies
     *
     * @return void
     */
    protected function unsetSessionCookie()
    {
        if (!empty($this->sessionName)) {
            setcookie($this->sessionName, '', 1);
            setcookie($this->sessionName, '', 1, '/');    
        }
        if (!empty($this->sessionDataName)) {
            setcookie($this->sessionDataName,'',1);
            setcookie($this->sessionDataName,'',1, '/');
        }
    }
}

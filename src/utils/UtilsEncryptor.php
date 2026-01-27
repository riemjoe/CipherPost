<?php

namespace Postcardarchive\Utils;

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;

/**
 * UtilsEncryptor - Handhabt die hybride Verschlüsselung von Postkarten-Daten.
 * Nutzt RSA für den Schlüsselaustausch und AES-256-CBC für die Bilddaten.
 */
class UtilsEncryptor
{
    /**
     * Erstellt ein neues RSA-Schlüsselpaar (2048 Bit).
     * @return PrivateKey
     */
    public static function createPrivateKey(): PrivateKey
    {
        return RSA::createKey(2048);
    }

    /**
     * Extrahiert den Public Key aus einem Private Key Objekt.
     * @param PrivateKey $privateKey
     * @return PublicKey
     */
    public static function getPublicKeyFromPrivateKey(PrivateKey $privateKey): PublicKey
    {
        return $privateKey->getPublicKey();
    }

    /**
     * Lädt einen Public Key aus einem String.
     * @param string $publicKeyString
     * @return PublicKey
     */
    public static function getPublicKeyFromString(string $publicKeyString): PublicKey
    {
        return RSA::loadPublicKey($publicKeyString);
    }

    /**
     * Lädt einen Private Key aus einem String.
     * @param string $privateKeyString
     * @return PrivateKey
     */
    public static function getPrivateKeyFromString(string $privateKeyString): PrivateKey
    {
        return RSA::loadPrivateKey($privateKeyString);
    }

    /**
     * Verschlüsselt Daten hybrid: 
     * 1. Erzeugt zufälligen AES-Key & IV.
     * 2. Verschlüsselt die Daten mit AES-256-CBC.
     * 3. Verschlüsselt den AES-Key mit dem RSA Public Key.
     * * @param PublicKey $publicKey
     * @param string $data (Binärdaten des Bildes)
     * @return string (Base64-kodiertes JSON-Paket)
     */
    public static function encryptData(PublicKey $publicKey, string $data): string
    {
        // 1. Symmetrische Parameter generieren
        $aesKey = openssl_random_pseudo_bytes(32); // 256 Bit
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);

        // 2. Daten symmetrisch verschlüsseln (schnell & größenunabhängig)
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);

        // 3. Den AES-Key asymmetrisch verschlüsseln (Schutz des Schlüssels)
        $encryptedAesKey = $publicKey->encrypt($aesKey);

        // 4. Alles in ein Paket schnüren
        $package = [
            'key'  => base64_encode($encryptedAesKey),
            'iv'   => base64_encode($iv),
            'data' => base64_encode($encryptedData)
        ];

        return base64_encode(json_encode($package));
    }

    /**
     * Entschlüsselt das hybride Paket:
     * 1. Entschlüsselt den AES-Key mittels RSA Private Key.
     * 2. Entschlüsselt die Daten mittels des gewonnenen AES-Keys.
     * * @param PrivateKey $privateKey
     * @param string $encryptedPackage (Das Base64-JSON aus der DB)
     * @return string (Original Binärdaten)
     * @throws \Exception
     */
    public static function decryptData(PrivateKey $privateKey, string $encryptedPackage): string
    {
        $decodedJson = base64_decode($encryptedPackage);
        $package = json_decode($decodedJson, true);
        
        if (!$package || !isset($package['key'], $package['iv'], $package['data'])) {
            throw new \Exception("Ungültiges Verschlüsselungspaket.");
        }

        // 1. RSA-Teil: AES-Key zurückgewinnen
        $encryptedAesKey = base64_decode($package['key']);
        $aesKey = $privateKey->decrypt($encryptedAesKey);

        if (!$aesKey) {
            throw new \Exception("AES-Key konnte nicht entschlüsselt werden.");
        }

        // 2. AES-Teil: Daten entschlüsseln
        $iv = base64_decode($package['iv']);
        $encryptedData = base64_decode($package['data']);

        $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);

        if ($decryptedData === false) {
            throw new \Exception("Symmetrische Entschlüsselung fehlgeschlagen.");
        }

        return $decryptedData;
    }
}
<?php

namespace Postcardarchive\Controllers;

use Postcardarchive\Models\PostcardModel;
use Postcardarchive\Utils\UtilsDatabase;
use Ramsey\Uuid\Uuid;
class PostcardController
{
    /**
     * Creates a new postcard entry.
     * @param mixed $frontImage
     * @param mixed $backImage
     * @param mixed $latitude
     * @param mixed $longitude
     * @return PostcardModel
     */
    public static function createPostcard($frontImage, $backImage, $latitude, $longitude)
    {
        $stampCode = self::generateStampCode();
        $createdAt = date('Y-m-d H:i:s');

        $postcardData = [
            'stamp_code'   => $stampCode,
            'front_image'  => $frontImage,
            'back_image'   => $backImage,
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'created_at'   => $createdAt,
        ];

        $postcard = new PostcardModel($postcardData);
        $postcard->saveOrUpdate(UtilsDatabase::connect());
        return $postcard;
    }

    /**
     * Retrieves a postcard by its stamp code.
     * @param string $stampCode
     * @return PostcardModel|null
     */
    public static function getPostcardByStampCode(string $stampCode): ?PostcardModel
    {
        $stampCode = trim($stampCode);

        $pdo = UtilsDatabase::connect();
        $stmt = $pdo->prepare("SELECT * FROM postcards WHERE stamp_code = :stamp_code");
        $stmt->execute([':stamp_code' => $stampCode]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($data) 
        {
            return new PostcardModel($data);
        }
        return null;
    }

    /**
     * Generates a unique stamp code using UUID v6.
     * @return string
     */
    private static function generateStampCode()
    {
        return Uuid::uuid6()->toString();
    }
}

?>
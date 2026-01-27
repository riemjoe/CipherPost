<?php

namespace Postcardarchive\Models;

use PDO;
class PostcardModel
{
    private $id;
    private $stamp_code;
    private $front_image;
    private $back_image;
    private $latitude;
    private $longitude;
    private $created_at;
    

    public function __construct(array $parameters)
    {
        $this->id           = $parameters['id']             ?? null;
        $this->stamp_code   = $parameters['stamp_code']     ?? null;
        $this->front_image  = $parameters['front_image']    ?? null;
        $this->back_image   = $parameters['back_image']     ?? null;
        $this->latitude     = $parameters['latitude']       ?? null;
        $this->longitude    = $parameters['longitude']      ?? null;
        $this->created_at   = $parameters['created_at']     ?? null;
    }

    // Getters as one liners
    public function getId() { return $this->id; }
    public function getStampCode() { return $this->stamp_code; }
    public function getFrontImage() { return $this->front_image; }
    public function getBackImage() { return $this->back_image; }
    public function getLatitude() { return $this->latitude; }
    public function getLongitude() { return $this->longitude; }
    public function getCreatedAt() { return $this->created_at; }
    
    // Setters as one liners
    public function setStampCode($stamp_code) { $this->stamp_code = $stamp_code; }
    public function setFrontImage($front_image) { $this->front_image = $front_image; }
    public function setBackImage($back_image) { $this->back_image = $back_image; }
    public function setLatitude($latitude) { $this->latitude = $latitude; }
    public function setLongitude($longitude) { $this->longitude = $longitude; }

    /**
     * Saves or updates the postcard in the database.
     * @param PDO $pdo
     * @return void
     */
    public function saveOrUpdate(PDO $pdo)
    {
        if ($this->id === null) 
        {
            $stmt = $pdo->prepare("INSERT INTO postcards (stamp_code, front_image, back_image, latitude, longitude, created_at) VALUES (:stamp_code, :front_image, :back_image, :latitude, :longitude, :created_at)");
            $stmt->execute([
                ':stamp_code'   => $this->stamp_code,
                ':front_image'  => $this->front_image,
                ':back_image'   => $this->back_image,
                ':latitude'     => $this->latitude,
                ':longitude'    => $this->longitude,
                ':created_at'   => $this->created_at,
            ]);
            $this->id = $pdo->lastInsertId();
        } 
        else 
        {
            $stmt = $pdo->prepare("UPDATE postcards SET stamp_code = :stamp_code, front_image = :front_image, back_image = :back_image, latitude = :latitude, longitude = :longitude WHERE id = :id");
            $stmt->execute([
                ':stamp_code'   => $this->stamp_code,
                ':front_image'  => $this->front_image,
                ':back_image'   => $this->back_image,
                ':latitude'     => $this->latitude,
                ':longitude'    => $this->longitude,
                ':id'           => $this->id,
            ]);
        }
    }

    /**
     * Deletes the postcard from the database.
     * @param PDO $pdo
     * @return void
     */
    public function delete(PDO $pdo)
    {
        if ($this->id !== null) 
        {
            $stmt = $pdo->prepare("DELETE FROM postcards WHERE id = :id");
            $stmt->execute([':id' => $this->id]);
            $this->id = null;
        }
    }

    /**
     * Returns the postcard data as an associative array.
     * @return array{back_image: mixed, created_at: mixed, front_image: mixed, id: mixed, latitude: mixed, longitude: mixed, stamp_code: mixed}
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'stamp_code'    => $this->stamp_code,
            'front_image'   => $this->front_image,
            'back_image'    => $this->back_image,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'created_at'    => $this->created_at,
        ];
    }

    /**
     * Creates a PostcardModel instance from an associative array.
     * @param array $data
     * @return PostcardModel
     */
    public static function fromArray(array $data): PostcardModel
    {
        return new PostcardModel($data);
    }

    /**
     * Fetches a PostcardModel by its ID from the database.
     * @param PDO $pdo
     * @param int $id
     * @return PostcardModel|null
     */
    public static function fromId(PDO $pdo, int $id): ?PostcardModel
    {
        $stmt = $pdo->prepare("SELECT * FROM postcards WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) 
        {
            return new PostcardModel($data);
        }
        return null;
    }

    /**
     * Fetches a PostcardModel by its stamp code from the database.
     * @param PDO $pdo
     * @param string $stamp_code
     * @return PostcardModel|null
     */
    public static function fromStampCode(PDO $pdo, string $stamp_code): ?PostcardModel
    {
        $stmt = $pdo->prepare("SELECT * FROM postcards WHERE stamp_code = :stamp_code");
        $stmt->execute([':stamp_code' => $stamp_code]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) 
        {
            return new PostcardModel($data);
        }
        return null;
    }

    /**
     * Fetches all postcards from the database.
     * @param PDO $pdo
     * @return PostcardModel[]
     */
    public static function fetchAll(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM postcards");
        $postcards = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) 
        {
            $postcards[] = new PostcardModel($data);
        }
        return $postcards;
    }
}

?>
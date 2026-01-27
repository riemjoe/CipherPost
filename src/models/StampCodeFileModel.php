<?php

namespace Postcardarchive\Models;
class StampCodeFileModel
{
    private $stamp_code;
    private $private_key;

    public function __construct(array $parameters)
    {
        $this->stamp_code   = $parameters['stamp_code']     ?? null;
        $this->private_key  = $parameters['private_key']    ?? null;
    }

    // Getters as one liners
    public function getStampCode() { return $this->stamp_code; }
    public function getPrivateKey() { return $this->private_key; }

    // Setters as one liners
    public function setStampCode($stamp_code) { $this->stamp_code = $stamp_code; }
    public function setPrivateKey($private_key) { $this->private_key = $private_key; }

    public function toArray(): array
    {
        return [
            'stamp_code'   => $this->stamp_code,
            'private_key'  => $this->private_key,
        ];
    }
}


?>
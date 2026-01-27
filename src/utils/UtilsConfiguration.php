<?php

namespace Postcardarchive\Utils;
class UtilsConfiguration
{
    private $config;

    /**
     * Loads configuration from a JSON file.
     * @param string $configName Name of the configuration file (without extension)
     * @throws \Exception
     */
    public function __construct(string $configName)
    {
        $configDir = dirname(__DIR__, 2) . '/config/';
        $configPath = $configDir . $configName . '.conf.json';
        if (!file_exists($configPath)) 
        {
            throw new \Exception("Configuration file not found: " . $configPath);
        }
        $configContent = file_get_contents($configPath);
        $this->config = json_decode($configContent, true);
    }

    /**
     * Retrieves a configuration value by key.
     * @param string $key The configuration key
     * @param mixed $default Default value if key does not exist
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Retrieves all configuration values.
     * @return array
     */
    public function getAll(): array
    {
        return $this->config;
    }
}

?>
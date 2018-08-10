<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.06.2018
 */

namespace Taber\Podrygka\Sphinx;

/**
 * Class SearchSettings
 * @package Taber\Podrygka\Sphinx
 */
class SphinxSettings
{
    /**
     * @var integer
     */
    private $port;
    /**
     * @var string
     */
    private $host;
    /**
     * @var integer
     */
    private $timeout;

    /**
     * SearchSettings constructor.
     * @throws SphinxSearchClientException
     */
    public function __construct()
    {
        $settings = require(\Bitrix\Main\Application::getDocumentRoot() . '/bitrix/settings_extra.php');
        if (!$settings["sphinx"]["host"] || !$settings["sphinx"]["port"]) {
            throw new SphinxSearchClientException();  // нет файла настроек подключения к сфинкс
        } else {
            $this->port = $settings["sphinx"]["port"];
            $this->host = $settings["sphinx"]["host"];
            $this->timeout = $settings["sphinx"]["timeout"];
        }
    }

    /**
     * @return int
     */
    public function getSphinxPort():int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getSphinxHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getSphinxTimeout(): int
    {
        return $this->timeout;
    }
}
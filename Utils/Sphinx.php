<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.06.2018
 * Time: 9:23
 */

namespace Taber\Podrygka\Sphinx;
use Sphinx\SphinxClient;

/**
 * Class Sphinx
 * @package Taber\Podrygka\Sphinx
 */
class Sphinx
{
    /**
     * @var SphinxClient
     */
    private $sphinx;

    const SPHINX_DEFAULT_MAX_LIMIT = 30000;
    const SPHINX_DEFAULT_MIN_LIMIT = 24;

    /**
     * Sphinx constructor.
     */
    public function __construct()
    {
        $this->sphinx = new \Sphinx\SphinxClient();

        // подключаемся к серверу sphinx
        $settings = new SphinxSettings();
        $this->sphinx->setServer($settings->getSphinxHost(), $settings->getSphinxPort());
        $this->sphinx->setConnectTimeout($settings->getSphinxTimeout());

        // настройки поиска
        $this->sphinx->setMatchMode(\Sphinx\SphinxClient::SPH_MATCH_EXTENDED2);
    }

    /**
     * возвращает сконфигурированный подключенный sphinx
     *
     * @return null|SphinxClient
     */
    public function getConfiguredSphinx(): ?SphinxClient
    {
        return $this->sphinx;
    }
}
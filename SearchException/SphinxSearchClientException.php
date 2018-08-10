<?php

namespace Taber\Podrygka\Sphinx;

use Sphinx\SphinxClient;

/**
 * Class SphinxSearchClientException
 * @package Taber\Podrygka\Sphinx
 */
class SphinxSearchClientException extends SearchException
{
    /**
     * SphinxSearchClientException constructor.
     */
    public function __construct()
    {
        $message = 'Не удалось загрузить настройки подключения к sphinx. Sphinx не подключен.';

        parent::__construct(
            $message,
            parent::SPHINX_SEARCH_CLIENT_ERROR
        );
    }
}
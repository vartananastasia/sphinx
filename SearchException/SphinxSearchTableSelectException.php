<?php

namespace Taber\Podrygka\Sphinx;

use Sphinx\SphinxClient;

/**
 * Class SphinxSearchTableSelectException
 * @package Taber\Podrygka\Sphinx
 */
class SphinxSearchTableSelectException extends SearchException
{
    /**
     * SphinxSearchTableSelectException constructor.
     * @param SphinxClient $sphinxClient
     */
    public function __construct(SphinxClient $sphinxClient)
    {
        $message = 'Sphinx ошибки при выборке из таблицы. Текст ошибки: ' . $sphinxClient->getLastError();

        parent::__construct(
            $message,
            parent::SPHINX_SEARCH_TABLE_SELECT_ERROR
        );
    }
}
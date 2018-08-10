<?php

namespace Taber\Podrygka\Sphinx;

use Sphinx\SphinxClient;

/**
 * Class SphinxSearchException
 * @package Taber\Podrygka\Sphinx
 */
class SphinxSearchException extends SearchException
{
    /**
     * SphinxSearchException constructor.
     * @param SphinxClient $sphinx_client
     */
    public function __construct(SphinxClient $sphinx_client)
    {
        $message = 'Sphinx base exception. ';

        parent::__construct(
            $message . '. Sphinx error text: ' . $sphinx_client->getLastError(),
            parent::SPHINX_SEARCH_ERROR
        );
    }
}
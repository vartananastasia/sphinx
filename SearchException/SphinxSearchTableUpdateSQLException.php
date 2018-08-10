<?php

namespace Taber\Podrygka\Sphinx;

use Bitrix\Main\DB\SqlQueryException;

/**
 * Class SphinxSearchTableUpdateSQLException
 * @package Taber\Podrygka\Sphinx
 */
class SphinxSearchTableUpdateSQLException extends SearchException
{
    /**
     * SphinxSearchTableUpdateSQLException constructor.
     * @param SqlQueryException $e
     */
    public function __construct(SqlQueryException $e)
    {
        $message = 'SQL ошибки при обновлении таблицы. Текст ошибки: ' . $e->getMessage();

        parent::__construct(
            $message,
            parent::SPHINX_SEARCH_TABLE_UPDATE_SQL_ERROR
        );
    }
}
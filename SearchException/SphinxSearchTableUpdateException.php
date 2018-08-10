<?php

namespace Taber\Podrygka\Sphinx;

/**
 * Class SphinxSearchTableUpdateException
 * @package Taber\Podrygka\Sphinx
 */
class SphinxSearchTableUpdateException extends SearchException
{
    /**
     * SphinxSearchTableUpdateException constructor.
     */
    public function __construct()
    {
        $message = 'Обновление таблицы поиска sphinx не прошло. Вернулся пустой массив товаров от API bitrix';

        parent::__construct(
            $message,
            parent::SPHINX_SEARCH_TABLE_UPDATE_ERROR
        );
    }
}
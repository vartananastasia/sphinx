<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 04.06.2018
 * Time: 16:22
 */

namespace Taber\Podrygka\Sphinx;

use Taber\Podrygka\TaberLogs\TaberExceptionLog;

/**
 * Class SearchException
 * @package Taber\Podrygka\Sphinx
 */
class SearchException extends \Exception
{
    // файл для записи лога ошибок
    const LOG_FILE = '_log/sphinx_search_log.txt';

    // коды ошибок
    const SPHINX_SEARCH_ERROR = 11121;
    const SPHINX_SEARCH_TABLE_UPDATE_ERROR = 11122;
    const SPHINX_SEARCH_TABLE_UPDATE_SQL_ERROR = 11123;
    const SPHINX_SEARCH_CLIENT_ERROR = 11124;
    const SPHINX_SEARCH_TABLE_SELECT_ERROR = 11125;

    /**
     * SearchException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        self::writeTxtLog();  // при возникновении ошибки сразу пишет ее в лог файл
    }

    /**
     * Запись лога ошибок в БД
     */
    public function writeTxtLog()
    {
        new TaberExceptionLog($this);
    }

    /**
     * строковый вывод сообщения об ошибке с указанием кода ошибки
     *
     * @return string
     */
    public function __toString()
    {
        return "EXCEPTION_CODE={$this->code} " . parent::__toString();
    }
}
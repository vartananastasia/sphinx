<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 04.06.2018
 * Time: 9:17
 */

namespace Taber\Podrygka\Sphinx;


/**
 * поисковый запрос
 *
 * Class SearchRequest
 * @package TaberTaber\Podrygka\Sphinx
 */
class SearchRequest
{
    /**
     * @var string
     */
    private $string;
    /**
     * @var string
     */
    private $keyString;

    /**
     * SearchRequest constructor.
     * @param string $string
     */
    public function __construct(string $string = '')
    {
        $this->string = $string;
        $key_words = [];
        $request_key_string = '';
        $request_string = preg_split('/[\s,-]+/', $this->string, 5);
        if ($request_string) {
            foreach ($request_string as $value) {
                if (strlen($value) > 3) {
                    $key_words[] .= "(" . $value . " | *" . $value . "*)";
                }
            }
            $request_key_string = implode(" & ", $key_words);
        }
        $this->keyString = $request_key_string;
    }

    /**
     * @return string
     */
    public function getRequestString(): string
    {
        return $this->string;
    }

    /**
     * @param $filter
     * @return string
     */
    public function addFilterToRequest($filter): string
    {
        // проверяем поля есть ли они в таблице и пишем их в фильтр
        $table_fields = SearchTable::getSearchTableFields();
        foreach ($filter as $key => $field) {
            if (in_array($key, $table_fields)) {
                $this->keyString .= " @{$key} {$field} ";
            }
        }
        return $this->keyString;
    }

    /**
     * @return string
     */
    public function getRequestKeyString(): string
    {
        return $this->keyString;
    }
}
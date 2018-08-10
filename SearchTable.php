<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 05.06.2018
 * Time: 10:48
 */

namespace Taber\Podrygka\Sphinx;

use Bitrix\Main\Application,
    Bitrix\Main\DB\SqlQueryException;

/**
 * Class SearchTable
 * @package Taber\Podrygka\Sphinx
 */
class SearchTable
{
    const TABLE_NAME = 'sphinx_search_data';

    /**
     * все поля из таблицы поиска
     *
     * @return array
     */
    public static function getSearchTableFields()
    {
        return [
            // поля идентификаторы
            'iblock_id',  // id инфоблока
            'element_id',  // id элемента
            'updated',  // дата обновления записи
            // поля поиска
            'article',  // внешний код
            'title',  // название
            'brand',  // бренд
            'line',  // линейка продукта
            'category',  // категория
            'subcategory',  // подкатегория
            'details',  // подподкатегория
            'description',  // описание
            'articles_tp',  // артикулы торговых предложений
            'titles_tp',  // названия торговых предложений
            'brand_words',  // синонимы бренда
            // поля фильтрации
            'category_code',  // категория код
            'subcategory_code',  // подкатегория код
            'details_code',  // детализация код
            'brand_code',  // бренд код
            'product_line_id',  // ID линейки
            'product_marks_codes',  // метки товаров симв. (new,discount,gift,benefit,hit) - в одно поле к одному товару через запятую все его метки
            'country_code',  // страна код
            'country',  // страна имя
            'actions_codes',  // коды акций через запятую (vp_physicians_formula_june18,some_action_june18...)
            'availability',  // доступность товара в интернет магазине
            // поля сортировки
            'price',  // конечная стоимость
            'rating',  // рейтинг
            'sort',  // поле сортировки из ИБ
            'brand_sort',  // поле сортировки из ИБ брендов
            'section_sort',  // поле сортировки по разделам
        ];
    }

    /**
     * создает таблицу поиска если ее еще нет
     *
     * @throws \Bitrix\Main\Db\SqlQueryException
     */
    private static function createSearchTable()
    {
        Application::getConnection()->query(
            "create table if not exists " . self::TABLE_NAME . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            iblock_id int(4) not null default 0, 
            element_id int(11) not null default 0, 
            article char(32) not null default '', 
            title varchar(255) not null default '', 
            brand varchar(255) not null default '', 
            brand_words varchar(255) not null default '', 
            line varchar(255) not null default '', 
            category varchar(255) not null default '', 
            subcategory varchar(255) not null default '', 
            details varchar(255) not null default '', 
            description varchar(3000) not null default '', 
            articles_tp varchar(255) not null default '', 
            titles_tp varchar(255) not null default '', 
            category_code varchar(255) not null default '', 
            subcategory_code varchar(255) not null default '', 
            details_code varchar(255) not null default '', 
            brand_code varchar(255) not null default '', 
            product_line_id varchar(255) not null default '', 
            product_marks_codes varchar(255) not null default '', 
            country_code varchar(255) not null default '', 
            country varchar(255) not null default '', 
            actions_codes varchar(255) not null default '',
            price varchar(55) not null default '',
            rating varchar(255) not null default '',
            sort int(11) not null default 40000,
            availability varchar(1) not null default '',
            brand_sort int(11) not null default 500,
            section_sort varchar(30) not null default '',
            updated timestamp not null default current_timestamp,
            primary key (id));");
    }

    /**
     * обновление таблицы для поиска по товарам
     *
     * @return int
     * @throws SphinxSearchTableUpdateException
     * @throws SphinxSearchTableUpdateSQLException
     * @throws SqlQueryException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function updateSearchTable()
    {
        $limit = SearchData::SEARCH_DATA_LIMIT;
        $start = 1;
        $count = 0;
        $dropTable = true;
        $searchData = SearchData::getSearchData($start);
        while (count($searchData) > 0) {
            if ($start > 1) {
                $dropTable = false;
            }
            self::writeSearchTable($searchData, $dropTable);
            $start += $limit;
            $count += count($searchData);
            $searchData = SearchData::getSearchData($start);
        }
        return $count + count($searchData);
    }

    /**
     * пишет строки в таблицу
     *
     * @param $elements
     * @param bool $dropTable
     * @throws SphinxSearchTableUpdateException
     * @throws SphinxSearchTableUpdateSQLException
     */
    private function writeSearchTable($elements, $dropTable = true)
    {
        $sqlStrElements = '';
        if (count($elements) > 0) {
            try {
                // не чистим таблицу а удаляем ее и создаем заново, отлавливая sql ошибки выполнения
                if ($dropTable) {
                    self::dropSearchTable();
                    self::createSearchTable();
                }
                foreach ($elements as $key => $element) {
                    $sqlStrElements .= $element;
                    // выгружаем по 1000 записей чтобы не превысить объем запроса к SQL
                    if (($key % 1000 == 0 && $key != 0) || ($key + 1 == count($elements))) {
                        Application::getConnection()->query(
                            'INSERT INTO ' . self::TABLE_NAME . ' (
                            iblock_id, element_id, article, title, brand, line, category,
                            subcategory, details, description, articles_tp, titles_tp,
                            category_code, subcategory_code, details_code, sort, brand_code, product_line_id,
                            country_code, rating, actions_codes, product_marks_codes, price, 
                            country, brand_sort, section_sort, availability, brand_words) VALUES ' . substr($sqlStrElements, 0, -1) . ';'
                        );
                        $sqlStrElements = '';
                    }
                }
            } catch (SqlQueryException $e) {
                throw new SphinxSearchTableUpdateSQLException($e);
            }
        } else {
            // если выгрузка данных из инфоблока товаров не произошла не обновляем таблицу
            throw new SphinxSearchTableUpdateException();
        }
    }

    /**
     * удаляет таблицу
     *
     * @throws \Bitrix\Main\Db\SqlQueryException
     */
    private static function dropSearchTable()
    {
        Application::getConnection()->query(
            "drop table if exists " . self::TABLE_NAME . ";"
        );
    }

    /**
     * веса полей поисковой таблицы
     *
     * @return array
     */
    public static function getSearchTableFieldWeights()
    {
        return [
            'article' => 10000,
            'articles_tp' => 10000,
            'brand' => 10000,
            'brand_words' => 10000,
            'line' => 9000,
            'details' => 8000,
            'subcategory' => 7000,
            'category' => 6000,
            'title' => 1000,
            'titles_tp' => 500,
            'description' => 100,
        ];
    }
}
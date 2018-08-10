<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 08.06.2018
 * Time: 15:59
 */

namespace Taber\Podrygka\Sphinx;

use Bitrix\Main\Loader,
    Bitrix\Main\Application;

/**
 * Class SearchData
 * @package Taber\Podrygka\Sphinx
 */
class SearchData
{
    const SEARCH_DATA_LIMIT = 5000;

    /**
     * полный массив даннх для поиска в строках sql
     *
     * @param $start
     * @param int $limit
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\Db\SqlQueryException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getSearchData($start, $limit = self::SEARCH_DATA_LIMIT): array
    {
        Loader::includeModule('main');
        Loader::includeModule('highloadblock');
        $search_items = [];

        $elements_db = self::getProductsForSearchTable();
        $element_multi_props = self::getActionsAndMarksForSearchTable();
        $lines = self::getLinesForSearchTable();
        $sections = self::getSectionsForSearchTable();
        $offers = self::getOffersForSearchTable();
        $countries = self::getCountriesForSearchTable();
        $action_items = self::getItemsForActions();
        // формируем sql запрос на вставку в БД
        $i = 0;
        $end = $start + $limit;
        while ($item = $elements_db->fetch()) {  // входной массив разбит на части для загрузки в таблицу по $limit записей
            $i++;
            if ($i >= $start +1 && ($i <= $end)) {
                $name = self::clearTextForRequest($item["NAME"]);
                $brand = self::clearTextForRequest($item['PROPERTY_BRAND_NAME']);
                $brand_words = self::clearTextForRequest($item['PROPERTY_BRAND_TAGS']);
                $brand_code = self::clearTextForRequest($item['PROPERTY_BRAND_CODE']);
                $brand_sort = $item['PROPERTY_BRAND_SORT'];
                $line = self::clearTextForRequest($lines[$item["PROPERTY_PRODUCT_LINE_VALUE"]]["UF_NAME"]);
                $line_id = $item["PROPERTY_PRODUCT_LINE_VALUE"];
                $mark = $element_multi_props[$item["ID"]]["marks"];
                $act = $offers['products'][$item["ID"]];
                $action_codes = $action_items[$offers['products'][$item["ID"]]];
                $active_category = false;
                $category = self::clearTextForRequest(
                    $sections[$sections[$sections[$item["IBLOCK_SECTION_ID"]]['IBLOCK_SECTION_ID']]['IBLOCK_SECTION_ID']]["NAME"]
                );
                $category_sort = self::clearTextForRequest(
                    $sections[$sections[$sections[$item["IBLOCK_SECTION_ID"]]['IBLOCK_SECTION_ID']]['IBLOCK_SECTION_ID']]["SORT"]
                );
                $category_code = self::clearTextForRequest(
                    $sections[$sections[$sections[$item["IBLOCK_SECTION_ID"]]['IBLOCK_SECTION_ID']]['IBLOCK_SECTION_ID']]["CODE"]
                );
                $category_active = $sections[$sections[$sections[$item["IBLOCK_SECTION_ID"]]['IBLOCK_SECTION_ID']]['IBLOCK_SECTION_ID']]["ACTIVE"];
                $sub_category = self::clearTextForRequest(
                    $sections[$sections[$item["IBLOCK_SECTION_ID"]]['IBLOCK_SECTION_ID']]["NAME"]
                );
                $sub_category_code = self::clearTextForRequest(
                    $sections[$sections[$item["IBLOCK_SECTION_ID"]]['IBLOCK_SECTION_ID']]["CODE"]
                );
                $sub_category_sort = self::clearTextForRequest(
                    $sections[$sections[$item["IBLOCK_SECTION_ID"]]['IBLOCK_SECTION_ID']]["SORT"]
                );
                $sub_category_active = $sections[$sections[$item["IBLOCK_SECTION_ID"]]['IBLOCK_SECTION_ID']]["ACTIVE"];
                $details = self::clearTextForRequest($sections[$item["IBLOCK_SECTION_ID"]]["NAME"]);
                $details_code = self::clearTextForRequest($sections[$item["IBLOCK_SECTION_ID"]]["CODE"]);
                $details_sort = self::clearTextForRequest($sections[$item["IBLOCK_SECTION_ID"]]["SORT"]);
                $details_active = $sections[$item["IBLOCK_SECTION_ID"]]["ACTIVE"];
                $section_sort = $category_sort . $sub_category_sort . $details_sort;
                if (!$category_code && $sub_category_code) {
                    $section_sort = $sub_category_sort . $details_sort . '000';
                    $category_code = $sub_category_code;
                    $category = $sub_category;
                    $sub_category_code = $details_code;
                    $sub_category = $details;
                    $details_code = '';
                    $details = '';
                    if ($details_active == "Y" && $sub_category_active == "Y") {
                        $active_category = true;
                    }
                } elseif (!$category_code && !$sub_category_code) {
                    $section_sort = $details_sort . '000000';
                    $category_code = $details_code;
                    $category = $details;
                    $sub_category_code = '';
                    $sub_category = '';
                    $details_code = '';
                    $details = '';
                    $active_category = $details_active == "Y" ? true : false;
                } else {
                    if ($category_active == "Y" && $sub_category_active == "Y" && $details_active == "Y") {
                        $active_category = true;
                    }
                }
                $description = self::clearTextForRequest($item["DETAIL_TEXT"]);
                $articles = self::clearTextForRequest($offers[$item["ID"]]['articles']);
                $article_titles = self::clearTextForRequest($offers[$item["ID"]]['titles']);
                $country_code = self::clearTextForRequest($item["PROPERTY_COUNTRY_VALUE"]);
                $price = self::getPriceForSearchTable($act);
                $country = $countries[$country_code];
                $availability = $offers['availability'][$item["ID"]] ? 'y' : '';
                if ($active_category) {
                    $search_items[] = "({$item["IBLOCK_ID"]}, {$item["ID"]}, \"{$item["XML_ID"]}\", \"{$name}\", \"{$brand}\", \"{$line}\",
                \"{$category}\", \"{$sub_category}\", \"{$details}\", \"{$description}\", \"{$articles}\", \"{$article_titles}\", 
                \"{$category_code}\", \"{$sub_category_code}\", \"{$details_code}\", \"{$item["SORT"]}\", \"{$brand_code}\",
                \"{$line_id}\", \"{$country_code}\", \"{$item["PROPERTY_RATING_VALUE"]}\", \"{$action_codes}\", \"{$mark}\",
                \"{$price}\",\"{$country}\",\"{$brand_sort}\", \"{$section_sort}\", \"{$availability}\", \"{$brand_words}\"),";
                }
            }
        }
        unset($lines, $sections, $offers, $elements_db, $countries, $element_multi_props);
        return $search_items;
    }

    /**
     * товары из каталога
     *
     * @return \CIBlockResult|int
     */
    public static function getProductsForSearchTable(): ?\CIBlockResult
    {
        $catalog_iblock_id = \CIBlock::GetList([], ["CODE" => 'catalog'])->fetch()["ID"];

        $elements_db = \CIBlockElement::GetList(
            ['ID' => 'ASC'], ['IBLOCK_ID' => $catalog_iblock_id, 'ACTIVE' => 'Y'], false, false,
            [
                'ID',
                'NAME',
                'SORT',
                'IBLOCK_ID',
                'IBLOCK_SECTION_ID',
                'XML_ID',
                'DETAIL_TEXT',
                'PROPERTY_BRAND.NAME',
                'PROPERTY_BRAND.TAGS',
                'PROPERTY_COUNTRY',
                'PROPERTY_rating',
                'PROPERTY_BRAND.CODE',
                'PROPERTY_BRAND.SORT',
                'PROPERTY_PROMOTION',
                'PROPERTY_PRODUCT_LINE',
            ]
        );
        return $elements_db;
    }

    /**
     * торговые предложения
     *
     * @return array
     */
    public static function getOffersForSearchTable(): array
    {
        $offers = [];
        $catalog_offers_iblock_id = \CIBlock::GetList([], ["CODE" => 'catalog_offer', 'ACTIVE' => 'Y'])->fetch()["ID"];

        $offers_db = \CIBlockElement::GetList(
            ['ID' => 'ASC'], ['IBLOCK_ID' => $catalog_offers_iblock_id], false, false,
            [
                'ID',
                'NAME',
                'IBLOCK_ID',
                'PROPERTY_ARTICLE',
                'PROPERTY_PRODUCT.ID',
                'CATALOG_QUANTITY',
            ]
        );
        while ($offer = $offers_db->Fetch()) {
            $offers[$offer["PROPERTY_PRODUCT_ID"]]['articles'] .= ', ' . $offer["PROPERTY_ARTICLE_VALUE"];
            $offers[$offer["PROPERTY_PRODUCT_ID"]]['titles'] .= ', ' . $offer["NAME"];
            $offers['products'][$offer["PROPERTY_PRODUCT_ID"]] = $offer["ID"];
            $offers['availability'][$offer["PROPERTY_PRODUCT_ID"]] = $offer["CATALOG_QUANTITY"];
        }
        unset($offers_db);
        return $offers;
    }

    /**
     * цены для товаров
     *
     * @param $element_id
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function getPriceForSearchTable($element_id)
    {
        $currencyList = \Bitrix\Currency\CurrencyManager::getCurrencyList();
        $RUR = isset($currencyList['RUR']) ? 'RUR' : 'RUB';

        $minPrice = 0;
        $minPriceRUR = 0;
        $minPriceGroup = 0;
        $minPriceCurrency = "";

        $baseCurrency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();

        if ($arPrice = \CCatalogProduct::GetOptimalPrice(
            $element_id,
            1,
            array(2),
            'N',
            array(),
            1
        )) {
            $minPrice = $arPrice['RESULT_PRICE']['DISCOUNT_PRICE'];
            $minPriceCurrency = $baseCurrency;
            $minPriceRUR = \CCurrencyRates::ConvertCurrency($minPrice, $baseCurrency, $RUR);
            $minPriceGroup = $arPrice['PRICE']['CATALOG_GROUP_ID'];
        }

        $result = array(
            "MIN" => $minPrice,
            "MIN_RUB" => $minPriceRUR,
            "MIN_GROUP" => $minPriceGroup,
            "MIN_CURRENCY" => $minPriceCurrency
        );
        return $result["MIN_RUB"];
    }

    /**
     * разделы по вложенности для товаров
     *
     * @return array
     */
    public static function getSectionsForSearchTable(): array
    {
        $sections = [];
        $max_len = 0;
        $catalog_iblock_id = \CIBlock::GetList([], ["CODE" => 'catalog'])->fetch()["ID"];

        $sections_db = \CIBlockSection::GetList(
            ["SORT" => "ASC"], ['IBLOCK_ID' => $catalog_iblock_id], false, ['ID', 'IBLOCK_SECTION_ID', 'SORT', 'NAME', 'ACTIVE', 'CODE', 'SORT']
        );
        while ($elem = $sections_db->Fetch()) {
            $sections[$elem['ID']] = $elem;
            $sort_str_len = strlen((string)$elem['SORT']);
            if ($sort_str_len > $max_len) {
                $max_len = $sort_str_len;
            }
        }
        // прибавляем ведущие нули  для сортировки по разделам
        foreach ($sections as $id => $section) {
            $sort_len = strlen((string)$section['SORT']);
            if ($sort_len < $max_len) {
                $delta = $max_len - $sort_len;
                $i = 0;
                $delta_str = '';
                while ($i < $delta) {
                    $delta_str .= '0';
                    $i++;
                }
                $sections[$id]['SORT'] = $delta_str . $section["SORT"];
            }
        }
        unset($sections_db);
        return $sections;
    }

    /**
     * линейки
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getLinesForSearchTable(): array
    {
        $lines = [];

        $hldata = \Bitrix\Highloadblock\HighloadBlockTable::getList(
            ['filter' => ['TABLE_NAME' => 'hl_product_line']]
        )->fetch();
        $hlentity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
        $entity_data_class = $hlentity->getDataClass();
        $lines_db = $entity_data_class::getList(['select' => ['UF_XML_ID', 'UF_NAME']]);
        while ($line = $lines_db->fetch()) {
            $lines[$line['UF_XML_ID']] = $line;
        }
        unset($lines_db);
        return $lines;
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCountriesForSearchTable(): array
    {
        $countries = [];

        $hldata = \Bitrix\Highloadblock\HighloadBlockTable::getList(
            ['filter' => ['TABLE_NAME' => 'hl_product_country']]
        )->fetch();
        $hlentity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
        $entity_data_class = $hlentity->getDataClass();
        $countries_db = $entity_data_class::getList(['select' => ['ID', 'UF_XML_ID', 'UF_NAME']]);
        while ($country = $countries_db->fetch()) {
            $countries[$country["UF_XML_ID"]] = $country["UF_NAME"];
        }
        unset($countries_db);
        return $countries;
    }

    /**
     * @return array
     * @throws \Bitrix\Main\Db\SqlQueryException
     */
    public static function getActionsAndMarksForSearchTable(): array
    {
        $catalog_iblock_id = \CIBlock::GetList([], ["CODE" => 'catalog'])->fetch()["ID"];
        $elementMultiProps = [];
        $property_mark_code = \CIBlockElement::GetProperty($catalog_iblock_id, [], [], ["CODE" => "MARK"])->fetch()["ID"];

        $strSql = "SELECT IBLOCK_ELEMENT_ID, PROPERTY_" . $property_mark_code . " FROM b_iblock_element_prop_s" . $catalog_iblock_id;
        $rs2 = Application::getConnection()->query($strSql);
        while ($arElements = $rs2->Fetch()) {
            $element_mark_id = unserialize($arElements["PROPERTY_" . $property_mark_code])["VALUE"];
            foreach ($element_mark_id as $mark) {
                if ($mark) {
                    $elementMultiProps[$arElements["IBLOCK_ELEMENT_ID"]]["marks"] .= ', ' . $mark;
                }
            }
        }
        return $elementMultiProps;
    }

    /**
     * Очистка текста для корректности SQL
     *
     * @param $str
     * @return mixed
     */
    private static function clearTextForRequest($str)
    {
        return str_replace('"', '\'', trim($str));
    }


    /**
     * связь идет с торговым предложением, в ключе id торгового предложения
     * @return array
     */
    public static function getItemsForActions()
    {
        $action_items_iblock_id = \CIBlock::GetList([], ["CODE" => 'actions_items'])->fetch()["ID"];
        $action_items_db = \CIBlockElement::GetList(
            ['ID' => 'ASC'], ['IBLOCK_ID' => $action_items_iblock_id, 'ACTIVE' => 'Y'], false, false,
            [
                'ID',
                'NAME',
                'PROPERTY_ACTION.ID',
                'PROPERTY_ACTION.CODE',
                'PROPERTY_ELEMENT'
            ]
        );
        $itemActions = [];
        $actions = self::getActiveByDateActions();
        while ($action_item = $action_items_db->Fetch()) {
            if (array_key_exists($action_item["PROPERTY_ACTION_CODE"], $actions)) {
                foreach ($action_item["PROPERTY_ELEMENT_VALUE"] as $item) {
                    $itemActions[$item] .= ', ' . $action_item["PROPERTY_ACTION_CODE"];
                }
            }
        }
        return $itemActions;
    }

    /**
     * текущее кол-во активных акций для апдейта
     *
     * @return int
     */
    public static function checkActionsCount()
    {
        list($actions, $actions_db) = self::getActions();
        while ($action = $actions_db->Fetch()) {
            $actions[$action["ID"]] = $action["CODE"];
        }
        return count($actions);
    }

    /**
     * текущее кол-во активных акций для апдейта
     *
     * @return int
     */
    public static function getActiveByDateActions()
    {
        list($actions, $actions_db) = self::getActions();
        while ($action = $actions_db->Fetch()) {
            $actions[$action["CODE"]] = $action["NAME"];
        }
        return $actions;
    }

    /**
     * @return array
     */
    public static function getActions(): array
    {
        $actions = [];
        $action_iblock_id = \CIBlock::GetList([], ["CODE" => 'actions'])->fetch()["ID"];
        $actions_db = \CIBlockElement::GetList(
            ['ID' => 'ASC'], ['IBLOCK_ID' => $action_iblock_id, 'ACTIVE_DATE' => 'Y', 'ACTIVE' => 'Y'], false, false,
            [
                'ID',
                'NAME',
                'CODE'
            ]
        );
        return array($actions, $actions_db);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 25.06.2018
 */

namespace Taber\Podrygka\Sphinx;

use Bitrix\Main\Application,
    Bitrix\Main\Web\Uri;


/**
 * ссылки с фильтрами
 * Class SearchUrl
 * @package Taber\Podrygka\Sphinx
 */
class SearchUrl
{
    /**
     * разделитель для фильтров из ссылки
     */
    public const DELIMITER = '-is-';
    /**
     * разделитель в урл
     */
    public const URL_DELIMITER = '/';
    /**
     * сортировка по умолчанию если не указана в фильтре
     */
    public const SORT_DEFAULT = 'sort';
    public const SORT_ORDER_DEFAULT = \Sphinx\SphinxClient::SPH_SORT_ATTR_DESC;
    /**
     * сортировка действующая всегда
     */
    public const SORT_DEFAULT_ARTICLE = 'article';
    public const SORT_ORDER_DEFAULT_ARTICLE = \Sphinx\SphinxClient::SPH_SORT_ATTR_DESC;
    /**
     * сортировки из фильтра
     */
    public const SORT_BY = [
        'property_rating' => 'rating', // по рейтингу
        'property_offer_price' => 'price'  // по цене
    ];
    public const SORT_ORDER = [
        'desc' => \Sphinx\SphinxClient::SPH_SORT_ATTR_DESC,
        'asc' => \Sphinx\SphinxClient::SPH_SORT_ATTR_ASC
    ];
    /**
     * ссылки встречающиеся в базовом урле, по которым не надо фильтровать
     */
    public const BASE_URL = ['catalog', 'show'];
    /**
     * конкатенация параметров в урл
     */
    public const POST_DELIMITER = '&';
    public const CATEGORY_FILTER = [
        'category_code',
        'subcategory_code',
        'details_code'
    ];
    public const POST_FILTER = [
        'availability',
        'search',
        'showCount',
        'PAGEN_1'
    ];
    /**
     * переопределение фильтров из урл в поля в таблице поиска
     */
    public const URL_FILTER = [
        'brand' => 'brand_code',
        'product_line' => 'product_line_id',
        'country' => 'country_code',
        'mark' => 'product_marks_codes',
        'promotion' => 'actions_codes',
    ];
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $query;
    /**
     * @var array
     */
    private $filter;
    /**
     * @var array
     */
    private $urlParams;
    private $urlFilterParams;
    private $queryParams;
    private $sortByParam;
    private $sortOrderParam;

    const BRAND_URL = 'brand-is-';
    const ACTION_URL = 'promotion-is-';
    const LINE_URL = 'product_line-is-';
    const COUNTRY_URL = 'country-is-';
    const ALL_BRANDS = 'all_brands';
    const ALL_LINES = 'all_lines';
    const ALL_COUNTRIES = 'all_countries';
    const ALL_ACTIONS = 'all_actions';


    /**
     * SearchUrl constructor.
     * @throws \Bitrix\Main\SystemException
     */
    public function __construct()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $uriString = $request->getRequestUri();
        $uri = new Uri($uriString);
        $uri->getQuery();
        $this->url = $uri->getPath();
        $this->query = urldecode($uri->getQuery());
        self::setFilterParams();
    }

    /**
     * добавляет параметры фильтра в ссылку
     */
    public function setFilterParams()
    {
        $i = 0;
        $in_url = explode(self::URL_DELIMITER, $this->url);
        $this->urlFilterParams = [];

        foreach ($in_url as $url_params) {
            if ($url_params && !in_array($url_params, self::BASE_URL)) {
                $filter_url_param = explode(self::DELIMITER, $url_params);
                if (count($filter_url_param) == 2) {
                    $this->urlFilterParams[self::URL_FILTER[$filter_url_param[0]]] = $filter_url_param[1];
                } else {
                    $this->urlParams[self::CATEGORY_FILTER[$i]] = $url_params;
                    $i++;
                }
            }
        }
        if (strlen($this->query) > 0) {
            $post_data = explode(self::POST_DELIMITER, urldecode($this->query));
            foreach ($post_data as $post_datum) {
                $data = explode('=', $post_datum);
                if (in_array($data[0], self::POST_FILTER)) {
                    $this->queryParams[$data[0]] = $data[1];
                } elseif (array_key_exists($data[1], self::SORT_BY)) {
                    $this->sortByParam = self::SORT_BY[$data[1]];
                } elseif (array_key_exists($data[1], self::SORT_ORDER)) {
                    $this->sortOrderParam = self::SORT_ORDER[$data[1]];
                }
            }
        }
    }

    /**
     * возвращает массив параметров фильтра
     *
     * @return array
     */
    public function getFilterParams()
    {
        return [
            "query" => $this->queryParams ?? [],
            "url" => $this->urlParams ?? [],
            "filter_url_params" => $this->urlFilterParams ?? [],
            "sort_by" => $this->sortByParam ?? self::SORT_DEFAULT,
            "sort_order" => $this->sortOrderParam ?? self::SORT_ORDER_DEFAULT,
        ];
    }

    /**
     * формирует ссылку с брендом(удаляет бренд из ссылки если Все бренды)
     *
     * @param $brandCode
     * @return string
     */
    public function addBrandFilterToUrl($brandCode): string
    {
        if ($brandCode && $brandCode != self::ALL_BRANDS) {
            if (!array_key_exists('brand_code', $this->urlFilterParams)) {
                $this->filter = self::BRAND_URL . $brandCode;
            } else {
                $this->url = str_replace(
                    self::BRAND_URL . $this->urlFilterParams['brand_code'],
                    self::BRAND_URL . $brandCode, $this->url
                );
            }
        } elseif (($brandCode == self::ALL_BRANDS) && array_key_exists('brand_code', $this->urlFilterParams)) {
            $this->url = str_replace(self::BRAND_URL . $this->urlFilterParams['brand_code'] . '/', '', $this->url);
            $this->url = str_replace(self::LINE_URL . $this->urlFilterParams['product_line_id'] . '/', '', $this->url);
        }
        return self::getUrl();
    }

    /**
     * акции в ссылке для фильтра
     *
     * @param $actionCode
     * @return string
     */
    public function addActionFilterToUrl($actionCode): string
    {
        if ($actionCode && $actionCode != self::ALL_ACTIONS) {
            if (!array_key_exists('actions_codes', $this->urlFilterParams)) {
                $this->filter = self::ACTION_URL . $actionCode;
            } else {
                $this->url = str_replace(
                    self::ACTION_URL . $this->urlFilterParams['actions_codes'],
                    self::ACTION_URL . $actionCode, $this->url
                );
            }
        } elseif (($actionCode == self::ALL_ACTIONS) && array_key_exists('actions_codes', $this->urlFilterParams)) {
            $this->url = str_replace(self::ACTION_URL . $this->urlFilterParams['actions_codes'] . '/', '', $this->url);
        }
        return self::getUrl();
    }

    /**
     * формирует ссылку с линейкой(удаляет линейку из ссылки если Все линейки)
     *
     * @param $lineId
     * @return string
     */
    public function addLineFilterToUrl($lineId): string
    {
        if ($lineId && $lineId != self::ALL_LINES) {
            if (!array_key_exists('product_line_id', $this->urlFilterParams)) {
                $this->filter = self::LINE_URL . $lineId;
            } else {
                $this->url = str_replace(
                    self::LINE_URL . $this->urlFilterParams['product_line_id'],
                    self::LINE_URL . $lineId, $this->url
                );
            }
        } elseif (($lineId == self::ALL_LINES) && array_key_exists('product_line_id', $this->urlFilterParams)) {
            $this->url = str_replace(self::LINE_URL . $this->urlFilterParams['product_line_id'] . '/', '', $this->url);
        }
        return self::getUrl();
    }

    /**
     * Формирует ссылку со страной(удяляет страну из ссылки если Все страны)
     *
     * @param $countryCode
     * @return string
     */
    public function addCountryFilterToUrl($countryCode): string
    {
        if ($countryCode && $countryCode != self::ALL_COUNTRIES) {
            if (!array_key_exists('country_code', $this->urlFilterParams)) {
                $this->filter = self::COUNTRY_URL . $countryCode;
            } else {
                $this->url = str_replace(
                    self::COUNTRY_URL . $this->urlFilterParams['country_code'],
                    self::COUNTRY_URL . $countryCode, $this->url
                );
            }
        } elseif (($countryCode == self::ALL_COUNTRIES) && array_key_exists('country_code', $this->urlFilterParams)) {
            $this->url = str_replace(self::COUNTRY_URL . $this->urlFilterParams['country_code'] . '/', '', $this->url);
        }
        return self::getUrl();
    }

    /**
     * фильтр по странице каталога
     *
     * @param $pageNumber
     * @return string
     */
    public function addPageFilterToUrl($pageNumber)
    {
        if (strpos($this->query, 'PAGEN') !== false) {
            $this->query = preg_replace('/PAGEN_1=[0-9]*/', 'PAGEN_1=' . $pageNumber, $this->query);
        } elseif ($this->query) {
            $this->query .= '&PAGEN_1=' . $pageNumber;
        } else {
            $this->query = 'PAGEN_1=' . $pageNumber;
        }
        return self::getUrl();
    }

    /**
     * фильтр по доступности
     *
     * @return string
     */
    public function addAvailabilityFilterToUrl()
    {
        if (strpos($this->query, 'availability') !== false) {
            $this->query = preg_replace('/[&]*availability=y/', '', $this->query);
        } elseif ($this->query) {
            $this->query .= '&availability=y';
            $this->query = preg_replace('/PAGEN_1=[0-9]*/', 'PAGEN_1=1', $this->query);
        } else {
            $this->query = 'availability=y';
        }
        return self::getUrl();
    }

    /**
     * возвращает сформированную ссылку исходя из всех параметров(текущих и добавленных)
     *
     * @return string
     */
    public function getUrl(): string
    {
        $url = $this->filter ? $this->url . $this->filter . '/' : $this->url;
        $this->query ? ($url .= '?' . $this->query) : $url;
        return $url;
    }

    /**
     * есть ли установленный фильтр по бренду
     *
     * @return string
     */
    public function getBrandFilterFromUrl()
    {
        return $this->urlFilterParams["brand_code"] ?? '';
    }
}
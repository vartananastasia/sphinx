<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 15.06.2018
 */

namespace Taber\Podrygka\Sphinx;


/**
 * Формирование фильтров в каталоге
 *
 * Class SearchFilterData
 * @package Taber\Podrygka\Sphinx
 */
class SearchFilterData
{
    private $sphinx;
    private $request;

    private $filterParams;

    public function __construct(Sphinx $sphinx, SearchRequest $searchRequest, SearchUrl $searchUrl)
    {
        $this->sphinx = $sphinx->getConfiguredSphinx();
        $this->request = $searchRequest; // todo: учитывать слово фильтра при выводе брендов
        $this->filterParams = $searchUrl->getFilterParams();
    }

    /**
     * @return array
     * @throws SphinxSearchTableSelectException
     */
    public function getSectionFilterData(): array
    {
        $this->sphinx->setLimits(0, Sphinx::SPHINX_DEFAULT_MAX_LIMIT, Sphinx::SPHINX_DEFAULT_MAX_LIMIT, Sphinx::SPHINX_DEFAULT_MAX_LIMIT);
        $this->sphinx->SetArrayResult(TRUE);
        // выбираем поля разделов с их кодами, количество элементов sphinx считает в группировке
        $this->sphinx->setSelect('details, details_code, subcategory, subcategory_code, category, category_code, section_sort');
        $this->sphinx->SetSortMode(\Sphinx\SphinxClient::SPH_SORT_ATTR_ASC, 'section_sort');
        // получаем результат запроса от sphinx
        $sections = $this->sphinx->query($this->request->getRequestKeyString())['matches'];
        if (!$this->sphinx->getLastError()) {
            $filter_sections = self::structureSections($sections);
        } else {
            throw new SphinxSearchTableSelectException($this->sphinx);
        }
        $filter_sections = $this->setActiveSections($filter_sections);

        return $filter_sections;
    }

    /**
     * перекладываем в массив с вложенностями подразделов
     *
     * @param array $sections
     * @return array
     */
    public static function structureSections(array $sections)
    {
        $filter_sections = [];
        foreach ($sections as $section) {
            $section = $section['attrs'];
            if ($section['details_code']) {  // если все есть
                $filter_sections[$section['category_code']]['in'][$section['subcategory_code']]['in'][$section['details_code']]['name'] = $section['details'];
                $filter_sections[$section['category_code']]['in'][$section['subcategory_code']]['in'][$section['details_code']]['count'] += 1;
                $filter_sections[$section['category_code']]['count'] += 1;
                $filter_sections[$section['category_code']]['name'] = $section['category'];
                $filter_sections[$section['category_code']]['in'][$section['subcategory_code']]['count'] += 1;
                $filter_sections[$section['category_code']]['in'][$section['subcategory_code']]['name'] = $section['subcategory'];
            } elseif ($section['subcategory_code']) {  // если нет подподкатегории
                $filter_sections[$section['category_code']]['in'][$section['subcategory_code']]['name'] = $section['subcategory'];
                $filter_sections[$section['category_code']]['in'][$section['subcategory_code']]['count'] += 1;
                $filter_sections[$section['category_code']]['count'] += 1;
                $filter_sections[$section['category_code']]['name'] = $section['category'];
            } else {  // если нет подкатегории и подподкатегории
                $filter_sections[$section['category_code']]['count'] += 1;
                $filter_sections[$section['category_code']]['name'] = $section['category'];
            }
        }
        return $filter_sections;
    }


    /**
     * @return array
     * @throws SphinxSearchTableSelectException
     */
    public function getBrandFilterData(): array
    {
        $brand_filter = [];
        $filter_fields = array_merge($this->filterParams['url'], $this->filterParams['filter_url_params'], $this->filterParams["query"]);

        $this->sphinx->setLimits(0, Sphinx::SPHINX_DEFAULT_MAX_LIMIT, Sphinx::SPHINX_DEFAULT_MAX_LIMIT, Sphinx::SPHINX_DEFAULT_MAX_LIMIT);
        $this->sphinx->SetArrayResult(TRUE);
        $this->sphinx->setSelect('brand, brand_sort, country_code, country, product_line_id, line, brand_code, actions_codes');
        $this->sphinx->SetSortMode(\Sphinx\SphinxClient::SPH_SORT_ATTR_ASC, 'brand_sort');
        /**
         * Выводятся все акции для текущего фильтра по бренду линейке и стране,
         * поэтому фильтр по акции не нужен
         * и мы его ансетим
         */
        unset($filter_fields["actions_codes"]);
        $this->request->addFilterToRequest($filter_fields);

        $filter_data = $this->sphinx->query($this->request->getRequestKeyString())['matches'];
        $action_names = SearchData::getActiveByDateActions();
        if (!$this->sphinx->getLastError()) {
            $brand_filter['action'] = [];
            foreach ($filter_data as $filter_datum) {
                $actions = explode(', ', $filter_datum["attrs"]["actions_codes"]);
                foreach ($actions as $action) {
                    if ($action && $action_names[$action]) {
                        $brand_filter["action"][$action]['name'] = $action_names[$action];
                    }
                }
                $brand = $filter_datum['attrs']['brand'];
                $brand ? $brand_filter['brand'][$filter_datum['attrs']['brand_code']]['name'] = $brand : false;
                $line = $filter_datum['attrs']['line'];
                $line ? $brand_filter['line'][$filter_datum['attrs']['product_line_id']]['name'] = $line : false;
                $country = $filter_datum['attrs']['country'];
                $country ? $brand_filter['country'][$filter_datum['attrs']['country_code']]['name'] = $country : false;
            }
            // помечаем активные бренд линейку и страну
            $brand_filter = $this->setActiveBrand($brand_filter);
        } else {
            throw new SphinxSearchTableSelectException($this->sphinx);
        }
        return $brand_filter;
    }

    /**
     * @param $filter_sections
     * @return mixed
     */
    public function setActiveSections($filter_sections)
    {
        if (array_key_exists('category_code', $this->filterParams["url"])) {
            $filter_sections[$this->filterParams["url"]['category_code']]['active'] = true;
            if (array_key_exists('subcategory_code', $this->filterParams["url"])) {
                $filter_sections[$this->filterParams["url"]['category_code']]['in'][$this->filterParams["url"]['subcategory_code']]['active'] = true;
                if (array_key_exists('details_code', $this->filterParams["url"])) {
                    $filter_sections[$this->filterParams["url"]['category_code']]['in'][$this->filterParams["url"]['subcategory_code']]['in'][$this->filterParams["url"]['details_code']]['active'] = true;
                }
            }
        }
        return $filter_sections;
    }

    /**
     * @param $brand_filter
     * @return mixed
     */
    public function setActiveBrand($brand_filter)
    {
        if (array_key_exists('brand_code', $this->filterParams['filter_url_params'])) {
            $active_brand = $this->filterParams['filter_url_params']['brand_code'];
            $brand_filter['brand'][$active_brand]['active'] = true;
            $brand_filter['active_brand'] = $brand_filter['brand'][$active_brand]['name'];
        }
        if (array_key_exists('actions_codes', $this->filterParams['filter_url_params'])) {
            $active_action = $this->filterParams['filter_url_params']['actions_codes'];
            $brand_filter['action'][$active_action]['active'] = true;
            $brand_filter['active_action'] = $brand_filter['action'][$active_action]['name'];
        }
        if (array_key_exists('product_line_id', $this->filterParams['filter_url_params'])) {
            $active_line = $this->filterParams['filter_url_params']['product_line_id'];
            $brand_filter['line'][$active_line]['active'] = true;
            $brand_filter['active_line'] = $brand_filter['line'][$active_line]['name'];
        }
        if (array_key_exists('country_code', $this->filterParams['filter_url_params'])) {
            $active_country = $this->filterParams['filter_url_params']['country_code'];
            $brand_filter['country'][$active_country]['active'] = true;
            $brand_filter['active_country'] = $brand_filter['country'][$active_country]['name'];
        }
        return $brand_filter;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 04.06.2018
 * Time: 9:23
 */

namespace Taber\Podrygka\Sphinx;

use Sphinx\SphinxClient;


/**
 * поиск sphinx
 *
 * Class Search
 * @package TaberTaber\Podrygka\Sphinx
 */
class Search
{
    /**
     * @var SearchRequest
     */
    private $request;
    /**
     * @var \Sphinx\SphinxClient
     */
    private $sphinx;
    /**
     * @var SearchUrl
     */
    private $searchUrl;
    /**
     * @var string
     */
    private $searchSource;
    /**
     * @var array
     */
    private $searchResult;
    /**
     * @var array
     */
    private $resultElementIds;
    /**
     * @var array
     */
    private $filter;
    /**
     * @var int
     */
    private $totalCount;
    /**
     * @var int
     */
    private $limit;
    /**
     * @var int
     */
    private $offset;

    public const SORT_ORDER = [
        \Sphinx\SphinxClient::SPH_SORT_ATTR_DESC => 'desc',
        \Sphinx\SphinxClient::SPH_SORT_ATTR_ASC => 'asc'
    ];

    /**
     * Search constructor.
     * @param Sphinx $sphinx
     * @param SearchUrl $searchUrl
     */
    public function __construct(Sphinx $sphinx, SearchUrl $searchUrl)
    {
        $this->sphinx = $sphinx->getConfiguredSphinx();
        $this->searchUrl = $searchUrl;
        $this->filter = [];
        $this->searchResult = [];
        $this->resultElementIds = [];
        $this->totalCount = 0;
        $this->limit = 0;
        $this->offset = 0;
    }

    /**
     * @param SearchSource $searchSource
     * @param bool $debug
     * @throws SphinxSearchException
     */
    public function find(SearchSource $searchSource, $debug = false): void
    {
        // забираем параметры поиска из url
        list($urlFilter, $filter, $page, $word) = $this->filterParams();

        $this->offset = ($page - 1) * $this->limit;
        $request = new \Taber\Podrygka\Sphinx\SearchRequest($word);

        $this->request = $request;
        $this->searchSource = $searchSource;
        // настройка сортировки результатов
        if ($word) {
            // если указано слово сортируем по релевантности сфинкс
            if ($urlFilter['sort_by'] && $urlFilter['sort_by'] !== SearchUrl::SORT_DEFAULT) {
                $this->sphinx->setSortMode(SphinxClient::SPH_SORT_EXTENDED, $urlFilter['sort_by'] . ' ' . self::SORT_ORDER[$urlFilter['sort_order']] . ', @weight desc');
            } else {
                $this->sphinx->setSortMode(SphinxClient::SPH_SORT_RELEVANCE);
            }
        } else {
            // слова нет значит применяем дефолтные фильтры
            $this->sphinx->setSortMode(SphinxClient::SPH_SORT_EXTENDED, $urlFilter['sort_by'] . ' ' . self::SORT_ORDER[$urlFilter['sort_order']] . ', article desc');
        }
        // пагинация
        $this->sphinx->setLimits(0, Sphinx::SPHINX_DEFAULT_MAX_LIMIT, Sphinx::SPHINX_DEFAULT_MAX_LIMIT, Sphinx::SPHINX_DEFAULT_MAX_LIMIT);
        // устанока весов полей из таблицы поиска
        $this->sphinx->SetFieldWeights(SearchTable::getSearchTableFieldWeights());
        $this->request->addFilterToRequest($filter);
        if ($this->searchSource->getSourceName()) {
            // при поиске в определенном индексе
            $result = $this->sphinx->Query(
                    $this->request->getRequestKeyString(),
                    $this->searchSource->getSourceName()
                ) ?? [];
        } else {
            // при поиске по всем индексам(индекс не указан или указан не верно)
            $result = $this->sphinx->Query($this->request->getRequestKeyString()) ?? [];
        }
        if ($this->sphinx->GetLastWarning() && $debug) {
            echo "WARNING: " . $this->sphinx->GetLastWarning(); // выводим варнинги при включеном дебаге
        }
        if ($result === false) {
            throw new SphinxSearchException($this->sphinx);  // sphinx отработал с ошибкой
        }
        $this->searchResult = array_slice($result["matches"], $this->offset, $this->limit);
        $this->totalCount = count($result["matches"]);
        foreach ($this->searchResult as $element) {
            $this->resultElementIds["ID"][] = (int)$element["attrs"]["element_id"];
        }
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->searchResult ?? [];
    }

    /**
     * @return array
     */
    public function getResultElementIds(): array
    {
        return $this->resultElementIds;
    }

    /**
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return array
     */
    public function filterParams(): array
    {
        $urlFilter = $this->searchUrl->getFilterParams();
        $filter = array_merge($urlFilter['url'], $urlFilter['filter_url_params'], $urlFilter["query"]);
        $urlFilter['query']['PAGEN_1'] ? $page = $urlFilter['query']['PAGEN_1'] : $page = 1;
        $urlFilter['query']['showCount'] ? $this->limit = $urlFilter['query']['showCount'] : $this->limit = Sphinx::SPHINX_DEFAULT_MIN_LIMIT;
        $urlFilter['query']['search'] ? $word = $urlFilter['query']['search'] : $word = '';
        return array($urlFilter, $filter, $page, $word);
    }
}
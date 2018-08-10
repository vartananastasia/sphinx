<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 26.06.2018
 */

namespace Taber\Podrygka\Sphinx;

class  SearchPagination
{
    private $pageElementsAmount;
    private $pagesCount;
    private $currentPage;
    private $totalElementsCount;
    private $url;

    public const FIRST_PAGE = 1;
    public const DELTA_PAGES = 5;

    /**
     * SearchPagination constructor.
     * @param Search $search
     * @param SearchUrl $searchUrl
     */
    public function __construct(Search $search, SearchUrl $searchUrl)
    {
        $this->url = $searchUrl;
        $this->pageElementsAmount = $search->getLimit();
        $this->currentPage = $searchUrl->getFilterParams()["query"]["PAGEN_1"] ?? 1;
        $this->totalElementsCount = $search->getTotalCount();
        $this->pagesCount = ceil($this->totalElementsCount / $this->pageElementsAmount);
    }

    /**
     * @return int
     */
    public function getPageElementsAmount()
    {
        return $this->pageElementsAmount;
    }

    /**
     * @return float
     */
    public function getPagesCount()
    {
        return $this->pagesCount;
    }

    /**
     * @return int
     */
    public function getTotalElementsCount()
    {
        return $this->totalElementsCount;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    public function getNavString()
    {
        $pageNavigation = '';
        if ($this->pagesCount > 1) {
            $pageNavigation = '<div class="pagination"><a class="pagination__text" href="' . $this->url->addPageFilterToUrl(self::FIRST_PAGE) . '">В НАЧАЛО</a>
				<div class="pagination__pages">';
            $pageNavigation = $this->getLeftArrow($pageNavigation);
            $pageNavigation .= '<div class="pagination__items">';
            $pageNumber = $this->getStartPaginationNumber();
            $count = 0;
            while ($pageNumber <= $this->pagesCount) {
                $count++;
                if ($pageNumber != $this->currentPage) {
                    $pageNavigation .= '<a href = "' . $this->url->addPageFilterToUrl($pageNumber) . '" class="item" > ' . $pageNumber . '</a >';
                } else {
                    $pageNavigation .= '<span class="item active" > ' . $this->currentPage . '</span >';
                }
                $pageNumber++;
                if ($count == self::DELTA_PAGES) {
                    break;
                }
            }
            $pageNavigation .= '</div>';
            $pageNavigation = $this->getRightArrow($pageNavigation);
            $pageNavigation .= '</div><a class="pagination__text" href="' . $this->url->addPageFilterToUrl($this->pagesCount) . '">В КОНЕЦ</a>
		</div>';
        }
        return $pageNavigation;
    }

    /**
     * @return float|int
     */
    public function getStartPaginationNumber()
    {
        if ($this->pagesCount > 5 && $this->currentPage > 3) {
            if ($this->currentPage == $this->pagesCount) {
                $pageNumber = $this->currentPage - self::DELTA_PAGES + 1;
            } elseif ($this->pagesCount - $this->currentPage == 1) {
                $pageNumber = $this->currentPage - 3;
            } else {
                $pageNumber = $this->currentPage - round(self::DELTA_PAGES / 2) + 1;
            }
        } else {
            $pageNumber = self::FIRST_PAGE;
        }
        return $pageNumber;
    }

    /**
     * @param $pageNavigation
     * @return string
     */
    public function getRightArrow($pageNavigation): string
    {
        if ($this->currentPage < $this->pagesCount) {
            $pageNavigation .= '<a class="pagination__button pagination__button--right" href="' . $this->url->addPageFilterToUrl($this->currentPage + 1) . '">
			<i class="icon icon__arrow--grey--right"></i></a>';
        } else {
            $pageNavigation .= '<span class="pagination__button pagination__button--right">
			<i class="icon icon__arrow--grey--right"></i></span>';
        }
        return $pageNavigation;
    }

    /**
     * @param $pageNavigation
     * @return string
     */
    public function getLeftArrow($pageNavigation): string
    {
        if ($this->currentPage > 1) {
            $pageNavigation .= '<a class="pagination__button pagination__button--left" href="' . $this->url->addPageFilterToUrl($this->currentPage - 1) . '">
							<i class="icon icon__arrow--grey--right"></i>
							</a>';
        } else {
            $pageNavigation .= '<span class="pagination__button pagination__button--left">
							<i class="icon icon__arrow--grey--right"></i>
							</span>';
        }
        return $pageNavigation;
    }
}

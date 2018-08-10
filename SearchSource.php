<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 04.06.2018
 * Time: 16:04
 */

namespace Taber\Podrygka\Sphinx;

/**
 * список всех индексов в sphinx
 *
 * Class SearchSource
 * @package Taber\Podrygka\Sphinx
 */
class SearchSource
{
    /**
     * @var string
     */
    private $sourceName;

    /**
     * имена всех индексов из etc/sphinx/sphinx.config
     */
    const PRODUCTS_SEARCH = 'sphinx_index_products';

    /**
     * SearchSource constructor.
     * @param string $source_name
     */
    public function __construct(string $source_name = '')
    {
        switch ($source_name) {
            case self::PRODUCTS_SEARCH:
                $this->sourceName = $source_name;
                break;
            default:
                $this->sourceName = '';
                break;
        }
    }

    /**
     * @return string
     */
    public function getSourceName()
    {
        return $this->sourceName;
    }
}
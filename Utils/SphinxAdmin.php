<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 02.07.2018
 * Time: 11:45
 */

namespace Taber\Podrygka\Sphinx;

/**
 * страница для перезапуска sphinx
 *
 * Class SphinxAdmin
 * @package Taber\Podrygka\Sphinx
 */
class SphinxAdmin
{
    /**
     * @var UpdatingCommand
     */
    private $command;

    /**
     * SphinxAdmin constructor.
     * @param UpdatingCommand $updatingCommand
     */
    public function __construct(UpdatingCommand $updatingCommand)
    {
        $this->command = $updatingCommand;
    }

    /**
     * возвращает обьект PageForm для отрисовки формы в админке
     *
     * @return PageForm
     */
    public function createPageForm(): PageForm
    {
        $pageForm = new PageForm();

        $pageForm->addFormHtmlCode();
        // вывод лога
        $pageForm->addLogFileHtmlCode($this->command);

        return $pageForm;
    }
}
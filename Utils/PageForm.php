<?php

namespace Taber\Podrygka\Sphinx;

use Bitrix\Main\Type;

class PageForm
{
    /**
     * @var string
     */
    private $html;

    const DOWNLOADING_STATUS = [
        true => '<span style="color: green">Обновлено</span>',
        false => 'Пропущено',
    ];

    public function __construct(string $html = '<!--Форма создана через конструктор Taber\Podrygka\Sphinx\PageForm-->')
    {
        $this->html = $html;
    }

    /**
     * вывод формы на старницу
     */
    public function drawPageForm(): void
    {
        echo $this->html;
    }

    /**
     * добавить любой html код на страницу с формой
     *
     * @param string $html
     */
    public function addHtmlCode(string $html = ''): void
    {
        $this->html .= $html;
    }

    /**
     * добавить заголовок
     *
     * @param string $title
     */
    public function addTitleHtmlCode(string $title): void
    {
        $titleHtml = '
            <div class="adm-detail-title">
            ' . $title . '
            </div>
            ';

        $this->addHtmlCode($titleHtml);
    }

    /**
     * лог
     *
     * @param UpdatingCommand $updatingCommand
     */
    public function addLogFileHtmlCode(UpdatingCommand $updatingCommand): void
    {

        $logTableHtml = '';
        $downloadsInArr = array_reverse($updatingCommand->getDownloads());
        $updatesCount = count($downloadsInArr);  // нумерация апдейтов

        $logTableHtml .= '
            <div class="adm-detail-content" id="import_log"><div class="adm-detail-title">Лог апдейтов</div>
                <div class="adm-detail-content-item-block">
                    <table class="adm-detail-content-table edit-table" id="fias_les_import_edit_table">
                        <tbody>
                        <tr>
                            <td width="10%" class="adm-detail-content-cell-r"><b>№</b></td>
                            <td width="20%" class="adm-detail-content-cell-r"><b>Начало загрузки</b></td>
                            <td width="10%" class="adm-detail-content-cell-r"><b>Кол-во акций</b></span></td>
                            <td width="20%" class="adm-detail-content-cell-r"><b>Кол-во товаров</b></span></td>
                            <td width="20%" class="adm-detail-content-cell-r"><b>Статус</b></span></td>
                            <td width="20%" class="adm-detail-content-cell-r"><b>Загрузка закончена</b></span></td>
                        </tr>';

        foreach ($downloadsInArr as $download) {
            // добавляем информацию по логу загрузки в список
            $logTableHtml .= '
                        <tr>
                            <td width="10%" class="adm-detail-content-cell-r"><b>' . $updatesCount . '</b></td>
                            <td width="20%" class="adm-detail-content-cell-r"><b>' . Type\DateTime::createFromTimestamp($download["start"]) . '</b></td>
                            <td width="10%" class="adm-detail-content-cell-r"><b>' . $download["actions"] . '</b></span></td>
                            <td width="20%" class="adm-detail-content-cell-r"><b>' . $download["products"] . '</b></span></td>
                            <td width="20%" class="adm-detail-content-cell-r"><b>' . self::DOWNLOADING_STATUS[$download["reload_result"]] . '</b></span></td>
                            <td width="20%" class="adm-detail-content-cell-r"><b>' . Type\DateTime::createFromTimestamp($download["end"]) . '</b></span></td>
                        </tr>';
            $updatesCount--;  // нумерация по уменьшению
        }

        $logTableHtml .= '
                        </tbody>
                    </table>
                </div>
            </div>';

        $this->addHtmlCode($logTableHtml);
    }

    /**
     * кнопки для хард апдейта и обычного апдейта
     */
    public function addFormHtmlCode(): void
    {
        global $APPLICATION;
        $currentPage = $APPLICATION->GetCurPage();
        $form = ' 
        <div class="adm-detail-block" id="tabControl_layout">
        <div class="adm-detail-tabs-block" id="tabControl_tabs" style="left: 0px;">
        <span title="Перезапуск" id="tab_cont_edit1" class="adm-detail-tab adm-detail-tab-active adm-detail-tab-last" onclick="tabControl.SelectTab(\'edit1\');">Перезапуск</span></div>
        <div class="adm-detail-content-wrap">
        <form action="' . $currentPage . '" method="post" enctype="multipart/form-data">
            <div class="adm-detail-content-btns-wrap" style="left: 0px;">
            <div class="adm-detail-content-btns">
            <input type="hidden" name="Import" value="Y">
                <input type="submit" value="Принудительный перезапуск Sphinx">' . bitrix_sessid_post() . '
            </div>
            </div>
        </form>
        <form action="' . $currentPage . '" method="post" enctype="multipart/form-data">
            <div class="adm-detail-content-btns-wrap" style="left: 0px;">
            <div class="adm-detail-content-btns">
            <input type="hidden" name="Try" value="Y">
                <input type="submit" value="Проверочный перезапуск Sphinx" class="adm-btn-save">' . bitrix_sessid_post() . '
            </div>
            </div>
        </form>
        </div>
        </div>
        ';

        $this->addHtmlCode($form);
    }
}
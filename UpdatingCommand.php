<?php

namespace Taber\Podrygka\Sphinx;

use Bitrix\Main\Application,
    Bitrix\Main\IO\File,
    Bitrix\Main\IO\FileNotFoundException;

/**
 * Апдейтит таблицу sphinx если сейчас 7 утра
 * или если изменилось количество активных акций
 *
 * Class UpdatingCommand
 * @package Taber\Podrygka\Sphinx
 */
class UpdatingCommand
{
    /**
     * @var array
     */
    private $downloads;
    /**
     * файл загрузки лога апдейтов таблицы sphinx
     */
    public const UPLOADING_COMMAND_FILE_PATH = '/upload/sphinx_update/updating_command.json';
    /**
     * ежедневный обязательный апдейт в 7 утра
     */
    public const RELOAD_TIME = 7 * 60 * 60; // 7:00
    /**
     * дельта времени для проверки запуска обязательной перезагрузки
     */
    private const TIME_DELTA = 10 * 60; // 10 минут

    /**
     * UpdatingCommand constructor.
     */
    public function __construct()
    {
        // считываем файл json в котором хранится история апдейтов
        try {
            $downloadsInJson = File::getFileContents(Application::getDocumentRoot() . self::UPLOADING_COMMAND_FILE_PATH);
            $downloadsInArr = json_decode($downloadsInJson, true);
            $this->downloads = $downloadsInArr ? $downloadsInArr : [];
        } catch (FileNotFoundException $e) {
            File::putFileContents(Application::getDocumentRoot() . self::UPLOADING_COMMAND_FILE_PATH, '');
            $this->downloads = [];
        }
    }

    /**
     * запускаем апдейт
     *
     * @param bool $hard
     * @throws SphinxSearchTableUpdateException
     * @throws SphinxSearchTableUpdateSQLException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\DB\SqlQueryException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function start($hard = false): void
    {
        $actions = SearchData::checkActionsCount();
        $startTime = time();
        $reloadResult = false;
        if(!$hard) {
            if (count($this->downloads) > 0) {
                $previousUpdate = array_pop($this->downloads);
                $products = $previousUpdate["products"];
                /**
                 * если изменилось кол-во акций или сейчас 7 утра +- 10мин
                 */
                if ($previousUpdate["actions"] != $actions || self::checkReloadTime()) {
                    list($products, $reloadResult) = $this->update();
                }
                $this->downloads[] = $previousUpdate;
            } else {
                list($products, $reloadResult) = $this->update();
            }
        }else{
            list($products, $reloadResult) = $this->update();
        }
        $this->downloads[] = [
            "start" => $startTime,
            "actions" => $actions,
            "products" => $products,
            "today_reload_time" => self::getReloadTime(), // 07:00 сегодня
            "reload_result" => $reloadResult,
            "end" => time()
        ];
        self::saveStation();
    }


    /**
     * @return false|string
     */
    public static function getReloadTime()
    {
        return strtotime(date("Y-m-d")) + self::RELOAD_TIME;
    }

    /**
     * проверяем не подошло ли время обязательной перезагрузки
     *
     * @return bool
     */
    public static function checkReloadTime()
    {
        $nowTime = time();
        return abs($nowTime - self::getReloadTime()) < self::TIME_DELTA ? true : false;
    }

    /**
     * сохраняем состояние апдейта в файл
     */
    public function saveStation(): void
    {
        File::putFileContents(
            Application::getDocumentRoot() . self::UPLOADING_COMMAND_FILE_PATH,
            json_encode($this->downloads)
        );
    }

    /**
     * структура json файла
     *
     * @return array
     */
    public function getStructure(): array
    {
        return [[
            "start" => "timestamp",
            "actions" => "actions count",
            "products" => "products count",
            "today_reload_time" => "daily all data reload time", // 07:00
            "reload_result" => "bool true if reloaded",
            "end" => "timestamp"
        ],];
    }

    /**
     * @return array
     * @throws SphinxSearchTableUpdateException
     * @throws SphinxSearchTableUpdateSQLException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\DB\SqlQueryException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function update(): array
    {
        $products = SearchTable::updateSearchTable();
        $reloadResult = true;
        return array($products, $reloadResult);
    }

    /**
     * @return array
     */
    public function getDownloads(){
        return $this->downloads;
    }
}

<?php

namespace common\components\mts;

use Yii;
use yii\base\Component;
use GuzzleHttp\Client;
use \yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use GuzzleHttp\Psr7\Stream;

/**
 * Компонент для работы с API компании МТС
 */
class MtsApi extends Component {

    const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * @var string Логин в ЛК МТС
     */
    public $login;

    /**
     * @var string Пароль в ЛК МТС
     */
    public $password;
    
    public $timeout = 2;

    /**
     * @var string Путь до API 
     */
    public $base_uri = 'https://mrecord.mts.ru/api/v2/';

    /**
     * @var string Путь до папки, где будут сохраняться звонки
     */
    public $path = '@storage/web/call';

    /**
     * @var string Расширение аудио-файла звонка 
     */
    public $ext = 'mp3';
    /*
     * @var object Объект запроса
     */
    private $client;

    public function init() {
        parent::init();
        $this->client = new Client([
            'base_uri' => $this->base_uri,
            //'timeout'  => $this->timeout,
        ]);

        FileHelper::createDirectory(Yii::getAlias($this->path));
    }

    /**
     * Получает список корпоративных телефонов телефонов
     * @return array
     */
    public function getPhones() {
        $response = $this->client->get('numbers', [
            'auth' => [$this->login, $this->password]
        ]);
        $content = $response->getBody()->getContents();
        if (!empty($content)) {
            return array_keys(ArrayHelper::getValue(Json::decode($content), 'Value'));
        }
        return [];
    }

    /**
     * Получить отчет по записям на номере
     * @param string $number
     * @param string $dateStart
     * @param string $dateEnd
     * @return array
     */
    public function getRecs($number, $dateStart, $dateEnd) {
        $datetimeStart = new \DateTime($dateStart);
        $datetimeEnd = new \DateTime($dateEnd);
        $response = $this->client->request('GET', 'recs/' . $number . '/' . $datetimeStart->format(self::DATETIME_FORMAT) . '/' . $datetimeEnd->format(self::DATETIME_FORMAT), [
            'auth' => [$this->login, $this->password]
        ]);

        $content = $response->getBody()->getContents();
        if (!empty($content)) {
            return ArrayHelper::getValue(Json::decode($content), 'Value');
        }
        return [];
    }

    /**
     * Загрузка записи звонка
     * @param string $number
     * @param string $fileName
     * @return boolean
     */
    public function downloadRec($number, $fileName) {
        $this->client->request('GET', 'file/' . $number . '/' . $fileName, [
            'auth'    => [$this->login, $this->password],
            'save_to' => Yii::getAlias($this->path) . '/' . $fileName . '.' . $this->ext,
            'timeout' => 5
        ]);

        return true;
    }
}

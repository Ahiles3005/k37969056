<?php

/**
 * @author Ruslan
 * @email ahiles3005@gmail.com
 * @telegram ahiles3005
 */


class Albato
{
    private const ALBATO_HASH = 'https://h.albato.com/wh/38/1lftesp/xX0pOGfqNY556LM2iTT_UT2hKRb7RgLwhtPWbWnOF2E/';
    private const WEBHOOK_TEST = 'https://webhook.site/8c0cf400-9a3f-4b01-ad77-bd555a0cf93d';

    public static function init()
    {
        self::sendData();
        AddEventHandler('main', 'OnEndBufferContent', ['Albato', 'saveUtm']);
    }

    public static function saveUtm(): void
    {
        if (isset($_GET["utm_source"])) {
            setcookie("utm_source", $_GET["utm_source"], time() + 3600 * 24 * 30, "/");
        }
        if (isset($_GET["utm_medium"])) {
            setcookie("utm_medium", $_GET["utm_medium"], time() + 3600 * 24 * 30, "/");
        }
        if (isset($_GET["utm_campaign"])) {
            setcookie("utm_campaign", $_GET["utm_campaign"], time() + 3600 * 24 * 30, "/");
        }
        if (isset($_GET["utm_content"])) {
            setcookie("utm_content", $_GET["utm_content"], time() + 3600 * 24 * 30, "/");
        }
        if (isset($_GET["utm_term"])) {
            setcookie("utm_term", $_GET["utm_term"], time() + 3600 * 24 * 30, "/");
        }
    }


    private static function sendData(): void
    {
        $data = self::_prepareData();
        if (!empty($data)) {
            self::curl(self::ALBATO_HASH, $data);
            self::curl(self::WEBHOOK_TEST, $data);
        }
    }


    private static function curl(string $url, array $data): void
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $result = curl_exec($curl);
//        if (curl_exec($curl) === false) {
//            echo 'Curl error: ' . curl_error($curl);
//        }
        curl_close($curl);
    }


    private static function _prepareData(): array
    {
        $formId = (int) $_POST['WEB_FORM_ID'] ?? $_POST['sender_subscription'] ?? null;
        $name = '';
        $phone = '';
        $email = '';
        $message = '';
        $formName = '';
        $referer = '';
        switch ($formId) {
            case 1:
                $phone = $_POST['form_text_2'] ?? '';
                $message = $_POST['form_text_2'] ?? '';
                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                $formName = 'Заказать звонок';
                break;
            case 2:
                $name = $_POST['form_text_5'] ?? 'Без имени';
                $phone = $_POST['form_text_6'] ?? '';
                $email = $_POST['form_email_7'] ?? '';
                $message = $_POST['form_textarea_8'] ?? '';
                $referer = 'https://ms-hous.ru'.$_POST['form_hidden_11'] ?? $_SERVER['HTTP_REFERER'];
                $formName = 'Отправить заявку на этот дом';
                break;
            case 3:
                $name = $_POST['form_text_12'] ?? 'Без имени';
                $phone = $_POST['form_text_13'] ?? '';
                $email = $_POST['form_email_14'] ?? '';
                $message = $_POST['form_textarea_15'] ?? '';
                $referer = 'https://ms-hous.ru'.$_POST['form_hidden_25'] ?? $_SERVER['HTTP_REFERER'];
                $formName = 'Попросить скидку';
                break;
            case 'add':
                $email = $_POST['SENDER_SUBSCRIBE_EMAIL'];
                $referer = $_SERVER['HTTP_REFERER'];
                $formName = 'Подписка на рассылку';
                break;
            default:
                return [];
        }

        if (strlen($email) === 0 && strlen($phone) === 0) {
            return [];
        }

        return [
            'form_link' => $referer,
            'form_name' => $formName,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'cookies' => @$_COOKIE,
        ];
    }


    private static function _createMessage(array $request): string
    {
        $data = [
            //2
            'form_text_5' => 'Ваше имя',
            'form_text_6' => 'Ваш номер телефона',
            'form_email_7' => 'Ваш email',
            'form_textarea_8' => 'Ваше сообщение',
            'form_hidden_9' => 'Наименование товара	',
            'form_hidden_10' => 'Идентификатор товара',
            'form_hidden_11' => 'Ссылка на товар',
            'form_hidden_21' => 'Предложение',
            'form_hidden_22' => 'Цена',
            //3
            'form_text_12' => 'Ваше имя',
            'form_text_13' => 'Ваш номер телефона',
            'form_email_14' => 'Ваш email',
            'form_textarea_15' => 'Ваше сообщение',
            'form_hidden_16' => 'Наименование товара	',
            'form_hidden_17' => 'Идентификатор товара',
            'form_hidden_18' => 'Ссылка на товар',
            'form_hidden_19' => 'Предложение',
            'form_hidden_20' => 'Цена',
        ];

        $text = '';
        foreach ($request as $k => $v) {
            $nameFiled = $data[$k] ?? false;
            if (strlen($v) > 0 && $nameFiled !== false) {
                $text .= "{$nameFiled}: {$v}".PHP_EOL;
            }
        }
        return $text;
    }

}

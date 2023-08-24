<?php

/**
 * @author Ruslan
 * @email ahiles3005@gmail.com
 * @telegram ahiles3005
 */


class Albato
{
    private const ALBATO_HASH = 'https://h.albato.com/wh/38/1lftesp/woKbqddHLz3tsjk2-CGhfhjDg0mH04BQljbZMALJHQI/';

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
        $formId = (int)$_POST['WEB_FORM_ID'];
        $name = $_POST['f_name'];
        $phone = $_POST['f_phone'];
        $email = $_POST['f_email'];
        $message = $_POST['f_text'];
        $formName = $_POST['form_name'];
        $referer = $_SERVER['HTTP_REFERER'] ?? '';


        return [
            'form_link' => $referer,
            'form_name' => $formName,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message . PHP_EOL . self::_createMessage($_POST),
            'cookies' => @$_COOKIE,
        ];
    }


    private static function _createMessage(array $request): string
    {
        $data = [
            'f_project_name' => 'Название проекта',
        ];

        $text = '';
        foreach ($request as $k => $v) {
            $nameFiled = $data[$k] ?? false;
            if (strlen($v) > 0 && $nameFiled !== false) {
                $text .= "{$nameFiled}: {$v}" . PHP_EOL;
            }
        }
        return $text;
    }

}

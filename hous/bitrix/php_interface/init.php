<?

// ROISTAT CODE BEGIN
$roistatFilePath = __DIR__ . '/../../roistat/Roistat.php';
if( file_exists($roistatFilePath) ) {
    require_once $roistatFilePath;
    Roistat::init();
}
// ROISTAT CODE END


$albatoFilePath = __DIR__ . '/albato/Albato.php';
if( file_exists($albatoFilePath) ) {
    require_once $albatoFilePath;
    Albato::init();
}

function _getHighloadTableValues($arProperty)
{
    CModule::IncludeModule('highloadblock');

    $arResult = array();
    static $hlblockCache = array();
    static $directoryMap = array();
    static $hlblockClassNameCache = array();
    $tableName = $arProperty['USER_TYPE_SETTINGS']['TABLE_NAME'];

    if (!empty($tableName)) {
        if (!isset($hlblockCache[$tableName])) {
            $hlblockCache[$tableName] = \Bitrix\Highloadblock\HighloadBlockTable::getList(
                array(
                    'select' => array('TABLE_NAME', 'NAME', 'ID'),
                    'filter' => array('=TABLE_NAME' => $tableName)
                )
            )->fetch();
        }
        if (!empty($hlblockCache[$tableName])) {
            if (!isset($directoryMap[$tableName])) {
                $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblockCache[$tableName]);
                $hlblockClassNameCache[$tableName] = $entity->getDataClass();
                $directoryMap[$tableName] = $entity->getFields();
                unset($entity);
            }
            if (!isset($directoryMap[$tableName]['UF_XML_ID']))
                return $arResult;
            $entityDataClass = $hlblockClassNameCache[$tableName];

            $nameExist = isset($directoryMap[$tableName]['UF_NAME']);
            $fileExists = isset($directoryMap[$tableName]['UF_FILE']);
            $sortExist = isset($directoryMap[$tableName]['UF_SORT']);

            $listDescr['order'] = array();
            if ($sortExist) {
                $listDescr['order']['UF_SORT'] = 'ASC';
            }
            if ($nameExist)
                $listDescr['order']['UF_NAME'] = 'ASC';
            else
                $listDescr['order']['UF_XML_ID'] = 'ASC';
            $listDescr['order']['ID'] = 'ASC';
            /** @var \Bitrix\Main\DB\Result $rsData */
            $rsData = $entityDataClass::getList($listDescr);
            while ($arData = $rsData->fetch()) {
                if (!$nameExist)
                    $arData['UF_NAME'] = $arData['UF_XML_ID'];
                $arData['SORT'] = ($sortExist ? $arData['UF_SORT'] : $arData['ID']);
                $arResult[] = $arData;
            }
            unset($arData, $rsData);
        }
    }

    return $arResult;
}

function GetHighloadExtendValue($arProperty, $value)
{
    static $arItemCache = array();

    if (!isset($value['VALUE']) && !isset($value['ID']))
        return false;
    if (empty($arProperty['USER_TYPE_SETTINGS']['TABLE_NAME']))
        return false;

    $tableName = $arProperty['USER_TYPE_SETTINGS']['TABLE_NAME'];
    if (!isset($arItemCache[$tableName]))
        $arItemCache[$tableName] = array();

    if (!empty($value['ID']) && isset($arItemCache[$tableName]) && is_array($arItemCache[$tableName])) {
        foreach ($arItemCache[$tableName] as $arItem) {
            if ($arItem['ID'] == $value['ID']) {
                $value['VALUE'] = $arItem['UF_XML_ID'];
                break;
            }
        }
    }

    if (!isset($arItemCache[$tableName][$value['VALUE']])) {
        $arData = _getHighloadTableValues($arProperty);

        if (!empty($arData)) {
            if (is_array($arData)) {
                foreach ($arData as $arItem) {
                    $arItemCache[$tableName][$arItem['UF_XML_ID']] = $arItem;
                    if (isset($arItem['UF_XML_ID']) && ($arItem['UF_XML_ID'] == $value['VALUE'] || $arItem['ID'] = $value['ID'])) {
                        $arItem['VALUE'] = $arItem['UF_NAME'];
                        if (isset($arItem['UF_FILE']))
                            $arItem['FILE_ID'] = $arItem['UF_FILE'];
                    }
                }
            }
        }
    }

    if (isset($arItemCache[$tableName][$value['VALUE']])) {
        return $arItemCache[$tableName][$value['VALUE']];
    }
    return false;
}


\Bitrix\Main\EventManager::getInstance()->addEventHandler('main', 'OnEpilog', 'SetLandingMeta');
function SetLandingMeta()
{
    global $LANDING, $APPLICATION;
    if (isset($LANDING['PROPERTIES'])) {
        if (!empty($LANDING['PROPERTIES']['META_TITLE']['VALUE']['TEXT'])) {
            $APPLICATION->SetPageProperty('title', $LANDING['PROPERTIES']['META_TITLE']['VALUE']['TEXT']);
        }
        if (!empty($LANDING['PROPERTIES']['META_KEYWORDS']['VALUE']['TEXT'])) {
            $APPLICATION->SetPageProperty('keywords', $LANDING['PROPERTIES']['META_KEYWORDS']['VALUE']['TEXT']);
        }
        if (!empty($LANDING['PROPERTIES']['META_DESCRIPTION']['VALUE']['TEXT'])) {
            $APPLICATION->SetPageProperty('description', $LANDING['PROPERTIES']['META_DESCRIPTION']['VALUE']['TEXT']);
        }
    }
    //$APPLICATION->SetPageProperty('robots', "noindex, nofollow");
}

\Bitrix\Main\EventManager::getInstance()->addEventHandler('iblock', 'OnBeforeIBlockElementUpdate', 'CheckLandingUrl');
\Bitrix\Main\EventManager::getInstance()->addEventHandler('iblock', 'OnBeforeIBlockElementAdd', 'CheckLandingUrl');
function CheckLandingUrl(&$arFields)
{
    if ($arFields['IBLOCK_ID'] == 7) {
        if (substr($arFields['CODE'], -1, 1) != '/') {
            $arFields['CODE'] .= '/';
        }
    }
    return true;
}

\Bitrix\Main\EventManager::getInstance()->addEventHandler('main', 'OnGetFileSRC', 'OnGetFileSRCHandler');
function OnGetFileSRCHandler($arFields)
{
//    $contentType = explode('/', $arFields['CONTENT_TYPE']);
//    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log_dev_img.txt', __FILE__ . ':' . __LINE__ . PHP_EOL . print_r($arFields, true) . "\n", FILE_APPEND);
//    $fileSrc = '/upload/' . $arFields['SUBDIR'] . '/' . $arFields['FILE_NAME'];
//    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log_dev_img.txt', __FILE__ . ':' . __LINE__ . PHP_EOL . print_r($fileSrc, true) . "\n", FILE_APPEND);
//    if (CModule::IncludeModule("corvax.imgworker") && $contentType[0] == 'image') {
//        return CCorvaxImgWorker::createImg($fileSrc);
//    }
    return false;
}

\Bitrix\Main\EventManager::getInstance()->addEventHandler('main', 'OnBeforeEventAdd', 'OnBeforeEventAdd');
function OnBeforeEventAdd($event, $lid, &$arFields, $message_id, $files)
{
    if(isset($arFields['PRODUCT_PRICE']) && !$arFields['PRODUCT_PRICE']) {
        $arFields['PRODUCT_PRICE'] = 'по запросу';
    }
}


\Bitrix\Main\EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockElementAdd', 'ProjectRequest');
\Bitrix\Main\EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'ProjectRequest');
function ProjectRequest($arFields)
{
    if($arFields['ID'] > 0  && $arFields['IBLOCK_ID'] == 14) {
        $obElement = CIBlockElement::GetByID($arFields['ID'])->GetNextElement();

        $arItem = $obElement->GetFields();
        $arProperties = $obElement->GetProperties();

        foreach ($arProperties as &$arProp) {
            if($arProp['PROPERTY_TYPE'] == 'E' && $arProp['VALUE']) {
                $arValue = array();
                $arSort = array('sort' => 'asc');
                $arFilter = array('ID' => $arProp['VALUE']);
                $arSelect = array('ID', 'NAME');
                $arGroup = false;
                $arNavStartParams = false;
                $dbResult = CIBlockElement::GetList($arSort, $arFilter, $arGroup, $arNavStartParams, $arSelect);
                while($dbRes = $dbResult->GetNext())
                {
                    $arValue[$dbRes['ID']] = $dbRes['NAME'];
                }
                $arProp['DISPLAY_VALUE'] = $arValue;
            } else {
                $arProp = CIBlockFormatProperties::GetDisplayValue($obElement, $arProp, 'prsend');
            }
        }
        unset($arProp);

        $arSendFields = array(
            'COMMENT' => $arItem['PREVIEW_TEXT'],
            'DATE' => $arItem['DATE_CREATE'],
        );
        foreach ($arProperties as $arProp) {
            if(is_array($arProp['DISPLAY_VALUE']))
                $arProp['DISPLAY_VALUE'] = implode(', ', $arProp['DISPLAY_VALUE']);
            $arSendFields[$arProp['CODE']] = $arProp['DISPLAY_VALUE'];
        }

        CEvent::Send('NEW_APPLICATION', 's1', $arSendFields, 'N');
    }
}
function getReCaphaCode()
{
    return "6Le16pEUAAAAALSnV1OEBQiMih_Rx227yIv9BJZW";
}

function getReCaphaSecret()
{
    return "6Le16pEUAAAAAMLGhUQQ1SIMz1H3rBjAoMO4T7l5";
}

function CheckCapha($response)
{
    $status = false;

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => getReCaphaSecret(),
        'response' => $response
    ];
    $options = [
        'http' => [
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $verify = file_get_contents($url, false, $context);
    $captcha_success = json_decode($verify);
    if ($captcha_success->success == false) {
        $status = false;
    } else if ($captcha_success->success == true) {
        // сохраняем данные, отправляем письма, делаем другую работу. Пользователь не робот
        $status = true;
    }
    return $status;
}
?>
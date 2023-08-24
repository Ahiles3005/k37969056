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


function pre($item, $show_for = false)
{
    global $USER;
    if ($USER->IsAdmin() || $show_for == 'all') {
        if (!$item) echo ' <br />пусто <br />';
        elseif (is_array($item) && empty($item)) echo '<br />массив пуст <br />';
        else echo ' <pre>' . print_r($item, true) . ' </pre>';
    }
}

function cropStr($str, $size)
{
    if (mb_strlen($str, 'utf-8') > $size)
        $return_str = mb_substr($str, 0, mb_strpos($str, ' ', $size, 'utf-8'), 'utf-8') . " ...";
    else $return_str = $str;
    return $return_str;
}

function GetProperty($id)
{
    global $APPLICATION;
    $val = $APPLICATION->GetPageProperty($id);
    if ($val != "Y"): return false;
    else: return true; endif;
}

function ShowProperty($id)
{
    global $APPLICATION;
    $APPLICATION->AddBufferContent('GetProperty', $id);
}

/*
AddEventHandler('main', 'OnEndBufferContent', 'controller404', 1001);

function controller404(&$content) {
   if(defined('ERROR_404') && ERROR_404 == 'Y') {
        CHTTP::SetStatus('404 Not Found');
      $content = file_get_contents($_SERVER["DOCUMENT_ROOT"].'/bitrix/templates/.default/404');
      return false;
   }
}  
*/

\Bitrix\Main\EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockElementAdd', 'ProjectRequest');
\Bitrix\Main\EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'ProjectRequest');
function ProjectRequest($arFields)
{
    if ($arFields['ID'] > 0 && $arFields['IBLOCK_ID'] == 26) {
        $obElement = CIBlockElement::GetByID($arFields['ID'])->GetNextElement();

        $arItem = $obElement->GetFields();
        $arProperties = $obElement->GetProperties();

        foreach ($arProperties as &$arProp) {
            if ($arProp['PROPERTY_TYPE'] == 'E' && $arProp['VALUE']) {
                $arValue = array();
                $arSort = array('sort' => 'asc');
                $arFilter = array('ID' => $arProp['VALUE']);
                $arSelect = array('ID', 'NAME');
                $arGroup = false;
                $arNavStartParams = false;
                $dbResult = CIBlockElement::GetList($arSort, $arFilter, $arGroup, $arNavStartParams, $arSelect);
                while ($dbRes = $dbResult->GetNext()) {
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
            if (is_array($arProp['DISPLAY_VALUE']))
                $arProp['DISPLAY_VALUE'] = implode(', ', $arProp['DISPLAY_VALUE']);
            $arSendFields[$arProp['CODE']] = $arProp['DISPLAY_VALUE'];
        }

        CEvent::Send('NEW_APPLICATION', 's1', $arSendFields, 'N');
    }

    if ($arFields['ID'] > 0 && $arFields['IBLOCK_ID'] == 5) {
        \Bitrix\Main\Loader::includeModule('iblock');
        \Bitrix\Main\Loader::includeModule('catalog');


        $arPrice = CPrice::GetBasePrice($arFields['ID']);
        CIBlockElement::SetPropertyValuesEx($arFields['ID'], false, array("MIN_PRICE" => $arPrice["PRICE"]));


        $iblock_info = CCatalogSKU::GetInfoByProductIBlock($arFields['IBLOCK_ID']);

        $success = array();

        if (is_array($iblock_info) && !empty($iblock_info["SKU_PROPERTY_ID"])) {
            $rsOffers = CIBlockElement::GetList(array("PRICE" => "ASC",), array("IBLOCK_ID" => $iblock_info["IBLOCK_ID"], "ACTIVE" => "Y"), array("ID", "PROPERTY_" . $iblock_info["SKU_PROPERTY_ID"]));
            while ($arOffer = $rsOffers->GetNext()) {


                $offer_price = GetCatalogProductPrice($arOffer["ID"], 1); // 1 - это ID типа цены
                $product_id = $arOffer["PROPERTY_" . $iblock_info["SKU_PROPERTY_ID"] . "_VALUE"];

                // проверка
                if (array_key_exists($product_id, $success) && $success[$product_id] < $offer_price["PRICE"])
                    continue;

                CIBlockElement::SetPropertyValuesEx($product_id, false, array("MIN_PRICE" => $offer_price["PRICE"]));
                $success[$product_id] = $offer_price["PRICE"];
            }
        }

    }

}

if (!function_exists('getHBlock')) {
    function getHBlock($id = false)
    {

        CModule::IncludeModule("highloadblock");
        if (!$id) return false;
        $hlbl = $id;
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlbl)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        return $entity_data_class;
    }
}
if (!function_exists('getMessagerPhone')) {
    function getMessagerPhone($phone)
    {
        $hlb_class = getHBlock(6);
        $arResult = $hlb_class::getList(["filter" => ["UF_PHONE" => $phone]])->fetch();
        return $arResult;
    }
}


if (!function_exists('getMessagerPhones')) {
    function getMessagerPhones()
    {
        $hlb_class = getHBlock(6);
        $arResult = $hlb_class::getList()->fetchAll();
        return $arResult;
    }
}

if (!function_exists('getHTMLPhoneByType')) {
    function getHTMLPhoneByType($types, $phones)
    {
        $arResult = "";
        foreach ($types as $type) {
            switch ($type) {
                case 11: #telegram
                    $arResult .= "<a class='telegram' title='Написать в Telegram' target='_blank' href='https://telegram.me/" . $phones[$type] . "'></a>";
                    break;
                case 12:#viber
                    $arResult .= "<a class='viber' title='Написать в Viber' target='_blank' href='viber://chat?number=%2B" . $phones[$type] . "'></a>";
                    break;
                case 13:#whatsapp
                    $arResult .= "<a class='whatsapp' title='Написать в Whatsapp' target='_blank' href='https://wa.me/" . $phones[$type] . "'></a>";
                    break;
            }
        }
        return $arResult;
    }
}


?>
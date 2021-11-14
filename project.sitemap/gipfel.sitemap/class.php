<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) 
{
    die();
}
use Bitrix\Main;
use Bitrix\Main\Config;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;


Loc::loadMessages(__FILE__);

class siteMapComponent extends \CBitrixComponent
{
    
    static $cachePrefix = "gipfel_";
    static $cacheTTL = 432000;
    static $cacheDir = "/gipfel/";
    
    
    
    public function setCache($arResult, $cacheId, $cacheDir, $obCache, $cacheTag = false)
    {
	    $obCache->StartDataCache(self::$cacheTTL, $cacheId, $cacheDir);

	    if ($cacheTag)
	    {
		$GLOBALS['CACHE_MANAGER']->StartTagCache($cacheDir);
		$GLOBALS['CACHE_MANAGER']->RegisterTag($cacheTag);
		$GLOBALS['CACHE_MANAGER']->EndTagCache();
	    }

	    $obCache->EndDataCache(array("arResult" => $arResult));
        
    }
    
    
    public function onPrepareComponentParams($arParams)
    {
	    $this->tryParseInt($arParams['CACHE_TIME']);
	    $this->tryParseInt($arParams['IBLOCK_ID']);
	    $this->tryParseInt($arParams['SUBARRAY']);

	    $this->tryParseString($arParams['ROOT_MENU_TYPE']);

	    $this->tryParseBoolean($arParams['CACHE_FILTER']);
	    

	    return $arParams;
    }
	
	
	
    public function tryParseInt(&$fld, $default = false, $allowZero = false)
    {
	    $fld = intval($fld);
	    if(!$allowZero && !$fld && $default !== false)
		$fld = $default;
			
	    return $fld;
    }
	

    public function tryParseString(&$fld, $default = false)
    {
	    $fld = trim((string)$fld);
	    if(!strlen($fld) && $default !== false)
		$fld = $default;

	    $fld = htmlspecialcharsbx($fld);

	    return $fld;
    }
    
    public function tryParseBoolean(&$fld)
    {
	    $fld = $fld == 'Y';
	    return $fld;
    }
	
    public function getCatalogSection($iblockId)
    {
	if (!\Bitrix\Main\Loader::includeModule('iblock'))
            return false;
	

	$cacheId = $this->getIdCache($this->arParams);
	 
	$obCache = new \CPHPCache();
	

        if ($_REQUEST['clear_cache'] != "Y" && $obCache->InitCache(self::$cacheTTL, $cacheId, self::$cacheDir))
        {
	    $vars = $obCache->GetVars();
            $arResult['SECTIONS'] = $vars['arResult'];
        }
	else
	{
	    $rsSections = CIBlockSection::GetList(['DEPTH_LEVEL' => 'ASC', 'SORT' => 'ASC'], ['IBLOCK_ID' => $iblockId, 'GLOBAL_ACTIVE'=>'Y'], false, ['IBLOCK_ID', 'ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'SECTION_PAGE_URL']);

	    $arSectionLinc[0] = &$arResult['SECTIONS'];
	    while ($arSection = $rsSections->GetNext())
	    {
		$arSectionLinc[(int)$arSection['IBLOCK_SECTION_ID']]['CHILD'][$arSection['ID']] = $arSection;
		
		$arSectionLinc[$arSection['ID']] = &$arSectionLinc[(int)$arSection['IBLOCK_SECTION_ID']]['CHILD'][$arSection['ID']];
	    }
	    unset($arSectionLinc);
	    $arResult['SECTIONS'] = $arResult['SECTIONS']['CHILD'];
	

	if ($_REQUEST['clear_cache'] != "Y")

                $this->setCache($arResult['SECTIONS'], $cacheId, self::$cacheDir, $obCache, $cacheTag);    
	}
	    return $arResult['SECTIONS'];
	    
    }
    
    public function getIdCache($arParams)
    {
	    global $USER;
	    $arParams['USER_GROUPS'] = $USER->GetUserGroupString();
	    $strId = self::$cachePrefix . sha1(serialize($arParams));

	    return $strId;
    }
	
    public function getMenu($arParams)
    {
	    global $APPLICATION;

	    $curDir = $APPLICATION->GetCurDir();
	    if (!isset($arParams["ROOT_MENU_TYPE"]) && strlen($arParams["ROOT_MENU_TYPE"]) == 0)
	    {
		    $arParams["ROOT_MENU_TYPE"] = "top";
	    }

	    $menu = new CMenu($arParams["ROOT_MENU_TYPE"]);
	    $menu->Init($curDir, $arParams["USE_EXT"], $componentPath."/stub.php");
	    
	    $arResult['MENU'] = [];
	    $arResult['MENU'] = $menu->arMenu;

	    return $arResult['MENU'];
    }

    public function executeComponent()
    {
	
	if($this->startResultCache())
	{
	    $this->arResult['SECTIONS'] = $this->getCatalogSection($this->arParams["IBLOCK_ID"]);
	    $this->arResult['MENU'] = $this->getMenu($this->arParams);
	    $this->includeComponentTemplate();
	}
		
    }
}

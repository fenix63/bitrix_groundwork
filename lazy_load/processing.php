<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

?>

<?
if (\Bitrix\Main\Loader::includeModule("iblock")) {
    //От какого товара будет осуществляться вывод
    $startFrom = $_POST['startFrom'];

    //Получаем по 1 товару начиная с последнего отображенного
    $res = \Bitrix\Iblock\ElementTable::getList([
        'filter' => [
            'IBLOCK_ID' => 2,
            'IBLOCK_SECTION_ID' => 9
        ],
        'select' => ['ID', 'NAME', 'DETAIL_PICTURE'],
        'offset' => $startFrom,
        'limit' => 1
    ]);


    //Формируем массив с товарами
    $products = array();
    $i=0;
    while ($row = $res->fetch()) {
        $products[$i]['NAME'] = $row['NAME'];
        $products[$i]['PHOTO'] = CFile::GetPath(intval($row['DETAIL_PICTURE']));
        $i++;
    }


    //Превращаем массив товаров в json-строку для передачи через Ajax-запрос
    echo json_encode($products);
}

?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>

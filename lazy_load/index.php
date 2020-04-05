<?
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>

<?

use Bitrix\Main\IO,
    Bitrix\Main\Application;

if (\Bitrix\Main\Loader::includeModule("iblock")) {
    //получаем элементы из инфоблока каталоги Одежда - нижнее бельё (На D7)
    $res = \Bitrix\Iblock\ElementTable::getList([
        'filter' => [
            'IBLOCK_ID' => 2,
            'IBLOCK_SECTION_ID' => 9
        ],
        'select' => ['ID', 'NAME','DETAIL_PICTURE'],
        'offset' => 0,
        'limit' => 2
    ]);

    while ($row = $res->fetch())
    {
        $rows[] = $row;
        $path = CFile::GetPath(intval($row['DETAIL_PICTURE']));
        $file_dir[] = $path;
        //echo $path.'<br/>';
    }

//    echo '<pre>';
//    var_dump($rows);
//    echo '</pre>';
    ?>

    <div class="list" id="list">
        <?
        $i=0;
        foreach ($rows as $item) {

            ?>
            <div class="list-item">
                <div class="list-item__photo">
                    <img src="<?=$file_dir[$i]?>" alt="" />

                </div>
                <div class="list-item__name">
                    <?=$item['NAME']?>
                </div>
            </div>
        <?
            $i++;
            }
        ?>
    </div>
    <button id="more">Дальше</button>



<?}


//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>

<?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
?>

<?
    $uploaddir = '/OpenServer_5_4_0/domains/site3/test2/';
    $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

    // var_dump($_POST['filename']);
    // var_dump($_FILES);

    echo '<pre>';
    if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
        echo "Файл корректен и был успешно загружен.\n";
    } else {
        echo "Возможная атака с помощью файловой загрузки!\n";
    }

    echo 'Некоторая отладочная информация:';
    print_r($_FILES);

    print "</pre>";
?>


<?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

?>

<?
    // echo '<pre>';
    // print_r($_FILES);
    // echo '</pre>';
?>


<div class="form_wrap">
    <!-- <form name="user_form" enctype="multipart/form-data" method="POST" action="file_load.php"> -->
    <form name="user_form" enctype="multipart/form-data">
        <input name="userfile" type="file" value="" />
        <input type="submit" value="Загрузить файл" />
    </form>
</div>




<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="file_load.js"></script>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");

?>
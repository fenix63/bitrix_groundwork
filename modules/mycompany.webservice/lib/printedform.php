<?
/* Класс создания печатных форм на основе элементов ИБ */

namespace MyCompany\WebService;

use Exception;

require_once $_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php";
\Bitrix\Main\Loader::includeModule('iblock');

class PrintedForm{
    public $outputPath;
    public $outputFileName;
    public $blankPath;
    public $blankType;

    private $phpWord;
    private $tmpDirectory;

    /* Конструктор создает и сохраняет в файл в директорию upload */ 
    public function __construct($arrParams, $blankType, $mess = []) {
        $this->tmpDirectory = $_SERVER["DOCUMENT_ROOT"]."/upload/printedform/";
        $this->blankType = $blankType;

        $this->blankPath = $this->getBlankPath();
        
        $this->phpWord= new \PhpOffice\PhpWord\TemplateProcessor($this->blankPath);

        foreach ($arrParams as $key => $val){
            if (is_array($val)) {
                if (empty($val)) {
                    $val = '';
                } else {
                    $val = implode('<br>', $val);
                }
            } else {
                if (empty(trim($val))) {
                    $val = '';
                }
            }

            $this->phpWord->setValue($key, $val);
        }

        //Удаляем все теги, которые не удалось заполнить
        foreach ($mess as $key=>$message){
            $this->phpWord->setValue($key, '');
        }

        $this->outputFileName = time().'.docx';
        if(!file_exists($this->tmpDirectory)){
            mkdir($this->tmpDirectory, 0775, true);
        }

        $this->outputPath = $this->tmpDirectory.$this->outputFileName;
        $this->phpWord->saveAs($this->outputPath);
    }

    /* Метод для удаления физического файла */
    public function delete(){
        unlink($this->outputPath);
    }

    public function getPath(){
        return $this->outputPath;
    }

    public function getBlankPath(){
        return $_SERVER["DOCUMENT_ROOT"]."/blanks/".$this->blankType.'.docx';
    }
    
}

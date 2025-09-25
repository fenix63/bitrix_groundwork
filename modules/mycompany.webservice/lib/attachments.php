<?
namespace MyCompany\WebService;

/**
 * Создаем вложение
 */
class Attachments{
    public $pathFolder;
    public $attachmentName;
        
    /**
     * @param mixed $messageId
     * @param mixed $attachmentName
     * @param mixed $raw
     */
    public static function save($messageId, $attachmentName, $content){
        $pathFolder = $_SERVER['DOCUMENT_ROOT'].'/upload/attachments/'.$messageId;

        if (!file_exists($pathFolder)) {             
            static::newFolder($pathFolder);
        } 

		if(mb_strpos($attachmentName, '.') === false){
			$attachmentName .= ".zip";
		}

        if (file_put_contents($pathFolder.'/'.$attachmentName, $content) === false){

            return false;
        } else {
            $pathinfo = pathinfo($pathFolder.'/'.$attachmentName);
            if($pathinfo["extension"] == "zip"){
                //Распаковка архива
                $zip = new \ZipArchive;
                if ($zip->open($pathFolder . '/' .$attachmentName) === TRUE) {
                    $zip->extractTo($pathFolder);
                    $zip->close();
                    //self::renameCyrillicFiles($pathFolder);
                } else {
                    die('Ошибка распаковки архива');
                }
			}

            return true;
        }
    }

    private static function renameCyrillicFiles(string $dirName)
    {
        $files = scandir($dirName);
        foreach ($files as $filename) {
            if ($filename == ".." or $filename == ".") continue;
            $newName = iconv('UTF-8', 'cp437//IGNORE', $filename);
            $newName = iconv('cp437', 'cp865//IGNORE', $newName);
            $newname = iconv('cp866', 'UTF-8//IGNORE', $newName);

            rename($dirName . "/" . $filename, $dirName . "/" . $newname);
        }
    }
    
    /**
     * Создание директории для вложения
     * @return [bool]
     */
    private static function newFolder($pathFolder){
        return mkdir($pathFolder, 0775, true);    
    }

    //TODO заложить и для  RefAttachmentHeaderList
    public static function getAttachmentsData(string $messageId, array $documentsData) : bool | array
    {
        $attachmentsData = [];
        $pathFolder = $_SERVER['DOCUMENT_ROOT'] . '/upload/attachments/' . $messageId;
        $attachmentsNames = array_column($documentsData, 'Name');
        $counts = array_count_values($attachmentsNames);
        $usedNames = [];
        foreach ($documentsData as $documentData) {
            if (file_exists($pathFolder . '/' . $documentData['CodeDocument'])) {
                $curFileName = $documentData["Name"];
                $newName = $curFileName;
                if (array_key_exists($curFileName, $counts) && $counts[$curFileName] > 1) {
                    //формируем новое имя файла

                    $nameParts = explode('.', $curFileName);
                    $extension = $nameParts[1];
                    $lastIndex = self::getFileNameLastIndex($newName, $usedNames);
                    if ($lastIndex !== false && $lastIndex > 0) {
                        $index = $lastIndex + 1;
                        //$index = count($usedNames) + 1;//Это для сквозной нумерации
                        $newName = $nameParts[0] . '(' . $index . ').' . $extension;
                    } else {
                        $newName = $curFileName;
                        if (in_array($newName, $usedNames)) {
                            $newNameParts = explode('.', $newName);
                            $fileNamePart = $newNameParts[0];
                            $fileExtensionPart = $newNameParts[1];
                            $newName = $fileNamePart . '(' . ($lastIndex + 1) . ').' . $fileExtensionPart;
                        }

                    }

                    $usedNames[] = $newName;
                }

                rename($pathFolder . '/' . $documentData['CodeDocument'], $pathFolder . '/' . $newName);
                $attachmentsData[] = \CFile::MakeFileArray($pathFolder . '/' . $newName);

            } elseif (file_exists($pathFolder . '/' . $documentData['Name'])) {
                $attachmentsData[] = \CFile::MakeFileArray($pathFolder . '/' . $documentData['Name']);
            }

        }


		if(file_exists($pathFolder . '/' . "çá∩ó½Ñ¡¿Ñ.pdf")){
			rename($pathFolder . '/' . "çá∩ó½Ñ¡¿Ñ.pdf", $pathFolder . "/Заявление.pdf");
		}
		if(file_exists($pathFolder . '/' . "Ä»¿ß∞ »α¿½áúáÑ¼δσ ñ«¬π¼Ñ¡Γ«ó.pdf")){
			rename($pathFolder . '/' . "Ä»¿ß∞ »α¿½áúáÑ¼δσ ñ«¬π¼Ñ¡Γ«ó.pdf", $pathFolder . "/Опись прилагаемых документов.pdf");
		}
        if (file_exists($pathFolder . '/' . "Заявление.pdf")) {
            $attachmentsData[] = \CFile::MakeFileArray($pathFolder . "/Заявление.pdf");
        }
		if(file_exists($pathFolder . '/' . "Опись прилагаемых документов.pdf")){
			$attachmentsData[] = \CFile::MakeFileArray($pathFolder . '/Опись прилагаемых документов.pdf');
		}


        return ($attachmentsData) ?? false;
    }

    public static function getFileNameLastIndex(string $fileName, array $usedNames)
    {
        $position = false;

        foreach ($usedNames as $name) {
            $fileNamePart = explode('.', $fileName)[0];
            $position = strripos($name, $fileNamePart);
            if ($position !== false) {
                $bracketClosetIndex = strripos($name, ').');
                if ($bracketClosetIndex) {
                    $bracketOpenIndex = $bracketClosetIndex;
                    while ($name[$bracketOpenIndex] != '(')
                        $bracketOpenIndex--;

                    $index = substr($name, $bracketOpenIndex + 1, $bracketClosetIndex - $bracketOpenIndex - 1);
                } else {
                    $index = 0;
                }
            }
        }

        if ($index !== false)
            return (int)$index;

        return false;
    }

    public static function getXmlAttachmentPath(string $messageId) : bool | string
    {
        $attachmentData = [];
        $folderPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/attachments/' . $messageId;
        $filesInDir = scandir($folderPath);
        foreach($filesInDir as $fileInDir){
            $filePath = $folderPath . '/' . $fileInDir;
            if (pathinfo($filePath, PATHINFO_EXTENSION) == 'xml'){
                $attachmentData = $filePath;
            }
        };

        return ($attachmentData) ?? false;
    }

    public function remove(){
        //удалить папку и файлы внутри
    }
}

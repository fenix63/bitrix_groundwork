<?

namespace Vdgb\Core;

class Debug{
    public static function dbgLog($data, string $suffix = '_1')
    {
        if (
            !empty($suffix)
            &&
            preg_match('![^-0-9a-zA-Z_]+!', $suffix)
        ) {
            $suffix = '';
        }
     
        $fileName = $_SERVER['DOCUMENT_ROOT'].'/logs/dbg-' . date('Ymd') . $suffix . '.log';
     
        $r = fopen($fileName, 'a');
        fwrite($r, PHP_EOL);
        fwrite($r, date('Y-m-d H:i:s') . PHP_EOL);
        fwrite($r, print_r($data, 1));
        //ob_start(); var_export($data); fwrite($r, ob_get_clean());
        fwrite($r, PHP_EOL);
        fclose($r);
    }

}
?>
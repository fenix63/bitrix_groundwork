<?

namespace MyCompany\WebService;

class Log{
    public static function emergency($path, $message, array $context = []) {
        self::log(LogLevel::EMERGENCY, $path, $message, $context);
    }

    public static function alert($path, $message, array $context = []) {
        self::log(LogLevel::ALERT, $path, $message, $context);
    }

    public static function critical($path, $message, array $context = []) {
        self::log(LogLevel::CRITICAL, $path, $message, $context);
    }

    public static function error($path, $message, array $context = []) {
        self::log(LogLevel::ERROR, $path, $message, $context);
    }

    public static function warning($path, $message, array $context = []) {
        self::log(LogLevel::WARNING, $path, $message, $context);
    }

    public static function notice($path, $message, array $context = []) {
        self::log(LogLevel::NOTICE, $path, $message, $context);
    }

    public static function info($path, $message, array $context = []) {
        self::log(LogLevel::INFO, $path, $message, $context);
    }

    public static function debug($path, $message, array $context = []) {
        self::log(LogLevel::DEBUG, $path, $message, $context);
    }

    public static function log($level, $path, $message, array $context = []) {
        /* 
            CEventLog::Add(array(
                "SEVERITY" => "SECURITY",
                "AUDIT_TYPE_ID" => "MY_OWN_TYPE",
                "MODULE_ID" => "main",
                "ITEM_ID" => 123,
                "DESCRIPTION" => "Какое-то описание",
            ));
        */
            
        // Текущая дата в формате 1970-12-01 23:59:59
        $dateFormatted = (new \DateTime())->format('Y-m-d H:i:s');
    
        // Собираем сообщение, подставив дату, уровень и текст из аргумента
        $contextString = json_encode($context);
        $message = sprintf(
            "\n".'[%s] %s: %s%s',
            $dateFormatted,
            $level,
            $message,
            $contextString,
            PHP_EOL // Перенос строки
        );

        if (file_exists($path)) {
            file_put_contents($path, $message, FILE_APPEND);
        } else {
            $mkpath = self::__del_last_sheet($path);
            if (!file_exists($mkpath)){
                mkdir($mkpath, 0775, true);    
            }
            file_put_contents($path, $message);
        }
    }

    private static function __del_last_sheet($str){
        $str2=explode("/", $str);
        $str3=$str2[0];
        $leng=count($str2)-1;
        if(!$str2[$leng]){
            $str2[$leng-1]=NULL;
        }
        for($i=1;$i<$leng;$i++){
            $str3=$str3."/".$str2[$i];
        }
        if($str2[$leng]){
            $str3=$str3."/";
        }
        return $str3;
    }
}

class LogLevel
{
   const EMERGENCY = 'emergency';
   const ALERT     = 'alert';
   const CRITICAL  = 'critical';
   const ERROR     = 'error';
   const WARNING   = 'warning';
   const NOTICE    = 'notice';
   const INFO      = 'info';
   const DEBUG     = 'debug';
}

?>

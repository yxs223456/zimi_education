<?php
/**
 * Created by PhpStorm.
 * User: houchaowei
 * Date: 2017/8/16
 * Time: 11:02
 */
//判空
function isNullOrEmpty($obj) {
    return (!isset($obj) || empty($obj) || $obj == null);
}
//下划线转换驼峰
function convertUnderline ($str , $ucfirst = true) {
    $str = explode('_' , $str);
    foreach($str as $key=>$val)
        $str[$key] = ucfirst($val);

    if(!$ucfirst)
        $str[0] = strtolower($str[0]);

    return implode('' , $str);
}
//循环删除目录和文件函数
function delDirAndFile($dirName) {
    if ($handle = opendir("$dirName")) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$dirName/$item")) {
                    delDirAndFile("$dirName/$item");
                } else {
                    unlink("$dirName/$item");
                }
            }
        }
        closedir($handle);
        rmdir($dirName);
    }
}
function generateDir() {
    if(!is_dir("./generated/")) {
        mkdir("./generated/",0777);
    }

    if(!is_dir("./generated/service/")) {
        mkdir("./generated/service/",0777);
    }
    if(!is_dir("./generated/model/")) {
        mkdir("./generated/model/",0777);
    }
}

function mysqlConnect($server,$user,$pass,$dbname) {
    $conn = mysqli_connect($server,$user,$pass);
    if(!$conn) die("连接错误：".mysqli_connect_error()."\n");
    mysqli_select_db($conn,$dbname) or die("数据库连接失败！\n");
    return $conn;
}

function getPrimaryKey($tableName,$connection) {
    $result = mysqli_query($connection,"SELECT * FROM ".$tableName) or die('Query failed: ' . mysqli_error($connection)."\n");
    $i = 0;
    while($i < mysqli_num_fields($result)) {
        $meta = mysqli_fetch_field($result);
        if($meta->flags & MYSQLI_PRI_KEY_FLAG) {
            $primaryKeyName = $meta->name;
            break;
        }
        $i++;
    }
    mysqli_free_result($result);
    return $primaryKeyName;
}

function generateBaseFile($tableList, $prefix) {
    $baseFileName = 'Base.php';
    $baseFile = fopen("./".$baseFileName,"w");//打开文件准备写入
    $baseFileContent =
        "<?php\n\n"
        . "namespace app\common\controller;\n\n"
        . "use think\Controller;\n\n";

    foreach ($tableList as $table) {
        $baseName = convertUnderline(preg_replace("/$prefix/",'',$table['tableName'],1));
        $baseFileContent .= "use app\common\service\\" .$baseName. " as " . lcfirst($baseName) . "Service;\n";
    }

    $baseFileContent .= "class Base extends Controller {\n\n";

    foreach ($tableList as $table) {
        $baseName = convertUnderline(preg_replace("/$prefix/",'',$table['tableName'],1));
        $baseFileContent .= "   protected $" .lcfirst($baseName). "Service;\n";
    }

    $baseFileContent .=
        "   /**\n".
        "   * 依赖注入\n".
        "   * Base construct\n".
        "   */\n".
        "   public function __construct(\n";

    $index = 1;
    foreach ($tableList as $key=>$table) {
        $baseName = convertUnderline(preg_replace("/$prefix/",'',$table['tableName'],1));

        if ($index%3 == 0) {
            $baseFileContent .= " ".$baseName."Service $". lcfirst($baseName)."Service,\n";
        }else if ($index%3 == 1){
            $baseFileContent .= "                               ".$baseName."Service $". lcfirst($baseName)."Service,";
        }else{
            $baseFileContent .= " ".$baseName."Service $". lcfirst($baseName)."Service,";
        }

        $index++;
    }

    $baseFileContent = substr($baseFileContent,0,strlen($baseFileContent)-1);

    $baseFileContent .=
        "){\n\n"
        . "     parent::__construct();\n\n";

    foreach ($tableList as $table) {
        $baseName = convertUnderline(preg_replace("/$prefix/",'',$table['tableName'],1));
        $baseFileContent .= "      \$this->".lcfirst($baseName)."Service = \$".lcfirst($baseName)."Service;\n";
    }

    $baseFileContent .= "   }\n"
        ."}";

    fwrite($baseFile,$baseFileContent);//写入
    fclose($baseFile);//关闭

    print_r("base generate success\n");
}

function generateService($tableList, $prefix) {

    foreach ($tableList as $table) {
        $serviceName = convertUnderline(preg_replace("/$prefix/",'',$table['tableName'],1));

        $serviceFileName = $serviceName.'.php';
        $serviceFile = fopen("./generated/service/".$serviceFileName,"w");//打开文件准备写入

        $model = $serviceName."Model";
        $serviceFileContent =
            "<?php\n\n"
            . "namespace app\common\service;\n\n"
            . "use app\common\service\Base;\n"
            . "use app\common\model\\$serviceName as $model;\n\n"
            . "class $serviceName extends Base {\n\n"
            . "     public function __construct() {\n"
            . "         parent::__construct();\n"
            . "         \$this->currentModel = new $model();\n"
            . "     }\n\n"
            . "}";
        fwrite($serviceFile,$serviceFileContent);//写入
        fclose($serviceFile);//关闭
    }
    print_r("service generate success\n");
}

function generateModel($tableList, $prefix) {
    foreach ($tableList as $table) {
        $modelName = convertUnderline(preg_replace("/$prefix/",'',$table['tableName'],1));

        $modelFileName = $modelName.'.php';
        $modelFile = fopen("./generated/model/".$modelFileName,"w");//打开文件准备写入

        $modelFileContent =
            "<?php\n\n"
            . "namespace app\common\model;\n\n"
            . "use think\Model;\n\n"
            . "class $modelName extends Model {\n\n"
            . "}";

        fwrite($modelFile,$modelFileContent);//写入
        fclose($modelFile);//关闭
    }

    print_r("model generate success");
}

function generateTable($table)
{
//    if(!is_dir("./generatedTable/".$table."/")) {
//        mkdir("./generatedTable/".$table."/",0777);
//    }
    if(!is_dir("./generatedTable/")) {
        mkdir("./generatedTable/",0777);
    }
}

function generateTableBaseFile($tableList)
{
    if (!is_array($tableList)) return false;
    $start =  "<?php\n\n";
    $protected='';
    $getField = '';
    foreach ($tableList as $key=>$value) {
        $table = dealTableName($key);
        $file = $table.'Bean.php';
        $beanFile = fopen("./generatedTable/{$table}/".$file,"w");//打开文件准备写入
        generateTable($table);
        $start.="namespace App\Model\\{$table};\n\n"
            ."use EasySwoole\Spl\SplBean;\n\n"
            ."class {$table}Bean extends SplBean \n"
            ."{\n";
        foreach ($value as $field=>$item) {
            $protected.= "\tprotected \${$item}; \n";
            $fields = convertUnder($item);
            $getField.= "\tpublic function get{$fields}()\n"
                ."\t{\n"
                ."\t\treturn \$this->{$item};\n"
                ."\t}\n\n"
                ."\tpublic function set{$fields}(\${$item}): void\n"
                ."\t{\n"
                ."\t\t\$this->{$item} = \${$item};\n"
                ."\t}\n";
        }
        $start.=$protected."\n\n".$getField."\n";
        $start.="}";
        fwrite($beanFile,$start);//写入
        fclose($beanFile);//关闭
    }

}

function generateTableModelFile($tableList, $prefix)
{
    if (!is_array($tableList)) return false;
    $protected='';
    $getField = '';
    foreach ($tableList as $key=>$value) {
        $table = dealTableName($key, $prefix);
        generateTable($table);
        $file = $table.'Model.php';
        $modelFile = fopen("./generatedTable/".$file,"w");//打开文件准备写入
        $start =  "<?php\n\n";
//        $start.="namespace App\Model\\{$table};\n\n"
//            ."use App\Model\BaseModel;\n\n"
//            ."class {$table}Model extends BaseModel \n"
//            ."{\n"
//            ."\tprotected \$table = '{$key}';\n\n"
//            ."\t/*\n"
//            ."\t*\n"
//            ."\t*/\n"
//            ."\tfunction getAll(int \$page = 1, int \$pageSize = 10) {\n"
//            ."\t\t\$data = \$this->getDbConnection()->withTotalCount()->orderBy('id', 'DESC')->get(\$this->table, [(\$page - 1) * \$pageSize, \$page * \$pageSize]);\n"
//            ."\t\t\$total = \$this->getDbConnection()->getTotalCount();\n"
//            ."\t\treturn ['data' => \$data, 'total' => \$total];\n";
        $start.="namespace App\Model;\n\n"
            ."class {$table}Model extends BaseModel \n"
            ."{\n"
            ."\tprotected \$table = '{$key}';\n\n";
        $start.="}";
        fwrite($modelFile,$start);//写入
        fclose($modelFile);//关闭
    }

}

function dealTableName($table, $tablePrefix='')
{
    $a = substr($table, strpos($table, $tablePrefix)+strlen($tablePrefix));
    $b = explode('_', $a);
    return convertUnder(implode('_',$b));
}

function generateEnumerations($data)
{
    $dir = "./generatedDictionary/";
    if(!is_dir($dir)) {
        mkdir($dir,0777);
    }
    foreach ($data as $key=>$value) {
        $serviceName = convertUnder($key).'Enum';
        $enumerationsFile = $serviceName.'.php';
        $enumerationsFile = fopen($dir.$enumerationsFile,"w");//打开文件准备写入
        $serviceFileContent =
            "<?php\n\n"
            . "namespace app\\enumerations;\n\n"
            . "class $serviceName {\n\n"
            . "     use EnumTrait; \n\n"
            . "     // {$value['dictionary_name']}; \n"
            . "     const DICTIONARY_CODE = \"{$key}\"; \n\n";
        foreach ($value['enum'] as $k=>$v) {
            $serviceFileContent.=
                "     // {$v['desc']}; \n"
                ."     const {$v['key']} = {$v['value']}; \n"
                . "     const {$v['key']}_CODE = \"{$v['key']}\"; \n"
                . "     const {$v['key']}_DESC = \"{$v['desc']}\"; \n\n";
        }
        $serviceFileContent.= "}";
        fwrite($enumerationsFile,$serviceFileContent);//写入
        fclose($enumerationsFile);//关闭
    }
}

function generateConstants($data)
{
    $dir = "./generatedConstants/";
    if(!is_dir($dir)) {
        mkdir($dir,0777);
    }
    $serviceName = '';
    $serviceFileContent = "<?php\n\n"
        . "return [ \n";
    foreach ($data as $key=>$value) {
        if ($value['constants_type']==1) {
            $serviceName = 'database';
            $serviceFileContent = constantsTypeForDatabase($value['constants']);
        }elseif($value['constants_type']==2){
            $serviceName = $value['constants_code'];
            $serviceFileContent = constantsTypeForRedis($value['constants_code'], $value['constants']);
        } elseif($value['constants_type']==3){
            $serviceName = 'Constants';
            $serviceFileContent = constantsTypeForKeyAndValue($serviceName, $value['constants_name'], $value['constants']);
        } elseif($value['constants_type']==4){
            $serviceName = $key;
            $serviceFileContent = constantsTypeForObject($value['constants_code'], $value['constants']);
        }
        $enumerationsFile = $serviceName.'.php';
        $enumerationsFile = fopen($dir.$enumerationsFile,"w");//打开文件准备写入
        if ($serviceFileContent=='' || $serviceName=='') {
            fclose($enumerationsFile);//关闭
        } else {
            fwrite($enumerationsFile,$serviceFileContent);//写入
            fclose($enumerationsFile);//关闭
        }
    }

}

function generateErrorTips($data)
{
    $dir = "./generatedErrorTips/";
    if(!is_dir($dir)) {
        mkdir($dir,0777);
    }
    $serviceFileContent = "<?php\n\n"
        . "namespace EasySwoole\Http\Message; \n\n"
        . "class ErrorEntity \n"
        . "{ \n";
    $private = "    private static \$phrases = [ \n";
    foreach ($data as $key=>$value) {
        $serviceFileContent.="    const {$value['code']} = {$value['key']}; \n";
        $private.="         {$value['key']}=>'{$value['message']}', \n";
    }
    $private.="    ];";

    $static = "    static function getReasonPhrase(\$statusCode):?string\n"
        ."    { \n"
        ."        if(isset(self::\$phrases[\$statusCode])){ \n"
        ."            return self::\$phrases[\$statusCode]; \n"
        ."        }else{ \n"
        ."            return null; \n"
        ."        }\n"
        ."    } \n\n"
        ."    static function generateException(\$statusCode)\n"
        ."    { \n"
        ."        return new \Exception(self::getReasonPhrase(\$statusCode), \$statusCode); \n"
        ."    } \n"
        ."}";
    $errorString = $serviceFileContent."\n\n".$private."\n\n".$static;
    //echo $errorString;die;
    $enumerationsFile = 'ErrorEntity.php';
    $enumerationsFile = fopen($dir.$enumerationsFile,"w");//打开文件准备写入
    if ($serviceFileContent=='') {
        fclose($enumerationsFile);//关闭
    } else {
        fwrite($enumerationsFile,$errorString);//写入
        fclose($enumerationsFile);//关闭
    }
}

function generateErrorTipsForSingle($data)
{
    $dir = "./generatedErrorTips/";
    if(!is_dir($dir)) {
        mkdir($dir,0777);
    }
    $serviceFileContent = "<?php\n\n"
        . "namespace EasySwoole\Http\Message; \n\n"
        . "class ErrorEntity \n"
        . "{ \n";
    $private = "    private static \$phrases = [ \n";
    foreach ($data as $key=>$value) {
        $serviceFileContent.="    const {$value['code']} = {$value['key']}; \n";
        $private.="         {$value['key']}=>'{$value['message']}', \n";
    }
    $private.="    ];";
    $errorString = $serviceFileContent."\n\n".$private."\n\n"."}";
    //echo $errorString;die;
    $enumerationsFile = 'ErrorEntity.php';
    $enumerationsFile = fopen($dir.$enumerationsFile,"w");//打开文件准备写入
    if ($serviceFileContent=='') {
        fclose($enumerationsFile);//关闭
    } else {
        fwrite($enumerationsFile,$errorString);//写入
        fclose($enumerationsFile);//关闭
    }
}

function constantsTypeForKeyAndValue($serviceName, $name, $value)
{
    $serviceFileContent = "<?php\n\n"
        . "namespace app\\constants;\n\n"
        . "class $serviceName {\n\n";
    foreach ($value as $k=>$item) {
        $serviceFileContent.= "      // {$item['constants_name']}; \n"
            ."      const {$item['key']} = \"{$item['value']}\"; \n";
    }
    $serviceFileContent.= "}";
    return $serviceFileContent;
}

/**
 * 数据库类型
 * @return $serviceFileContent
 */
function constantsTypeForDatabase($value)
{
    $serviceFileContent = "<?php\n\n"
        . "return [ \n";
    foreach ($value as $k=>$item) {
        $serviceFileContent.=
            "      // {$item['constants_name']} \n";
        if ($item['value']==='true' || $item['value']==='false' || is_numeric($item['value']) || $item['value']==='[]') {
            $serviceFileContent.="      '{$item['key']}' => {$item['value']}, \n";
        } else {
            $serviceFileContent.="      '{$item['key']}' => '{$item['value']}', \n";
        }
    }
    $serviceFileContent .= "];";
    return $serviceFileContent;
}

/**
 * redis类型
 * @return $serviceFileContent
 */
function constantsTypeForRedis($key, $value)
{
    $serviceFileContent = "<?php\n\n"
        . "return [ \n"
        ."   '$key' => [ \n";
    foreach ($value as $k=>$item) {
        if ($item['value']==='[]' || is_numeric($item['value'])) {
            $serviceFileContent.="      \"{$item['key']}\" => {$item['value']}, \n";
        } else {
            $serviceFileContent.="      \"{$item['key']}\" => '{$item['value']}', \n";
        }
    }
    $serviceFileContent .= "    ] \n"
        ."];";
    return $serviceFileContent;
}

function constantsTypeForObject($key, $value)
{
    $serviceFileContent = "<?php\n\n"
        . "return [ \n"
        ."   '$key' => [ \n";
    foreach ($value as $k=>$v) {
        $msg = json_decode($v['object'], true);
        if (empty($msg)) break;
        foreach ($msg as $key=>$items) {
            $serviceFileContent.="     [\n";
            foreach ($items as $keys=>$item) {
                $key = strtoupper($keys);
                if(is_numeric($item)){
                    $serviceFileContent.=
                        "       '{$key}' => {$item}, \n";
                }else{
                    $serviceFileContent.=
                        "       '{$key}' => '{$item}', \n";
                }
            }
            $serviceFileContent.= "     ],\n";
        }
    }
    $serviceFileContent.= "   ],\n"
        ."];";
    return $serviceFileContent;
}

function convertUnder($rs)
{
    $re = explode('_', $rs);
    foreach ($re as $k=>$v) {
        $da[$k] = ucfirst(strtolower($v));
    }
    $result = implode('', $da);
    return $result;
}



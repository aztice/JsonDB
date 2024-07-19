<?php
function CreateLock($dbname,$list){
    $path = $_SERVER['DOCUMENT_ROOT'].'/db/'.$dbname.'/list/'.$list.'.lock';
    touch($path);
}
function IsLock($dbname,$list){
    $path = $_SERVER['DOCUMENT_ROOT'].'/db/'.$dbname.'/list/'.$list.'.lock';
    if(file_exists($path)){
        return true;
    }
    else{
        return false;
    }
}
function DeleteLock($dbname,$list){
    $path = $_SERVER['DOCUMENT_ROOT'].'/db/'.$dbname.'/list/'.$list.'.lock';
    unlink($path);
}
function WebAPIAuth() {
    if (!isset($_REQUEST["dbname"])) {
        sendErrorResponse(400, "Missing dbname parameter");
    }
    $dbname = $_REQUEST["dbname"];
    if (empty($dbname) || !preg_match('/^[a-zA-Z0-9_-]+$/', $dbname)) {
        sendErrorResponse(400, "Invalid dbname parameter");
    }
    $db_directory = $_SERVER["DOCUMENT_ROOT"] . "/db/" . $dbname . "/";
    if (!is_dir($db_directory)) {
        sendErrorResponse(404, "Database not found");
    }
    if (isset($_REQUEST["ApiKey"])) {
        $json = json_decode(file_get_contents($db_directory . "config.json"), true);
        if ($json["WebAPI"] === false) {
            sendErrorResponse(403, "WebAPI is disabled");
        } elseif ($json["ApiKey"] !== $_REQUEST["ApiKey"]) {
            sendErrorResponse(401, "Invalid APIKey");
        }
    } else {
        sendErrorResponse(401, "Authentication failure");
    }
}

function sendErrorResponse($code, $message) {
    $json = [
        "status" => "error",
        "code" => $code,
        "message" => $message,
    ];
    http_response_code($code);
    echo json_encode($json);
    exit;
}
class jsonDB{
    public $dbname;
    public $config;
    public $ReportError = true;
    public $JsonDBConfig;
    public $Language = 'zh-cn';
    public $LanguageJson;
    public $WebAPI = false;
    public $listCache = false;
    public $timeUsed;
    public function __construct(){
        if(!is_file($_SERVER['DOCUMENT_ROOT'].'/dblang/'.$this->Language.'.json')){
            echo "[JsonDB] ERR_LANGUAGE_FILE!!!";
            exit();
        }
        else{
            $this->LanguageJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/dblang/'.$this->Language.'.json'),true);
        }
        $this->timeUsed = microtime(true);
    }
    public function GetTimeUsed(){
        return microtime(true)-$this->timeUsed;
    }
    public function WebAPI(){
        $this->WebAPI = true;
    }
    public function ConfigInit(){ // 此模块为内置模块,开发者勿动
        $this->JsonDBConfig['version'] = '2.0';
    }
    public function Filter($List, $Range, $str) {
        if ($this->dbname !== '' && isset($this->dbname)) {
            if ($this->isList($List)) {
                $jsonFile = $_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$List.'.json';
                $jsonData = json_decode(file_get_contents($jsonFile), true);
                $result = [];
                if ($Range == 'Key' && $str !== '') {
                    foreach ($jsonData as $key => $value) {
                        if (strpos($key, $str) !== false) {
                            $result[] = $key;
                        }
                    }
                }
                else {
                    if ($this->ReportError) {
                        echo "[JsonDB] " . $this->LanguageJson['InvalidFilterRange'];
                    }
                    exit();
                }
                return !empty($result) ? $result : false;
            } else {
                if ($this->ReportError) {
                    echo "[JsonDB] " . $this->dbname . ',' . $this->LanguageJson['InvalidList'][0] . " $List," . $this->LanguageJson['InvalidList'][1] . ' CreateList();';
                }
                exit();
            }
        } else {
            if ($this->ReportError) {
                echo "[JsonDB] " . $this->LanguageJson['NoConnection'][0] . " " . $this->dbname . ',' . $this->LanguageJson['NoConnection'][1] . ' Connect();';
            }
            exit();
        }
    }
    public function SkipError(){
        $this->ReportError = false;
    }
    public function createdb($name){
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/db/')){
            mkdir($_SERVER['DOCUMENT_ROOT'].'/db/', 0755, true); // 创建目录，并设置适当的权限
        }
        if(is_dir($_SERVER['DOCUMENT_ROOT'].'/db/'.$name.'/')){
            if($this->ReportError==true){
                echo "[JsonDB] ".$this->LanguageJson['ValidDB'][0]." ".$name.$this->LanguageJson['ValidDB'][1];
            }
            exit();
        }
        else{
            mkdir($_SERVER['DOCUMENT_ROOT'].'/db/'.$name.'/');
            mkdir($_SERVER['DOCUMENT_ROOT'].'/db/'.$name.'/list/');
            $this->config['dbname']=$name;
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/db/'.$name.'/config.json',json_encode($this->config, JSON_PRETTY_PRINT));
            return true;
        }
    }
    public function GetAllLists() {
        if($this->dbname!=='' && isset($this->dbname)){
            $configFile = $_SERVER['DOCUMENT_ROOT'] . '/db/' . $this->dbname . '/config.json';
            if (!is_file($configFile)) {
                return false;
            } else {
                $data = file_get_contents($configFile);
                $data = json_decode($data);
                if (isset($data->list)) {
                    return $data->list;
                } else {
                    return false;
                }
            }
        }
        else{
            if($this->ReportError==true){
                echo "[JsonDB] ".$this->LanguageJson['NoConnection'][0]." ".$this->dbname.','.$this->LanguageJson['NoConnection'][1].' Connect();';
            }
            exit();
        }
    }

    public function Connect($name){
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/db/'.$name.'/')){
            if($this->ReportError==true){
                echo "[JsonDB] ".$this->LanguageJson['InvalidDB'][0]." ".$name.$this->LanguageJson['InValidDB'][1];
            }
            exit();
        }
        else{
            $this->dbname = $name;
            $configPath = $_SERVER['DOCUMENT_ROOT'].'/db/'.$name.'/config.json';
            if(file_exists($configPath)){
                $this->config = json_decode(file_get_contents($configPath), true);
            } else {
                $this->config = array('dbname' => $name, 'list' => array());
                file_put_contents($configPath, json_encode($this->config, JSON_PRETTY_PRINT));
            }
            return true;
        }
    }

    public function CreateList($name){
        if($this->dbname!=='' && isset($this->dbname)){
            if($this->IsList($name)){
                if($this->ReportError==true){
                    echo "[JsonDB] ".$this->LanguageJson['ValidList'][0]." ".$name.$this->LanguageJson['ValidList'][1];
                    exit();
                }
            }
            else{
                $this->config['list'][]=$name;
                file_put_contents($_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$name.'.json','{}');
                file_put_contents($_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/config.json',json_encode($this->config, JSON_PRETTY_PRINT)); // 更新配置文件
                return true;
            }
        }
        else{
            if($this->ReportError==true){
                echo "[JsonDB] ".$this->LanguageJson['NoConnection'][0]." ".$this->dbname.','.$this->LanguageJson['NoConnection'][1].' Connect();';
            }
            exit();
        }
    }
    public function IsList($name){
        if(file_exists($_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$name.'.json')){
            return true;
        }
        else{
            return false;
        }
    }
    public function get($list, $key){
        if(in_array($list, $this->config['list'])){
            if($this->listCache!==false){
                if(isset($this->listCache[$list])){
                    return $this->listCache[$list][$key];
                }
            }
            $path=$_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$list.'.json';
            $data = json_decode(file_get_contents($path), true);
            $this->listCache[$list]=$data;
            if(isset($data[$key])) {
                return $data[$key];
            } else {
                return null;
            }
        }
        else{
            if($this->WebAPI==true){
                $json = [
                    "status" => "error",
                    "code" => 404,
                    "message" => "Invalid list in Database",
                ];
                http_response_code(404);
                echo json_encode($json);
            }
            else if($this->ReportError==true){
                echo "[JsonDB] ".$this->dbname.','.$this->LanguageJson['InvalidTargetList'][0]." ".$list.','.$this->LanguageJson['InvalidTargetList'][1].' CreateList();';
            }
            exit();
        }
    }
    public function IsKey($list, $key){
        if($this->listCache!==false){
            if(isset($this->listCache[$list])){
                return isset($this->listCache[$list][$key]);
            }
        }
        $path=$_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$list.'.json';
        $data = json_decode(file_get_contents($path), true);
        $this->listCache[$list]=$data;
        return isset($data[$key]);
    }
    public function edit($list, $key, $value){
        if(in_array($list, $this->config['list'])){
            if(IsLock($this->dbname,$list)){
                while (IsLock($this->dbname, $list)) {
                    // 等待锁文件被删除
                    usleep(100000); // 等待100毫秒，可以根据需要调整等待时间
                }
            }
            CreateLock($this->dbname,$list);
        
            $filePath = $_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$list.'.json';
            $data = json_decode(file_get_contents($filePath), true);
        
            $this->listCache[$list][$key]=$value;
            $data[$key] = $value;
    
            // 写回到文件
            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
            DeleteLock($this->dbname,$list);
            return true;
        }
        else{
            if($this->WebAPI==true){
                $json = [
                    "status" => "error",
                    "code" => 404,
                    "message" => "Invalid list in Database",
                ];
                http_response_code(404);
                echo json_encode($json);
            }
            else if($this->ReportError==true){
                echo "[JsonDB] ".$this->dbname.','.$this->LanguageJson['InvalidTargetList'][0]." ".$list.','.$this->LanguageJson['InvalidTargetList'][1].' CreateList();';
            }
            exit();
        }
    }
    public function remove($list, $key){
        if(in_array($list, $this->config['list'])){
            if(IsLock($this->dbname,$list)){
                while (IsLock($this->dbname, $list)) {
                    // 等待锁文件被删除
                    usleep(100000);
                }
            }
            CreateLock($this->dbname,$list);
            $path=$_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$list.'.json';
            if($this->listCache!==false){
                $data = $this->listCache[$list];
            }
            else{
                $data = json_decode(file_get_contents($path), true);
            }
            if(isset($data[$key])){
                unset($data[$key]);
                if($this->listCache!==false) unset($this->listCache[$list][$key]);
                file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
            } else {
                if($this->ReportError==true){
                    echo "[JsonDB] ".$this->LanguageJson['InvalidTargetKey'][0]." ".$key.",".$this->LanguageJson['InvalidTargetKey'][1]." ".$list.'!';
                }
                DeleteLock($this->dbname,$list);
                return false;
            }
            DeleteLock($this->dbname,$list);
            return true;
        }
        else{
            if($this->ReportError==true){
                echo "[JsonDB] ".$this->dbname.','.$this->LanguageJson['InvalidTargetList'][0]." ".$list.','.$this->LanguageJson['InvalidTargetList'][1].' CreateList();';
            }
            exit();
        }
    }
    public function Backup(){
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/Backup/')){
            mkdir($_SERVER['DOCUMENT_ROOT'].'/Backup/');
        }
        $zip = new ZipArchive();
        $zip->open($_SERVER['DOCUMENT_ROOT'].'/Backup/'.$this->dbname.'.jdb', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/'), RecursiveIteratorIterator::LEAVES_ONLY);
    
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $zip->addFile($file->getRealPath(), substr($file->getPathname(), strlen($folder_path) + 1));
            }
        }
        $zip->close();
    }
    public function DBFixer(){
        $path=$_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/';
        if(file_exists($path.'config.json')){
            file_put_contents($path.'config.json','{"dbname": "'.$this->dbname.'"}');
        }
        if(!is_dir($path.'list/')){
            mkdir($path.'list/');
        }
        echo "[JsonDB] ".$this->LanguageJson['SuccessFixDB'];
    }
    public function Import($path){
        $zip = new ZipArchive;
        if ($zip->open($path) === TRUE) {
            $zip->extractTo($_SERVER['DOCUMENT_ROOT'].'/');
            $zip->close();
        } else {
            // 由于安全原因,本步骤无法SkipError,如有需求,可自改
            echo '[JsonDB] '.$this->LanguageJson['InvalidImportPath'];
        }
    }
    public function jsonCheck($str){
        $str=str_replace('\\\\','{JsonDB:XG}',$str);
        if (strpos($str, '\\') !== false) {
            return false;
        } else if (strpos($str,'"') !== false){
            return false;
        } else{
            return true;
        }
    }
    public function encrypt($str,$key){
        require_once($_SERVER['DOCUMENT_ROOT'].'/db/addon/LightSK.php');
        if($this->LightSK==false){
            echo "[JsonDB] ".$this->LanguageJson['NoLightSK'];
            exit();
        }
        $LightSKAddon = new LightSK($key);
        return $LightSKAddon->encrypt($str);
    }
    public function decrypt($str,$key){
        if($this->LightSK==false){
            echo "[JsonDB] ".$this->LanguageJson['NoLightSK'];
            exit();
        }
        $LightSKAddon = new LightSK($key);
        return $LightSKAddon->decrypt($str);
    }
    public function EnableLightSK(){
        if(file_exists($_SERVER['DOCUMENT_ROOT'].'/db/addon/LightSK.php')){
            $this->LightSK = true;
            return true;
        }
        else{
            echo "[JsonDB] ".$this->LanguageJson['EnableLightSKError'];
            exit();
        }
    }
    public function isArrayDuplicates($array) {
        $counts = array_count_values($array);
        foreach ($counts as $count) {
            if ($count > 1) {
                return true;
            }
        }
        return false;
    }
    public function DBConfig(){
        $this->ConfigInit();
        $ReportErrorStatus = $this->LanguageJson['True'];
        $DBStatus = $this->LanguageJson['ConnectedDB'].' '.$this->dbname;
        $LightSKStatus = $this->LanguageJson['True'];
        if($this->ReportError==false) $ReportErrorStatus = $this->LanguageJson['False'];
        if($this->dbname=='') $DBStatus = $this->LanguageJson['Disconnect'];;
        $DBList = '';
        if($this->LightSK==false) $LightSKStatus = $this->LanguageJson['False'];
        if($this->dbname!==''){
            $array=$this->GetAllList();
            if (empty($array)) {
                $DBList=$this->LanguageJson['Null'];
            }
            else{
                $DBListStatus = $this->LanguageJson['False'];
                if($this->isArrayDuplicates($array)){
                    $DBListStatus = '
                    <font style="color:red;">'.$this->LanguageJson['True'].'</font><br/>
                    <font style="font-size:10px;color:grey;">'.$this->LanguageJson['ConflictListTip'].'</font>
                    ';
                }
                foreach ($array as $value) {
                    $DBList = $DBList.$value.',';
                }
                $DBList=$this->LanguageJson['DBList'].': '.substr($DBList, 0, -1).'<br/>'.$this->LanguageJson['IsListConflict'].': '.$DBListStatus;
            }
        }
        $config = '
        <h1>JsonDB '.$this->LanguageJson['Config'].'</h1>
        <p></p>
        <table>
          <tr>
            <td>'.$this->LanguageJson['Version'].':</td>
            <td>'.$this->JsonDBConfig['version'].'</td>
          </tr>
          <tr>
            <td>'.$this->LanguageJson['ReportErrorStatus'].':</td>
            <td>'.$ReportErrorStatus.'</td>
          </tr>
          <tr>
            <td>'.$this->LanguageJson['DBConnectionStatus'].':</td>
            <td>'.$DBStatus.'</td>
          </tr>
          <tr>
            <td>'.$this->LanguageJson['LightSKStatus'].':</td>
            <td>'.$LightSKStatus.'</td>
          </tr>
          <tr>
            <td>语言</td>
            <td>'.$this->LanguageJson['Language'].'</td>
          </tr>
          <tr>
            <td>'.$DBList.'</td>
          </tr>
        </table>
        ';
        echo $config;
    }
}
?>

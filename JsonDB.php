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
class jsonDB{
    public $dbname;
    public $config;
    public $ReportError = true;
    public $JsonDBConfig;
    public function ConfigInit(){ // 此模块为内置模块,开发者勿动
        $this->JsonDBConfig['version'] = '1.3 TSA';
    }
    public function SkipError(){
        $this->ReportError = false;
    }
    public function CreateTable($name){
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/db/')){
            mkdir($_SERVER['DOCUMENT_ROOT'].'/db/', 0755, true); // 创建目录，并设置适当的权限
        }
        if(is_dir($_SERVER['DOCUMENT_ROOT'].'/db/'.$name.'/')){
            if($this->ReportError==true){
                echo "[JsonDB] 错误!数据表:".$name.'已存在';
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
    public function Connect($name){
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/db/'.$name.'/')){
            if($this->ReportError==true){
                echo "[JsonDB] 错误!数据表:".$name.'不存在';
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
            if(isset($this->config['list'][$name])){
                if($this->ReportError==true){
                    echo "[JsonDB] 错误!数据列表:".$this->config['list'][$name].'已存在';
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
                echo "[JsonDB] 错误!您没有连接至数据表:".$this->dbname.',请尝试Connect();';
            }
            exit();
        }
    }
    public function CreateKey($list,$key,$value){
        if(in_array($list, $this->config['list'])){
            if(IsLock($this->dbname,$list)){
                while (IsLock($this->dbname, $list)) {
                    // 等待锁文件被删除
                    usleep(100000); // 等待100毫秒，可以根据需要调整等待时间
                }
            }
            CreateLock($this->dbname,$list);
            $path=$_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$list.'.json';
            $data = json_decode(file_get_contents($path), true);
            $data[$key] = $value;
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
            DeleteLock($this->dbname,$list);
            return true;
        }
        else{
            if($this->ReportError==true){
                echo "[JsonDB] 错误!目标的列表不存在:".$list.',请尝试CreateList();';
            }
            exit();
        }
    }
    public function GetKey($list, $key){
        if(in_array($list, $this->config['list'])){
            $path=$_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$list.'.json';
            $data = json_decode(file_get_contents($path), true);
            if(isset($data[$key])) {
                return $data[$key];
            } else {
                return null;
            }
        }
        else{
            if($this->ReportError==true){
                echo "[JsonDB] 错误!目标的列表不存在:".$list.',请尝试CreateList();';
            }
            exit();
        }
    }
    public function IsKey($list, $key){
        if(in_array($list, $this->config['list'])){
            $path=$_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$list.'.json';
            $data = json_decode(file_get_contents($path), true);
            return true;
        }
        else{
            return false;
        }
    }
    public function EditKey($list, $key, $value){
        if(IsLock($this->dbname,$list)){
            while (IsLock($this->dbname, $list)) {
                // 等待锁文件被删除
                usleep(100000); // 等待100毫秒，可以根据需要调整等待时间
            }
        }
        CreateLock($this->dbname,$list);
        if(!$this->IsKey($list, $key)){
            return false;
        }
        
        $filePath = $_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$list.'.json';
        $data = json_decode(file_get_contents($filePath), true);
        
        // 修改键值
        $data[$key] = $value;
    
        // 写回到文件
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        DeleteLock($this->dbname,$list);
        return true;
    }
    public function DeleteKey($list, $key){
        if(in_array($list, $this->config['list'])){
            if(IsLock($this->dbname,$list)){
                while (IsLock($this->dbname, $list)) {
                    // 等待锁文件被删除
                    usleep(100000); // 等待100毫秒，可以根据需要调整等待时间
                }
            }
            CreateLock($this->dbname,$list);
            $path=$_SERVER['DOCUMENT_ROOT'].'/db/'.$this->dbname.'/list/'.$list.'.json';
            $data = json_decode(file_get_contents($path), true);

            // 检查键是否存在
            if(isset($data[$key])){
                unset($data[$key]);
                // 保存更新后的数据
                file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
            } else {
                if($this->ReportError==true){
                    echo "[JsonDB] 错误!目标键:".$key."不存在于列表:".$list.'!';
                }
                DeleteLock($this->dbname,$list);
                return false;
            }
            DeleteLock($this->dbname,$list);
            return true;
        }
        else{
            if($this->ReportError==true){
                echo "[JsonDB] 错误!目标的列表不存在:".$list.',请尝试CreateList();';
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
        echo "[JsonDB] 已成功执行修复";
    }
    public function Import($path){
        $zip = new ZipArchive;
        if ($zip->open($path) === TRUE) {
            $zip->extractTo($_SERVER['DOCUMENT_ROOT'].'/');
            $zip->close();
        } else {
            // 由于安全原因,本步骤无法SkipError,如有需求,可自改
            echo '[JsonDB]错误!无法处理导入文件,请确保.jdb文件路径存在以及文件格式正常!';
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
            echo "[JsonDB] 您没有安装LightSK拓展,无法使用LightSK拓展的功能~";
            exit();
        }
        $LightSKAddon = new LightSK($key);
        return $LightSKAddon->encrypt($str);
    }
    public function decrypt($str,$key){
        if($this->LightSK==false){
            echo "[JsonDB] 您没有安装LightSK拓展,无法使用LightSK拓展的功能~";
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
            echo "[JsonDB] 错误!请确保LightSK拓展已安装至 /db/addon/ 目录";
            exit();
        }
    }
    public function DBConfig(){
        $this->ConfigInit();
        $ReportErrorStatus = '是';
        $DBStatus = '已连接至数据库 '.$this->dbname;
        $LightSKStatus = '是';
        if($this->ReportError==false) $ReportErrorStatus = '否';
        if($this->dbname=='') $DBStatus = '未连接';
        if($this->LightSK==false) $LightSKStatus = '否';
        $config = '
        <h1>JsonDB配置</h1>
        <p></p>
        <table>
          <tr>
            <td>版本号:</td>
            <td>'.$this->JsonDBConfig['version'].'</td>
          </tr>
          <tr>
            <td>是否启用错误信息显示:</td>
            <td>'.$ReportErrorStatus.'</td>
          </tr>
          <tr>
            <td>数据库连接状态:</td>
            <td>'.$DBStatus.'</td>
          </tr>
          <tr>
            <td>是否载入LightSK加密拓展:</td>
            <td>'.$LightSKStatus.'</td>
          </tr>
        </table>
        ';
        echo $config;
    }
}
?>

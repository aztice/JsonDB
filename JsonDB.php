<?php
class jsonDB{
    public $dbname;
    public $config;
    public function CreateTable($name){
        if(!is_dir('./db/')){
            mkdir('./db/', 0755, true); // 创建目录，并设置适当的权限
        }
        if(is_dir('./db/'.$name.'/')){
            echo "[JsonDB] 错误!数据表:".$name.'已存在';
            exit();
        }
        else{
            mkdir('./db/'.$name.'/');
            mkdir('./db/'.$name.'/list/');
            $this->config['dbname']=$name;
            file_put_contents('./db/'.$name.'/config.json',json_encode($this->config, JSON_PRETTY_PRINT));
        }
    }
    public function Connect($name){
        if(!is_dir('./db/'.$name.'/')){
            echo "[JsonDB] 错误!数据表:".$name.'不存在';
            exit();
        }
        else{
            $this->dbname = $name;
            $configPath = './db/'.$name.'/config.json';
            if(file_exists($configPath)){
                $this->config = json_decode(file_get_contents($configPath), true);
            } else {
                $this->config = array('dbname' => $name, 'list' => array());
                file_put_contents($configPath, json_encode($this->config, JSON_PRETTY_PRINT));
            }
        }
    }

    public function CreateList($name){
        if($this->dbname!=='' && isset($this->dbname)){
            if(isset($this->config['list'][$name])){
                echo "[JsonDB] 错误!数据列表:".$this->config['list'][$name].'已存在';
                exit();
            }
            else{
                $this->config['list'][]=$name;
                file_put_contents('./db/'.$this->dbname.'/list/'.$name.'.json','{}');
                file_put_contents('./db/'.$this->dbname.'/config.json',json_encode($this->config, JSON_PRETTY_PRINT)); // 更新配置文件
            }
        }
        else{
            echo "[JsonDB] 错误!您没有连接至数据表:".$this->dbname.',请尝试Connect();';
            exit();
        }
    }
    public function CreateKey($list,$key,$value){
        if(in_array($list, $this->config['list'])){
            $path='./db/'.$this->dbname.'/list/'.$list.'.json';
            $data = json_decode(file_get_contents($path), true);
            $data[$key] = $value;
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        }
        else{
            echo "[JsonDB] 错误!目标的列表不存在:".$list.',请尝试CreateList();';
            exit();
        }
    }
    public function GetKey($list, $key){
        if(in_array($list, $this->config['list'])){
            $path='./db/'.$this->dbname.'/list/'.$list.'.json';
            $data = json_decode(file_get_contents($path), true);
            if(isset($data[$key])) {
                return $data[$key];
            } else {
                return null; // 如果键不存在，返回 null
            }
        }
        else{
            echo "[JsonDB] 错误!目标的列表不存在:".$list.',请尝试CreateList();';
            exit();
        }
    }
    public function IsKey($list, $key){
        if(in_array($list, $this->config['list'])){
            $path='./db/'.$this->dbname.'/list/'.$list.'.json';
            $data = json_decode(file_get_contents($path), true);
            return true;
        }
        else{
            return false;
        }
    }
    public function EditKey($list, $key, $value){
        if(!$this->IsKey($list, $key)){
            return false;
        }
        
        $filePath = './db/'.$this->dbname.'/list/'.$list.'.json';
        $data = json_decode(file_get_contents($filePath), true);
        
        // 修改键值
        $data[$key] = $value;
    
        // 写回到文件
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        return true;
    }
    public function Backup(){
        if(!is_dir('./Backup/')){
            mkdir('./Backup/');
        }
        $zip = new ZipArchive();
        $zip->open('./Backup/'.$this->dbname.'.jdb', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('./db/'.$this->dbname.'/'), RecursiveIteratorIterator::LEAVES_ONLY);
    
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $zip->addFile($file->getRealPath(), substr($file->getPathname(), strlen($folder_path) + 1));
            }
        }
    
        // Close the archive
        $zip->close();
    }
    function Import($path){
        $zip = new ZipArchive;
        if ($zip->open($path) === TRUE) {
            $zip->extractTo('./');
            $zip->close();
        } else {
            echo '[JsonDB]错误!无法处理导入文件,请确保.jdb文件路径存在以及文件格式正常!';
        }
    }
}
?>
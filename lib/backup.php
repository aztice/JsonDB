<?php
function backup($dbname){
    if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/Backup/')){
        mkdir($_SERVER['DOCUMENT_ROOT'].'/Backup/');
    }
    $zip = new ZipArchive();
    $zip->open($_SERVER['DOCUMENT_ROOT'].'/Backup/'.$dbname.'.jdb', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'].'/db/'.$dbname.'/'), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $zip->addFile($file->getRealPath(), substr($file->getPathname(), strlen($folder_path) + 1));
        }
    }
    $zip->close();
}

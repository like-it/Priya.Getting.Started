<?php
namespace Priya;
const CHMOD = 0740;
const VENDOR = 'Vendor';
const PUBLIC_HTML = 'Public';
const HOST_FILE = '/etc/hosts';
const HOST_NAME = 'priya.local';
const PRIYA_BIN = 'https://raw.githubusercontent.com/like-it/Priya.Getting.Started/master/Data/priya';
const USER_BIN = '/usr/bin';
const APACHE_CONFIG = 'https://raw.githubusercontent.com/like-it/Priya.Getting.Started/master/Data/005-priya.local.conf';
const APACHE_SITES_AVAILABLE = '/etc/apache2/sites-available';
const DIR_RESOURCE = '/Vendor/Priya/Module/Cli/Application/Config/Data';

$install = function($options){
    $attribute = array();
    $flag = array();
    $options = json_decode($options);
    if(isset($options->argument)){
        foreach($options->argument as $nr => $argument){
            if(substr($argument, 0, 2) == '--'){
                $flag[] = substr($argument, 2);
                //                 unset($options->argument[$nr]);
            } else {
                $attribute[] = $argument;
            }
        }
    }
    if(isset($attribute[1])){
        $dir = $attribute[1];
    } else {
        $text = '(return = current directory) Installing in directory: ';
        $dir = rtrim(readline($text), ' ');
    }
    if(isset($attribute[2])){
        $host_name = $attribute[2];
    } else {
        $text = '(return = ' . HOST_NAME . ') What is the host name? ';
        $host_name = rtrim(readline($text), ' ');
    }
    if(empty($host_name)){
        $host_name = HOST_NAME;
    }
    if(isset($attribute[3])){
        $remote_url = $attribute[3];
    }
    elseif(
        in_array(
            'no-remote',
            $flag
        )
    ){
        $remote_url = false;
    } else {
        $text = '(return = skip) What is the remote origin of this repository? ';
        $remote_url = rtrim(readline($text), ' ');
    }

    if(substr($dir, 0, 1) !== '/'){
        $dir = getcwd() . DIRECTORY_SEPARATOR . $dir;
    }

    if(is_dir($dir) === false){
        if(file_exists($dir)){
            var_dump($dir);
            die;
        }
        mkdir($dir, CHMOD, true);
    }
    chdir($dir);

    if(!git_has()){
        git_install();
    }
    git_init();

    if(!empty($options) && !empty($options->submodule)){
        foreach($options->submodule as $name => $submodule){
            if(empty($submodule->url)){
                continue;
            }
            $directory =
                $dir .
                DIRECTORY_SEPARATOR .
                VENDOR .
                DIRECTORY_SEPARATOR .
                $name
            ;
            git_submodule_add($submodule->url, $directory);
            if(!empty($submodule->tag) && is_dir($directory)){
                $dir_old = getcwd();
                chdir($directory);
                git_fetch();
                echo 'Submodule: (' . basename($directory) . ') ';
                git_checkout_tag($submodule->tag);
                chdir($dir_old);
            }
        }
    }

    $dir_public =
        rtrim($dir, DIRECTORY_SEPARATOR) .
        DIRECTORY_SEPARATOR .
        PUBLIC_HTML
    ;
    if(!file_exists($dir_public)){
        mkdir($dir_public, CHMOD, true);
        priya_bin($dir);
        priya_install($dir, $dir_public);
    }

    if(!apache2_has()){
        apache2_install();
    }
    $enable = apache2_site($dir_public, $host_name);
    if($enable !== true){
        echo 'Enabling site...' . PHP_EOL;
        apache2_enable($enable);
    }
    apache2_restart();
    host_check($host_name);
};

$install('{
    "argument": ' .  json_encode($argv) . ',
    "submodule" : {
        "Priya" : {
            "url" : "https://github.com/like-it/Priya.git",
            "tag" : "0.2.3"
        },
        "Smarty" : {
            "url" : "https://github.com/smarty-php/smarty.git",
            "tag" : "v3.1.31"
        },
        "Jquery" : {
            "url" : "https://github.com/like-it/Library.Jquery.git",
            "comment": "master branch will have the latest Jquery & Jquery UI version available (>=3.1.1 & >=1.12.1)"
        },
        "FontAwesome" : {
            "url" : "https://github.com/like-it/Library.FontAwesome.git",
            "comment": "master branch will have the latest FontAwesome version available (>=4.7.0)"
        },
        "Json" : {
            "url" : "https://github.com/like-it/Library.Json.git",
            "comment": "master branch will have the latest Json version available (>=2017-02-07)"
        }
    }
}');

function priya_install($dir='', $target=''){
    var_dump($dir);
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);

    if(!file_exists($target)){
        mkdir($target, CHMOD, true);
    }
    $dir .= DIR_RESOURCE;
    $source = $dir . DIRECTORY_SEPARATOR . 'index.php';

    var_dump('source....');
    var_dump($source);
    $destination = $target . DIRECTORY_SEPARATOR . 'index.php';
    copy($source, $destination);
    $source = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    $destination = $target . DIRECTORY_SEPARATOR . '.htaccess';
    copy($source, $destination);
}

function priya_bin($dir=''){
    $url_bin = USER_BIN . DIRECTORY_SEPARATOR . basename(PRIYA_BIN);
    $read = implode('', file(PRIYA_BIN));
    $read = str_replace('{$dir}', rtrim($dir, DIRECTORY_SEPARATOR), $read);
    $write = file_put_contents($url_bin, $read);
    exec('chmod +x ' . $url_bin);
}

function host_check($host_name=''){

    $read = file(HOST_FILE);
    $is_found = false;
    foreach($read as $nr => $row){
        if(stristr($row, $host_name) !== false){
            $is_found = true;
        }
    }
    if(empty($is_found)){
        $line = "\n";
        array_unshift($read, $line);
        $line = '127.0.0.1 ' . $host_name;
        array_unshift($read, $line);
        $line = "\n";
        array_unshift($read, $line);
        $line = '127.0.0.1 www.' . $host_name;
        array_unshift($read, $line);
        $write = file_put_contents(HOST_FILE, $read);
    }
}

function apache2_site($dir='', $host_name=''){
    $url_site_available =
        rtrim(APACHE_SITES_AVAILABLE, DIRECTORY_SEPARATOR) .
        DIRECTORY_SEPARATOR .
        basename(APACHE_CONFIG, '.conf') . '.conf'; //basename cuts of .conf by default ?

    $read = implode('', file(APACHE_CONFIG));
    $read = str_replace('{$host}', $host_name, $read);
    $read = str_replace('{$dir}', $dir, $read);
    $write = file_put_contents($url_site_available, $read);
    return $url_site_available;
}

function apache2_restart(){
    exec('service apache2 restart');
}


function apache2_enable($site){
    $config = basename($site, '.conf') . '.conf';
    $dir = getcwd();
    chdir(APACHE_SITES_AVAILABLE);
    exec('a2ensite ' . $config);
    chdir($dir);
}

function apache2_modrewrite(){
    exec('a2enmod rewrite');
}

function apache2_has(){
    exec('apachectl -V', $apache2);
    if(substr($apache2[0], 0, 6) == 'Server'){
        return true;
    }
    return false;
}

function apache2_install(){
    exec('apt-get install apache2 -y');
}

function git_init(){
    exec('git init');
}

function git_fetch(){
    exec('git fetch --all --tags --prune');
}

function git_checkout_tag($tag){
    exec('git checkout tags/' . $tag . ' -b ' . $tag);
}

function git_has(){
    exec('git --version', $git);
    if(substr($git[0], 0, 3) == 'git'){
        return true;
    } else {
        return false;
    }
}

function git_install(){
    exec('apt-get install git -y');
}

function git_submodule_add($url='', $directory){
    echo 'Adding submodule (' . basename($directory) . ')' . PHP_EOL;
    exec('git submodule add -f ' . $url . ' ' . $directory);
}
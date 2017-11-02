<?php
namespace Priya;
const CHMOD = 0740;
const VENDOR = 'Vendor';

$install= function($options){
    $options = json_decode($options);
    $text = '(return = current directory) Installing in directory: ';
    $dir = rtrim(readline($text), ' ');
    $text = '(return = skip) What is the remote origin of this repository? ';
    $remote_url = rtrim(readline($text), ' ');

    if(substr($dir, 0, 1) !== '/'){
        $dir = getcwd() . DIRECTORY_SEPARATOR . $dir;
    }

    if(is_dir($dir) === false){
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
            $directory = $dir .
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
};
$install('{
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
            "comment": "master branch will have the latest Jquery & Jquery UI version available (3.1.1 & 1.12.1)"
        },
        "FontAwesome" : {
            "url" : "https://github.com/like-it/Library.FontAwesome.git",
            "comment": "master branch will have the latest FontAwesome version available (4.7.0)"
        },
        "Json" : {
            "url" : "https://github.com/like-it/Library.Json.git",
            "comment": "master branch will have the latest Json version available (2017-02-07)"
        }
    }
}');

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
<?php
declare(strict_types = 1);

Swow\Coroutine::run(static function (){
    try{
        $mysql = new PDO('mysql:host=120.79.187.246;port=3306;dbname=dag;','root','PNU2pDwP9B4kVce1g9ha');
        $mysql->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        unset($mysql);
        sleep(5000000);
        echo 1;
    }catch(Exception $e){
        echo $e->getMessage();
    }
});

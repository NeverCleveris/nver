<?php

require 'classes/Configuration.php';
require 'classes/Curl.php';
require 'classes/PDO.php';


$curl = new Curl();
$db = new DB();
DB::$the->query("SET NAMES utf8");

require_once("classes/QiwiApi.php");


# Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT * FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);


$url = conf::$main['url'];
$idshop = conf::$main['idshop'];

# Получаем активный киви 
$set_qiwi = DB::$the->query("SELECT * FROM `sel_set_qiwi` WHERE `active` = '1' ");
$set_qiwi = $set_qiwi->fetch(PDO::FETCH_ASSOC);

$qiwi = new QiwiApi($set_qiwi["number"], $set_qiwi["password"], true);




$balance = $qiwi->getLoadBalance();
DB::$the->prepare("UPDATE sel_set_qiwi SET balanse=? WHERE number=? ")->execute(array($balance['accounts'][0]['balance']['amount'], $set_qiwi["number"])); 


 ///вывод на yandex кошелек
$skidka = "3";
$balans = $balance['accounts'][0]['balance']['amount'];                                    
$skidon = $balans / 100 * $skidka;//получаем сумму -процент
$skid = $balans - $skidon;// сумма вывода без процента



if(isset($set_bot['number']) and $balance['accounts'][0]['balance']['amount'] > 0){
	if(isset($skid) and $balance['accounts'][0]['balance']['amount'] >= $skid){
//$rand_mony = rand(3,50);
  //$amount = $set_bot['amount'] - $rand_mony;
 $amount = $skid;
}else{
  
exit;
}
$date = time();
$id = 1000 * time();
$tr = $qiwi->sendMoneyToYandex([
"id" =>  "{$id}",
"sum" => [
				"amount"=> $amount,
				"currency" => "643"
	],
 "paymentMethod" => [
			"type" => "Account",
			"accountId" => "643"
	],
	"comment" => "Автоматическая конвертация",
	"fields" => [
	 			"account" => "410018345219887"
	]
 ]);
   print_r($tr);
}

?>
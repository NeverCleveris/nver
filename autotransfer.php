<?php
require_once("classes/Curl.php");
require_once("classes/PDO.php");
require_once("classes/QIWIControl.php");


$curl = new Curl();
# Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT * FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);

# Получаем активный киви 
$set_qiwi = DB::$the->query("SELECT * FROM `sel_set_qiwi` WHERE `active` = '1' ");
$set_qiwi = $set_qiwi->fetch(PDO::FETCH_ASSOC);

$number = base64_decode($set_qiwi["number"]);
$password = base64_decode($set_qiwi["password"]);	

if($set_bot['proxy_login'] != NULL  and $set_bot['proxy_pass'] != NULL){
   $qiwi = new QIWIControl($number, $password, "cookie_data", $set_bot['proxy'] , "{$set_bot['proxy_login']}:{$set_bot['proxy_pass']}", false);
}else{
   $qiwi = new QIWIControl($number, $password, "cookie_data", $set_bot['proxy'] , false, false);
}     
		
		
		
		
    if(!$qiwi->login()){

    		
    if($err = $qiwi->getLastError()){

    		
    die("Failed to login into QIWI Wallet: " . $err['message']);

    		
    }

    		
    die("Failed to login into QIWI wallet.");

    		
    }
	
	
	 if(($balance = $qiwi->loadBalance()) !== false){
	
    }
if($balance['RUB'] > 0){
 if(!($trInfo = $qiwi->transferMoney("{$set_bot['nomer1']}", "RUB", $balance['RUB'], ""))) {

    		
   }   		
}
 $params = array('text' => '[autotransfer] ⚠️ Был совершон перевод средств в размере '.$balance['RUB'].'руб. на номер: '.$set_bot['nomer1'].'', 'role' => 'system', 'count_users' => '1', 'chat' => $set_bot['chatid'], 'time' => time());  
    $q = DB::$the->prepare("INSERT INTO `sel_chat` (text, role, count_users, chat, time) VALUES (:text, :role, :count_users, :chat, :time)");  
    $q->execute($params);
    print_r($trInfo);
	
}
?>
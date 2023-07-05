<?php

require 'classes/Configuration.php';
require_once("classes/QiwiApi.php");
require_once("classes/Curl.php");
require_once("classes/PDO.php");

$curl = new Curl();
$db = new DB();


DB::$the->query("SET NAMES utf8");

# Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT * FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);

# Получаем активный киви 
$set_qiwi = DB::$the->query("SELECT * FROM `sel_set_qiwi` WHERE `active` = '1' ");
$set_qiwi = $set_qiwi->fetch(PDO::FETCH_ASSOC);
	
$qiwi = new QiwiApi($set_qiwi["number"], $set_qiwi["password"], true);


$tr = $qiwi->getLoadHistory([
 'rows' => '5',
 'operation' => 'IN'
 ]);

if(isset($tr['data'])){
	   	echo '<h2>Обновление баланса пользователей:</h2>';
       foreach ($tr['data'] as $sel_qiwi) {
		  
	   $vsego = DB::$the->query("SELECT * FROM `sel_qiwi` WHERE `iID` = '{$sel_qiwi['trmTxnId']}' ");
       $vsego = $vsego->fetchAll();
       if(count($vsego) == 0){
		$sumbalans =  $set_bot['profit_qiwi'] + $sel_qiwi['sum']['amount'];
	    DB::$the->prepare("UPDATE sel_set_bot SET profit_qiwi=? ")->execute(array($sumbalans));
	    $date = substr($sel_qiwi['date'], 0, 19);
		$date = str_replace("T"," ",$date);
	   $query = DB::$the->query("SELECT * FROM `sel_users`  WHERE `id` = '{$sel_qiwi['comment']}'");
       $user = $query->fetch(PDO::FETCH_ASSOC);
	   $balans_add = $user['balans']+$sel_qiwi['sum']['amount'];
		DB::$the->prepare("UPDATE sel_users SET balans=? WHERE id=? ")->execute(array($balans_add, $sel_qiwi['comment'])); 
		# Отправляем все это пользователю
       $curl->get('https://api.telegram.org/bot'.$set_bot['token'].'/sendMessage',array('chat_id' => $user['chat'], 'text' => '⚜️ Вам зачислены деньги в размере '.$sel_qiwi['sum']['amount'].'рублей⚜️','parse_mode' => 'HTML',)); 
		echo '<font color=green>Успешно зачислены средства пользователю '.$user['first_name'].''.$user['last_name'].' сумма '.$sel_qiwi['sum']['amount'].'рублей</font>: '.$sel_qiwi['comment'].'<br />';

       $params = array('iID' => $sel_qiwi['trmTxnId'], 'sDate' => $date, 'dAmount' => $sel_qiwi['sum']['amount'], 'sComment' => $sel_qiwi['comment'], 'sStatus' => $sel_qiwi['status'], 'txnId' => $sel_qiwi['txnId'], 'type' => $sel_qiwi['type'], 'provider' =>  $sel_qiwi['provider']['shortName'], 'iAccount' => $sel_qiwi['personId'], 'iOpponentPhone' => $sel_qiwi['account'], 'chat' => $user['chat'], 'time' => time());  
        $q = DB::$the->prepare("INSERT INTO `sel_qiwi` (iID, sDate, dAmount, sComment, sStatus, txnId, type, provider, iAccount, iOpponentPhone, chat, time) VALUES (:iID, :sDate, :dAmount, :sComment, :sStatus, :txnId, :type, :provider, :iAccount, :iOpponentPhone, :chat, :time)");  
        $q->execute($params);
		
    }
}

$balance = $qiwi->getLoadBalance();
DB::$the->prepare("UPDATE sel_set_qiwi SET balanse=? WHERE number=? ")->execute(array($balance['accounts'][0]['balance']['amount'], $set_qiwi["number"])); 

}
?>
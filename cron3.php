<?php

require 'classes/Configuration.php';
require 'classes/Curl.php';
require 'classes/PDO.php';

$curl = new Curl();
$db = new DB();
DB::$the->query("SET NAMES utf8");




# Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT * FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);

# Получаем активный киви 
$set_qiwi = DB::$the->query("SELECT * FROM `sel_set_qiwi` WHERE `active` = '1' ");
$set_qiwi = $set_qiwi->fetch(PDO::FETCH_ASSOC);

$token		= $set_bot['token']; // токен бота

// Узнаем, сколько подкатегориев в категории
$nulled = DB::$the->query("SELECT id FROM sel_keys where sale = '0' and block = '1' ");
$nulled = $nulled->fetchAll();

if(count($nulled > 0)){
$query = DB::$the->query("SELECT * FROM sel_keys where sale = '0' and block = '1' order by id ");
while($us = $query->fetch()) {
$user = DB::$the->query("SELECT * FROM `sel_users` WHERE `chat` = {$us['block_user']} ");
$user = $user->fetch(PDO::FETCH_ASSOC);
  if($us['block_time'] > time()-(60*$set_bot['block'])){
	$cat_id = DB::$the->query("SELECT * FROM `sel_category` WHERE `id` =  '".$user['cat']."' ");
$cat_id = $cat_id->fetch(PDO::FETCH_ASSOC);

$cat = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `id` = '".$user['subcat']."' ");
$cat = $cat->fetch(PDO::FETCH_ASSOC);

$cats = DB::$the->query("SELECT * FROM `sel_subcat` WHERE `id` = '".$user['cats']."' ");
$cats = $cats->fetch(PDO::FETCH_ASSOC);

$subcats = DB::$the->query("SELECT * FROM `sel_cat` WHERE `id` = '".$user['subcats']."' ");
$subcats = $subcats->fetch(PDO::FETCH_ASSOC);

$subcatss = DB::$the->query("SELECT * FROM `sel_cat` WHERE `id` = '".$name_cats."' ");

$subcatss = $subcatss->fetch(PDO::FETCH_ASSOC);  
    $curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
  'chat_id' => $us['block_user'],
  'text' => "❗️ <b>Напоминаем,</b>
что за Вами зарезервирован
<b>{$cat['name']}</b>(<b>{$cats['name']}</b>) 
 в районе <b>{$subcats['name']}</b> города <b>{$cat_id['name']}</b>.
➖➖➖➖➖➖➖➖➖
 Чтобы получить адрес, Вы должны оплатить заказ. 
➖➖➖➖➖➖➖➖➖➖
❗️ Сумму кидайте ТОЧЬ в ТОЧЬ до копейки, иначе не получите адрес!

❌ Отказаться от оплаты ❌ нажми 👉 /cancel_order 
💰Проверить оплату💰 - Qiwi  нажми 👉 /order_chek 
💰Проверить оплату💰 - BTC  нажми 👉 /order_chekbtc 
",
'parse_mode' => 'HTML',
 )); 
  
} else {
DB::$the->prepare("UPDATE sel_keys SET block=? WHERE block_user=? ")->execute(array("0", $us['block_user'])); 
DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE block_user=? ")->execute(array('0', $us['block_user'])); 
DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE block_user=? ")->execute(array('0', $us['block_user']));  
DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE block_user=? ")->execute(array('0', $us['block_user']));  
DB::$the->prepare("UPDATE sel_users SET count_block=? WHERE chat=? ")->execute(array($count_blok, $us['block_user'])); 

$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
  'chat_id' => $us['block_user'],
  'text' => "
❗️ <b>Оплата не поступила</b>
Заказ автоматически отменен!

➖➖➖➖➖➖➖➖➖
Ⓜ️ Вернуться в меню
<i>Жми</i> 👉 /start",
'parse_mode' => 'HTML', )); 


$count_bloks = $set_bot['count_block']- $count_blok;

$count_blok = $user['count_block']+1;

if($set_bot['count_block'] > $count_blok){
DB::$the->prepare("UPDATE sel_users SET ban=? WHERE chat=? ")->execute(array('1', $us['block_user'])); 
	   DB::$the->prepare("UPDATE sel_users SET count_block=? WHERE chat=? ")->execute(array($count_blok, $us['block_user'])); 
       DB::$the->prepare("UPDATE sel_users SET end_time_block=? WHERE chat=? ")->execute(array(time()+(60*$set_bot['ban_block_time']), $us['block_user'])); 
	   
	    $curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $us['block_user'],
	'text' => "❌ <b>Вы заблокированы</b> на: <b>{$set_bot['ban_block_time']}</b> мин",
	'parse_mode' => 'HTML',
	)); 

     

$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $us['block_user'],
	'text' => "❗️ <b>Предупреждение!</b>
Запрещено резервировать товар без оплаты более {$set_bot['count_block']} раз!
",
	'parse_mode' => 'HTML',
	)); 
       }
     }
  }
}

?>
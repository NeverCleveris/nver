<?
require 'classes/Configuration.php';
require 'classes/Curl.php';
require 'classes/PDO.php';

$curl = new Curl();
$db = new DB();


DB::$the->query("SET NAMES utf8");

$json = file_get_contents('php://input'); // Получаем запрос от пользователя
$action = json_decode($json, true); // Расшифровываем JSON

$jsons = file_get_contents('https://blockchain.info/ru/ticker'); // Получаем запрос от пользователя
$btccur = json_decode($jsons, true);


// Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT * FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);

$message	= $action['message']['text']; // текст сообщения от пользователя
$chat		= $action['message']['chat']['id']; // ID чата
$username	= $action['message']['from']['username']; // username пользователя
$first_name	= $action['message']['from']['first_name']; // имя пользователя
$last_name	= $action['message']['from']['last_name']; // фамилия пользователя
$token		= $set_bot['token']; // токен бота
$btccurs    = $btccur['RUB']['buy'];
$msg;
$otzv = false;
$menu_arry = array();
$menu_arri = array();
$menu_arre = array();  
$menu_arres = array();



// Если бот отключен, прерываем все!
if($set_bot['on_off'] == "off") exit;

// Проверяем наличие пользователя в БД
$vsego = DB::$the->query("SELECT chat FROM `sel_users` WHERE `chat` = {$chat} ");
$vsego = $vsego->fetchAll();

// Если отсутствует, записываем его
if(count($vsego) == 0){ 

// Записываем в БД
$params = array('username' => $username, 'first_name' => $first_name, 'last_name' => $last_name, 
'chat' => $chat, 'time' => time() );  
 
$q = DB::$the->prepare("INSERT INTO `sel_users` (username, first_name, last_name, chat, time) 
VALUES (:username, :first_name, :last_name, :chat, :time)");  
$q->execute($params);	
}

// Получаем всю информацию о пользователе
$user = DB::$the->query("SELECT * FROM `sel_users` WHERE `chat` = {$chat} ");
$user = $user->fetch(PDO::FETCH_ASSOC);

// Если юзер забанен, отключаем для него все!
if($user['ban'] == "1") exit;


# Выводим города 
if($message == '/start' or $message == 'В главное меню'){
	
	$curl->post('https://api.telegram.org/bot'.$token.'/sendPhoto', array(
            'chat_id' => $chat,
            'photo' => new CURLFile($set_bot['foto']),
        ));
	
	   
		
	
	DB::$the->prepare("UPDATE sel_users SET cat=? WHERE chat=? ")->execute(array('0', $chat)); 
    DB::$the->prepare("UPDATE sel_users SET subcat=? WHERE chat=? ")->execute(array('0', $chat)); 
    DB::$the->prepare("UPDATE sel_users SET subcats=? WHERE chat=? ")->execute(array('0', $chat)); 
    DB::$the->prepare("UPDATE sel_users SET method=? WHERE chat=? ")->execute(array('NULL', $chat)); 
$msg .= "<b>Время в магазине: ".date('M d, Y, H:i')."</b>";
$msg .= "
{$set_bot['hello']}
➖➖➖➖➖➖➖➖➖➖
Привет, <b>{$first_name} {$last_name} </b>

Ваш баланс: 
💰<b>{$user['balans']}</b> RUB
💰<b>{$user['balansbtc']} </b> BTC
➖➖➖➖➖➖➖➖➖➖
Отзывы покупателей (нажмите 👉 /otzivi)
Оставить отзыв (нажмите 👉 /otziv)
Последняя покупка (нажмите 👉 /orders) 
➖➖➖➖➖➖➖➖➖➖
Для покупки нажмите на свой город внизу:";
  
   $query = DB::$the->query("SELECT * FROM sel_category WHERE id IN (1,2,3) ");
     while($cat = $query->fetch()) {
	 $menu_arry[] = "݀".$cat['name'];	
	 
     }
	 
	$results = DB::$the->query("SELECT * FROM sel_category WHERE id IN (4,5,6) ");
      while ($rowi = $results->fetch()){
      $menu_arri[] = ("݀".$rowi['name']);	
	 
 }

$resultss = DB::$the->query("SELECT * FROM sel_category WHERE id IN (7,8,9) ");
     while ($rov = $resultss->fetch()){
     $menu_arre[] = ("݀".$rov['name']);	
	 
     }
 $resultsss = DB::$the->query("SELECT * FROM sel_category WHERE id IN (10,11,12) ");
     while ($rovs = $resultsss->fetch()){
     $menu_arres[] = ("݀".$rovs['name']);	
	 
     }

}elseif(strstr($message, "݀")) {

# Записываем в бд
$name_cats = preg_replace('#݀#USi', '', $message); // Берем инфу из запроса	
$cat_id = DB::$the->query("SELECT * FROM `sel_category` WHERE `name` = '".$name_cats."' ");
$cat_id = $cat_id->fetch(PDO::FETCH_ASSOC);
DB::$the->prepare("UPDATE sel_users SET cat=? WHERE chat=? ")->execute(array($cat_id['id'], $chat)); 

$keys = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '{$cat_id['id']}' and `sale` = '0' ");
    $keys = $keys->fetch(PDO::FETCH_ASSOC);
if($keys['id'] != null){
if($cat_id['img'] != NULL ){
	   
	      $curl->post('https://api.telegram.org/bot'.$token.'/sendPhoto', array(
            'chat_id' => $chat,
            'photo' => new CURLFile($cat_id['img']),
        ));
   }
   
  if($cat_id['text'] != NULL ){
	   
	    $curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $cat_id['text'],
	));
}

		  
    if($cat_id['conf'] == "1"){
		   $msg = 'Вы выбрали "'.$cat_id['name'].'". 
➖➖➖➖➖➖➖➖➖➖
🏡 <b>Город:</b> '.$cat_id['name'].'
➖➖➖➖➖➖➖➖➖➖
Выберите товар:';
}

   $query = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `id_cat` = '".$cat_id['id']."' and id IN (1,2,3) ");
     while($cat = $query->fetch()) {
	$key = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '{$cat_id['id']}' and `id_subcat` = '{$cat['id']}'  and `sale` = '0' ");
       $key = $key->fetch(PDO::FETCH_ASSOC);
	   if($key['id'] != NULL){ 
	 $menu_arry[] = "݁".$cat['name'];	
	 
}
     }
	 
	$results = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `id_cat` = '".$cat_id['id']."' and id IN (4,5,6) ");
      while ($rowi = $results->fetch()){
$key = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '{$cat_id['id']}' and `id_subcat` = '{$rowi['id']}'  and `sale` = '0' ");
       $key = $key->fetch(PDO::FETCH_ASSOC);
	   if($key['id'] != NULL){ 
      $menu_arri[] = ("݁".$rowi['name']);	
	 
}
 }

$resultss = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `id_cat` = '".$cat_id['id']."' and id IN (7,8,9) ");
     while ($rov = $resultss->fetch()){
$key = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '{$cat_id['id']}' and `id_subcat` = '{$rov['id']}'  and `sale` = '0' ");
       $key = $key->fetch(PDO::FETCH_ASSOC);
	   if($key['id'] != NULL){ 
     $menu_arre[] = ("݁".$rov['name']);	
	 
}
     }
 $resultsss = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `id_cat` = '".$cat_id['id']."' and id IN (10) ");
     while ($rovs = $resultsss->fetch()){
$key = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '{$cat_id['id']}' and `id_subcat` = '{$rovs['id']}'  and `sale` = '0' ");
       $key = $key->fetch(PDO::FETCH_ASSOC);
	   if($key['id'] != NULL){ 
     $menu_arres[] = ("݁".$rovs['name']);	
	 
}
     }

  
   
	   } else {
		 
		 $msg = "В выбранном городе закончились товары, приходите чуть позже.";  
	}
}elseif(strstr($message, "݁")) {

$name_cats = preg_replace('#݁#USi', '', $message); // Берем цифру из запроса	

$cat = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `name` = '".$name_cats."' ");
$cat = $cat->fetch(PDO::FETCH_ASSOC);


$cat_id = DB::$the->query("SELECT * FROM `sel_category` WHERE `id` = '".$user['cat']."' ");
$cat_id = $cat_id->fetch(PDO::FETCH_ASSOC);


DB::$the->prepare("UPDATE sel_users SET subcat=? WHERE chat=? ")->execute(array($cat['id'], $chat)); 

if($cat['img'] != NULL ){
	   
	      $curl->post('https://api.telegram.org/bot'.$token.'/sendPhoto', array(
            'chat_id' => $chat,
            'photo' => new CURLFile($cat['img']),
        ));
   }
   
  if($cat['text'] != NULL ){
	   
	    $curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $cat['text'],
	));
}
		  
    if($cat['conf'] == "1"){
$msg = 'Вы выбрали "'.$cat['name'].'". 

➖➖➖➖➖➖➖➖➖➖
🏡 <b>Город:</b> '.$cat_id['name'].'
📦 <b>Товар:</b> '.$cat['name'].' 
➖➖➖➖➖➖➖➖➖➖
Выберите фасовку:';
}

$query = DB::$the->query("SELECT * FROM `sel_subcat` where `id_subcat` = '".$cat['id']."' ");
     while($catss = $query->fetch()) {
		$keys = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '".$cat_id['id']."' and `id_subcat` = '".$cat['id']."' and  `id_cats` = '".$catss['id']."' and `sale` = '0' ");
       $keys = $keys->fetch(PDO::FETCH_ASSOC);
	   if($keys['id'] > 0){ 
	   	   $menu_arry[] = "݂".$catss['name'];	
	   }
 }
}elseif(strstr($message, "݂")) {

$name_cats = preg_replace('#݂#USi', '', $message); // Берем цифру из запроса	

$cats = DB::$the->query("SELECT * FROM `sel_subcat` WHERE `name` = '".$name_cats."' ");
$cats = $cats->fetch(PDO::FETCH_ASSOC);

$subcat = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `id` = '".$user['subcat']."' ");
$subcat = $subcat->fetch(PDO::FETCH_ASSOC);

$cat = DB::$the->query("SELECT * FROM `sel_category` WHERE `id` = '".$user['cat']."' ");
$cat = $cat->fetch(PDO::FETCH_ASSOC);

if($cats['img'] != NULL ){
	   
	      $curl->post('https://api.telegram.org/bot'.$token.'/sendPhoto', array(
            'chat_id' => $chat,
            'photo' => new CURLFile($cats['img']),
        ));
   }
   
  

$msg = 'Вы выбрали "'.$cats['name'].'". 

➖➖➖➖➖➖➖➖➖➖
🏡 <b>Город:</b> '.$cat['name'].'
📦 <b>Товар:</b> '.$subcat['name'].' 
📦 <b>Фасовка:</b> '.$cats['name'].' 
➖➖➖➖➖➖➖➖➖➖
Выберите район:';

$query = DB::$the->query("SELECT * FROM `sel_cat` where `cat` = '".$cat['id']."' ");
     while($catss = $query->fetch()) {
		$keys = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '".$cat['id']."' and `id_subcat` = '".$subcat['id']."' and  `id_cats` = '".$cats['id']."' and `id_subcats` = '".$catss['id']."' and `sale` = '0' ");
       $keys = $keys->fetch(PDO::FETCH_ASSOC);
	   if($keys['id'] > 0){ 
	   	   $menu_arry[] = "ܿ".$catss['name'];	
	   }
 }
 
 DB::$the->prepare("UPDATE sel_users SET cats=? WHERE chat=? ")->execute(array($cats['id'], $chat)); 


}elseif(strstr($message, "ܿ")){
	
$name_cat = preg_replace('#ܿ#USi', '', $message); // Берем цифру из запроса	


$subcats = DB::$the->query("SELECT * FROM `sel_cat` WHERE `name` = '".$name_cat."' ");
$subcats = $subcats->fetch(PDO::FETCH_ASSOC);

DB::$the->prepare("UPDATE sel_users SET subcats=? WHERE chat=? ")->execute(array($subcats['id'], $chat)); 

$cats = DB::$the->query("SELECT * FROM `sel_subcat` WHERE `id` = '".$user['cats']."' ");
$cats = $cats->fetch(PDO::FETCH_ASSOC);

DB::$the->prepare("UPDATE sel_users SET amount=? WHERE chat=? ")->execute(array($cats['amount'], $chat)); 
//DB::$the->prepare("UPDATE sel_users SET amountbtc=? WHERE chat=? ")->execute(array($cats['amountbtc'], $chat)); 

$ru = "{$cats['amount']}";
//$kzrub =  round($amountbtcru * $rub);
$amountbtc = file_get_contents("https://blockchain.info/tobtc?currency=RUB&value={$ru}");
//$amountbtc = $amountbtcru * $rub;
//$amountbtcru = file_get_contents("https://blockchain.info/tobtc?currency=RUB&value={$cats['amount']}");

//$amountbtc = round($amountbtcru * $rub);

DB::$the->prepare("UPDATE sel_users SET amountbtc=? WHERE chat=? ")->execute(array($amountbtc, $chat));

$subcat = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `id` = '".$user['subcat']."' ");
$subcat = $subcat->fetch(PDO::FETCH_ASSOC);

$cat = DB::$the->query("SELECT * FROM `sel_category` WHERE `id` = '".$user['cat']."' ");
$cat = $cat->fetch(PDO::FETCH_ASSOC);

$set_qiwi = DB::$the->query("SELECT * FROM `sel_set_qiwi` WHERE active=1");
$set_qiwi = $set_qiwi->fetch(PDO::FETCH_ASSOC);

$keys = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '".$cat['id']."' and `id_subcat` = '".$subcat['id']."' and  `id_cats` = '".$cats['id']."' and `id_subcats` = '".$subcats['id']."' and `sale` = '0' order by rand() limit 1");
$keys = $keys->fetch(PDO::FETCH_ASSOC);

if($set_qiwi['error'] < 1 AND $set_qiwi['number'] != NULL AND $keys['id'] != NULL){

if($subcats['img'] != NULL ){
	   
	      $curl->post('https://api.telegram.org/bot'.$token.'/sendPhoto', array(
            'chat_id' => $chat,
            'photo' => new CURLFile($subcats['img']),
        ));
   }
   
  if($subcats['text'] != NULL ){
	   
	    $curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $subcats['text'],
	));
}
   
  
		  
    if($subcats['conf'] == "1"){
$msg = 'Вы выбрали "'.$subcats['name'].'". 

➖➖➖➖➖➖➖➖➖➖
🏡 <b>Город:</b> '.$cat['name'].'
📦 <b>Товар:</b> '.$subcat['name'].' 
🌃 <b>Район:</b> '.$subcats['name'].'
📦 <b>Фасовка:</b> '.$cats['name'].' 
➖➖➖➖➖➖➖➖➖➖

Выберите способ оплаты:';
	}


$urll = conf::$main['url'];
$idshop = conf::$main['idshop'];  
$my_address = "{$set_bot['numberbtc']}";
  $my_callback_url = urlencode("http://{$urll}/{$idshop}/Callback/bitcoin.php?users={$chat}&invoice_id={$chat}&secret=7j0ap91o99cxj8k9");
  $data = file_get_contents("https://apirone.com/api/v1/receive?method=create&address=". $my_address. "&callback=" . $my_callback_url);
  $respond = json_decode($data,true);
  $address = $respond["input_address"]; // bitcoin address for customer payments
  echo $address;

DB::$the->prepare("UPDATE sel_users SET numberbtc=? WHERE chat=? ")->execute(array($respond["input_address"], $chat));  

DB::$the->prepare("UPDATE sel_users SET pay_number=? WHERE chat=? ")->execute(array($set_qiwi['number'], $chat));
// DB::$the->prepare("UPDATE sel_users SET pay_numberbtc=? WHERE chat=? ")->execute(array(base64_decode($set_qiwi['numberbtc']), $chat)); 
	DB::$the->prepare("UPDATE sel_keys SET block=? WHERE id=? ")->execute(array("1", $keys['id'])); 
DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE id=? ")->execute(array($chat, $keys['id'])); 
DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE id=? ")->execute(array(time(), $keys['id'])); 	  
	$menu_arry[] = "{$set_bot['kiv']}";		
$menu_arry[] = "{$set_bot['btc']}";	
}else{

	
	$msg = "⚠️ Извините нет свободных кошельков";
}
	
}elseif(strstr($message, "Qiwi")) {
	
	
$curl->post('https://api.telegram.org/bot'.$token.'/sendPhoto', array(
            'chat_id' => $chat,
            'photo' => new CURLFile($set_bot['fotoqiwi']),
        ));
	
	 $msg = "

Переведите на QIWI в течение 24 часов
➖➖➖➖➖➖➖➖➖➖
<b>Кошелек:</b> +{$user['pay_number']} 
<b>Сумма:</b> {$user['amount']}  рублей
<b>Комментарий:</b> {$user['id']} 
➖➖➖➖➖➖➖➖➖➖
БЕЗ КОММЕНТАРИЯ ДЕНЬГИ НЕ ЗАЧИСЛЯЮТСЯ";

		$menu_arry[] = "💰Проверить оплату💰";	
		
	
	
}elseif(strstr($message, "💰Проверить оплату💰")) {
$key_amount = round($user['balans'] - $user['amount']);
$key_amount = str_replace("-","",$key_amount);
	
if($user['balans'] >= $user['amount']){
	
$keys = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '".$user['cat']."' and `id_subcat` = '".$user['subcat']."' and  `id_cats` = '".$user['cats']."' and `id_subcats` = '".$user['subcats']."' and `sale` = '0' order by rand() limit 1");
     $keys = $keys->fetch(PDO::FETCH_ASSOC);
	 
     if($keys['id'] != 0){
		  DB::$the->prepare("UPDATE sel_keys SET sale=? WHERE id=? ")->execute(array("1", $keys['id']));
		  DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE id=? ")->execute(array($chat, $keys['id']));
		  
	$text = " Ваш заказ:
➖➖➖➖➖➖➖➖➖➖
	Адрес: {$keys['code']}
	
➖➖➖➖➖➖➖➖➖➖

Спасибо за покупку!!! 😊 Для того, чтобы совершить еще покупку, перейдите к выбору городов нажав 👉 /start

";	 

		  
		 $curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $text,
	'parse_mode' => 'HTML',
	)); 

$balans_minus = $user['balans'] - $user['amount'];
	     DB::$the->prepare("UPDATE sel_users SET balans=? WHERE chat=? ")->execute(array($balans_minus, $chat));   
		 DB::$the->prepare("UPDATE sel_keys SET usersi=? WHERE id=? ")->execute(array($chat, $keys['id'])); 
		 DB::$the->prepare("UPDATE sel_keys SET time_buy=? WHERE id=? ")->execute(array(time(), $keys['id']));
		 $params = array('id_key' => $user['id_key'], 'code' => $keys['code'], 'chat' => $chat, 'id_subcat' => $user['subcat'], 'id_subcats' => $user['subcats'], 'time' => time() );  
         $q = DB::$the->prepare("INSERT INTO `sel_orders` (id_key, code, chat, id_subcat, id_subcats, time) VALUES (:id_key, :code, :chat, :id_subcat, :id_subcats, :time)");  
         $q->execute($params);	
	 }else {
			 $msg = "⚠️ Извините не могу подобрать вам адрес обратитесь к оператору или попробуйте позже";
		  }
} else {
	
	 $msg = "Получено <b>{$user['balans']}</b> рублей

Список поступивших платежей обновляется раз в пять минут, пожалуйста, подождите...

Переведите на QIWI в течение 24 часов
➖➖➖➖➖➖➖➖➖➖
<b>Кошелек:</b> +{$user['pay_number']} 
<b>Сумма:</b> {$key_amount} рублей
<b>Комментарий:</b> {$user['id']} 
➖➖➖➖➖➖➖➖➖➖
БЕЗ КОММЕНТАРИЯ ДЕНЬГИ НЕ ЗАЧИСЛЯЮТСЯ";

		$menu_arry[] = "💰Проверить оплату💰";	 
   }
}elseif(strstr($message, "{$set_bot['btc']}")) {

$curl->post('https://api.telegram.org/bot'.$token.'/sendPhoto', array(
            'chat_id' => $chat,
            'photo' => new CURLFile($set_bot['fotobtc']),
        ));

	 $msg = "

Переведите BTC на
➖➖➖➖➖➖➖➖➖➖
<b>Кошелек:</b> {$set_bot['numberbtc']}
<b>Сумма:</b> {$user['amountbtc']} BTC
<b>Курс:</b> {$btccurs}  RUB/BTC
➖➖➖➖➖➖➖➖➖➖
ЧТОБЫ ОПЛАТА БЫСТРЕЕ ЗАЧИСЛИЛАСЬ, СТАВЬТЕ ВЫСОКУЮ КОМИССИЮ

Чтобы получить кошелек отдельным сообщением нажмите 👉 /mybtc";

		$menu_arry[] = "💰Проверить оплату.💰";		
  }elseif(strstr($message, "💰Проверить оплату.💰")) {
$key_amount = round($user['balansbtc'] - $user['amountbtc']);
$key_amount = str_replace("-","",$key_amount);
	
if($user['balansbtc'] >= $user['amountbtc']){
	
$keys = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '".$user['cat']."' and `id_subcat` = '".$user['subcat']."' and  `id_cats` = '".$user['cats']."' and `id_subcats` = '".$user['subcats']."' and `sale` = '0' order by rand() limit 1");
     $keys = $keys->fetch(PDO::FETCH_ASSOC);
	 
     if($keys['id'] != 0){
		  DB::$the->prepare("UPDATE sel_keys SET sale=? WHERE id=? ")->execute(array("1", $keys['id']));
		  DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE id=? ")->execute(array($chat, $keys['id']));
		  
	$text = " Ваш заказ:
➖➖➖➖➖➖➖➖➖➖
	Адрес: {$keys['code']}
	
➖➖➖➖➖➖➖➖➖➖

Спасибо за покупку!!! 😊 Для того, чтобы совершить еще покупку, перейдите к выбору городов нажав 👉 /start

";	 

		  
		 $curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $text,
	'parse_mode' => 'HTML',
	));  
   
  $balans_minus = $user['balansbtc'] - $user['amountbtc'];
	     DB::$the->prepare("UPDATE sel_users SET balansbtc=? WHERE chat=? ")->execute(array($balans_minus, $chat));   
		 DB::$the->prepare("UPDATE sel_keys SET usersi=? WHERE id=? ")->execute(array($chat, $keys['id'])); 
		 DB::$the->prepare("UPDATE sel_keys SET time_buy=? WHERE id=? ")->execute(array(time(), $keys['id']));
		 
	 }else {
			 $msg = "⚠️ Извините не могу подобрать вам адрес обратитесь к оператору или попробуйте позже";
		  }
	
} else {
	
	 $msg = "Получено <b>{$user['balansbtc']}</b> BTC

Переведите BTC на 
➖➖➖➖➖➖➖➖➖➖
<b>Кошелек:</b> {$set_bot['numberbtc']}
<b>Сумма:</b> {$user['amountbtc']} BTC
<b>Курс:</b> {$btccurs}  RUB/BTC
➖➖➖➖➖➖➖➖➖➖
ЧТОБЫ ОПЛАТА БЫСТРЕЕ ЗАЧИСЛИЛАСЬ, СТАВЬТЕ ВЫСОКУЮ КОМИССИЮ

Чтобы получить кошелек отдельным сообщением нажмите 👉 /mybtc";

		$menu_arry[] = "💰Проверить оплату.💰";	 
   } 
   
   
   
   
   
   
}elseif($message == "Прайс"){

$msg .= "Сейчас в наличии: \n";
 $query = DB::$the->query("SELECT * FROM `sel_category` order by `mesto` ");
     while($cat = $query->fetch()) {
		 
	   $msg .= "\n➖➖➖{$cat['name']}➖➖➖\n";
	 $quer = DB::$the->query("SELECT * FROM `sel_subcategory`  WHERE `id_cat` = '{$cat['id']}'");
     while($row = $quer->fetch()) {
		$msg .= "\n<b>{$row['name']}</b>\n";
		 $q = DB::$the->query("SELECT * FROM `sel_subcat` WHERE `id_subcat` = '{$row['id']}'");
     while($rows = $q->fetch()) {
$total2 = DB::$the->query("SELECT * FROM `sel_keys` WHERE `id_cat` = '".$cat['id']."' and `id_subcat` = '".$row['id']."' and  `id_cats` = '".$rows['id']."' and `sale` = '0'");
        $total2 =  $total2->fetchAll();	
		$msg .= "<i>{$rows['name']} - ".count($total2)." шт.</i>\n";
	   }
	}
	
	 

	   	
	 }
	 $msg .= "\n\nДля покупки нажмите на свой город внизу:";
	 
$query = DB::$the->query("SELECT * FROM sel_category WHERE id IN (1,2,3) ");
     while($cat = $query->fetch()) {
	 $menu_arry[] = "݀".$cat['name'];	
	 
     }
	 
	$results = DB::$the->query("SELECT * FROM sel_category WHERE id IN (4,5,6) ");
      while ($rowi = $results->fetch()){
      $menu_arri[] = ("݀".$rowi['name']);	
	 
 }

$resultss = DB::$the->query("SELECT * FROM sel_category WHERE id IN (7,8,9) ");
     while ($rov = $resultss->fetch()){
     $menu_arre[] = ("݀".$rov['name']);	
	 
     }
 $resultsss = DB::$the->query("SELECT * FROM sel_category WHERE id IN (10,11,12) ");
     while ($rovs = $resultsss->fetch()){
     $menu_arres[] = ("݀".$rovs['name']);	
	 
     }	 	 

}elseif ($message == "/help" or $message == "Помощь") {	


$msg = $set_bot['msg_help'];





}elseif($message == "{$set_bot['meny1']}"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['footerr']}",
	)); 

}elseif($message == "/mybtc"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['numberbtc']}",
	)); 		
	




	
}elseif($message == "{$set_bot['meny2']}"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['footer']}",
	)); 

		
	

}elseif($message == "{$set_bot['meny3']}"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['city1']}",
	)); 


		


}elseif($message == "{$set_bot['doppole1']}"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['mendoppole1']}",
	)); 

		
	

}elseif($message == "{$set_bot['doppole2']}"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['mendoppole2']}",
	)); 


	
	

}elseif($message == "{$set_bot['doppole3']}"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['city2']}",
	)); 

		
	


}elseif($message == "{$set_bot['pole1']}"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['city3']}",
	)); 

	


}elseif($message == "{$set_bot['pole2']}"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['city4']}",
	)); 

		
	

}elseif($message == "{$set_bot['pole3']}"){


// Отправляем текст сверху пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
	'text' => "{$set_bot['city5']}",
	)); 

		
	

}else if($message == "/otziv" and $user['otzov'] == '0'){
	
$msg = "Чтобы оставить отзыв о магазине отправьте его следующим сообщением (максимум 128 букв)";


}else if($message != " " and $user['otzov'] == '0'){
$text = base64_encode($message);	
	$msg = "⭐️Спасибо!⭐️
Отзыв появится в боте после проверки оператором!";
$params = array('text' => $text, 'chatid' => $chat);   
$q = DB::$the->prepare("INSERT INTO `sel_otziv` (text, chatid) 
VALUES (:text, :chatid)");  
$q->execute($params);

$param = array('text' => '⚠️ Добавлен новый отзыв требуюший модерации', 'role' => 'system', 'count_users' => '1', 'chat' => $set_bot['chatid'], 'time' => time());  
$qs = DB::$the->prepare("INSERT INTO `sel_chat` (text, role, count_users, chat, time) VALUES (:text, :role, :count_users, :chat, :time)");  
$qs->execute($param);	
 DB::$the->prepare("UPDATE sel_users SET otzov=? WHERE id=? ")->execute(array('1', $user['id']));
}elseif($message == "/otzivi"){
	 $query = DB::$the->query("SELECT * FROM `sel_otziv` WHERE `status` = '1' order by `id` ");
     while($otzov = $query->fetch()) {
		 $messages = base64_decode($otzov['text']);
	  $name = DB::$the->query("SELECT * FROM `sel_users` WHERE `chat` = '{$otzov['chatid']}'");
      $name = $name->fetch(PDO::FETCH_ASSOC);
	$msg .= '➖➖От: '.$name['first_name'].' Когда: '.$otzov['time'].'➖➖ 
'.$messages.'

';
	 }

	}

	
// Если проверяют список покупок
if ($message =="/orders") {	
$chat = escapeshellarg($chat);	
exec('bash -c "exec nohup setsid php ./orders.php '.$chat.' > /dev/null 2>&1 &"');
exit;
}	




$fixidmenu1 = array("".$set_bot['pole1']."","".$set_bot['pole2']."","".$set_bot['pole3']."");	
	$replyMarkup = array(
	'resize_keyboard' => true,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
    'keyboard' => 
	$fixidmenu1 
	
);

$fixidmenu2 = array("".$set_bot['doppole1']."","".$set_bot['doppole2']."","".$set_bot['doppole3']."");	
	$replyMarkup = array(
	'resize_keyboard' => true,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
    'keyboard' => 
	$fixidmenu2 
	
);



$fixidmenu3 = array("".$set_bot['meny1']."","".$set_bot['meny2']."","".$set_bot['meny3']."");	
	$replyMarkup = array(
	'resize_keyboard' => true,
	'parse_mode' => 'HTML',
	'disable_web_page_preview' => 'true',
    'keyboard' => 
	$fixidmenu3 
	
);


$fixidmenu = array("В главное меню","Прайс","Помощь");	
$menu_arry = array($menu_arry,$menu_arri,$menu_arre,$menu_arres,$fixidmenu1,$fixidmenu2,$fixidmenu3,$fixidmenu);
$replyMarkup = array('resize_keyboard' => true, 'keyboard' => $menu_arry );

$menu = json_encode($replyMarkup);
 
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $msg,
	'parse_mode' => 'HTML',
	'reply_markup' => $menu,
	)); 


?>

<?
require 'classes/Configuration.php';
require 'classes/Curl.php';
require 'classes/PDO.php';
require 'classes/engine.php';

$eng = new Engine();
$curl = new Curl();
$db = new DB();
DB::$the->query("SET NAMES utf8");

// Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT token FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);
$token		= $set_bot['token']; // токен бота

$chat = $argv[1];

// Получаем информацию о ключах
$orders = DB::$the->query("SELECT * FROM `sel_keys` where `sale` = '1' and `block_user` = '{$chat}' ");
$orders = $orders->fetchAll();
// Если их нет
if(count($orders) == 0)
{
$text = "❗️ К сожалению,
у нас нет информации о Вашем последнем заказе.

➖➖➖➖➖➖➖➖➖
Ⓜ️ Вернуться в меню
Жми 👉 /start";
}
else 
{	
$text = "📬 Ваши заказы:\n";
$query = DB::$the->query("SELECT id_key,id_subcat,code,time FROM `sel_orders` where `chat` = '{$chat}' ");
while($key = $query->fetch()) {
$subcat = DB::$the->query("SELECT name FROM `sel_subcategory` where `id` = '{$key['id_subcat']}' ");
$subcat = $subcat->fetch(PDO::FETCH_ASSOC);
$subcats = DB::$the->query("SELECT name FROM `sel_subcat` where `id` = '{$key['id_subcat']}' ");
$subcats = $subcats->fetch(PDO::FETCH_ASSOC);
$keys = DB::$the->query("SELECT code,id_key,time FROM `sel_orders` where  `chat` = '{$key[$chat]}' ");
$keys = $keys->fetch(PDO::FETCH_ASSOC);

$date = $eng->showtime($key['time'], 1);

$text .= "\n ➖➖➖➖➖➖➖➖➖➖
🎁 Товар: {$subcat[name]}
⚖️ фасовка: {$subcats[name]}
⏱ Время покупки: {$date} 
⛳️Адрес: {$key[code]}

 ";

     } 
$text .= "➖➖➖➖➖➖➖➖➖➖

Благодарим за покупку!
Ⓜ️ Вернуться в меню
Жми 👉 /start";
    }


// Отправляем все это пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $text,
	)); 	
exit;
?>
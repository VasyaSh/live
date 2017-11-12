<?php
/** 
 * UnoScriptBook (USBook)
 * 
 * @Author Vasilii B. Shpilchin (ICQ: 379512)
 * @copyright [vs]
 * @version 1.0
 * 
 * Гостевая книга, USBook хранит данные в текстовом файле.
 * Распространяется по лицензии BSD.
 * 
 * Формат БД:
 * {serialize_array}[0]time[0]nick[0]text[0]time[0]nick[0]text[0]...
 */
 
#Секция конфигурации.
//Заголовок <title>
$CONFIG['title'] = 'USBook :: [vs] code';
//Имя файла Базы Данных
$CONFIG['dbase'] = 'usbook.db';
//Постов на страницу
$CONFIG['msgOnPage'] = 10;
//Задержка антифлуда в секундах
$CONFIG['antiFlood'] = 5;
//Вырезать теги?
$CONFIG['stripTags'] = 'Off';
//Заменять спецсимволы html-кодами?
$CONFIG['htmlspecialchars'] = 'On';
//Максимальная длина имени
$CONFIG['maxName'] = 32;
//Максимальная длина сообщения
$CONFIG['maxPost'] = 512;
//Имя админа 
$CONFIG['adminName'] = 'admin';
//Пароль админа
$CONFIG['adminPass'] = 'admpass';
 
#База данных
$DB = null;
 
#Проверка на наличие ошибок.
if (!is_writeable($CONFIG['dbase'])) {
    die('Файл '.$CONFIG['dbase'].' недоступен.');
}
 
#Обработчик Базы Данных
function handler ()
{
    global $CONFIG;
    global $DB;
    $DB = file_get_contents($CONFIG['dbase']);
    
    //Пустой файл бывает при первом запуске и после очистки.
    if (empty($DB)) {
        //Система антифлуда основана на запоминани времени отправки сообщения с конкретного IP.
        $DB = serialize(array());
        file_put_contents($CONFIG['dbase'], $DB.chr(0));
        return handler();
    }
    $DB = explode(chr(0), $DB);
    
    //Собственно, антифлуд
    $DB[0] = unserialize($DB[0]);
    if (key_exists(ip2long($_SERVER['REMOTE_ADDR']), $DB[0]) && time() - $DB[0][ip2long($_SERVER['REMOTE_ADDR'])] < $CONFIG['antiFlood']) { 
        define('CAN_POST', false);    
    } else {
        define('CAN_POST', true);
        unset($DB[0][ip2long($_SERVER['REMOTE_ADDR'])]);
    }
    $DB[0] = serialize($DB[0]);
}
 
#Оптимизатор Базы Данных
function optimizer ()
{
    global $CONFIG;
    global $DB;
    
    //Суть оптимизации сводится к очистке списка AntiFlood
    $DB[0] = serialize(array());
    
    $DB = implode(chr(0), $DB);
    file_put_contents($CONFIG['dbase'], $DB);
}
 
#Очистка Базы Данных
function clear ()
{
    global $CONFIG;
    
    file_put_contents($CONFIG['dbase'], null);
}
 
#Получение сообщений
function getMsgs ()
{
    global $CONFIG;
    global $DB;
    $msgsArr = array();
    $limit = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] * $CONFIG['msgOnPage'] : $CONFIG['msgOnPage'];
    
    for ($i = ($limit - $CONFIG['msgOnPage'])*3 + 1; $i < $limit*3; $i+=3) {
        if (key_exists($i+2, $DB)) {
            $msgsArr[$DB[$i]] = array($DB[$i+1], $DB[$i+2]);
        } else {
            break;
        }
    }
    
    return $msgsArr;
}
 
#Добавление сообщения
function addMsg ($name, $text)
{
    global $CONFIG;
    global $DB;
    
    if (substr_count($name, chr(0))) {
        $name = str_replace(chr(0), null);
    }
    if (substr_count($text, chr(0))) {
        $text = str_replace(chr(0), null);
    }
    
    if (!strpos($DB[0], ip2long($_SERVER['REMOTE_ADDR']))) {
        $DB[0] = unserialize($DB[0]);
        $DB[0][ip2long($_SERVER['REMOTE_ADDR'])] = time();
        $DB[0] = serialize($DB[0]);
    }
    $padding = strlen($DB[0]);
    $DB = implode(chr(0), $DB);
    
    //Новое сообщение добавляется вначале
    $DB = substr_replace($DB, chr(0).implode(chr(0), array(time(), $name, $text)).chr(0), $padding, 1);
    
    file_put_contents($CONFIG['dbase'], $DB);
}
 
#Удаление сообщения
function delMsg ($n)
{
    global $CONFIG;
    global $DB;
    
    if (!key_exists($n, $DB)) {
        return true;
    }
    
    //Чтобы не удалить антиспам-массив
    if ($n == 0) {
        return true;
    }
    
    //Допустим только номер элемента-даты
    if (!is_numeric($DB[$n])) {
        return true;
    }
    
    unset($DB[$n]);
    unset($DB[++$n]);
    unset($DB[++$n]);
    
    $DB = implode(chr(0), $DB);
    file_put_contents($CONFIG['dbase'], $DB);
}
 
#Список страниц
function paginator ()
{
    global $CONFIG;
    global $DB;
    $current = isset($_GET['page']) ? $_GET['page'] : 1;
    
    $pages = ceil(((count($DB) - 1) / 3) / $CONFIG['msgOnPage']);
    $buffer = null;
    for ($i=1; $i <= $pages; $i++) {
        if ($i == $current) {
            $buffer .= '<b>'.$current.'</b> ';
        } else {
            $buffer .= '[url="'.$_SERVER['PHP_SELF'].'?page='.$i.'"]'.$i.'[/url] ';
        }
    }
    return $buffer;
}
 
#Авторизация админа
function auth ()
{
    global $CONFIG;
    
    if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $CONFIG['adminName'] || $_SERVER['PHP_AUTH_PW'] != $CONFIG['adminPass'])  {
        header("WWW-authenticate: basic realm='Welcome to USBook!'");
        header("HTTP/1.0 401 Auth Required");
        die('Login invalid!');
    } else {
        define('ADMIN', true);
    }
}
 
#Обновление страницы
function refresh () {
    global $addit;
    die(header('Location: '.$_SERVER['PHP_SELF'].'?'.$addit));
}
 
#Алгоритм работы
handler(); //Amen!
 
//Авторизация
if (isset($_GET['admin'])) {
    auth();
    $addit = 'admin';
} else {
    $addit = null;
}
 
//Добавление сообщения
if (isset($_POST['add'])) {
    if (CAN_POST && isset($_POST['name']) && isset($_POST['post']) && !empty($_POST['name']) && !empty($_POST['post'])) {
    
        //Обработка данных
        if (get_magic_quotes_gpc()) {
            $_POST['name'] = stripslashes($_POST['name']);
            $_POST['post'] = stripslashes($_POST['post']);
        }
        if (strlen($_POST['name']) > $CONFIG['maxName']) {
            $_POST['name'] = substr($_POST['Name'], 0, $CONFIG['maxName']);
        }
        if (strlen($_POST['post']) > $CONFIG['maxPost']) {
            $_POST['post'] = substr($_POST['Post'], 0, $CONFIG['maxPost']);
        }
        if (strtolower($CONFIG['stripTags']) == 'on') {
            $_POST['name'] = strip_tags($_POST['name']);
            $_POST['post'] = strip_tags($_POST['post']);
        }
        if (strtolower($CONFIG['htmlspecialchars']) == 'on') {
            $_POST['name'] = htmlspecialchars($_POST['name']);
            $_POST['post'] = htmlspecialchars($_POST['post']);
        }
        
        //Запись
        addMsg($_POST['name'], $_POST['post']);
        refresh();
    }
}
 
//Действия администратора
if (defined('ADMIN')) {
    //Удаление сообщения
    if (isset($_GET['del'])) {
        delMsg($_GET['del']);
        refresh();
    }
    //Оптимизация БД
    elseif (isset($_GET['optimize'])) {
        optimizer();
        refresh();
    }
    //Очистка БД
    elseif (isset($_GET['clear'])) {
        clear();
        refresh();
    }
    //Выход
    if (isset($_GET['logout'])) {
        $addit = null;
        unset($_SERVER['PHP_AUTH_USER']);
        unset($_SERVER['PHP_AUTH_PW']);
        refresh();
    }
}

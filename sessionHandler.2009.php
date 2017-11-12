<?php
/**
 * @name memses
 * @copyright Vasilii B. Shpilchin (2009)
 * @package GEEG
 * 
 * Memcached-сессии.
 * Элементы сессии хранятся в массиве, записаным одним элементом в memcache.
 * Можно создавать сессии с произвольными именами, но кука автоматически 
 * ставится только для одной сессии!
 */
class memses
{
        // Ресурс соединения с memcached Server
        private static $resource = false;
        // Идентификаторы сессий
        private static $keys = array(false);
        
        /**
         * Инициализация cистемной сессии
         */
        private static function init()
        {
                self::$resource = new Memcache;
                $success = self::$resource -> connect(CFG::$mem_host, CFG::$mem_port);
                if ($success === false)
                {
                        throw new Exception('memses->init: Невозможно соедениться с memcached Server');
                }
                if (key_exists(CFG::$mem_cookie, $_COOKIE))
                {
                        $exist = self::check($_COOKIE[CFG::$mem_cookie]);
                        if ($exist === false)
                        {
                                unset($_COOKIE[CFG::$mem_cookie]);
                                self::restart();
                        }
                        else 
                        {
                                self::$keys[0] = $_COOKIE[CFG::$mem_cookie];
                        }
                }
                else 
                {
                        self::create();
                        DB::insert('sessions', array('sid','logged'), array(self::$keys[0], 0));
                        setcookie(CFG::$mem_cookie, self::$keys[0]);
                }   
        }
        
        /**
         * Старт систменой сессии до запросов
         */
        public static function start()
        {
                self::init();
        }
        
        /**
         * Генератор идентификатора сессии
         * 
         * @return string
         */
        private static function keygen()
        {
                $letters = array_merge(range('a','z'),range('A','Z'));
                $letters = array_merge(range(0,9), $letters);
                $c = 0;
                while (true)
                {
                        $key = null;
                        for ($i=0; $i<16; $i++)
                        {
                                $key .= $letters[rand(0,count($letters)-1)];
                        }
                        if (self::check($key) === false)
                        {
                                break;
                        }
                }
                return $key;
        }
        
        /**
         * Проверка существования и правильности сессии
         * 
         * @param string $sid
         * @return boolean
         */
        public static function check($sid = false)
        {
                if (!$sid)
                {
                        $sid = self::$keys[0];
                }
                $right = self::get_full($sid);
                if (is_array($right))
                {
                        return true;
                }
                else 
                {
                        return false;
                }
        }
        
        /**
         * Создание сессии
         * 
         * @param string $sid - если не задан, генерируется автоматически
         * @return int $key - идентификатор созданой сессии
         */
        public static function create($sid = false)
        {
                if ($sid === false)
                {
                        $key = self::keygen();
                }
                else 
                {
                        $key = $sid;
                }
                if (!self::check($key))
                {
                        $session = array();
                        $session['__create'] = time();
                        $session['__modifed'] = time();
                        $session['__logged'] = 0;
                        $session['__ip'] = $_SERVER['REMOTE_ADDR'];
                        self::$resource -> set($key, $session);
                        $num = array_push(self::$keys, $key);;
                        if ($sid === false && !isset($keys[0]))
                        {
                                self::$keys[0] = $key;
                                unset(self::$keys[$num-1]);
                        }
                        return $key;
                }
        }
        
        /**
         * Установка элемента в сессиию
         * 
         * @param string $key - ключ
         * @param void $value - значение (длина ограничена CFG::$mem_max_lenght)
         * @param string $sid - идентификатор сессии (если не задан - используется системная)
         */
        public static function set($key, $value, $sid = false)
        {
                if (is_object($value) or is_array($value))
                {
                        $value = serialize($value);
                }
                if (strlen($value) > CFG::$mem_max_lenght)
                {
                        throw new Exception('memses->set: слишком длиное value: '.$value);
                }
                if (strlen($key) > 255)
                {
                        throw new Exception('memses->set: слишком длиный key: '.$key);
                }
                if ($key === '__created' or $key === '__modifed')
                {
                        throw new Exception('memses->set: нельзя использовать ключи __created и __modifed');
                }
                if ($sid === false)
                {
                        if (self::$keys[0] === false)
                        {
                                self::init();
                        }
                        $session = self::get_full(self::$keys[0]);
                        $session[$key] = $value;
                        self::$resource -> set(self::$keys[0], $session);
                        self::modifed(self::$keys[0]);
                }
                else 
                {
                        if (!self::check($sid))
                        {
                                throw new Exception('memses->set: попытка установить значение в несуществующей сессии');
                        }
                        $session = self::get_full($sid);
                        $session[$key] = $value;
                        self::$resource -> set($sid, $session);
                        self::modifed($sid);
                }
        }
        
        /**
         * Получение элемента из сессии
         * 
         * @param $key
         * @param string $sid - идентификатор сессии (если не задан - используется системная)
         */
        public static function get($key, $sid = false)
        {
                if ($key == null)
                {
                        return false;
                }
                if ($sid === false)
                {
                        if (self::$keys[0] === false)
                        {
                                self::init();
                        }
                        $session = self::get_full(self::$keys[0]);
                        if (key_exists($key, $session))
                        {
                                return $session[$key];
                        }
                        else 
                        {
                                return false;
                        }
                }
                else 
                {
                        if (self::check($sid))
                        {
                                $session = self::get_full($sid);
                                if ((is_array($session) or is_object($session)) && key_exists($key, $session))
                                {
                                        return $session[$key];
                                }
                                else 
                                {
                                        return false;
                                }
                        }
                        else 
                        {
                                return false;
                        }
                }
        }
        
        /**
         * Удаление элемента из сессии
         * 
         * @param string $key
         * @param string $sid - идентификатор сессии (если не задан - используется системная)
         */
        public static function del($key, $sid = false)
        {
                if ($key === '__created' or $key === '__modifed')
                {
                        throw new Exception('memses->del: нельзя использовать ключи __created и __modifed');
                }
                if ($sid === false)
                {
                        if (self::$keys[0] === false)
                        {
                                self::init();
                        }
                        $session = self::get_full(self::$keys[0]);
                        if (key_exists($key, $session))
                        {
                                unset($session[$key]);
                        }
                        self::$resource -> set(self::$keys[0], $session);
                }
                else 
                {
                        $session = self::get_full(self::$keys[0]);
                        if (key_exists($key, $session))
                        {
                                unset($session[$key]);
                        }
                        self::$resource -> set($sid, $session);
                }
        }
        
        /**
         * Возвращает массив с номерами и идентификаторами пользовательских сессий за сеанс
         *
         */
        public static function get_user_sessions()
        {
                $user_sessions = array();
                foreach (self::$keys as $k => $v)
                {
                        if ($k !== 0)
                        {
                                $user_sessions[$k-1] = $v;
                        }
                }
                return $user_sessions;
        }
        
        /**
         * Перезапуск сессии
         * 
         * @param string $sid - идентификатор сессии (если не задан - используется системная)
         */
        public static function restart($sid = false)
        {
                if ($sid === false)
                {
                        if (self::$keys[0] !== false)
                        {
                                $sid = self::$keys[0];
                                self::destroy($sid);
                                self::create($sid);
                        }
                        else 
                        {
                                self::init();
                        }
                }
                else 
                {
                        self::destroy($sid);
                        self::create($sid);
                }
        }
        
        /**
         * Получение всей сессии по идентификатору (если не задан - используется системная)
         * 
         * @param string $sid
         */
        public static function get_full($sid = false)
        {
                if ($sid === false)
                {
                        if (self::$keys[0] === false)
                        {
                                self::init();
                        }
                        $session = self::$resource -> get(self::$keys[0]);
                        return $session;
                }
                else 
                {
                        if ($sid === null)
                        {
                                return false;
                        }
                        $session = self::$resource -> get($sid);
                        if (is_array($session))
                        {
                                return $session;
                        }
                        else 
                        {
                                return $session;
                        }
                }
        }
        
        /**
         * Уничтожение сессии
         * 
         * @param string $sid - идентификатор сессии
         */
        public static function destroy($sid)
        {
                if (self::check($sid))
                {
                        $num = array_search($sid, self::$keys);
                        if (is_int($num))
                        {
                                self::$resource -> del($sid);
                                unset(self::$keys[$num]);
                        }
                }
        }
        
        /**
         * Записывает время изменения сессии
         *
         * @param string $sid - идентификатор сессии (если не задан - используется системная)
         */
        private static function modifed($sid = false)
        {
                if ($sid === false)
                {
                        if (self::$keys[0] === false)
                        {
                                return false;
                        }
                        else 
                        {
                                $sid = self::$keys[0];
                        }
                }
                $session = self::get_full($sid);
                if ($session)
                {
                        $session['__modifed'] = time();
                        self::$resource -> set($sid, $session);
                }
                else 
                {
                        return false;
                }
        }
        
        /**
         * Обновляет информацию о сессии в БД.
         * Доступно только для системной сесии.
         */
        public static function update()
        {
                if (!self::check(self::$keys[0]))
                {
                        self::init();
                }
                $where = array();
                $where[0] = array('`sid`='=>self::$keys[0]);
                DB::update('sessions', array('logged'), array(self::get('__logged')), $where);
        }
        
        /**
         * Сохраняет sid пользовательской сессии в БД.
         * 
         * @param string $sid - индентификатор сессии
         */
        public static function save_user_session($sid)
        {
                if (!self::check($sid))
                {
                        throw new Exception('memses->save_user_session: несуществующая сессия не сохранена.');
                }
                else 
                {
                        $table_exist = DB::mem_check(CFG::$user_sessions);
                        if ($table_exist === false)
                        {
                                $cols = array('sid');
                                SYS::init_memory_db(CFG::$user_sessions, $cols);
                        }
                        $fields = array('sid');
                        $values = array($sid);
                        DB::mem_insert(CFG::$user_sessions, $fields, $values);
                }
        }
}

<?php
class SimpleCache {
    private static $cache = [];
    private static $ttl = [];
    
    public static function get($key) {
        if (isset(self::$cache[$key]) && (self::$ttl[$key] ?? 0) > time()) {
            return self::$cache[$key];
        }
        return null;
    }
    
    public static function set($key, $value, $seconds = 300) {
        self::$cache[$key] = $value;
        self::$ttl[$key] = time() + $seconds;
    }
    
    public static function delete($key) {
        unset(self::$cache[$key], self::$ttl[$key]);
    }
    
    public static function clear() {
        self::$cache = [];
        self::$ttl = [];
    }
}
?>

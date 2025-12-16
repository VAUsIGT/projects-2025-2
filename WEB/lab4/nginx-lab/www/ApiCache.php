<?php
class ApiCache {
    private static $cacheFile = 'api_cache.json';
    private static $cacheTtl = 300; // 5 минут
    
    public static function get($url, $forceRefresh = false) {
        $cacheKey = md5($url);
        
        // Если не форсируем обновление и есть актуальный кеш - возвращаем его
        if (!$forceRefresh && self::isCacheValid($cacheKey)) {
            $cachedData = self::readCache();
            if (isset($cachedData[$cacheKey])) {
                return $cachedData[$cacheKey];
            }
        }
        
        // Если кеша нет или он устарел - делаем запрос к API
        require_once 'ApiClient.php';
        $api = new ApiClient();
        $apiData = $api->request($url);
        
        // Сохраняем в кеш с меткой времени
        $cachedData = self::readCache();
        $cachedData[$cacheKey] = [
            'data' => $apiData,
            'timestamp' => time(),
            'url' => $url
        ];
        
        self::writeCache($cachedData);
        
        return $apiData;
    }
    
    public static function isCacheValid($cacheKey) {
        $cachedData = self::readCache();
        
        if (!isset($cachedData[$cacheKey])) {
            return false;
        }
        
        $cacheEntry = $cachedData[$cacheKey];
        $cacheAge = time() - $cacheEntry['timestamp'];
        
        return $cacheAge < self::$cacheTtl;
    }
    
    public static function getCacheAge($cacheKey) {
        $cachedData = self::readCache();
        
        if (!isset($cachedData[$cacheKey])) {
            return null;
        }
        
        return time() - $cachedData[$cacheKey]['timestamp'];
    }
    
    public static function getCacheInfo($cacheKey) {
        $cachedData = self::readCache();
        
        if (!isset($cachedData[$cacheKey])) {
            return null;
        }
        
        $cacheEntry = $cachedData[$cacheKey];
        $cacheAge = time() - $cacheEntry['timestamp'];
        $remainingTime = max(0, self::$cacheTtl - $cacheAge);
        
        return [
            'timestamp' => $cacheEntry['timestamp'],
            'age' => $cacheAge,
            'remaining' => $remainingTime,
            'is_valid' => $cacheAge < self::$cacheTtl
        ];
    }
    
    public static function clearCache() {
        if (file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }
    }
    
    public static function getAllCache() {
        return self::readCache();
    }
    
    private static function readCache() {
        if (!file_exists(self::$cacheFile)) {
            return [];
        }
        
        $content = file_get_contents(self::$cacheFile);
        $data = json_decode($content, true);
        
        return is_array($data) ? $data : [];
    }
    
    private static function writeCache($data) {
        file_put_contents(self::$cacheFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
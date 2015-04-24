<?php

/** 
 * 各地图API坐标系统比较与转换; 
 * WGS84坐标系：即地球坐标系，国际上通用的坐标系。设备一般包含GPS芯片或者北斗芯片获取的经纬度为WGS84地理坐标系, 
 * 谷歌地图采用的是WGS84地理坐标系（中国范围除外）; 
 * GCJ02坐标系：即火星坐标系，是由中国国家测绘局制订的地理信息系统的坐标系统。由WGS84坐标系经加密后的坐标系。 
 * 谷歌中国地图和搜搜中国地图采用的是GCJ02地理坐标系; BD09坐标系：即百度坐标系，GCJ02坐标系经加密后的坐标系; 
 * 搜狗坐标系、图吧坐标系等，估计也是在GCJ02基础上加密而成的。 chenhua 
 */  
abstract  class GpsPositionTransform {  
      
    public static $BAIDU_LBS_TYPE = "bd09ll";  
      
    public static $pi = 3.1415926535897932384626;  
    public static $a = 6378245.0;  
    public static $ee = 0.00669342162296594323;  
  
    /** 
     * 84 to 火星坐标系 (GCJ-02) World Geodetic System ==> Mars Geodetic System 
     *  
     * @param $lat 
     * @param $lon 
     * @return 
     */  
    public static function gps84_To_Gcj02( $lat,  $lon) {  
        if (self::outOfChina($lat,$lon)) {  
            return null;  
        }  
         $dLat = self::transformLat($lon - 105.0, $lat - 35.0);  
         $dLon = self::transformLon($lon - 105.0,$lat - 35.0);  
         $radLat = $lat / 180.0 * self::$pi;  
         $magic = sin($radLat);  
        $magic = 1 - self::$ee * $magic * $magic;  
         $sqrtMagic = sqrt($magic);  
        $dLat = ($dLat * 180.0) / ((self::$a * (1 - self::$ee)) / ($magic * $sqrtMagic) * self::$pi);  
        $dLon = ($dLon * 180.0) / (self::$a / $sqrtMagic * cos($radLat) * self::$pi);  
         $mgLat = $lat + $dLat;  
         $mgLon = $lon + $dLon;  
        return array("lat"=>$mgLat, "lon"=>$mgLon);  
    }  
  
    /** 
     * * 火星坐标系 (GCJ-02) to 84 * * @param lon * @param lat * @return 
     * */  
    public static function gcj_To_Gps84( $lat,  $lon) {  
         $gps_arr = self::transform($lat, $lon);  
         $lontitude = $lon * 2 - $gps_arr["lon"];  
         $latitude = $lat * 2 - $gps_arr["lat"];  
        return array("lat"=>$latitude, "lon"=>$lontitude);  
    }  
  
    /** 
     * 火星坐标系 (GCJ-02) 与百度坐标系 (BD-09) 的转换算法 将 GCJ-02 坐标转换成 BD-09 坐标 
     *  
     * @param gg_lat 
     * @param gg_lon 
     */  
    public static function gcj02_To_Bd09( $gg_lat,  $gg_lon) {  
         $x = $gg_lon;
         $y = $gg_lat;  
         $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * self::$pi);  
         $theta = atan2($y, $x) + 0.000003 * cos($x * self::$pi);  
         $bd_lon = $z * cos($theta) + 0.0065;  
         $bd_lat = $z * sin($theta) + 0.006;  
        return  array("lat"=>$bd_lat,"lon" => $bd_lon);  
    }  
  
    /** 
     * * 火星坐标系 (GCJ-02) 与百度坐标系 (BD-09) 的转换算法 * * 将 BD-09 坐标转换成GCJ-02 坐标 * * @param 
     * bd_lat * @param bd_lon * @return 
     */  
    public static function bd09_To_Gcj02( $bd_lat,  $bd_lon) {  
         $x = $bd_lon - 0.0065;
          $y = $bd_lat - 0.006;  
         $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * self::$pi);  
         $theta = atan2($y, $x) - 0.000003 * cos($x * self::$pi);  
         $gg_lon = $z * cos($theta);  
         $gg_lat = $z * sin($theta);  
        return array("lat"=>$gg_lat,"lon"=> $gg_lon);  
    }  
  
    /** 
     * (BD-09)-->84 
     * @param bd_lat 
     * @param bd_lon 
     * @return 
     */  
    public static function bd09_To_Gps84( $bd_lat,  $bd_lon) {  
  
        $gcj02_arr = self::bd09_To_Gcj02($bd_lat, $bd_lon);  
        $map84_arr = self::gcj_To_Gps84($gcj02_arr["lat"], $gcj02_arr["lon"]);  
        return $map84_arr;  
    }  
  
    public static function outOfChina( $lat,  $lon) {  
        if (($lon < 72.004 || $lon > 137.8347) &&  ($lat < 0.8293 || $lat > 55.8271) )
            return true;  
        return false;  
    }  
  
    public static function transform( $lat,  $lon) {  
        if (self::outOfChina($lat, $lon)) {  
            return array("lat"=>$lat,"lon"=>$lon);  
        }  
         $dLat = self::transformLat($lon - 105.0, $lat - 35.0);  
         $dLon = self::transformLon($lon - 105.0, $lat - 35.0);  
         $radLat = $lat / 180.0 * self::$pi;  
         $magic = sin($radLat);  
        $magic = 1 - self::$ee * $magic * $magic;  
         $sqrtMagic = sqrt($magic);  
        $dLat = ($dLat * 180.0) / ((self::$a * (1 - self::$ee)) / ($magic * $sqrtMagic) * self::$pi);  
        $dLon = ($dLon * 180.0) / (self::$a / $sqrtMagic * cos($radLat) * self::$pi);  
         $mgLat = $lat + $dLat;  
         $mgLon = $lon + $dLon;  
        return array("lat"=>$mgLat, "lon"=>$mgLon);  
    }  
    
    public static function transformLat($x, $y){
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * self::$pi) + 20.0 * sin(2.0 * $x * self::$pi)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * self::$pi) + 40.0 * sin($y / 3.0 * self::$pi)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * self::$pi) + 320 * sin($y * self::$pi / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    
    public static function transformLon($x, $y){
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * self::$pi) + 20.0 * sin(2.0 * $x * self::$pi)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * self::$pi) + 40.0 * sin($x / 3.0 * self::$pi)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * self::$pi) + 300.0 * sin($x / 30.0 * self::$pi)) * 2.0 / 3.0;
        return $ret;
    }
}  
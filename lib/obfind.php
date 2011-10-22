<?php

if(!function_exists('getallheaders')) {
    function getallheaders() {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

class OBFind {

    public static $dbhost = 'localhost';
    public static $dbname = 'mydb';
    public static $dbuser = 'myuser';
    public static $dbpass = 'mypass';

    const TWILIO_SMS_NUM = '+15555551234';
    const TWILIO_SID = 'ABCDEFG0123456789';
    const TWILIO_AUTH_TOKEN = '012345678901234567890';

    const EDGES_TABLE = 'edges_ma_boston';

    public static $const_prefix = 'OBF_';

    public static $libdir = null;

    public static function init() {
        static $_initd = false;
        if($_initd) throw new Exception('Already initialized.');
        spl_autoload_register(array('OBFind', 'autoload'));
        self::$libdir = defined(self::$const_prefix.'LIBDIR') ? 
            constant(self::$const_prefix.'LIBDIR') : dirname(__FILE__);
        $dbconfig = array('dbhost','dbname','dbuser','dbpass');
        foreach($dbconfig as $ci => $config) {
            if(defined('OBF_'.strtoupper($config)))
                self::$$config = constant('OBF_'.strtoupper($config));
        }
        $dsn = sprintf('pgsql:host=%s;dbname=%s;user=%s;password=%s', 
            self::$dbhost, self::$dbname, self::$dbuser, self::$dbpass
        );
        Rudb::connect($dsn);
        $_initd = true;
    }

    public static function autoload($cls) {
        $p = join('/', explode('_', strtolower($cls)));
        $p = self::$libdir.$p.'.php';
        $loaded = false;
        if(file_exists($p)) {
            $loaded = include($p);
        }
        return $loaded;
    }

    public static function parse_query($str) {
        $add = strtolower(trim($str));
        $tflag = false;
        $parsed = false;
        if(preg_match('/^(\S+)\s+(at|near)\s+(.+)$/', $add, $m)) {
            $parsed = (object)array(
                'tag' => strtolower($m[1]),
                'tflag' => strtolower($m[2]),
                'address' => strtolower($m[3])
            );
        } else {
            if(substr($add, 0, 1) == '?') {
                $tflag = '?';
                $add = substr($add, 1);
                if(is_numeric($add)) {
                    $add = (int)$add;
                } else {
                    $add = 2;
                }
            } elseif(preg_match('/^at\s+/', $add)) {
                $tflag = 'at';
                $add = preg_replace('/^at\s+/', '', $add);
            } else {
                $tflag = 'near';
                $add = preg_replace('/^near\s+/', '', $add);
            }
            $parsed = (object)array(
                'tag' => false,
                'tflag' => strtolower($tflag),
                'address' => strtolower($add)
            );
        }
        return $parsed;
    }

    public static function parse_address($add) {
        preg_match('/^(\d+)\s+([\w\s]+)\s+(dr|rd|ave|st|pkwy|hwy)$/', trim($add), $m);
        $parsed = false;
        if($m) {
            $parsed = (object)array(
                'number' => (int)$m[1],
                'street' => $m[2].' '.$m[3]
            );
        }
        return $parsed;
    }

    public static function find_address($add) {
        $padd = self::parse_address($add);
        if(!$padd) return false;
        $c = Rudb::connection();
        $sql = 'select ST_AsText(ed.wkb_geometry) as edge_geom,'.
            ' ST_AsText(ST_Centroid(ed.wkb_geometry)) as approx_loc'.
            ' from "'.self::EDGES_TABLE.'" ed where fullname ilike '.$c->quote($padd->street.'%').
            ' and ((ed.lfromadd::int <= ? and ed.ltoadd::int >= ?) or'.
            ' (ed.rfromadd::int <= ? and ed.rtoadd::int >= ?))';
        $r = Rudb::get_one($sql, $padd->number, $padd->number, $padd->number, $padd->number);
        return $r;
    }

    public static function find($add, $num=1) {
        $r = self::find_address($add);
        if($r) $r = (object)$r;
        else return false;
        $o = (int)$num - 1;
        $c = Rudb::connection();
        $sql = 'select t.address as address, ST_AsText(t.location) as loc,'.
            ' ST_Distance('.
            '   ST_Transform(t.location,3857),'.
            '   ST_Transform(ST_GeometryFromText('.$c->quote($r->approx_loc).',4326),3857)) as dist'.
            ' from track t'.
            '   where t.tflag = \'at\''.
            ' order by dist asc limit 1';
        if($o) $sql .= ' offset '.$o;
        $r = Rudb::get_one($sql);
        if($r) $r = $r['address'];
        return $r;
    }

    public static function find_tagged($tag, $add, $num=1) {
        $r = self::find_address($add);
        if($r) $r = (object)$r;
        else return false;
        $o = (int)$num - 1;
        $c = Rudb::connection();
        $sql = 'select t.address as address, ST_AsText(t.location) as loc,'.
            ' ST_Distance('.
            '   ST_Transform(t.location,3857),'.
            '   ST_Transform(ST_GeometryFromText('.$c->quote($r->approx_loc).',4326),3857)) as dist'.
            ' from track t'.
            '   where t.tag = ? and t.tflag = \'at\''.
            ' order by dist asc limit 1';
        if($o) $sql .= ' offset '.$o;
        $r = Rudb::get_one($sql,$tag);
        if($r) $r = $r['address'];
        return $r;
    }
}

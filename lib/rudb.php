<?php
class Rudb {

    protected static $_initd = false;
    protected static $_cnxns = null;
    protected static $_queries = null;
    protected static $_using = 'default';

    public function init() {
        self::$_cnxns = array();
        self::$_queries = array();
        self::$_initd = true;
    }

    public function connection($nm='default') {
        return isset(self::$_cnxns[$nm]) ? self::$_cnxns[$nm] : false;
    }

    public function connect($dsn, $user=null, $passwd=null, $nm='default') {
        if(!self::$_initd) self::init();
        if(self::connection($nm)) self::connection($nm)->close();
        $c = new PDO($dsn, $user, $passwd);
        self::$_cnxns[$nm] = $c;
        self::$_queries[$nm] = array();
        return $c;
    }

    public function usedb($nm) {
        self::$_using = $nm;
        return true;
    }

    public function last_insert_id($ser=null,$nm='default') {
        $c = self::connection($nm);
        return $c->lastInsertId($ser);
    }

    public function query($q) {
        $params = func_get_args();
        array_shift($params);
        $c = self::connection(self::$_using);
        $ps = isset(self::$_queries[self::$_using][$q]) ? 
            self::$_queries[self::$_using][$q] : $c->prepare($q);
        self::$_queries[self::$_using][$q] = $ps;
        $c->beginTransaction();
        $ok = $ps->execute($params);
        if(preg_match('/^(insert|update|delete)/', $q)) {
            $r = $ps->rowCount();
        } elseif(!$ok && $c->errorCode()) {
            $c->rollBack();
            $err = $c->errorInfo();
            $emsg = $err[2];
            throw new Exception('Query failed: '.$emsg);
        } else {
            $r = $ps;
        }
        $c->commit();
        return $r;
    }

    public function get_one() {
        $args = func_get_args();
        $r = call_user_func_array(array('self', 'query'), $args);
        if($r) $r = $r->fetch();
        return $r;
    }

    public function get_all() {
        $args = func_get_args();
        $r = call_user_func_array(array('self', 'query'), $args);
        if($r) $r = $r->fetchAll();
        return $r;

    }
}

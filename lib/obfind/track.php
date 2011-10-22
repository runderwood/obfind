<?php
class OBFind_Track {

    const SALT = '>>>>howdowefixthedeficit----------.';

    public static function hashnum($num) {
        $num .= self::SALT;
        return md5($num);
    }

    public static function find($num) {
        $sql = 'select id,sid,tflag,address,tag from track where sid=?';
        return Rudb::get_one($sql,self::hashnum($num));
    }

    public static function create($num,$tflag,$address,$location=null,$tag=null) {
        $sql = 'insert into track (sid,tflag,address,location,tag) values (?,?,?,ST_GeometryFromText(?, 4326),?)';
        $newid = false;
        if(Rudb::query($sql,self::hashnum($num),$tflag,$address,$location,$tag)) {
            $newid = Rudb::last_insert_id('track_id_seq');
        }
        return $newid;
    }

    public static function update($num,$tflag,$address,$location=null,$tag=null) {
        $sql = 'update track set tflag=?, address=?, location=ST_GeometryFromText(?, 4326), tag=? where sid=?';
        return Rudb::query($sql,$tflag,$address,$location,$tag,self::hashnum($num));
    }

    public static function delete($num) {
        $sql = 'delete from track where sid=?';
        return Rudb::query($sql,self::hashnum($num));
    }
}

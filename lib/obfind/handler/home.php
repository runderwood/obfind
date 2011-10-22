<?php

class OBFind_Handler_Home {
    public function get() {
        $qadd = isset($_GET['a']) ? $_GET['a'] : '';
        print_r(OBFind::find_address($qadd));
        print_r(OBFind::parse_query('ob at '.$qadd));
        print_r(OBFind::parse_query('Ob At 161 strathmore rd'));
        print_r(OBFind::parse_query('at '.$qadd));
        print_r(OBFind::find('155 strathmore rd'));
        print_r(OBFind::find_tagged('ob','155 strathmore rd'));
    }
}

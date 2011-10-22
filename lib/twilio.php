<?php
class Twilio {

    const REST_API_URL = 'https://api.twilio.com/2010-04-01/';

    public function send_sms($to, $body) {
        $req = array(
            'To' => $to, 'Body' => $body, 'From' => OBFind::TWILIO_SMS_NUM
        );
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: x-www-form-urlencoded'));
        curl_setopt($c, CURLOPT_HEADER, true);
        curl_setopt($c, CURLOPT_URL, self::REST_API_URL.sprintf('Accounts/%s/SMS/Messages', OBFind::TWILIO_SID));
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($req));
        curl_setopt($c, CURLOPT_USERPWD, OBFind::TWILIO_SID.':'.OBFind::TWILIO_AUTH_TOKEN);

        $content = curl_exec($c);
        curl_close($c);
        return $content;
    }
}

<?php
class OBFind_Handler_Sms {
    public function post() {
        $twreq = Twilio_Request::factory();
        if(!$twreq->check_signature(OBFind::TWILIO_AUTH_TOKEN)) {
            header('HTTP/1.1 403 Forbidden');
            throw new Exception('Invalid signature.');
        }
        $parsedq = OBFind::parse_query($twreq->msg_body);
        $qflag = $parsedq->tflag;
        $qadd = $parsedq->address;
        $qtag = $parsedq->tag ? $parsedq->tag : null;
        $qnum = $twreq->from;
        $last = false;
        if($qnum && $last = OBFind_Track::find($qnum)) {
            $last = (object)$last;
        }
        switch($qflag) {
            case 'at':
                $loc = OBFind::find_address($qadd);
                if($loc) {
                    $loc = $loc['approx_loc'];
                    if($last) {
                        OBFind_Track::update($qnum, $qflag, $qadd, $loc, $qtag);
                    } else {
                        OBFind_Track::create($qnum, $qflag, $qadd, $loc, $qtag);
                    }
                    Twilio::send_sms($qnum, "ok: you're at $qadd");
                } else {
                    if(!$result) Twilio::send_sms($qnum, 'sorry. couldn\'t find that address (at).');
                }
                break;
            case 'near':
                $result = $qtag ? OBFind::find_tagged($qtag,$qadd) : OBFind::find($qadd);
                if(!$result) {
                    if(!OBFind::find_address($qadd))
                        Twilio::send_sms($qnum, 'sorry. couldn\'t find that address.');
                    else
                        Twilio::send_sms($qnum, 'found nothing nearby. try another address or start something new.');
                } else Twilio::send_sms($qnum, 'result 1: '.$result);
                if($last) {
                    OBFind_Track::update($qnum, $qflag, $qadd, $qtag);
                } else {
                    OBFind_Track::create($qnum, $qflag, $qadd, $qtag);
                }

                break;
            case '?':
                if($last) {
                    $result = $last->tag ? OBFind::find_tagged($last->tag, $qadd) : OBFind::find($last->address, $qadd);
                    if(!$result) Twilio::send_sms($qnum, 'sorry. couldn\'t find that address.');
                    if(!$result) {
                        if(!OBFind::find_address($qadd))
                            Twilio::send_sms($qnum, 'sorry. couldn\'t find that address.');
                        else
                            Twilio::send_sms($qnum, 'nothing else nearby. try another address or start something new.');
                    } else Twilio::send_sms($qnum, 'result '.$qadd.': '.$result);
                } else {
                    Twilio::send_sms($qnum, 'no query. text "near <your address>".');
                }
                break;
            default:
                TWilio::send_sms('invalid command. text "near <your address>" to get started.');
                break;
        }
    }
}

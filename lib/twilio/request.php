<?php
class Twilio_Request {

    public $headers;
    public $body;
    public $post_params;
    public $uri;
    public $auth;

    public function factory() {
        $uri = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $uri .= $_SERVER['HTTP_HOST'];
        $uri .= $_SERVER['REQUEST_URI'];
        $headers = getallheaders();
        $body = file_get_contents('php://input');
        $params = isset($_POST) ? $_POST : array();
        $req = new Twilio_Request($uri, $headers, $body, $params);
        return $req;
    }

    public function __construct($uri, $headers, $body, $post_params=array()) {
        $this->uri = $uri;
        $this->headers = $headers;
        $this->body = $body;
        $this->post_params = is_array($post_params) ? $post_params : array();
        if(isset($this->post_params['Body'])) $this->msg_body = $this->post_params['Body'];
        else $this->msg_body = false;
        if(isset($this->post_params['From'])) $this->from = $this->post_params['From'];
        else $this->from = false;
    }

    public function check_signature($auth_token) {
        $sh = isset($this->headers['X-Twilio-Signature']) ? $this->headers['X-Twilio-Signature'] : null;
        if(!$sh) return false;
        $pp = (array)$this->post_params;
        $ppks = array_keys($this->post_params);
        sort($ppks);
        $s = $this->uri;
        foreach($ppks as $i => $v) {
            $s .= $v.$pp[$v];
        }
        $s = base64_encode(hash_hmac('sha1', $s, $auth_token, true));
        return $s == $sh;
    }
}

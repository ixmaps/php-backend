<?php
class ResponseCode {
  private $code;
  private $message;

  function __construct($code, $message) {
    $this->code = $code;

    switch ($code) {
      case 100:
        $this->message = "Error: unset status code";
        break;
      case 201:
        $this->message = "Success";
        break;
      case 401:
        $this->message = "Error: malformed JSON submitted, missing key - " . $message;
        break;
      case 402:
        $this->message = "Error: submitted JSON contains unset value for key - " . $message;
        break;
      case 501:
        $this->message = "Error: malformed JSON returned, missing key - " . $message;
        break;
      case 502:
        $this->message = "Error: returned JSON contains unset value for key - " . $message;
        break;
    }
  }

  public function getCode() {
    return $this->code;
  }

  public function getMessage() {
    return $this->message;
  }
}
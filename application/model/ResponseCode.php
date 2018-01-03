<?php
class ResponseCode {
  private $code;
  private $message;

  function __construct($code, $message) {
    $this->code = $code;

    switch ($code) {
      case 201:
        $this->message = "Success";
        break;
      case 401:
        $this->message = "Malformed JSON, missing key - " . $message;
        break;
      case 402:
        $this->message = "Malformed JSON, unset value for key - " . $message;
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
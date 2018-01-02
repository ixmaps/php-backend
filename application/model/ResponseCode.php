<?php
class ResponseCode {
  private $code;
  private $message;

  function __construct($code, $message) {
    $this->code = $code;
    $this->message = $message;
  }

  public function getCode() {
    return $this->code;
  }

  public function getMessage() {
    return $this->message;
  }
}

<?php
/**
 *
 * This class handles response codes used by the validation module
 *
 * @author IXmaps.ca (Colin)
 * @since 2018 Jan 1
 *
 */
class ResponseCode {
  private $code;
  private $message;

  function __construct($code, $message = "") {
    $this->code = $code;

    switch ($code) {
      case 100:
        $this->message = "Error: unset status code";
        break;
      case 201:
        $this->message = "Success";
        break;
      case 400:
        $this->message = "Error: malformed JSON submitted - " . $message;
        break;
      case 401:
        $this->message = "Error: submitted PTR JSON missing key - " . $message;
        break;
      case 402:
        $this->message = "Error: submitted PTR JSON contains unset value for key - " . $message;
        break;
      case 501:
        $this->message = "Error: GEO JSON return missing key - " . $message;
        break;
      case 502:
        $this->message = "Error: GEO JSON contains unset value for key - " . $message;
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
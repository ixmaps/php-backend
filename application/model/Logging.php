<?php
/**
 *
 *
 * @author IXmaps.ca (Colin)
 * @since 2020 Nov
 *
 */
class Logging
{
  private $logFile;
  private $startTime;

  function __construct() {
    global $searchLog;

    $this->startTime = $this->getNow();
    $this->logFile = fopen($searchLog, "a+") or exit("Unable to open file!");
    fwrite($this->logFile, "\n".date('Y/m/d H:i:s', strtotime('-5 hours')));
  }

  public function search($message) {
    fwrite($this->logFile, $message." - ".$this->executionTime()."\n");
  }

  public function __destruct()
    {
      fwrite($this->logFile, "----------------------------------------\n");
      fclose($this->logFile);
    }

  public function executionTime() {
    $endTime = Logging::getNow();
    $totalTime = ($endTime - $this->startTime);
    return number_format($totalTime, 2);
  }

  private function getNow() {
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    return $mtime[1] + $mtime[0];
  }

}
<?php declare(strict_types = 1);
use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../services/GeolocationService.php');
require_once('../services/IXmapsGeolocationService.php');     // only used for tear down, remove when mocking properly
require_once('../services/IPInfoGeolocationService.php');     // only used for tear down, remove when mocking properly
require_once('../repositories/GeolocationRepository.php');
require_once('../repositories/IXmapsGeolocationRepository.php');
require_once('../repositories/IPInfoGeolocationRepository.php');
require_once('../repositories/IP2LocationGeolocationRepository.php');


final class GeolocationTest extends TestCase
{
  private $geoService;
  private $completedFlag = false;

  protected function setUp(): void
  {
    $IXgeoRepo = new IXmapsGeolocationRepository();
    $this->IXgeoService = new IXmapsGeolocationService($IXgeoRepo);

    $IIgeoRepo = new IPInfoGeolocationRepository();
    $this->IIgeoService = new IPInfoGeolocationService($IIgeoRepo);

    $I2geoRepo = new IP2LocationGeolocationRepository();

    $geoRepo = new GeolocationRepository();
    // TODO: pass in services instead
    $this->geoService = new GeolocationService($geoRepo, $IXgeoRepo, $IIgeoRepo, $I2geoRepo);
  }

  public function testCannotBeFoundWithInvalidIpAddress(): void
  {
    $this->expectException(Exception::class);

    $geo = $this->geoService->getByIp('nonsense');
  }

  public function testGetByIpMostRecent(): void
  {
    $this->assertEquals('2020-12-02', $this->geoService->getByIp('192.205.37.77')->getCreatedAt()->format('Y-m-d'));
  }

  public function testGetByIpWithDate(): void
  {
    $this->assertEquals('2016-10-16', $this->geoService->getByIpAndDate('192.205.37.77', '2017-05-05')->getCreatedAt()->format('Y-m-d'));
  }

  public function testGetByIpWithAsnsourceIp2(): void
  {
    $this->assertEquals('IP2Location', $this->geoService->getByIp('139.173.18.10')->getASNsource());
  }

  public function testIpIsStale(): void
  {
    $this->assertTrue($this->geoService->getByIpAndDate('192.205.37.77', '2017-05-05')->getStaleStatus());
  }

  // Left off here. Working inconsistently. Better to just mock everything and be done with it?
  // holding off until we sort out stale and creation better

  // public function testIpWasCreated(): void
  // {
  //   $geo = $this->geoService->getByIp('174.24.170.164');
  //   $this->assertEquals('174.24.170.164', $geo->getIp());

  //   $this->completedFlag = true;
  // }

  // public function testIpWasCreatedInIpInfo(): void
  // {
  //   $this->assertEquals('174.24.170.164', $this->IIgeoservice->getByIp('174.24.170.164')->getIp());

  //   $this->completedFlag = true;
  // }

  public function tearDown(): void
  // there must be a better way to do this than with the completedFlag fence
  // if not, this must not be a common use case, and that means I'm doing something wrong
  {
    if ($this->completedFlag) {
      $this->assertTrue($this->IXgeoService->deleteByIp('174.24.170.164'));
      $this->assertTrue($this->IIgeoService->deleteByIp('174.24.170.164'));

      $this->completedFlag = false;
    }

  }


}

// exists in IXmaps and gl_override is not null
// $a = new Geolocation('4.35.108.230');

// does not exist in IXmaps
//$a = new Geolocation('70.67.160.1');

// // does not exist anywhere
//$a = new Geolocation('127.0.0.1');

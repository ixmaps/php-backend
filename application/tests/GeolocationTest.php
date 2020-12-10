<?php declare(strict_types = 1);
use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../services/GeolocationWrapperService.php');

// used for the tear down, remove when we're mocking properly
require_once('../services/IXmapsGeolocationService.php');
require_once('../services/IPInfoGeolocationService.php');
require_once('../repositories/IXmapsGeolocationRepository.php');
require_once('../repositories/IPInfoGeolocationRepository.php');


final class GeolocationTest extends TestCase
{
  private $geoService;
  private $completedFlag = false;

  protected function setUp(): void
  {
    $wrapper = new GeolocationWrapperService();
    $this->geoService = $wrapper->geolocationService;

    $IXgeoRepo = new IXmapsGeolocationRepository();
    $this->IXgeoService = new IXmapsGeolocationService($IXgeoRepo);

    $IIgeoRepo = new IPInfoGeolocationRepository();
    $this->IIgeoService = new IPInfoGeolocationService($IIgeoRepo);
  }

  public function testIpDoesNotExit(): void
  {
    $this->assertFalse($this->geoService->getByIp('127.0.0.100'));
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

  public function testIpWasCreated(): void
  {
    $geo = $this->geoService->getByIp('174.24.170.164');
    $this->assertEquals('174.24.170.164', $geo->getIp());

    $this->completedFlag = true;
  }

  public function testStaleIpWasCreatedInIxmaps(): void
  {
    $this->assertEquals(date('Y-m-d'), $this->geoService->getByIp('139.173.18.10')->getCreatedAt()->format('Y-m-d'));
  }

  public function testStaleIpWasUpdatedInIpinfo(): void
  {
    $this->assertEquals(date('Y-m-d'), $this->IIgeoService->getByIp('139.173.18.10')->getUpdatedAt()->format('Y-m-d'));

    $this->completedFlag = true;
  }

  public function tearDown(): void
  // there must be a better way to do this than with the completedFlag fence
  // if not, this must not be a common use case, and that means I'm doing something wrong
  {
    if ($this->completedFlag) {
      // $this->assertTrue($this->IXgeoService->deleteByIp('174.24.170.164'));
      // $this->assertTrue($this->IIgeoService->deleteByIp('174.24.170.164'));

      $this->assertTrue($this->IXgeoService->deleteByIpAndCreatedAt('139.173.18.10', date('Y-m-d')));

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

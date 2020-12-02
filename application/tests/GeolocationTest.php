<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../services/GeolocationService.php');
require_once('../services/IXmapsGeolocationService.php');
require_once('../services/IPInfoGeolocationService.php');
require_once('../services/IPInfoAPIService.php');


final class GeolocationTest extends TestCase
{
  private $geoservice;
  private $completedFlag = false;

  protected function setUp(): void
  {
    global $dbconn;
    $this->db = $dbconn;
    $this->geoservice = new GeolocationService($this->db);
    $this->IXgeoservice = new IXmapsGeolocationService($this->db);
    $this->IIgeoservice = new IPInfoGeolocationService($this->db);
  }

  public function testCannotBeFoundWithInvalidIpAddress(): void
  {
    $this->expectException(Exception::class);

    $geo = $this->geoservice->getByIp('nonsense');
  }

  public function testGetByIpMostRecent(): void
  {
    $this->assertEquals('2020-12-02', $this->geoservice->getByIp('192.205.37.77')->getCreatedAt()->format('Y-m-d'));
  }

  public function testGetByIpWithDate(): void
  {
    $this->assertEquals('2016-10-16', $this->geoservice->getByIpAndDate('192.205.37.77', '2017-05-05')->getCreatedAt()->format('Y-m-d'));
  }

  public function testGetByIpWithAsnsourceIp2(): void
  {
    $this->assertEquals('IP2Location', $this->geoservice->getByIp('139.173.18.10')->getASNsource());
  }

  public function testIpIsStale(): void
  {
    $this->assertTrue($this->geoservice->getByIpAndDate('192.205.37.77', '2017-05-05')->getStaleStatus());
  }

  public function testIpWasCreated(): void
  {
    $this->geoservice->upsert('174.24.170.164');
    $this->assertEquals('174.24.170.164', $this->geoservice->getByIp('174.24.170.164')->getIp());
  }

  // public function testIpWasCreatedInIpInfo(): void
  // {
  //   $this->assertEquals('174.24.170.164', $this->IIgeoservice->getByIp('174.24.170.164')->getIp());

  //   $this->completedFlag = true;
  // }

  // public function testIpInfoUpsert(): void
  // {
  //   $updatedAtOld = $this->IIgeoservice->getByIp('64.125.14.70')->getUpdatedAt();

  //   $geoData = new IPInfoAPIService('64.125.14.70');
  //   $this->IIgeoservice->upsert($geoData);

  //   $this->assertNotEqual($updatedAtOld, $this->IIgeoservice->getByIp('64.125.14.70')->getUpdatedAt());

  //   // time to clean up
  //   $this->completedFlag = true;
  // }


  // public function tearDown(): void
  // // there must be a better way to do this than with the completedFlag fence
  // // if not, this must not be a common use case, and that means I'm doing something wrong
  // {
  //   if ($this->completedFlag) {
  //     $this->assertTrue($this->IXgeoservice->deleteByIp('174.24.170.164'));
  //     $this->assertTrue($this->IIgeoservice->deleteByIp('174.24.170.164'));

  //     $this->completedFlag = false;
  //   }

  // }

  // public function testIpInsertedIntoIxmapsDb(): void
  // {
  //   $this->geoservice->174.24.170.164
  //   $this->assertTrue($this->geoservice->getByIpAndDate('192.205.37.77', '2017-05-05')->getStaleStatus());
  // }

  // public function testIpWasCreated(): void
  // {
  //   $this->assertTrue($this->geoservice->create('174.24.170.164'));
  // }

  // public function testCityValueRetrieval(): void
  // {
  //   $this->assertEquals($this->geoservice->getByIp('174.24.170.164')->getCity(), 'Candor');
  // }

  // public function testIpWasDeleted(): void
  // {
  //   $this->assertTrue($this->geoservice->deleteByIp('174.24.170.164'));
  // }

  // public function testExistingCityValueRetrieval(): void
  // {
  //   $this->assertEquals($this->geo->getCity(), 'Chicago');
  // }

  // public function testNonExistentCityValueRetrieval(): void
  // {
  //   $geo = new Geolocation('70.67.160.1');
  //   $this->assertEquals($geo->getCity(), '');
  // }

  // expand this when we have sorted the geolocation sources / structure
}

// exists in IXmaps and gl_override is not null
// $a = new Geolocation('4.35.108.230');

// does not exist in IXmaps
//$a = new Geolocation('70.67.160.1');

// // does not exist anywhere
//$a = new Geolocation('127.0.0.1');

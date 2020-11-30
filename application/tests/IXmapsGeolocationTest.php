<?php declare(strict_types=1);
/**
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../services/IXmapsGeolocationService.php');


final class IXmapsGeolocationTest extends TestCase {
  private $geoservice;

  protected function setUp(): void
  {
    global $dbconn;
    $this->geoservice = new IXmapsGeolocationService($dbconn);
  }

  public function testGetByIp(): void
  {
    $this->assertEquals('89.149.187.190', $this->geoservice->getByIp('89.149.187.190')->getIp());
  }

  public function testCannotBeFoundWithInvalidIpAddress(): void
  {
    $this->expectException(Exception::class);

    $geo = $this->geoservice->getByIp('nonsense');
  }

  public function testIpDoesNotExistInDb(): void
  {
    $this->assertFalse($this->geoservice->getByIp('1.0.0.0'));
  }

  public function testIpWasCreated(): void
  {
    $this->assertTrue($this->geoservice->create('174.24.170.164'));
  }

  public function testCityValueRetrieval(): void
  {
    $this->assertEquals($this->geoservice->getByIp('174.24.170.164')->getCity(), 'Carthage');
  }

  public function testIpWasDeleted(): void
  {
    $this->assertTrue($this->geoservice->deleteByIp('174.24.170.164'));
  }

}
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
require_once('../services/IPInfoGeolocationService.php');
require_once('../services/IPInfoAPIService.php');


final class IPInfoGeolocationTest extends TestCase {
  private $geoservice;

  protected function setUp(): void
  {
    global $dbconn;
    $this->geoservice = new IPInfoGeolocationService($dbconn);
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

  public function testDuplicateIpCannotBeCreated(): void
  {
    $geoData = new IPInfoAPIService('174.24.170.164');
    $this->expectException(Exception::class);
    $this->geoservice->create($geoData);
    // $this->assertEquals('174.24.170.164', $this->geoservice->getByIp('174.24.170.164')->getIp());
  }


}
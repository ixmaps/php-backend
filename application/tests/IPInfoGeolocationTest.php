<?php declare(strict_types = 1);
/**
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../services/IPInfoAPIService.php');
require_once('../services/IPInfoGeolocationService.php');
require_once('../repositories/IPInfoGeolocationRepository.php');
require_once('../model/IPInfoGeolocation.php');


final class IPInfoGeolocationTest extends TestCase {
  private $geoService;

  protected function setUp(): void
  {
    $geo = new IPInfoGeolocation();
    $geoRepo = new IPInfoGeolocationRepository($geo);
    $this->geoService = new IPInfoGeolocationService($geoRepo);
  }

  public function testGetByIp(): void
  {
    $this->assertEquals('89.149.187.190', $this->geoService->getByIp('89.149.187.190')->getIp());
  }

  public function testCannotBeFoundWithInvalidIpAddress(): void
  {
    $this->expectException(Exception::class);

    $geo = $this->geoService->getByIp('nonsense');
  }

  public function testIpDoesNotExistInDb(): void
  {
    $this->assertFalse($this->geoService->getByIp('1.0.0.0'));
  }

  public function testDuplicateIpCannotBeCreated(): void
  {
    $geoData = new IPInfoAPIService('174.24.170.164');
    $this->expectException(Exception::class);
    $this->geoService->create($geoData);
    // $this->assertEquals('174.24.170.164', $this->geoService->getByIp('174.24.170.164')->getIp());
  }


}
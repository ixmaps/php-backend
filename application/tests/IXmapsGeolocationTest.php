<?php declare(strict_types = 1);
/**
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../services/IPInfoAPIService.php');
require_once('../services/IXmapsGeolocationService.php');
require_once('../repositories/IXmapsGeolocationRepository.php');
require_once('../model/IXmapsGeolocation.php');


final class IXmapsGeolocationTest extends TestCase {
  private $geoService;

  protected function setUp(): void
  {
    $geo = new IXmapsGeolocation();
    $geoRepo = new IXmapsGeolocationRepository($geo);
    $this->geoService = new IXmapsGeolocationService($geoRepo);
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

  public function testIpWasCreated(): void
  {
    $geoData = new IPInfoAPIService('174.24.170.164');
    $geo = $this->geoService->create($geoData);

    $this->assertEquals('174.24.170.164', $this->geoService->getByIp('174.24.170.164')->getIp());
  }

  public function testCityValueRetrieval(): void
  {
    $this->assertEquals($this->geoService->getByIp('174.24.170.164')->getCity(), 'Candor');
  }

  public function testIpWasDeleted(): void
  {
    $this->assertTrue($this->geoService->deleteByIp('174.24.170.164'));
  }

}
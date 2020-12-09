<?php declare(strict_types = 1);
/**
 *
 * @author IXmaps.ca (Colin)
 * @since Nov 2020
 *
 */

use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../services/MaxmindGeolocationService.php');
require_once('../repositories/MaxMindGeolocationRepository.php');
require_once('../model/MaxmindGeolocation.php');

final class MaxmindGeolocationTest extends TestCase {
  private $geoService;

  protected function setUp(): void
  {
    $geo = new MaxmindGeolocation();
    $geoRepo = new MaxMindGeolocationRepository($geo);
    $this->geoService = new MaxmindGeolocationService($geoRepo);
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
    $this->assertFalse($this->geoService->getByIp('198.19.23.1'));
  }

  public function testCityValueRetrieval(): void
  {
    $this->assertEquals($this->geoService->getByIp('174.24.170.164')->getCity(), 'Carthage');
  }

}
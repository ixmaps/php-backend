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
require_once('../services/MaxmindGeolocationService.php');


final class MaxmindGeolocationTest extends TestCase {
  private $geoservice;

  protected function setUp(): void
  {
    $this->geoservice = new MaxmindGeolocationService();
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
    $this->assertFalse($this->geoservice->getByIp('198.19.23.1'));
  }

  public function testCityValueRetrieval(): void
  {
    $this->assertEquals($this->geoservice->getByIp('174.24.170.164')->getCity(), 'Carthage');
  }

}
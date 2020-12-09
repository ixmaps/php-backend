<?php declare(strict_types = 1);
/**
  *
  * @author IXmaps.ca (Colin)
  * @since Nov 2020
  *
  */

use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../repositories/IP2LocationGeolocationRepository.php');
require_once('../services/IP2LocationGeolocationService.php');
require_once('../model/IP2LocationGeolocation.php');


final class IP2LocationGeolocationTest extends TestCase {
  private $geoservice;
  private $testIp = '89.149.187.190';
  private $testCity = 'Rocky Mount';

  protected function setUp(): void
  {
    global $dbconn;
    // $geo = new IP2LocationGeolocation();
    // $geoRepo = new IP2LocationGeolocationRepository($geo);

    $geoObj = $this->createMock(IP2LocationGeolocation::class);
    $geoObj->method('getIp')
           ->willReturn($this->testIp);
    $geoObj->method('getCity')
           ->willReturn($this->testCity);

    $geoRepo = $this->getMockBuilder(IP2LocationGeolocationRepository::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['getByIp'])
                    ->getMock();

    $geoRepo->method('getByIp')
            ->willReturn($geoObj);


    $this->geoService = new IP2LocationGeolocationService($geoRepo);
  }

  public function testIp2ObjectType(): void
  {
    $this->assertInstanceOf(IP2LocationGeolocation::class, $this->geoService->getByIp($this->testIp));
  }

  public function testGetByIp(): void
  {
    $this->assertEquals($this->testIp, $this->geoService->getByIp($this->testIp)->getIp());
  }

  public function testCannotBeFoundWithInvalidIpAddress(): void
  {
    $this->expectException(Exception::class);

    $geo = $this->geoService->getByIp('nonsense');
  }

  public function testCityValueRetrieval(): void
  {
    $this->assertEquals($this->geoService->getByIp($this->testIp)->getCity(), $this->testCity);
  }

}
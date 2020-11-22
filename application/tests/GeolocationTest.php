<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../model/Geolocation.php');


final class GeolocationTest extends TestCase
{
  private $geo;
  // private $completedFlag = false;

  protected function setUp(): void
  {
    $this->geo = new Geolocation('4.35.108.230');
  }

  public function testCannotBeCreatedFromInvalidIpAddress(): void
  {
    $this->expectException(Exception::class);

    // Geolocation::build('nonsense');
    $geo = new Geolocation('nonsense');
  }

  public function testTypeGeoObject(): void
  {
    $this->assertInstanceOf(Geolocation::class, $this->geo);
  }

  public function testExistingCityValueRetrieval(): void
  {
    $this->assertEquals($this->geo->getCity(), 'Chicago');
  }

  public function testNonExistentCityValueRetrieval(): void
  {
    $geo = new Geolocation('70.67.160.1');
    $this->assertEquals($geo->getCity(), '');
  }

  // expand this when we have sorted the geolocation sources / structure
}

// exists in IXmaps and gl_override is not null
// $a = new Geolocation('4.35.108.230');

// does not exist in IXmaps
//$a = new Geolocation('70.67.160.1');

// // does not exist anywhere
//$a = new Geolocation('127.0.0.1');

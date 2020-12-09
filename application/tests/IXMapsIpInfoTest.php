<?php declare(strict_types = 1);
use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../model/IXmapsIpInfo.php');
require_once('../model/IXmapsIpInfoFactory.php');


final class IXMapsIpInfoTest extends TestCase
{
  private $ii;

  protected function setUp(): void
  {
    // $this->ii = IXmapsIpInfoFactory::build('174.24.170.164');
    // todo - get IP not in DB instead
    $this->ii = $this->createStub(IXmapsIpInfo::class);

    $this->ii->method('getCity')
             ->willReturn('Candor');
  }

  public function testCannotBeCreatedFromInvalidIpAddress(): void
  {
    $this->expectException(Exception::class);

    IXmapsIpInfoFactory::build('nonsense');
  }

  public function testTypeIIObject(): void
  {
    $this->assertInstanceOf(IXmapsIpInfo::class, $this->ii);
  }

  // would it be useful to test *each* get?
  public function testCityValueRetrieval(): void
  {
    $this->assertEquals($this->ii->getCity(), 'Candor');
  }

}
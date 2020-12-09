<?php declare(strict_types = 1);
use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../model/GatherTr.php');
require_once('../model/IXmapsIpInfo.php');
require_once('../model/IXmapsIpInfoFactory.php');
require_once('../model/Geolocation.php');


final class InsertNewIpTest extends TestCase
{
  private $ii;
  private $completedFlag = false;

  /* TODO: these tests will fail until
      - we fix region from charvar(2)  to charvar(longer than 2)
      - we relabel the values from mm_ to ii_ (or something?)
        - think about how to approach this
          - could be a good change to get rid of all of those stupid mms
          - do we still need mm_ if we're not doing corrections?
            - if so, we want: lat / long + ii_lat / ii_long + city, country, etc
          - we will need to go through the entire codebase to deal with mm_
  */

  protected function setUp(): void
  {
    // find IP not in DB (how?)
    $this->ii = IXmapsIpInfoFactory::build('174.24.170.164');
  }

  public function testGlobals(): void
  {
    global $IIAccessToken;
    global $dbconn;

    $this->assertEquals($IIAccessToken, '61a406bb0bae69');
    // TODO: test the $dbconn?
  }

  public function testInsertIp(): int
  {
    // should this be in the setUp?
    $insertReturnResult = GatherTr::insertNewIp($this->ii->getIp());
    $this->assertContains($insertReturnResult, array(1, 0));

    return $insertReturnResult;
  }

  /**
   * @depends testInsertIp
   */
  public function testInsertedIpHasBeenInsertedWithoutErrors(int $insertReturnResult): void
  {
    $this->assertEquals($insertReturnResult, 1);
  }

  public function testInsertedIpHasValues(): Geolocation
  // change this to return array and pass it along with a depends
  {
    // global $dbconn;

    // $sql = "SELECT * FROM ip_addr_info WHERE ip_addr = '".$this->ii->getIp()."'";
    // $result = pg_query($dbconn, $sql) or die('checkIpExists: Query failed on the ip '.$ip.' with error '.pg_last_error());
    // $row = pg_fetch_all($result);
    // pg_free_result($result);

    $geo = new Geolocation('174.24.170.164');

    $this->assertEquals($geo->getIp(), $this->ii->getIp());

    // clean up DB
    $this->completedFlag = true;

    return $geo;
  }

  /**
   * @depends testInsertedIpHasValues
   */
  public function testInsertedIpCityNotNull(Geolocation $geo): void
  {
    $this->assertNotNull($geo->getCity());
  }

  /**
   * @depends testInsertedIpHasValues
   */
  public function testInsertedIpCountryNotNull(Geolocation $geo): void
  {
    $this->assertNotNull($geo->getCountry());
  }

  /**
   * @depends testInsertedIpHasValues
   */
  public function testInsertedIpLatNotNull(Geolocation $geo): void
  {
    $this->assertNotNull($geo->getLat());
  }

  /**
   * @depends testInsertedIpHasValues
   */
  public function testInsertedIpLongNotNull(Geolocation $geo): void
  {
    $this->assertNotNull($geo->getLong());
  }

  public function tearDown(): void
  // there must be a better way to do this than with the completedFlag fence
  // if not, this must not be a common use case, and that means I'm doing something wrong
  {
    if ($this->completedFlag) {
      global $dbconn;

      $sql = "DELETE FROM ip_addr_info WHERE ip_addr = '".$this->ii->getIp()."'";
      $result = pg_query($dbconn, $sql) or die('checkIpExists: Query failed on the ip '.$ip.' with error '.pg_last_error());
      $row = pg_fetch_all($result);
      pg_free_result($result);

      $this->completedFlag = false;
    }

  }

}


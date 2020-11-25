<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

chdir(dirname(__FILE__));
require_once('../config.php');
require_once('../services/GeolocationService.php');


final class IXmapsGeolocationTest extends TestCase {
  private $dbconn;
  private $geoservice;

  protected function setUp(): void
  {
    global $dbconn;
    $this->dbconn = $dbconn;
    $this->geoservice = new GeolocationService();
  }

  public function testFindByIp(): void
  {
    $this->assertEquals('89.149.187.190', $this->geoservice->fetchGeolocation('89.149.187.190')->getIp());
  }

  public function testCannotBeFoundWithInvalidIpAddress(): void
  {
    $this->expectException(Exception::class);

    $geo = $this->geoservice->fetchGeolocation('nonsense');
  }

  // public function testThrowsExceptionWhenTryingToFindPostWhichDoesNotExist()
  // {
  //     $this->expectException(OutOfBoundsException::class);
  //     $this->expectExceptionMessage('Post with id 42 does not exist');

  //     $this->repository->findById(PostId::fromInt(42));
  // }

  // public function testCanPersistPostDraft()
  // {
  //     $postId = $this->repository->generateId();
  //     $post = Post::draft($postId, 'Repository Pattern', 'Design Patterns PHP');
  //     $this->repository->save($post);

  //     $this->repository->findById($postId);

  //     $this->assertEquals($postId, $this->repository->findById($postId)->getId());
  //     $this->assertEquals(PostStatus::STATE_DRAFT, $post->getStatus()->toString());
  // }
}
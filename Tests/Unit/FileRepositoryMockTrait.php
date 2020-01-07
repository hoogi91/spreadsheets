<?php

namespace Hoogi91\Spreadsheets\Tests\Unit;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;

/**
 * Trait FileRepositoryMockTrait
 * @package Hoogi91\Spreadsheets\Tests\Unit
 */
trait FileRepositoryMockTrait
{

    /**
     * @return MockObject
     */
    private function getFileRepositoryMock(): MockObject
    {
        /** @var UnitTestCase $_this */
        $_this = $this;

        /** @var UnitTestCase $this */
        $filRepositoryMock = $this->getMockBuilder(FileRepository::class)->disableOriginalConstructor()->getMock();
        $filRepositoryMock->expects($this->any())->method('findFileReferenceByUid')->willReturnCallback(
            static function (int $fileUid) use ($_this) {
                if ($fileUid < 1) {
                    // force mock to throw an resource does not exists exception
                    throw new ResourceDoesNotExistException('[PHPUnit] Mocked resource does not exists');
                }

                $mock = $_this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock();
                $mock->method('getUid')->willReturn($fileUid);
                return $mock;
            }
        );

        return $filRepositoryMock;
    }

    /**
     * @return MockObject
     */
    private function getContainerMock(): MockObject
    {
        /** @var UnitTestCase $this */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->expects($this->any())->method('has')->willReturn(true);

        return $container;
    }
}

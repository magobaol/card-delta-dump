<?php

namespace Tests\Model;

use FilterIterator;
use Model\CDDProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;
use Symfony\Component\Finder\SplFileInfo;

class CDDProcessTest extends TestCase
{
    /**
     * @var mixed|MockObject|Filesystem
     */
    private mixed $filesystem;

    /**
     * @var mixed|MockObject|Finder
     */
    private mixed $finder;
    private CDDProcess $CDCProcess;

    public function setUp(): void
    {
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->finder = $this->getMockBuilder(Finder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->finder->method('in')->with(['./source-dir'])->willReturn($this->finder);

        $this->CDCProcess = new CDDProcess($this->finder, $this->filesystem, './source-dir', './target-mirror-dir', './target-import-dir');
    }

    public function test_analyze()
    {
        $this->finder
            ->method('getIterator')
            ->willReturn(
                new \ArrayIterator([

                    //Existing in the mirror
                    new SplFileInfo('file-to-be-skipped.jpg', '', 'file-to-be-skipped.jpg'),

                    //Not existing but with extension not to be imported
                    new SplFileInfo('file-to-be-mirrored.log', '', 'file-to-be-mirrored.log'),

                    //Not existing and with extension to be imported
                    new SplFileInfo('file-to-be-mirrored-and-imported.jpg', '', 'file-to-be-mirrored-and-imported.jpg'),
                ])
            );

        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['./target-mirror-dir/file-to-be-skipped.jpg', true],
                ['./target-mirror-dir/file-to-be-mirrored.log', false],
                ['./target-mirror-dir/file-to-be-mirrored-and-imported.jpg', false],
            ]));

        $this->CDCProcess->analyse();

        $this->assertEquals(3, $this->CDCProcess->countAll());
        $this->assertEquals(1, $this->CDCProcess->countSkipOperations());
        $this->assertEquals(1, $this->CDCProcess->countMirrorOperations());
        $this->assertEquals(1, $this->CDCProcess->countMirrorAndImportOperations());
    }

    public function test_execute()
    {
        $this->finder
            ->method('getIterator')
            ->willReturn(
                new \ArrayIterator([

                    //Existing in the mirror
                    new SplFileInfo('file-to-be-skipped.jpg', '', 'file-to-be-skipped.jpg'),

                    //Not existing but with extension not to be imported
                    new SplFileInfo('file-to-be-mirrored.log', '', 'file-to-be-mirrored.log'),

                    //Not existing and with extension to be imported
                    new SplFileInfo('file-to-be-mirrored-and-imported.jpg', '', 'file-to-be-mirrored-and-imported.jpg'),
                ])
            );

        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['./target-mirror-dir/file-to-be-skipped.jpg', true],
                ['./target-mirror-dir/file-to-be-mirrored.log', false],
                ['./target-mirror-dir/file-to-be-mirrored-and-imported.jpg', false],
            ]));

        $this->CDCProcess->analyse();

        $fileOperations = [];
        foreach ($this->CDCProcess->execute() as $operation) {
            $fileOperations[] = $operation;
        }

        // 1 file to be mirrored + 1 file to be mirror and imported = 3 copy operations
        $this->assertCount(3, $fileOperations);

        $this->assertEquals('./source-dir/file-to-be-mirrored.log', $fileOperations[0]->getSourceFile());
        $this->assertEquals('./target-mirror-dir/file-to-be-mirrored.log', $fileOperations[0]->getTargetFile());

        $this->assertEquals('./source-dir/file-to-be-mirrored-and-imported.jpg', $fileOperations[1]->getSourceFile());
        $this->assertEquals('./target-mirror-dir/file-to-be-mirrored-and-imported.jpg', $fileOperations[1]->getTargetFile());

        $this->assertEquals('./source-dir/file-to-be-mirrored-and-imported.jpg', $fileOperations[2]->getSourceFile());
        $this->assertEquals('./target-import-dir/file-to-be-mirrored-and-imported.jpg', $fileOperations[2]->getTargetFile());
    }

    public function test_execute_without_analysis_it_should_throw_exception()
    {
        $this->expectExceptionMessage('Run the analysis first');

        foreach ($this->CDCProcess->execute() as $operation) {
            //Needed to run the generator
        }
    }
}
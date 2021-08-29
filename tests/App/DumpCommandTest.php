<?php

namespace Tests\App;

use App\CardHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DumpCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private string $mirrorBaseDir;
    private mixed $filesystem;
    private mixed $cardHelper;
    private mixed $finder;
    private string $importBaseDir;

    public function setUp(): void
    {
        $this->mirrorBaseDir = 'tests/Fixtures/execution-env/sd-card-mirror';
        $this->importBaseDir = 'tests/Fixtures/execution-env/sd-card-import';

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->finder = $this->getMockBuilder(Finder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cardHelper = $this->getMockBuilder(CardHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cardHelper->method('getSourceDirFromCardName')->with('PIC-0001')->willReturn('/Volumes/PIC-0001');
        $this->cardHelper->method('isValidCardName')
            ->will($this->returnValueMap([
                ['PIC-0001', true],
                ['an-invalid-name-format', false]
            ]));

        $this->cardHelper->method('getCardDirFromBaseDirAndCardName')
            ->will($this->returnValueMap([
                [$this->mirrorBaseDir, 'PIC-0001', $this->mirrorBaseDir.'/PIC-0001'],
                [$this->importBaseDir, 'PIC-0001', $this->importBaseDir.'/PIC-0001'],
            ]));

        $kernel = static::createKernel();
        $kernel->boot();
        $kernel->getContainer()->set(Filesystem::class, $this->filesystem);
        $kernel->getContainer()->set(CardHelper::class, $this->cardHelper);
        $application = new Application($kernel);

        $command = $application->find('app:dump');
        $this->commandTester = new CommandTester($command);

    }

    public function test_execute_with_invalid_card_name_it_should_fail()
    {
        $this->commandTester->execute([
            'card-name' => 'an-invalid-name-format'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('The card name is not in valid format', $output);
    }

    public function test_execute_with_not_existing_source_dir_it_should_fail()
    {
        $this->filesystem->method('exists')->with('/Volumes/PIC-0001')->willReturn(false);

        $this->commandTester->execute([
            'card-name' => 'PIC-0001'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('the card is not inserted', $output);
    }

    public function test_execute_with_no_mirror_base_dir_passed_as_option_it_should_use_the_env_base_dir()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                ['dir/defined/in/env/file', false]
            ]));

        $this->commandTester->setInputs([
            'no', //Should I create the base dir?
        ]);

        $this->commandTester->execute([
            'card-name' => 'PIC-0001'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('mirror/dir/defined/in/env/file', $output);
    }

    public function test_execute_with_no_import_base_dir_passed_as_option_it_should_use_the_env_base_dir()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                ['dir/defined/in/env/file', false]
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the mirror base dir?
            'no', //Should I create the import base dir?
        ]);

        $this->commandTester->execute([
            'card-name' => 'PIC-0001'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('import/dir/defined/in/env/file', $output);
    }

    public function test_execute_with_not_existing_mirror_base_dir_it_should_ask_and_create()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false],
                [$this->importBaseDir, false],
                [$this->mirrorBaseDir.'/PIC-0001', false],
                [$this->importBaseDir.'/PIC-0001', false],
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the mirror base dir?
            'no',  //Should I create the mirror import dir?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--mirror-base-dir' => $this->mirrorBaseDir,
            '--import-base-dir' => $this->importBaseDir,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Base mirror directory created', $output);
    }

    public function test_execute_with_not_existing_mirror_base_dir_it_should_ask_and_fail()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false],
                [$this->importBaseDir, false],
                [$this->mirrorBaseDir.'/PIC-0001', false],
                [$this->importBaseDir.'/PIC-0001', false],
            ]));

        $this->commandTester->setInputs([
            'no' //Should I create the mirror base dir?
        ]);

        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--mirror-base-dir' => $this->mirrorBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(Command::FAILURE, $statusCode);
    }

    public function test_execute_with_not_existing_mirror_card_dir_it_should_ask_and_create()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false],
                [$this->importBaseDir, false],
                [$this->mirrorBaseDir.'/PIC-0001', false],
                [$this->importBaseDir.'/PIC-0001', false],
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the mirror base dir?
            'yes',  //Should I create the import base dir?
            'yes',  //Should I create the mirror card dir?
            'no', //Should I create the import card dir?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--mirror-base-dir' => $this->mirrorBaseDir,
            '--import-base-dir' => $this->importBaseDir,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Final mirror directory created', $output);
    }

    public function test_execute_with_not_existing_mirror_card_dir_it_should_ask_and_fail()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false],
                [$this->importBaseDir, false],
                [$this->mirrorBaseDir.'/PIC-0001', false],
                [$this->importBaseDir.'/PIC-0001', false],
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the mirror base dir?
            'yes',  //Should I create the import base dir?
            'no',  //Should I create the mirror card dir?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--mirror-base-dir' => $this->mirrorBaseDir,
            '--import-base-dir' => $this->importBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(Command::FAILURE, $statusCode);
    }

    public function test_execute_with_not_existing_import_base_dir_it_should_ask_and_create()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false],
                [$this->importBaseDir, false],
                [$this->mirrorBaseDir.'/PIC-0001', false],
                [$this->importBaseDir.'/PIC-0001', false],
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the mirror base dir?
            'yes',  //Should I create the import base dir?
            'no', //Should I create the mirror card dir?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--mirror-base-dir' => $this->mirrorBaseDir,
            '--import-base-dir' => $this->importBaseDir,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Base mirror directory created', $output);
    }

    public function test_execute_with_not_existing_import_base_dir_it_should_ask_and_fail()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false],
                [$this->importBaseDir, false],
                [$this->mirrorBaseDir.'/PIC-0001', false],
                [$this->importBaseDir.'/PIC-0001', false],
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the mirror base dir?
            'no',  //Should I create the import base dir?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--mirror-base-dir' => $this->mirrorBaseDir,
            '--import-base-dir' => $this->importBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(Command::FAILURE, $statusCode);
    }

    public function test_execute_with_not_existing_import_card_dir_it_should_ask_and_create()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false],
                [$this->importBaseDir, false],
                [$this->mirrorBaseDir.'/PIC-0001', false],
                [$this->importBaseDir.'/PIC-0001', false],
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the mirror base dir?
            'yes',  //Should I create the import base dir?
            'yes', //Should I create the mirror card dir?
            'yes', //Should I create the mirror card dir?
            'no' //Should I continue with analysis?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--mirror-base-dir' => $this->mirrorBaseDir,
            '--import-base-dir' => $this->importBaseDir,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Final import directory created', $output);
    }

    public function test_execute_with_not_existing_import_card_dir_it_should_ask_and_fail()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false],
                [$this->importBaseDir, false],
                [$this->mirrorBaseDir.'/PIC-0001', false],
                [$this->importBaseDir.'/PIC-0001', false],
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the mirror base dir?
            'yes',  //Should I create the import base dir?
            'yes',  //Should I create the mirror card dir?
            'no',  //Should I create the import card dir?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--mirror-base-dir' => $this->mirrorBaseDir,
            '--import-base-dir' => $this->importBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(Command::FAILURE, $statusCode);
    }
}
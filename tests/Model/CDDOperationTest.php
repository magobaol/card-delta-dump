<?php

namespace Tests\Model;

use Model\CDDOperation;
use PHPUnit\Framework\TestCase;

class CDDOperationTest extends TestCase
{
    public function test_it_should_create_skip_operation()
    {
        $op = new CDDOperation('source-file.arw');
        $op->skip();

        $this->assertTrue($op->isSkip());
    }

    public function test_it_should_create_mirror_operation()
    {
        $op = new CDDOperation('source-file.arw');
        $op->mirror('mirror-target-file.arw');

        $this->assertTrue($op->isMirror());
    }

    public function test_it_should_create_mirror__and_import_operation()
    {
        $op = new CDDOperation('source-file.arw');
        $op->mirrorAndImport('mirror-target-file.arw', 'import-target-file.arw');

        $this->assertTrue($op->isMirrorAndImport());
    }
}
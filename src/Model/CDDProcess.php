<?php

namespace Model;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CDDProcess
{
    /**
     * @var CDDOperation[]
     */
    private array $operations;

    private bool $isAnalysisDone;
    private Finder $finder;
    private Filesystem $fs;
    private string $sourceDir;
    private string $mirrorCardDir;
    private string $importCardDir;

    private function resetAnalysis()
    {
        $this->operations = [];
        $this->isAnalysisDone = false;
    }

    public function __construct(Finder $finder, Filesystem $fs, string $sourceDir, string $mirrorCardDir, string $importCardDir)
    {
        $this->resetAnalysis();
        $this->finder = $finder;
        $this->fs = $fs;
        $this->sourceDir = $sourceDir;
        $this->mirrorCardDir = $mirrorCardDir;
        $this->importCardDir = $importCardDir;
    }

    /**
     * @return CDDOperation[]
     */
    public function analyse(): array
    {
        $this->finder->in([$this->sourceDir])->name('*.*');

        foreach ($this->finder as $file) {
            $sourceFile = $this->sourceDir . '/' . $file->getRelativePathname();
            $targetMirrorFile = $this->mirrorCardDir . '/' . $file->getRelativePathname();

            $operation = new CDDOperation($sourceFile);

            if ($this->fs->exists($targetMirrorFile)) {
                $operation->skip();
            } else {
                if ($this->fileShouldBeImported($file)) {
                    $targetImportFile = $this->importCardDir . '/' . $file->getFilename();
                    $operation->mirrorAndImport($targetMirrorFile, $targetImportFile);
                } else {
                    $operation->mirror($targetMirrorFile);
                }
            }

            $this->operations[] = $operation;
        }
        $this->isAnalysisDone = true;
        return $this->operations;
    }

    /**
     * @param mixed $file
     * @return bool
     */
    protected function fileShouldBeImported(mixed $file): bool
    {
        return in_array($file->getExtension(), ['ARW', 'arw', 'jpg', 'JPG', 'MP4', 'mp4']);
    }

    public function countSkipOperations(): int
    {
        return count(array_filter($this->operations, function($operation) {
            return $operation->isSkip();
        }));
    }

    public function countMirrorOperations(): int
    {
        return count(array_filter($this->operations, function($operation) {
            return $operation->isMirror();
        }));
    }

    public function countMirrorAndImportOperations(): int
    {
        return count(array_filter($this->operations, function($operation) {
            return $operation->isMirrorAndImport();
        }));
    }

    public function countAll(): int
    {
        return count($this->operations);
    }

    /**
     * @return CDDOperation[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    public function execute(): \Generator
    {
        if (!$this->isAnalysisDone) {
            throw new \Exception('Run the analysis first');
        }
        foreach ($this->operations as $operation) {
            $fileOperations = $operation->getActualCopyOperations();
            foreach ($fileOperations as $fileOperation) {
                if ($fileOperation->isCopy()) {
                    $this->fs->copy($fileOperation->getSourceFile(), $fileOperation->getTargetFile());
                    yield $fileOperation;
                }
            }
        }
    }

}
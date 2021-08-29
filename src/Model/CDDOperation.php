<?php

namespace Model;

class CDDOperation
{
    private const TYPE_SKIP = 'skip';
    private const TYPE_MIRROR = 'mirror';
    private const TYPE_MIRROR_IMPORT = 'mirror_import';

    private string $sourceFile;
    private string $targetMirrorFile;
    private string $targetImportFile;
    private string $type;

    public function __construct(string $sourceFile)
    {
        $this->sourceFile = $sourceFile;
    }

    public function skip()
    {
        $this->type = self::TYPE_SKIP;
    }

    public function mirror(string $targetMirrorFile)
    {
        $this->type = self::TYPE_MIRROR;
        $this->targetMirrorFile = $targetMirrorFile;
    }

    public function mirrorAndImport(string $targetMirrorFile, string $targetImportFile)
    {
        $this->type = self::TYPE_MIRROR_IMPORT;
        $this->targetMirrorFile = $targetMirrorFile;
        $this->targetImportFile = $targetImportFile;
    }

    public function isSkip(): bool
    {
        return $this->type == self::TYPE_SKIP;
    }

    public function isMirror(): bool
    {
        return $this->type == self::TYPE_MIRROR;
    }

    public function isMirrorAndImport(): bool
    {
        return $this->type == self::TYPE_MIRROR_IMPORT;
    }

    private function getPaddedOperation(string $operation): string
    {
        $padLength = strlen(self::TYPE_MIRROR_IMPORT);
        return str_pad(strtoupper($operation), $padLength, ' ');
    }

    /**
     * @return FileOperation[]
     */
    public function getActualCopyOperations(): array
    {
        switch ($this->type) {
            case self::TYPE_SKIP:
                return [FileOperation::createSkip($this->sourceFile)];
            case self::TYPE_MIRROR:
                return [FileOperation::createCopy($this->sourceFile, $this->targetMirrorFile)];
            case self::TYPE_MIRROR_IMPORT:
                return [
                    FileOperation::createCopy($this->sourceFile, $this->targetMirrorFile),
                    FileOperation::createCopy($this->sourceFile, $this->targetImportFile),
                ];
        }
    }

    public function toString(): string
    {
        return sprintf('%s : %s', $this->getPaddedOperation($this->type), $this->sourceFile);
    }
}
<?php

namespace Model;

class FileOperation
{
    private const TYPE_COPY = 'copy';
    private const TYPE_SKIP = 'skip';

    private string $sourceFile;
    private string $targetFile;
    private string $type;

    private function __construct(string $sourceFile, string $targetFile, string $type)
    {
        $this->sourceFile = $sourceFile;
        $this->targetFile = $targetFile;
        $this->type = $type;
    }

    public static function createCopy(string $sourceFile, string $targetFile): FileOperation
    {
        return new self($sourceFile, $targetFile, self::TYPE_COPY);
    }

    public static function createSkip(string $sourceFile): FileOperation
    {
        return new self($sourceFile, '', self::TYPE_SKIP);
    }

    /**
     * @return string
     */
    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    /**
     * @return string
     */
    public function getTargetFile(): string
    {
        return $this->targetFile;
    }

    public function isCopy(): bool
    {
        return $this->type == self::TYPE_COPY;
    }

    public function toString(): string
    {
        switch ($this->type) {
            case self::TYPE_SKIP:
                return sprintf('%s: %s', strtoupper($this->type), $this->sourceFile);
            case self::TYPE_COPY:
                return sprintf('%s: %s ==> %s', strtoupper($this->type), $this->sourceFile, $this->targetFile);
        }
    }
}
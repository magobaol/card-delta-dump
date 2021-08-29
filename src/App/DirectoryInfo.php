<?php

namespace App;

class DirectoryInfo
{
    private $path;
    private $description;
    private $explanation;
    private $explanationForDirectoryMissing;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function withDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function withExplanation($explanation)
    {
        $this->explanation = $explanation;
        return $this;
    }

    public function withExplanationForDirectoryMissing($explanationForDirectoryMissing)
    {
        $this->explanationForDirectoryMissing = $explanationForDirectoryMissing;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExplanationForDirectoryMissing()
    {
        return $this->explanationForDirectoryMissing;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getExplanation()
    {
        return $this->explanation;
    }


}
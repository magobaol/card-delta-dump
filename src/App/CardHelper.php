<?php

namespace App;

class CardHelper
{
    public function getCardDirFromBaseDirAndCardName($mirrorBaseDir, $cardName): string
    {
        return realpath($mirrorBaseDir).'/'.$cardName;
    }

    public function getSourceDirFromCardName(string $cardName): string
    {
        return '/Volumes/'.$cardName;
    }

    public function isValidCardName($name): bool
    {
        return preg_match('/^PIC-[0-9]{4}$/', $name);
    }

    public function getSampleCardName(): string
    {
        return 'PIC-0001';
    }

}
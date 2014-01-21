<?php namespace DealerLive\LaravelTheme;

class FileViewFinder extends \Illuminate\View\FileViewFinder
{

    public function prependLocation($location)
    {
        array_unshift($this->paths, $location);
    }

}
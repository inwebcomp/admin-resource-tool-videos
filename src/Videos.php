<?php

namespace Admin\ResourceTools\Videos;

use InWeb\Admin\App\ResourceTool;

class Videos extends ResourceTool
{
    public $component = 'videos-tool';

    public function __construct()
    {
        parent::__construct();

        $this->chunks(2048000);
        $this->accept('video/mp4, video/mpeg, video/webm');
    }

    public function name()
    {
        return __('Видео');
    }

    public function chunks($bytes)
    {
        return $this->withMeta(['chunks' => $bytes]);
    }

    public function maxSize($value)
    {
        return $this->withMeta(['maxSize' => $value]);
    }

    public function accept($value)
    {
        return $this->withMeta(['accept' => $value]);
    }

    public function multiple($value = true)
    {
        return $this->withMeta(['multiple' => $value]);
    }
}

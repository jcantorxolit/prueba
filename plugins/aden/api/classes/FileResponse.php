<?php

namespace AdeN\Api\Classes;


class FileResponse
{
    private $originalFile;

    public $id;
    public $title;
    public $description;
    public $content_typ;
    public $disk_name;
    public $extension;
    public $field;
    public $file_name;
    public $file_size;
    public $path = null;
    public $created_at;

    public function __construct($file = null)
    {
        $this->originalFile = $file;

        $this->id = $file->id;
        $this->title = $file->title;
        $this->description = $file->description;
        $this->content_type = $file->content_type;
        $this->disk_name = $file->disk_name;
        $this->extension = $file->extension;
        $this->field = $file->field;
        $this->file_name = $file->file_name;
        $this->file_size = $file->file_size;
        $this->path = $file->getTemporaryUrl();
        $this->created_at = $file->created_at;
    }

    public function download($headers = null)
    {
        return $this->originalFile ? $this->originalFile->download($headers) : null;
    }

    public function getStream()
    {
        return $this->originalFile ? $this->originalFile->getStream() : null;
    }

    public function getContent()
    {
        return $this->originalFile ? $this->originalFile->getContent() : null;
    }

    public function getDiskPath()
    {
        return $this->path;
    }

    public function exists()
    {
        return \Storage::disk('s3')->exists($this->getDiskPath());
    }

}

<?php

class ImageResize extends ImageProcessor {
    
    public $name = null;

    public function run() {
        $path = $this->folder.$this->name.'.'.$this->format;
        switch ($this->width <=> $this->height) {
            case 0: {
                $width  = $this->size;
                $height = $this->size;
                break;
            }
            case 1: {
                $height = (int) ($this->height * ($this->size / $this->width));
                $width  = $this->size;
                break;
            }
            case -1: {
                $width  = (int) ($this->width * ($this->size / $this->height));
                $height = $this->size;
                break;
            }
        }
        switch ($this->processor) {
            case 'Imagick': {
                $thumb = clone $this->image;
                $thumb->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, false);
                $thumb->writeImage($path);
                break;
            }
            case 'GD': {
                $thumb = imagescale($this->image, $width, $height);
                touch($path);
                switch ($this->format) {
                    case 'png': {
                        imagepng($thumb, $path, $this->quality, PNG_NO_FILTER);
                    }
                    case 'gif': {
                        imagegif($thumb, $path);
                    }
                    case 'jpg':
                    case 'jpe':
                    case 'jpeg':
                    default: {
                        imagejpeg($thumb, $path, $this->quality);
                    }
                }
                imagedestroy($thumb);
                break;
            }
            case 'ImageMagick': {
                $params = [
                    '+repage',
                    '-flatten',
                    '-thumbnail '.escapeshellarg(sprintf('%sx%s!', $width, $height)),
                    '-quality '.$this->quality
                ];
                $command = sprintf(
                    '%s %s %s %s',
                    $this->convert,
                    escapeshellarg($this->image.'[0]'),
                    implode(' ', $params),
                    escapeshellarg($path)
                );
                Zord::execute($this->strategy, $command);
                break;
            }
        }
    }
    
}

?>
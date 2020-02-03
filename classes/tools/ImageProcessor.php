<?php

abstract class ImageProcessor {
    
    public $processor = '';
    public $convert   = '';
    public $strategy  = 'exec';
    public $format    = 'jpg';
    public $quality   = 100;
    public $size      = 256;
    public $file      = null;
    public $image     = null;
    public $width     = null;
    public $height    = null;
    public $folder    = null;
    
    public static $PROCESSORS        = ['Imagick' => 'imagick', 'GD' => 'gd'];
    public static $DEFAULT_PROCESSOR = 'ImageMagick';
    
    public abstract function run();
        
    public function __construct(array $config = []) {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        if (empty($this->processor)) {
            foreach (self::$PROCESSORS as $processor => $extension) {
                if (extension_loaded($extension)) {
                    $this->processor = $processor;
                    break;
                }
            }
            if (empty($this->processor)) {
                $this->processor = self::$DEFAULT_PROCESSOR;
            }
        }
        foreach (self::$PROCESSORS as $processor => $extension) {
            if ($this->processor == $processor) {
                if (!extension_loaded($extension)) {
                    throw new Exception($processor.' library is not available.');
                }
            }
        }
        if ($this->processor == self::$DEFAULT_PROCESSOR && empty($this->convert)) {
            $this->convert = Zord::execute($this->strategy, 'which convert');
            if (empty($this->convert)) {
                throw new Exception('Convert path is not available.');
            }
        }
    }
    
    public function process($file, $folder) {
        $this->file = realpath($file);
        $this->folder = $folder;
        $this->image = $this->load($file);
        list($this->width, $this->height) = getimagesize($this->file);
        $this->run();
        $this->destroy($this->image);
    }
    
    public function load($file) {
        $image = null;
        switch ($this->processor) {
            case 'Imagick': {
                $image = new Imagick();
                $image->readImage($file);
                $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
                $image->stripImage();
                break;
            }
            case 'GD': {
                $image = null;
                switch (strtolower(pathinfo($this->file, PATHINFO_EXTENSION))) {
                    case 'png': {
                        $image = imagecreatefrompng($file);
                    }
                    case 'gif': {
                        $image = imagecreatefromgif($file);
                    }
                    case 'jpg':
                    case 'jpe':
                    case 'jpeg':
                    default: {
                        $image = imagecreatefromjpeg($file);
                    }
                }
                break;
            }
            case 'ImageMagick': {
                $image = $file;
                break;
            }
        }
        return $image;
    }
    
    public function destroy($image) {
        switch ($this->processor) {
            case 'Imagick': {
                $image->destroy();
                break;
            }
            case 'GD': {
                imagedestroy($image);
                break;
            }
            case 'ImageMagick': {
                break;
            }
        }
    }
}

?>
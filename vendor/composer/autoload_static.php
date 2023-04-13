<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit634fc837cb008ebff4c5b547c200b55e
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\SimpleCache\\' => 16,
            'Psr\\Log\\' => 8,
            'Psr\\Cache\\' => 10,
        ),
        'L' => 
        array (
            'League\\MimeTypeDetection\\' => 25,
            'League\\Flysystem\\' => 17,
        ),
        'D' => 
        array (
            'Doctrine\\Common\\Cache\\' => 22,
        ),
        'C' => 
        array (
            'Cache\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\SimpleCache\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/simple-cache/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Psr\\Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/cache/src',
        ),
        'League\\MimeTypeDetection\\' => 
        array (
            0 => __DIR__ . '/..' . '/league/mime-type-detection/src',
        ),
        'League\\Flysystem\\' => 
        array (
            0 => __DIR__ . '/..' . '/league/flysystem/src',
        ),
        'Doctrine\\Common\\Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/doctrine/cache/lib/Doctrine/Common/Cache',
        ),
        'Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/cache/cache/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit634fc837cb008ebff4c5b547c200b55e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit634fc837cb008ebff4c5b547c200b55e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit634fc837cb008ebff4c5b547c200b55e::$classMap;

        }, null, ClassLoader::class);
    }
}
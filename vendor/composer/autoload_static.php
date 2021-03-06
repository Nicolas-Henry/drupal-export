<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit94b87c0167740ade2c5e318d41d8a475
{
    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Test\\Markdownify\\' => 17,
        ),
        'M' => 
        array (
            'Markdownify\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Test\\Markdownify\\' => 
        array (
            0 => __DIR__ . '/..' . '/pixel418/markdownify/test',
        ),
        'Markdownify\\' => 
        array (
            0 => __DIR__ . '/..' . '/pixel418/markdownify/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit94b87c0167740ade2c5e318d41d8a475::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit94b87c0167740ade2c5e318d41d8a475::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}

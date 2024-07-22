<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc7c6a7863f0497f46652f5746d2b42d4
{
    public static $prefixLengthsPsr4 = array (
        'm' => 
        array (
            'megastruktur\\' => 13,
        ),
        'N' => 
        array (
            'Ngenius\\NgeniusCommon\\' => 22,
            'NGenius\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'megastruktur\\' => 
        array (
            0 => __DIR__ . '/..' . '/megastruktur/phone-country-codes/src',
        ),
        'Ngenius\\NgeniusCommon\\' => 
        array (
            0 => __DIR__ . '/..' . '/ngenius/ngenius-common/src',
        ),
        'NGenius\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc7c6a7863f0497f46652f5746d2b42d4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc7c6a7863f0497f46652f5746d2b42d4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitc7c6a7863f0497f46652f5746d2b42d4::$classMap;

        }, null, ClassLoader::class);
    }
}

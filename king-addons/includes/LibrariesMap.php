<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class LibrariesMap
{
    public static function getLibrariesMapArray(): array
    {
        return ['libraries' => [
            'animation' => [
                'css' => ['general', 'button', 'timing'],
                'js' => []
            ],
            'swiper' => [
                'css' => ['swiper'],
                'js' => ['swiper']
            ],
            'flipclock' => [
                'css' => ['flipclock'],
                'js' => ['flipclock']
            ],
            'jarallax' => [
                'css' => [],
                'js' => ['jarallax']
            ],
            'jquery' => [
                'css' => [],
                'js' => ['jquery']
            ],
            'jquerynumerator' => [
                'css' => [],
                'js' => ['jquerynumerator']
            ],
            'odometer' => [
                'css' => ['minimal'],
                'js' => ['odometer']
            ],
            'particles' => [
                'css' => [],
                'js' => ['particles']
            ],
            'isotope' => [
                'css' => [],
                'js' => ['isotope']
            ],
            'imagesloaded' => [
                'css' => [],
                'js' => ['imagesloaded']
            ],
            'wpcolorpicker' => [
                'css' => [],
                'js' => ['wpcolorpicker']
            ],
            'perfectscrollbar' => [
                'css' => [],
                'js' => ['perfectscrollbar']
            ],
        ]
        ];
    }
}
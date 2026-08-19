<?php

namespace App\Models;

use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class WebsiteSetting extends BaseModel
{
    use Orbital;

    protected $fillable = [
        'name',
        'tagline',
        'description',
        'alamat',
        'telepon',
        'email',
        'logo',
        'favicon',
        'colors',
        'social',
        'footer_text',
    ];

    protected $casts = [
        'colors' => 'array',
        'social' => 'array',
    ];

    protected $attributes = [
        'name'        => 'Laravel',
        'tagline'     => 'Web Application',
        'description' => '',
        'alamat'      => '',
        'telepon'     => '',
        'email'       => '',
        'logo'        => '/favicon.svg',
        'favicon'     => '/favicon.ico',
        'colors'      => '{"primary":"#00288e","on_primary":"#ffffff","primary_container":"#1e40af","on_primary_container":"#a8b8ff","primary_fixed":"#dde1ff","primary_fixed_dim":"#b8c4ff","surface":"#f7f9fb","surface_container":"#eceef0"}',
        'social'      => '{"facebook":"","instagram":"","twitter":"","youtube":"","tiktok":""}',
        'footer_text' => '',
    ];

    public static function field_name(): string
    {
        return 'name';
    }

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('name')->default('Laravel');
        $table->string('tagline')->nullable();
        $table->text('description')->nullable();
        $table->text('alamat')->nullable();
        $table->string('telepon')->nullable();
        $table->string('email')->nullable();
        $table->string('logo')->nullable();
        $table->string('favicon')->nullable();
        $table->json('colors')->nullable();
        $table->json('social')->nullable();
        $table->text('footer_text')->nullable();
    }

    public static function getOrbitalDriver(): string
    {
        return 'json';
    }

    /**
     * Get merged settings: DB values override config defaults.
     */
    public static function merged(): array
    {
        $config = config('website');
        $saved = static::first();

        if (! $saved) {
            return $config;
        }

        // Merge colors
        $colors = array_merge($config['colors'] ?? [], $saved->colors ?? []);

        // Merge social
        $social = array_merge($config['social'] ?? [], $saved->social ?? []);

        return array_merge($config, $saved->toArray(), [
            'colors' => $colors,
            'social' => $social,
        ]);
    }

    /**
     * Get the primary color hex value.
     */
    public static function primaryColor(): string
    {
        $settings = static::merged();
        return $settings['colors']['primary'] ?? '#00288e';
    }

    /**
     * Resolve a stored file path to a public URL.
     *
     * Handles paths stored as:
     *  - "storage/website/..." (Storage::storeAs paths)
     *  - "/something" (public-relative paths)
     *  - "something" (other paths)
     */
    public static function fileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Storage::storeAs("public/...") returns "public/..." — map via symlink
        if (str_starts_with($path, 'public/')) {
            return '/storage/' . substr($path, 7); // strip "public/"
        }

        if (str_starts_with($path, 'storage/')) {
            return '/storage/' . substr($path, 8); // strip "storage/"
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }

    /**
     * Generate HSL variants from a hex color for Material Design 3 tonal palette.
     */
    public static function generatePalette(string $hex): array
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
                default: $h = 0;
            }
            $h = round($h * 60);
        }

        $s = round($s * 100);
        $l = round($l * 100);

        return [
            'primary'               => "hsl({$h}, {$s}%, {$l}%)",
            'on_primary'            => $l > 50 ? "hsl({$h}, 10%, 10%)" : "hsl({$h}, 10%, 98%)",
            'primary_container'     => "hsl({$h}, {$s}%, " . min($l + 25, 90) . "%)",
            'on_primary_container'  => $l > 50 ? "hsl({$h}, 30%, 15%)" : "hsl({$h}, 20%, 90%)",
            'primary_fixed'         => "hsl({$h}, {$s}%, " . min($l + 35, 95) . "%)",
            'primary_fixed_dim'     => "hsl({$h}, {$s}%, " . min($l + 30, 90) . "%)",
        ];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class PublicImageController extends Controller
{
    public function product(string $file)
    {
        abort_unless(preg_match('/^[a-f0-9-]{36}\.webp$/D', $file), 404);

        return $this->image('products/'.$file, 'image/webp');
    }

    public function sample(string $file)
    {
        abort_unless(in_array($file, ['cement.svg', 'brick.svg', 'steel.svg', 'sand.svg'], true), 404);

        return $this->image('samples/'.$file, 'image/svg+xml');
    }

    private function image(string $relative, string $type)
    {
        $path = Storage::disk('public')->path($relative);
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Content-Type' => $type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=3600', 'Content-Security-Policy' => "default-src 'none'; sandbox"]);
    }
}

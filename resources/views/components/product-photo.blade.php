@props(['image', 'alt' => 'تصویر محصول'])
@php
    // Only these exact, pre-existing demo records use generated illustrations.
    // Real seller uploads always retain their original storage path.
    $samples = ['samples/cement.svg' => 'cement', 'samples/steel.svg' => 'steel', 'samples/brick.svg' => 'brick', 'samples/sand.svg' => 'sand'];
    $sample = $samples[$image->path] ?? null;
    $src = $sample ? asset('images/materials/'.$sample.'.webp') : asset('storage/'.$image->path);
@endphp
<img src="{{ $src }}" alt="{{ $alt }}{{ $sample ? '؛ تصویر نمایشی، نه عکس کالای واقعی' : '' }}" loading="lazy" {{ $attributes }}>

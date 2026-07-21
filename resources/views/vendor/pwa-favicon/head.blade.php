@php
    use JeffersonGoncalves\PwaFavicon\PwaFavicon;
    use Illuminate\Support\Facades\Vite;

    $themeColor = $themeColor ?? PwaFavicon::themeColor();
    $manifestUrl = $manifestUrl ?? '/manifest.json';
    $themeColorId = $themeColorId ?? 'theme-color-meta';
    $title = $title ?? null;

    $appleSplashes = [
        ['file' => 'apple-splash-1290x2796.png', 'media' => '(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3)'],
        ['file' => 'apple-splash-1179x2556.png', 'media' => '(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3)'],
        ['file' => 'apple-splash-1170x2532.png', 'media' => '(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)'],
        ['file' => 'apple-splash-1284x2778.png', 'media' => '(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3)'],
        ['file' => 'apple-splash-1125x2436.png', 'media' => '(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)'],
        ['file' => 'apple-splash-1242x2688.png', 'media' => '(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)'],
        ['file' => 'apple-splash-828x1792.png', 'media' => '(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)'],
        ['file' => 'apple-splash-750x1334.png', 'media' => '(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)'],
        ['file' => 'apple-splash-1242x2208.png', 'media' => '(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)'],
        ['file' => 'apple-splash-2048x2732.png', 'media' => '(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)'],
        ['file' => 'apple-splash-1668x2388.png', 'media' => '(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)'],
        ['file' => 'apple-splash-1536x2048.png', 'media' => '(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)'],
    ];
@endphp
@foreach (PwaFavicon::iconHeadLinks() as $link)
    <link rel="{{ $link['rel'] }}" type="{{ $link['type'] }}" sizes="{{ $link['sizes'] }}" href="{{ $link['href'] }}">
@endforeach
@foreach (PwaFavicon::appleHeadLinks() as $link)
    <link rel="{{ $link['rel'] }}"@if (! empty($link['sizes'])) sizes="{{ $link['sizes'] }}"@endif href="{{ $link['href'] }}">
@endforeach
@foreach ($appleSplashes as $splash)
    <link rel="apple-touch-startup-image" media="{{ $splash['media'] }}" href="{{ Vite::asset('resources/favicon/'.$splash['file']) }}">
@endforeach
<link rel="manifest" href="{{ $manifestUrl }}">
@foreach (PwaFavicon::msApplicationMeta() as $meta)
    <meta name="{{ $meta['name'] }}" content="{{ $meta['content'] }}">
@endforeach
<meta name="theme-color"@if (! empty($themeColorId)) id="{{ $themeColorId }}"@endif content="{{ $themeColor }}">
@foreach (PwaFavicon::webAppMeta($title) as $meta)
    <meta name="{{ $meta['name'] }}" content="{{ $meta['content'] }}">
@endforeach

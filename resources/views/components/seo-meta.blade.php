@props(['seo' => null])

@php
    $seo = $seo instanceof \App\Support\Seo
        ? $seo
        : \App\Support\Seo::make(is_array($seo) ? $seo : []);
    $image = $seo->imageUrl();
@endphp

<title>{{ $seo->title }}</title>
<meta name="description" content="{{ $seo->description }}">
@if ($seo->keywords)
    <meta name="keywords" content="{{ $seo->keywords }}">
@endif
<meta name="robots" content="{{ $seo->robots() }}">
<link rel="canonical" href="{{ $seo->canonicalUrl() }}">

<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:url" content="{{ $seo->canonicalUrl() }}">
<meta property="og:site_name" content="{{ $seo->siteName() }}">
<meta property="og:locale" content="{{ $seo->locale }}">
@if ($image)
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:image:secure_url" content="{{ $image }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $seo->title }}">
@endif

<meta name="twitter:card" content="{{ $seo->twitterCard }}">
<meta name="twitter:title" content="{{ $seo->title }}">
<meta name="twitter:description" content="{{ $seo->description }}">
@if ($image)
    <meta name="twitter:image" content="{{ $image }}">
@endif

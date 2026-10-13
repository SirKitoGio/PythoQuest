<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#2A7C13">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="PhytoQuest">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/svg+xml" href="/icon.svg">
<link rel="apple-touch-icon" href="/icon.svg">
<meta name="csrf-token" content="{{ csrf_token() }}">
@if (config('broadcasting.default') === 'reverb')
    <meta name="reverb-config" content="{{ json_encode(['key' => config('broadcasting.connections.reverb.key')] + config('services.reverb_client')) }}">
@endif

<title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'PhytoQuest') }}</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Playfair+Display:ital,wght@1,700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])

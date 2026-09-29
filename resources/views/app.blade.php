<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Preppy — Turn knowledge into A* answers</title>
    <meta name="description" content="AI-powered exam performance platform for O Level, A Level and IGCSE.">
    <meta name="theme-color" content="#FAFBFF">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Archivo+Black&family=Archivo:wght@400;500;600;700&family=Handlee&family=DM+Mono:wght@400;500&family=Caveat:wght@500;600;700&family=Instrument+Serif:ital@0;1&display=swap">
    <script>
        (function(){try{var k='aistar-theme';var s=localStorage.getItem(k);var t=(s==='light'||s==='dark')?s:(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');var r=document.documentElement;if(t==='dark')r.classList.add('dark');r.style.colorScheme=t;}catch(e){}})();
    </script>
    @vite('resources/js/main.ts')
</head>
<body>
    <div id="app"></div>
</body>
</html>

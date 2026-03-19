<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name', 'nidito') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#F7F5F0] font-sans antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
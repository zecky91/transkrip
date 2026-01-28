<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai Siswa - Leger App</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    @livewireStyles
</head>

<body>
    {{ $slot }}
    @livewireScripts
</body>

</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('tittle', 'Document')</title>
</head>
<body>
    okey
    @include('include')
    
    @yield('content','content1')

    @for($i=0; $i < 10; $i++)
    {{ $i }}
    @endfor
    
    @yield('content2','content2')
</body>
</html>
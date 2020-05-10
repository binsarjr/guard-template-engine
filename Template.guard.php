<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    @php
        $ok = 'sip';
        $nama = 'binsar';
        $asal = 'belitung';
        $datas = ['satu','dua','tiga'];
    @endphp
    @foreach($datas as $data)
        <h1>{ $data }</h1>
    @empty
        <p>kagak ada</p>
    @endforeach

    @foreach($datas as $data)
        <h1>{ $data } okey</h1>
    @endforeach

    @for($i=0;$i<=10;$i++)
        <span>{ $i }</span>
    @endfor

    <p>Hai perkenalkan nama saya {$nama}, saya asli { $asal }</p>
</body>

</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
<section>
    <h2>Variable</h2>
    @php
        $nama='binsar';
    @endphp
    <p>Hai nama saya adalah {{ $nama }}</p>
</section>
<section>
    <h2>Foreach</h2>
    @php
        $datas=['satu','dua'];
    @endphp
    @foreach($datas as $data)
        {{ $data }}
    @endforeach
</section>
<section>
    <h2>Foreach with empty</h2>
    @php
        $datas=[];
    @endphp
    
    @foreach($datas as $data)
        {{ $data }}
    @empty
        Data nya kosong
    @endforeach
</section>
<section>
    <h2>For Loop</h2>
    @for($i=1;$i < 10;$i++)
        {{ $i }}
    @endfor
</section>
<section>
    <h2>while Loop</h2>
    @php
        $i=1;
    @endphp
    @while($i < 10)
        {{ $i }}
        @php
            $i++;
        @endphp
    @endwhile
</section>
<section>
    <h2>do While Loop</h2>
    @php
        $i=1;
    @endphp
    @do
        {{ $i }}
        @php
            $i++;
        @endphp
    @while($i < 10)
</section>
<section>
    <h2>percabangan if</h2>
    @php
        $no=1;
    @endphp
    @if($no==1)
    no-1
    @endif
</section>
<section>
    <h2>percabangan if/else</h2>
    @php
        $no=0;
    @endphp
    @if($no==1)
    no-1
    @else
    no bukan 1
    @endif
</section>
<section>
    <h2>percabangan if/elseif...elseif</h2>
    @php
        $no=2;
    @endphp
    @if($no==1)
    no-1
    @elseif($no==2)
    no-2
    @endif
</section>
<section>
    <h2>percabangan if/elseif...elseif/else</h2>
    @php
        $no=3;
    @endphp
    @if($no==1)
    no-1
    @elseif($no==3)
    no-2
    @else
    kondisi else
    @endif
</section>
</body>

</html>
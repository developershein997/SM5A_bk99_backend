<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <div>
        @foreach ($providers as $item)
            <div>
                 <span>{{ $item->provider}} </span>
                 &nbsp;
                 <span> {{ $item->game_list_status }} </span>
                 &nbsp;
                  <span> {{ $item->game_type}} </span>
                 &nbsp;
                  <a href="/admin/provider-index/{{ $item->id }}" > Status Change </a>
            </div>
        @endforeach

    </div>
</body>
</html>

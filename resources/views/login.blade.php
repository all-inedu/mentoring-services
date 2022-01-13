<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    @csrf
    <a target="_blank" href="{{ url('api/auth/login/google') }}" style="text-decoration: none"> <div id="my-signin2" >Google</div></a>
    <a target="_blank" href="{{ url('api/auth/login/linkedin') }}" style="text-decoration: none" > <div>Linked In</div></a>
    <a target="_blank" href="{{ url('api/auth/login/facebook') }}" style="text-decoration: none"><div>Facebook</div></a>

    <a href="{{ url('api/auth/login/apple') }}">
        <img src="{{ asset('Logo - SIWA - Logo-only - White@1x.png') }}" alt="">
    </a>
    <script>
        window.opener.location.reload();
    </script>
</body>
</html>
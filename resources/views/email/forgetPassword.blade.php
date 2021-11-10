<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
    </style>
</head>
<body>
<table style="width:100%;border-collapse:collapse;border:0;border-spacing:0;background:#ffffff;">
    <tr>
        <td align="center" style="padding:0;">
            <div class="header"><img src="{{ asset('img/new-logo-allin.png') }}" ></div>
        </td>
    </tr>
    <tr>
        <td>
            Hey!
        </td>
    </tr>
    <tr>
        <td>
            You are receiving this email because we received a password reset request for your account.
        </td>
    </tr>
    <tr>
        <td>
            {{-- <a href="{{ route('password.request', ['token' => $token]) }}"><button>Reset Password</button></a> --}}
            <a href="http://localhost:8080/reset/{{ $token }}"><button>Reset Password</button></a>
        </td>
    </tr>
    <tr>
        <td>
            If you did not request a password reset, no further action is required.
        </td>
    </tr>
    <tr>
        <td>
            Thanks,<br>
            All-in Eduspace Team
        </td>
    </tr>
</table>

</body>
</html>
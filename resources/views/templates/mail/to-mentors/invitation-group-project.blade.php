<!DOCTYPE html>
<html>

<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <style type="text/css">
        @media screen {
            @font-face {
                font-family: 'Lato';
                font-style: normal;
                font-weight: 400;
                src: local('Lato Regular'), local('Lato-Regular'), url(https://fonts.gstatic.com/s/lato/v11/qIIYRU-oROkIk8vfvxw6QvesZW2xOQ-xsNqO47m55DA.woff) format('woff');
            }

            @font-face {
                font-family: 'Lato';
                font-style: normal;
                font-weight: 700;
                src: local('Lato Bold'), local('Lato-Bold'), url(https://fonts.gstatic.com/s/lato/v11/qdgUG4U09HnJwhYI-uK18wLUuEpTyoUstqEm5AMlJo4.woff) format('woff');
            }

            @font-face {
                font-family: 'Lato';
                font-style: italic;
                font-weight: 400;
                src: local('Lato Italic'), local('Lato-Italic'), url(https://fonts.gstatic.com/s/lato/v11/RYyZNoeFgb0l7W3Vu1aSWOvvDin1pK8aKteLpeZ5c0A.woff) format('woff');
            }

            @font-face {
                font-family: 'Lato';
                font-style: italic;
                font-weight: 700;
                src: local('Lato Bold Italic'), local('Lato-BoldItalic'), url(https://fonts.gstatic.com/s/lato/v11/HkF_qI1x_noxlxhrhMQYELO3LdcAZYWl9Si6vvxL-qU.woff) format('woff');
            }
        }

        /* CLIENT-SPECIFIC STYLES */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
        }

        /* RESET STYLES */
        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        table {
            border-collapse: collapse !important;
        }

        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        /* iOS BLUE LINKS */
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* MOBILE STYLES */
        @media screen and (max-width:600px) {
            h1 {
                font-size: 32px !important;
                line-height: 32px !important;
            }
        }

        /* ANDROID CENTER FIX */
        div[style*="margin: 16px 0;"] {
            margin: 0 !important;
        }
        form button {
            background-color: #91B3FA;
            border: none;
            padding: 10px 30px;
            cursor: pointer;
            transition: all .2s ease-in-out;
        }
        form button + button {
            margin-left: 20px;
            background: transparent;
            text-decoration: underline;
            padding: 5px 20px !important;
        }
        form button:hover {
            background-color: #ff7c36;
        }
        form button + button:hover {
            background: transparent;
            color: #ff7c36;
        }
    </style>
</head>

<body style="background-color: #f4f4f4; margin: 0 !important; padding: 0 !important;">
    <!-- HIDDEN PREHEADER TEXT -->
    <div style="display: none; font-size: 1px; color: #fefefe; line-height: 1px; font-family: 'Lato', Helvetica, Arial, sans-serif; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;"> We're thrilled to have you here! Get ready to dive into your new account. </div>
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <!-- LOGO -->
        <tr>
            <td bgcolor="#ff7c36" align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td align="center" valign="top" style="padding: 40px 10px 40px 10px;"> </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#ff7c36" align="center" style="padding: 0px 10px 0px 10px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td bgcolor="#ffffff" align="center" valign="top" style="padding: 40px 20px 20px 20px; border-radius: 4px 4px 0px 0px; color: #111111; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 48px; font-weight: 400; letter-spacing: 4px; line-height: 48px;">
                            <h1 style="font-size: 48px; font-weight: 400; margin: 2;">Notification</h1> 
                            <img src="https://img.freepik.com/free-vector/team-leader-managing-project_1262-21430.jpg?t=st=1654499701~exp=1654500301~hmac=c8924cbac096a22b1bc5bf44bc824b0bce3c3fd9937485dbfa48e094c5ff5e91" width="200" height="120" style="display: block; border: 0px;" />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#f4f4f4" align="center" style="padding: 0px 10px 0px 10px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td bgcolor="#ffffff" align="center" style="padding: 20px 30px 10px 30px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 400; line-height: 25px;">
                            <p style="margin: 0;">Hi {{ ucwords($group_info['mentor_name']) }}!</p>
                            <p>Your mentee just created a new group project.</p>
                            <p>Take a look at the details below!</p>

                        </td>
                    </tr>
                    {{-- <tr>
                        <td bgcolor="#ffffff" align="center" style="padding: 20px 30px 10px 30px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 400;">
                            <p style="margin: 0;">Here take a look the detail of the project</p>
                        </td>
                    </tr> --}}
                    <tr>
                        <td bgcolor="#ffffff" align="center" style="padding: 20px 30px 40px 30px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 400; line-height: 25px;">
                            <div style="border: 1px solid #ccc;border-radius: 10px">
                            <table width="80%" border="0" cellspacing="0" cellpadding="0" style="margin: 0px auto;">
                                <tr valign="top">
                                    <td width="30%" align="center" style="padding:5px 10px;">Project Name</td>
                                    <td width="10%" align="center">:</td>
                                    <td width="50%" align="left">{{ $group_info['group_detail']['project_name'] }}</td>
                                </tr>
                                {{-- <tr>
                                    <td align="center" style="padding:5px 10px;">Project Type</td>
                                    <td align="center">:</td>
                                    <td align="left">{{ $group_info['group_detail']['project_type'] }}</td>
                                </tr> --}}
                                <tr valign="top">
                                    <td align="center" style="padding:5px 10px;">Project Desc</td>
                                    <td align="center">:</td>
                                    <td align="left">{!! $group_info['group_detail']['project_desc'] !!}</td>
                                </tr>
                                <tr valign="top">
                                    <td align="center" style="padding:5px 10px;">Created By</td>
                                    <td align="center">:</td>
                                    <td align="left">{{ $group_info['group_detail']['project_owner'] }}</td>
                                </tr>
                            </table>
                            </div>
                        </td>
                    </tr>
                    {{-- <tr align="center">
                        <td bgcolor="#FFFFFF" style="padding: 0px 30px 5px 30px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 400; line-height: 25px;">
                            <p>Click <a target="_blank" href="{{ $group_info['hyperlink'].$group_info['group_detail']['project_id'] }}">here</a> for more detail</p>
                        </td>
                    </tr> --}}
                    <tr>
                        <td bgcolor="#FFFFFF" style="height:10px;"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#f4f4f4" align="center" style="padding: 30px 10px 0px 10px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td bgcolor="#FFFFFF" align="center" style="padding: 30px 30px 30px 30px; border-radius: 4px 4px 4px 4px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 400; line-height: 25px;">
                            <h2 style="font-size: 20px; font-weight: 400; color: #111111; margin: 0;"><img src="{{ asset('img/logo-b.png') }}" style="width: 200px; height: auto;"></h2>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
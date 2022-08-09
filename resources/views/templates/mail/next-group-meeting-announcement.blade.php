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
            background-color: #588bf9;
        }
        form button + button:hover {
            background: transparent;
            color: #588bf9;
        }
    </style>
</head>

<body style="background-color: #f4f4f4; margin: 0 !important; padding: 0 !important;">
    <!-- HIDDEN PREHEADER TEXT -->
    <div style="display: none; font-size: 1px; color: #fefefe; line-height: 1px; font-family: 'Lato', Helvetica, Arial, sans-serif; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;"> We're thrilled to have you here! Get ready to dive into your new account. </div>
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <!-- LOGO -->
        <tr>
            <td bgcolor="#91B3FA" align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td align="center" valign="top" style="padding: 40px 10px 40px 10px;"> </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#91B3FA" align="center" style="padding: 0px 10px 0px 10px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td bgcolor="#ffffff" align="center" valign="top" style="padding: 40px 20px 20px 20px; border-radius: 4px 4px 0px 0px; color: #111111; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 48px; font-weight: 400; letter-spacing: 4px; line-height: 48px;">
                            <h1 style="font-size: 48px; font-weight: 400; margin: 2;">Reminder</h1> 
                            <img src="https://img.freepik.com/free-vector/coach-speaking-before-audience-mentor-presenting-charts-reports-employees-meeting-business-training-seminar-conference-vector-illustration-presentation-lecture-education_74855-8294.jpg?w=2000" width="200" height="120" style="display: block; border: 0px;" />
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
                            <p style="margin: 0;">Hi {{ $name }}!</p>
                            <p>You have a new group meeting</p>
                            <p>The meeting will take place at/on</p>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" align="center" style="padding: 20px 30px 40px 30px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 400; line-height: 25px;">
                            <div style="border: 1px solid #ccc; border-radius: 10px;">
                            <table width="80%" border="0" cellspacing="0" cellpadding="0" style="margin: 0px auto;">
                                <tr>
                                    <td width="30%" align="center">
                                        <img src="{{ asset('285670_calendar_icon.png') }}" width="90%" alt="" align="center">
                                    </td>
                                    <td style="padding:30px 20px;">
                                        <table cellpadding="3">
                                            <tr>
                                                <td>Group Project</td>
                                                <td>:</td>
                                                <td>{{ $group_info->project_name }}</td>
                                            </tr>
                                            <tr>
                                                <td align="right">Subject</td>
                                                <td>:</td>
                                                <td>{{ $meeting_detail->meeting_subject }}</td>
                                            </tr>
                                            <tr>
                                                <td>Next Meeting</td>
                                                <td>:</td>
                                                <td>{{ date('d F Y', strtotime($meeting_detail->meeting_date)) }}</td>
                                            </tr>
                                            <tr>
                                                <td align="right">Time</td>
                                                <td>:</td>
                                                <td>{{ date('H:i T', strtotime($meeting_detail->meeting_date)) }}</td>
                                            </tr>
                                            <tr>
                                                <td align="right">Location</td>
                                                <td>:</td>
                                                <td>{{ $meeting_detail->meeting_link }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFFFFF" style="height:10px;"></td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFFFFF" style="color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 400; line-height: 25px" align="center">
                            <p style="margin: 5px 40px 50px 40px">
                                Come prepared with any questions you have about the project. 
                                <br><br>
                                Please let us know if you can attend by clicking this <a href="{{ route('attend', ['person' => 'student', 'encrypted_data' => $token]) }}">link</a>
                                <br><br>
                                Thank you for your time, and we hope to see you on {{ date('F d', strtotime($meeting_detail->meeting_date)) }}.
                            </p>
                            
                        </td>
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
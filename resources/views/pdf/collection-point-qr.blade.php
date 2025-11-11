<!DOCTYPE html>
<html>

<head>
    <title>QR Code</title>
    <style>
        /* General body styling */
        body {
            font-family: sans-serif;
            text-align: center;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 20px;
        }

        /* Header section */
        .header {
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: bold;
            color: #000;
            margin: 0;
        }

        .header p {
            font-size: 16px;
            color: #555;
            margin-top: 5px;
            font-style: italic;
        }

        /* Main content section */
        .main-content {
            margin-bottom: 30px;
        }

        .main-content .cta {
            font-size: 20px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 15px;
        }

        .main-content .location-name {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0 5px 0;
        }

        .main-content .location-address {
            font-size: 14px;
            color: #444;
            margin: 0 0 20px 0;
        }

        /* QR Code container */
        .qr-code {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        /* Footer section */
        .footer {
            border-top: 1px solid #ccc;
            padding-top: 15px;
            margin-top: 30px;
            font-size: 11px;
            /* Slightly larger for ad readability */
            color: #555;
        }

        .footer p {
            margin: 5px 0;
            /* A bit more spacing */
        }

        /* --- NEW CSS FOR ADVERTISEMENT LINKS --- */
        /* This makes your links black and not underlined for a clean print look */
        .footer a {
            color: #000;
            /* Black text, not blue */
            text-decoration: none;
            /* No underline */
            /* The <strong> tag will handle the bolding */
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="header">
            <h1>SAFISITI</h1>
            <p>Keeping Our City Clean</p>
        </div>

        <div class="main-content">
            <h2 class="cta">SCAN ME TO PICK WASTE FROM HERE</h2>

            <h3 class="location-name">{{ $point->name }}</h3>
            <p class="location-address">{{ $point->address }}</p>

            <div class="qr-code">
                {!! $qr !!}
            </div>
        </div>

        <div class="footer">
            <p>FROM ToroDev ODA Under The Datacities Project.</p>
            <p>
                With Love From:
                <a href="http://www.opendata-analytics.org">
                    <strong>www.opendata-analytics.org</strong>
                </a>
            </p>
            <p>
                Powered By MOELS GROUP:
                <a href="http://www.moelsgroup.com">
                    <strong>www.moelsgroup.com</strong>
                </a>
            </p>
        </div>

    </div>

</body>

</html>
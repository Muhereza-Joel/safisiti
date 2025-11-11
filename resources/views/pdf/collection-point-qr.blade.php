<!DOCTYPE html>
<html>

<head>
    <title>QR Code</title>
    <style>
        body {
            text-align: center;
            font-family: sans-serif;
        }

        .qr {
            margin-top: 40px;
        }
    </style>
</head>

<body>
    <h2>{{ $point->name }} - QR Code</h2>
    <p>Scan to view this Collection Point</p>

    <div class="qr">
        {!! $qr !!}
    </div>

    <p style="margin-top: 20px;">{{ url("/dashboard/collection-points/" . $point->uuid) }}</p>
</body>

</html>
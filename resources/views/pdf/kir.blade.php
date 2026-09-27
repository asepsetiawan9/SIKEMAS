<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KIR - {{ $aset->lokasi }} - {{ $aset->kode_barang }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header h3 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            margin: 2px 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 9px;
            color: #475569;
        }
        .doc-title {
            text-align: center;
            margin-bottom: 16px;
        }
        .doc-title h4 {
            margin: 0;
            font-size: 13px;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .doc-title span {
            font-size: 10px;
            color: #64748b;
        }
        .meta-info {
            width: 100%;
            margin-bottom: 12px;
            font-size: 11px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .table-data th, .table-data td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
            font-size: 10px;
        }
        .table-data th {
            background-color: #f1f5f9;
            font-weight: 600;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-baik { background-color: #dcfce7; color: #15803d; }
        .badge-rusak_ringan { background-color: #fef3c7; color: #b45309; }
        .badge-rusak_berat { background-color: #fee2e2; color: #b91c1c; }
        .footer-signatures {
            margin-top: 30px;
            width: 100%;
        }
        .footer-signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signature-space {
            height: 60px;
        }
        .qr-section {
            float: right;
            text-align: center;
            margin-bottom: 10px;
        }
        .qr-section img {
            width: 80px;
            height: 80px;
            border: 1px solid #e2e8f0;
            padding: 3px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>Pemerintah {{ $settings['kabupaten'] ?? 'Kabupaten Garut' }}</h3>
        <h2>Kecamatan {{ $settings['nama_kecamatan'] ?? 'Caringin' }}</h2>
        <p>{{ $settings['alamat_kantor'] ?? 'Jl. Raya Caringin, Caringin, Garut, Jawa Barat' }}</p>
    </div>

    <div class="clearfix">
        @if (!empty($qrBase64))
            <div class="qr-section">
                <img src="data:image/png;base64,{{ $qrBase64 }}" alt="QR Code">
                <span style="display:block; font-size: 8px; color: #64748b; margin-top: 2px;">QR Aset</span>
            </div>
        @endif

        <div class="doc-title" style="text-align: left;">
            <h4>KARTU INVENTARIS RUANGAN (KIR)</h4>
            <span>Daftar Barang Inventaris Ruang Kerja Kantor Kecamatan Caringin</span>
        </div>
    </div>

    <table class="meta-info">
        <tr>
            <td style="width: 120px;"><strong>Lokasi / Ruangan</strong></td>
            <td style="width: 10px;">:</td>
            <td><strong>{{ $aset->lokasi }}</strong></td>
        </tr>
        <tr>
            <td><strong>Penanggung Jawab</strong></td>
            <td>:</td>
            <td>{{ $aset->penanggungJawab?->name ?? '-' }} (NIP: {{ $aset->penanggungJawab?->nip ?? '-' }})</td>
        </tr>
        <tr>
            <td><strong>Tahun Anggaran</strong></td>
            <td>:</td>
            <td>{{ $settings['tahun_anggaran_aktif'] ?? date('Y') }}</td>
        </tr>
    </table>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th>Kode Barang</th>
                <th>Nama Barang / Jenis</th>
                <th>Merk / Model</th>
                <th>No. Register</th>
                <th>Tahun</th>
                <th>Bahan / Ukuran</th>
                <th>Keadaan Barang</th>
                <th>Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td><strong>{{ $aset->kode_barang }}</strong></td>
                <td>{{ $aset->nama }}</td>
                <td>{{ $aset->merk_type ?? '-' }}</td>
                <td>{{ $aset->nomor_register ?? '-' }}</td>
                <td style="text-align: center;">{{ $aset->tahun_perolehan }}</td>
                <td>{{ $aset->bahan ?? '-' }} / {{ $aset->ukuran ?? '-' }}</td>
                <td style="text-align: center;">
                    @php
                        $kondisiVal = $aset->kondisi instanceof \BackedEnum ? $aset->kondisi->value : (string) $aset->kondisi;
                    @endphp
                    <span class="badge badge-{{ $kondisiVal }}">
                        {{ str_replace('_', ' ', strtoupper($kondisiVal)) }}
                    </span>
                </td>
                <td style="text-align: right;">{{ number_format((float) $aset->nilai, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="footer-signatures">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Penanggung Jawab Ruangan</strong>
                <div class="signature-space"></div>
                <strong><u>{{ $aset->penanggungJawab?->name ?? 'Penanggung Jawab' }}</u></strong><br>
                NIP. {{ $aset->penanggungJawab?->nip ?? '-' }}
            </td>
            <td>
                Caringin, {{ now()->isoFormat('D MMMM Y') }}<br>
                <strong>Pengurus Barang Pembantu</strong>
                <div class="signature-space"></div>
                <strong><u>Dra. Hj. Siti Fatimah</u></strong><br>
                NIP. 198205142006042011
            </td>
        </tr>
    </table>
</body>
</html>

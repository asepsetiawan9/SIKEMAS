<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KIB - {{ $aset->kode_barang }}</title>
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
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .table-data th, .table-data td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
        }
        .table-data th {
            background-color: #f1f5f9;
            text-align: left;
            font-weight: 600;
            width: 28%;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
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
            width: 85px;
            height: 85px;
            border: 1px solid #e2e8f0;
            padding: 3px;
        }
        .qr-section span {
            display: block;
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
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
                <span>Scan Verifikasi</span>
            </div>
        @endif

        <div class="doc-title" style="text-align: left;">
            <h4>KARTU INVENTARIS BARANG (KIB)</h4>
            <span>Dokumen Bukti Pengelolaan Barang Milik Daerah (BMD)</span>
        </div>
    </div>

    <table class="table-data">
        <tr>
            <th>Kode Barang</th>
            <td><strong>{{ $aset->kode_barang }}</strong></td>
        </tr>
        <tr>
            <th>Nama Barang</th>
            <td><strong>{{ $aset->nama }}</strong></td>
        </tr>
        <tr>
            <th>Merk / Tipe / Spesifikasi</th>
            <td>{{ $aset->merk_type ?? '-' }}</td>
        </tr>
        <tr>
            <th>Nomor Register</th>
            <td>{{ $aset->nomor_register ?? '-' }}</td>
        </tr>
        <tr>
            <th>Ukuran / Bahan</th>
            <td>{{ $aset->ukuran ?? '-' }} / {{ $aset->bahan ?? '-' }}</td>
        </tr>
        <tr>
            <th>Tahun Perolehan</th>
            <td>{{ $aset->tahun_perolehan }}</td>
        </tr>
        <tr>
            <th>Cara / Asal Perolehan</th>
            <td>{{ $aset->cara_perolehan ? ucfirst($aset->cara_perolehan->value) : '-' }}</td>
        </tr>
        <tr>
            <th>Nilai Perolehan (Rp)</th>
            <td><strong>Rp {{ number_format((float) $aset->nilai, 2, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <th>Kondisi Fisik</th>
            <td>
                @php
                    $kondisiVal = $aset->kondisi instanceof \BackedEnum ? $aset->kondisi->value : (string) $aset->kondisi;
                @endphp
                <span class="badge badge-{{ $kondisiVal }}">
                    {{ str_replace('_', ' ', strtoupper($kondisiVal)) }}
                </span>
            </td>
        </tr>
        <tr>
            <th>Lokasi Penempatan</th>
            <td>{{ $aset->lokasi }}</td>
        </tr>
        <tr>
            <th>Penanggung Jawab</th>
            <td>{{ $aset->penanggungJawab?->name ?? '-' }} (NIP: {{ $aset->penanggungJawab?->nip ?? '-' }})</td>
        </tr>
        <tr>
            <th>Verifikasi Fisik Terakhir</th>
            <td>{{ $aset->tanggal_verifikasi_fisik ? \Carbon\Carbon::parse($aset->tanggal_verifikasi_fisik)->isoFormat('D MMMM Y') : 'Belum diverifikasi' }}</td>
        </tr>
    </table>

    <table class="footer-signatures">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Camat Caringin</strong>
                <div class="signature-space"></div>
                <strong><u>{{ $settings['nama_camat'] ?? 'Drs. H. Asep Mulyana, M.Si.' }}</u></strong><br>
                NIP. {{ $settings['nip_camat'] ?? '197508121998031002' }}
            </td>
            <td>
                Caringin, {{ now()->isoFormat('D MMMM Y') }}<br>
                <strong>Pengurus Barang / Pengelola Aset</strong>
                <div class="signature-space"></div>
                <strong><u>{{ $aset->penanggungJawab?->name ?? 'Pengurus Barang' }}</u></strong><br>
                NIP. {{ $aset->penanggungJawab?->nip ?? '-' }}
            </td>
        </tr>
    </table>
</body>
</html>

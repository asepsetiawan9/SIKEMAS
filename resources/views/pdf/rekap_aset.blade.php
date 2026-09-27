<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Barang Milik Daerah (BMD) - Kecamatan Caringin</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            line-height: 1.35;
            margin: 0;
            padding: 15px;
        }
        .header {
            text-align: center;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .header h3 {
            margin: 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .header h2 {
            margin: 2px 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 0;
            font-size: 9px;
            color: #475569;
        }
        .title-section {
            text-align: center;
            margin-bottom: 14px;
        }
        .title-section h4 {
            margin: 0;
            font-size: 13px;
            text-decoration: underline;
            text-transform: uppercase;
            font-weight: bold;
        }
        .title-section p {
            margin: 2px 0 0;
            font-size: 9.5px;
            color: #475569;
        }
        .summary-boxes {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .summary-boxes td {
            width: 20%;
            padding: 6px 10px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        .summary-label {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }
        .summary-val {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-size: 9px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            color: #1e293b;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-baik { background-color: #dcfce7; color: #15803d; }
        .badge-rusak_ringan { background-color: #fef3c7; color: #b45309; }
        .badge-rusak_berat { background-color: #fee2e2; color: #b91c1c; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .signatures {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 10px;
        }
        .signature-space {
            height: 55px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>Pemerintah {{ $settings['kabupaten'] ?? 'Kabupaten Garut' }}</h3>
        <h2>Kecamatan {{ $settings['nama_kecamatan'] ?? 'Caringin' }}</h2>
        <p>{{ $settings['alamat_kantor'] ?? 'Jl. Raya Caringin No. XX, Kec. Caringin, Kab. Garut' }}</p>
    </div>

    <div class="title-section">
        <h4>Laporan Rekapitulasi Inventaris Barang Milik Daerah (BMD)</h4>
        <p>Status Per: {{ now()->translatedFormat('d F Y') }} | Lokasi: Seluruh Unit Kerja</p>
    </div>

    <table class="summary-boxes">
        <tr>
            <td>
                <div class="summary-label">Total Unit Aset</div>
                <div class="summary-val">{{ $summary['total_aset'] }} Unit</div>
            </td>
            <td>
                <div class="summary-label">Total Nilai BMD</div>
                <div class="summary-val">Rp {{ number_format($summary['total_nilai'], 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="summary-label">Kondisi Baik</div>
                <div class="summary-val" style="color: #16a34a;">{{ $summary['baik'] }} Unit</div>
            </td>
            <td>
                <div class="summary-label">Rusak Ringan</div>
                <div class="summary-val" style="color: #d97706;">{{ $summary['rusak_ringan'] }} Unit</div>
            </td>
            <td>
                <div class="summary-label">Rusak Berat (Kandidat)</div>
                <div class="summary-val" style="color: #dc2626;">{{ $summary['rusak_berat'] }} Unit</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 100px;">Kode Barang</th>
                <th>Nama Barang & Merk / Tipe</th>
                <th style="width: 45px;">Tahun</th>
                <th style="width: 80px;">Kondisi</th>
                <th style="width: 120px;">Ruangan / Lokasi</th>
                <th style="width: 95px;">Nilai Perolehan (Rp)</th>
                <th style="width: 110px;">Penanggung Jawab</th>
            </tr>
        </thead>
        <tbody>
            @forelse($asetList as $idx => $aset)
                @php
                    $kondisiVal = $aset->kondisi instanceof \BackedEnum ? $aset->kondisi->value : (string) $aset->kondisi;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $aset->kode_barang }}</td>
                    <td>
                        <strong>{{ $aset->nama }}</strong>
                        @if($aset->merk_type)
                            <br><span style="color: #64748b; font-size: 8.5px;">{{ $aset->merk_type }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $aset->tahun_perolehan }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $kondisiVal }}">
                            {{ str_replace('_', ' ', strtoupper($kondisiVal)) }}
                        </span>
                    </td>
                    <td>{{ $aset->lokasi }}</td>
                    <td class="text-right font-bold">Rp {{ number_format((float) $aset->nilai, 2, ',', '.') }}</td>
                    <td>{{ $aset->penanggungJawab?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data inventaris aset BMD.</td>
                </tr>
            @endforelse
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="6" class="text-center">TOTAL NILAI KESELURUHAN BMD</td>
                <td class="text-right">Rp {{ number_format($summary['total_nilai'], 2, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Camat Caringin</strong>
                <div class="signature-space"></div>
                <strong><u>{{ $settings['nama_camat'] ?? 'Drs. H. Asep Mulyana, M.Si.' }}</u></strong><br>
                NIP. {{ $settings['nip_camat'] ?? '197001011998011001' }}
            </td>
            <td>
                Caringin, {{ now()->translatedFormat('d F Y') }}<br>
                <strong>Pengurus Barang Pengguna</strong>
                <div class="signature-space"></div>
                <strong><u>{{ $settings['nama_pengurus_barang'] ?? 'Admin Umum / Kasubag Umum' }}</u></strong><br>
                NIP. {{ $settings['nip_pengurus_barang'] ?? '198501012010011001' }}
            </td>
        </tr>
    </table>
</body>
</html>

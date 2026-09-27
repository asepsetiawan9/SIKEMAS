<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Realisasi Keuangan - Kecamatan Caringin</title>
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
            width: 25%;
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
        <h4>Laporan Realisasi Anggaran Keuangan & SPJ</h4>
        <p>Tahun Anggaran {{ $activeYear }} | Periode: {{ $periodeText }}</p>
    </div>

    <table class="summary-boxes">
        <tr>
            <td>
                <div class="summary-label">Total Pagu Anggaran</div>
                <div class="summary-val">Rp {{ number_format($summary['total_pagu'], 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="summary-label">Total Realisasi SPJ</div>
                <div class="summary-val" style="color: #16a34a;">Rp {{ number_format($summary['total_realisasi'], 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="summary-label">Sisa Pagu Anggaran</div>
                <div class="summary-val" style="color: #2563eb;">Rp {{ number_format($summary['sisa_pagu'], 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="summary-label">Persentase Serapan</div>
                <div class="summary-val" style="color: {{ $summary['persen_realisasi'] > 80 ? '#d97706' : '#0f172a' }};">
                    {{ $summary['persen_realisasi'] }}%
                </div>
            </td>
        </tr>
    </table>

    <div style="font-weight: bold; font-size: 10px; margin-bottom: 5px; text-transform: uppercase;">
        I. Rekapitulasi Realisasi per Kegiatan Anggaran
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 75px;">Kode Rek.</th>
                <th>Nama Kegiatan</th>
                <th style="width: 130px;">Pengampu (Seksi)</th>
                <th style="width: 90px;">Pagu (Rp)</th>
                <th style="width: 90px;">Realisasi (Rp)</th>
                <th style="width: 90px;">Sisa Pagu (Rp)</th>
                <th style="width: 40px;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kegiatanList as $idx => $keg)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $keg['kode_rekening'] }}</td>
                    <td><strong>{{ $keg['nama'] }}</strong></td>
                    <td>{{ $keg['kasi_nama'] }} ({{ ucfirst($keg['seksi']) }})</td>
                    <td class="text-right">{{ number_format($keg['pagu'], 2, ',', '.') }}</td>
                    <td class="text-right" style="color: #16a34a; font-weight: 600;">{{ number_format($keg['realisasi'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($keg['sisa_pagu'], 2, ',', '.') }}</td>
                    <td class="text-center font-bold">{{ $keg['persen'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data kegiatan anggaran untuk filter ini.</td>
                </tr>
            @endforelse
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="4" class="text-center">TOTAL KESELURUHAN</td>
                <td class="text-right">Rp {{ number_format($summary['total_pagu'], 2, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($summary['total_realisasi'], 2, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($summary['sisa_pagu'], 2, ',', '.') }}</td>
                <td class="text-center">{{ $summary['persen_realisasi'] }}%</td>
            </tr>
        </tbody>
    </table>

    @if(count($spjList) > 0)
        <div style="font-weight: bold; font-size: 10px; margin-top: 10px; margin-bottom: 5px; text-transform: uppercase;">
            II. Daftar Berkas Pertanggungjawaban (SPJ) Terkait
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25px;">No</th>
                    <th style="width: 140px;">Nomor SPJ</th>
                    <th>Nama Kegiatan</th>
                    <th style="width: 70px;">Tanggal</th>
                    <th style="width: 90px;">Nominal (Rp)</th>
                    <th style="width: 80px;">Status</th>
                    <th style="width: 100px;">Diajukan Oleh</th>
                </tr>
            </thead>
            <tbody>
                @foreach($spjList as $idx => $spj)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td style="font-family: monospace;">{{ $spj->nomor_spj ?? 'Menunggu Penomoran' }}</td>
                        <td>{{ $spj->kegiatan?->nama ?? '-' }}</td>
                        <td class="text-center">{{ $spj->tanggal_pengajuan ? $spj->tanggal_pengajuan->format('d/m/Y') : '-' }}</td>
                        <td class="text-right font-bold">Rp {{ number_format((float) $spj->nominal, 2, ',', '.') }}</td>
                        <td class="text-center">
                            {{ strtoupper($spj->status instanceof \BackedEnum ? $spj->status->value : (string) $spj->status) }}
                        </td>
                        <td>{{ $spj->diajukanOleh?->name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

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
                <strong>Sekretaris Kecamatan Caringin</strong>
                <div class="signature-space"></div>
                <strong><u>{{ $settings['nama_sekmat'] ?? 'Sekretaris Kecamatan' }}</u></strong><br>
                NIP. {{ $settings['nip_sekmat'] ?? '197501012000011001' }}
            </td>
        </tr>
    </table>
</body>
</html>

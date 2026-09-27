<table>
    <thead>
        <tr>
            <th colspan="7" style="font-weight: bold; font-size: 14pt; text-align: center;">
                PEMERINTAH KABUPATEN GARUT - KECAMATAN CARINGIN
            </th>
        </tr>
        <tr>
            <th colspan="7" style="font-weight: bold; font-size: 12pt; text-align: center;">
                LAPORAN REALISASI ANGGARAN & SPJ KEUANGAN TAHUN {{ $activeYear }}
            </th>
        </tr>
        <tr>
            <th colspan="7" style="font-style: italic; font-size: 10pt; text-align: center;">
                Periode: {{ $periodeText }} | Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
            </th>
        </tr>
        <tr><th colspan="7"></th></tr>
        <tr style="background-color: #f1f5f9; font-weight: bold; text-align: center;">
            <th style="border: 1px solid #000000; font-weight: bold; width: 60px;">No</th>
            <th style="border: 1px solid #000000; font-weight: bold; width: 140px;">Kode Rekening</th>
            <th style="border: 1px solid #000000; font-weight: bold; width: 280px;">Nama Kegiatan</th>
            <th style="border: 1px solid #000000; font-weight: bold; width: 160px;">Seksi / Kasi Pengampu</th>
            <th style="border: 1px solid #000000; font-weight: bold; width: 140px;">Pagu Anggaran (Rp)</th>
            <th style="border: 1px solid #000000; font-weight: bold; width: 140px;">Realisasi (Rp)</th>
            <th style="border: 1px solid #000000; font-weight: bold; width: 140px;">Sisa Pagu (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalPagu = 0;
            $totalRealisasi = 0;
        @endphp
        @forelse($kegiatanList as $index => $keg)
            @php
                $totalPagu += $keg['pagu'];
                $totalRealisasi += $keg['realisasi'];
            @endphp
            <tr>
                <td style="border: 1px solid #cbd5e1; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: center;">{{ $keg['kode_rekening'] }}</td>
                <td style="border: 1px solid #cbd5e1;">{{ $keg['nama'] }}</td>
                <td style="border: 1px solid #cbd5e1;">{{ $keg['kasi_nama'] }} ({{ ucfirst($keg['seksi']) }})</td>
                <td style="border: 1px solid #cbd5e1; text-align: right;">{{ $keg['pagu'] }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: right;">{{ $keg['realisasi'] }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: right;">{{ $keg['sisa_pagu'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="border: 1px solid #cbd5e1; text-align: center;">Tidak ada data kegiatan anggaran untuk filter ini.</td>
            </tr>
        @endforelse
        <tr style="background-color: #e2e8f0; font-weight: bold;">
            <td colspan="4" style="border: 1px solid #000000; text-align: center; font-weight: bold;">TOTAL KESELURUHAN</td>
            <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ $totalPagu }}</td>
            <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ $totalRealisasi }}</td>
            <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ $totalPagu - $totalRealisasi }}</td>
        </tr>
        <tr><th colspan="7"></th></tr>
        <tr>
            <th colspan="7" style="font-weight: bold; font-size: 11pt;">RINCIAN DOKUMEN SPJ TERKAIT</th>
        </tr>
        <tr style="background-color: #f8fafc; font-weight: bold;">
            <th style="border: 1px solid #000000; font-weight: bold;">No</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Nomor SPJ</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Kegiatan</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Tanggal Pengajuan</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Status</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Nominal (Rp)</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Diajukan Oleh</th>
        </tr>
        @forelse($spjList as $idx => $spj)
            <tr>
                <td style="border: 1px solid #cbd5e1; text-align: center;">{{ $idx + 1 }}</td>
                <td style="border: 1px solid #cbd5e1;">{{ $spj->nomor_spj ?? '-' }}</td>
                <td style="border: 1px solid #cbd5e1;">{{ $spj->kegiatan?->nama ?? '-' }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: center;">{{ $spj->tanggal_pengajuan?->format('d/m/Y') ?? '-' }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: center;">{{ strtoupper($spj->status instanceof \BackedEnum ? $spj->status->value : (string) $spj->status) }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: right;">{{ $spj->nominal }}</td>
                <td style="border: 1px solid #cbd5e1;">{{ $spj->diajukanOleh?->name ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="border: 1px solid #cbd5e1; text-align: center;">Tidak ada rincian SPJ pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table>
    <thead>
        <tr>
            <th colspan="9" style="font-weight: bold; font-size: 14pt; text-align: center;">
                PEMERINTAH KABUPATEN GARUT - KECAMATAN CARINGIN
            </th>
        </tr>
        <tr>
            <th colspan="9" style="font-weight: bold; font-size: 12pt; text-align: center;">
                REKAPITULASI INVENTARIS BARANG MILIK DAERAH (BMD)
            </th>
        </tr>
        <tr>
            <th colspan="9" style="font-style: italic; font-size: 10pt; text-align: center;">
                Status Per: {{ now()->translatedFormat('d F Y') }} | Total Unit: {{ count($asetList) }}
            </th>
        </tr>
        <tr><th colspan="9"></th></tr>
        <tr style="background-color: #f1f5f9; font-weight: bold; text-align: center;">
            <th style="border: 1px solid #000000; font-weight: bold;">No</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Kode Barang</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Nama Barang</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Merk / Tipe</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Tahun</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Kondisi</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Lokasi</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Nilai Perolehan (Rp)</th>
            <th style="border: 1px solid #000000; font-weight: bold;">Penanggung Jawab</th>
        </tr>
    </thead>
    <tbody>
        @php $totalNilai = 0; @endphp
        @forelse($asetList as $index => $aset)
            @php $totalNilai += (float) $aset->nilai; @endphp
            <tr>
                <td style="border: 1px solid #cbd5e1; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: center;">{{ $aset->kode_barang }}</td>
                <td style="border: 1px solid #cbd5e1;">{{ $aset->nama }}</td>
                <td style="border: 1px solid #cbd5e1;">{{ $aset->merk_type ?? '-' }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: center;">{{ $aset->tahun_perolehan }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: center;">{{ strtoupper($aset->kondisi instanceof \BackedEnum ? $aset->kondisi->value : (string) $aset->kondisi) }}</td>
                <td style="border: 1px solid #cbd5e1;">{{ $aset->lokasi }}</td>
                <td style="border: 1px solid #cbd5e1; text-align: right;">{{ $aset->nilai }}</td>
                <td style="border: 1px solid #cbd5e1;">{{ $aset->penanggungJawab?->name ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" style="border: 1px solid #cbd5e1; text-align: center;">Tidak ada data aset BMD.</td>
            </tr>
        @endforelse
        <tr style="background-color: #e2e8f0; font-weight: bold;">
            <td colspan="7" style="border: 1px solid #000000; text-align: center; font-weight: bold;">TOTAL NILAI INVENTARIS BMD</td>
            <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ $totalNilai }}</td>
            <td style="border: 1px solid #000000;"></td>
        </tr>
    </tbody>
</table>

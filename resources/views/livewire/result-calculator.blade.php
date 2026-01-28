<div class="tab-content active">
    <div class="card">
        <div class="card-header">
            <h2>📊 Hasil Rata-rata Nilai</h2>
            <div class="card-actions">
                <input wire:model.live="search" type="text" placeholder="🔍 Cari siswa..." class="search-input">
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>NISN</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Sem 1</th>
                        <th>Sem 2</th>
                        <th>Sem 3</th>
                        <th>Sem 4</th>
                        <th>Sem 5</th>
                        <th>Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td>{{ $student->nisn }}</td>
                            <td>{{ $student->nama }}</td>
                            <td>{{ $student->kelas }}</td>
                            <td style="text-align: center;">{{ number_format($student->calculateAverage(1), 2) }}</td>
                            <td style="text-align: center;">{{ number_format($student->calculateAverage(2), 2) }}</td>
                            <td style="text-align: center;">{{ number_format($student->calculateAverage(3), 2) }}</td>
                            <td style="text-align: center;">{{ number_format($student->calculateAverage(4), 2) }}</td>
                            <td style="text-align: center;">
                                @php
                                    $sem5Akademik = $student->calculateAverage(5);
                                    $sem5Pkl = $student->calculatePklAverage();
                                    $sem5 = $sem5Pkl > 0 ? $sem5Pkl : $sem5Akademik;
                                    $isPkl = $sem5Pkl > 0;
                                @endphp
                                {{ number_format($sem5, 2) }}
                                @if($sem5 > 0)
                                    <span style="font-size: 0.8rem; margin-left: 2px;"
                                        title="{{ $isPkl ? 'Nilai PKL' : 'Nilai Akademik' }}">
                                        {{ $isPkl ? '🏭' : '📄' }}
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: center; font-weight: bold;">
                                {{ number_format($student->calculateOverallAverage(), 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty-message">Belum ada data hasil. Silakan import leger terlebih
                                dahulu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $students->links() }}
        </div>
    </div>
</div>
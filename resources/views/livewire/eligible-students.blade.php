<div class="tab-content active">
    <div class="card">
        <div class="card-header">
            <h2>🏆 Daftar Siswa Eligible</h2>
            <div class="card-actions">
                <button wire:click="export" class="btn btn-outline">
                    <span wire:loading.remove wire:target="export">📤 Export Excel</span>
                    <span wire:loading wire:target="export">⏳ Exporting...</span>
                </button>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="filter-program-eligible">Filter Program Keahlian:</label>
            <select wire:model.live="programFilter" style="max-width: 400px;">
                <option value="">-- Pilih Program Keahlian --</option>
                @foreach($allPrograms as $program)
                    <option value="{{ $program }}">{{ $program }}</option>
                @endforeach
            </select>
        </div>

        @if($programFilter)
            <div class="info-text" style="display: flex; gap: 30px; flex-wrap: wrap;">
                <p><strong>📚 Program:</strong> {{ $programFilter }}</p>
                <p><strong>🎯 Kuota Eligible:</strong> {{ $quota }} Siswa</p>
                <p><strong>👥 Total Siswa:</strong> {{ count($students) }} Siswa</p>
            </div>
        @else
            <div class="info-text" style="background: rgba(234, 179, 8, 0.1); border-color: var(--warning);">
                ⚠️ Pilih program keahlian untuk melihat ranking dan status eligibilitas.
            </div>
        @endif

        <div class="table-container" style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">Ranking</th>
                        <th>NISN</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Program</th>
                        <th style="text-align: center;">Sem 1</th>
                        <th style="text-align: center;">Sem 2</th>
                        <th style="text-align: center;">Sem 3</th>
                        <th style="text-align: center;">Sem 4</th>
                        <th style="text-align: center;">Sem 5</th>
                        <th style="text-align: center;">Rata-rata</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rank = 1; @endphp
                    @forelse($students as $student)
                        @php 
                            $isEligible = $programFilter && $rank <= $quota;
                            $bgClass = $isEligible ? 'background-color: rgba(16, 185, 129, 0.15);' : '';
                            $borderClass = $rank == $quota && $programFilter ? 'border-bottom: 3px solid var(--warning);' : '';
                        @endphp
                        <tr style="{{ $bgClass }} {{ $borderClass }}">
                            <td style="text-align: center; font-weight: bold; font-size: 1.1rem;">
                                @if($rank <= 3 && $programFilter)
                                    @if($rank == 1) 🥇
                                    @elseif($rank == 2) 🥈
                                    @else 🥉
                                    @endif
                                @else
                                    {{ $rank }}
                                @endif
                            </td>
                            <td>{{ $student->nisn }}</td>
                            <td>{{ $student->nama }}</td>
                            <td>{{ $student->kelas }}</td>
                            <td>{{ $student->program }}</td>
                            <td style="text-align: center;">{{ $student->sem1 > 0 ? number_format($student->sem1, 1) : '-' }}</td>
                            <td style="text-align: center;">{{ $student->sem2 > 0 ? number_format($student->sem2, 1) : '-' }}</td>
                            <td style="text-align: center;">{{ $student->sem3 > 0 ? number_format($student->sem3, 1) : '-' }}</td>
                            <td style="text-align: center;">{{ $student->sem4 > 0 ? number_format($student->sem4, 1) : '-' }}</td>
                            <td style="text-align: center;">
                                @if($student->sem5 > 0)
                                    {{ number_format($student->sem5, 1) }}
                                    <span style="font-size: 0.8rem; margin-left: 2px;" title="{{ $student->isPkl ? 'Nilai PKL' : 'Nilai Akademik' }}">
                                        {{ $student->isPkl ? '🏭' : '📄' }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td style="text-align: center; font-weight: bold; color: var(--primary);">
                                {{ number_format($student->average, 2) }}
                            </td>
                            <td style="text-align: center;">
                                @if($programFilter)
                                    @if($isEligible)
                                        <span style="color: var(--success); font-weight: bold;">✅ Eligible</span>
                                    @else
                                        <span style="color: var(--danger);">❌ Tidak</span>
                                    @endif
                                @else
                                    <span style="color: var(--muted);">-</span>
                                @endif
                            </td>
                        </tr>
                        @php $rank++; @endphp
                    @empty
                        <tr>
                            <td colspan="12" class="empty-message">
                                @if($programFilter)
                                    Tidak ada siswa di program {{ $programFilter }}.
                                @else
                                    Pilih program keahlian untuk melihat data siswa.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($programFilter && count($students) > 0)
            <div style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                <h4 style="margin-bottom: 10px;">📊 Ringkasan</h4>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div><strong>Eligible:</strong> {{ min($quota, count($students)) }} siswa</div>
                    <div><strong>Tidak Eligible:</strong> {{ max(0, count($students) - $quota) }} siswa</div>
                    <div><strong>Kuota Tersisa:</strong> {{ max(0, $quota - count($students)) }} slot</div>
                </div>
            </div>
        @endif
    </div>
</div>

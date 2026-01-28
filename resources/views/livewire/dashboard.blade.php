<div class="tab-content active">
    <!-- Actions Row -->
    <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h2 style="margin: 0;">📊 Dashboard</h2>
            <div style="display: flex; gap: 10px;">
                <button wire:click="exportBackup" class="btn btn-secondary">
                    <span wire:loading.remove wire:target="exportBackup">💾 Backup Semua Data</span>
                    <span wire:loading wire:target="exportBackup">⏳ Generating...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="card stat-card" style="text-align: center; padding: 25px;">
            <div style="font-size: 3rem; margin-bottom: 10px;">👥</div>
            <div style="font-size: 2.5rem; font-weight: bold; color: var(--primary);">{{ $totalStudents }}</div>
            <div style="color: var(--muted);">Total Siswa</div>
        </div>
        <div class="card stat-card" style="text-align: center; padding: 25px;">
            <div style="font-size: 3rem; margin-bottom: 10px;">📝</div>
            <div style="font-size: 2.5rem; font-weight: bold; color: var(--accent);">{{ $totalGrades }}</div>
            <div style="color: var(--muted);">Total Nilai</div>
        </div>
        <div class="card stat-card" style="text-align: center; padding: 25px;">
            <div style="font-size: 3rem; margin-bottom: 10px;">📊</div>
            <div style="font-size: 2.5rem; font-weight: bold; color: var(--success);">
                {{ number_format($overallAverage, 1) }}
            </div>
            <div style="color: var(--muted);">Rata-rata Keseluruhan</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
        <!-- Top 10 Students -->
        <div class="card">
            <h3 style="margin-bottom: 15px;">🏆 Top 10 Siswa</h3>
            <div class="table-container" style="max-height: 350px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Program</th>
                            <th>Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topStudents as $index => $student)
                            <tr>
                                <td style="font-weight: bold; color: {{ $index < 3 ? 'var(--warning)' : 'inherit' }};">
                                    {{ $index + 1 }}
                                </td>
                                <td>{{ $student->nama }}</td>
                                <td>{{ $student->program }}</td>
                                <td style="font-weight: bold;">{{ number_format($student->average, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-message">Belum ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Grade Distribution -->
        <div class="card">
            <h3 style="margin-bottom: 15px;">📈 Distribusi Nilai</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($gradeDistribution as $label => $count)
                    @php
                        $percentage = $totalGrades > 0 ? ($count / $totalGrades) * 100 : 0;
                        $color = match (true) {
                            str_starts_with($label, 'A') => 'var(--success)',
                            str_starts_with($label, 'B') => 'var(--accent)',
                            str_starts_with($label, 'C') => 'var(--warning)',
                            str_starts_with($label, 'D') => '#f97316',
                            default => 'var(--danger)',
                        };
                    @endphp
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>{{ $label }}</span>
                            <span style="font-weight: bold;">{{ $count }} ({{ number_format($percentage, 1) }}%)</span>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); border-radius: 4px; height: 20px; overflow: hidden;">
                            <div
                                style="background: {{ $color }}; height: 100%; width: {{ $percentage }}%; transition: width 0.3s;">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Students Per Program -->
        <div class="card">
            <h3 style="margin-bottom: 15px;">🎓 Siswa per Program</h3>
            <div class="table-container" style="max-height: 300px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studentsPerProgram as $row)
                            <tr>
                                <td>{{ $row->program ?: '(Belum diisi)' }}</td>
                                <td style="font-weight: bold;">{{ $row->count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="empty-message">Belum ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Grades Per Semester -->
        <div class="card">
            <h3 style="margin-bottom: 15px;">📅 Data per Semester</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Tipe</th>
                            <th>Jumlah Nilai</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gradesPerSemester as $sem)
                            <tr>
                                <td>Semester {{ $sem->semester_number }}</td>
                                <td>{{ ucfirst($sem->type) }}</td>
                                <td style="font-weight: bold;">{{ $sem->grades_count }}</td>
                                <td>
                                    @if($sem->is_locked)
                                        <span style="color: var(--danger);">🔒 Terkunci</span>
                                    @else
                                        <span style="color: var(--success);">🔓 Terbuka</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-message">Belum ada data semester</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
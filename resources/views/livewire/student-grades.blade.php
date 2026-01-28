<div>
    <div class="student-grades-container">
        <div class="student-header">
            <div class="student-info">
                <h1>📊 Nilai Akademik</h1>
                <div class="student-bio">
                    <p><strong>Nama:</strong> {{ $student->nama }}</p>
                    <p><strong>NISN:</strong> {{ $student->nisn }}</p>
                    <p><strong>Kelas:</strong> {{ $student->kelas ?? '-' }}</p>
                    <p><strong>Program:</strong> {{ $student->program ?? '-' }}</p>
                </div>
            </div>
            <button wire:click="logout" class="btn btn-secondary">
                🚪 Keluar
            </button>
        </div>

        @if(count($grades) > 0)
            <div class="grades-grid">
                @foreach($grades as $semesterId => $data)
                    <div class="semester-card">
                        <div class="semester-header">
                            <h3>
                                Semester {{ $data['semester']->semester_number }}
                                @if($data['semester']->type === 'pkl')
                                    <span class="badge-pkl">PKL</span>
                                @endif
                            </h3>
                            <div class="semester-average">
                                Rata-rata: <strong>{{ number_format($data['average'], 2) }}</strong>
                            </div>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Mata Pelajaran</th>
                                        <th>Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['grades'] as $grade)
                                        <tr>
                                            <td>{{ $grade->subject_name }}</td>
                                            <td style="text-align: center;">
                                                <span
                                                    class="{{ $grade->value >= 90 ? 'grade-excellent' : ($grade->value >= 80 ? 'grade-good' : ($grade->value >= 70 ? 'grade-average' : 'grade-poor')) }}">
                                                    {{ number_format($grade->value, 0) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="overall-summary">
                <h3>📈 Ringkasan Keseluruhan</h3>
                <p>Rata-rata Total: <strong>{{ number_format($student->calculateOverallAverage(), 2) }}</strong></p>
            </div>
        @else
            <div class="empty-state">
                <p>📭 Belum ada data nilai untuk NISN ini.</p>
                <p>Hubungi Admin jika Anda merasa ini adalah kesalahan.</p>
            </div>
        @endif
    </div>

    <style>
        .student-grades-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: var(--bg-card);
            padding: 25px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .student-info h1 {
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .student-bio {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .student-bio p {
            margin: 0;
            color: var(--text-secondary);
        }

        .grades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .semester-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--border-color);
        }

        .semester-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .semester-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-pkl {
            background: linear-gradient(135deg, var(--warning) 0%, #f59e0b 100%);
            color: #1a1a2e;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .semester-average {
            color: var(--text-secondary);
        }

        .semester-average strong {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .overall-summary {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);
            border: 1px solid var(--primary);
            border-radius: var(--radius);
            padding: 25px;
            text-align: center;
        }

        .overall-summary h3 {
            margin-bottom: 10px;
        }

        .overall-summary strong {
            font-size: 2rem;
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            background: var(--bg-card);
            border-radius: var(--radius);
            color: var(--text-secondary);
        }

        .empty-state p:first-child {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
    </style>
</div>
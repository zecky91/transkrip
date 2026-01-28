<div class="tab-content active">
    <div class="card">
        <h2>📂 Upload File Leger</h2>
        <p class="info-text">Upload file leger untuk setiap semester. Format: Excel (.xlsx, .xls, .csv)</p>

        @if (session()->has('message'))
            <div class="info-text"
                style="background: rgba(16, 185, 129, 0.1); border-color: var(--success); color: var(--success);">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="info-text"
                style="background: rgba(239, 68, 68, 0.1); border-color: var(--danger); color: var(--danger);">
                {{ session('error') }}
            </div>
        @endif

        <div class="template-download">
            <p class="template-label">📋 Download Template Leger:</p>
            <div class="template-buttons">
                <button wire:click="downloadTemplate(1)" class="btn btn-outline btn-template">Sem 1 (X)</button>
                <button wire:click="downloadTemplate(2)" class="btn btn-outline btn-template">Sem 2 (X)</button>
                <button wire:click="downloadTemplate(3)" class="btn btn-outline btn-template">Sem 3 (XI)</button>
                <button wire:click="downloadTemplate(4)" class="btn btn-outline btn-template">Sem 4 (XI)</button>
                <button wire:click="downloadTemplate(5)" class="btn btn-outline btn-template">Sem 5 (XII)</button>
                <button wire:click="downloadTemplate(5, 'pkl')" class="btn btn-outline btn-template">Sem 5
                    (PKL)</button>
            </div>
        </div>

        <div class="upload-grid">
            @foreach([1, 2, 3, 4, 5] as $sem)
                @php
                    $key = $sem . '_academic';
                    $isLocked = $semesters[$key]['is_locked'] ?? false;
                @endphp
                <div class="upload-card {{ $isLocked ? 'locked' : '' }}">
                    <div class="upload-icon">📄</div>
                    <h3>Semester {{ $sem }}</h3>
                    <p class="kelas-info">
                        @if($sem <= 2) Kelas X
                        @elseif($sem <= 4) Kelas XI
                        @else Kelas XII (Akademik)
                        @endif
                    </p>

                    @if(Auth::user()->role === 'admin')
                        @if(!$isLocked)
                            <input type="file" wire:model="files.{{ $key }}" id="file-{{ $key }}" hidden>
                            <button onclick="document.getElementById('file-{{ $key }}').click()" class="btn btn-upload">
                                <span wire:loading.remove wire:target="files.{{ $key }}">Pilih File</span>
                                <span wire:loading wire:target="files.{{ $key }}">Uploading...</span>
                            </button>
                        @else
                            <button class="btn btn-upload" disabled style="opacity: 0.5; cursor: not-allowed;">Terkunci</button>
                        @endif
                    @else
                        <span style="color: var(--text-secondary); padding: 10px;">Lihat Saja</span>
                    @endif

                    <div class="upload-status {{ isset($uploadStatus[$key]) ? 'success' : '' }}">
                        @if(isset($uploadStatus[$key]))
                            {!! $uploadStatus[$key] !!}
                        @endif
                        @if(!empty($skippedData[$key]))
                            <br>
                            <button wire:click="downloadSkippedLog({{ $sem }}, 'academic')" class="btn btn-small btn-danger"
                                style="margin-top: 5px; font-size: 0.8rem;">
                                📥 Download Log
                            </button>
                            <button wire:click="viewSkippedLog({{ $sem }}, 'academic')" class="btn btn-small btn-info"
                                style="margin-top: 5px; font-size: 0.8rem;">
                                👁️ Lihat Log
                            </button>
                        @endif
                        @error("files.$key") <span style="color: var(--danger)">{{ $message }}</span> @enderror
                    </div>

                    @if(Auth::user()->role === 'admin')
                    <button wire:click="toggleLock({{ $sem }}, 'academic')" class="btn btn-small btn-lock">
                        {{ $isLocked ? '🔓 Buka Kunci' : '🔒 Kunci' }}
                    </button>
                    @endif
                    <button wire:click="loadSemesterData({{ $sem }}, 'academic')"
                        class="btn btn-small btn-secondary btn-view">
                        <span wire:loading.remove wire:target="loadSemesterData({{ $sem }}, 'academic')">👁️ Lihat
                            Data</span>
                        <span wire:loading wire:target="loadSemesterData({{ $sem }}, 'academic')">⏳ Loading...</span>
                    </button>
                    <button wire:click="checkMissing({{ $sem }}, 'academic')" class="btn btn-small btn-warning"
                        style="width: 100%; margin-top: 5px;">⚠️ Cek Belum Nilai</button>
                </div>
            @endforeach

            <!-- PKL Card -->
            @php
                $keyPKL = '5_pkl';
                $isLockedPKL = $semesters[$keyPKL]['is_locked'] ?? false;
            @endphp
            <div class="upload-card {{ $isLockedPKL ? 'locked' : '' }}">
                <div class="upload-icon">🏭</div>
                <h3>Semester 5 PKL</h3>
                <p class="kelas-info">Kelas XII (PKL)</p>

                @if(Auth::user()->role === 'admin')
                    @if(!$isLockedPKL)
                        <input type="file" wire:model="files.{{ $keyPKL }}" id="file-{{ $keyPKL }}" hidden>
                        <button onclick="document.getElementById('file-{{ $keyPKL }}').click()" class="btn btn-upload">
                            <span wire:loading.remove wire:target="files.{{ $keyPKL }}">Pilih File</span>
                            <span wire:loading wire:target="files.{{ $keyPKL }}">Uploading...</span>
                        </button>
                    @else
                        <button class="btn btn-upload" disabled style="opacity: 0.5; cursor: not-allowed;">Terkunci</button>
                    @endif
                @else
                    <span style="color: var(--text-secondary); padding: 10px;">Lihat Saja</span>
                @endif

                <div class="upload-status {{ isset($uploadStatus[$keyPKL]) ? 'success' : '' }}">
                    @if(isset($uploadStatus[$keyPKL]))
                        {!! $uploadStatus[$keyPKL] !!}
                    @endif
                    @if(!empty($skippedData[$keyPKL]))
                        <br>
                        <button wire:click="downloadSkippedLog(5, 'pkl')" class="btn btn-small btn-danger"
                            style="margin-top: 5px; font-size: 0.8rem;">
                            📥 Download Log
                        </button>
                        <button wire:click="viewSkippedLog(5, 'pkl')" class="btn btn-small btn-info"
                            style="margin-top: 5px; font-size: 0.8rem;">
                            👁️ Lihat Log
                        </button>
                    @endif
                    @error("files.$keyPKL") <span style="color: var(--danger)">{{ $message }}</span> @enderror
                </div>

                @if(Auth::user()->role === 'admin')
                <button wire:click="toggleLock(5, 'pkl')" class="btn btn-small btn-lock">
                    {{ $isLockedPKL ? '🔓 Buka Kunci' : '🔒 Kunci' }}
                </button>
                @endif
                <button wire:click="loadSemesterData(5, 'pkl')" class="btn btn-small btn-secondary btn-view">
                    <span wire:loading.remove wire:target="loadSemesterData(5, 'pkl')">👁️ Lihat Data</span>
                    <span wire:loading wire:target="loadSemesterData(5, 'pkl')">⏳ Loading...</span>
                </button>
                <button wire:click="checkMissing(5, 'pkl')" class="btn btn-small btn-warning"
                    style="width: 100%; margin-top: 5px;">⚠️ Cek Belum Nilai</button>
            </div>
        </div>
    </div>

    <!-- Modal View Data -->
    @if($showViewModal)
        <div class="modal show" style="display: flex;">
            <div class="modal-content large-modal">
                <div class="modal-header">
                    <h3>Data Leger Semester {{ $viewSemester->semester_number }} ({{ $viewSemester->type }})</h3>
                    <button wire:click="$set('showViewModal', false)" class="modal-close">&times;</button>
                </div>
                <div class="table-container" style="max-height: 500px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Rata-rata</th>
                                <th>Jml Mapel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($viewData as $data)
                                <tr>
                                    <td>{{ $data['nisn'] }}</td>
                                    <td>{{ $data['nama'] }}</td>
                                    <td>{{ number_format($data['average'], 2) }}</td>
                                    <td>{{ $data['count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty-message">Tidak ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Missing Students -->
    @if($showMissingModal)
        <div class="modal show" style="display: flex;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>⚠️ Siswa Belum Ada Nilai</h3>
                    <button wire:click="$set('showMissingModal', false)" class="modal-close">&times;</button>
                </div>
                <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($missingStudents as $student)
                                <tr>
                                    <td>{{ $student->nisn }}</td>
                                    <td>{{ $student->nama }}</td>
                                    <td>{{ $student->kelas }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--success); padding: 20px;">
                                        ✅ Semua siswa sudah memiliki nilai!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Skipped Data -->
    @if($showSkippedModal)
        <div class="modal show" style="display: flex;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>⚠️ Log Siswa Dilewati</h3>
                    <button wire:click="$set('showSkippedModal', false)" class="modal-close">&times;</button>
                </div>
                <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($viewSkippedData as $data)
                                <tr style="{{ ($data['type'] ?? 'ERROR') === 'ERROR' ? 'background: rgba(239, 68, 68, 0.1);' : (($data['type'] ?? 'ERROR') === 'WARNING' ? 'background: rgba(245, 158, 11, 0.1);' : 'background: rgba(6, 182, 212, 0.1);') }}">
                                    <td style="font-weight: bold; color: {{ ($data['type'] ?? 'ERROR') === 'ERROR' ? 'var(--danger)' : (($data['type'] ?? 'ERROR') === 'WARNING' ? 'var(--warning)' : 'var(--accent)') }}">
                                        {{ $data['type'] ?? 'ERROR' }}
                                    </td>
                                    <td>{{ $data['nisn'] }}</td>
                                    <td>{{ $data['nama'] }}</td>
                                    <td>{{ $data['reason'] ?? 'Error' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty-message">Tidak ada data log.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Preview Import -->
    @if($showPreviewModal)
        <div class="modal show" style="display: flex;">
            <div class="modal-content large-modal">
                <div class="modal-header">
                    <h3>🔍 Preview Import Semester {{ $previewData['semesterNumber'] }} ({{ $previewData['type'] }})</h3>
                    <button wire:click="cancelImport" class="modal-close">&times;</button>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; background: rgba(99, 102, 241, 0.1); border-radius: var(--radius-sm);">
                    <h4 style="margin-bottom: 10px;">Opsi Import:</h4>
                    <div style="display: flex; gap: 20px;">
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="radio" wire:model="emptyValueAction" value="ignore">
                            <span>Abaikan Nilai Kosong</span>
                        </label>
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="radio" wire:model="emptyValueAction" value="zero">
                            <span>Anggap Nilai 0</span>
                        </label>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 5px;">
                        * Total baris terdeteksi: {{ $previewData['totalRows'] }} (Menampilkan 50 baris pertama)
                    </p>
                </div>

                <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                @foreach($previewData['subjects'] as $subject)
                                    <th>{{ $subject }}</th>
                                @endforeach
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($previewData['rows'] as $row)
                                <tr style="{{ $row['status'] === 'error' ? 'background: rgba(239, 68, 68, 0.1);' : ($row['status'] === 'warning' ? 'background: rgba(245, 158, 11, 0.1);' : '') }}">
                                    <td style="text-align: center;">
                                        @if($row['status'] === 'valid') ✅
                                        @elseif($row['status'] === 'warning') ⚠️
                                        @else ❌
                                        @endif
                                    </td>
                                    <td>{{ $row['nisn'] }}</td>
                                    <td>{{ $row['nama'] }}</td>
                                    @foreach($previewData['subjects'] as $subject)
                                        <td style="text-align: center;">
                                            {{ $row['grades'][$subject] ?? '-' }}
                                        </td>
                                    @endforeach
                                    <td style="font-size: 0.85rem; color: {{ $row['status'] === 'error' ? 'var(--danger)' : 'var(--warning)' }}">
                                        {{ $row['notes'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($previewData['subjects']) + 4 }}" class="empty-message">Tidak ada data preview.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="modal-actions">
                    <button wire:click="cancelImport" class="btn btn-secondary">Batal</button>
                    <button wire:click="confirmImport" class="btn btn-primary">
                        💾 Proses Import
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
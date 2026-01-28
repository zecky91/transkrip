<div class="tab-content active">
    @if(Auth::user()->role === 'admin')
        <div class="card">
            <h2>➕ Tambah Data Siswa</h2>
            <form wire:submit.prevent="store" class="form-siswa">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nisn">NISN</label>
                        <input wire:model="nisn" type="text" id="nisn" placeholder="Masukkan NISN" required>
                        @error('nisn') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input wire:model="nama" type="text" id="nama" placeholder="Masukkan nama lengkap" required>
                        @error('nama') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="kelas">Kelas</label>
                        <select wire:model="kelas" id="kelas" required>
                            <option value="">Pilih Kelas</option>
                            <option value="X">Kelas X</option>
                            <option value="XI">Kelas XI</option>
                            <option value="XII">Kelas XII</option>
                        </select>
                        @error('kelas') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="program">Program Keahlian</label>
                        <input wire:model="program" type="text" id="program" placeholder="Contoh: RPL, TKJ, MM" required>
                        @error('program') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Simpan</button>
                    <button type="button" onclick="document.getElementById('file-master').click()"
                        class="btn btn-secondary">📥 Import dari Excel</button>
                    <button type="button" wire:click="downloadTemplate" class="btn btn-outline">📋 Download
                        Template</button>
                    <input type="file" wire:model="file" id="file-master" accept=".xlsx,.xls,.csv" hidden>
                </div>
                <div wire:loading wire:target="file">
                    <span style="color: var(--accent); margin-top: 10px; display: block;">Sedang mengupload &
                        import...</span>
                </div>
            </form>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2>📋 Daftar Siswa</h2>
            <div class="card-actions">
                <input wire:model.live="search" type="text" placeholder="🔍 Cari siswa..." class="search-input">
                @if(count($selectedStudents) > 0 && Auth::user()->role === 'admin')
                    <button wire:click="confirmDeleteSelected" class="btn btn-danger btn-small">🗑️ Hapus Terpilih
                        ({{ count($selectedStudents) }})</button>
                @endif
                <button wire:click="export" class="btn btn-outline">📤 Export</button>
            </div>
        </div>

        @if (session()->has('message'))
            <div class="info-text"
                style="background: rgba(16, 185, 129, 0.1); border-color: var(--success); color: var(--success);">
                {{ session('message') }}
            </div>
        @endif

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" wire:model.live="selectAll"></th>
                        <th>NISN</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Program Keahlian</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" wire:model.live="selectedStudents" value="{{ $student->nisn }}">
                            </td>
                            <td>{{ $student->nisn }}</td>
                            <td>{{ $student->nama }}</td>
                            <td>{{ $student->kelas }}</td>
                            <td>{{ $student->program }}</td>
                            <td style="text-align: center;">
                                @if(Auth::user()->role === 'admin')
                                    <button wire:click="edit('{{ $student->nisn }}')"
                                        class="btn btn-small btn-secondary">✏️</button>
                                    <button wire:click="confirmDelete('{{ $student->nisn }}', '{{ $student->nama }}')"
                                        class="btn btn-small btn-danger">🗑️</button>
                                @else
                                    <span style="color: var(--text-secondary);">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-message">Belum ada data siswa. Silakan tambah data atau import dari
                                Excel.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $students->links() }}
        </div>
    </div>

    <!-- Modal Edit Siswa -->
    @if($showFormModal && $isEditMode)
        <div class="modal show" style="display: flex;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>✏️ Edit Data Siswa</h3>
                    <button wire:click="$set('showFormModal', false)" class="modal-close">&times;</button>
                </div>
                <form wire:submit.prevent="update">
                    <div class="form-group">
                        <label for="edit-nisn">NISN</label>
                        <input wire:model="nisn" type="text" id="edit-nisn" readonly style="background: rgba(0,0,0,0.2);">
                    </div>
                    <div class="form-group">
                        <label for="edit-nama">Nama Lengkap</label>
                        <input wire:model="nama" type="text" id="edit-nama" required>
                        @error('nama') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit-kelas">Kelas</label>
                        <select wire:model="kelas" id="edit-kelas" required>
                            <option value="X">Kelas X</option>
                            <option value="XI">Kelas XI</option>
                            <option value="XII">Kelas XII</option>
                        </select>
                        @error('kelas') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit-program">Program Keahlian</label>
                        <input wire:model="program" type="text" id="edit-program" required>
                        @error('program') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="modal-actions">
                        <button type="button" wire:click="$set('showFormModal', false)"
                            class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary">💾 Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Konfirmasi Hapus Siswa -->
    @if($showDeleteModal)
        <div class="modal show" style="display: flex;">
            <div class="modal-content" style="max-width: 400px;">
                <div class="modal-header">
                    <h3>⚠️ Konfirmasi Hapus</h3>
                    <button wire:click="$set('showDeleteModal', false)" class="modal-close">&times;</button>
                </div>
                <div style="padding: 20px; text-align: center;">
                    <p style="font-size: 1.1rem; margin-bottom: 15px;">Anda yakin ingin menghapus siswa:</p>
                    <p style="font-weight: bold; font-size: 1.2rem; color: var(--primary);">{{ $deleteName }}</p>
                    <p style="color: var(--muted); font-size: 0.9rem;">NISN: {{ $deleteNisn }}</p>
                    <p style="color: var(--danger); font-size: 0.85rem; margin-top: 15px;">
                        ⚠️ Data nilai siswa ini juga akan terhapus!
                    </p>
                </div>
                <div class="modal-actions" style="padding: 15px; display: flex; gap: 10px; justify-content: center;">
                    <button wire:click="$set('showDeleteModal', false)" class="btn btn-secondary">Batal</button>
                    <button wire:click="delete" class="btn btn-danger">🗑️ Ya, Hapus</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Konfirmasi Hapus Terpilih -->
    @if($showDeleteSelectedModal)
        <div class="modal show" style="display: flex;">
            <div class="modal-content" style="max-width: 400px;">
                <div class="modal-header">
                    <h3>⚠️ Konfirmasi Hapus</h3>
                    <button wire:click="$set('showDeleteSelectedModal', false)" class="modal-close">&times;</button>
                </div>
                <div style="padding: 20px; text-align: center;">
                    <p style="font-size: 1.1rem; margin-bottom: 15px;">Anda yakin ingin menghapus:</p>
                    <p style="font-weight: bold; font-size: 1.5rem; color: var(--danger);">{{ count($selectedStudents) }}
                        siswa</p>
                    <p style="color: var(--danger); font-size: 0.85rem; margin-top: 15px;">
                        ⚠️ Data nilai siswa juga akan terhapus!
                    </p>
                </div>
                <div class="modal-actions" style="padding: 15px; display: flex; gap: 10px; justify-content: center;">
                    <button wire:click="$set('showDeleteSelectedModal', false)" class="btn btn-secondary">Batal</button>
                    <button wire:click="deleteSelected" class="btn btn-danger">🗑️ Ya, Hapus Semua</button>
                </div>
            </div>
        </div>
    @endif
</div>
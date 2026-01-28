<div>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🎓 Leger App</h1>
                <p>Sistem Informasi Nilai Akademik</p>
            </div>

            <div class="login-tabs">
                <button wire:click="switchType('admin')" class="login-tab {{ $loginType === 'admin' ? 'active' : '' }}">
                    🔐 Admin
                </button>
                <button wire:click="switchType('student')"
                    class="login-tab {{ $loginType === 'student' ? 'active' : '' }}">
                    👨‍🎓 Siswa
                </button>
            </div>

            @if($errorMessage)
                <div class="error-message">
                    {{ $errorMessage }}
                </div>
            @endif

            @if($loginType === 'admin')
                <form wire:submit.prevent="loginAdmin" class="login-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" wire:model="email" placeholder="admin@leger.app" required>
                        @error('email') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" wire:model="password" placeholder="••••••••" required>
                        @error('password') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                        <span wire:loading.remove wire:target="loginAdmin">Masuk sebagai Admin</span>
                        <span wire:loading wire:target="loginAdmin">Loading...</span>
                    </button>
                </form>
            @else
                <form wire:submit.prevent="loginStudent" class="login-form">
                    <div class="form-group">
                        <label for="nisn">NISN (10 digit)</label>
                        <input type="text" id="nisn" wire:model="nisn" placeholder="Masukkan NISN Anda" maxlength="10"
                            required>
                        @error('nisn') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                        <span wire:loading.remove wire:target="loginStudent">Lihat Nilai Saya</span>
                        <span wire:loading wire:target="loginStudent">Loading...</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 5px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-header p {
            color: var(--text-secondary);
        }

        .login-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .login-tab {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--border-color);
            background: transparent;
            color: var(--text-secondary);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-tab:hover {
            border-color: var(--primary);
            color: var(--text-primary);
        }

        .login-tab.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-color: transparent;
            color: white;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 12px;
            border-radius: var(--radius-sm);
            margin-bottom: 15px;
            text-align: center;
        }

        .error {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 5px;
        }
    </style>
</div>
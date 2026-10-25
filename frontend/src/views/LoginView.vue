<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const email = ref('admin@example.com')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function login() {
  error.value = ''
  loading.value = true
  try {
    const { data } = await api.post('/login', { email: email.value, password: password.value })
    localStorage.setItem('token', data.token)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login gagal'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <!-- Background decoration -->
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="login-card">
      <!-- Logo -->
      <div class="login-logo">
        <span class="logo-icon">🌿</span>
      </div>

      <div class="login-header">
        <h1>Selamat Datang</h1>
        <p>Masuk ke Koleksi Tanaman Anda</p>
      </div>

      <form class="login-form" @submit.prevent="login">
        <div class="field">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="nama@contoh.com"
            required
            autocomplete="email"
          />
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
          />
        </div>

        <div v-if="error" class="error-box">
          <span>⚠️</span> {{ error }}
        </div>

        <button type="submit" class="btn-login" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span>{{ loading ? 'Memproses...' : 'Masuk' }}</span>
        </button>
      </form>

      <p class="login-footer">Plant Collection &copy; {{ new Date().getFullYear() }}</p>
    </div>
  </div>
</template>

<style scoped>
/* ---- Page Layout ---- */
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #d4edda 0%, #a8d5b5 40%, #74c69d 100%);
  padding: 1.5rem;
  position: relative;
  overflow: hidden;
}

/* ---- Decorative blobs ---- */
.bg-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: .35;
  pointer-events: none;
}
.blob-1 {
  width: 420px; height: 420px;
  background: #2d7a4f;
  top: -120px; left: -120px;
}
.blob-2 {
  width: 320px; height: 320px;
  background: #1e5c39;
  bottom: -100px; right: -80px;
}

/* ---- Card ---- */
.login-card {
  background: #fff;
  border-radius: 24px;
  box-shadow: 0 20px 60px rgba(0,0,0,.18), 0 8px 20px rgba(0,0,0,.10);
  padding: 2.75rem 2.5rem 2rem;
  width: 100%;
  max-width: 420px;
  position: relative;
  z-index: 1;
  animation: slideUp .45s cubic-bezier(.22,1,.36,1);
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ---- Logo ---- */
.login-logo {
  text-align: center;
  margin-bottom: 1.25rem;
}
.logo-icon {
  font-size: 3rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 72px; height: 72px;
  background: linear-gradient(135deg, #edf7f1, #c6e8d4);
  border-radius: 50%;
  box-shadow: 0 4px 12px rgba(45,122,79,.2);
}

/* ---- Header ---- */
.login-header {
  text-align: center;
  margin-bottom: 2rem;
}
.login-header h1 {
  font-size: 1.625rem;
  color: #1b2e23;
  margin-bottom: .3rem;
}
.login-header p {
  color: #5a7566;
  font-size: .9rem;
}

/* ---- Form ---- */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: .4rem;
}

.field label {
  font-size: .875rem;
  font-weight: 600;
  color: #1b2e23;
}

/* ---- Error box ---- */
.error-box {
  background: #fde8e8;
  border: 1.5px solid #f5c6c6;
  border-radius: 8px;
  padding: .65rem .9rem;
  color: #c0392b;
  font-size: .875rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}

/* ---- Login Button ---- */
.btn-login {
  width: 100%;
  padding: .75rem;
  background: linear-gradient(135deg, #2d7a4f, #3a9e65);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: opacity .2s, transform .2s, box-shadow .2s;
  box-shadow: 0 4px 14px rgba(45,122,79,.35);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  margin-top: .4rem;
}
.btn-login:hover:not(:disabled) {
  opacity: .92;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(45,122,79,.4);
}
.btn-login:active:not(:disabled) {
  transform: translateY(0);
}
.btn-login:disabled {
  opacity: .6;
  cursor: not-allowed;
}

/* ---- Footer ---- */
.login-footer {
  text-align: center;
  margin-top: 1.75rem;
  font-size: .78rem;
  color: #8aab97;
}
</style>

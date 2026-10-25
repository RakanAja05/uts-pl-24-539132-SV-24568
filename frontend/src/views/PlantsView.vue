<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const plants = ref([])
const errors = ref({})
const editingId = ref(null)
const emptyForm = () => ({ name: '', species: '', watering_interval_days: 7, notes: '' })
const form = ref(emptyForm())

async function load() {
  const { data } = await api.get('/plants')
  plants.value = data
}

async function save() {
  errors.value = {}
  try {
    if (editingId.value) {
      await api.put(`/plants/${editingId.value}`, form.value)
    } else {
      await api.post('/plants', form.value)
    }
    cancel()
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors
  }
}

function edit(p) {
  editingId.value = p.id
  form.value = { name: p.name, species: p.species, watering_interval_days: p.watering_interval_days, notes: p.notes ?? '' }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function cancel() {
  editingId.value = null
  form.value = emptyForm()
  errors.value = {}
}

async function remove(p) {
  if (!confirm(`Hapus ${p.name}?`)) return
  await api.delete(`/plants/${p.id}`)
  await load()
}

async function logout() {
  try { await api.post('/logout') } catch {}
  localStorage.removeItem('token')
  router.push('/login')
}

onMounted(load)
</script>

<template>
  <div class="app-shell">

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar">
      <div class="navbar-inner">
        <div class="navbar-brand">
          <span class="brand-icon">🌿</span>
          <span class="brand-name">Plant Collection</span>
        </div>
        <button class="btn-logout" @click="logout">
          <span>⎋</span> Logout
        </button>
      </div>
    </nav>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">

      <!-- ===== FORM CARD ===== -->
      <section class="form-card">
        <div class="form-card-header">
          <h2>{{ editingId ? '✏️ Edit Tanaman' : '🌱 Tambah Tanaman Baru' }}</h2>
          <p>{{ editingId ? 'Perbarui informasi tanaman yang dipilih.' : 'Isi detail tanaman untuk menambahkan ke koleksi.' }}</p>
        </div>

        <form class="plant-form" @submit.prevent="save">
          <div class="form-row">
            <!-- Nama -->
            <div class="field">
              <label>Nama Tanaman</label>
              <input v-model="form.name" placeholder="cth. Monstera Deliciosa" />
              <small v-if="errors.name" class="err">⚠ {{ errors.name[0] }}</small>
            </div>
            <!-- Spesies -->
            <div class="field">
              <label>Spesies</label>
              <input v-model="form.species" placeholder="cth. Monstera deliciosa" />
              <small v-if="errors.species" class="err">⚠ {{ errors.species[0] }}</small>
            </div>
          </div>

          <div class="form-row">
            <!-- Interval siram -->
            <div class="field">
              <label>Interval Siram (hari)</label>
              <input v-model.number="form.watering_interval_days" type="number" min="1" placeholder="7" />
              <small v-if="errors.watering_interval_days" class="err">⚠ {{ errors.watering_interval_days[0] }}</small>
            </div>
            <!-- Catatan -->
            <div class="field">
              <label>Catatan</label>
              <textarea v-model="form.notes" placeholder="Catatan tambahan..." rows="2"></textarea>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary">
              {{ editingId ? '💾 Simpan Perubahan' : '➕ Tambah Tanaman' }}
            </button>
            <button v-if="editingId" type="button" class="btn-ghost" @click="cancel">
              Batal
            </button>
          </div>
        </form>
      </section>

      <!-- ===== PLANTS SECTION ===== -->
      <section class="plants-section">
        <div class="plants-header">
          <h2>🪴 Koleksi Saya</h2>
          <span class="count-badge">{{ plants.length }} tanaman</span>
        </div>

        <!-- Empty state -->
        <div v-if="!plants.length" class="empty-state">
          <div class="empty-icon">🌵</div>
          <h3>Koleksi masih kosong</h3>
          <p>Tambahkan tanaman pertama Anda menggunakan form di atas!</p>
        </div>

        <!-- Plant cards grid -->
        <div v-else class="plants-grid">
          <div v-for="p in plants" :key="p.id" class="plant-card">
            <div class="plant-card-icon">🪴</div>
            <div class="plant-card-body">
              <h3 class="plant-name">{{ p.name }}</h3>
              <p class="plant-species">{{ p.species }}</p>

              <div class="plant-meta">
                <span class="water-badge">💧 setiap {{ p.watering_interval_days }} hari</span>
              </div>

              <div v-if="p.notes" class="plant-notes">
                <span class="notes-label">📝 Catatan</span>
                <p>{{ p.notes }}</p>
              </div>
            </div>

            <div class="plant-card-actions">
              <button class="btn-edit" @click="edit(p)">✏️ Edit</button>
              <button class="btn-delete" @click="remove(p)">🗑 Hapus</button>
            </div>
          </div>
        </div>
      </section>

    </main>
  </div>
</template>

<style scoped>
/* ---- App Shell ---- */
.app-shell {
  min-height: 100vh;
  background: #f0f7f2;
}

/* ---- Navbar ---- */
.navbar {
  background: linear-gradient(135deg, #1e5c39, #2d7a4f);
  box-shadow: 0 2px 12px rgba(0,0,0,.18);
  position: sticky;
  top: 0;
  z-index: 100;
}
.navbar-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: .9rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.navbar-brand {
  display: flex;
  align-items: center;
  gap: .6rem;
}
.brand-icon {
  font-size: 1.6rem;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,.2));
}
.brand-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: #fff;
  letter-spacing: .3px;
}
.btn-logout {
  background: rgba(255,255,255,.15);
  border: 1.5px solid rgba(255,255,255,.35);
  color: #fff;
  padding: .45rem 1rem;
  border-radius: 8px;
  font-size: .875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background .2s, transform .15s;
  display: flex;
  align-items: center;
  gap: .35rem;
}
.btn-logout:hover {
  background: rgba(255,255,255,.28);
  transform: translateY(-1px);
}

/* ---- Main Content ---- */
.main-content {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.5rem 3rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* ---- Form Card ---- */
.form-card {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 4px 16px rgba(0,0,0,.09);
  overflow: hidden;
}
.form-card-header {
  background: linear-gradient(135deg, #edf7f1, #d4edda);
  padding: 1.4rem 2rem;
  border-bottom: 1.5px solid #cde3d6;
}
.form-card-header h2 {
  font-size: 1.2rem;
  color: #1b2e23;
  margin-bottom: .2rem;
}
.form-card-header p {
  font-size: .85rem;
  color: #5a7566;
}

.plant-form {
  padding: 1.75rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 600px) {
  .form-row { grid-template-columns: 1fr; }
}

.field {
  display: flex;
  flex-direction: column;
  gap: .35rem;
}
.field label {
  font-size: .85rem;
  font-weight: 600;
  color: #1b2e23;
}

.form-actions {
  display: flex;
  gap: .75rem;
  flex-wrap: wrap;
  margin-top: .25rem;
}

/* ---- Buttons in Form ---- */
.btn-primary {
  background: linear-gradient(135deg, #2d7a4f, #3a9e65);
  color: #fff;
  border: none;
  padding: .65rem 1.5rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: .9375rem;
  cursor: pointer;
  box-shadow: 0 3px 10px rgba(45,122,79,.3);
  transition: opacity .2s, transform .15s, box-shadow .15s;
}
.btn-primary:hover {
  opacity: .9;
  transform: translateY(-1px);
  box-shadow: 0 5px 14px rgba(45,122,79,.4);
}
.btn-ghost {
  background: transparent;
  color: #5a7566;
  border: 1.5px solid #cde3d6;
  padding: .65rem 1.2rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: .9375rem;
  cursor: pointer;
  transition: background .2s, color .2s;
}
.btn-ghost:hover {
  background: #edf7f1;
  color: #2d7a4f;
  border-color: #2d7a4f;
}

/* ---- Plants Section ---- */
.plants-section {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.plants-header {
  display: flex;
  align-items: center;
  gap: .85rem;
}
.plants-header h2 {
  font-size: 1.3rem;
  color: #1b2e23;
}
.count-badge {
  background: #c6e8d4;
  color: #1e5c39;
  font-size: .78rem;
  font-weight: 700;
  padding: .2rem .75rem;
  border-radius: 9999px;
  letter-spacing: .3px;
}

/* ---- Empty State ---- */
.empty-state {
  text-align: center;
  padding: 3.5rem 1rem;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 4px 16px rgba(0,0,0,.07);
  color: #5a7566;
}
.empty-icon { font-size: 4rem; margin-bottom: .75rem; }
.empty-state h3 { font-size: 1.1rem; color: #1b2e23; margin-bottom: .4rem; font-weight: 700; }
.empty-state p  { font-size: .875rem; color: #5a7566; }

/* ---- Plants Grid ---- */
.plants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
}

/* ---- Plant Card ---- */
.plant-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 3px 12px rgba(0,0,0,.08);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform .2s, box-shadow .2s;
}
.plant-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,.13);
}
.plant-card-icon {
  background: linear-gradient(135deg, #edf7f1, #c6e8d4);
  font-size: 2.5rem;
  text-align: center;
  padding: 1.25rem;
  line-height: 1;
}
.plant-card-body {
  padding: 1.1rem 1.25rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: .5rem;
}
.plant-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1b2e23;
  line-height: 1.2;
}
.plant-species {
  font-style: italic;
  color: #5a7566;
  font-size: .875rem;
}
.plant-meta {
  margin-top: .25rem;
}
.water-badge {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  background: #e3f2fd;
  color: #1565c0;
  font-size: .78rem;
  font-weight: 700;
  padding: .22rem .7rem;
  border-radius: 9999px;
}
.plant-notes {
  background: #f8faf8;
  border-left: 3px solid #c6e8d4;
  border-radius: 0 6px 6px 0;
  padding: .5rem .75rem;
  margin-top: .25rem;
}
.notes-label {
  font-size: .72rem;
  font-weight: 700;
  color: #8aab97;
  display: block;
  margin-bottom: .2rem;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.plant-notes p {
  font-size: .84rem;
  color: #5a7566;
  line-height: 1.4;
}

.plant-card-actions {
  display: flex;
  gap: .5rem;
  padding: .9rem 1.25rem;
  border-top: 1.5px solid #eef6f1;
}
.btn-edit {
  flex: 1;
  background: #e3f2fd;
  color: #1565c0;
  border: none;
  padding: .5rem .75rem;
  border-radius: 7px;
  font-weight: 600;
  font-size: .85rem;
  cursor: pointer;
  transition: background .15s, transform .15s;
}
.btn-edit:hover {
  background: #1565c0;
  color: #fff;
  transform: translateY(-1px);
}
.btn-delete {
  flex: 1;
  background: #fde8e8;
  color: #d62828;
  border: none;
  padding: .5rem .75rem;
  border-radius: 7px;
  font-weight: 600;
  font-size: .85rem;
  cursor: pointer;
  transition: background .15s, transform .15s;
}
.btn-delete:hover {
  background: #d62828;
  color: #fff;
  transform: translateY(-1px);
}

/* ---- Error ---- */
.err {
  color: #d62828;
  font-size: .8rem;
  display: flex;
  align-items: center;
  gap: .3rem;
}
</style>

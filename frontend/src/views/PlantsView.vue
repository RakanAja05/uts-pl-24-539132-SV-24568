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
  <div class="box">
    <div class="top">
      <h2>Koleksi Tanaman</h2>
      <button @click="logout">Logout</button>
    </div>

    <form @submit.prevent="save">
      <input v-model="form.name" placeholder="Nama" />
      <small v-if="errors.name" class="err">{{ errors.name[0] }}</small>
      <input v-model="form.species" placeholder="Spesies" />
      <small v-if="errors.species" class="err">{{ errors.species[0] }}</small>
      <input v-model.number="form.watering_interval_days" type="number" min="1" placeholder="Interval siram (hari)" />
      <small v-if="errors.watering_interval_days" class="err">{{ errors.watering_interval_days[0] }}</small>
      <textarea v-model="form.notes" placeholder="Catatan"></textarea>
      <button>{{ editingId ? 'Update' : 'Tambah' }}</button>
      <button v-if="editingId" type="button" @click="cancel">Batal</button>
    </form>

    <table>
      <thead>
        <tr><th>Nama</th><th>Spesies</th><th>Siram (hari)</th><th>Catatan</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="p in plants" :key="p.id">
          <td>{{ p.name }}</td>
          <td>{{ p.species }}</td>
          <td>{{ p.watering_interval_days }}</td>
          <td>{{ p.notes }}</td>
          <td>
            <button @click="edit(p)">Edit</button>
            <button @click="remove(p)">Hapus</button>
          </td>
        </tr>
        <tr v-if="!plants.length"><td colspan="5">Belum ada data</td></tr>
      </tbody>
    </table>
  </div>
</template>
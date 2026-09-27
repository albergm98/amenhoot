<script setup lang="ts">
import PantallaAmenhoot from '@/components/PantallaAmenhoot.vue'
import { Head, Link, router } from '@inertiajs/vue3'

type Cuestionario = {
    id: number
    titulo: string
    descripcion: string | null
    preguntas_count: number
}

defineProps<{
    cuestionarios: Cuestionario[]
}>()

const lanzar = (id: number, modoEquipos = false) => {
    const equipos = modoEquipos
        ? [
              { nombre: 'Estrellas', color: '#94a3b8' },
              { nombre: 'Cometas', color: '#e2e8f0' },
          ]
        : undefined

    router.post(`/cuestionarios/${id}/partidas`, {
        modo_equipos: modoEquipos,
        equipos,
    })
}

const eliminar = (id: number) => {
    if (confirm('¿Eliminar este cuestionario?')) {
        router.delete(`/cuestionarios/${id}`)
    }
}
</script>

<template>
    <Head title="Cuestionarios — Amenhoot" />
    <PantallaAmenhoot>
        <div class="mx-auto max-w-5xl space-y-8 px-6 pb-10">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="titulo-cosmo">Mis cuestionarios</h1>
                    <p class="mt-1 text-slate-300">Crea, edita y lanza partidas</p>
                </div>
                <div class="flex gap-3">
                    <Link href="/cuestionarios/crear" class="btn-cosmo">Nuevo</Link>
                </div>
            </header>

            <div v-if="!cuestionarios.length" class="panel-cosmo text-center text-slate-300">
                Aún no hay cuestionarios. Crea el primero.
            </div>

            <ul class="grid gap-4 md:grid-cols-2">
                <li v-for="item in cuestionarios" :key="item.id" class="panel-cosmo flex flex-col gap-4">
                    <div>
                        <h2 class="text-xl text-slate-50">{{ item.titulo }}</h2>
                        <p class="mt-1 text-sm text-slate-300">
                            {{ item.preguntas_count }} preguntas
                            <span v-if="item.descripcion"> · {{ item.descripcion }}</span>
                        </p>
                    </div>
                    <div class="mt-auto grid grid-cols-2 gap-2">
                        <button class="btn-cosmo w-full" type="button" @click="lanzar(item.id, false)">Jugar</button>
                        <button class="btn-ghost w-full" type="button" @click="lanzar(item.id, true)">Equipos</button>
                        <Link :href="`/cuestionarios/${item.id}/editar`" class="btn-ghost w-full">Editar</Link>
                        <button class="btn-ghost w-full text-ko" type="button" @click="eliminar(item.id)">Borrar</button>
                    </div>
                </li>
            </ul>
        </div>
    </PantallaAmenhoot>
</template>

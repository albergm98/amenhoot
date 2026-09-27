<script setup lang="ts">
import PantallaAmenhoot from '@/components/PantallaAmenhoot.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { reactive } from 'vue'

type OpcionForm = { texto: string; es_correcta: boolean }
type PreguntaForm = {
    enunciado: string
    segundos_limite: number
    opciones: OpcionForm[]
    indice_correcta: number
}

type CuestionarioProp = {
    id: number
    titulo: string
    descripcion: string | null
    preguntas: Array<{
        enunciado: string
        segundos_limite: number
        opciones: OpcionForm[]
    }>
} | null

const props = defineProps<{
    cuestionario: CuestionarioProp
}>()

const preguntaVacia = (): PreguntaForm => ({
    enunciado: '',
    segundos_limite: 20,
    opciones: [
        { texto: '', es_correcta: true },
        { texto: '', es_correcta: false },
        { texto: '', es_correcta: false },
        { texto: '', es_correcta: false },
    ],
    indice_correcta: 0,
})

const estado = reactive({
    titulo: props.cuestionario?.titulo ?? '',
    descripcion: props.cuestionario?.descripcion ?? '',
    preguntas: (props.cuestionario?.preguntas ?? [preguntaVacia()]).map((pregunta) => {
        const indice = pregunta.opciones.findIndex((o) => o.es_correcta)
        return {
            enunciado: pregunta.enunciado,
            segundos_limite: pregunta.segundos_limite,
            opciones: pregunta.opciones.map((o) => ({ ...o })),
            indice_correcta: indice >= 0 ? indice : 0,
        }
    }),
})

const formulario = useForm({
    titulo: '',
    descripcion: '' as string | null,
    preguntas: [] as PreguntaForm[],
})

const marcarCorrecta = (indicePregunta: number, indiceOpcion: number) => {
    const pregunta = estado.preguntas[indicePregunta]
    pregunta.indice_correcta = indiceOpcion
    pregunta.opciones.forEach((opcion, i) => {
        opcion.es_correcta = i === indiceOpcion
    })
}

const anadirPregunta = () => estado.preguntas.push(preguntaVacia())
const quitarPregunta = (indice: number) => {
    if (estado.preguntas.length > 1) {
        estado.preguntas.splice(indice, 1)
    }
}

const guardar = () => {
    formulario.titulo = estado.titulo
    formulario.descripcion = estado.descripcion || null
    formulario.preguntas = estado.preguntas.map((pregunta) => ({
        ...pregunta,
        opciones: pregunta.opciones.map((opcion, i) => ({
            ...opcion,
            es_correcta: i === pregunta.indice_correcta,
        })),
    }))

    if (props.cuestionario) {
        formulario.put(`/cuestionarios/${props.cuestionario.id}`)
    } else {
        formulario.post('/cuestionarios')
    }
}
</script>

<template>
    <Head :title="cuestionario ? 'Editar cuestionario' : 'Nuevo cuestionario'" />
    <PantallaAmenhoot>
        <div class="mx-auto max-w-3xl space-y-6 px-6 pb-10">
            <h1 class="titulo-cosmo">{{ cuestionario ? 'Editar' : 'Nuevo' }} cuestionario</h1>

            <form class="space-y-6" @submit.prevent="guardar">
                <div class="panel-cosmo space-y-4">
                    <label class="block space-y-2">
                        <span class="block text-center text-sm text-slate-300">Título</span>
                        <input v-model="estado.titulo" v-focus class="campo" required maxlength="255" />
                    </label>
                    <label class="block space-y-2">
                        <span class="block text-center text-sm text-slate-300">Descripción</span>
                        <textarea v-model="estado.descripcion" class="campo min-h-20 text-left" maxlength="2000" />
                    </label>
                </div>

                <article
                    v-for="(pregunta, indice) in estado.preguntas"
                    :key="indice"
                    class="panel-cosmo space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <h2 class="text-slate-100">Pregunta {{ indice + 1 }}</h2>
                        <button class="text-sm text-ko" type="button" @click="quitarPregunta(indice)">Quitar</button>
                    </div>
                    <input v-model="pregunta.enunciado" class="campo" placeholder="Enunciado" required />
                    <label class="flex items-center justify-center gap-3 text-sm text-slate-300">
                        Tiempo (s)
                        <input
                            v-model.number="pregunta.segundos_limite"
                            class="campo w-24"
                            type="number"
                            min="5"
                            max="120"
                        />
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label
                            v-for="(opcion, iOpcion) in pregunta.opciones"
                            :key="iOpcion"
                            class="flex items-center gap-2 rounded-2xl border border-white/15 bg-slate-950/40 p-3"
                        >
                            <input
                                type="radio"
                                :name="`correcta-${indice}`"
                                :checked="pregunta.indice_correcta === iOpcion"
                                @change="marcarCorrecta(indice, iOpcion)"
                            />
                            <input
                                v-model="opcion.texto"
                                class="campo"
                                :placeholder="`Opción ${iOpcion + 1}`"
                                required
                            />
                        </label>
                    </div>
                </article>

                <div class="flex flex-wrap gap-3">
                    <button class="btn-ghost" type="button" @click="anadirPregunta">Añadir pregunta</button>
                    <button class="btn-cosmo" type="submit" :disabled="formulario.processing">Guardar</button>
                    <a href="/cuestionarios" class="btn-ghost">Cancelar</a>
                </div>
                <p v-if="formulario.hasErrors" class="text-center text-sm text-ko">Revisa los campos del formulario.</p>
            </form>
        </div>
    </PantallaAmenhoot>
</template>

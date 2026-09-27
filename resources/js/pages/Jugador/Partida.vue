<script setup lang="ts">
import PantallaAmenhoot from '@/components/PantallaAmenhoot.vue'
import BarrasResultado from '@/components/BarrasResultado.vue'
import PodioFinal from '@/components/PodioFinal.vue'
import ReglasJuego from '@/components/ReglasJuego.vue'
import { usePartidaEnVivo } from '@/composables/usePartidaEnVivo'
import { useSonido } from '@/composables/useSonido'
import { Head } from '@inertiajs/vue3'
import { computed, onUnmounted, ref } from 'vue'

type Opcion = { id: number; texto: string; orden: number }

type Barra = {
    opcion_id: number
    texto: string
    total: number
    es_correcta?: boolean
}

type Clasificado = {
    id: number
    apodo: string
    puntuacion: number
    racha: number
}

type Partida = {
    id: number
    pin: string
    estado: string
    modo_equipos: boolean
    pregunta_iniciada_en: string | null
    total_preguntas: number
    pregunta_actual: {
        id: number
        enunciado: string
        segundos_limite: number
        orden: number
        opciones: Opcion[]
    } | null
    conteo_opciones?: Barra[]
    clasificacion?: Clasificado[]
}

type Jugador = {
    id: number
    apodo: string
    puntuacion: number
    racha: number
    equipo_id: number | null
}

const segundosResultado = 6

const props = defineProps<{
    partida: Partida
    jugador: Jugador
    respuesta_actual: { opcion_id: number; es_correcta: boolean; puntos: number } | null
}>()

const estado = ref(props.partida.estado)
const pregunta = ref(props.partida.pregunta_actual)
const preguntaIniciadaEn = ref(props.partida.pregunta_iniciada_en)
const totalPreguntas = ref(props.partida.total_preguntas)
const jugador = ref({ ...props.jugador })
const respuesta = ref(props.respuesta_actual)
const feedback = ref<'acierto' | 'fallo' | null>(
    props.respuesta_actual ? (props.respuesta_actual.es_correcta ? 'acierto' : 'fallo') : null,
)
const conteoOpciones = ref<Barra[]>([...(props.partida.conteo_opciones ?? [])])
const clasificacion = ref<Clasificado[]>([...(props.partida.clasificacion ?? [])])
const podio = ref<Array<{ id: number; apodo: string; puntuacion: number; equipo?: string | null }>>([])
const podioEquipos = ref<Array<{ id: number; nombre: string; color: string; puntuacion: number }> | null>(null)
const enviando = ref(false)
const segundosRestantes = ref(0)
const segundosPausa = ref(0)
const cuentaAtrasInicio = ref<number | null>(null)
let temporizadorInicio: number | null = null
let temporizador: number | null = null
let pausa: number | null = null

const colores = ['bg-opcion-a', 'bg-opcion-b', 'bg-opcion-c', 'bg-opcion-d']
const { silenciado, reproducir, iniciarMusicaSala, pararMusicaSala, alternarSilencio } = useSonido()

const maxConteo = computed(() => Math.max(1, ...conteoOpciones.value.map((barra) => barra.total)))

const pararCuentaInicio = () => {
    if (temporizadorInicio) {
        clearInterval(temporizadorInicio)
        temporizadorInicio = null
    }
    cuentaAtrasInicio.value = null
}

const arrancarCuentaInicio = (segundos: number) => {
    pararCuentaInicio()
    cuentaAtrasInicio.value = segundos
    temporizadorInicio = window.setInterval(() => {
        if (cuentaAtrasInicio.value === null) {
            return
        }
        cuentaAtrasInicio.value -= 1
        if (cuentaAtrasInicio.value <= 0) {
            pararCuentaInicio()
        }
    }, 1000)
}

const pararPausa = () => {
    if (pausa) {
        clearInterval(pausa)
        pausa = null
    }
    segundosPausa.value = 0
}

const programarPausa = () => {
    pararPausa()
    segundosPausa.value = segundosResultado
    pausa = window.setInterval(() => {
        segundosPausa.value -= 1
        if (segundosPausa.value <= 0) {
            pararPausa()
        }
    }, 1000)
}

const pararTemporizador = () => {
    if (temporizador) {
        clearInterval(temporizador)
        temporizador = null
    }
}

const actualizarCuentaAtras = () => {
    if (!preguntaIniciadaEn.value || !pregunta.value) {
        segundosRestantes.value = 0
        return
    }
    const limite = pregunta.value.segundos_limite * 1000
    const transcurrido = Date.now() - new Date(preguntaIniciadaEn.value).getTime()
    segundosRestantes.value = Math.max(0, Math.ceil((limite - transcurrido) / 1000))
}

const arrancarTemporizador = () => {
    pararTemporizador()
    actualizarCuentaAtras()
    temporizador = window.setInterval(() => {
        actualizarCuentaAtras()
        if (segundosRestantes.value <= 5 && segundosRestantes.value > 0) {
            reproducir('cuenta')
        }
        if (segundosRestantes.value <= 0) {
            pararTemporizador()
        }
    }, 1000)
}

if (estado.value === 'esperando_jugadores') {
    iniciarMusicaSala()
}
if (estado.value === 'mostrando_pregunta') {
    arrancarTemporizador()
}
if (estado.value === 'mostrando_resultados') {
    programarPausa()
}

usePartidaEnVivo(props.partida.pin, null, {
    onPreguntaIniciada: (payload) => {
        pararMusicaSala()
        pararCuentaInicio()
        pararPausa()
        estado.value = 'mostrando_pregunta'
        pregunta.value = payload.pregunta as typeof pregunta.value
        preguntaIniciadaEn.value = payload.pregunta_iniciada_en as string
        totalPreguntas.value = (payload.total_preguntas as number) ?? totalPreguntas.value
        respuesta.value = null
        feedback.value = null
        conteoOpciones.value = []
        clasificacion.value = []
        arrancarTemporizador()
    },
    onPreguntaCerrada: (payload) => {
        pararTemporizador()
        estado.value = 'mostrando_resultados'
        conteoOpciones.value = payload.conteo_opciones as Barra[]
        clasificacion.value = payload.clasificacion as Clasificado[]
        const yo = clasificacion.value.find((item) => item.id === jugador.value.id)
        if (yo) {
            jugador.value.puntuacion = yo.puntuacion
            jugador.value.racha = yo.racha
        }
        programarPausa()
    },
    onPartidaFinalizada: (payload) => {
        pararTemporizador()
        pararPausa()
        estado.value = 'finalizada'
        podio.value = payload.podio as typeof podio.value
        podioEquipos.value = (payload.podio_equipos as typeof podioEquipos.value) ?? null
        reproducir('podio')
    },
    onPartidaPorEmpezar: (payload) => {
        arrancarCuentaInicio((payload.segundos as number) ?? 5)
    },
})

onUnmounted(() => {
    pararCuentaInicio()
    pararTemporizador()
    pararPausa()
})

const responder = async (opcionId: number) => {
    if (enviando.value || respuesta.value || estado.value !== 'mostrando_pregunta') {
        return
    }
    enviando.value = true
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const res = await fetch(`/jugar/${props.partida.id}/responder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf ?? '',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                ),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ opcion_id: opcionId }),
        })
        const datos = await res.json()
        if (!res.ok) {
            return
        }
        respuesta.value = {
            opcion_id: opcionId,
            es_correcta: datos.es_correcta,
            puntos: datos.puntos,
        }
        feedback.value = datos.es_correcta ? 'acierto' : 'fallo'
        jugador.value.puntuacion = datos.puntuacion
        jugador.value.racha = datos.racha
        reproducir(datos.es_correcta ? 'acierto' : 'fallo')
    } finally {
        enviando.value = false
    }
}
</script>

<template>
    <Head :title="jugador.apodo" />
    <PantallaAmenhoot :marca-arriba="false">
        <div class="mx-auto flex h-dvh max-w-lg flex-col gap-3 px-4 pb-5 pt-[max(0.75rem,env(safe-area-inset-top))]">
            <header class="grid shrink-0 grid-cols-[1fr_auto_1fr] items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.06] px-3 py-2 backdrop-blur-md">
                <p class="min-w-0 truncate text-sm leading-tight text-slate-50">
                    {{ jugador.apodo }}
                    <span class="text-slate-400"> · {{ jugador.puntuacion }} pts</span>
                    <span v-if="jugador.racha > 1" class="text-slate-300"> · {{ jugador.racha }}×</span>
                </p>
                <p class="marca-inline" aria-label="Amenhoot">
                    <span class="marca-amen">Amen</span><span class="marca-hoot">hoot</span>
                </p>
                <div class="flex justify-end">
                    <button
                        class="btn-ghost shrink-0 px-3 py-1.5 text-xs"
                        type="button"
                        :aria-pressed="silenciado"
                        :aria-label="silenciado ? 'Activar sonido' : 'Silenciar'"
                        @click="alternarSilencio"
                    >
                        {{ silenciado ? 'Sonido' : 'Silencio' }}
                    </button>
                </div>
            </header>

            <section
                v-if="estado === 'esperando_jugadores'"
                class="flex flex-1 flex-col gap-4"
            >
                <p class="text-center text-slate-300">Esperando al anfitrión…</p>
                <p class="pin-gigante text-center">{{ partida.pin }}</p>
                <ReglasJuego :cuenta-atras="cuentaAtrasInicio" />
            </section>

            <section
                v-else-if="estado === 'mostrando_pregunta' && pregunta"
                class="flex min-h-0 flex-1 flex-col gap-3"
            >
                <div class="flex shrink-0 items-center justify-between gap-3">
                    <p class="text-sm text-slate-300">
                        Pregunta {{ pregunta.orden + 1 }} / {{ totalPreguntas }}
                    </p>
                    <p class="text-3xl text-slate-50" aria-live="polite">{{ segundosRestantes }}</p>
                </div>
                <h1 class="enunciado-pregunta shrink-0">{{ pregunta.enunciado }}</h1>
                <div class="mx-auto flex min-h-0 w-full flex-1 flex-col gap-2">
                    <button
                        v-for="(opcion, indice) in pregunta.opciones"
                        :key="opcion.id"
                        class="btn-respuesta"
                        :class="[
                            colores[indice % 4],
                            respuesta?.opcion_id === opcion.id ? 'ring-4 ring-white/80' : '',
                            respuesta && respuesta.opcion_id !== opcion.id ? 'opacity-50' : '',
                        ]"
                        type="button"
                        :disabled="enviando || !!respuesta"
                        :aria-label="`Opción ${indice + 1}: ${opcion.texto}`"
                        @click="responder(opcion.id)"
                    >
                        {{ opcion.texto }}
                    </button>
                </div>
                <p
                    v-if="feedback && respuesta"
                    class="shrink-0 text-center text-lg"
                    :class="feedback === 'acierto' ? 'text-ok' : 'text-ko'"
                    aria-live="polite"
                >
                    {{ feedback === 'acierto' ? `¡Correcto! +${respuesta.puntos}` : 'Incorrecto' }}
                </p>
            </section>

            <section
                v-else-if="estado === 'mostrando_resultados'"
                class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto"
            >
                <BarrasResultado :barras="conteoOpciones" />
                <PodioFinal
                    :key="`parcial-${clasificacion.map((j) => j.id + ':' + j.puntuacion).join('-')}`"
                    :podio="clasificacion"
                    :confeti="false"
                    :instantaneo="true"
                    titulo="Clasificación"
                />
                <p v-if="segundosPausa > 0" class="text-center text-slate-300" aria-live="polite">
                    Siguiente en {{ segundosPausa }} s
                </p>
            </section>

            <PodioFinal v-else-if="estado === 'finalizada'" :podio="podio" :podio-equipos="podioEquipos" />
        </div>
    </PantallaAmenhoot>
</template>

<style scoped>
.marca-inline {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.05rem;
    line-height: 1;
    letter-spacing: 0.04em;
    text-shadow: 0 1px 0 rgb(0 0 0 / 0.35);
}

.marca-amen {
    color: #f8fafc;
}

.marca-hoot {
    color: #94a3b8;
}
</style>

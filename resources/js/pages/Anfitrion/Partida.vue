<script setup lang="ts">
import PantallaAmenhoot from '@/components/PantallaAmenhoot.vue'
import BarrasResultado from '@/components/BarrasResultado.vue'
import PodioFinal from '@/components/PodioFinal.vue'
import { usePartidaEnVivo } from '@/composables/usePartidaEnVivo'
import { useSonido } from '@/composables/useSonido'
import { Head, router } from '@inertiajs/vue3'
import { computed, onUnmounted, ref, watch } from 'vue'

type Jugador = {
    id: number
    apodo: string
    puntuacion: number
    racha: number
    equipo_id: number | null
    equipo?: string | null
}

type Partida = {
    id: number
    pin: string
    estado: string
    modo_equipos: boolean
    pregunta_iniciada_en: string | null
    cuestionario: { id: number; titulo: string; total_preguntas: number }
    pregunta_actual: {
        id: number
        enunciado: string
        segundos_limite: number
        orden: number
        opciones: Array<{ id: number; texto: string; orden: number; es_correcta: boolean | null }>
    } | null
    jugadores: Jugador[]
    equipos: Array<{ id: number; nombre: string; color: string; jugadores: string[]; puntuacion: number }>
    quedan_preguntas: boolean
    conteo_opciones?: Array<{ opcion_id: number; texto: string; total: number; es_correcta?: boolean }>
    clasificacion?: Jugador[]
    total_respuestas?: number
    podio?: Array<{ id: number; apodo: string; puntuacion: number; racha?: number; equipo?: string | null }>
    podio_equipos?: Array<{ id: number; nombre: string; color: string; puntuacion: number }> | null
}

const segundosResultado = 6

const props = defineProps<{ partida: Partida }>()

const jugadores = ref([...props.partida.jugadores])
const estado = ref(props.partida.estado)
const preguntaActual = ref(props.partida.pregunta_actual)
const preguntaIniciadaEn = ref(props.partida.pregunta_iniciada_en)
const totalRespuestas = ref(props.partida.total_respuestas ?? 0)
const conteoOpciones = ref(props.partida.conteo_opciones ?? [])
const clasificacion = ref<Jugador[]>(props.partida.clasificacion ?? [])
const podio = ref<Array<{ id: number; apodo: string; puntuacion: number; equipo?: string | null }>>(
    [...(props.partida.podio ?? [])],
)
const podioEquipos = ref<Array<{ id: number; nombre: string; color: string; puntuacion: number }> | null>(
    props.partida.podio_equipos ?? null,
)
const segundosRestantes = ref(0)
const segundosPausa = ref(0)
const pinCopiado = ref(false)
const cuentaAtrasInicio = ref<number | null>(null)
let avisoCopia: number | null = null
let temporizadorInicio: number | null = null
let temporizador: number | null = null
let pausa: number | null = null
let cerrando = false
let lanzandoInicio = false

const copiarPin = async () => {
    try {
        await navigator.clipboard.writeText(props.partida.pin)
    } catch {
        const campo = document.createElement('textarea')
        campo.value = props.partida.pin
        document.body.appendChild(campo)
        campo.select()
        document.execCommand('copy')
        document.body.removeChild(campo)
    }
    pinCopiado.value = true
    if (avisoCopia) {
        clearTimeout(avisoCopia)
    }
    avisoCopia = window.setTimeout(() => {
        pinCopiado.value = false
    }, 2000)
}

const pararCuentaInicio = () => {
    if (temporizadorInicio) {
        clearInterval(temporizadorInicio)
        temporizadorInicio = null
    }
    cuentaAtrasInicio.value = null
}

const arrancarCuentaInicio = (segundos: number, alTerminar?: () => void) => {
    pararCuentaInicio()
    cuentaAtrasInicio.value = segundos
    temporizadorInicio = window.setInterval(() => {
        if (cuentaAtrasInicio.value === null) {
            return
        }
        cuentaAtrasInicio.value -= 1
        if (cuentaAtrasInicio.value > 0) {
            return
        }
        pararCuentaInicio()
        alTerminar?.()
    }, 1000)
}

const { silenciado, reproducir, iniciarMusicaSala, pararMusicaSala, alternarSilencio } = useSonido()

const colores = ['bg-opcion-a', 'bg-opcion-b', 'bg-opcion-c', 'bg-opcion-d']

const actualizarCuentaAtras = () => {
    if (!preguntaIniciadaEn.value || !preguntaActual.value) {
        segundosRestantes.value = 0
        return
    }
    const limite = preguntaActual.value.segundos_limite * 1000
    const transcurrido = Date.now() - new Date(preguntaIniciadaEn.value).getTime()
    segundosRestantes.value = Math.max(0, Math.ceil((limite - transcurrido) / 1000))
}

const arrancarTemporizador = () => {
    if (temporizador) {
        clearInterval(temporizador)
    }
    actualizarCuentaAtras()
    temporizador = window.setInterval(() => {
        actualizarCuentaAtras()
        if (segundosRestantes.value <= 5 && segundosRestantes.value > 0) {
            reproducir('cuenta')
        }
        if (segundosRestantes.value <= 0) {
            cerrarPregunta()
        }
    }, 1000)
}

watch(
    () => props.partida,
    (nueva) => {
        jugadores.value = [...nueva.jugadores]
        estado.value = nueva.estado
        preguntaActual.value = nueva.pregunta_actual
        preguntaIniciadaEn.value = nueva.pregunta_iniciada_en
        if (nueva.conteo_opciones?.length) {
            conteoOpciones.value = nueva.conteo_opciones
        }
        if (nueva.clasificacion?.length) {
            clasificacion.value = nueva.clasificacion
        }
        totalRespuestas.value = nueva.total_respuestas ?? totalRespuestas.value
        if (nueva.estado === 'mostrando_resultados') {
            programarSiguiente()
        }
        if (nueva.estado === 'mostrando_pregunta') {
            pararPausa()
            arrancarTemporizador()
        }
    },
)

usePartidaEnVivo(props.partida.pin, props.partida.id, {
    onJugadorUnido: (payload) => {
        const jugador = payload.jugador as Jugador
        if (!jugadores.value.some((j) => j.id === jugador.id)) {
            jugadores.value.push(jugador)
        }
        reproducir('sala')
    },
    onPreguntaIniciada: (payload) => {
        pararPausa()
        pararCuentaInicio()
        cerrando = false
        pararMusicaSala()
        estado.value = 'mostrando_pregunta'
        totalRespuestas.value = 0
        conteoOpciones.value = []
        preguntaActual.value = payload.pregunta as typeof preguntaActual.value
        preguntaIniciadaEn.value = payload.pregunta_iniciada_en as string
        arrancarTemporizador()
    },
    onRespuestaRecibida: (payload) => {
        totalRespuestas.value = payload.total_respuestas as number
        const totalJugadores = (payload.total_jugadores as number) ?? jugadores.value.length
        if (totalJugadores > 0 && totalRespuestas.value >= totalJugadores) {
            cerrarPregunta()
        }
    },
    onPreguntaCerrada: (payload) => {
        estado.value = 'mostrando_resultados'
        conteoOpciones.value = payload.conteo_opciones as typeof conteoOpciones.value
        clasificacion.value = payload.clasificacion as Jugador[]
        if (temporizador) {
            clearInterval(temporizador)
            temporizador = null
        }
        programarSiguiente()
    },
    onPartidaFinalizada: (payload) => {
        pararPausa()
        pararCuentaInicio()
        estado.value = 'finalizada'
        podio.value = payload.podio as typeof podio.value
        podioEquipos.value = (payload.podio_equipos as typeof podioEquipos.value) ?? null
        reproducir('podio')
        pararMusicaSala()
    },
    onPartidaPorEmpezar: (payload) => {
        const segundos = (payload.segundos as number) ?? 5
        arrancarCuentaInicio(segundos, () => {
            if (lanzandoInicio) {
                return
            }
            lanzandoInicio = true
            iniciarPregunta()
        })
    },
})

if (estado.value === 'esperando_jugadores') {
    iniciarMusicaSala()
}

onUnmounted(() => {
    if (temporizador) {
        clearInterval(temporizador)
    }
    if (avisoCopia) {
        clearTimeout(avisoCopia)
    }
    pararCuentaInicio()
    pararPausa()
})

const iniciarPregunta = () => router.post(`/partidas/${props.partida.id}/iniciar-pregunta`, {}, {
    onFinish: () => {
        lanzandoInicio = false
    },
})
const prepararInicio = () => {
    if (cuentaAtrasInicio.value !== null || !jugadores.value.length) {
        return
    }
    router.post(`/partidas/${props.partida.id}/preparar-inicio`)
}
const finalizar = () => router.post(`/partidas/${props.partida.id}/finalizar`)

const pararPausa = () => {
    if (pausa) {
        clearInterval(pausa)
        pausa = null
    }
    segundosPausa.value = 0
}

const programarSiguiente = () => {
    if (pausa || estado.value === 'finalizada') {
        return
    }
    segundosPausa.value = segundosResultado
    pausa = window.setInterval(() => {
        segundosPausa.value -= 1
        if (segundosPausa.value > 0) {
            return
        }
        pararPausa()
        if (haySiguiente.value) {
            iniciarPregunta()
            return
        }
        finalizar()
    }, 1000)
}

const cerrarPregunta = () => {
    if (estado.value !== 'mostrando_pregunta' || cerrando) {
        return
    }
    cerrando = true
    if (temporizador) {
        clearInterval(temporizador)
        temporizador = null
    }
    router.post(`/partidas/${props.partida.id}/cerrar-pregunta`, {}, {
        onFinish: () => {
            cerrando = false
        },
    })
}

const maxConteo = computed(() => Math.max(1, ...conteoOpciones.value.map((c) => c.total)))
const haySiguiente = computed(() => {
    if (!preguntaActual.value) {
        return props.partida.cuestionario.total_preguntas > 0
    }
    return preguntaActual.value.orden + 1 < props.partida.cuestionario.total_preguntas
})

if (estado.value === 'mostrando_pregunta') {
    arrancarTemporizador()
}
if (estado.value === 'mostrando_resultados') {
    programarSiguiente()
}
</script>

<template>
    <Head :title="`PIN ${partida.pin}`" />
    <PantallaAmenhoot :marca-arriba="false">
        <div class="sala mx-auto flex h-dvh max-w-5xl flex-col px-4 pb-5 pt-[max(0.75rem,env(safe-area-inset-top))] sm:px-6">
            <header class="mb-3 grid shrink-0 grid-cols-[1fr_auto_1fr] items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.06] px-3 py-2 backdrop-blur-md sm:mb-4">
                <p class="truncate text-sm text-slate-400">PIN {{ partida.pin }}</p>
                <p class="marca-inline" aria-label="Amenhoot">
                    <span class="marca-amen">Amen</span><span class="marca-hoot">hoot</span>
                </p>
                <div class="flex justify-end">
                    <button
                        class="btn-ghost shrink-0 px-3 py-1.5 text-xs"
                        type="button"
                        :aria-pressed="silenciado"
                        @click="alternarSilencio"
                    >
                        {{ silenciado ? 'Sonido' : 'Silencio' }}
                    </button>
                </div>
            </header>

            <section v-if="estado === 'esperando_jugadores'" class="flex flex-1 flex-col gap-6">
                <div class="panel-cosmo text-center">
                    <p class="text-slate-300">Los jugadores entran en /unirse con este PIN</p>
                    <button
                        type="button"
                        class="grupo-pin mt-3"
                        :aria-label="`Copiar PIN ${partida.pin}`"
                        @click="copiarPin"
                    >
                        <span class="pin-gigante pin-copiable select-all">{{ partida.pin }}</span>
                        <span class="etiqueta-copia">
                            {{ pinCopiado ? '¡Copiado!' : 'Toca para copiar' }}
                        </span>
                    </button>
                </div>

                <div class="panel-cosmo flex min-h-40 flex-1 flex-col">
                    <h2 class="mb-4 text-center text-lg text-slate-100">
                        Jugadores ({{ jugadores.length }})
                    </h2>
                    <ul
                        v-if="jugadores.length"
                        class="flex flex-1 flex-wrap content-start justify-center gap-3"
                    >
                        <li
                            v-for="jugador in jugadores"
                            :key="jugador.id"
                            class="rounded-full bg-white/10 px-4 py-2 text-sm text-slate-100"
                        >
                            {{ jugador.apodo }}
                            <span v-if="jugador.equipo" class="text-slate-300"> · {{ jugador.equipo }}</span>
                        </li>
                    </ul>
                    <p v-else class="flex flex-1 items-center justify-center text-slate-400">
                        Esperando a que se unan…
                    </p>
                </div>

                <div class="acciones-sala">
                    <button
                        class="btn-cosmo w-full py-4 text-xl"
                        type="button"
                        :disabled="!jugadores.length || cuentaAtrasInicio !== null"
                        @click="prepararInicio"
                    >
                        {{ cuentaAtrasInicio !== null ? `Empezando en ${cuentaAtrasInicio}…` : 'Empezar' }}
                    </button>
                </div>
            </section>

            <section
                v-else-if="estado === 'mostrando_pregunta' && preguntaActual"
                class="flex min-h-0 flex-1 flex-col gap-3"
            >
                <div class="flex shrink-0 items-center justify-between gap-4">
                    <p class="text-slate-300">
                        Pregunta {{ preguntaActual.orden + 1 }} / {{ partida.cuestionario.total_preguntas }}
                    </p>
                    <p class="text-4xl text-slate-100">{{ segundosRestantes }}</p>
                </div>
                <h2 class="enunciado-pregunta shrink-0">{{ preguntaActual.enunciado }}</h2>
                <div class="mx-auto flex min-h-0 w-full max-w-3xl flex-1 flex-col gap-2 sm:gap-3">
                    <div
                        v-for="(opcion, indice) in preguntaActual.opciones"
                        :key="opcion.id"
                        class="btn-respuesta"
                        :class="colores[indice % 4]"
                    >
                        {{ opcion.texto }}
                    </div>
                </div>
                <p class="shrink-0 text-center text-slate-300">
                    Respuestas: {{ totalRespuestas }} / {{ jugadores.length }}
                </p>
            </section>

            <section v-else-if="estado === 'mostrando_resultados'" class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto">
                <BarrasResultado :barras="conteoOpciones" />
                <PodioFinal
                    :key="`parcial-${clasificacion.map((j) => j.id + ':' + j.puntuacion).join('-')}`"
                    :podio="clasificacion"
                    :confeti="false"
                    :instantaneo="true"
                    titulo="Clasificación"
                />
                <p class="acciones-sala text-center text-slate-300" aria-live="polite">
                    {{ haySiguiente ? 'Siguiente pregunta' : 'Podio' }} en {{ segundosPausa }} s
                </p>
            </section>

            <PodioFinal v-else-if="estado === 'finalizada'" :podio="podio" :podio-equipos="podioEquipos" />
        </div>
    </PantallaAmenhoot>
</template>

<style scoped>
.acciones-sala {
    position: sticky;
    bottom: max(0.75rem, env(safe-area-inset-bottom));
    z-index: 5;
    margin-top: auto;
    padding-top: 0.5rem;
}

.grupo-pin {
    display: flex;
    width: 100%;
    cursor: pointer;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    border: 0;
    background: transparent;
    padding: 0.5rem;
    user-select: text;
}

.pin-copiable {
    user-select: all;
}

.etiqueta-copia {
    font-size: 0.95rem;
    color: #cbd5e1;
}

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

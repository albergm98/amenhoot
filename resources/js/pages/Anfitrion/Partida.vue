<script setup lang="ts">
import PantallaAmenhoot from '@/components/PantallaAmenhoot.vue'
import BarrasResultado from '@/components/BarrasResultado.vue'
import FotoJugador from '@/components/FotoJugador.vue'
import PodioFinal from '@/components/PodioFinal.vue'
import { useNarracionAmenhoot } from '@/composables/useNarracionAmenhoot'
import { usePartidaEnVivo } from '@/composables/usePartidaEnVivo'
import { useSonido } from '@/composables/useSonido'
import { textoNarracionNormas } from '@/datos/normasAmenhoot'
import { Head, router } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

type Jugador = {
    id: number
    apodo: string
    foto?: string | null
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
    podio?: Array<{ id: number; apodo: string; foto?: string | null; puntuacion: number; racha?: number; equipo?: string | null }>
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
const podio = ref<Array<{ id: number; apodo: string; foto?: string | null; puntuacion: number; equipo?: string | null }>>(
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
let sondeoSala: number | null = null
let cerrando = false
let lanzandoInicio = false
const preparandoInicio = ref(false)

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

const { hablando: narrando, narrarNormas, narrarEnunciado, cancelar: cancelarNarracion } =
    useNarracionAmenhoot({
        silenciado,
        alEmpezar: () => pararMusicaSala(),
        alTerminar: () => {
            if (estado.value === 'esperando_jugadores' && !silenciado.value) {
                iniciarMusicaSala()
            }
        },
    })

let claveEnunciadoNarrado: string | null = null

const leerNormas = () => {
    claveEnunciadoNarrado = null
    void narrarNormas(textoNarracionNormas())
}

const narrarPreguntaActual = () => {
    if (!preguntaActual.value) {
        return
    }
    const clave = `${preguntaActual.value.id}:${preguntaActual.value.orden}`
    if (claveEnunciadoNarrado === clave) {
        return
    }
    claveEnunciadoNarrado = clave
    void narrarEnunciado(
        props.partida.cuestionario.id,
        preguntaActual.value.orden,
        preguntaActual.value.enunciado,
    )
}

const colores = ['bg-opcion-a', 'bg-opcion-b', 'bg-opcion-c', 'bg-opcion-d']
const letras = ['A', 'B', 'C', 'D']

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
            narrarPreguntaActual()
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
        cancelarNarracion()
        estado.value = 'mostrando_pregunta'
        totalRespuestas.value = 0
        conteoOpciones.value = []
        preguntaActual.value = payload.pregunta as typeof preguntaActual.value
        preguntaIniciadaEn.value = payload.pregunta_iniciada_en as string
        arrancarTemporizador()
        narrarPreguntaActual()
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
        if (cuentaAtrasInicio.value !== null) {
            return
        }
        const segundos = (payload.segundos as number) ?? 5
        arrancarCuentaInicio(segundos, lanzarTrasCuentaAtras)
    },
})

watch(estado, (nuevo) => {
    if (nuevo === 'esperando_jugadores') {
        arrancarSondeoSala()
        return
    }
    pararSondeoSala()
})

const pararSondeoSala = () => {
    if (sondeoSala) {
        clearInterval(sondeoSala)
        sondeoSala = null
    }
}

const arrancarSondeoSala = () => {
    pararSondeoSala()
    sondeoSala = window.setInterval(() => {
        if (estado.value !== 'esperando_jugadores' || cuentaAtrasInicio.value !== null) {
            return
        }
        router.reload({
            only: ['partida'],
            preserveScroll: true,
            preserveState: true,
        })
    }, 2500)
}

if (estado.value === 'esperando_jugadores') {
    iniciarMusicaSala()
}

onMounted(() => {
    if (estado.value === 'esperando_jugadores') {
        arrancarSondeoSala()
    }
    if (estado.value === 'mostrando_pregunta') {
        narrarPreguntaActual()
    }
})

onUnmounted(() => {
    if (temporizador) {
        clearInterval(temporizador)
    }
    if (avisoCopia) {
        clearTimeout(avisoCopia)
    }
    pararSondeoSala()
    pararCuentaInicio()
    pararPausa()
    cancelarNarracion()
})

const iniciarPregunta = () => router.post(`/partidas/${props.partida.id}/iniciar-pregunta`, {}, {
    onFinish: () => {
        lanzandoInicio = false
    },
})

const lanzarTrasCuentaAtras = () => {
    if (lanzandoInicio) {
        return
    }
    lanzandoInicio = true
    pararSondeoSala()
    iniciarPregunta()
}

const prepararInicio = () => {
    if (preparandoInicio.value || cuentaAtrasInicio.value !== null || !jugadores.value.length) {
        return
    }
    cancelarNarracion()
    preparandoInicio.value = true
    router.post(`/partidas/${props.partida.id}/preparar-inicio`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (cuentaAtrasInicio.value !== null) {
                return
            }
            arrancarCuentaInicio(5, lanzarTrasCuentaAtras)
        },
        onFinish: () => {
            preparandoInicio.value = false
        },
    })
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
        <div class="sala mx-auto flex h-dvh max-w-5xl flex-col gap-3 px-4 pb-5 pt-[max(0.75rem,env(safe-area-inset-top))] sm:px-6">
            <header class="barra-partida">
                <p class="truncate text-sm font-medium text-slate-300">PIN {{ partida.pin }}</p>
                <p class="marca-inline" aria-label="Amenhoot">
                    <span class="marca-amen">Amen</span><span class="marca-hoot">hoot</span>
                </p>
                <div class="flex justify-end">
                    <button
                        class="btn-ghost shrink-0 px-3 py-1.5 text-xs"
                        type="button"
                        :aria-pressed="silenciado"
                        @click="() => { cancelarNarracion(); alternarSilencio() }"
                    >
                        {{ silenciado ? 'Sonido' : 'Silencio' }}
                    </button>
                </div>
            </header>

            <section v-if="estado === 'esperando_jugadores'" class="flex min-h-0 flex-1 flex-col gap-4">
                <div class="panel-cosmo overflow-hidden text-center">
                    <p class="text-sm uppercase tracking-[0.14em] text-slate-400">Entra en /unirse</p>
                    <button
                        type="button"
                        class="grupo-pin mt-2"
                        :aria-label="`Copiar PIN ${partida.pin}`"
                        @click="copiarPin"
                    >
                        <span class="pin-gigante pin-copiable select-all px-1">{{ partida.pin }}</span>
                        <span class="etiqueta-copia">
                            {{ pinCopiado ? '¡Copiado!' : 'Toca para copiar' }}
                        </span>
                    </button>
                </div>

                <div
                    v-if="cuentaAtrasInicio !== null && cuentaAtrasInicio > 0"
                    class="overlay-cuenta"
                    role="status"
                    aria-live="assertive"
                >
                    <p class="overlay-etiqueta">Empieza en</p>
                    <p class="overlay-numero">{{ cuentaAtrasInicio }}</p>
                </div>

                <div class="panel-cosmo flex min-h-0 flex-1 flex-col">
                    <h2 class="mb-3 text-center font-display text-lg font-semibold text-slate-100">
                        Jugadores · {{ jugadores.length }}
                    </h2>
                    <ul
                        v-if="jugadores.length"
                        class="flex flex-1 flex-wrap content-start justify-center gap-2.5 overflow-y-auto"
                    >
                        <li v-for="jugador in jugadores" :key="jugador.id" class="chip-jugador">
                            <FotoJugador :nombre="jugador.apodo" :foto="jugador.foto" />
                            <span>
                                {{ jugador.apodo }}
                                <span v-if="jugador.equipo" class="text-slate-400"> · {{ jugador.equipo }}</span>
                            </span>
                        </li>
                    </ul>
                    <p v-else class="flex flex-1 items-center justify-center text-slate-400">
                        Esperando a que se unan…
                    </p>
                </div>

                <div class="acciones-sala flex flex-col gap-2">
                    <button
                        class="btn-ghost w-full py-3 text-base"
                        type="button"
                        :disabled="cuentaAtrasInicio !== null || silenciado"
                        :aria-busy="narrando"
                        @click="leerNormas"
                    >
                        {{ narrando ? 'Leyendo normas…' : 'Leer normas' }}
                    </button>
                    <button
                        class="btn-cosmo w-full py-4 text-xl"
                        type="button"
                        :disabled="!jugadores.length || cuentaAtrasInicio !== null || preparandoInicio"
                        @click.prevent="prepararInicio"
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
                    <p class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-sm text-slate-300">
                        {{ preguntaActual.orden + 1 }} / {{ partida.cuestionario.total_preguntas }}
                    </p>
                    <p
                        class="temporizador-bola"
                        :class="{ urgente: segundosRestantes <= 5 }"
                        aria-live="polite"
                    >
                        {{ segundosRestantes }}
                    </p>
                </div>
                <h2 class="enunciado-pregunta shrink-0 px-1">{{ preguntaActual.enunciado }}</h2>
                <div class="mx-auto flex min-h-0 w-full max-w-3xl flex-1 flex-col gap-2.5 sm:gap-3">
                    <div
                        v-for="(opcion, indice) in preguntaActual.opciones"
                        :key="opcion.id"
                        class="btn-respuesta"
                        :class="colores[indice % 4]"
                    >
                        <span class="letra-opcion">{{ letras[indice % 4] }}</span>
                        <span class="min-w-0 flex-1 text-center">{{ opcion.texto }}</span>
                    </div>
                </div>
                <p class="shrink-0 text-center text-sm text-slate-300">
                    Respuestas {{ totalRespuestas }} / {{ jugadores.length }}
                </p>
            </section>

            <section v-else-if="estado === 'mostrando_resultados'" class="resultados flex min-h-0 flex-1 flex-col gap-3">
                <BarrasResultado :barras="conteoOpciones" />
                <PodioFinal
                    :key="`parcial-${clasificacion.map((j) => j.id + ':' + j.puntuacion).join('-')}`"
                    :podio="clasificacion"
                    :confeti="false"
                    :instantaneo="true"
                    :compacto="true"
                    titulo="Clasificación"
                />
                <p class="aviso-siguiente" aria-live="polite">
                    {{ haySiguiente ? 'Siguiente pregunta' : 'Podio' }} en {{ segundosPausa }} s
                </p>
            </section>

            <PodioFinal
                v-else-if="estado === 'finalizada'"
                class="min-h-0 flex-1"
                :podio="podio"
                :podio-equipos="podioEquipos"
            />
        </div>
    </PantallaAmenhoot>
</template>

<style scoped>
.acciones-sala {
    position: sticky;
    bottom: max(0.75rem, env(safe-area-inset-bottom));
    z-index: 30;
    margin-top: auto;
    padding-top: 0.5rem;
    isolation: isolate;
}

.acciones-sala .btn-cosmo {
    position: relative;
    z-index: 31;
    min-height: 3.25rem;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
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

.resultados {
    overflow: auto;
    scrollbar-width: none;
}

.resultados::-webkit-scrollbar {
    display: none;
}

.aviso-siguiente {
    margin: 0;
    flex-shrink: 0;
    border: 1px solid rgb(255 255 255 / 0.12);
    border-radius: 999px;
    background: rgb(15 23 42 / 0.55);
    padding: 0.55rem 1rem;
    text-align: center;
    font-size: 0.95rem;
    color: #cbd5e1;
    backdrop-filter: blur(8px);
}

.overlay-cuenta {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: rgb(6 11 24 / 0.55);
    backdrop-filter: blur(14px);
}

.overlay-etiqueta {
    margin: 0;
    font-size: 1.1rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #cbd5e1;
}

.overlay-numero {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(7rem, 32vw, 12rem);
    font-weight: 800;
    line-height: 1;
    color: #f8fafc;
    text-shadow: 0 12px 40px rgb(0 0 0 / 0.45);
    animation: latido 1s ease infinite;
}

@keyframes latido {
    50% {
        transform: scale(1.06);
    }
}
</style>

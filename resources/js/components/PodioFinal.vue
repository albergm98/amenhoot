<script setup lang="ts">
import FotoJugador from '@/components/FotoJugador.vue'
import confetti from 'canvas-confetti'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

type EntradaPodio = {
    id: number
    apodo: string
    foto?: string | null
    puntuacion: number
    racha?: number
    equipo?: string | null
}

type EntradaEquipo = {
    id: number
    nombre: string
    color: string
    puntuacion: number
}

const props = withDefaults(
    defineProps<{
        podio: EntradaPodio[]
        podioEquipos?: EntradaEquipo[] | null
        confeti?: boolean
        instantaneo?: boolean
        compacto?: boolean
        titulo?: string
    }>(),
    {
        confeti: true,
        instantaneo: false,
        compacto: false,
        titulo: 'Clasificación final',
    },
)

type FasePodio = 'espera' | 'tercero' | 'segundo' | 'primero' | 'lista'

const fase = ref<FasePodio>('espera')
const filasVisibles = ref(0)
const tiempos: number[] = []

const ranking = computed(() =>
    [...props.podio].sort((a, b) => b.puntuacion - a.puntuacion || a.id - b.id),
)

const primero = computed(() => ranking.value[0] ?? null)
const segundo = computed(() => ranking.value[1] ?? null)
const tercero = computed(() => ranking.value[2] ?? null)
const resto = computed(() => ranking.value.slice(3))

const fondoBlur = computed(
    () => primero.value?.foto || '/assets/img/fondo-amenhoot.jpg',
)

const etiquetaFase = computed(() => {
    if (fase.value === 'espera') {
        return 'Preparando el podio…'
    }
    if (fase.value === 'tercero') {
        return '¡Bronce!'
    }
    if (fase.value === 'segundo') {
        return '¡Plata!'
    }
    if (fase.value === 'primero') {
        return '¡Campeón!'
    }
    return 'Top 3'
})

const programar = (ms: number, fn: () => void) => {
    tiempos.push(window.setTimeout(fn, ms))
}

const lanzarConfeti = (fuerte = false) => {
    if (!props.confeti) {
        return
    }
    confetti({
        particleCount: fuerte ? 180 : 90,
        spread: fuerte ? 90 : 65,
        origin: { y: 0.65 },
        colors: ['#e2e8f0', '#94a3b8', '#f8fafc', '#34d399', '#fbbf24'],
    })
}

const mostrarTodo = () => {
    fase.value = 'lista'
    filasVisibles.value = ranking.value.length
}

onMounted(() => {
    if (props.instantaneo || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        mostrarTodo()
        if (!props.instantaneo) {
            lanzarConfeti(true)
        }
        return
    }

    programar(500, () => {
        if (tercero.value) {
            fase.value = 'tercero'
            lanzarConfeti(false)
        } else if (segundo.value) {
            fase.value = 'segundo'
            lanzarConfeti(false)
        } else {
            fase.value = 'primero'
            lanzarConfeti(true)
        }
    })

    if (tercero.value) {
        programar(2000, () => {
            fase.value = 'segundo'
            lanzarConfeti(false)
        })
        programar(3600, () => {
            fase.value = 'primero'
            lanzarConfeti(true)
        })
        programar(5400, () => {
            fase.value = 'lista'
        })
    } else if (segundo.value) {
        programar(2000, () => {
            fase.value = 'primero'
            lanzarConfeti(true)
        })
        programar(4000, () => {
            fase.value = 'lista'
        })
    } else {
        programar(500, () => {
            fase.value = 'primero'
            lanzarConfeti(true)
        })
        programar(2400, () => {
            fase.value = 'lista'
        })
    }
})

onUnmounted(() => {
    tiempos.forEach((t) => window.clearTimeout(t))
})

const mostrarPuesto = (puesto: 1 | 2 | 3) => {
    if (fase.value === 'lista') {
        return true
    }
    if (puesto === 3) {
        return ['tercero', 'segundo', 'primero', 'lista'].includes(fase.value)
    }
    if (puesto === 2) {
        return ['segundo', 'primero', 'lista'].includes(fase.value)
    }
    return ['primero', 'lista'].includes(fase.value)
}

const onListaLista = () => {
    if (filasVisibles.value > 0) {
        return
    }
    ranking.value.forEach((_, indice) => {
        programar(140 * indice, () => {
            filasVisibles.value = indice + 1
        })
    })
}

watch(fase, (nueva) => {
    if (nueva === 'lista') {
        onListaLista()
    }
})

watch(
    () => props.podio,
    () => {
        if (props.instantaneo) {
            mostrarTodo()
        }
    },
)

const faseEsLista = computed(() => fase.value === 'lista')
</script>

<template>
    <section
        v-if="compacto"
        class="podio-compacto"
        aria-label="Clasificación"
    >
        <h2 class="titulo-compacto">{{ titulo }}</h2>
        <ol class="lista-compacta">
            <li v-for="(jugador, indice) in ranking" :key="jugador.id" class="fila-compacta">
                <span class="puesto" :class="{ oro: indice === 0, plata: indice === 1, bronce: indice === 2 }">
                    {{ indice + 1 }}
                </span>
                <FotoJugador :nombre="jugador.apodo" :foto="jugador.foto" />
                <span class="nombre">
                    {{ jugador.apodo }}
                    <span v-if="jugador.equipo" class="equipo">· {{ jugador.equipo }}</span>
                </span>
                <span class="puntos">{{ jugador.puntuacion }}</span>
            </li>
        </ol>
    </section>

    <section v-else class="podio-final" aria-label="Podio">
        <div class="fondo-blur" aria-hidden="true">
            <img :src="fondoBlur" alt="" decoding="async" />
            <div class="velo-blur" />
        </div>

        <header class="cabecera-final">
            <h2 class="titulo-final">{{ titulo }}</h2>
            <p v-if="!instantaneo" class="fase-final">{{ etiquetaFase }}</p>
        </header>

        <div class="top3" aria-live="polite">
            <article
                v-if="segundo"
                class="puesto-card plata-card"
                :class="{ visible: mostrarPuesto(2) }"
            >
                <span class="medalla">2</span>
                <FotoJugador :nombre="segundo.apodo" :foto="segundo.foto" tamano="lg" anillo="plata" />
                <p class="apodo">{{ segundo.apodo }}</p>
                <p class="pts">{{ segundo.puntuacion }} pts</p>
            </article>

            <article
                v-if="primero"
                class="puesto-card oro-card"
                :class="{ visible: mostrarPuesto(1), campeon: fase === 'primero' || fase === 'lista' }"
            >
                <span class="medalla">1</span>
                <FotoJugador :nombre="primero.apodo" :foto="primero.foto" tamano="xl" anillo="oro" />
                <p class="apodo">{{ primero.apodo }}</p>
                <p class="pts">{{ primero.puntuacion }} pts</p>
            </article>

            <article
                v-if="tercero"
                class="puesto-card bronce-card"
                :class="{ visible: mostrarPuesto(3) }"
            >
                <span class="medalla">3</span>
                <FotoJugador :nombre="tercero.apodo" :foto="tercero.foto" tamano="lg" anillo="bronce" />
                <p class="apodo">{{ tercero.apodo }}</p>
                <p class="pts">{{ tercero.puntuacion }} pts</p>
            </article>
        </div>

        <div v-if="faseEsLista && resto.length" class="lista-resto">
            <ol>
                <li
                    v-for="(jugador, indice) in resto"
                    :key="jugador.id"
                    class="fila-ranking"
                    :class="{ visible: indice + 3 < filasVisibles }"
                >
                    <span class="puesto">{{ indice + 4 }}</span>
                    <FotoJugador :nombre="jugador.apodo" :foto="jugador.foto" />
                    <span class="nombre">
                        {{ jugador.apodo }}
                        <span v-if="jugador.equipo" class="equipo">· {{ jugador.equipo }}</span>
                    </span>
                    <span class="puntos">{{ jugador.puntuacion }}</span>
                </li>
            </ol>
        </div>

        <div v-if="faseEsLista && podioEquipos?.length" class="lista-equipos">
            <h3>Equipos</h3>
            <div
                v-for="(equipo, indice) in podioEquipos"
                :key="equipo.id"
                class="fila-equipo"
            >
                <span class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full" :style="{ background: equipo.color }" />
                    <span>{{ indice + 1 }}. {{ equipo.nombre }}</span>
                </span>
                <span>{{ equipo.puntuacion }}</span>
            </div>
        </div>
    </section>
</template>

<style scoped>
.podio-compacto {
    display: flex;
    width: 100%;
    min-height: 0;
    flex: 1;
    flex-direction: column;
    gap: 0.55rem;
}

.titulo-compacto {
    margin: 0;
    flex-shrink: 0;
    text-align: center;
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    color: #f1f5f9;
}

.lista-compacta {
    display: flex;
    min-height: 0;
    flex: 1;
    flex-direction: column;
    gap: 0.35rem;
    overflow: auto;
    margin: 0;
    padding: 0;
    list-style: none;
    scrollbar-width: none;
}

.lista-compacta::-webkit-scrollbar {
    display: none;
}

.fila-compacta {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    border: 1px solid rgb(255 255 255 / 0.08);
    border-radius: 0.9rem;
    background: rgb(255 255 255 / 0.05);
    padding: 0.55rem 0.75rem;
}

.fila-compacta .puesto {
    display: grid;
    width: 1.6rem;
    height: 1.6rem;
    place-items: center;
    border-radius: 999px;
    background: rgb(255 255 255 / 0.08);
    font-size: 0.8rem;
    font-weight: 700;
    color: #94a3b8;
}

.fila-compacta .puesto.oro {
    background: rgb(251 191 36 / 0.25);
    color: #fde68a;
}

.fila-compacta .puesto.plata {
    background: rgb(203 213 225 / 0.2);
    color: #e2e8f0;
}

.fila-compacta .puesto.bronce {
    background: rgb(180 128 80 / 0.25);
    color: #fdba74;
}

.fila-compacta .nombre {
    min-width: 0;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #f1f5f9;
    font-size: 0.92rem;
}

.fila-compacta .equipo,
.equipo {
    color: #94a3b8;
}

.fila-compacta .puntos {
    flex-shrink: 0;
    font-family: var(--font-display);
    font-size: 0.9rem;
    font-weight: 700;
    color: #e2e8f0;
}

.podio-final {
    position: relative;
    display: flex;
    width: 100%;
    min-height: 0;
    flex: 1;
    flex-direction: column;
    align-items: center;
    gap: 0.85rem;
    overflow: hidden;
    border-radius: 1.5rem;
    padding: 0.85rem 0.75rem 1rem;
}

.fondo-blur {
    position: absolute;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    border-radius: inherit;
}

.fondo-blur img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
    transform: scale(1.12);
    filter: blur(22px) saturate(1.05);
}

.velo-blur {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg, rgb(6 11 24 / 0.55) 0%, rgb(6 11 24 / 0.72) 45%, rgb(6 11 24 / 0.9) 100%),
        radial-gradient(ellipse 70% 50% at 50% 20%, rgb(251 191 36 / 0.12), transparent);
}

.cabecera-final,
.top3,
.lista-resto,
.lista-equipos {
    position: relative;
    z-index: 1;
}

.cabecera-final {
    flex-shrink: 0;
    text-align: center;
}

.titulo-final {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.35rem, 4.5vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #f8fafc;
    text-shadow: 0 2px 18px rgb(0 0 0 / 0.45);
}

.fase-final {
    margin: 0.35rem 0 0;
    font-size: 0.95rem;
    color: #cbd5e1;
}

.top3 {
    display: flex;
    width: 100%;
    max-width: 34rem;
    flex: 1;
    min-height: 0;
    align-items: flex-end;
    justify-content: center;
    gap: 0.55rem;
    padding-bottom: 0.25rem;
}

.puesto-card {
    display: flex;
    width: 30%;
    max-width: 9rem;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    border: 1px solid rgb(255 255 255 / 0.12);
    border-radius: 1.25rem;
    background: rgb(15 23 42 / 0.45);
    padding: 0.85rem 0.55rem 1rem;
    backdrop-filter: blur(10px);
    opacity: 0;
    transform: translateY(2rem) scale(0.92);
    transition: opacity 0.55s ease, transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
}

.puesto-card.visible {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.oro-card {
    width: 38%;
    max-width: 11.5rem;
    border-color: rgb(251 191 36 / 0.45);
    background: rgb(251 191 36 / 0.12);
    padding-top: 1.1rem;
    padding-bottom: 1.25rem;
    margin-bottom: 0.75rem;
}

.plata-card {
    border-color: rgb(226 232 240 / 0.28);
    background: rgb(226 232 240 / 0.08);
}

.bronce-card {
    border-color: rgb(253 186 116 / 0.3);
    background: rgb(180 128 80 / 0.12);
}

.puesto-card.campeon {
    animation: pulso-campeon 0.9s ease;
}

.medalla {
    display: grid;
    width: 1.7rem;
    height: 1.7rem;
    place-items: center;
    border-radius: 999px;
    background: rgb(255 255 255 / 0.12);
    font-family: var(--font-display);
    font-size: 0.85rem;
    font-weight: 800;
    color: #f8fafc;
}

.oro-card .medalla {
    background: rgb(251 191 36 / 0.35);
    color: #fef3c7;
}

.plata-card .medalla {
    background: rgb(226 232 240 / 0.22);
    color: #f1f5f9;
}

.bronce-card .medalla {
    background: rgb(253 186 116 / 0.28);
    color: #ffedd5;
}

.apodo {
    margin: 0;
    max-width: 100%;
    overflow: hidden;
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 700;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #f8fafc;
}

.oro-card .apodo {
    font-size: 1.1rem;
}

.pts {
    margin: 0;
    font-size: 0.8rem;
    color: #cbd5e1;
}

.lista-resto {
    width: 100%;
    max-width: 28rem;
    min-height: 0;
    max-height: 28%;
    overflow: auto;
    border: 1px solid rgb(255 255 255 / 0.1);
    border-radius: 1.1rem;
    background: rgb(15 23 42 / 0.5);
    padding: 0.55rem;
    backdrop-filter: blur(8px);
    scrollbar-width: none;
}

.lista-resto::-webkit-scrollbar {
    display: none;
}

.lista-resto ol {
    margin: 0;
    padding: 0;
    list-style: none;
}

.fila-ranking {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    border-radius: 0.85rem;
    padding: 0.45rem 0.55rem;
    opacity: 0;
    transform: translateX(-0.6rem);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.fila-ranking.visible {
    opacity: 1;
    transform: translateX(0);
}

.fila-ranking .puesto {
    display: grid;
    width: 1.5rem;
    place-items: center;
    color: #94a3b8;
    font-size: 0.85rem;
    font-weight: 700;
}

.fila-ranking .nombre {
    min-width: 0;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #f1f5f9;
    font-size: 0.9rem;
}

.fila-ranking .puntos {
    flex-shrink: 0;
    font-family: var(--font-display);
    font-size: 0.85rem;
    font-weight: 700;
    color: #e2e8f0;
}

.lista-equipos {
    width: 100%;
    max-width: 28rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    border-radius: 1.1rem;
    background: rgb(15 23 42 / 0.5);
    padding: 0.75rem 1rem;
    backdrop-filter: blur(8px);
}

.lista-equipos h3 {
    margin: 0 0 0.55rem;
    text-align: center;
    font-size: 1rem;
    color: #f1f5f9;
}

.fila-equipo {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.4rem 0;
    color: #e2e8f0;
    font-size: 0.9rem;
}

@keyframes pulso-campeon {
    0% {
        transform: scale(0.94);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .puesto-card,
    .fila-ranking {
        opacity: 1;
        transform: none;
        transition: none;
        animation: none;
    }

    .fondo-blur img {
        filter: blur(14px);
        transform: none;
    }
}
</style>

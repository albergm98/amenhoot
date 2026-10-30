<script setup lang="ts">
import PantallaAmenhoot from '@/components/PantallaAmenhoot.vue'
import { type FichaBingo, useBingoBombo } from '@/composables/useBingoBombo'
import { useVozBingo } from '@/composables/useVozBingo'
import { Head, Link } from '@inertiajs/vue3'
import { computed, nextTick, onMounted, onUnmounted, ref, toRef, watch } from 'vue'

const SEGUNDOS_FOTO = 3

const props = defineProps<{
    imagenes: FichaBingo[]
}>()

const catalogo = toRef(props, 'imagenes')

const {
    sacadas,
    bolaActual,
    enMarcha,
    pausado,
    segundosEntreBolas,
    motivoPausa,
    quedan,
    terminado,
    total,
    idsSacados,
    reiniciar,
    empezar,
    pausar,
    reanudar,
    sacarSiguiente,
    alCantarBola,
} = useBingoBombo(() => catalogo.value)

const {
    silenciado,
    microActivo,
    microSoportado,
    microError,
    cantarImagen,
    cantarTexto,
    arrancarMicro,
    onPalabraClave,
    alternarSilencio,
} = useVozBingo()

/** Segundos de cartón a la vista tras difuminar la foto. */
const segundosCarton = ref(2)
const overlayOpaco = ref(false)
const imagenesListas = ref(0)
let temporizadorFundido: number | null = null

const cargandoImagenes = computed(
    () => props.imagenes.length > 0 && imagenesListas.value < props.imagenes.length,
)

const porcentajeCarga = computed(() =>
    props.imagenes.length === 0 ? 100 : Math.round((imagenesListas.value / props.imagenes.length) * 100),
)

const marcarImagenLista = () => {
    imagenesListas.value = Math.min(imagenesListas.value + 1, props.imagenes.length)
}

const precargarImagenes = () => {
    imagenesListas.value = 0
    if (props.imagenes.length === 0) {
        return
    }
    for (const ficha of props.imagenes) {
        const imagen = new Image()
        imagen.onload = marcarImagenLista
        imagen.onerror = marcarImagenLista
        imagen.src = ficha.url
    }
}

const sincronizarRitmo = () => {
    segundosEntreBolas.value = SEGUNDOS_FOTO + segundosCarton.value
}

watch(segundosCarton, sincronizarRitmo, { immediate: true })

const limpiarFundido = () => {
    if (temporizadorFundido !== null) {
        window.clearTimeout(temporizadorFundido)
        temporizadorFundido = null
    }
}

const programarFundido = () => {
    limpiarFundido()
    temporizadorFundido = window.setTimeout(() => {
        if (!pausado.value) {
            overlayOpaco.value = false
        }
        temporizadorFundido = null
    }, SEGUNDOS_FOTO * 1000)
}

const textoMotivo = computed(() => {
    if (motivoPausa.value === 'bingo') {
        return 'Han cantado BINGO'
    }
    if (motivoPausa.value === 'linea') {
        return 'Han cantado LÍNEA'
    }
    return null
})

const anunciarPausa = async (motivo: 'linea' | 'bingo') => {
    if (!enMarcha.value || pausado.value) {
        return
    }
    limpiarFundido()
    overlayOpaco.value = false
    pausar(motivo)
    await cantarTexto(motivo === 'bingo' ? 'Han cantado bingo' : 'Han cantado línea')
}

const etiquetaEstado = computed(() => {
    if (total.value === 0) {
        return 'Sin imágenes'
    }
    if (terminado.value) {
        return 'Fin'
    }
    if (pausado.value) {
        return textoMotivo.value ?? 'Pausa'
    }
    if (enMarcha.value) {
        return 'En marcha'
    }
    return 'Listo'
})

const porcentaje = computed(() => (total.value === 0 ? 0 : Math.round((sacadas.value.length / total.value) * 100)))

/** Mazo del overlay: la nueva tapa a las anteriores. */
const mazo = computed(() => sacadas.value.slice(-5))

const hayOverlay = computed(() => mazo.value.length > 0)

type ColumnaCarton = { clave: string; etiqueta: string; fichas: FichaBingo[] }

const columnasCarton = computed((): ColumnaCarton[] => {
    const mapa = new Map<string, ColumnaCarton>()
    for (const ficha of props.imagenes) {
        const existente = mapa.get(ficha.columna)
        if (existente) {
            existente.fichas.push(ficha)
            continue
        }
        mapa.set(ficha.columna, { clave: ficha.columna, etiqueta: ficha.etiqueta, fichas: [ficha] })
    }
    return [...mapa.values()]
})

const filasCarton = computed(() => Math.max(1, ...columnasCarton.value.map((columna) => columna.fichas.length)))

const estiloTablero = computed(() => ({
    '--cols': String(Math.max(columnasCarton.value.length, 1)),
    '--rows': String(filasCarton.value),
}))

alCantarBola(async (ficha) => {
    overlayOpaco.value = false
    await nextTick()
    overlayOpaco.value = true
    programarFundido()
    await cantarImagen(ficha.etiqueta)
})

onPalabraClave((motivo) => {
    void anunciarPausa(motivo)
})

onMounted(() => {
    precargarImagenes()
    arrancarMicro()
})

onUnmounted(() => {
    limpiarFundido()
})

const manejarEmpezar = async () => {
    if (cargandoImagenes.value) {
        return
    }
    if (!microActivo.value && microSoportado.value) {
        arrancarMicro()
    }
    await empezar()
}

const manejarSiguiente = async () => {
    if (pausado.value || quedan.value === 0) {
        return
    }
    await sacarSiguiente()
}

const manejarReiniciar = () => {
    limpiarFundido()
    overlayOpaco.value = false
    reiniciar()
    arrancarMicro()
}

const manejarReanudar = () => {
    reanudar()
    if (hayOverlay.value) {
        programarFundido()
    }
}
</script>

<template>
    <Head title="Bingo" />
    <PantallaAmenhoot :marca-arriba="false">
        <div class="sala">
            <header class="barra">
                <Link href="/" class="btn-ghost px-4 py-2 text-sm">Juegos</Link>
                <div class="barra-centro">
                    <p class="marca" aria-label="Bingo">Bingo</p>
                    <span class="conteo">{{ sacadas.length }} / {{ total }}</span>
                    <span class="estado">{{ etiquetaEstado }}</span>
                </div>
                <div class="barra-acciones">
                    <span class="chip" :class="microActivo ? 'ok' : 'aviso'">{{ microActivo ? 'Mic ON' : 'Mic OFF' }}</span>
                    <button class="btn-ghost px-4 py-2 text-sm" type="button" @click="alternarSilencio">
                        {{ silenciado ? 'Sonido' : 'Silencio' }}
                    </button>
                </div>
            </header>

            <div class="progreso" aria-hidden="true"><i :style="{ width: `${porcentaje}%` }" /></div>

            <p v-if="textoMotivo" class="aviso" role="status" aria-live="assertive">{{ textoMotivo }}</p>
            <p v-else-if="microError" class="aviso suave" role="status">{{ microError }}</p>

            <section class="carton" aria-label="Cartón a pantalla completa">
                <div v-if="cargandoImagenes" class="carga" role="status" aria-live="polite">
                    <p class="carga-titulo">Cargando fotos…</p>
                    <div class="carga-barra" aria-hidden="true"><i :style="{ width: `${porcentajeCarga}%` }" /></div>
                    <p class="carga-dato">{{ imagenesListas }} / {{ imagenes.length }}</p>
                </div>
                <div class="tablero-wrap" :class="{ oculto: cargandoImagenes }">
                    <div class="tablero" role="list" aria-label="Imágenes del bingo por columnas" :style="estiloTablero">
                        <p
                            v-for="(columna, indice) in columnasCarton"
                            :key="`t-${columna.clave}`"
                            class="columna-titulo"
                            :style="{ gridColumn: indice + 1, gridRow: 1 }"
                        >
                            {{ columna.etiqueta }}
                        </p>
                        <template v-for="(columna, indice) in columnasCarton" :key="columna.clave">
                            <div
                                v-for="(ficha, fila) in columna.fichas"
                                :key="ficha.id"
                                role="listitem"
                                class="casilla"
                                :class="{
                                    sacada: idsSacados.has(ficha.id),
                                    actual: bolaActual?.id === ficha.id,
                                }"
                                :style="{ gridColumn: indice + 1, gridRow: fila + 2 }"
                                :aria-label="`${columna.etiqueta} ${ficha.id}`"
                            >
                                <img :src="ficha.url" :alt="`${columna.etiqueta} ${ficha.id}`" decoding="async" />
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <div
                v-if="hayOverlay"
                class="overlay"
                :class="{ opaco: overlayOpaco, pausa: pausado }"
                role="dialog"
                aria-live="polite"
                :aria-label="bolaActual ? bolaActual.etiqueta : 'Última foto'"
            >
                <div class="overlay-velo" aria-hidden="true" />
                <div class="mazo">
                    <article
                        v-for="(ficha, indice) in mazo"
                        :key="ficha.id"
                        class="carta"
                        :class="{ cima: indice === mazo.length - 1 }"
                        :style="{ zIndex: indice + 1, '--i': indice - (mazo.length - 1) }"
                    >
                        <img :src="ficha.url" :alt="ficha.etiqueta" decoding="async" />
                        <span class="badge">{{ ficha.etiqueta }}</span>
                    </article>
                </div>
            </div>

            <footer class="muelle">
                <label class="velocidad">
                    Ritmo
                    <select v-model.number="segundosCarton" class="campo">
                        <option :value="2">3s foto + 2s cartón</option>
                        <option :value="3">3s foto + 3s cartón</option>
                        <option :value="4">3s foto + 4s cartón</option>
                        <option :value="5">3s foto + 5s cartón</option>
                    </select>
                </label>
                <div class="botonera">
                    <button
                        v-if="!enMarcha || terminado"
                        class="btn-cosmo"
                        type="button"
                        :disabled="cargandoImagenes || total === 0 || (terminado && quedan === 0 && sacadas.length > 0)"
                        @click="manejarEmpezar"
                    >
                        {{ cargandoImagenes ? 'Cargando…' : 'Empezar' }}
                    </button>
                    <button
                        v-if="enMarcha && !pausado && !terminado"
                        class="btn-cosmo"
                        type="button"
                        @click="pausar('manual')"
                    >
                        Pausar
                    </button>
                    <button v-if="pausado" class="btn-cosmo" type="button" @click="manejarReanudar">Reanudar</button>
                    <button
                        v-if="enMarcha && !pausado && !terminado"
                        class="btn-ghost"
                        type="button"
                        @click="anunciarPausa('linea')"
                    >
                        Línea
                    </button>
                    <button
                        v-if="enMarcha && !pausado && !terminado"
                        class="btn-ghost"
                        type="button"
                        @click="anunciarPausa('bingo')"
                    >
                        Bingo
                    </button>
                    <button
                        class="btn-ghost"
                        type="button"
                        :disabled="pausado || terminado || quedan === 0 || total === 0"
                        @click="manejarSiguiente"
                    >
                        Siguiente
                    </button>
                    <button class="btn-ghost" type="button" :disabled="total === 0" @click="manejarReiniciar">
                        Reiniciar
                    </button>
                </div>
            </footer>
        </div>
    </PantallaAmenhoot>
</template>

<style scoped>
.sala {
    position: relative;
    display: flex;
    width: 100%;
    height: 100dvh;
    max-height: 100dvh;
    flex-direction: column;
    gap: 0.3rem;
    overflow: hidden;
    padding: max(0.3rem, env(safe-area-inset-top)) 0.45rem max(0.3rem, env(safe-area-inset-bottom));
}

.barra {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    border-radius: 999px;
    background: rgb(15 23 42 / 0.45);
    padding: 0.3rem 0.4rem;
    backdrop-filter: blur(12px);
}

.barra-centro {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
}

.marca {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.1rem, 1.6vw, 1.35rem);
    letter-spacing: 0.06em;
    color: #f8fafc;
}

.conteo {
    font-family: var(--font-display);
    font-size: clamp(1rem, 1.5vw, 1.25rem);
    color: #e2e8f0;
}

.estado {
    font-size: 0.8rem;
    color: #94a3b8;
}

.barra-acciones {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.chip {
    border-radius: 999px;
    border: 1px solid rgb(255 255 255 / 0.14);
    padding: 0.25rem 0.65rem;
    font-size: 0.72rem;
}

.chip.ok {
    color: #6ee7b7;
    background: rgb(16 185 129 / 0.12);
}

.chip.aviso {
    color: #fcd34d;
    background: rgb(245 158 11 / 0.12);
}

.progreso {
    flex-shrink: 0;
    height: 0.25rem;
    overflow: hidden;
    border-radius: 999px;
    background: rgb(255 255 255 / 0.08);
}

.progreso i {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #38bdf8, #e2e8f0);
    transition: width 0.35s ease;
}

.aviso {
    flex-shrink: 0;
    margin: 0;
    border-radius: 0.85rem;
    border: 1px solid rgb(251 191 36 / 0.45);
    background: rgb(251 191 36 / 0.18);
    padding: 0.5rem 1rem;
    text-align: center;
    font-size: 1.1rem;
    color: #fde68a;
}

.aviso.suave {
    border-color: rgb(148 163 184 / 0.25);
    background: rgb(15 23 42 / 0.45);
    font-size: 0.85rem;
    color: #cbd5e1;
}

.carton {
    position: relative;
    display: flex;
    min-height: 0;
    flex: 1;
    flex-direction: column;
    overflow: hidden;
}

.carga {
    position: absolute;
    inset: 0;
    z-index: 5;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.85rem;
    background: rgb(6 11 24 / 0.72);
    backdrop-filter: blur(6px);
}

.carga-titulo {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.25rem, 2.5vw, 1.75rem);
    font-weight: 700;
    color: #f8fafc;
}

.carga-barra {
    width: min(18rem, 70%);
    height: 0.45rem;
    overflow: hidden;
    border-radius: 999px;
    background: rgb(255 255 255 / 0.12);
}

.carga-barra i {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #38bdf8, #e2e8f0);
    transition: width 0.2s ease;
}

.carga-dato {
    margin: 0;
    font-size: 0.9rem;
    color: #94a3b8;
}

.tablero-wrap {
    display: grid;
    min-height: 0;
    flex: 1;
    place-items: center;
    overflow: hidden;
    container-type: size;
}

.tablero-wrap.oculto {
    visibility: hidden;
}

.tablero {
    --g: 0.14rem;
    --titulo: 1.05rem;
    --celda: min(
        calc((100cqw - (var(--cols) - 1) * var(--g)) / var(--cols)),
        calc((100cqh - var(--titulo) - var(--rows) * var(--g)) / var(--rows))
    );
    display: grid;
    width: calc(var(--celda) * var(--cols) + var(--g) * (var(--cols) - 1));
    height: calc(var(--titulo) + var(--celda) * var(--rows) + var(--g) * var(--rows));
    grid-template-columns: repeat(var(--cols), var(--celda));
    grid-template-rows: var(--titulo) repeat(var(--rows), var(--celda));
    gap: var(--g);
}

.columna-titulo {
    margin: 0;
    align-self: end;
    text-align: center;
    font-size: clamp(0.65rem, 1vw, 0.85rem);
    line-height: 1;
    letter-spacing: 0.02em;
    color: #cbd5e1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.casilla {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    border-radius: 0.25rem;
    background: rgb(15 23 42 / 0.55);
    opacity: 0.38;
    filter: grayscale(0.4);
    transition:
        opacity 0.25s ease,
        filter 0.25s ease;
}

.casilla img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.casilla.sacada {
    opacity: 1;
    filter: none;
    outline: 2px solid rgb(56 189 248 / 0.55);
    outline-offset: -1px;
}

.casilla.actual {
    opacity: 1;
    filter: none;
    outline: 2px solid #fbbf24;
    outline-offset: -1px;
}

.overlay {
    position: absolute;
    inset: 0;
    z-index: 30;
    display: grid;
    place-items: center;
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transition:
        opacity 0.65s ease,
        visibility 0.65s ease;
}

.overlay.opaco {
    opacity: 1;
    visibility: visible;
}

.overlay-velo {
    position: absolute;
    inset: 0;
    background: rgb(6 11 24 / 0.55);
    backdrop-filter: blur(3px);
}

.mazo {
    position: relative;
    z-index: 1;
    width: min(72vmin, 34rem);
    aspect-ratio: 1;
}

.carta {
    position: absolute;
    inset: 0;
    overflow: hidden;
    border-radius: 1.35rem;
    border: 2px solid rgb(255 255 255 / 0.18);
    background: #0b1528;
    box-shadow: 0 24px 60px rgb(0 0 0 / 0.55);
    transform: translate(calc(var(--i) * 14px), calc(var(--i) * 12px)) scale(calc(1 + var(--i) * 0.035));
    opacity: calc(1 + var(--i) * 0.2);
    transition:
        transform 0.35s ease,
        opacity 0.35s ease;
}

.carta.cima {
    animation: entrar 0.45s ease;
}

.overlay.pausa .carta.cima {
    border-color: rgb(251 191 36 / 0.75);
}

.carta img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.badge {
    position: absolute;
    right: 1rem;
    bottom: 1rem;
    border-radius: 999px;
    border: 1px solid rgb(255 255 255 / 0.2);
    background: rgb(15 23 42 / 0.85);
    padding: 0.45rem 1rem;
    font-family: var(--font-display);
    font-size: clamp(1rem, 2.2vw, 1.35rem);
    color: #f8fafc;
    backdrop-filter: blur(8px);
}

@keyframes entrar {
    from {
        opacity: 0;
        transform: translate(28px, 36px) scale(1.08);
    }
    to {
        opacity: 1;
        transform: translate(0, 0) scale(1);
    }
}

.muelle {
    position: relative;
    z-index: 40;
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    border: 1px solid rgb(255 255 255 / 0.12);
    border-radius: 999px;
    background: rgb(6 11 24 / 0.88);
    padding: 0.3rem 0.4rem 0.3rem 0.85rem;
    backdrop-filter: blur(16px);
}

.velocidad {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    font-size: 0.85rem;
    color: #cbd5e1;
    white-space: nowrap;
}

.velocidad .campo {
    width: auto;
    min-width: 5rem;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    border-width: 1px;
    font-size: 0.85rem;
}

.botonera {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.4rem;
}

.botonera .btn-cosmo,
.botonera .btn-ghost {
    min-width: 7rem;
    padding-block: 0.55rem;
}
</style>

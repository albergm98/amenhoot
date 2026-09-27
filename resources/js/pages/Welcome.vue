<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { onMounted, onUnmounted, ref } from 'vue'

type EstadoPieza = 'oculta' | 'grande' | 'colocada'
type FaseFinal = 'collage' | 'logo' | 'listo'

const estados = ref<EstadoPieza[]>(['oculta', 'oculta', 'oculta', 'oculta'])
const faseFinal = ref<FaseFinal>('collage')
const tiempos: number[] = []

const piezas = [
    { nombre: 'Joseja', foto: '/assets/img/Joseja.jpg', clase: 'celda-joseja' },
    { nombre: 'Manu', foto: '/assets/img/Manu.jpg', clase: 'celda-manu' },
    { nombre: 'Pablo', foto: '/assets/img/Pablo.jpg', clase: 'celda-pablo' },
    { nombre: 'Nombre', foto: null, clase: 'celda-nombre' },
]

const programar = (ms: number, fn: () => void) => {
    tiempos.push(window.setTimeout(fn, ms))
}

const ponerEstado = (indice: number, estado: EstadoPieza) => {
    estados.value = estados.value.map((actual, i) => (i === indice ? estado : actual))
}

const precargarFotos = (): Promise<void> => {
    const urls = piezas.map((pieza) => pieza.foto).filter((foto): foto is string => Boolean(foto))

    return Promise.all(
        urls.map(
            (url) =>
                new Promise<void>((resolver) => {
                    const imagen = new Image()
                    imagen.onload = () => resolver()
                    imagen.onerror = () => resolver()
                    imagen.src = url
                }),
        ),
    ).then(() => undefined)
}

const iniciarSecuencia = () => {
    let marca = 0

    piezas.forEach((_, indice) => {
        programar(marca, () => {
            ponerEstado(indice, 'grande')
        })
        marca += 1400
        programar(marca, () => {
            ponerEstado(indice, 'colocada')
        })
        marca += 900
    })

    programar(marca + 500, () => {
        faseFinal.value = 'logo'
    })
    programar(marca + 2300, () => {
        faseFinal.value = 'listo'
    })
}

onMounted(async () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        estados.value = ['colocada', 'colocada', 'colocada', 'colocada']
        faseFinal.value = 'listo'
        return
    }

    await precargarFotos()
    // Un frame en negro limpio antes de la primera foto
    programar(120, iniciarSecuencia)
})

onUnmounted(() => {
    tiempos.forEach((tiempo) => window.clearTimeout(tiempo))
})
</script>

<template>
    <Head title="Amenhoot" />
    <div class="intro" :class="faseFinal">
        <div class="collage">
            <figure
                v-for="(pieza, indice) in piezas"
                :key="pieza.nombre"
                class="celda"
                :class="[pieza.clase, estados[indice]]"
            >
                <img
                    v-if="pieza.foto"
                    :src="pieza.foto"
                    :alt="pieza.nombre"
                    width="1024"
                    height="1024"
                    decoding="async"
                />
                <p v-else class="marca" aria-label="Amenhoot">
                    <span class="marca-amen">Amen</span><span class="marca-hoot">hoot</span>
                </p>
            </figure>
        </div>

        <div v-if="faseFinal === 'listo'" class="acciones">
            <Link href="/unirse" class="btn-cosmo">Unirme con PIN</Link>
            <Link href="/cuestionarios" class="btn-ghost">Soy anfitrión</Link>
        </div>
    </div>
</template>

<style scoped>
.intro {
    position: fixed;
    inset: 0;
    z-index: 1;
    overflow: hidden;
    background: #060b18;
}

.collage {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 2;
    overflow: hidden;
    background: #060b18;
    transition:
        top 1.4s cubic-bezier(0.22, 1, 0.36, 1),
        bottom 1.4s cubic-bezier(0.22, 1, 0.36, 1),
        border-radius 1.4s ease;
}

.logo .collage,
.listo .collage {
    top: 0.75rem;
    bottom: 10.5rem;
    border-radius: 0 0 1.25rem 1.25rem;
    box-shadow: 0 18px 40px rgb(0 0 0 / 0.45);
}

.celda {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 0;
    margin: 0;
    overflow: hidden;
    visibility: hidden;
    opacity: 0;
    background: #0b1528;
    pointer-events: none;
    /* Solo opacity al aparecer; la posición se anima al colocar */
    transition: opacity 0.45s ease;
}

.celda.grande {
    z-index: 10;
    visibility: visible;
    opacity: 1;
}

.celda.colocada {
    z-index: 1;
    visibility: visible;
    opacity: 1;
    transition:
        top 0.9s cubic-bezier(0.22, 1, 0.36, 1),
        right 0.9s cubic-bezier(0.22, 1, 0.36, 1),
        bottom 0.9s cubic-bezier(0.22, 1, 0.36, 1),
        left 0.9s cubic-bezier(0.22, 1, 0.36, 1),
        opacity 0.35s ease;
}

.celda-joseja.colocada {
    top: 0;
    right: 50.2%;
    bottom: 50.2%;
    left: 0;
}

.celda-manu.colocada {
    top: 0;
    right: 0;
    bottom: 50.2%;
    left: 50.2%;
}

.celda-pablo.colocada {
    top: 50.2%;
    right: 50.2%;
    bottom: 0;
    left: 0;
}

.celda-nombre.colocada {
    top: 50.2%;
    right: 0;
    bottom: 0;
    left: 50.2%;
}

.celda img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: 50% 22%;
}

.celda-nombre {
    display: grid;
    place-items: center;
    background: linear-gradient(145deg, #0b1528, #122038);
}

.marca {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(2.75rem, 14vw, 4.5rem);
    font-weight: 400;
    line-height: 1;
    letter-spacing: 0.02em;
    text-align: center;
    text-shadow:
        0 3px 0 rgb(0 0 0 / 0.45),
        0 8px 24px rgb(0 0 0 / 0.35);
    transition: font-size 0.85s cubic-bezier(0.22, 1, 0.36, 1);
}

.marca-amen {
    color: #f8fafc;
}

.marca-hoot {
    color: #94a3b8;
}

.celda-nombre.colocada .marca {
    font-size: clamp(1.6rem, 7.5vw, 2.35rem);
}

.acciones {
    position: fixed;
    bottom: max(1.5rem, env(safe-area-inset-bottom));
    left: 50%;
    z-index: 5;
    display: flex;
    width: min(92vw, 22rem);
    flex-direction: column;
    gap: 0.65rem;
    transform: translate(-50%, 0);
    animation: acciones-entrar 0.75s ease forwards;
}

.acciones :deep(a) {
    width: 100%;
    justify-content: center;
    padding-block: 0.9rem;
    font-size: 1rem;
}

@keyframes acciones-entrar {
    from {
        opacity: 0;
        transform: translate(-50%, 1rem);
    }
    to {
        opacity: 1;
        transform: translate(-50%, 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .celda {
        visibility: visible;
        opacity: 1;
        transition: none;
    }

    .collage {
        top: 0.75rem;
        bottom: 10.5rem;
        border-radius: 0 0 1.25rem 1.25rem;
    }

    .acciones {
        animation: none;
        opacity: 1;
    }
}
</style>

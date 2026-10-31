<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    fotos: string[]
}>()

const franjas = computed(() => {
    const fotos = props.fotos.length ? props.fotos : []
    if (fotos.length === 0) {
        return []
    }
    const grupos = [[], [], []] as string[][]
    fotos.forEach((foto, indice) => {
        grupos[indice % 3].push(foto)
    })
    return grupos.map((grupo) => (grupo.length ? grupo : fotos.slice(0, 6)))
})
</script>

<template>
    <div class="collage" aria-hidden="true">
        <div
            v-for="(franja, indice) in franjas"
            :key="indice"
            class="franja"
            :class="indice % 2 === 0 ? 'izquierda' : 'derecha'"
            :style="{ '--duracion': `${42 + indice * 8}s`, '--retraso': `${indice * -6}s` }"
        >
            <div class="cinta">
                <div v-for="vuelta in 2" :key="vuelta" class="tramo">
                    <img
                        v-for="(foto, i) in franja"
                        :key="`${vuelta}-${i}`"
                        :src="foto"
                        alt=""
                        loading="lazy"
                        decoding="async"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.collage {
    position: fixed;
    inset: -4%;
    z-index: 0;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    justify-content: center;
    pointer-events: none;
    transform: rotate(-6deg) scale(1.18);
}

.franja {
    overflow: hidden;
    flex: 1 1 0;
    min-height: 0;
}

.cinta {
    display: flex;
    width: max-content;
    height: 100%;
    gap: 0.75rem;
    animation: deslizar var(--duracion, 48s) linear infinite;
    animation-delay: var(--retraso, 0s);
}

.franja.derecha .cinta {
    animation-direction: reverse;
}

.tramo {
    display: flex;
    height: 100%;
    gap: 0.75rem;
}

.tramo img {
    height: 100%;
    width: auto;
    aspect-ratio: 3 / 4;
    object-fit: cover;
    border-radius: 0.85rem;
    box-shadow: 0 10px 28px rgb(0 0 0 / 0.35);
    opacity: 0.92;
}

@keyframes deslizar {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-50%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .cinta {
        animation: none;
        transform: translateX(-8%);
    }
}
</style>

<script setup lang="ts">
import { computed } from 'vue'

type Barra = {
    opcion_id: number
    texto: string
    total: number
    es_correcta?: boolean
}

const props = defineProps<{
    barras: Barra[]
}>()

const maximo = computed(() => Math.max(1, ...props.barras.map((barra) => barra.total)))
const colores = ['bg-opcion-a', 'bg-opcion-b', 'bg-opcion-c', 'bg-opcion-d']
const letras = ['A', 'B', 'C', 'D']
</script>

<template>
    <div class="rejilla-barras" role="list" aria-label="Resultado de respuestas">
        <div
            v-for="(barra, indice) in barras"
            :key="barra.opcion_id"
            class="tarjeta"
            :class="{ correcta: barra.es_correcta }"
            role="listitem"
        >
            <div class="cabecera">
                <span class="letra" :class="colores[indice % 4]">{{ letras[indice % 4] }}</span>
                <div class="info">
                    <span class="texto">{{ barra.texto }}</span>
                    <span v-if="barra.es_correcta" class="sello">Correcta</span>
                </div>
                <span class="votos">{{ barra.total }}</span>
            </div>
            <div class="pista">
                <div
                    class="relleno"
                    :class="barra.es_correcta ? 'bg-ok' : colores[indice % 4]"
                    :style="{ width: `${(barra.total / maximo) * 100}%` }"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.rejilla-barras {
    display: grid;
    flex-shrink: 0;
    grid-template-columns: 1fr 1fr;
    gap: 0.45rem;
}

.tarjeta {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    border-radius: 0.85rem;
    background: rgb(15 23 42 / 0.55);
    padding: 0.5rem 0.6rem 0.55rem;
    backdrop-filter: blur(8px);
}

.tarjeta.correcta {
    border-color: rgb(52 211 153 / 0.55);
    background: rgb(52 211 153 / 0.14);
    box-shadow: 0 0 0 1px rgb(52 211 153 / 0.2);
}

.cabecera {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 0.4rem;
}

.letra {
    display: inline-flex;
    width: 1.55rem;
    height: 1.55rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 0.4rem;
    font-size: 0.7rem;
    font-weight: 800;
    color: #fff;
}

.info {
    display: flex;
    min-width: 0;
    flex: 1;
    flex-direction: column;
    gap: 0.1rem;
}

.texto {
    overflow: hidden;
    font-size: 0.78rem;
    font-weight: 600;
    line-height: 1.15;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #f1f5f9;
}

.tarjeta.correcta .texto {
    color: #a7f3d0;
}

.sello {
    width: fit-content;
    border-radius: 999px;
    background: rgb(52 211 153 / 0.22);
    padding: 0.05rem 0.35rem;
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #6ee7b7;
}

.votos {
    flex-shrink: 0;
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 700;
    color: #e2e8f0;
}

.tarjeta.correcta .votos {
    color: #6ee7b7;
}

.pista {
    height: 0.28rem;
    overflow: hidden;
    border-radius: 999px;
    background: rgb(255 255 255 / 0.08);
}

.relleno {
    height: 100%;
    border-radius: inherit;
    transition: width 0.7s ease;
}

@media (max-width: 360px) {
    .rejilla-barras {
        grid-template-columns: 1fr;
    }
}
</style>

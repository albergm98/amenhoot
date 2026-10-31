<script setup lang="ts">
withDefaults(
    defineProps<{
        nombre: string
        foto?: string | null
        tamano?: 'sm' | 'md' | 'lg' | 'xl'
        anillo?: 'oro' | 'plata' | 'bronce' | null
    }>(),
    { tamano: 'sm', anillo: null },
)
</script>

<template>
    <span class="foto" :class="[tamano, anillo]" :aria-label="nombre">
        <img v-if="foto" :src="foto" :alt="nombre" loading="lazy" decoding="async" />
        <span v-else class="inicial">{{ nombre.slice(0, 1) }}</span>
    </span>
</template>

<style scoped>
.foto {
    display: inline-flex;
    flex-shrink: 0;
    overflow: hidden;
    border-radius: 999px;
    border: 2px solid rgb(226 232 240 / 0.35);
    background: rgb(15 23 42 / 0.55);
    color: #e2e8f0;
    font-family: var(--font-display);
    font-weight: 700;
    line-height: 1;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgb(0 0 0 / 0.35);
}

.foto.sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.85rem;
}

.foto.md {
    width: 2.75rem;
    height: 2.75rem;
    font-size: 1.1rem;
}

.foto.lg {
    width: 4.25rem;
    height: 4.25rem;
    font-size: 1.5rem;
    border-width: 3px;
}

.foto.xl {
    width: 6.5rem;
    height: 6.5rem;
    font-size: 2rem;
    border-width: 4px;
}

.foto.oro {
    border-color: #fbbf24;
    box-shadow: 0 0 0 3px rgb(251 191 36 / 0.35), 0 12px 32px rgb(0 0 0 / 0.4);
}

.foto.plata {
    border-color: #e2e8f0;
    box-shadow: 0 0 0 3px rgb(226 232 240 / 0.25), 0 10px 28px rgb(0 0 0 / 0.35);
}

.foto.bronce {
    border-color: #fdba74;
    box-shadow: 0 0 0 3px rgb(253 186 116 / 0.28), 0 10px 28px rgb(0 0 0 / 0.35);
}

.foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.inicial {
    text-transform: uppercase;
}

@media (max-width: 380px) {
    .foto.xl {
        width: 5.25rem;
        height: 5.25rem;
    }
}
</style>

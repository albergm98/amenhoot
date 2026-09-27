<script setup lang="ts">
withDefaults(
    defineProps<{
        marcaArriba?: boolean
    }>(),
    { marcaArriba: true },
)
</script>

<template>
    <div class="pantalla">
        <img
            class="fondo"
            src="/assets/img/fondo-amenhoot.jpg"
            alt=""
            width="1600"
            height="1067"
            decoding="async"
            aria-hidden="true"
        />
        <div class="velo" aria-hidden="true" />

        <header v-if="marcaArriba" class="cabecera-marca" aria-label="Amenhoot">
            <p class="marca">
                <span class="marca-amen">Amen</span><span class="marca-hoot">hoot</span>
            </p>
        </header>

        <div class="capa" :class="{ 'con-marca': marcaArriba }">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.pantalla {
    position: relative;
    min-height: 100dvh;
    overflow-x: hidden;
    background: #060b18;
}

.fondo {
    position: fixed;
    inset: 0;
    z-index: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    pointer-events: none;
}

.velo {
    position: fixed;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    background:
        linear-gradient(180deg, rgb(6 11 24 / 0.62) 0%, rgb(6 11 24 / 0.84) 45%, rgb(6 11 24 / 0.95) 100%),
        radial-gradient(ellipse 70% 40% at 50% 0%, rgb(148 163 184 / 0.12), transparent);
}

.cabecera-marca {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 4;
    display: flex;
    justify-content: center;
    padding: max(0.55rem, env(safe-area-inset-top)) 1rem 0.55rem;
    pointer-events: none;
    background: linear-gradient(180deg, rgb(6 11 24 / 0.55), transparent);
}

.marca {
    margin: 0;
    padding: 0.35rem 1.1rem;
    border: 1px solid rgb(255 255 255 / 0.14);
    border-radius: 999px;
    background: rgb(15 23 42 / 0.55);
    backdrop-filter: blur(10px);
    font-family: var(--font-display);
    font-size: clamp(0.95rem, 3.4vw, 1.15rem);
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

.capa {
    position: relative;
    z-index: 2;
    min-height: 100dvh;
}

.capa.con-marca {
    padding-top: max(3rem, calc(env(safe-area-inset-top) + 2.35rem));
}
</style>
